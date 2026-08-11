<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, X-Auth-Token, Authorization, Accept');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

require_once __DIR__ . '/db.php';

const DEFAULT_HOURLY_RATE = 4.50;

function jsonResponse($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function stringValue($value): string
{
    return trim((string) $value);
}

function intValue($value, int $default = 0): int
{
    if ($value === null || $value === '') {
        return $default;
    }
    if (!is_numeric($value)) {
        return $default;
    }
    return (int) $value;
}

function floatValue($value, float $default = 0.0): float
{
    if ($value === null || $value === '') {
        return $default;
    }
    if (!is_numeric($value)) {
        return $default;
    }
    return (float) $value;
}

function money(float $value): float
{
    return round($value, 2);
}

function normalizeDateTimeNullable($value): ?string
{
    if ($value === null) {
        return null;
    }

    $raw = stringValue($value);
    if ($raw === '') {
        return null;
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function normalizeStatus($value): string
{
    $allowed = ['reported', 'approved', 'in_progress', 'completed', 'cancelled'];
    $normalized = strtolower(stringValue($value));
    return in_array($normalized, $allowed, true) ? $normalized : 'reported';
}

function normalizeSeverity($value): string
{
    $allowed = ['low', 'medium', 'high', 'critical'];
    $normalized = strtolower(stringValue($value));
    return in_array($normalized, $allowed, true) ? $normalized : 'medium';
}

function normalizeIssueType($value): string
{
    $allowed = ['service', 'mot', 'tyres', 'brakes', 'engine', 'electrical', 'bodywork', 'breakdown', 'other'];
    $normalized = strtolower(stringValue($value));
    return in_array($normalized, $allowed, true) ? $normalized : 'other';
}

function isOpenMaintenanceStatus(string $status): bool
{
    return in_array($status, ['reported', 'approved', 'in_progress'], true);
}

function ensureVehicleTable(mysqli $conn): void
{
    $vehiclesSql = <<<SQL
CREATE TABLE IF NOT EXISTS company_vehicles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reg_number VARCHAR(20) NOT NULL,
    make VARCHAR(120) NOT NULL DEFAULT '',
    model VARCHAR(120) NOT NULL DEFAULT '',
    hourly_rate_after_free DECIMAL(10,2) NOT NULL DEFAULT 4.50,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY vehicles_reg_unique (reg_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($vehiclesSql)) {
        throw new RuntimeException('Failed to initialize vehicle table.');
    }
}

function ensureMaintenanceTable(mysqli $conn): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS vehicle_maintenance_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    issue_type VARCHAR(40) NOT NULL DEFAULT 'other',
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    status VARCHAR(20) NOT NULL DEFAULT 'reported',
    logged_at DATETIME NOT NULL,
    description TEXT NULL,
    assigned_garage VARCHAR(160) NOT NULL DEFAULT '',
    estimated_return_at DATETIME NULL,
    actual_return_at DATETIME NULL,
    estimated_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    parts_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    labour_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    final_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    mileage INT UNSIGNED NULL,
    created_by VARCHAR(160) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY maintenance_vehicle_idx (vehicle_id),
    KEY maintenance_status_idx (status),
    KEY maintenance_logged_idx (logged_at),
    KEY maintenance_expected_idx (estimated_return_at),
    CONSTRAINT fk_maintenance_vehicle FOREIGN KEY (vehicle_id) REFERENCES company_vehicles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($sql)) {
        throw new RuntimeException('Failed to initialize maintenance table.');
    }
}

function ensureSeedVehicles(mysqli $conn): void
{
    $result = $conn->query('SELECT COUNT(*) AS total FROM company_vehicles');
    if ($result === false) {
        return;
    }

    $row = $result->fetch_assoc();
    $count = (int) ($row['total'] ?? 0);
    if ($count > 0) {
        return;
    }

    $seedRows = [
        ['NX64TL', 'HYUNDAI', 'i10', DEFAULT_HOURLY_RATE],
        ['PJ62DWY', 'HYUNDAI', 'i10', DEFAULT_HOURLY_RATE],
        ['EO18TWA', 'KIA', 'CEED', 5.00],
    ];

    $stmt = $conn->prepare('INSERT INTO company_vehicles (reg_number, make, model, hourly_rate_after_free, is_active) VALUES (?, ?, ?, ?, 1)');
    if ($stmt === false) {
        return;
    }

    foreach ($seedRows as $seed) {
        $reg = $seed[0];
        $make = $seed[1];
        $model = $seed[2];
        $rate = (float) $seed[3];
        $stmt->bind_param('sssd', $reg, $make, $model, $rate);
        $stmt->execute();
    }

    $stmt->close();
}

function fetchVehicles(mysqli $conn): array
{
    $result = $conn->query('SELECT id, reg_number, make, model, is_active FROM company_vehicles ORDER BY reg_number ASC');
    if ($result === false) {
        throw new RuntimeException('Failed to load vehicles.');
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'id' => (int) $row['id'],
            'regNumber' => $row['reg_number'],
            'make' => $row['make'],
            'model' => $row['model'],
            'isActive' => (int) $row['is_active'] === 1,
        ];
    }

    return $rows;
}

