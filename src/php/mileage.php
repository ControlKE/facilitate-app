<?php
$allowedOrigins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'https://facilitatecareservices.co.uk',
    'https://www.facilitatecareservices.co.uk',
];

$requestOrigin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
$normalizedRequestOrigin = strtolower($requestOrigin);
$isLocalOrigin = preg_match('/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $normalizedRequestOrigin) === 1;
$isAllowedOrigin = $isLocalOrigin || in_array($normalizedRequestOrigin, $allowedOrigins, true);

header_remove('Access-Control-Allow-Origin');
header('Access-Control-Allow-Origin: ' . (($requestOrigin !== '' && $isAllowedOrigin) ? $requestOrigin : 'http://localhost:5173'));
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, X-Auth-Token, Authorization, Accept');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once __DIR__ . '/db.php';

const DEFAULT_MILEAGE_RATE = 0.30;
const DEFAULT_THRESHOLD_MILES = 10.0;

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function readPayload(): array
{
    $raw = file_get_contents('php://input');
    $decoded = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : [];
    return is_array($decoded) ? $decoded : [];
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

function intv($value, int $fallback = 0): int
{
    return is_numeric($value) ? (int) $value : $fallback;
}

function boolv($value): bool
{
    if (is_bool($value)) {
        return $value;
    }
    return in_array(strtolower(strv($value)), ['1', 'true', 'yes', 'y'], true);
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

function rowToEntry(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'userId' => (int) $row['user_id'],
        'driverName' => (string) $row['driver_name'],
        'source' => (string) ($row['source'] ?? 'office'),
        'submitterPhone' => (string) ($row['submitter_phone'] ?? ''),
        'submitterEmail' => (string) ($row['submitter_email'] ?? ''),
        'photoPath' => $row['photo_path'] ?? null,
        'driverOdometerStart' => isset($row['driver_odometer_start']) ? (float) $row['driver_odometer_start'] : null,
        'driverOdometerEnd' => isset($row['driver_odometer_end']) ? (float) $row['driver_odometer_end'] : null,
        'workDate' => (string) $row['work_date'],
        'submissionWeekStart' => (string) $row['submission_week_start'],
        'submissionWeekEnd' => (string) $row['submission_week_end'],
        'startingLocation' => (string) $row['starting_location'],
        'endingLocation' => (string) $row['ending_location'],
        'odometerStart' => (float) $row['odometer_start'],
        'odometerEnd' => (float) $row['odometer_end'],
        'claimedMileage' => (float) $row['claimed_mileage'],
        'expectedSystemMileage' => (float) $row['expected_system_mileage'],
        'accessRunTotalMileage' => isset($row['access_run_total_mileage']) ? (float) $row['access_run_total_mileage'] : null,
        'homeToFirstClientMileage' => isset($row['home_to_first_client_mileage']) ? (float) $row['home_to_first_client_mileage'] : null,
        'lastClientToHomeMileage' => isset($row['last_client_to_home_mileage']) ? (float) $row['last_client_to_home_mileage'] : null,
        'colleagueAddress' => (string) ($row['colleague_address'] ?? ''),
        'isHalfDaySwap' => ((int) ($row['is_half_day_swap'] ?? 0)) === 1,
        'middayColleagueSwapMileage' => isset($row['midday_colleague_swap_mileage']) ? (float) $row['midday_colleague_swap_mileage'] : null,
        'middayDropoffColleagueAddress' => (string) ($row['midday_dropoff_colleague_address'] ?? ''),
        'middayPickupColleagueAddress' => (string) ($row['midday_pickup_colleague_address'] ?? ''),
        'verifiedAt' => $row['verified_at'] ?? null,
        'passengerPickupMileage' => (float) $row['passenger_pickup_mileage'],
        'middayPayableMileage' => (float) $row['midday_payable_mileage'],
        'middayMileageReason' => (string) ($row['midday_mileage_reason'] ?? ''),
        'lunchHomeMileageDeduction' => (float) $row['lunch_home_mileage_deduction'],
        'wentHomeForLunch' => ((int) $row['went_home_for_lunch']) === 1,
        'adjustedClaimedMileage' => (float) $row['adjusted_claimed_mileage'],
        'differenceFromSystem' => (float) $row['difference_from_system'],
        'thresholdFlag' => ((int) $row['threshold_flag']) === 1,
        'explanationRequired' => ((int) $row['explanation_required']) === 1,
        'driverExplanation' => (string) ($row['driver_explanation'] ?? ''),
        'adminStatus' => (string) $row['admin_status'],
        'adminAdjustedPayableMileage' => $row['admin_adjusted_payable_mileage'] === null ? null : (float) $row['admin_adjusted_payable_mileage'],
        'finalPayableMileage' => $row['final_payable_mileage'] === null ? null : (float) $row['final_payable_mileage'],
        'mileageRate' => (float) $row['mileage_rate'],
        'finalPayableAmount' => $row['final_payable_amount'] === null ? null : (float) $row['final_payable_amount'],
        'notes' => (string) ($row['notes'] ?? ''),
        'adminNotes' => (string) ($row['admin_notes'] ?? ''),
        'submittedAt' => $row['submitted_at'],
        'reviewedAt' => $row['reviewed_at'],
        'createdAt' => $row['created_at'],
        'updatedAt' => $row['updated_at'],
    ];
}

function getSettings(mysqli $conn): array
{
    $settings = [
        'mileage_rate' => '0.30',
        'threshold_miles' => '10',
        'week_starts_on' => 'wednesday',
        'submission_due_day' => 'tuesday',
        'payment_window' => 'thursday-friday',
    ];
    $rows = [];
    $result = $conn->query('SELECT setting_key, setting_value, description FROM mileage_settings ORDER BY setting_key ASC');
    if ($result !== false) {
        while ($row = $result->fetch_assoc()) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
            $rows[] = [
                'settingKey' => (string) $row['setting_key'],
                'settingValue' => (string) $row['setting_value'],
                'description' => (string) ($row['description'] ?? ''),
            ];
        }
    }
    return [
        'mileageRate' => (float) $settings['mileage_rate'],
        'thresholdMiles' => (float) $settings['threshold_miles'],
        'weekStartsOn' => (string) $settings['week_starts_on'],
        'submissionDueDay' => (string) $settings['submission_due_day'],
        'paymentWindow' => (string) $settings['payment_window'],
        'rows' => $rows,
    ];
}

function calculateEntry(array $payload, array $settings, ?string $status = null): array
{
    $odometerStart = numv($payload['odometerStart'] ?? 0);
    $odometerEnd = numv($payload['odometerEnd'] ?? 0);
    if ($odometerEnd < $odometerStart) {
        throw new RuntimeException('Odometer end must be greater than or equal to odometer start.');
    }
    $claimed = miles(max(0, $odometerEnd - $odometerStart));
    $expected = miles(max(0, numv($payload['expectedSystemMileage'] ?? 0)));
    $pickup = miles(max(0, numv($payload['passengerPickupMileage'] ?? 0)));
    $midday = miles(max(0, numv($payload['middayPayableMileage'] ?? 0)));
    $deduction = min(max(0, numv($payload['lunchHomeMileageDeduction'] ?? 0)), $claimed);
    $adjusted = miles(max(0, $claimed - $deduction + $midday));
    $expectedTotal = miles($expected + $pickup);
    $difference = miles($adjusted - $expectedTotal);
    $threshold = max(0, (float) ($settings['thresholdMiles'] ?? DEFAULT_THRESHOLD_MILES));
    $flag = $adjusted > ($expectedTotal + $threshold);
    $adminStatus = $status ?: ($flag ? 'pending_review' : 'draft');
    $finalMileage = $flag ? null : max($adjusted, $expectedTotal);
    if ($adminStatus === 'rejected') {
        $finalMileage = 0;
    }
    $rate = (float) ($settings['mileageRate'] ?? DEFAULT_MILEAGE_RATE);
    return [
        'claimedMileage' => $claimed,
        'expectedSystemMileage' => $expected,
        'passengerPickupMileage' => $pickup,
        'middayPayableMileage' => $midday,
        'lunchHomeMileageDeduction' => $deduction,
        'adjustedClaimedMileage' => $adjusted,
        'differenceFromSystem' => $difference,
        'thresholdFlag' => $flag,
        'explanationRequired' => $flag,
        'adminStatus' => $adminStatus,
        'finalPayableMileage' => $finalMileage,
        'mileageRate' => $rate,
        'finalPayableAmount' => $finalMileage === null ? null : round($finalMileage * $rate, 2),
    ];
}

function getEntry(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM mileage_entries WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    return $row ? rowToEntry($row) : null;
}

function listEntries(mysqli $conn): array
{
    $where = ['deleted_at IS NULL'];
    $types = '';
    $params = [];
    if (isset($_GET['userId']) && $_GET['userId'] !== '') {
        $where[] = 'user_id = ?';
        $types .= 'i';
        $params[] = intv($_GET['userId']);
    }
    if (isset($_GET['weekStart']) && $_GET['weekStart'] !== '') {
        $where[] = 'submission_week_start >= ?';
        $types .= 's';
        $params[] = strv($_GET['weekStart']);
    }
    if (isset($_GET['weekEnd']) && $_GET['weekEnd'] !== '') {
        $where[] = 'submission_week_end <= ?';
        $types .= 's';
        $params[] = strv($_GET['weekEnd']);
    }
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $where[] = 'admin_status = ?';
        $types .= 's';
        $params[] = strv($_GET['status']);
    }
    if (boolv($_GET['flaggedOnly'] ?? false)) {
        $where[] = 'threshold_flag = 1';
    }
    if (isset($_GET['driver']) && $_GET['driver'] !== '') {
        $where[] = 'driver_name LIKE ?';
        $types .= 's';
        $params[] = '%' . strv($_GET['driver']) . '%';
    }
    if (isset($_GET['source']) && $_GET['source'] !== '') {
        $where[] = 'source = ?';
        $types .= 's';
        $params[] = strv($_GET['source']);
    }
    $sql = 'SELECT * FROM mileage_entries WHERE ' . implode(' AND ', $where) . ' ORDER BY work_date DESC, id DESC LIMIT 500';
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = rowToEntry($row);
    }
    return $rows;
}

