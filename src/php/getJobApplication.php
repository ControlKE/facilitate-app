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

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function stringValue($value): string
{
    return trim((string) $value);
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

function resolveDeleteRequest(): array
{
    $payload = json_decode(file_get_contents('php://input'), true);
    $id = 0;
    $source = 'auto';

    if (is_array($payload)) {
        if (isset($payload['id'])) {
            $id = (int) $payload['id'];
        }
        if (isset($payload['source'])) {
            $source = (string) $payload['source'];
        }
    }

    if (isset($_POST['id'])) {
        $id = (int) $_POST['id'];
    }
    if (isset($_POST['source'])) {
        $source = (string) $_POST['source'];
    }

    if (isset($_GET['id'])) {
        $id = (int) $_GET['id'];
    }
    if (isset($_GET['source'])) {
        $source = (string) $_GET['source'];
    }

    return [
        'id' => $id,
        'source' => normalizeDbSource($source),
    ];
}

function fetchJobApplicationRows(string $source): array
{
    $connections = createDatabaseConnectionsForRead($source);
    $rows = [];
    $hasSuccessfulQuery = false;

    foreach ($connections as $connectionSource => $conn) {
        ensureJobApplicationsSchema($conn);
        $result = $conn->query('SELECT * FROM jobapplications');
        if ($result !== false) {
            $hasSuccessfulQuery = true;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row['__source'] = $connectionSource;
                    $rows[] = $row;
                }
            }
        }
        $conn->close();
    }

    if (!$hasSuccessfulQuery) {
        throw new RuntimeException('Failed to load job applications from available data sources.');
    }

    usort($rows, static function ($first, $second) {
        $firstTime = strtotime($first['Date'] ?? '') ?: 0;
        $secondTime = strtotime($second['Date'] ?? '') ?: 0;
        return $secondTime <=> $firstTime;
    });

    return $rows;
}

$action = stringValue($_GET['action'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && $action === 'getJobApplication') {
    try {
        $source = normalizeDbSource((string) ($_GET['source'] ?? 'auto'));
        $data = fetchJobApplicationRows($source);
        echo json_encode($data);
    } catch (Throwable $exception) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load job applications.']);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $action === 'deleteJobApplication') {
    $request = resolveDeleteRequest();
    $id = (int) ($request['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid job application ID.'], 422);
    }

    $conn = null;
    try {
        $deleteSource = resolveWriteSource((string) ($request['source'] ?? 'auto'));
        $conn = createDatabaseConnection($deleteSource);
        ensureJobApplicationsSchema($conn);
    } catch (Throwable $exception) {
        if ($conn instanceof mysqli) {
            $conn->close();
        }
        jsonResponse(['success' => false, 'message' => 'Database connection failed.'], 500);
    }

    $stmt = $conn->prepare('DELETE FROM jobapplications WHERE ID = ?');
    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Unable to prepare delete query.'], 500);
    }

    $stmt->bind_param('i', $id);
    $executed = $stmt->execute();

    if ($executed === false) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to delete job application.'], 500);
    }

    if ($stmt->affected_rows < 1) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Job application not found.'], 404);
    }

    $stmt->close();
    $conn->close();
    jsonResponse(['success' => true, 'id' => $id, 'source' => $deleteSource]);
}

jsonResponse(['success' => false, 'message' => 'Invalid request.'], 400);
?>