function mapLogRow(array $row): array
{
    $partsCost = (float) ($row['parts_cost'] ?? 0);
    $labourCost = (float) ($row['labour_cost'] ?? 0);
    $finalCost = (float) ($row['final_cost'] ?? 0);
    $effectiveFinal = $finalCost > 0 ? $finalCost : ($partsCost + $labourCost);

    return [
        'id' => (int) $row['id'],
        'vehicleId' => (int) $row['vehicle_id'],
        'vehicleReg' => $row['vehicle_reg'] ?? '',
        'vehicleLabel' => trim(($row['vehicle_reg'] ?? '') . ' - ' . ($row['vehicle_make'] ?? '') . ' ' . ($row['vehicle_model'] ?? '')),
        'issueType' => $row['issue_type'],
        'severity' => $row['severity'],
        'status' => $row['status'],
        'loggedAt' => $row['logged_at'],
        'description' => $row['description'] ?? '',
        'assignedGarage' => $row['assigned_garage'] ?? '',
        'estimatedReturnAt' => $row['estimated_return_at'],
        'actualReturnAt' => $row['actual_return_at'],
        'estimatedCost' => (float) ($row['estimated_cost'] ?? 0),
        'partsCost' => $partsCost,
        'labourCost' => $labourCost,
        'finalCost' => $finalCost,
        'effectiveFinalCost' => money($effectiveFinal),
        'mileage' => $row['mileage'] !== null ? (int) $row['mileage'] : null,
        'createdBy' => $row['created_by'] ?? '',
        'createdAt' => $row['created_at'],
        'updatedAt' => $row['updated_at'],
    ];
}

function fetchMaintenanceLogs(mysqli $conn): array
{
    $sql = 'SELECT
                m.*,
                v.reg_number AS vehicle_reg,
                v.make AS vehicle_make,
                v.model AS vehicle_model
            FROM vehicle_maintenance_logs m
            INNER JOIN company_vehicles v ON v.id = m.vehicle_id
            ORDER BY m.logged_at DESC, m.id DESC';
    $result = $conn->query($sql);
    if ($result === false) {
        throw new RuntimeException('Failed to load maintenance logs.');
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = mapLogRow($row);
    }

    return $rows;
}

function fetchMaintenanceLogById(mysqli $conn, int $id): ?array
{
    $sql = 'SELECT
                m.*,
                v.reg_number AS vehicle_reg,
                v.make AS vehicle_make,
                v.model AS vehicle_model
            FROM vehicle_maintenance_logs m
            INNER JOIN company_vehicles v ON v.id = m.vehicle_id
            WHERE m.id = ?
            LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare maintenance log query.');
    }

    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Failed to fetch maintenance log.');
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ? mapLogRow($row) : null;
}