function saveEntry(mysqli $conn, array $payload): array
{
    $settings = getSettings($conn);
    $id = intv($payload['id'] ?? 0);
    $existing = $id > 0 ? getEntry($conn, $id) : null;
    $workDate = strv($payload['workDate'] ?? date('Y-m-d'));
    $week = getSubmissionWeek($workDate);
    $calc = calculateEntry($payload, $settings, $existing['adminStatus'] ?? null);
    if ($calc['explanationRequired'] && strv($payload['driverExplanation'] ?? '') === '') {
        throw new RuntimeException('Driver explanation is required when mileage is above threshold.');
    }

    $values = [
        intv($payload['userId'] ?? 1, 1),
        strv($payload['driverName'] ?? 'Current User', 'Current User'),
        $workDate,
        $week['weekStart'],
        $week['weekEnd'],
        strv($payload['startingLocation'] ?? ''),
        strv($payload['endingLocation'] ?? ''),
        numv($payload['odometerStart'] ?? 0),
        numv($payload['odometerEnd'] ?? 0),
        $calc['claimedMileage'],
        $calc['expectedSystemMileage'],
        $calc['passengerPickupMileage'],
        $calc['middayPayableMileage'],
        strv($payload['middayMileageReason'] ?? ''),
        $calc['lunchHomeMileageDeduction'],
        boolv($payload['wentHomeForLunch'] ?? false) ? 1 : 0,
        $calc['adjustedClaimedMileage'],
        $calc['differenceFromSystem'],
        $calc['thresholdFlag'] ? 1 : 0,
        $calc['explanationRequired'] ? 1 : 0,
        strv($payload['driverExplanation'] ?? ''),
        $calc['adminStatus'],
        $calc['finalPayableMileage'],
        $calc['mileageRate'],
        $calc['finalPayableAmount'],
        strv($payload['notes'] ?? ''),
    ];

    if ($id > 0) {
        $sql = "UPDATE mileage_entries SET
            user_id=?, driver_name=?, work_date=?, submission_week_start=?, submission_week_end=?,
            starting_location=?, ending_location=?, odometer_start=?, odometer_end=?, claimed_mileage=?,
            expected_system_mileage=?, passenger_pickup_mileage=?, midday_payable_mileage=?, midday_mileage_reason=?,
            lunch_home_mileage_deduction=?, went_home_for_lunch=?, adjusted_claimed_mileage=?, difference_from_system=?,
            threshold_flag=?, explanation_required=?, driver_explanation=?, admin_status=?, final_payable_mileage=?,
            mileage_rate=?, final_payable_amount=?, notes=?
            WHERE id=? AND deleted_at IS NULL";
        $values[] = $id;
        $stmt = $conn->prepare($sql);
        $types = 'issssssddddddsdiddiissdddsi';
        $stmt->bind_param($types, ...$values);
    } else {
        $sql = "INSERT INTO mileage_entries (
            user_id, driver_name, work_date, submission_week_start, submission_week_end,
            starting_location, ending_location, odometer_start, odometer_end, claimed_mileage,
            expected_system_mileage, passenger_pickup_mileage, midday_payable_mileage, midday_mileage_reason,
            lunch_home_mileage_deduction, went_home_for_lunch, adjusted_claimed_mileage, difference_from_system,
            threshold_flag, explanation_required, driver_explanation, admin_status, final_payable_mileage,
            mileage_rate, final_payable_amount, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $types = 'issssssddddddsdiddiissddds';
        $stmt->bind_param($types, ...$values);
    }
    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error ?: 'Failed to save mileage entry.');
    }
    $entryId = $id > 0 ? $id : (int) $conn->insert_id;
    return getEntry($conn, $entryId) ?: [];
}

