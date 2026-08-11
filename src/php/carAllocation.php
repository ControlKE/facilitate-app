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
require_once __DIR__ . '/vendorAutoload.php';
require_once __DIR__ . '/messageRoutingHelper.php';

const DEFAULT_FREE_HOURS = 72;
const DEFAULT_VAT_RATE = 0.0;
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

function boolValue($value, bool $default = false): bool
{
    if ($value === null) {
        return $default;
    }
    if (is_bool($value)) {
        return $value;
    }

    $normalized = strtolower(stringValue($value));
    if (in_array($normalized, ['1', 'true', 'yes', 'y'], true)) {
        return true;
    }
    if (in_array($normalized, ['0', 'false', 'no', 'n'], true)) {
        return false;
    }

    return $default;
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

function normalizeDateTime($value, bool $fallbackToNow = true): string
{
    $raw = stringValue($value);
    if ($raw === '') {
        return $fallbackToNow ? date('Y-m-d H:i:s') : '';
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return $fallbackToNow ? date('Y-m-d H:i:s') : '';
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function money(float $value): float
{
    return round($value, 2);
}

function clampVatRate(float $value): float
{
    if ($value < 0) {
        return 0.0;
    }
    if ($value > 1) {
        return 1.0;
    }
    return $value;
}

function ensureCarAllocationTables(mysqli $conn): void
{
    $carersSql = <<<SQL
CREATE TABLE IF NOT EXISTS carers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(160) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(40) NOT NULL DEFAULT '',
    employee_code VARCHAR(40) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY carers_email_unique (email),
    KEY carers_name_idx (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

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

    $assignmentsSql = <<<SQL
CREATE TABLE IF NOT EXISTS car_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    carer_id INT UNSIGNED NOT NULL,
    vehicle_id INT UNSIGNED NOT NULL,
    assigned_by VARCHAR(160) NOT NULL,
    assigned_at DATETIME NOT NULL,
    returned_at DATETIME NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    hourly_rate_locked DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    free_hours INT UNSIGNED NOT NULL DEFAULT 72,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY assignments_status_idx (status),
    KEY assignments_vehicle_idx (vehicle_id, status),
    KEY assignments_carer_idx (carer_id, status),
    CONSTRAINT fk_assignments_carer FOREIGN KEY (carer_id) REFERENCES carers(id),
    CONSTRAINT fk_assignments_vehicle FOREIGN KEY (vehicle_id) REFERENCES company_vehicles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    $invoicesSql = <<<SQL
CREATE TABLE IF NOT EXISTS car_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL,
    assignment_id INT UNSIGNED NOT NULL,
    carer_id INT UNSIGNED NOT NULL,
    vehicle_id INT UNSIGNED NOT NULL,
    issued_at DATETIME NOT NULL,
    period_start DATETIME NOT NULL,
    period_end DATETIME NOT NULL,
    total_hours INT UNSIGNED NOT NULL DEFAULT 0,
    free_hours INT UNSIGNED NOT NULL DEFAULT 72,
    billable_hours INT UNSIGNED NOT NULL DEFAULT 0,
    hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    vat_rate DECIMAL(6,4) NOT NULL DEFAULT 0.0000,
    vat_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    email_to VARCHAR(255) NOT NULL DEFAULT '',
    email_sent TINYINT(1) NOT NULL DEFAULT 0,
    email_sent_at DATETIME NULL,
    email_error TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY invoices_number_unique (invoice_number),
    UNIQUE KEY invoices_assignment_unique (assignment_id),
    KEY invoices_carer_idx (carer_id),
    KEY invoices_vehicle_idx (vehicle_id),
    KEY invoices_issue_idx (issued_at),
    CONSTRAINT fk_invoices_assignment FOREIGN KEY (assignment_id) REFERENCES car_assignments(id),
    CONSTRAINT fk_invoices_carer FOREIGN KEY (carer_id) REFERENCES carers(id),
    CONSTRAINT fk_invoices_vehicle FOREIGN KEY (vehicle_id) REFERENCES company_vehicles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (
        !$conn->query($carersSql) ||
        !$conn->query($vehiclesSql) ||
        !$conn->query($assignmentsSql) ||
        !$conn->query($invoicesSql)
    ) {
        throw new RuntimeException('Failed to initialize car allocation tables.');
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
        ['NX64TL', 'HYUNDAI', 'i10', 4.50],
        ['PJ62DWY', 'HYUNDAI', 'i10', 4.50],
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

function ensureVehicleMaintenanceTable(mysqli $conn): void
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

function ensureCarAllocationSettingsTable(mysqli $conn): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS car_allocation_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL,
    config_value TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY car_allocation_settings_key_unique (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($sql)) {
        throw new RuntimeException('Failed to initialize car allocation settings table.');
    }
}

function getConfigValue(mysqli $conn, string $key, ?string $defaultValue = null): ?string
{
    $stmt = $conn->prepare('SELECT config_value FROM car_allocation_settings WHERE config_key = ? LIMIT 1');
    if ($stmt === false) {
        return $defaultValue;
    }

    $stmt->bind_param('s', $key);
    if (!$stmt->execute()) {
        $stmt->close();
        return $defaultValue;
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($row && array_key_exists('config_value', $row)) {
        return (string) $row['config_value'];
    }

    if ($defaultValue !== null) {
        $insertStmt = $conn->prepare('INSERT INTO car_allocation_settings (config_key, config_value) VALUES (?, ?)
                                      ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)');
        if ($insertStmt !== false) {
            $insertStmt->bind_param('ss', $key, $defaultValue);
            $insertStmt->execute();
            $insertStmt->close();
        }
    }

    return $defaultValue;
}

function setConfigValue(mysqli $conn, string $key, string $value): void
{
    $stmt = $conn->prepare('INSERT INTO car_allocation_settings (config_key, config_value) VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = CURRENT_TIMESTAMP');
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare config upsert query.');
    }

    $stmt->bind_param('ss', $key, $value);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Failed to save configuration.');
    }
    $stmt->close();
}

function getConfiguredFreeHours(mysqli $conn): int
{
    $value = getConfigValue($conn, 'free_hours', (string) DEFAULT_FREE_HOURS);
    $hours = intValue($value, DEFAULT_FREE_HOURS);
    return $hours > 0 ? $hours : DEFAULT_FREE_HOURS;
}

function fetchOffRoadMaintenanceMap(mysqli $conn): array
{
    $sql = 'SELECT vehicle_id, status, issue_type, estimated_return_at
            FROM vehicle_maintenance_logs
            WHERE status IN ("approved", "in_progress")
            ORDER BY logged_at DESC, id DESC';
    $result = $conn->query($sql);
    if ($result === false) {
        return [];
    }

    $map = [];
    while ($row = $result->fetch_assoc()) {
        $vehicleId = (int) ($row['vehicle_id'] ?? 0);
        if ($vehicleId <= 0 || isset($map[$vehicleId])) {
            continue;
        }

        $map[$vehicleId] = [
            'status' => strtolower(stringValue($row['status'] ?? '')),
            'issueType' => strtolower(stringValue($row['issue_type'] ?? '')),
            'estimatedReturnAt' => $row['estimated_return_at'] ?? null,
        ];
    }

    return $map;
}

function durationHours(string $startAt, string $endAt): int
{
    $startTs = strtotime($startAt);
    $endTs = strtotime($endAt);
    if ($startTs === false || $endTs === false || $endTs <= $startTs) {
        return 0;
    }

    return (int) ceil(($endTs - $startTs) / 3600);
}

function calculateInvoiceMetrics(string $startAt, string $endAt, int $freeHours, float $hourlyRate, float $vatRate = DEFAULT_VAT_RATE): array
{
    $totalHours = durationHours($startAt, $endAt);
    $safeFreeHours = max(0, $freeHours);
    $billableHours = max(0, $totalHours - $safeFreeHours);
    $safeRate = max(0.0, $hourlyRate);
    $safeVatRate = clampVatRate($vatRate);

    $subtotal = money($billableHours * $safeRate);
    $vatAmount = money($subtotal * $safeVatRate);
    $total = money($subtotal + $vatAmount);

    return [
        'totalHours' => $totalHours,
        'freeHours' => $safeFreeHours,
        'billableHours' => $billableHours,
        'hourlyRate' => $safeRate,
        'subtotal' => $subtotal,
        'vatRate' => $safeVatRate,
        'vatAmount' => $vatAmount,
        'total' => $total,
    ];
}

function generateInvoiceNumber(): string
{
    $suffix = '';
    try {
        $suffix = strtoupper(bin2hex(random_bytes(2)));
    } catch (Throwable $exception) {
        $suffix = strtoupper(dechex(mt_rand(4096, 65535)));
    }

    return 'INV-' . date('Ymd-His') . '-' . $suffix;
}

function sendInvoiceEmail(string $toEmail, string $subject, string $body, string &$errorMessage): bool
{
    $errorMessage = '';
    ensureVendorAutoload();

    if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = messageRoutingSmtpHost();
            $mail->SMTPAuth = true;
            $mail->Username = messageRoutingSmtpUsername();
            $mail->Password = messageRoutingSmtpPassword();
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = messageRoutingSmtpPort();
            $mail->CharSet = 'utf-8';
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->setFrom('steve@facilitatecareservices.co.uk', 'Facilitate Care Services');
            $mail->addAddress($toEmail);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            return true;
        } catch (Throwable $exception) {
            $errorMessage = $exception->getMessage();
        }
    }

    $headers = "From: Facilitate Care Services <steve@facilitatecareservices.co.uk>\r\n";
    $mailSent = @mail($toEmail, $subject, $body, $headers);
    if ($mailSent) {
        return true;
    }

    if ($errorMessage === '') {
        $errorMessage = 'Unable to send email from server.';
    }
    return false;
}

function buildInvoiceEmailBody(array $invoice): string
{
    $carerName = stringValue($invoice['carerName'] ?? '');
    $vehicleReg = stringValue($invoice['vehicleReg'] ?? '');
    $invoiceNumber = stringValue($invoice['invoiceNumber'] ?? '');
    $periodStart = stringValue($invoice['periodStart'] ?? '');
    $periodEnd = stringValue($invoice['periodEnd'] ?? '');
    $totalHours = intValue($invoice['totalHours'] ?? 0, 0);
    $freeHours = intValue($invoice['freeHours'] ?? DEFAULT_FREE_HOURS, DEFAULT_FREE_HOURS);
    $billableHours = intValue($invoice['billableHours'] ?? 0, 0);
    $hourlyRate = number_format((float) ($invoice['hourlyRate'] ?? 0.0), 2);
    $subtotal = number_format((float) ($invoice['subtotal'] ?? 0.0), 2);
    $vatAmount = number_format((float) ($invoice['vatAmount'] ?? 0.0), 2);
    $totalAmount = number_format((float) ($invoice['totalAmount'] ?? 0.0), 2);

    return "Dear {$carerName},\n\n"
        . "Your company car usage invoice has been generated.\n\n"
        . "Invoice Number: {$invoiceNumber}\n"
        . "Vehicle: {$vehicleReg}\n"
        . "Usage Period: {$periodStart} to {$periodEnd}\n"
        . "Total Usage Hours: {$totalHours}\n"
        . "Free Usage Hours: {$freeHours}\n"
        . "Billable Hours: {$billableHours}\n"
        . "Rate After Free Period (per hour): GBP {$hourlyRate}\n"
        . "Subtotal: GBP {$subtotal}\n"
        . "VAT: GBP {$vatAmount}\n"
        . "Total Due: GBP {$totalAmount}\n\n"
        . "Please contact the office if you have any questions.\n\n"
        . "Facilitate Care Services";
}

function fetchCarers(mysqli $conn): array
{
    $sql = 'SELECT id, full_name, email, phone, employee_code, is_active, created_at, updated_at
            FROM carers
            ORDER BY full_name ASC';
    $result = $conn->query($sql);
    if ($result === false) {
        throw new RuntimeException('Failed to load carers.');
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'id' => (int) $row['id'],
            'fullName' => $row['full_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'employeeCode' => $row['employee_code'],
            'isActive' => (int) $row['is_active'] === 1,
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
    }

    return $rows;
}

function fetchVehicles(mysqli $conn, array $offRoadMap = [], array $activeAssignmentMap = []): array
{
    $sql = 'SELECT id, reg_number, make, model, hourly_rate_after_free, is_active, created_at, updated_at
            FROM company_vehicles
            ORDER BY reg_number ASC';
    $result = $conn->query($sql);
    if ($result === false) {
        throw new RuntimeException('Failed to load vehicles.');
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $vehicleId = (int) $row['id'];
        $maintenanceLock = $offRoadMap[$vehicleId] ?? null;
        $activeAssignment = $activeAssignmentMap[$vehicleId] ?? null;
        $rows[] = [
            'id' => $vehicleId,
            'regNumber' => $row['reg_number'],
            'make' => $row['make'],
            'model' => $row['model'],
            'hourlyRateAfterFree' => (float) $row['hourly_rate_after_free'],
            'isActive' => (int) $row['is_active'] === 1,
            'isAssigned' => is_array($activeAssignment),
            'assignmentId' => $activeAssignment['assignmentId'] ?? null,
            'assignedAt' => $activeAssignment['assignedAt'] ?? null,
            'assignedTo' => $activeAssignment['carerName'] ?? '',
            'assignedToEmail' => $activeAssignment['carerEmail'] ?? '',
            'isOffRoad' => is_array($maintenanceLock),
            'maintenanceStatus' => $maintenanceLock['status'] ?? '',
            'maintenanceIssueType' => $maintenanceLock['issueType'] ?? '',
            'maintenanceEta' => $maintenanceLock['estimatedReturnAt'] ?? null,
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
    }

    return $rows;
}

function mapActiveAssignmentRow(array $row): array
{
    $metrics = calculateInvoiceMetrics(
        $row['assigned_at'],
        date('Y-m-d H:i:s'),
        (int) $row['free_hours'],
        (float) $row['hourly_rate_locked'],
        DEFAULT_VAT_RATE
    );

    return [
        'id' => (int) $row['id'],
        'carerId' => (int) $row['carer_id'],
        'vehicleId' => (int) $row['vehicle_id'],
        'assignedBy' => $row['assigned_by'],
        'assignedAt' => $row['assigned_at'],
        'returnedAt' => $row['returned_at'],
        'status' => $row['status'],
        'notes' => $row['notes'] ?? '',
        'hourlyRateLocked' => (float) $row['hourly_rate_locked'],
        'freeHours' => (int) $row['free_hours'],
        'carerName' => $row['carer_name'],
        'carerEmail' => $row['carer_email'],
        'carerPhone' => $row['carer_phone'],
        'vehicleReg' => $row['vehicle_reg'],
        'vehicleMake' => $row['vehicle_make'],
        'vehicleModel' => $row['vehicle_model'],
        'currentTotalHours' => $metrics['totalHours'],
        'currentBillableHours' => $metrics['billableHours'],
        'currentSubtotal' => $metrics['subtotal'],
    ];
}

function fetchActiveAssignments(mysqli $conn): array
{
    $sql = 'SELECT
                a.id,
                a.carer_id,
                a.vehicle_id,
                a.assigned_by,
                a.assigned_at,
                a.returned_at,
                a.status,
                a.notes,
                a.hourly_rate_locked,
                a.free_hours,
                c.full_name AS carer_name,
                c.email AS carer_email,
                c.phone AS carer_phone,
                v.reg_number AS vehicle_reg,
                v.make AS vehicle_make,
                v.model AS vehicle_model
            FROM car_assignments a
            INNER JOIN carers c ON c.id = a.carer_id
            INNER JOIN company_vehicles v ON v.id = a.vehicle_id
            WHERE a.status = "active"
            ORDER BY a.assigned_at DESC';
    $result = $conn->query($sql);
    if ($result === false) {
        throw new RuntimeException('Failed to load active assignments.');
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = mapActiveAssignmentRow($row);
    }

    return $rows;
}

function mapActiveAssignmentsByVehicle(array $assignments): array
{
    $map = [];
    foreach ($assignments as $assignment) {
        $vehicleId = (int) ($assignment['vehicleId'] ?? 0);
        if ($vehicleId <= 0 || isset($map[$vehicleId])) {
            continue;
        }

        $map[$vehicleId] = [
            'assignmentId' => (int) ($assignment['id'] ?? 0),
            'assignedAt' => $assignment['assignedAt'] ?? null,
            'carerName' => stringValue($assignment['carerName'] ?? ''),
            'carerEmail' => stringValue($assignment['carerEmail'] ?? ''),
        ];
    }

    return $map;
}

function mapInvoiceRow(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'invoiceNumber' => $row['invoice_number'],
        'assignmentId' => (int) $row['assignment_id'],
        'carerId' => (int) $row['carer_id'],
        'vehicleId' => (int) $row['vehicle_id'],
        'issuedAt' => $row['issued_at'],
        'periodStart' => $row['period_start'],
        'periodEnd' => $row['period_end'],
        'totalHours' => (int) $row['total_hours'],
        'freeHours' => (int) $row['free_hours'],
        'billableHours' => (int) $row['billable_hours'],
        'hourlyRate' => (float) $row['hourly_rate'],
        'subtotal' => (float) $row['subtotal'],
        'vatRate' => (float) $row['vat_rate'],
        'vatAmount' => (float) $row['vat_amount'],
        'totalAmount' => (float) $row['total_amount'],
        'emailTo' => $row['email_to'],
        'emailSent' => (int) $row['email_sent'] === 1,
        'emailSentAt' => $row['email_sent_at'],
        'emailError' => $row['email_error'],
        'createdAt' => $row['created_at'],
        'carerName' => $row['carer_name'] ?? '',
        'vehicleReg' => $row['vehicle_reg'] ?? '',
    ];
}

function fetchInvoices(mysqli $conn, int $limit = 50): array
{
    $safeLimit = max(1, min(200, $limit));
    $sql = 'SELECT
                i.*,
                c.full_name AS carer_name,
                v.reg_number AS vehicle_reg
            FROM car_invoices i
            INNER JOIN carers c ON c.id = i.carer_id
            INNER JOIN company_vehicles v ON v.id = i.vehicle_id
            ORDER BY i.issued_at DESC
            LIMIT ?';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare invoice query.');
    }

    $stmt->bind_param('i', $safeLimit);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Failed to load invoices.');
    }

    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = mapInvoiceRow($row);
    }

    $stmt->close();
    return $rows;
}

