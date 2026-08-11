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
        'workDate' => (string) $row['work_date'],
        'submissionWeekStart' => (string) $row['submission_week_start'],
        'submissionWeekEnd' => (string) $row['submission_week_end'],
        'startingLocation' => (string) $row['starting_location'],
        'endingLocation' => (string) $row['ending_location'],
        'odometerStart' => (float) $row['odometer_start'],
        'odometerEnd' => (float) $row['odometer_end'],
        'claimedMileage' => (float) $row['claimed_mileage'],
        'expectedSystemMileage' => (float) $row['expected_system_mileage'],
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
    if ($action === 'currentPayrollWeek') jsonResponse(['success' => true, 'week' => getSubmissionWeek(strv($_GET['date'] ?? date('Y-m-d')))]);
    if ($action === 'weeklyBreakdown') jsonResponse(['success' => true, 'breakdown' => weeklyBreakdown($conn)]);
    if ($action === 'settings') jsonResponse(['success' => true, 'settings' => getSettings($conn)]);
    if ($action === 'updateSettings') jsonResponse(['success' => true, 'settings' => updateSettings($conn, $payload)]);
    if ($action === 'weeklyReport') jsonResponse(['success' => true, 'report' => []]);
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