function ensureCarerDirectoryTable(mysqli $conn): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS carer_directory (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_name VARCHAR(190) NOT NULL,
    home_address VARCHAR(255) NOT NULL DEFAULT '',
    notes VARCHAR(255) NULL DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_carer_directory_name (driver_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
    // Best-effort fallback if server/db/mileage_verification_workflow.sql
    // hasn't been run yet -- mirrors the pattern used elsewhere in this file.
    @$conn->query($sql);

    // Structured bio-data columns (first/last name, broken-out address).
    // driver_name/home_address stay populated (composed from these) so
    // every other lookup in the app that matches/reads on those two
    // columns keeps working unchanged.
    $columns = [
        'first_name' => "ALTER TABLE carer_directory ADD COLUMN first_name VARCHAR(100) NOT NULL DEFAULT '' AFTER driver_name",
        'last_name' => "ALTER TABLE carer_directory ADD COLUMN last_name VARCHAR(100) NOT NULL DEFAULT '' AFTER first_name",
        'address_line1' => "ALTER TABLE carer_directory ADD COLUMN address_line1 VARCHAR(190) NULL AFTER home_address",
        'address_line2' => "ALTER TABLE carer_directory ADD COLUMN address_line2 VARCHAR(190) NULL AFTER address_line1",
        'town_city' => "ALTER TABLE carer_directory ADD COLUMN town_city VARCHAR(120) NULL AFTER address_line2",
        'county' => "ALTER TABLE carer_directory ADD COLUMN county VARCHAR(120) NULL AFTER town_city",
        'postcode' => "ALTER TABLE carer_directory ADD COLUMN postcode VARCHAR(20) NULL AFTER county",
    ];
    foreach ($columns as $columnName => $alterSql) {
        $check = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'carer_directory' AND COLUMN_NAME = '{$columnName}' LIMIT 1");
        if ($check !== false && $check->num_rows > 0) {
            continue;
        }
        @$conn->query($alterSql);
    }
}

function listCarers(mysqli $conn): array
{
    ensureCarerDirectoryTable($conn);
    $result = $conn->query('SELECT id, driver_name, first_name, last_name, home_address, address_line1, address_line2, town_city, county, postcode, notes, is_active FROM carer_directory ORDER BY driver_name ASC');
    $rows = [];
    if ($result !== false) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                'id' => (int) $row['id'],
                'driverName' => (string) $row['driver_name'],
                'firstName' => (string) ($row['first_name'] ?? ''),
                'lastName' => (string) ($row['last_name'] ?? ''),
                'homeAddress' => (string) $row['home_address'],
                'addressLine1' => (string) ($row['address_line1'] ?? ''),
                'addressLine2' => (string) ($row['address_line2'] ?? ''),
                'townCity' => (string) ($row['town_city'] ?? ''),
                'county' => (string) ($row['county'] ?? ''),
                'postcode' => (string) ($row['postcode'] ?? ''),
                'notes' => (string) ($row['notes'] ?? ''),
                'isActive' => ((int) $row['is_active']) === 1,
            ];
        }
    }
    return $rows;
}

