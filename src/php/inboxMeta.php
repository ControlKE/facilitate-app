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

function normalizeInboxType($value): string
{
    $normalized = strtolower(trim((string) $value));
    $map = [
        'complaint' => 'complaints',
        'complaints' => 'complaints',
        'contact' => 'contact',
        'contacts' => 'contact',
        'contactform' => 'contact',
        'thanks' => 'thanks',
        'carethanks' => 'thanks',
        'carerthanks' => 'thanks',
        'jobapplication' => 'jobapplications',
        'jobapplications' => 'jobapplications',
        'jobs' => 'jobapplications',
    ];

    return $map[$normalized] ?? '';
}

function normalizeSourceValue($value): string
{
    $normalized = strtolower(trim((string) $value));
    if ($normalized === 'remote') {
        return 'remote';
    }
    return 'local';
}

function normalizeMessageId($value): string
{
    return trim((string) $value);
}

function normalizeStatus($value): string
{
    $normalized = strtolower(trim((string) $value));
    $allowed = ['new', 'in_progress', 'resolved', 'closed'];
    return in_array($normalized, $allowed, true) ? $normalized : 'new';
}

function normalizePriority($value): string
{
    $normalized = strtolower(trim((string) $value));
    $allowed = ['low', 'normal', 'high', 'urgent'];
    return in_array($normalized, $allowed, true) ? $normalized : 'normal';
}