function buildMaintenanceSummary(array $logs): array
{
    $openJobs = 0;
    $offRoadVehicleIds = [];
    $overdue = 0;
    $thisMonthCost = 0.0;

    $monthStart = strtotime(date('Y-m-01 00:00:00'));
    $monthEnd = strtotime(date('Y-m-t 23:59:59'));
    $now = time();

    foreach ($logs as $log) {
        $status = (string) ($log['status'] ?? '');
        $vehicleId = (int) ($log['vehicleId'] ?? 0);
        $estimatedReturnAt = $log['estimatedReturnAt'] ?? null;
        $actualReturnAt = $log['actualReturnAt'] ?? null;
        $effectiveFinalCost = (float) ($log['effectiveFinalCost'] ?? 0);

        if (isOpenMaintenanceStatus($status)) {
            $openJobs++;
        }

        if (in_array($status, ['approved', 'in_progress'], true) && $vehicleId > 0) {
            $offRoadVehicleIds[$vehicleId] = true;
        }

        if (isOpenMaintenanceStatus($status) && $estimatedReturnAt) {
            $eta = strtotime($estimatedReturnAt);
            if ($eta !== false && $eta < $now) {
                $overdue++;
            }
        }

        if ($status === 'completed') {
            $costTime = strtotime((string) ($actualReturnAt ?: ($log['updatedAt'] ?? '')));
            if ($costTime !== false && $costTime >= $monthStart && $costTime <= $monthEnd) {
                $thisMonthCost += $effectiveFinalCost;
            }
        }
    }

    return [
        'openJobs' => $openJobs,
        'offRoadVehicles' => count($offRoadVehicleIds),
        'overdue' => $overdue,
        'thisMonthCost' => money($thisMonthCost),
    ];
}

$body = getJsonBody();
$action = $_GET['action'] ?? '';
$source = normalizeDbSource((string) ($_GET['source'] ?? ($body['source'] ?? 'auto')));

try {
    $conn = createDatabaseConnection($source);
    ensureVehicleTable($conn);
    ensureMaintenanceTable($conn);
    ensureSeedVehicles($conn);
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'message' => 'Failed to initialize maintenance service.'], 500);
}