function saveCarer(mysqli $conn, array $payload): array
{
    ensureCarerDirectoryTable($conn);
    $id = intv($payload['id'] ?? 0);
    $firstName = strv($payload['firstName'] ?? '');
    $lastName = strv($payload['lastName'] ?? '');
    if ($firstName === '' || $lastName === '') {
        throw new RuntimeException('First name and last name are required.');
    }
    $driverName = trim($firstName . ' ' . $lastName);

    $addressLine1 = strv($payload['addressLine1'] ?? '');
    $addressLine2 = strv($payload['addressLine2'] ?? '');
    $townCity = strv($payload['townCity'] ?? '');
    $county = strv($payload['county'] ?? '');
    $postcode = strv($payload['postcode'] ?? '');
    // home_address stays populated as a composed one-line string so the
    // rest of the app (e.g. the verification screen's address lookup)
    // keeps working without needing to know about the individual parts.
    $homeAddress = implode(', ', array_filter([$addressLine1, $addressLine2, $townCity, $county, $postcode], fn ($part) => $part !== ''));

    $notes = strv($payload['notes'] ?? '');
    $isActive = boolv($payload['isActive'] ?? true) ? 1 : 0;

    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE carer_directory SET driver_name = ?, first_name = ?, last_name = ?, home_address = ?, address_line1 = ?, address_line2 = ?, town_city = ?, county = ?, postcode = ?, notes = ?, is_active = ? WHERE id = ?');
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare carer update.');
        }
        $stmt->bind_param(
            'ssssssssssii',
            $driverName,
            $firstName,
            $lastName,
            $homeAddress,
            $addressLine1,
            $addressLine2,
            $townCity,
            $county,
            $postcode,
            $notes,
            $isActive,
            $id
        );
    } else {
        $stmt = $conn->prepare('INSERT INTO carer_directory (driver_name, first_name, last_name, home_address, address_line1, address_line2, town_city, county, postcode, notes, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), home_address = VALUES(home_address), address_line1 = VALUES(address_line1), address_line2 = VALUES(address_line2), town_city = VALUES(town_city), county = VALUES(county), postcode = VALUES(postcode), notes = VALUES(notes), is_active = VALUES(is_active)');
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare carer insert.');
        }
        $stmt->bind_param(
            'ssssssssssi',
            $driverName,
            $firstName,
            $lastName,
            $homeAddress,
            $addressLine1,
            $addressLine2,
            $townCity,
            $county,
            $postcode,
            $notes,
            $isActive
        );
    }
    if (!$stmt->execute()) {
        $message = $stmt->error ?: 'Failed to save carer.';
        $stmt->close();
        throw new RuntimeException($message);
    }
    $stmt->close();
    return ['success' => true];
}