function fetchInvoiceById(mysqli $conn, int $invoiceId): ?array
{
    $sql = 'SELECT
                i.*,
                c.full_name AS carer_name,
                v.reg_number AS vehicle_reg
            FROM car_invoices i
            INNER JOIN carers c ON c.id = i.carer_id
            INNER JOIN company_vehicles v ON v.id = i.vehicle_id
            WHERE i.id = ?
            LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare invoice fetch query.');
    }

    $stmt->bind_param('i', $invoiceId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Failed to fetch invoice.');
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ? mapInvoiceRow($row) : null;
}

function fetchActiveAssignmentById(mysqli $conn, int $assignmentId): ?array
{
    $sql = 'SELECT
                a.id,
                a.carer_id,
                a.vehicle_id,
                a.assigned_by,
                a.assigned_at,
                a.returned_at,
                a.status,
                a.notes,
                a.hourly_rate_locked,
                a.free_hours,
                c.full_name AS carer_name,
                c.email AS carer_email,
                c.phone AS carer_phone,
                v.reg_number AS vehicle_reg,
                v.make AS vehicle_make,
                v.model AS vehicle_model
            FROM car_assignments a
            INNER JOIN carers c ON c.id = a.carer_id
            INNER JOIN company_vehicles v ON v.id = a.vehicle_id
            WHERE a.id = ? AND a.status = "active"
            LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare active assignment query.');
    }

    $stmt->bind_param('i', $assignmentId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Failed to fetch active assignment.');
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return null;
    }

    return mapActiveAssignmentRow($row);
}