function normalizeDateTimeNullable($value): ?string
{
    if ($value === null) {
        return null;
    }

    $raw = trim((string) $value);
    if ($raw === '') {
        return null;
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function normalizeMetaRow(array $row): array
{
    return [
        'status' => normalizeStatus($row['status'] ?? 'new'),
        'assignedTo' => trim((string) ($row['assigned_to'] ?? '')),
        'isRead' => (int) ($row['is_read'] ?? 0) === 1,
        'priority' => normalizePriority($row['priority'] ?? 'normal'),
        'followUpAt' => $row['follow_up_at'] ?? null,
        'deletedAt' => $row['deleted_at'] ?? null,
        'updatedAt' => $row['updated_at'] ?? null,
    ];
}

function metaKey(string $source, string $messageId): string
{
    return $source . '|' . $messageId;
}

function ensureInboxTables(mysqli $conn): void
{
    $metaSql = <<<SQL
CREATE TABLE IF NOT EXISTS inbox_meta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inbox_type VARCHAR(40) NOT NULL,
    record_source VARCHAR(20) NOT NULL DEFAULT 'local',
    message_id VARCHAR(80) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    assigned_to VARCHAR(140) NOT NULL DEFAULT '',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    priority VARCHAR(20) NOT NULL DEFAULT 'normal',
    follow_up_at DATETIME NULL,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY inbox_meta_unique (inbox_type, record_source, message_id),
    KEY inbox_meta_lookup (inbox_type, record_source, message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    $notesSql = <<<SQL
CREATE TABLE IF NOT EXISTS inbox_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inbox_type VARCHAR(40) NOT NULL,
    record_source VARCHAR(20) NOT NULL DEFAULT 'local',
    message_id VARCHAR(80) NOT NULL,
    note_text TEXT NOT NULL,
    author VARCHAR(140) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY inbox_notes_lookup (inbox_type, record_source, message_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    $repliesSql = <<<SQL
CREATE TABLE IF NOT EXISTS inbox_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inbox_type VARCHAR(40) NOT NULL,
    record_source VARCHAR(20) NOT NULL DEFAULT 'local',
    message_id VARCHAR(80) NOT NULL,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    sent_by VARCHAR(140) NOT NULL DEFAULT '',
    sent_success TINYINT(1) NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY inbox_replies_lookup (inbox_type, record_source, message_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($metaSql) || !$conn->query($notesSql) || !$conn->query($repliesSql)) {
        throw new RuntimeException('Failed to initialize inbox tables.');
    }
}

function getMetaRow(mysqli $conn, string $inboxType, string $source, string $messageId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM inbox_meta WHERE inbox_type = ? AND record_source = ? AND message_id = ? LIMIT 1');
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare meta query.');
    }

    $stmt->bind_param('sss', $inboxType, $source, $messageId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Failed to execute meta query.');
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function upsertMeta(mysqli $conn, string $inboxType, string $source, string $messageId, array $patch): array
{
    $existing = getMetaRow($conn, $inboxType, $source, $messageId);
    $base = $existing ? normalizeMetaRow($existing) : [
        'status' => 'new',
        'assignedTo' => '',
        'isRead' => false,
        'priority' => 'normal',
        'followUpAt' => null,
        'deletedAt' => null,
        'updatedAt' => null,
    ];

    $merged = $base;

    if (array_key_exists('status', $patch)) {
        $merged['status'] = normalizeStatus($patch['status']);
    }
    if (array_key_exists('assignedTo', $patch)) {
        $merged['assignedTo'] = trim((string) $patch['assignedTo']);
    }
    if (array_key_exists('isRead', $patch)) {
        $merged['isRead'] = (bool) $patch['isRead'];
    }
    if (array_key_exists('priority', $patch)) {
        $merged['priority'] = normalizePriority($patch['priority']);
    }
    if (array_key_exists('followUpAt', $patch)) {
        $merged['followUpAt'] = normalizeDateTimeNullable($patch['followUpAt']);
    }
    if (array_key_exists('deletedAt', $patch)) {
        $merged['deletedAt'] = normalizeDateTimeNullable($patch['deletedAt']);
    }

    $status = $merged['status'];
    $assignedTo = $merged['assignedTo'];
    $isRead = $merged['isRead'] ? 1 : 0;
    $priority = $merged['priority'];
    $followUpAt = $merged['followUpAt'];
    $deletedAt = $merged['deletedAt'];

    $sql = 'INSERT INTO inbox_meta (inbox_type, record_source, message_id, status, assigned_to, is_read, priority, follow_up_at, deleted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                assigned_to = VALUES(assigned_to),
                is_read = VALUES(is_read),
                priority = VALUES(priority),
                follow_up_at = VALUES(follow_up_at),
                deleted_at = VALUES(deleted_at),
                updated_at = CURRENT_TIMESTAMP';

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare meta upsert query.');
    }

    $stmt->bind_param(
        'sssssisss',
        $inboxType,
        $source,
        $messageId,
        $status,
        $assignedTo,
        $isRead,
        $priority,
        $followUpAt,
        $deletedAt
    );

    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Failed to save inbox metadata.');
    }

    $stmt->close();
    $fresh = getMetaRow($conn, $inboxType, $source, $messageId);
    return $fresh ? normalizeMetaRow($fresh) : $merged;
}

function sendMailboxReply(string $toEmail, string $subject, string $body, string $sentBy, string &$errorMessage): bool
{
    $errorMessage = '';
    ensureVendorAutoload();

    if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
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

            $mail->setFrom('steve@facilitatecareservices.co.uk', $sentBy !== '' ? $sentBy : 'Facilitate Care Services');
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
    if ($sentBy !== '') {
        $headers .= "Reply-To: steve@facilitatecareservices.co.uk\r\n";
    }

    $mailSent = @mail($toEmail, $subject, $body, $headers);
    if ($mailSent) {
        return true;
    }

    if ($errorMessage === '') {
        $errorMessage = 'Unable to send email from server.';
    }
    return false;
}

$action = $_GET['action'] ?? '';

try {
    $conn = createDatabaseConnection();
    ensureInboxTables($conn);
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'message' => 'Failed to initialize inbox metadata service.'], 500);
}

$body = getJsonBody();
$query = $_GET;

if ($action === 'getMetaBatch' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $inboxType = normalizeInboxType($body['inboxType'] ?? '');
    $records = is_array($body['records'] ?? null) ? $body['records'] : [];

    if ($inboxType === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Invalid inbox type.'], 422);
    }

    $requestedKeys = [];
    foreach ($records as $record) {
        if (!is_array($record)) {
            continue;
        }
        $messageId = normalizeMessageId($record['messageId'] ?? '');
        if ($messageId === '') {
            continue;
        }
        $source = normalizeSourceValue($record['source'] ?? 'local');
        $requestedKeys[metaKey($source, $messageId)] = true;
    }

    $stmt = $conn->prepare('SELECT * FROM inbox_meta WHERE inbox_type = ?');
    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare metadata query.'], 500);
    }

    $stmt->bind_param('s', $inboxType);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to load metadata.'], 500);
    }

    $result = $stmt->get_result();
    $meta = [];
    while ($row = $result->fetch_assoc()) {
        $key = metaKey($row['record_source'], $row['message_id']);
        if (!isset($requestedKeys[$key])) {
            continue;
        }
        $meta[$key] = normalizeMetaRow($row);
    }

    $stmt->close();
    $conn->close();
    jsonResponse(['success' => true, 'meta' => $meta]);
}