if ($action === 'getBootstrap' && ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST')) {
    try {
        $vehicles = fetchVehicles($conn);
        $logs = fetchMaintenanceLogs($conn);
        $summary = buildMaintenanceSummary($logs);
        $conn->close();

        jsonResponse([
            'success' => true,
            'vehicles' => $vehicles,
            'logs' => $logs,
            'summary' => $summary,
            'config' => [
                'issueTypes' => ['service', 'mot', 'tyres', 'brakes', 'engine', 'electrical', 'bodywork', 'breakdown', 'other'],
                'severities' => ['low', 'medium', 'high', 'critical'],
                'statuses' => ['reported', 'approved', 'in_progress', 'completed', 'cancelled'],
            ],
        ]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to load maintenance records.'], 500);
    }
}

if ($action === 'saveLog' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intValue($body['id'] ?? 0, 0);
    $vehicleId = intValue($body['vehicleId'] ?? 0, 0);
    $issueType = normalizeIssueType($body['issueType'] ?? 'other');
    $severity = normalizeSeverity($body['severity'] ?? 'medium');
    $status = normalizeStatus($body['status'] ?? 'reported');
    $loggedAt = normalizeDateTimeNullable($body['loggedAt'] ?? date('Y-m-d H:i:s')) ?: date('Y-m-d H:i:s');
    $description = stringValue($body['description'] ?? '');
    $assignedGarage = stringValue($body['assignedGarage'] ?? '');
    $estimatedReturnAt = normalizeDateTimeNullable($body['estimatedReturnAt'] ?? null);
    $actualReturnAt = normalizeDateTimeNullable($body['actualReturnAt'] ?? null);
    $estimatedCost = max(0.0, floatValue($body['estimatedCost'] ?? 0));
    $partsCost = max(0.0, floatValue($body['partsCost'] ?? 0));
    $labourCost = max(0.0, floatValue($body['labourCost'] ?? 0));
    $finalCost = max(0.0, floatValue($body['finalCost'] ?? 0));
    $mileage = $body['mileage'] === null || $body['mileage'] === '' ? null : max(0, intValue($body['mileage'] ?? 0));
    $createdBy = stringValue($body['createdBy'] ?? '');

    if ($vehicleId <= 0) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Vehicle is required.'], 422);
    }

    if ($status === 'completed' && $actualReturnAt === null) {
        $actualReturnAt = date('Y-m-d H:i:s');
    }

    if ($id > 0) {
        $sql = 'UPDATE vehicle_maintenance_logs
                SET vehicle_id = ?, issue_type = ?, severity = ?, status = ?, logged_at = ?, description = ?,
                    assigned_garage = ?, estimated_return_at = ?, actual_return_at = ?, estimated_cost = ?,
                    parts_cost = ?, labour_cost = ?, final_cost = ?, mileage = ?, created_by = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?';
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $conn->close();
            jsonResponse(['success' => false, 'message' => 'Failed to prepare maintenance update query.'], 500);
        }

        $stmt->bind_param(
            'issssssssddddisi',
            $vehicleId,
            $issueType,
            $severity,
            $status,
            $loggedAt,
            $description,
            $assignedGarage,
            $estimatedReturnAt,
            $actualReturnAt,
            $estimatedCost,
            $partsCost,
            $labourCost,
            $finalCost,
            $mileage,
            $createdBy,
            $id
        );
    } else {
        $sql = 'INSERT INTO vehicle_maintenance_logs
                (vehicle_id, issue_type, severity, status, logged_at, description, assigned_garage, estimated_return_at, actual_return_at,
                 estimated_cost, parts_cost, labour_cost, final_cost, mileage, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $conn->close();
            jsonResponse(['success' => false, 'message' => 'Failed to prepare maintenance insert query.'], 500);
        }

        $stmt->bind_param(
            'issssssssddddis',
            $vehicleId,
            $issueType,
            $severity,
            $status,
            $loggedAt,
            $description,
            $assignedGarage,
            $estimatedReturnAt,
            $actualReturnAt,
            $estimatedCost,
            $partsCost,
            $labourCost,
            $finalCost,
            $mileage,
            $createdBy
        );
    }

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to save maintenance record.'], 500);
    }

    $savedId = $id > 0 ? $id : (int) $stmt->insert_id;
    $stmt->close();

    try {
        $saved = fetchMaintenanceLogById($conn, $savedId);
        $logs = fetchMaintenanceLogs($conn);
        $summary = buildMaintenanceSummary($logs);
        $conn->close();
        jsonResponse([
            'success' => true,
            'record' => $saved,
            'logs' => $logs,
            'summary' => $summary,
        ]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => true]);
    }
}

if ($action === 'setStatus' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intValue($body['id'] ?? 0, 0);
    $status = normalizeStatus($body['status'] ?? 'reported');
    $actualReturnAt = normalizeDateTimeNullable($body['actualReturnAt'] ?? null);

    if ($id <= 0) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Invalid maintenance record ID.'], 422);
    }

    if ($status === 'completed' && $actualReturnAt === null) {
        $actualReturnAt = date('Y-m-d H:i:s');
    }
    if ($status !== 'completed') {
        $actualReturnAt = null;
    }

    $stmt = $conn->prepare('UPDATE vehicle_maintenance_logs SET status = ?, actual_return_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare status update query.'], 500);
    }

    $stmt->bind_param('ssi', $status, $actualReturnAt, $id);
    if (!$stmt->execute() || $stmt->affected_rows < 1) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to update status.'], 500);
    }
    $stmt->close();

    try {
        $record = fetchMaintenanceLogById($conn, $id);
        $logs = fetchMaintenanceLogs($conn);
        $summary = buildMaintenanceSummary($logs);
        $conn->close();
        jsonResponse([
            'success' => true,
            'record' => $record,
            'logs' => $logs,
            'summary' => $summary,
        ]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => true]);
    }
}

$conn->close();
jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
?>