function updateInvoiceEmailStatus(mysqli $conn, int $invoiceId, bool $emailSent, string $emailError = ''): void
{
    $sentFlag = $emailSent ? 1 : 0;
    $sentAt = $emailSent ? date('Y-m-d H:i:s') : null;
    $error = $emailError !== '' ? $emailError : null;

    $stmt = $conn->prepare('UPDATE car_invoices SET email_sent = ?, email_sent_at = ?, email_error = ? WHERE id = ?');
    if ($stmt === false) {
        return;
    }

    $stmt->bind_param('issi', $sentFlag, $sentAt, $error, $invoiceId);
    $stmt->execute();
    $stmt->close();
}

$body = getJsonBody();
$action = $_GET['action'] ?? '';
$source = normalizeDbSource((string) ($_GET['source'] ?? ($body['source'] ?? 'auto')));

try {
    $conn = createDatabaseConnection($source);
    ensureCarAllocationTables($conn);
    ensureSeedVehicles($conn);
    ensureVehicleMaintenanceTable($conn);
    ensureCarAllocationSettingsTable($conn);
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'message' => 'Failed to initialize car allocation service.'], 500);
}

if ($action === 'getBootstrap' && ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST')) {
    try {
        $configuredFreeHours = getConfiguredFreeHours($conn);
        $offRoadMap = fetchOffRoadMaintenanceMap($conn);
        $carers = fetchCarers($conn);
        $activeAssignments = fetchActiveAssignments($conn);
        $activeAssignmentMap = mapActiveAssignmentsByVehicle($activeAssignments);
        $vehicles = fetchVehicles($conn, $offRoadMap, $activeAssignmentMap);
        $invoices = fetchInvoices($conn, 60);

        $conn->close();
        jsonResponse([
            'success' => true,
            'config' => [
                'freeHours' => $configuredFreeHours,
                'vatRate' => DEFAULT_VAT_RATE,
            ],
            'offRoadCount' => count($offRoadMap),
            'carers' => $carers,
            'vehicles' => $vehicles,
            'activeAssignments' => $activeAssignments,
            'invoices' => $invoices,
        ]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to load car allocation data.'], 500);
    }
}

if ($action === 'updateConfig' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $freeHours = intValue($body['freeHours'] ?? 0, 0);
    if ($freeHours < 1 || $freeHours > 720) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Free period must be between 1 and 720 hours.'], 422);
    }

    try {
        setConfigValue($conn, 'free_hours', (string) $freeHours);
        $configuredFreeHours = getConfiguredFreeHours($conn);
        $conn->close();
        jsonResponse([
            'success' => true,
            'config' => [
                'freeHours' => $configuredFreeHours,
                'vatRate' => DEFAULT_VAT_RATE,
            ],
        ]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to update configuration.'], 500);
    }
}

if ($action === 'addCarer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = stringValue($body['fullName'] ?? '');
    $email = strtolower(stringValue($body['email'] ?? ''));
    $phone = stringValue($body['phone'] ?? '');
    $employeeCode = strtoupper(stringValue($body['employeeCode'] ?? ''));

    if ($fullName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Valid full name and email are required.'], 422);
    }

    $sql = 'INSERT INTO carers (full_name, email, phone, employee_code, is_active)
            VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                full_name = VALUES(full_name),
                phone = VALUES(phone),
                employee_code = VALUES(employee_code),
                is_active = 1,
                updated_at = CURRENT_TIMESTAMP';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare carer save query.'], 500);
    }

    $stmt->bind_param('ssss', $fullName, $email, $phone, $employeeCode);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to save carer.'], 500);
    }
    $stmt->close();

    try {
        $carers = fetchCarers($conn);
        $saved = null;
        foreach ($carers as $carer) {
            if (strtolower($carer['email']) === $email) {
                $saved = $carer;
                break;
            }
        }

        $conn->close();
        jsonResponse(['success' => true, 'carer' => $saved, 'carers' => $carers]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => true, 'carer' => null]);
    }
}