function deleteCarer(mysqli $conn, int $id): void
{
    ensureCarerDirectoryTable($conn);
    $stmt = $conn->prepare('DELETE FROM carer_directory WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

function verifyEntry(mysqli $conn, int $id, array $payload): ?array
{
    $entry = getEntry($conn, $id);
    if ($entry === null) {
        return null;
    }

    $accessRunTotal = numv($payload['accessRunTotalMileage'] ?? 0);
    $homeToFirstClient = numv($payload['homeToFirstClientMileage'] ?? 0);
    $lastClientToHome = numv($payload['lastClientToHomeMileage'] ?? 0);
    $colleagueAddress = strv($payload['colleagueAddress'] ?? '');
    $isHalfDaySwap = !empty($payload['isHalfDaySwap']) ? 1 : 0;
    $middayColleagueSwap = $isHalfDaySwap ? numv($payload['middayColleagueSwapMileage'] ?? 0) : 0.0;
    $middayDropoffColleagueAddress = $isHalfDaySwap ? strv($payload['middayDropoffColleagueAddress'] ?? '') : '';
    $middayPickupColleagueAddress = $isHalfDaySwap ? strv($payload['middayPickupColleagueAddress'] ?? '') : '';

    // expected_system_mileage is the sum of the Access route total plus the
    // home<->work commute legs Access doesn't account for, plus the midday
    // colleague-swap leg on half-day handover shifts (dropping the morning
    // colleague home and collecting the afternoon colleague on the way back
    // out) -- also not on the Access route. The existing passenger_pickup_mileage
    // field (the optional flat allowance) is added on top of this below, same
    // as it already was.
    $expectedSystemMileage = miles(max(0, $accessRunTotal + $homeToFirstClient + $lastClientToHome + $middayColleagueSwap));
    $expectedTotal = miles($expectedSystemMileage + $entry['passengerPickupMileage']);
    $differenceFromSystem = miles($entry['adjustedClaimedMileage'] - $expectedTotal);

    $settings = getSettings($conn);
    $threshold = max(0, (float) ($settings['thresholdMiles'] ?? DEFAULT_THRESHOLD_MILES));
    $thresholdFlag = $entry['adjustedClaimedMileage'] > ($expectedTotal + $threshold);

    $stmt = $conn->prepare('UPDATE mileage_entries SET
        access_run_total_mileage = ?, home_to_first_client_mileage = ?, last_client_to_home_mileage = ?,
        colleague_address = ?, is_half_day_swap = ?, midday_colleague_swap_mileage = ?,
        midday_dropoff_colleague_address = ?, midday_pickup_colleague_address = ?,
        expected_system_mileage = ?, difference_from_system = ?,
        threshold_flag = ?, explanation_required = ?, admin_status = \'pending_manager_approval\', verified_at = NOW()
        WHERE id = ? AND deleted_at IS NULL');
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare verification query.');
    }
    $thresholdFlagInt = $thresholdFlag ? 1 : 0;
    $stmt->bind_param(
        'dddsidssddiii',
        $accessRunTotal,
        $homeToFirstClient,
        $lastClientToHome,
        $colleagueAddress,
        $isHalfDaySwap,
        $middayColleagueSwap,
        $middayDropoffColleagueAddress,
        $middayPickupColleagueAddress,
        $expectedSystemMileage,
        $differenceFromSystem,
        $thresholdFlagInt,
        $thresholdFlagInt,
        $id
    );
    if (!$stmt->execute()) {
        $message = $stmt->error ?: 'Failed to save verification.';
        $stmt->close();
        throw new RuntimeException($message);
    }
    $stmt->close();

    return getEntry($conn, $id);
}

function reviewEntry(mysqli $conn, int $id, array $payload): ?array
{
    $entry = getEntry($conn, $id);
    if ($entry === null) {
        return null;
    }

    $status = strv($payload['status'] ?? 'approved');
    if (!in_array($status, ['approved', 'rejected', 'adjusted'], true)) {
        $status = 'approved';
    }
    $adminNotes = strv($payload['adminNotes'] ?? '');

    if ($status === 'rejected') {
        $finalMileage = 0.0;
    } elseif ($status === 'adjusted') {
        $finalMileage = numv($payload['adminAdjustedPayableMileage'] ?? $entry['adjustedClaimedMileage']);
    } else {
        $expectedTotal = $entry['expectedSystemMileage'] + $entry['passengerPickupMileage'];
        $finalMileage = max($entry['adjustedClaimedMileage'], $expectedTotal);
    }
    $finalMileage = miles(max(0, $finalMileage));
    $finalAmount = round($finalMileage * $entry['mileageRate'], 2);
    $adjustedParam = $status === 'adjusted' ? $finalMileage : null;

    $stmt = $conn->prepare('UPDATE mileage_entries SET
        admin_status = ?, admin_adjusted_payable_mileage = ?, final_payable_mileage = ?,
        final_payable_amount = ?, admin_notes = ?, reviewed_at = NOW()
        WHERE id = ? AND deleted_at IS NULL');
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare review query.');
    }
    $stmt->bind_param('sdddsi', $status, $adjustedParam, $finalMileage, $finalAmount, $adminNotes, $id);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException($stmt->error ?: 'Failed to save mileage review.');
    }
    $stmt->close();

    return getEntry($conn, $id);
}

function weekSubmissionSummary(mysqli $conn, int $userId, string $weekStart, string $weekEnd): array
{
    $stmt = $conn->prepare('SELECT
        ROUND(COALESCE(SUM(claimed_mileage),0),2) AS claimed,
        ROUND(COALESCE(SUM(adjusted_claimed_mileage),0),2) AS adjusted,
        ROUND(COALESCE(SUM(expected_system_mileage + passenger_pickup_mileage),0),2) AS expected,
        ROUND(COALESCE(SUM(final_payable_mileage),0),2) AS payable,
        ROUND(COALESCE(SUM(final_payable_amount),0),2) AS amount,
        SUM(threshold_flag) AS flaggedCount
      FROM mileage_entries
      WHERE user_id = ? AND submission_week_start = ? AND submission_week_end = ? AND deleted_at IS NULL');
    $stmt->bind_param('iss', $userId, $weekStart, $weekEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return [
        'claimed' => (float) ($row['claimed'] ?? 0),
        'adjusted' => (float) ($row['adjusted'] ?? 0),
        'expected' => (float) ($row['expected'] ?? 0),
        'payable' => (float) ($row['payable'] ?? 0),
        'amount' => (float) ($row['amount'] ?? 0),
        'flaggedCount' => (int) ($row['flaggedCount'] ?? 0),
    ];
}

function submitWeek(mysqli $conn, int $userId, string $driverName, string $weekStart, string $weekEnd): array
{
    $stmt = $conn->prepare("UPDATE mileage_entries
        SET admin_status = IF(threshold_flag = 1, 'pending_review', 'submitted'),
            submitted_at = COALESCE(submitted_at, NOW())
        WHERE user_id = ? AND submission_week_start = ? AND submission_week_end = ?
          AND admin_status = 'draft' AND deleted_at IS NULL");
    $stmt->bind_param('iss', $userId, $weekStart, $weekEnd);
    $stmt->execute();
    $updatedCount = $stmt->affected_rows;
    $stmt->close();

    $summary = weekSubmissionSummary($conn, $userId, $weekStart, $weekEnd);

    $stmt = $conn->prepare("INSERT INTO mileage_submissions (
        user_id, driver_name, week_start, week_end, status,
        total_claimed_mileage, total_adjusted_claimed_mileage, total_expected_system_mileage,
        total_final_payable_mileage, total_payable_amount, flagged_count, submitted_at
    ) VALUES (?, ?, ?, ?, 'submitted', ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        status = 'submitted',
        total_claimed_mileage = VALUES(total_claimed_mileage),
        total_adjusted_claimed_mileage = VALUES(total_adjusted_claimed_mileage),
        total_expected_system_mileage = VALUES(total_expected_system_mileage),
        total_final_payable_mileage = VALUES(total_final_payable_mileage),
        total_payable_amount = VALUES(total_payable_amount),
        flagged_count = VALUES(flagged_count),
        submitted_at = NOW()");
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare weekly submission query.');
    }
    $stmt->bind_param(
        'isssdddddi',
        $userId,
        $driverName,
        $weekStart,
        $weekEnd,
        $summary['claimed'],
        $summary['adjusted'],
        $summary['expected'],
        $summary['payable'],
        $summary['amount'],
        $summary['flaggedCount']
    );
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException($stmt->error ?: 'Failed to save weekly submission.');
    }
    $stmt->close();

    return ['updatedCount' => $updatedCount, 'summary' => $summary];
}

function reviewWeek(mysqli $conn, int $userId, string $weekStart, string $weekEnd, array $payload): array
{
    $status = strv($payload['status'] ?? 'approved');
    if (!in_array($status, ['approved', 'rejected'], true)) {
        $status = 'approved';
    }
    $adminNotes = strv($payload['adminNotes'] ?? '');

    $stmt = $conn->prepare("SELECT id FROM mileage_entries
        WHERE user_id = ? AND submission_week_start = ? AND submission_week_end = ?
          AND admin_status IN ('submitted','pending_review') AND deleted_at IS NULL");
    $stmt->bind_param('iss', $userId, $weekStart, $weekEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int) $row['id'];
    }
    $stmt->close();

    foreach ($ids as $entryId) {
        reviewEntry($conn, $entryId, ['status' => $status, 'adminNotes' => $adminNotes]);
    }

    $stmt = $conn->prepare('UPDATE mileage_submissions SET status = ? WHERE user_id = ? AND week_start = ? AND week_end = ?');
    if ($stmt !== false) {
        $stmt->bind_param('siss', $status, $userId, $weekStart, $weekEnd);
        $stmt->execute();
        $stmt->close();
    }

    return ['reviewedCount' => count($ids), 'status' => $status];
}

function submitEntry(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("UPDATE mileage_entries SET admin_status = IF(threshold_flag = 1, 'pending_review', 'submitted'), submitted_at = COALESCE(submitted_at, NOW()) WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return getEntry($conn, $id);
}

function weeklyBreakdown(mysqli $conn): array
{
    $week = getSubmissionWeek(strv($_GET['weekStart'] ?? $_GET['date'] ?? date('Y-m-d')));
    $where = ['deleted_at IS NULL', 'submission_week_start = ?', 'submission_week_end = ?'];
    $types = 'ss';
    $params = [$week['weekStart'], $week['weekEnd']];
    if (isset($_GET['driver']) && $_GET['driver'] !== '') {
        $where[] = 'driver_name LIKE ?';
        $types .= 's';
        $params[] = '%' . strv($_GET['driver']) . '%';
    }
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $where[] = 'admin_status = ?';
        $types .= 's';
        $params[] = strv($_GET['status']);
    }
    if (boolv($_GET['flaggedOnly'] ?? false)) {
        $where[] = 'threshold_flag = 1';
    }
    if (boolv($_GET['pendingOnly'] ?? false)) {
        $where[] = "(admin_status = 'pending_review' OR explanation_required = 1)";
    }
    $whereSql = implode(' AND ', $where);
    $sql = "SELECT
        user_id AS userId, driver_name AS driverName, COUNT(*) AS entryCount,
        ROUND(COALESCE(SUM(claimed_mileage),0),2) AS claimedMileageTotal,
        ROUND(COALESCE(SUM(expected_system_mileage),0),2) AS accessMileageTotal,
        ROUND(COALESCE(SUM(passenger_pickup_mileage),0),2) AS passengerPickupMileageTotal,
        ROUND(COALESCE(SUM(midday_payable_mileage),0),2) AS middayPayableMileageTotal,
        ROUND(COALESCE(SUM(expected_system_mileage + passenger_pickup_mileage),0),2) AS expectedTotalMileage,
        ROUND(COALESCE(SUM(expected_system_mileage + passenger_pickup_mileage - claimed_mileage),0),2) AS mileageDifference,
        ROUND(COALESCE(SUM(final_payable_mileage),0),2) AS finalPayableMileageTotal,
        ROUND(COALESCE(SUM(final_payable_amount),0),2) AS finalPayableAmountTotal,
        ROUND(COALESCE(AVG(mileage_rate),0.30),2) AS rate,
        SUM(threshold_flag) AS flaggedCount,
        SUM(admin_status = 'pending_review') AS pendingReviewCount,
        SUM(explanation_required) AS explanationRequiredCount,
        SUM(admin_status IN ('approved','adjusted')) AS readyCount,
        SUM(admin_status = 'rejected') AS rejectedCount
      FROM mileage_entries WHERE {$whereSql}
      GROUP BY user_id, driver_name ORDER BY driver_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = [];
    while ($row = $result->fetch_assoc()) {
        $key = ((int) $row['userId']) . '|' . strtolower(trim((string) $row['driverName']));
        $entryCount = (int) $row['entryCount'];
        $needs = ((int) $row['pendingReviewCount']) > 0 || ((int) $row['explanationRequiredCount']) > 0;
        $ready = ((int) $row['readyCount']) + ((int) $row['rejectedCount']);
        $summary[$key] = [
            'userId' => (int) $row['userId'],
            'driverName' => (string) $row['driverName'],
            'entryCount' => $entryCount,
            'claimedMileageTotal' => (float) $row['claimedMileageTotal'],
            'accessMileageTotal' => (float) $row['accessMileageTotal'],
            'passengerPickupMileageTotal' => (float) $row['passengerPickupMileageTotal'],
            'middayPayableMileageTotal' => (float) $row['middayPayableMileageTotal'],
            'expectedTotalMileage' => (float) $row['expectedTotalMileage'],
            'mileageDifference' => (float) $row['mileageDifference'],
            'finalPayableMileageTotal' => (float) $row['finalPayableMileageTotal'],
            'finalPayableAmountTotal' => (float) $row['finalPayableAmountTotal'],
            'rate' => (float) $row['rate'],
            'flaggedCount' => (int) $row['flaggedCount'],
            'pendingReviewCount' => (int) $row['pendingReviewCount'],
            'explanationRequiredCount' => (int) $row['explanationRequiredCount'],
            'weeklyStatus' => $needs ? 'needs_review' : ($entryCount > 0 && $ready === $entryCount ? 'ready' : 'mixed'),
            'entries' => [],
        ];
    }

    $stmt = $conn->prepare("SELECT *, ROUND(expected_system_mileage + passenger_pickup_mileage, 2) AS expected_total_mileage, ROUND(expected_system_mileage + passenger_pickup_mileage - claimed_mileage, 2) AS manager_mileage_difference FROM mileage_entries WHERE {$whereSql} ORDER BY driver_name ASC, work_date ASC, id ASC");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $entry = rowToEntry($row);
        $entry['expectedTotalMileage'] = (float) $row['expected_total_mileage'];
        $entry['managerMileageDifference'] = (float) $row['manager_mileage_difference'];
        $key = ((int) $row['user_id']) . '|' . strtolower(trim((string) $row['driver_name']));
        if (isset($summary[$key])) {
            $summary[$key]['entries'][] = $entry;
        }
    }

    $rows = array_values($summary);
    $totals = [
        'totalCarers' => count($rows),
        'entryCount' => 0,
        'claimedMileageTotal' => 0,
        'accessMileageTotal' => 0,
        'passengerPickupMileageTotal' => 0,
        'middayPayableMileageTotal' => 0,
        'expectedTotalMileage' => 0,
        'mileageDifference' => 0,
        'finalPayableMileageTotal' => 0,
        'finalPayableAmountTotal' => 0,
        'flaggedCount' => 0,
        'pendingReviewCount' => 0,
    ];
    $statusCounts = [];
    foreach ($rows as $row) {
        foreach ($totals as $key => $value) {
            if ($key !== 'totalCarers' && isset($row[$key])) {
                $totals[$key] = round((float) $totals[$key] + (float) $row[$key], 2);
            }
        }
        $statusCounts[$row['weeklyStatus']] = ($statusCounts[$row['weeklyStatus']] ?? 0) + 1;
    }
    return ['week' => $week, 'rows' => $rows, 'totals' => $totals, 'statusCounts' => $statusCounts];
}

function weeklyReport(mysqli $conn): array
{
    $where = ['deleted_at IS NULL'];
    $types = '';
    $params = [];
    if (isset($_GET['weekStart']) && $_GET['weekStart'] !== '') {
        $where[] = 'submission_week_start >= ?';
        $types .= 's';
        $params[] = strv($_GET['weekStart']);
    }
    if (isset($_GET['weekEnd']) && $_GET['weekEnd'] !== '') {
        $where[] = 'submission_week_end <= ?';
        $types .= 's';
        $params[] = strv($_GET['weekEnd']);
    }
    if (isset($_GET['driver']) && $_GET['driver'] !== '') {
        $where[] = 'driver_name LIKE ?';
        $types .= 's';
        $params[] = '%' . strv($_GET['driver']) . '%';
    }
    $whereSql = implode(' AND ', $where);
    $sql = "SELECT
        user_id AS userId, driver_name AS driverName,
        submission_week_start AS weekStart, submission_week_end AS weekEnd,
        COUNT(*) AS entryCount,
        ROUND(COALESCE(SUM(claimed_mileage),0),2) AS totalClaimedMileage,
        ROUND(COALESCE(SUM(adjusted_claimed_mileage),0),2) AS totalAdjustedClaimedMileage,
        ROUND(COALESCE(SUM(expected_system_mileage + passenger_pickup_mileage),0),2) AS totalExpectedSystemMileage,
        ROUND(COALESCE(SUM(final_payable_mileage),0),2) AS totalFinalPayableMileage,
        ROUND(COALESCE(SUM(final_payable_amount),0),2) AS totalPayableAmount,
        SUM(threshold_flag) AS flaggedCount
      FROM mileage_entries WHERE {$whereSql}
      GROUP BY user_id, driver_name, submission_week_start, submission_week_end
      ORDER BY submission_week_start DESC, driver_name ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare weekly report query.');
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'userId' => (int) $row['userId'],
            'driverName' => (string) $row['driverName'],
            'weekStart' => (string) $row['weekStart'],
            'weekEnd' => (string) $row['weekEnd'],
            'entryCount' => (int) $row['entryCount'],
            'totalClaimedMileage' => (float) $row['totalClaimedMileage'],
            'totalAdjustedClaimedMileage' => (float) $row['totalAdjustedClaimedMileage'],
            'totalExpectedSystemMileage' => (float) $row['totalExpectedSystemMileage'],
            'totalFinalPayableMileage' => (float) $row['totalFinalPayableMileage'],
            'totalPayableAmount' => (float) $row['totalPayableAmount'],
            'flaggedCount' => (int) $row['flaggedCount'],
        ];
    }
    return $rows;
}

