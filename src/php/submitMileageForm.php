<?php
// Public, unauthenticated endpoint for the driver-facing mileage submission
// page. Mirrors addJobApplication.php's pattern: no login required, drivers
// identify themselves by typing their name (same trust model as the paper
// form process it replaces). Submissions land directly in mileage_entries
// so they show up in the existing Admin Mileage Review screen.

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, X-Auth-Token, Authorization, Accept');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Only expose the real error detail to localhost dev origins -- keeps
// production responses generic while making it possible to diagnose a
// failure without needing direct access to the server's error log.
$requestOrigin = strtolower(trim((string) ($_SERVER['HTTP_ORIGIN'] ?? '')));
$isLocalOrigin = preg_match('/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $requestOrigin) === 1;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/messageRoutingHelper.php';

const MILEAGE_UPLOAD_MAX_BYTES = 15 * 1024 * 1024; // 15MB
const MILEAGE_UPLOAD_ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
const MILEAGE_DEFAULT_RATE = 0.30;
const MILEAGE_DEFAULT_THRESHOLD = 10.0;

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function strv($value, string $fallback = ''): string
{
    $text = trim((string) ($value ?? ''));
    return $text === '' ? $fallback : $text;
}

function numv($value, float $fallback = 0.0): float
{
    return is_numeric($value) ? (float) $value : $fallback;
}

function miles(float $value): float
{
    return round($value, 2);
}

function getSubmissionWeek(string $dateValue): array
{
    $timestamp = strtotime($dateValue . ' 12:00:00');
    if ($timestamp === false) {
        $timestamp = time();
    }
    $day = (int) date('w', $timestamp);
    $daysSinceWednesday = ($day + 4) % 7;
    $start = strtotime("-{$daysSinceWednesday} days", $timestamp);
    $end = strtotime('+6 days', $start);
    return [
        'weekStart' => date('Y-m-d', $start),
        'weekEnd' => date('Y-m-d', $end),
    ];
}

function mileageRateAndThreshold(mysqli $conn): array
{
    $rate = MILEAGE_DEFAULT_RATE;
    $threshold = MILEAGE_DEFAULT_THRESHOLD;
    $result = $conn->query("SELECT setting_key, setting_value FROM mileage_settings WHERE setting_key IN ('mileage_rate','threshold_miles')");
    if ($result !== false) {
        while ($row = $result->fetch_assoc()) {
            if ($row['setting_key'] === 'mileage_rate') $rate = (float) $row['setting_value'];
            if ($row['setting_key'] === 'threshold_miles') $threshold = (float) $row['setting_value'];
        }
    }
    return [$rate, $threshold];
}

function ensurePublicSubmissionColumns(mysqli $conn): void
{
    $columns = [
        'source' => "ALTER TABLE mileage_entries ADD COLUMN source ENUM('office','driver_portal') NOT NULL DEFAULT 'office' AFTER driver_name",
        'submitter_phone' => "ALTER TABLE mileage_entries ADD COLUMN submitter_phone VARCHAR(40) NULL AFTER source",
        'submitter_email' => "ALTER TABLE mileage_entries ADD COLUMN submitter_email VARCHAR(191) NULL AFTER submitter_phone",
        'photo_path' => "ALTER TABLE mileage_entries ADD COLUMN photo_path VARCHAR(255) NULL AFTER submitter_email",
        // Raw odometer readings as written on the paper form. Recorded for
        // reference only -- claimed_mileage/odometer_start/odometer_end
        // (used by the payable-mileage calculation) are always driven by
        // the "Mileage" figure the driver enters directly, per the office's
        // existing paper-form review process. These are not processed.
        'driver_odometer_start' => "ALTER TABLE mileage_entries ADD COLUMN driver_odometer_start DECIMAL(10,2) NULL AFTER photo_path",
        'driver_odometer_end' => "ALTER TABLE mileage_entries ADD COLUMN driver_odometer_end DECIMAL(10,2) NULL AFTER driver_odometer_start",
        'vehicle_registration' => "ALTER TABLE mileage_entries ADD COLUMN vehicle_registration VARCHAR(20) NULL AFTER driver_odometer_end",
        'colleague_name' => "ALTER TABLE mileage_entries ADD COLUMN colleague_name VARCHAR(190) NULL AFTER vehicle_registration",
        'run_name' => "ALTER TABLE mileage_entries ADD COLUMN run_name VARCHAR(190) NULL AFTER colleague_name",
    ];
    foreach ($columns as $columnName => $alterSql) {
        $check = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mileage_entries' AND COLUMN_NAME = '{$columnName}' LIMIT 1");
        if ($check !== false && $check->num_rows > 0) {
            continue;
        }
        // Column already applied via server/db/mileage_public_submissions.sql on
        // most installs; this is a fallback so the form still works if that
        // migration hasn't been run yet.
        @$conn->query($alterSql);
    }
}

// Lightweight, read-only lookups for the mileage form's Colleague/Run
// dropdowns. Self-contained (no dependency on mileage.php) since this file
// is the public, unauthenticated entrypoint; only returns active names, no
// address/phone/email.
function ensureCarerDirectoryTableExists(mysqli $conn): void
{
    @$conn->query("CREATE TABLE IF NOT EXISTS carer_directory (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        driver_name VARCHAR(190) NOT NULL,
        home_address VARCHAR(255) NOT NULL DEFAULT '',
        notes VARCHAR(255) NULL DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_carer_directory_name (driver_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function listActiveCarerNames(mysqli $conn): array
{
    ensureCarerDirectoryTableExists($conn);
    $names = [];
    $result = $conn->query('SELECT driver_name FROM carer_directory WHERE is_active = 1 ORDER BY driver_name ASC');
    if ($result !== false) {
        while ($row = $result->fetch_assoc()) {
            $names[] = (string) $row['driver_name'];
        }
    }
    return $names;
}

function ensureRunsTableExists(mysqli $conn): void
{
    @$conn->query("CREATE TABLE IF NOT EXISTS mileage_runs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_mileage_runs_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function listActiveRunNames(mysqli $conn): array
{
    ensureRunsTableExists($conn);
    $names = [];
    $result = $conn->query('SELECT name FROM mileage_runs WHERE is_active = 1 ORDER BY name ASC');
    if ($result !== false) {
        while ($row = $result->fetch_assoc()) {
            $names[] = (string) $row['name'];
        }
    }
    return $names;
}

function uploadErrorMessage(int $errorCode): string
{
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'That photo is too large for the server to accept. Please try a smaller photo, or take it at a lower resolution.';
        case UPLOAD_ERR_PARTIAL:
            return 'The photo upload was interrupted. Please check your connection and try again.';
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
            return 'The server could not store the uploaded photo. Please contact the office.';
        default:
            return 'Photo upload failed. Please try again.';
    }
}

function saveUploadedPhoto(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, ''];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return [null, uploadErrorMessage((int) ($file['error'] ?? UPLOAD_ERR_OK))];
    }
    if ((int) ($file['size'] ?? 0) > MILEAGE_UPLOAD_MAX_BYTES) {
        return [null, 'Photo is too large (max 15MB).'];
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = (string) finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    }
    if ($mime === '' || !in_array($mime, MILEAGE_UPLOAD_ALLOWED_MIME, true)) {
        return [null, 'Please upload a JPEG, PNG, WEBP, or HEIC photo.'];
    }

    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
    ];
    $extension = $extensionMap[$mime] ?? 'jpg';

    $uploadDir = __DIR__ . '/uploads/mileage';
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0750, true) && !is_dir($uploadDir)) {
        return [null, 'Server could not store the uploaded photo.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return [null, 'Server could not store the uploaded photo.'];
    }

    return ['mileage/' . $filename, ''];
}

$action = strv($_GET['action'] ?? 'submit');

// Read-only lookups for the form's Colleague/Run dropdowns -- no login, GET,
// return active names only (no address/phone/email).
if ($action === 'listCarers' || $action === 'listRuns') {
    try {
        $conn = createDatabaseConnection('auto');
        if ($action === 'listCarers') {
            jsonResponse(['success' => true, 'names' => listActiveCarerNames($conn)]);
        }
        jsonResponse(['success' => true, 'names' => listActiveRunNames($conn)]);
    } catch (Throwable $exception) {
        error_log('Unable to load mileage form lookups: ' . $exception->getMessage());
        jsonResponse(['success' => false, 'message' => 'Unable to load list right now.', 'names' => []], 500);
    }
}

if ($action !== 'submit' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 400);
}