if ($action === 'upsertMeta' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $inboxType = normalizeInboxType($body['inboxType'] ?? '');
    $messageId = normalizeMessageId($body['messageId'] ?? '');
    $source = normalizeSourceValue($body['source'] ?? 'local');
    $patch = is_array($body['patch'] ?? null) ? $body['patch'] : [];

    if ($inboxType === '' || $messageId === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Invalid metadata target.'], 422);
    }

    try {
        $meta = upsertMeta($conn, $inboxType, $source, $messageId, $patch);
        $conn->close();
        jsonResponse(['success' => true, 'meta' => $meta]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to save metadata.'], 500);
    }
}

if ($action === 'bulkUpdate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $inboxType = normalizeInboxType($body['inboxType'] ?? '');
    $records = is_array($body['records'] ?? null) ? $body['records'] : [];
    $patch = is_array($body['patch'] ?? null) ? $body['patch'] : [];

    if ($inboxType === '' || empty($records)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Invalid bulk update request.'], 422);
    }

    $updatedCount = 0;
    foreach ($records as $record) {
        if (!is_array($record)) {
            continue;
        }

        $messageId = normalizeMessageId($record['messageId'] ?? '');
        if ($messageId === '') {
            continue;
        }
        $source = normalizeSourceValue($record['source'] ?? 'local');

        try {
            upsertMeta($conn, $inboxType, $source, $messageId, $patch);
            $updatedCount++;
        } catch (Throwable $exception) {
            // Continue updating other records, then report partial results.
        }
    }

    $conn->close();
    jsonResponse(['success' => true, 'updated' => $updatedCount]);
}

if ($action === 'getNotes' && ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST')) {
    $inboxType = normalizeInboxType($query['inboxType'] ?? ($body['inboxType'] ?? ''));
    $messageId = normalizeMessageId($query['messageId'] ?? ($body['messageId'] ?? ''));
    $source = normalizeSourceValue($query['source'] ?? ($body['source'] ?? 'local'));

    if ($inboxType === '' || $messageId === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Invalid notes target.'], 422);
    }

    $stmt = $conn->prepare('SELECT id, note_text, author, created_at FROM inbox_notes WHERE inbox_type = ? AND record_source = ? AND message_id = ? ORDER BY created_at DESC');
    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare notes query.'], 500);
    }

    $stmt->bind_param('sss', $inboxType, $source, $messageId);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to load notes.'], 500);
    }

    $result = $stmt->get_result();
    $notes = [];
    while ($row = $result->fetch_assoc()) {
        $notes[] = [
            'id' => (int) $row['id'],
            'note' => $row['note_text'],
            'author' => $row['author'],
            'createdAt' => $row['created_at'],
        ];
    }

    $stmt->close();
    $conn->close();
    jsonResponse(['success' => true, 'notes' => $notes]);
}

if ($action === 'addNote' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $inboxType = normalizeInboxType($body['inboxType'] ?? '');
    $messageId = normalizeMessageId($body['messageId'] ?? '');
    $source = normalizeSourceValue($body['source'] ?? 'local');
    $note = trim((string) ($body['note'] ?? ''));
    $author = trim((string) ($body['author'] ?? ''));

    if ($inboxType === '' || $messageId === '' || $note === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Invalid note payload.'], 422);
    }

    $stmt = $conn->prepare('INSERT INTO inbox_notes (inbox_type, record_source, message_id, note_text, author) VALUES (?, ?, ?, ?, ?)');
    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare note insert.'], 500);
    }

    $stmt->bind_param('sssss', $inboxType, $source, $messageId, $note, $author);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to save note.'], 500);
    }

    $noteId = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    jsonResponse([
        'success' => true,
        'note' => [
            'id' => (int) $noteId,
            'note' => $note,
            'author' => $author,
            'createdAt' => date('Y-m-d H:i:s'),
        ],
    ]);
}