function updateSettings(mysqli $conn, array $payload): array
{
    $items = [
        ['mileage_rate', numv($payload['mileageRate'] ?? 0.30, 0.30), 'Default payable mileage rate in GBP per mile.'],
        ['threshold_miles', numv($payload['thresholdMiles'] ?? 10, 10), 'Miles above expected route mileage before explanation and review are required.'],
        ['week_starts_on', strv($payload['weekStartsOn'] ?? 'wednesday', 'wednesday'), 'Mileage submission week starts on this day.'],
        ['submission_due_day', strv($payload['submissionDueDay'] ?? 'tuesday', 'tuesday'), 'Weekly mileage forms are submitted on this day.'],
        ['payment_window', strv($payload['paymentWindow'] ?? 'thursday-friday', 'thursday-friday'), 'Mileage payment window.'],
    ];
    $stmt = $conn->prepare('INSERT INTO mileage_settings (setting_key, setting_value, description, updated_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = VALUES(description), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP');
    foreach ($items as $item) {
        $key = $item[0];
        $value = (string) $item[1];
        $description = $item[2];
        $updatedBy = intv($payload['updatedBy'] ?? 0) ?: null;
        $stmt->bind_param('sssi', $key, $value, $description, $updatedBy);
        $stmt->execute();
    }
    return getSettings($conn);
}