if ($action === 'addVehicle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = intValue($body['id'] ?? 0, 0);
    $regNumber = strtoupper(stringValue($body['regNumber'] ?? ''));
    $make = strtoupper(stringValue($body['make'] ?? ''));
    $model = strtoupper(stringValue($body['model'] ?? ''));
    $hourlyRate = floatValue($body['hourlyRateAfterFree'] ?? $body['hourlyRate'] ?? DEFAULT_HOURLY_RATE, DEFAULT_HOURLY_RATE);
    $hourlyRate = $hourlyRate > 0 ? $hourlyRate : DEFAULT_HOURLY_RATE;
    $isActive = boolValue($body['isActive'] ?? true, true);
    $activeFlag = $isActive ? 1 : 0;

    if ($regNumber === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Vehicle registration is required.'], 422);
    }

    if ($vehicleId > 0) {
        $existsStmt = $conn->prepare('SELECT id FROM company_vehicles WHERE id = ? LIMIT 1');
        if ($existsStmt === false) {
            $conn->close();
            jsonResponse(['success' => false, 'message' => 'Failed to prepare vehicle lookup query.'], 500);
        }
        $existsStmt->bind_param('i', $vehicleId);
        $existsStmt->execute();
        $existsResult = $existsStmt->get_result();
        $existsVehicle = $existsResult ? $existsResult->fetch_assoc() : null;
        $existsStmt->close();
        if (!$existsVehicle) {
            $conn->close();
            jsonResponse(['success' => false, 'message' => 'Vehicle not found.'], 404);
        }

        if ($activeFlag === 0) {
            $activeAssignmentStmt = $conn->prepare('SELECT a.id, a.assigned_at, c.full_name AS carer_name
                                                    FROM car_assignments a
                                                    INNER JOIN carers c ON c.id = a.carer_id
                                                    WHERE a.vehicle_id = ? AND a.status = "active"
                                                    LIMIT 1');
            if ($activeAssignmentStmt === false) {
                $conn->close();
                jsonResponse(['success' => false, 'message' => 'Failed to validate active assignment state.'], 500);
            }

            $activeAssignmentStmt->bind_param('i', $vehicleId);
            $activeAssignmentStmt->execute();
            $activeAssignmentResult = $activeAssignmentStmt->get_result();
            $activeAssignment = $activeAssignmentResult ? $activeAssignmentResult->fetch_assoc() : null;
            $activeAssignmentStmt->close();

            if ($activeAssignment) {
                $assigneeName = stringValue($activeAssignment['carer_name'] ?? 'this carer');
                $conn->close();
                jsonResponse([
                    'success' => false,
                    'message' => 'Vehicle cannot be marked inactive while assigned to ' . $assigneeName . '.',
                    'activeAssignment' => [
                        'id' => (int) ($activeAssignment['id'] ?? 0),
                        'assignedAt' => $activeAssignment['assigned_at'] ?? null,
                        'carerName' => $assigneeName,
                    ],
                ], 409);
            }
        }

        $updateSql = 'UPDATE company_vehicles
                      SET reg_number = ?, make = ?, model = ?, hourly_rate_after_free = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                      WHERE id = ?
                      LIMIT 1';
        $updateStmt = $conn->prepare($updateSql);
        if ($updateStmt === false) {
            $conn->close();
            jsonResponse(['success' => false, 'message' => 'Failed to prepare vehicle update query.'], 500);
        }

        $updateStmt->bind_param('sssdii', $regNumber, $make, $model, $hourlyRate, $activeFlag, $vehicleId);
        if (!$updateStmt->execute()) {
            $errno = (int) $updateStmt->errno;
            $updateStmt->close();
            $conn->close();
            if ($errno === 1062) {
                jsonResponse(['success' => false, 'message' => 'Another vehicle already uses this registration number.'], 409);
            }
            jsonResponse(['success' => false, 'message' => 'Failed to update vehicle.'], 500);
        }
        $updateStmt->close();
    } else {
        $sql = 'INSERT INTO company_vehicles (reg_number, make, model, hourly_rate_after_free, is_active)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    make = VALUES(make),
                    model = VALUES(model),
                    hourly_rate_after_free = VALUES(hourly_rate_after_free),
                    is_active = VALUES(is_active),
                    updated_at = CURRENT_TIMESTAMP';
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $conn->close();
            jsonResponse(['success' => false, 'message' => 'Failed to prepare vehicle save query.'], 500);
        }

        $stmt->bind_param('sssdi', $regNumber, $make, $model, $hourlyRate, $activeFlag);
        if (!$stmt->execute()) {
            $errno = (int) $stmt->errno;
            $stmt->close();
            $conn->close();
            if ($errno === 1062) {
                jsonResponse(['success' => false, 'message' => 'Another vehicle already uses this registration number.'], 409);
            }
            jsonResponse(['success' => false, 'message' => 'Failed to save vehicle.'], 500);
        }
        $stmt->close();
    }

    try {
        $offRoadMap = fetchOffRoadMaintenanceMap($conn);
        $activeAssignments = fetchActiveAssignments($conn);
        $activeAssignmentMap = mapActiveAssignmentsByVehicle($activeAssignments);
        $vehicles = fetchVehicles($conn, $offRoadMap, $activeAssignmentMap);
        $saved = null;
        foreach ($vehicles as $vehicle) {
            if ($vehicleId > 0 && (int) $vehicle['id'] === $vehicleId) {
                $saved = $vehicle;
                break;
            }
            if ($vehicleId <= 0 && strtoupper($vehicle['regNumber']) === $regNumber) {
                $saved = $vehicle;
                break;
            }
        }

        $conn->close();
        jsonResponse(['success' => true, 'vehicle' => $saved, 'vehicles' => $vehicles]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => true, 'vehicle' => null]);
    }
}