// If the request body was larger than PHP's post_max_size, PHP silently
// drops *all* POST fields and files -- not just the oversized one -- with
// no $_FILES error code to catch. Detect that signature explicitly so the
// driver gets an accurate message instead of "please enter your name".
$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
    jsonResponse([
        'success' => false,
        'message' => 'That photo is too large for the server to accept. Please try a smaller photo, or take it at a lower resolution.',
    ], 413);
}

$submissionType = strtolower(strv($_POST['submissionType'] ?? 'single'));
if (!in_array($submissionType, ['single', 'photo'], true)) {
    $submissionType = 'single';
}

$driverName = strv($_POST['driverName'] ?? '');
$phone = strv($_POST['phone'] ?? '');
$email = strv($_POST['email'] ?? '');
$workDate = strv($_POST['workDate'] ?? date('Y-m-d'));
$mileageClaimed = numv($_POST['mileage'] ?? 0);
$notes = strv($_POST['notes'] ?? '');
// Optional, "recorded but not processed" fields matching the paper form.
$startingLocation = strv($_POST['startingLocation'] ?? '');
$destination = strv($_POST['destination'] ?? '');
$driverOdometerStartRaw = strv($_POST['driverOdometerStart'] ?? '');
$driverOdometerEndRaw = strv($_POST['driverOdometerEnd'] ?? '');
$driverOdometerStart = $driverOdometerStartRaw === '' ? null : numv($driverOdometerStartRaw);
$driverOdometerEnd = $driverOdometerEndRaw === '' ? null : numv($driverOdometerEndRaw);
$vehicleRegistrationRaw = strv($_POST['vehicleRegistration'] ?? '');
$vehicleRegistration = $vehicleRegistrationRaw === '' ? null : strtoupper($vehicleRegistrationRaw);
// Colleague and run can differ per entry (different day, different pairing),
// so they're captured per row, not once for the whole batch.
$colleagueNameRaw = strv($_POST['colleagueName'] ?? '');
$colleagueName = $colleagueNameRaw === '' ? null : $colleagueNameRaw;
$runNameRaw = strv($_POST['runName'] ?? '');
$runName = $runNameRaw === '' ? null : $runNameRaw;

