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

$action = isset($_GET['action']) ? $_GET['action'] : '';

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

function fetchContactRows(string $source): array
{
    $connections = createDatabaseConnectionsForRead($source);
    $rows = [];
    $hasSuccessfulQuery = false;

    foreach ($connections as $connectionSource => $conn) {
        $result = $conn->query('SELECT * FROM contactform');
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
        throw new RuntimeException('Failed to load enquiries from available data sources.');
    }

    usort($rows, static function ($first, $second) {
        $firstTime = strtotime($first['Date'] ?? '') ?: 0;
        $secondTime = strtotime($second['Date'] ?? '') ?: 0;
        return $secondTime <=> $firstTime;
    });

    return $rows;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'getContact') {
    try {
        $source = normalizeDbSource((string) ($_GET['source'] ?? 'auto'));
        $data = fetchContactRows($source);
        echo json_encode($data);
    } catch (Throwable $exception) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load enquiries.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'deleteContact') {
    $request = resolveDeleteRequest();
    $id = $request['id'];
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid enquiry ID.']);
        exit;
    }

    $conn = null;
    try {
        $deleteSource = resolveWriteSource($request['source']);
        $conn = createDatabaseConnection($deleteSource);
    } catch (Throwable $exception) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit;
    }

    $stmt = $conn->prepare('DELETE FROM contactform WHERE ID = ?');
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to prepare delete query.']);
        $conn->close();
        exit;
    }

    $stmt->bind_param('i', $id);
    $executed = $stmt->execute();

    if ($executed === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete enquiry.']);
        $stmt->close();
        $conn->close();
        exit;
    }

    if ($stmt->affected_rows < 1) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Enquiry not found.']);
        $stmt->close();
        $conn->close();
        exit;
    }

    echo json_encode(['success' => true, 'id' => $id, 'source' => $deleteSource]);
    $stmt->close();
    $conn->close();
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
?>