try {
    $action = strv($_GET['action'] ?? 'list');
    $payload = readPayload();
    $conn = createDatabaseConnection($_GET['source'] ?? 'auto');

    if ($action === 'list') jsonResponse(['success' => true, 'entries' => listEntries($conn)]);
    if ($action === 'get') {
        $entry = getEntry($conn, intv($_GET['id'] ?? $payload['id'] ?? 0));
        jsonResponse($entry ? ['success' => true, 'entry' => $entry] : ['success' => false, 'message' => 'Mileage entry not found.'], $entry ? 200 : 404);
    }
    if ($action === 'save') jsonResponse(['success' => true, 'entry' => saveEntry($conn, $payload)]);
    if ($action === 'delete') {
        $id = intv($_GET['id'] ?? $payload['id'] ?? 0);
        $stmt = $conn->prepare('UPDATE mileage_entries SET deleted_at = NOW() WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        jsonResponse(['success' => true]);
    }
    if ($action === 'submit') jsonResponse(['success' => true, 'entry' => submitEntry($conn, intv($_GET['id'] ?? $payload['id'] ?? 0))]);
    if ($action === 'review') {
        $id = intv($_GET['id'] ?? $payload['id'] ?? 0);
        $entry = reviewEntry($conn, $id, $payload);
        jsonResponse($entry ? ['success' => true, 'entry' => $entry] : ['success' => false, 'message' => 'Mileage entry not found.'], $entry ? 200 : 404);
    }
    if ($action === 'verify') {
        $id = intv($_GET['id'] ?? $payload['id'] ?? 0);
        $entry = verifyEntry($conn, $id, $payload);
        jsonResponse($entry ? ['success' => true, 'entry' => $entry] : ['success' => false, 'message' => 'Mileage entry not found.'], $entry ? 200 : 404);
    }
    if ($action === 'listCarers') jsonResponse(['success' => true, 'carers' => listCarers($conn)]);
    if ($action === 'saveCarer') jsonResponse(saveCarer($conn, $payload) + ['carers' => listCarers($conn)]);
    if ($action === 'deleteCarer') {
        deleteCarer($conn, intv($_GET['id'] ?? $payload['id'] ?? 0));
        jsonResponse(['success' => true, 'carers' => listCarers($conn)]);
    }
    if ($action === 'submitWeek') {
        $userId = intv($payload['userId'] ?? 0);
        $driverName = strv($payload['driverName'] ?? 'Current User', 'Current User');
        $weekStart = strv($payload['weekStart'] ?? '');
        $weekEnd = strv($payload['weekEnd'] ?? '');
        if ($userId <= 0 || $weekStart === '' || $weekEnd === '') {
            jsonResponse(['success' => false, 'message' => 'User, week start, and week end are required.'], 422);
        }
        jsonResponse(['success' => true] + submitWeek($conn, $userId, $driverName, $weekStart, $weekEnd));
    }
    if ($action === 'reviewWeek') {
        $userId = intv($payload['userId'] ?? 0);
        $weekStart = strv($payload['weekStart'] ?? '');
        $weekEnd = strv($payload['weekEnd'] ?? '');
        if ($userId <= 0 || $weekStart === '' || $weekEnd === '') {
            jsonResponse(['success' => false, 'message' => 'User, week start, and week end are required.'], 422);
        }
        jsonResponse(['success' => true] + reviewWeek($conn, $userId, $weekStart, $weekEnd, $payload));
    }
    if ($action === 'currentPayrollWeek') jsonResponse(['success' => true, 'week' => getSubmissionWeek(strv($_GET['date'] ?? date('Y-m-d')))]);
    if ($action === 'weeklyBreakdown') jsonResponse(['success' => true, 'breakdown' => weeklyBreakdown($conn)]);
    if ($action === 'settings') jsonResponse(['success' => true, 'settings' => getSettings($conn)]);
    if ($action === 'updateSettings') jsonResponse(['success' => true, 'settings' => updateSettings($conn, $payload)]);
    if ($action === 'weeklyReport') jsonResponse(['success' => true, 'report' => weeklyReport($conn)]);
    if ($action === 'pending') {
        $_GET['status'] = 'pending_review';
        jsonResponse(['success' => true, 'entries' => listEntries($conn)]);
    }

    jsonResponse(['success' => false, 'message' => 'Unknown mileage action.'], 400);
} catch (Throwable $exception) {
    jsonResponse([
        'success' => false,
        'message' => 'Mileage request failed.',
        'error' => $exception->getMessage(),
    ], 500);
}