if ($driverName === '') {
    jsonResponse(['success' => false, 'message' => 'Please enter your name.'], 422);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Please provide a valid email address, or leave it blank.'], 422);
}
if ($submissionType === 'single') {
    if (strtotime($workDate) === false) {
        jsonResponse(['success' => false, 'message' => 'Please provide a valid date.'], 422);
    }
    if ($mileageClaimed <= 0) {
        jsonResponse(['success' => false, 'message' => 'Please enter the mileage for that day.'], 422);
    }
} elseif (empty($_FILES['photo']['name'])) {
    jsonResponse(['success' => false, 'message' => 'Please attach a photo of your mileage form.'], 422);
}

try {
    $conn = createDatabaseConnection('auto');
    ensurePublicSubmissionColumns($conn);

    $photoPath = null;
    if ($submissionType === 'photo' || !empty($_FILES['photo']['name'])) {
        [$photoPath, $photoError] = saveUploadedPhoto($_FILES['photo'] ?? []);
        if ($photoError !== '') {
            $conn->close();
            jsonResponse(['success' => false, 'message' => $photoError], 422);
        }
    }

    [$rate, $threshold] = mileageRateAndThreshold($conn);
    $week = getSubmissionWeek($workDate);

    if ($submissionType === 'single') {
        $claimed = miles(max(0, $mileageClaimed));
        // No "Access"/system mileage figure is captured from the driver
        // portal, so the entry always lands in pending_review -- the office
        // fills in the expected system mileage during their normal review,
        // same as they would from a paper form.
        $adminStatus = 'pending_review';
        $thresholdFlag = 1;
    } else {
        // Photo-only submission: no numeric claim yet, office transcribes
        // it from the attached photo during review.
        $claimed = 0.0;
        $adminStatus = 'pending_review';
        $thresholdFlag = 0;
    }

    $stmt = $conn->prepare('INSERT INTO mileage_entries (
        user_id, driver_name, source, submitter_phone, submitter_email, photo_path,
        driver_odometer_start, driver_odometer_end, vehicle_registration, colleague_name, run_name,
        work_date, submission_week_start, submission_week_end,
        starting_location, ending_location, odometer_start, odometer_end,
        claimed_mileage, adjusted_claimed_mileage, threshold_flag, explanation_required,
        driver_explanation, admin_status, mileage_rate, notes, submitted_at
    ) VALUES (0, ?, \'driver_portal\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');

    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare mileage submission.'], 500);
    }

    $explanationRequired = $submissionType === 'single' ? 1 : 0;
    $stmt->bind_param(
        'ssssddssssssssdddiissds',
        $driverName,
        $phone,
        $email,
        $photoPath,
        $driverOdometerStart,
        $driverOdometerEnd,
        $vehicleRegistration,
        $colleagueName,
        $runName,
        $workDate,
        $week['weekStart'],
        $week['weekEnd'],
        $startingLocation,
        $destination,
        $claimed,
        $claimed,
        $claimed,
        $thresholdFlag,
        $explanationRequired,
        $notes,
        $adminStatus,
        $rate,
        $notes
    );

    if (!$stmt->execute()) {
        $message = $stmt->error ? 'Failed to save mileage submission: ' . $stmt->error : 'Failed to save mileage submission.';
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => $message], 500);
    }

    $newId = (int) $stmt->insert_id;
    $stmt->close();

    $notification = safeDispatchCategoryNotification($conn, MESSAGE_CATEGORY_MILEAGE_SUBMISSIONS, [
        'submittedAt' => date('Y-m-d H:i:s'),
        'driverName' => $driverName,
        'phone' => $phone,
        'email' => $email,
        'submissionType' => $submissionType === 'photo' ? 'Photo of paper form' : 'Single day entry',
        'workDate' => $workDate,
        'mileage' => $submissionType === 'single' ? (string) $claimed : 'See attached photo',
        'hasPhoto' => $photoPath !== null ? 'Yes' : 'No',
        'replyToEmail' => $email,
        'replyToName' => $driverName,
    ]);

    $conn->close();

    jsonResponse([
        'success' => true,
        'id' => $newId,
        'message' => 'Thanks -- your mileage has been submitted for office review.',
        'notification' => $notification,
    ]);
} catch (Throwable $exception) {
    error_log('Unable to save mileage submission: ' . $exception->getMessage());
    $payload = [
        'success' => false,
        'message' => 'Unable to save your submission right now. Please try again shortly.',
    ];
    if ($isLocalOrigin) {
        $payload['debugError'] = $exception->getMessage();
        $payload['debugErrorFile'] = $exception->getFile() . ':' . $exception->getLine();
    }
    jsonResponse($payload, 500);
}