if ($action === 'getReplies' && ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST')) {
    $inboxType = normalizeInboxType($query['inboxType'] ?? ($body['inboxType'] ?? ''));
    $messageId = normalizeMessageId($query['messageId'] ?? ($body['messageId'] ?? ''));
    $source = normalizeSourceValue($query['source'] ?? ($body['source'] ?? 'local'));

    if ($inboxType === '' || $messageId === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Invalid reply-history target.'], 422);
    }

    $stmt = $conn->prepare('SELECT id, to_email, subject, body, sent_by, sent_success, error_message, created_at
                            FROM inbox_replies
                            WHERE inbox_type = ? AND record_source = ? AND message_id = ?
                            ORDER BY created_at DESC');
    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare reply-history query.'], 500);
    }

    $stmt->bind_param('sss', $inboxType, $source, $messageId);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to load reply history.'], 500);
    }

    $result = $stmt->get_result();
    $replies = [];
    while ($row = $result->fetch_assoc()) {
        $replies[] = [
            'id' => (int) $row['id'],
            'toEmail' => $row['to_email'],
            'subject' => $row['subject'],
            'body' => $row['body'],
            'sentBy' => $row['sent_by'],
            'sentSuccess' => (int) $row['sent_success'] === 1,
            'errorMessage' => $row['error_message'],
            'createdAt' => $row['created_at'],
        ];
    }

    $stmt->close();
    $conn->close();
    jsonResponse(['success' => true, 'replies' => $replies]);
}

if ($action === 'sendReply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $inboxType = normalizeInboxType($body['inboxType'] ?? '');
    $messageId = normalizeMessageId($body['messageId'] ?? '');
    $source = normalizeSourceValue($body['source'] ?? 'local');
    $toEmail = trim((string) ($body['toEmail'] ?? ''));
    $subject = trim((string) ($body['subject'] ?? ''));
    $replyBody = trim((string) ($body['body'] ?? ''));
    $sentBy = trim((string) ($body['sentBy'] ?? ''));

    if ($inboxType === '' || $messageId === '' || $toEmail === '' || $subject === '' || $replyBody === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Missing reply fields.'], 422);
    }

    $errorMessage = '';
    $sentSuccess = sendMailboxReply($toEmail, $subject, $replyBody, $sentBy, $errorMessage);

    $insertSql = 'INSERT INTO inbox_replies (inbox_type, record_source, message_id, to_email, subject, body, sent_by, sent_success, error_message)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $conn->prepare($insertSql);
    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare reply history insert.'], 500);
    }

    $sentFlag = $sentSuccess ? 1 : 0;
    $stmt->bind_param('sssssssis', $inboxType, $source, $messageId, $toEmail, $subject, $replyBody, $sentBy, $sentFlag, $errorMessage);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to save reply history.'], 500);
    }

    $replyId = $stmt->insert_id;
    $stmt->close();

    // Mark item as read and in-progress when a reply is sent/logged.
    try {
        upsertMeta($conn, $inboxType, $source, $messageId, [
            'isRead' => true,
            'status' => 'in_progress',
        ]);
    } catch (Throwable $exception) {
        // Non-fatal for sending reply.
    }

    $conn->close();
    jsonResponse([
        'success' => $sentSuccess,
        'logged' => true,
        'message' => $sentSuccess ? 'Reply sent successfully.' : ('Reply was logged but sending failed: ' . $errorMessage),
        'reply' => [
            'id' => (int) $replyId,
            'toEmail' => $toEmail,
            'subject' => $subject,
            'body' => $replyBody,
            'sentBy' => $sentBy,
            'sentSuccess' => $sentSuccess,
            'errorMessage' => $errorMessage !== '' ? $errorMessage : null,
            'createdAt' => date('Y-m-d H:i:s'),
        ],
    ]);
}

$conn->close();
jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
?>