if ($action === 'assignCar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $carerId = intValue($body['carerId'] ?? 0, 0);
    $vehicleId = intValue($body['vehicleId'] ?? 0, 0);
    $assignedBy = stringValue($body['assignedBy'] ?? '');
    $assignedAt = normalizeDateTime($body['assignedAt'] ?? '', true);
    $notes = stringValue($body['notes'] ?? '');
    $configuredFreeHours = getConfiguredFreeHours($conn);
    $freeHours = intValue($body['freeHours'] ?? $configuredFreeHours, $configuredFreeHours);
    $freeHours = $freeHours > 0 ? $freeHours : $configuredFreeHours;

    if ($carerId <= 0 || $vehicleId <= 0 || $assignedBy === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Carer, vehicle, and assigned-by are required.'], 422);
    }

    $carerStmt = $conn->prepare('SELECT id, full_name, email, is_active FROM carers WHERE id = ? LIMIT 1');
    if ($carerStmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare carer validation query.'], 500);
    }
    $carerStmt->bind_param('i', $carerId);
    $carerStmt->execute();
    $carerResult = $carerStmt->get_result();
    $carer = $carerResult ? $carerResult->fetch_assoc() : null;
    $carerStmt->close();
    if (!$carer || (int) $carer['is_active'] !== 1) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Selected carer is not active.'], 422);
    }

    $vehicleStmt = $conn->prepare('SELECT id, reg_number, hourly_rate_after_free, is_active FROM company_vehicles WHERE id = ? LIMIT 1');
    if ($vehicleStmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare vehicle validation query.'], 500);
    }
    $vehicleStmt->bind_param('i', $vehicleId);
    $vehicleStmt->execute();
    $vehicleResult = $vehicleStmt->get_result();
    $vehicle = $vehicleResult ? $vehicleResult->fetch_assoc() : null;
    $vehicleStmt->close();
    if (!$vehicle || (int) $vehicle['is_active'] !== 1) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Selected vehicle is not active.'], 422);
    }

    $maintenanceStmt = $conn->prepare('SELECT issue_type, status, estimated_return_at
                                       FROM vehicle_maintenance_logs
                                       WHERE vehicle_id = ? AND status IN ("approved", "in_progress")
                                       ORDER BY logged_at DESC, id DESC
                                       LIMIT 1');
    if ($maintenanceStmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to check maintenance availability.'], 500);
    }

    $maintenanceStmt->bind_param('i', $vehicleId);
    $maintenanceStmt->execute();
    $maintenanceResult = $maintenanceStmt->get_result();
    $maintenanceLock = $maintenanceResult ? $maintenanceResult->fetch_assoc() : null;
    $maintenanceStmt->close();

    if ($maintenanceLock) {
        $statusText = strtolower(stringValue($maintenanceLock['status'] ?? 'maintenance'));
        $issueText = strtolower(stringValue($maintenanceLock['issue_type'] ?? 'issue'));
        $eta = stringValue($maintenanceLock['estimated_return_at'] ?? '');
        $etaText = $eta !== '' ? ' Expected return: ' . $eta . '.' : '';

        $conn->close();
        jsonResponse([
            'success' => false,
            'message' => 'Vehicle is unavailable due to maintenance (' . $statusText . ': ' . $issueText . ').' . $etaText,
            'maintenanceLock' => [
                'status' => $statusText,
                'issueType' => $issueText,
                'estimatedReturnAt' => $eta !== '' ? $eta : null,
            ],
        ], 409);
    }

    $activeVehicleStmt = $conn->prepare('SELECT id FROM car_assignments WHERE vehicle_id = ? AND status = "active" LIMIT 1');
    if ($activeVehicleStmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to check vehicle availability.'], 500);
    }
    $activeVehicleStmt->bind_param('i', $vehicleId);
    $activeVehicleStmt->execute();
    $activeVehicleResult = $activeVehicleStmt->get_result();
    $activeVehicle = $activeVehicleResult ? $activeVehicleResult->fetch_assoc() : null;
    $activeVehicleStmt->close();
    if ($activeVehicle) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Vehicle is already assigned to another carer.'], 409);
    }

    $activeCarerStmt = $conn->prepare('SELECT id FROM car_assignments WHERE carer_id = ? AND status = "active" LIMIT 1');
    if ($activeCarerStmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to check current carer assignment.'], 500);
    }
    $activeCarerStmt->bind_param('i', $carerId);
    $activeCarerStmt->execute();
    $activeCarerResult = $activeCarerStmt->get_result();
    $activeCarer = $activeCarerResult ? $activeCarerResult->fetch_assoc() : null;
    $activeCarerStmt->close();
    if ($activeCarer) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'This carer already has an active vehicle assignment.'], 409);
    }

    $hourlyRateLocked = (float) ($vehicle['hourly_rate_after_free'] ?? DEFAULT_HOURLY_RATE);
    if ($hourlyRateLocked <= 0) {
        $hourlyRateLocked = DEFAULT_HOURLY_RATE;
    }

    $insertSql = 'INSERT INTO car_assignments
        (carer_id, vehicle_id, assigned_by, assigned_at, returned_at, status, notes, hourly_rate_locked, free_hours)
        VALUES (?, ?, ?, ?, NULL, "active", ?, ?, ?)';
    $insertStmt = $conn->prepare($insertSql);
    if ($insertStmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare assignment insert query.'], 500);
    }

    $insertStmt->bind_param(
        'iisssdi',
        $carerId,
        $vehicleId,
        $assignedBy,
        $assignedAt,
        $notes,
        $hourlyRateLocked,
        $freeHours
    );
    if (!$insertStmt->execute()) {
        $insertStmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to create assignment.'], 500);
    }

    $assignmentId = (int) $insertStmt->insert_id;
    $insertStmt->close();

    try {
        $assignment = fetchActiveAssignmentById($conn, $assignmentId);
        $activeAssignments = fetchActiveAssignments($conn);
        $conn->close();
        jsonResponse([
            'success' => true,
            'assignment' => $assignment,
            'activeAssignments' => $activeAssignments,
        ]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => true, 'assignment' => null]);
    }
}

