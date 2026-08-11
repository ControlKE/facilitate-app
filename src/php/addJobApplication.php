<?php
header('Access-Control-Allow-Origin: *');
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
require_once __DIR__ . '/messageRoutingHelper.php';

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function readPayload(): array
{
    $raw = file_get_contents('php://input');
    $raw = is_string($raw) ? trim($raw) : '';

    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (strlen($raw) > 1) {
            $first = $raw[0];
            $last = $raw[strlen($raw) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $unwrapped = substr($raw, 1, -1);
                $decodedWrapped = json_decode($unwrapped, true);
                if (is_array($decodedWrapped)) {
                    return $decodedWrapped;
                }
            }
        }

        $form = [];
        parse_str($raw, $form);
        if (is_array($form) && !empty($form)) {
            return $form;
        }
    }

    if (!empty($_POST) && is_array($_POST)) {
        return $_POST;
    }

    return [];
}

function strValue($value): string
{
    return trim((string) $value);
}

function boolText($value): string
{
    $normalized = strtoupper(strValue($value));
    if ($normalized === 'YES' || $normalized === 'NO') {
        return $normalized;
    }
    return '';
}

function normalizeDateValue($value): ?string
{
    $raw = strValue($value);
    if ($raw === '') {
        return null;
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d', $timestamp);
}

function columnExists(mysqli $conn, string $tableName, string $columnName): bool
{
    $table = $conn->real_escape_string($tableName);
    $column = $conn->real_escape_string($columnName);
    $sql = "SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND COLUMN_NAME = '{$column}'
            LIMIT 1";
    $result = $conn->query($sql);
    return $result !== false && $result->num_rows > 0;
}

function addColumnIfMissing(mysqli $conn, string $tableName, string $columnName, string $definition): void
{
    if (columnExists($conn, $tableName, $columnName)) {
        return;
    }

    $sql = "ALTER TABLE `{$tableName}` ADD COLUMN {$definition}";
    if (!$conn->query($sql)) {
        throw new RuntimeException("Failed to add column {$columnName}.");
    }
}

function tableColumns(mysqli $conn, string $tableName): array
{
    $table = $conn->real_escape_string($tableName);
    $sql = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
            ORDER BY ORDINAL_POSITION";
    $result = $conn->query($sql);
    if ($result === false) {
        throw new RuntimeException('Unable to inspect job applications table.');
    }

    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $name = (string) ($row['COLUMN_NAME'] ?? '');
        if ($name === '') {
            continue;
        }
        $columns[] = [
            'name' => $name,
            'key' => strtolower($name),
            'type' => strtolower((string) ($row['DATA_TYPE'] ?? '')),
            'nullable' => strtoupper((string) ($row['IS_NULLABLE'] ?? 'NO')) === 'YES',
            'default' => $row['COLUMN_DEFAULT'] ?? null,
            'extra' => strtolower((string) ($row['EXTRA'] ?? '')),
        ];
    }

    return $columns;
}

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function ensureJobApplicationsSchema(mysqli $conn): void
{
    $createSql = <<<SQL
CREATE TABLE IF NOT EXISTS `jobapplications` (
    `ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `Name` VARCHAR(191) NOT NULL DEFAULT '',
    `PhoneNumber` VARCHAR(60) NOT NULL DEFAULT '',
    `Email` VARCHAR(191) NOT NULL DEFAULT '',
    `Message` TEXT NULL,
    `Date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($createSql)) {
        throw new RuntimeException('Failed to initialize job applications table.');
    }

    addColumnIfMissing($conn, 'jobapplications', 'Name', "`Name` VARCHAR(191) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'PhoneNumber', "`PhoneNumber` VARCHAR(60) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'Email', "`Email` VARCHAR(191) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'Message', "`Message` TEXT NULL");
    addColumnIfMissing($conn, 'jobapplications', 'Date', "`Date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");

    addColumnIfMissing($conn, 'jobapplications', 'Title', "`Title` VARCHAR(30) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'FullName', "`FullName` VARCHAR(191) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'JobType', "`JobType` VARCHAR(40) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'HasDomiciliaryExperience', "`HasDomiciliaryExperience` VARCHAR(10) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'ExperienceDuration', "`ExperienceDuration` VARCHAR(80) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'HasDriverLicense', "`HasDriverLicense` VARCHAR(10) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'LicenseType', "`LicenseType` VARCHAR(40) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'InternationalLicenseExpiry', "`InternationalLicenseExpiry` DATE NULL");
    addColumnIfMissing($conn, 'jobapplications', 'UkLicenseType', "`UkLicenseType` VARCHAR(40) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'City', "`City` VARCHAR(120) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'ResidenceArea', "`ResidenceArea` VARCHAR(180) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'jobapplications', 'ResidenceDuration', "`ResidenceDuration` VARCHAR(120) NOT NULL DEFAULT ''");
}

$action = strValue($_GET['action'] ?? '');

if ($action !== 'addpost' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 400);
}

$payload = readPayload();

$fullName = strValue($payload['FullName'] ?? '');
$phoneNumber = strValue($payload['PhoneNumber'] ?? '');
$email = strValue($payload['Email'] ?? '');
$title = strValue($payload['Title'] ?? '');
$jobType = strtoupper(strValue($payload['JobType'] ?? ''));
$hasExperience = boolText($payload['HasDomiciliaryExperience'] ?? '');
$experienceDuration = strValue($payload['ExperienceDuration'] ?? '');
$hasDriverLicense = boolText($payload['HasDriverLicense'] ?? '');
$licenseType = strtoupper(strValue($payload['LicenseType'] ?? ''));
$internationalLicenseExpiry = normalizeDateValue($payload['InternationalLicenseExpiry'] ?? null);
$ukLicenseType = strtoupper(strValue($payload['UkLicenseType'] ?? ''));
$city = strValue($payload['City'] ?? '');
$residenceArea = strValue($payload['ResidenceArea'] ?? '');
$residenceDuration = strValue($payload['ResidenceDuration'] ?? '');

if ($fullName === '' || $phoneNumber === '' || $jobType === '') {
    jsonResponse(['success' => false, 'message' => 'Full name, phone number and job type are required.'], 422);
}

if ($hasExperience !== 'YES') {
    $experienceDuration = '';
}

if ($hasDriverLicense !== 'YES') {
    $licenseType = '';
    $internationalLicenseExpiry = null;
    $ukLicenseType = '';
}

if (strtoupper($city) !== 'OTHER') {
    $residenceArea = '';
}

$summaryLines = [];
$summaryLines[] = 'Job application submitted via caregiver page.';
if ($jobType !== '') {
    $summaryLines[] = 'Job Type: ' . $jobType;
}
if ($hasExperience !== '') {
    $line = 'Domiciliary Experience: ' . $hasExperience;
    if ($hasExperience === 'YES' && $experienceDuration !== '') {
        $line .= ' (' . $experienceDuration . ')';
    }
    $summaryLines[] = $line;
}
if ($hasDriverLicense !== '') {
    $line = 'Driver License: ' . $hasDriverLicense;
    if ($hasDriverLicense === 'YES' && $licenseType !== '') {
        $line .= ' - ' . $licenseType;
    }
    if ($licenseType === 'UK LICENSE' && $ukLicenseType !== '') {
        $line .= ' (' . $ukLicenseType . ')';
    }
    if ($licenseType === 'INTERNATIONAL LICENSE' && $internationalLicenseExpiry !== null) {
        $line .= ' (Expiry: ' . $internationalLicenseExpiry . ')';
    }
    $summaryLines[] = $line;
}
if ($city !== '') {
    $summaryLines[] = 'City: ' . $city;
}
if ($residenceArea !== '') {
    $summaryLines[] = 'Residence Area: ' . $residenceArea;
}
if ($residenceDuration !== '') {
    $summaryLines[] = 'Residence Duration: ' . $residenceDuration;
}
$summary = implode("\n", $summaryLines);
$fullNameParts = preg_split('/\s+/', $fullName) ?: [];
$firstName = trim((string) ($fullNameParts[0] ?? ''));
$secondName = trim(implode(' ', array_slice($fullNameParts, 1)));

try {
    $conn = createDatabaseConnection();
    try {
        ensureJobApplicationsSchema($conn);
    } catch (Throwable $schemaException) {
        // One.com installations may already have the legacy jobapplications table.
        // Keep saving against whatever columns exist instead of blocking the public form.
        error_log('Job application schema check failed: ' . $schemaException->getMessage());
    }

    $fieldValues = [
        'title' => $title,
        'fullname' => $fullName,
        'full_name' => $fullName,
        'name' => $fullName,
        'names' => $fullName,
        'firstname' => $firstName,
        'first_name' => $firstName,
        'secondname' => $secondName,
        'second_name' => $secondName,
        'lastname' => $secondName,
        'last_name' => $secondName,
        'surname' => $secondName,
        'phonenumber' => $phoneNumber,
        'phone_number' => $phoneNumber,
        'phone' => $phoneNumber,
        'telephone' => $phoneNumber,
        'email' => $email,
        'jobtype' => $jobType,
        'job_type' => $jobType,
        'hasdomiciliaryexperience' => $hasExperience,
        'has_domiciliary_experience' => $hasExperience,
        'experienceduration' => $experienceDuration,
        'experience_duration' => $experienceDuration,
        'hasdriverlicense' => $hasDriverLicense,
        'has_driver_license' => $hasDriverLicense,
        'licensetype' => $licenseType,
        'license_type' => $licenseType,
        'internationallicenseexpiry' => $internationalLicenseExpiry,
        'international_license_expiry' => $internationalLicenseExpiry,
        'uklicensetype' => $ukLicenseType,
        'uk_license_type' => $ukLicenseType,
        'city' => $city,
        'residencearea' => $residenceArea,
        'residence_area' => $residenceArea,
        'residenceduration' => $residenceDuration,
        'residence_duration' => $residenceDuration,
        'message' => $summary,
        'notes' => $summary,
    ];

    $columns = tableColumns($conn, 'jobapplications');
    $insertColumns = [];
    $placeholders = [];
    $values = [];

    foreach ($columns as $column) {
        $columnName = (string) $column['name'];
        $columnKey = (string) $column['key'];
        $columnType = (string) $column['type'];
        $extra = (string) $column['extra'];

        if (strpos($extra, 'auto_increment') !== false || strpos($extra, 'generated') !== false) {
            continue;
        }

        if ($columnKey === 'date' || $columnKey === 'created_at' || $columnKey === 'submitted_at') {
            $insertColumns[] = quoteIdentifier($columnName);
            $placeholders[] = 'NOW()';
            continue;
        }

        $hasKnownValue = array_key_exists($columnKey, $fieldValues);
        $value = $hasKnownValue ? $fieldValues[$columnKey] : null;

        if (!$hasKnownValue && !$column['nullable'] && $column['default'] === null) {
            $value = in_array($columnType, ['int', 'bigint', 'smallint', 'tinyint', 'mediumint', 'decimal', 'float', 'double'], true) ? '0' : '';
            $hasKnownValue = true;
        }

        if (!$hasKnownValue) {
            continue;
        }

        $insertColumns[] = quoteIdentifier($columnName);
        $placeholders[] = '?';
        $values[] = $value === null ? null : (string) $value;
    }

    if (empty($insertColumns)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Job applications table is missing the required save columns.'], 500);
    }

    $sql = 'INSERT INTO `jobapplications` (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $placeholders) . ')';

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $prepareError = $conn->error;
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare job application query: ' . $prepareError], 500);
    }

    if (!empty($values)) {
        $types = str_repeat('s', count($values));
        $bindParams = array_merge([$types], $values);
        $bindRefs = [];
        foreach ($bindParams as $key => $value) {
            $bindRefs[$key] = &$bindParams[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindRefs);
    }

    if (!$stmt->execute()) {
        $message = $stmt->error ? 'Failed to save job application: ' . $stmt->error : 'Failed to save job application.';
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => $message], 500);
    }

    $newId = (int) $stmt->insert_id;
    $stmt->close();
    $notification = safeDispatchCategoryNotification($conn, MESSAGE_CATEGORY_JOB_APPLICATIONS, [
        'submittedAt' => date('Y-m-d H:i:s'),
        'title' => $title,
        'fullName' => $fullName,
        'email' => $email,
        'phone' => $phoneNumber,
        'jobType' => $jobType,
        'hasDomiciliaryExperience' => $hasExperience,
        'experienceDuration' => $experienceDuration,
        'hasDriverLicense' => $hasDriverLicense,
        'licenseType' => $licenseType,
        'internationalLicenseExpiry' => $internationalLicenseExpiry,
        'ukLicenseType' => $ukLicenseType,
        'city' => $city,
        'residenceArea' => $residenceArea,
        'residenceDuration' => $residenceDuration,
        'message' => $summary,
    ]);
    $conn->close();

    jsonResponse([
        'success' => true,
        'id' => $newId,
        'message' => $notification['sent']
            ? 'Job application submitted successfully.'
            : 'Job application saved, but the email notification could not be sent.',
        'notification' => $notification,
    ]);
} catch (Throwable $exception) {
    error_log('Unable to save job application: ' . $exception->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Unable to save job application: ' . $exception->getMessage(),
    ], 500);
}
?>