if ($action === 'returnAndInvoice' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignmentId = intValue($body['assignmentId'] ?? 0, 0);
    $returnedAt = normalizeDateTime($body['returnedAt'] ?? '', true);
    $vatRate = clampVatRate(floatValue($body['vatRate'] ?? DEFAULT_VAT_RATE, DEFAULT_VAT_RATE));
    $sendEmail = boolValue($body['sendEmail'] ?? true, true);

    if ($assignmentId <= 0) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Invalid assignment ID.'], 422);
    }

    $assignment = fetchActiveAssignmentById($conn, $assignmentId);
    if (!$assignment) {
        $existingInvoiceStmt = $conn->prepare('SELECT id FROM car_invoices WHERE assignment_id = ? LIMIT 1');
        if ($existingInvoiceStmt !== false) {
            $existingInvoiceStmt->bind_param('i', $assignmentId);
            $existingInvoiceStmt->execute();
            $existingResult = $existingInvoiceStmt->get_result();
            $existingInvoice = $existingResult ? $existingResult->fetch_assoc() : null;
            $existingInvoiceStmt->close();

            if ($existingInvoice) {
                $invoice = fetchInvoiceById($conn, (int) $existingInvoice['id']);
                $conn->close();
                jsonResponse([
                    'success' => false,
                    'message' => 'Assignment already invoiced.',
                    'invoice' => $invoice,
                ], 409);
            }
        }

        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Active assignment not found.'], 404);
    }

    $assignedAt = $assignment['assignedAt'];
    $assignedTs = strtotime($assignedAt);
    $returnedTs = strtotime($returnedAt);
    if ($assignedTs !== false && $returnedTs !== false && $returnedTs < $assignedTs) {
        $returnedAt = $assignedAt;
    }

    $metrics = calculateInvoiceMetrics(
        $assignedAt,
        $returnedAt,
        (int) $assignment['freeHours'],
        (float) $assignment['hourlyRateLocked'],
        $vatRate
    );

    $invoiceId = 0;
    $invoiceNumber = generateInvoiceNumber();
    $issuedAt = date('Y-m-d H:i:s');
    $emailTo = stringValue($assignment['carerEmail'] ?? '');

    $conn->begin_transaction();
    try {
        $updateAssignmentStmt = $conn->prepare('UPDATE car_assignments SET returned_at = ?, status = "invoiced", updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = "active"');
        if ($updateAssignmentStmt === false) {
            throw new RuntimeException('Failed to prepare assignment close query.');
        }

        $updateAssignmentStmt->bind_param('si', $returnedAt, $assignmentId);
        if (!$updateAssignmentStmt->execute() || $updateAssignmentStmt->affected_rows < 1) {
            $updateAssignmentStmt->close();
            throw new RuntimeException('Failed to close assignment.');
        }
        $updateAssignmentStmt->close();

        $insertInvoiceSql = 'INSERT INTO car_invoices
            (invoice_number, assignment_id, carer_id, vehicle_id, issued_at, period_start, period_end,
             total_hours, free_hours, billable_hours, hourly_rate, subtotal, vat_rate, vat_amount, total_amount,
             email_to, email_sent, email_sent_at, email_error)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NULL, NULL)';
        $insertInvoiceStmt = $conn->prepare($insertInvoiceSql);
        if ($insertInvoiceStmt === false) {
            throw new RuntimeException('Failed to prepare invoice insert query.');
        }

        $insertInvoiceStmt->bind_param(
            'siiisssiiiddddds',
            $invoiceNumber,
            $assignmentId,
            $assignment['carerId'],
            $assignment['vehicleId'],
            $issuedAt,
            $assignedAt,
            $returnedAt,
            $metrics['totalHours'],
            $metrics['freeHours'],
            $metrics['billableHours'],
            $metrics['hourlyRate'],
            $metrics['subtotal'],
            $metrics['vatRate'],
            $metrics['vatAmount'],
            $metrics['total'],
            $emailTo
        );

        if (!$insertInvoiceStmt->execute()) {
            $insertInvoiceStmt->close();
            throw new RuntimeException('Failed to save invoice.');
        }

        $invoiceId = (int) $insertInvoiceStmt->insert_id;
        $insertInvoiceStmt->close();

        $conn->commit();
    } catch (Throwable $exception) {
        $conn->rollback();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to generate invoice.'], 500);
    }

    $emailSent = false;
    $emailError = '';
    if ($sendEmail && $emailTo !== '') {
        $invoice = fetchInvoiceById($conn, $invoiceId);
        if ($invoice) {
            $subject = 'Car Usage Invoice - ' . $invoice['invoiceNumber'];
            $bodyText = buildInvoiceEmailBody($invoice);
            $emailSent = sendInvoiceEmail($emailTo, $subject, $bodyText, $emailError);
            updateInvoiceEmailStatus($conn, $invoiceId, $emailSent, $emailError);
        }
    }

    try {
        $invoice = fetchInvoiceById($conn, $invoiceId);
        $activeAssignments = fetchActiveAssignments($conn);
        $invoices = fetchInvoices($conn, 60);
        $conn->close();

        $message = 'Invoice generated successfully.';
        if ($sendEmail && $emailTo !== '') {
            $message = $emailSent
                ? 'Invoice generated and emailed successfully.'
                : 'Invoice generated, but email sending failed.';
        } elseif ($sendEmail && $emailTo === '') {
            $message = 'Invoice generated. Carer email is missing, so email was skipped.';
        }

        jsonResponse([
            'success' => true,
            'message' => $message,
            'invoice' => $invoice,
            'activeAssignments' => $activeAssignments,
            'invoices' => $invoices,
        ]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => true, 'message' => 'Invoice generated.']);
    }
}

if ($action === 'resendInvoiceEmail' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoiceId = intValue($body['invoiceId'] ?? 0, 0);
    $toEmail = strtolower(stringValue($body['toEmail'] ?? ''));
    if ($invoiceId <= 0) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Invalid invoice ID.'], 422);
    }

    try {
        $invoice = fetchInvoiceById($conn, $invoiceId);
        if (!$invoice) {
            $conn->close();
            jsonResponse(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        $targetEmail = $toEmail !== '' ? $toEmail : stringValue($invoice['emailTo'] ?? '');
        if ($targetEmail === '' || !filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
            $conn->close();
            jsonResponse(['success' => false, 'message' => 'A valid recipient email is required.'], 422);
        }

        $subject = 'Car Usage Invoice - ' . $invoice['invoiceNumber'];
        $bodyText = buildInvoiceEmailBody($invoice);
        $error = '';
        $sent = sendInvoiceEmail($targetEmail, $subject, $bodyText, $error);

        if ($targetEmail !== stringValue($invoice['emailTo'] ?? '')) {
            $updateEmailStmt = $conn->prepare('UPDATE car_invoices SET email_to = ? WHERE id = ?');
            if ($updateEmailStmt !== false) {
                $updateEmailStmt->bind_param('si', $targetEmail, $invoiceId);
                $updateEmailStmt->execute();
                $updateEmailStmt->close();
            }
        }

        updateInvoiceEmailStatus($conn, $invoiceId, $sent, $error);
        $freshInvoice = fetchInvoiceById($conn, $invoiceId);
        $conn->close();

        if (!$sent) {
            jsonResponse([
                'success' => false,
                'message' => 'Failed to resend invoice email.',
                'invoice' => $freshInvoice,
                'error' => $error,
            ], 500);
        }

        jsonResponse([
            'success' => true,
            'message' => 'Invoice email sent successfully.',
            'invoice' => $freshInvoice,
        ]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to resend invoice email.'], 500);
    }
}

$conn->close();
jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
?>
