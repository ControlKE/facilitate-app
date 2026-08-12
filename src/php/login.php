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

if ($requestOrigin !== '' && $isAllowedOrigin) {
    header_remove('Access-Control-Allow-Origin');
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Vary: Origin');
} else {
    // With credentials enabled, wildcard origin is invalid.
    // For non-browser clients (missing Origin) use local dev origin by default.
    header_remove('Access-Control-Allow-Origin');
    header('Access-Control-Allow-Origin: http://localhost:5173');
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, X-Auth-Token, Authorization, Accept');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Safety net: turn any otherwise-uncaught error/exception (e.g. a mysqli
// query error, which PHP 8.1+ throws by default instead of returning false)
// into a JSON response instead of a blank Apache error page. Detail is only
// exposed to local dev origins so production doesn't leak internals.
set_exception_handler(static function (Throwable $exception) use ($isLocalOrigin) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    $payload = [
        'success' => false,
        'message' => 'An unexpected server error occurred.',
    ];
    if ($isLocalOrigin) {
        $payload['debugError'] = $exception->getMessage();
        $payload['debugErrorFile'] = $exception->getFile() . ':' . $exception->getLine();
    }
    echo json_encode($payload);
    exit;
});

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/messageRoutingHelper.php';

const ROLE_DIRECTOR = 'director';
const ROLE_MANAGER = 'manager';
const ROLE_CARE_COORDINATOR = 'care_coordinator';
const ROLE_CARER = 'carer';
const PASSWORD_RESET_TTL_MINUTES = 30;

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

function readJsonPayload(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || stringValue($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function roleLabels(): array
{
    return [
        ROLE_DIRECTOR => 'Director',
        ROLE_MANAGER => 'Manager',
        ROLE_CARE_COORDINATOR => 'Care Coordinator',
        ROLE_CARER => 'Carer',
    ];
}

function normalizeRoleKey(string $value): string
{
    $normalized = strtolower(trim($value));
    $normalized = str_replace(['-', ' '], '_', $normalized);

    if ($normalized === 'carecoordinator') {
        $normalized = ROLE_CARE_COORDINATOR;
    }

    if (array_key_exists($normalized, roleLabels())) {
        return $normalized;
    }

    return ROLE_CARE_COORDINATOR;
}

function permissionCatalog(): array
{
    return [
        ['key' => 'dashboard.analytics', 'group' => 'Overview', 'label' => 'Analytics Dashboard', 'description' => 'View analytics dashboard widgets.'],
        ['key' => 'inbox.general_enquiries', 'group' => 'Inbox', 'label' => 'General Enquiries', 'description' => 'View and manage general enquiry inbox.'],
        ['key' => 'inbox.complaints', 'group' => 'Inbox', 'label' => 'Complaints', 'description' => 'View and manage complaints inbox.'],
        ['key' => 'inbox.care_thanks', 'group' => 'Inbox', 'label' => 'Carer Thanks', 'description' => 'View and manage carer thanks messages.'],
        ['key' => MESSAGE_ROUTING_PERMISSION_KEY, 'group' => 'Inbox', 'label' => 'Inbox Email Routing', 'description' => 'Choose which email addresses receive website inbox notifications.'],
        ['key' => 'cars.dashboard', 'group' => 'Cars', 'label' => 'Car Dashboard', 'description' => 'View car operations dashboard.'],
        ['key' => 'cars.allocate', 'group' => 'Cars', 'label' => 'Car Allocation', 'description' => 'Assign and return cars.'],
        ['key' => 'cars.maintenance', 'group' => 'Cars', 'label' => 'Maintenance Log', 'description' => 'Manage maintenance records and off-road vehicles.'],
        ['key' => 'cars.directory', 'group' => 'Cars', 'label' => 'Vehicle Directory', 'description' => 'View and update company vehicle directory.'],
        ['key' => 'routes.optimiser', 'group' => 'Care Planning', 'label' => 'Route Optimiser', 'description' => 'Manage client route runs and suggested visit order.'],
        ['key' => 'files.google_drive', 'group' => 'Files', 'label' => 'Google Drive', 'description' => 'Browse shared client files and folders on Google Drive.'],
        ['key' => 'mileage.final_approval', 'group' => 'Mileage', 'label' => 'Final Mileage Approval', 'description' => 'Give final sign-off on mileage claims after office verification against Access Care Planning.'],
        ['key' => 'website.content', 'group' => 'Website', 'label' => 'Website Content', 'description' => 'Edit website content registry and media values.'],
        ['key' => 'mileage.claims', 'group' => 'Mileage', 'label' => 'Mileage Claims', 'description' => 'Create, submit, review, and report weekly mileage claims.'],
        ['key' => 'users.manage_accounts', 'group' => 'Users', 'label' => 'Manage Accounts', 'description' => 'Create users and view account list.'],
        ['key' => 'users.manage_permissions', 'group' => 'Users', 'label' => 'Manage Role Access', 'description' => 'Edit role permissions matrix.'],
    ];
}

function permissionKeys(): array
{
    $keys = [];
    foreach (permissionCatalog() as $item) {
        $keys[] = $item['key'];
    }
    return $keys;
}

function buildPermissionMap(bool $value): array
{
    $map = [];
    foreach (permissionKeys() as $key) {
        $map[$key] = $value;
    }
    return $map;
}

function defaultRolePermissionMatrix(): array
{
    $allEnabled = buildPermissionMap(true);
    $allDisabled = buildPermissionMap(false);

    return [
        ROLE_DIRECTOR => $allEnabled,
        ROLE_MANAGER => array_merge($allEnabled, [
            'users.manage_permissions' => false,
        ]),
        ROLE_CARE_COORDINATOR => array_merge($allDisabled, [
            'dashboard.analytics' => true,
            'inbox.general_enquiries' => true,
            'inbox.complaints' => true,
            'inbox.care_thanks' => true,
            'cars.dashboard' => true,
            'cars.allocate' => true,
            'cars.maintenance' => true,
            'cars.directory' => true,
            'routes.optimiser' => true,
            'files.google_drive' => true,
            'mileage.claims' => true,
        ]),
        ROLE_CARER => array_merge($allDisabled, [
            'cars.dashboard' => true,
            'mileage.claims' => true,
        ]),
    ];
}

function defaultPermissionsForRole(string $roleKey): array
{
    $role = normalizeRoleKey($roleKey);
    $matrix = defaultRolePermissionMatrix();
    return $matrix[$role] ?? $matrix[ROLE_CARE_COORDINATOR];
}

function normalizePermissionMap($input, string $roleKey): array
{
    $normalized = defaultPermissionsForRole($roleKey);
    if (!is_array($input)) {
        return $normalized;
    }

    foreach (permissionKeys() as $key) {
        if (array_key_exists($key, $input)) {
            $normalized[$key] = (bool) $input[$key];
        }
    }

    return $normalized;
}

function columnExists(mysqli $conn, string $tableName, string $columnName): bool
{
    $table = $conn->real_escape_string($tableName);
    $column = $conn->real_escape_string($columnName);
    $sql = "SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND COLUMN_NAME = '{$column}'
            LIMIT 1";
    $result = $conn->query($sql);
    return $result !== false && $result->num_rows > 0;
}

function indexExists(mysqli $conn, string $tableName, string $indexName): bool
{
    $table = $conn->real_escape_string($tableName);
    $index = $conn->real_escape_string($indexName);
    $sql = "SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND INDEX_NAME = '{$index}'
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
        throw new RuntimeException("Failed to add column {$columnName} to {$tableName}: {$conn->error}");
    }
}

function addIndexIfMissing(mysqli $conn, string $tableName, string $indexName, string $alterStatement): void
{
    if (indexExists($conn, $tableName, $indexName)) {
        return;
    }

    if (!$conn->query($alterStatement)) {
        throw new RuntimeException("Failed to add index {$indexName} to {$tableName}: {$conn->error}");
    }
}

function ensureAccountsSchema(mysqli $conn): void
{
    addColumnIfMissing($conn, 'accounts', 'full_name', "`full_name` VARCHAR(160) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'accounts', 'username', "`username` VARCHAR(120) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'accounts', 'role_key', "`role_key` VARCHAR(40) NOT NULL DEFAULT 'manager'");
    addColumnIfMissing($conn, 'accounts', 'is_active', "`is_active` TINYINT(1) NOT NULL DEFAULT 1");
    addColumnIfMissing($conn, 'accounts', 'created_at', "`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    addColumnIfMissing($conn, 'accounts', 'updated_at', "`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

    addIndexIfMissing($conn, 'accounts', 'accounts_role_idx', 'ALTER TABLE `accounts` ADD KEY `accounts_role_idx` (`role_key`)');
    addIndexIfMissing($conn, 'accounts', 'accounts_active_idx', 'ALTER TABLE `accounts` ADD KEY `accounts_active_idx` (`is_active`)');
    addIndexIfMissing($conn, 'accounts', 'accounts_email_idx', 'ALTER TABLE `accounts` ADD KEY `accounts_email_idx` (`Email`)');
    addIndexIfMissing($conn, 'accounts', 'accounts_username_idx', 'ALTER TABLE `accounts` ADD KEY `accounts_username_idx` (`username`)');

    $conn->query("UPDATE `accounts` SET `role_key` = 'manager' WHERE `role_key` IS NULL OR TRIM(`role_key`) = ''");
    $conn->query("UPDATE `accounts` SET `is_active` = 1 WHERE `is_active` IS NULL");
    $conn->query("UPDATE `accounts` SET `full_name` = SUBSTRING_INDEX(`Email`, '@', 1) WHERE `full_name` IS NULL OR TRIM(`full_name`) = ''");
    $conn->query("UPDATE `accounts` SET `username` = SUBSTRING_INDEX(`Email`, '@', 1) WHERE `username` IS NULL OR TRIM(`username`) = ''");

    $directorCountResult = $conn->query("SELECT COUNT(*) AS total FROM `accounts` WHERE `role_key` = 'director'");
    $directorCount = 0;
    if ($directorCountResult !== false) {
        $directorRow = $directorCountResult->fetch_assoc();
        $directorCount = (int) ($directorRow['total'] ?? 0);
    }

    if ($directorCount === 0) {
        $conn->query("UPDATE `accounts`
                      SET `role_key` = 'director'
                      WHERE `ID` = (
                          SELECT min_id FROM (
                              SELECT MIN(`ID`) AS min_id FROM `accounts`
                          ) AS t
                      )");
    }
}

function ensureRolePermissionsTable(mysqli $conn): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(40) NOT NULL,
    permission_key VARCHAR(80) NOT NULL,
    is_allowed TINYINT(1) NOT NULL DEFAULT 0,
    updated_by INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY role_permission_unique (role_key, permission_key),
    KEY role_permission_permission_idx (permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($sql)) {
        throw new RuntimeException('Failed to initialize role permissions table.');
    }
}

function ensurePasswordResetsTable(mysqli $conn): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id INT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL DEFAULT NULL,
    requested_ip VARCHAR(64) NULL DEFAULT NULL,
    user_agent VARCHAR(255) NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY password_resets_token_hash_uq (token_hash),
    KEY password_resets_account_idx (account_id),
    KEY password_resets_expires_idx (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($sql)) {
        throw new RuntimeException('Failed to initialize password reset table.');
    }
}

function cleanupPasswordResets(mysqli $conn): void
{
    // Remove stale reset records to keep table size bounded.
    $conn->query("DELETE FROM password_resets
                  WHERE (used_at IS NOT NULL AND used_at < DATE_SUB(NOW(), INTERVAL 30 DAY))
                     OR (expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY))");
}

function seedRolePermissions(mysqli $conn): void
{
    $matrix = defaultRolePermissionMatrix();
    $stmt = $conn->prepare('INSERT IGNORE INTO role_permissions (role_key, permission_key, is_allowed) VALUES (?, ?, ?)');
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare role permission seed query.');
    }

    foreach ($matrix as $roleKey => $permissions) {
        foreach ($permissions as $permissionKey => $isAllowed) {
            $allowedInt = $isAllowed ? 1 : 0;
            $stmt->bind_param('ssi', $roleKey, $permissionKey, $allowedInt);
            $stmt->execute();
        }
    }

    $stmt->close();
}

function ensureAuthSchema(mysqli $conn): void
{
    ensureAccountsSchema($conn);
    ensureRolePermissionsTable($conn);
    ensurePasswordResetsTable($conn);
    seedRolePermissions($conn);
    cleanupPasswordResets($conn);
}

function fetchPermissionsForRole(mysqli $conn, string $roleKey): array
{
    $role = normalizeRoleKey($roleKey);
    $permissions = defaultPermissionsForRole($role);

    $stmt = $conn->prepare('SELECT permission_key, is_allowed FROM role_permissions WHERE role_key = ?');
    if ($stmt === false) {
        return $permissions;
    }

    $stmt->bind_param('s', $role);
    if (!$stmt->execute()) {
        $stmt->close();
        return $permissions;
    }

    $result = $stmt->get_result();
    if ($result !== false) {
        while ($row = $result->fetch_assoc()) {
            $key = (string) ($row['permission_key'] ?? '');
            if ($key !== '' && array_key_exists($key, $permissions)) {
                $permissions[$key] = ((int) ($row['is_allowed'] ?? 0)) === 1;
            }
        }
    }

    $stmt->close();
    return $permissions;
}

function isAccountActive(array $account): bool
{
    if (!array_key_exists('is_active', $account)) {
        return true;
    }
    return ((int) $account['is_active']) === 1;
}

function isHashPassword(string $value): bool
{
    $info = password_get_info($value);
    return (int) ($info['algo'] ?? 0) !== 0;
}

function matchesLegacyHash(string $stored, string $plainPassword): bool
{
    $normalized = strtolower(trim($stored));
    if ($normalized === '') {
        return false;
    }

    if (preg_match('/^[a-f0-9]{32}$/', $normalized) === 1) {
        return hash_equals($normalized, md5($plainPassword));
    }

    if (preg_match('/^[a-f0-9]{40}$/', $normalized) === 1) {
        return hash_equals($normalized, sha1($plainPassword));
    }

    return false;
}

function verifyAccountPassword(array $account, string $plainPassword): bool
{
    $stored = (string) ($account['Password'] ?? '');
    if ($stored === '') {
        return false;
    }

    if (isHashPassword($stored)) {
        return password_verify($plainPassword, $stored);
    }

    if (matchesLegacyHash($stored, $plainPassword)) {
        return true;
    }

    return hash_equals($stored, $plainPassword);
}

function maybeUpgradePasswordHash(mysqli $conn, array $account, string $plainPassword): void
{
    $stored = (string) ($account['Password'] ?? '');
    if ($stored === '' || isHashPassword($stored)) {
        return;
    }

    $accountId = (int) ($account['ID'] ?? 0);
    if ($accountId <= 0) {
        return;
    }

    $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    if ($newHash === false) {
        return;
    }

    $stmt = $conn->prepare('UPDATE accounts SET Password = ? WHERE ID = ? LIMIT 1');
    if ($stmt === false) {
        return;
    }
    $stmt->bind_param('si', $newHash, $accountId);
    $stmt->execute();
    $stmt->close();
}

function fetchAccountByIdentifier(mysqli $conn, string $identifier): ?array
{
    $stmt = $conn->prepare('SELECT ID, Email, Password, full_name, username, role_key, is_active, created_at, updated_at FROM accounts WHERE Email = ? OR username = ? LIMIT 1');
    if ($stmt === false) {
        throw new RuntimeException('Unable to prepare login query.');
    }

    $stmt->bind_param('ss', $identifier, $identifier);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Login query failed.');
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function fetchAccountById(mysqli $conn, int $accountId): ?array
{
    if ($accountId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT ID, Email, Password, full_name, username, role_key, is_active, created_at, updated_at FROM accounts WHERE ID = ? LIMIT 1');
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('i', $accountId);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function fetchAccountByEmail(mysqli $conn, string $email): ?array
{
    $emailValue = stringValue($email);
    if ($emailValue === '') {
        return null;
    }

    $stmt = $conn->prepare('SELECT ID, Email, Password, full_name, username, role_key, is_active, created_at, updated_at FROM accounts WHERE Email = ? LIMIT 1');
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('s', $emailValue);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function sessionAccount(mysqli $conn): ?array
{
    $sessionAccountId = (int) ($_SESSION['account_id'] ?? 0);
    if ($sessionAccountId > 0) {
        $account = fetchAccountById($conn, $sessionAccountId);
        if ($account !== null) {
            return $account;
        }
    }

    $sessionEmail = stringValue($_SESSION['Email'] ?? '');
    if ($sessionEmail === '') {
        return null;
    }

    return fetchAccountByEmail($conn, $sessionEmail);
}

function accountDisplayName(array $account): string
{
    $fullName = stringValue($account['full_name'] ?? '');
    if ($fullName !== '') {
        return $fullName;
    }

    $username = stringValue($account['username'] ?? '');
    if ($username !== '') {
        return $username;
    }

    $email = stringValue($account['Email'] ?? '');
    if ($email !== '') {
        return explode('@', $email)[0];
    }

    return 'Dashboard User';
}

function buildUserPayload(mysqli $conn, array $account): array
{
    $roleKey = normalizeRoleKey((string) ($account['role_key'] ?? ROLE_CARE_COORDINATOR));
    $permissions = fetchPermissionsForRole($conn, $roleKey);
    $email = stringValue($account['Email'] ?? '');
    $username = stringValue($account['username'] ?? '');

    return [
        'id' => (int) ($account['ID'] ?? 0),
        'name' => accountDisplayName($account),
        'email' => $email,
        'username' => $username !== '' ? $username : $email,
        'role' => $roleKey,
        'roleLabel' => roleLabels()[$roleKey] ?? 'Care Coordinator',
        'permissions' => $permissions,
    ];
}

function userHasPermission(array $userPayload, string $permissionKey): bool
{
    $role = normalizeRoleKey((string) ($userPayload['role'] ?? ''));
    if ($role === ROLE_DIRECTOR) {
        return true;
    }

    $permissions = $userPayload['permissions'] ?? [];
    return is_array($permissions) && !empty($permissions[$permissionKey]);
}

function roleCanAssignRole(string $actorRole, string $targetRole): bool
{
    $actor = normalizeRoleKey($actorRole);
    $target = normalizeRoleKey($targetRole);

    if ($actor === ROLE_DIRECTOR) {
        return true;
    }

    if ($actor === ROLE_MANAGER) {
        return $target === ROLE_CARE_COORDINATOR || $target === ROLE_CARER;
    }

    return false;
}

function roleCanEditRolePermissions(string $actorRole, string $targetRole): bool
{
    return roleCanAssignRole($actorRole, $targetRole);
}

function boolValue($value, bool $default = false): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return ((int) $value) === 1;
    }

    if (is_string($value)) {
        $normalized = strtolower(trim($value));
        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }
    }

    return $default;
}

function passwordValidationError(string $password): string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }

    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'Password must include at least one letter and one number.';
    }

    return '';
}

function createPasswordHash(string $password): string
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    return is_string($hash) ? $hash : '';
}

function createPasswordResetToken(mysqli $conn, array $account): ?array
{
    $accountId = (int) ($account['ID'] ?? 0);
    if ($accountId <= 0) {
        return null;
    }

    try {
        $token = bin2hex(random_bytes(24));
    } catch (Throwable $exception) {
        return null;
    }

    $tokenHash = hash('sha256', $token);
    $email = stringValue($account['Email'] ?? '');
    $expiresAt = date('Y-m-d H:i:s', time() + (PASSWORD_RESET_TTL_MINUTES * 60));
    $requestIp = stringValue($_SERVER['REMOTE_ADDR'] ?? '');
    $userAgent = stringValue($_SERVER['HTTP_USER_AGENT'] ?? '');

    $stmt = $conn->prepare('INSERT INTO password_resets (account_id, email, token_hash, expires_at, requested_ip, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    if ($stmt === false) {
        return null;
    }

    $stmt->bind_param('isssss', $accountId, $email, $tokenHash, $expiresAt, $requestIp, $userAgent);
    $success = $stmt->execute();
    $stmt->close();

    if (!$success) {
        return null;
    }

    return [
        'token' => $token,
        'expiresAt' => $expiresAt,
    ];
}

function fetchPasswordResetByToken(mysqli $conn, string $token): ?array
{
    $tokenValue = stringValue($token);
    if ($tokenValue === '') {
        return null;
    }

    $tokenHash = hash('sha256', $tokenValue);
    $stmt = $conn->prepare('SELECT pr.id, pr.account_id, pr.email, pr.expires_at, pr.used_at, a.is_active
                            FROM password_resets pr
                            INNER JOIN accounts a ON a.ID = pr.account_id
                            WHERE pr.token_hash = ?
                            LIMIT 1');
    if ($stmt === false) {
        return null;
    }

    $stmt->bind_param('s', $tokenHash);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function invalidatePasswordResetTokens(mysqli $conn, int $accountId): void
{
    if ($accountId <= 0) {
        return;
    }

    $stmt = $conn->prepare('UPDATE password_resets SET used_at = NOW() WHERE account_id = ? AND used_at IS NULL');
    if ($stmt === false) {
        return;
    }
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $stmt->close();
}

function sendPasswordResetMail(string $email, string $displayName, string $resetLink): bool
{
    if ($email === '' || $resetLink === '') {
        return false;
    }

    $safeName = $displayName !== '' ? $displayName : 'Team member';
    $subject = 'Facilitate Care Services - Password Reset';
    $message = "Hello {$safeName},\n\n"
        . "We received a password reset request for your staff account.\n"
        . "Use the link below to set a new password:\n{$resetLink}\n\n"
        . 'This link expires in ' . PASSWORD_RESET_TTL_MINUTES . " minutes.\n"
        . "If you did not request this change, please ignore this email.\n\n"
        . "Facilitate Care Services";

    $headers = "MIME-Version: 1.0\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "From: Facilitate Care Services <no-reply@facilitatecareservices.co.uk>\r\n"
        . "Reply-To: no-reply@facilitatecareservices.co.uk\r\n";

    return @mail($email, $subject, $message, $headers);
}

function safeSessionDestroy(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

$action = stringValue($_GET['action'] ?? '');
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$source = normalizeDbSource((string) ($_GET['source'] ?? ($_POST['source'] ?? 'auto')));

if ($action === 'logout' && ($method === 'POST' || $method === 'GET')) {
    safeSessionDestroy();
    jsonResponse(['success' => true, 'message' => 'Logged out.']);
}

try {
    $conn = createDatabaseConnection($source);
    ensureAuthSchema($conn);
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed.'], 500);
}

if ($action === 'session' && ($method === 'GET' || $method === 'POST')) {
    $account = sessionAccount($conn);
    if ($account === null || !isAccountActive($account)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'No active session.'], 401);
    }

    $user = buildUserPayload($conn, $account);
    $_SESSION['account_id'] = (int) ($account['ID'] ?? 0);
    $_SESSION['Email'] = (string) ($account['Email'] ?? '');
    $conn->close();
    jsonResponse(['success' => true, 'user' => $user]);
}

if ($action === 'login' && $method === 'POST') {
    $payload = readJsonPayload();
    $identifier = stringValue($payload['email'] ?? ($payload['username'] ?? ''));
    $password = (string) ($payload['password'] ?? '');

    if ($identifier === '' || $password === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Username and password are required.'], 422);
    }

    try {
        $account = fetchAccountByIdentifier($conn, $identifier);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Unable to prepare login query.'], 500);
    }

    if ($account === null || !verifyAccountPassword($account, $password)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Invalid username or password.'], 401);
    }

    if (!isAccountActive($account)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'This account has been disabled.'], 403);
    }

    maybeUpgradePasswordHash($conn, $account, $password);

    $_SESSION['account_id'] = (int) ($account['ID'] ?? 0);
    $_SESSION['Email'] = (string) ($account['Email'] ?? '');

    $user = buildUserPayload($conn, $account);
    $conn->close();
    jsonResponse(['success' => true, 'message' => 'Login successful.', 'user' => $user]);
}

if ($action === 'requestPasswordReset' && $method === 'POST') {
    $payload = readJsonPayload();
    $identifier = stringValue($payload['identifier'] ?? ($payload['email'] ?? ($payload['username'] ?? '')));

    if ($identifier === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Email or username is required.'], 422);
    }

    $account = null;
    try {
        $account = fetchAccountByIdentifier($conn, $identifier);
    } catch (Throwable $exception) {
        $account = null;
    }

    $response = [
        'success' => true,
        'message' => 'If the account exists, password reset instructions have been sent.',
    ];

    if ($account !== null && isAccountActive($account)) {
        $resetData = createPasswordResetToken($conn, $account);
        if ($resetData !== null) {
            $origin = $requestOrigin !== '' ? rtrim($requestOrigin, '/') : 'http://localhost:5173';
            $resetLink = $origin . '/login?mode=reset&token=' . urlencode((string) $resetData['token']);
            $email = stringValue($account['Email'] ?? '');
            $displayName = accountDisplayName($account);
            $mailSent = sendPasswordResetMail($email, $displayName, $resetLink);

            if ($isLocalOrigin) {
                $response['debugResetToken'] = (string) $resetData['token'];
                $response['debugResetLink'] = $resetLink;
            }

            if (!$mailSent && !$isLocalOrigin) {
                // Keep response generic. Admin can monitor mail transport separately.
                $response['message'] = 'If the account exists, password reset instructions have been queued.';
            }
        }
    }

    $conn->close();
    jsonResponse($response);
}

if ($action === 'resetPassword' && $method === 'POST') {
    $payload = readJsonPayload();
    $token = stringValue($payload['token'] ?? '');
    $newPassword = (string) ($payload['newPassword'] ?? '');

    if ($token === '' || $newPassword === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Reset token and new password are required.'], 422);
    }

    $passwordValidationMessage = passwordValidationError($newPassword);
    if ($passwordValidationMessage !== '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => $passwordValidationMessage], 422);
    }

    $resetRow = fetchPasswordResetByToken($conn, $token);
    if ($resetRow === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Reset token is invalid or expired.'], 400);
    }

    if (stringValue($resetRow['used_at'] ?? '') !== '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Reset token has already been used.'], 400);
    }

    $expiresAt = strtotime((string) ($resetRow['expires_at'] ?? ''));
    if ($expiresAt === false || $expiresAt < time()) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Reset token is invalid or expired.'], 400);
    }

    if (((int) ($resetRow['is_active'] ?? 0)) !== 1) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'This account is disabled. Contact your manager.'], 403);
    }

    $accountId = (int) ($resetRow['account_id'] ?? 0);
    if ($accountId <= 0) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Reset token is invalid or expired.'], 400);
    }

    $passwordHash = createPasswordHash($newPassword);
    if ($passwordHash === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to secure password hash.'], 500);
    }

    $stmt = $conn->prepare('UPDATE accounts SET Password = ?, updated_at = NOW() WHERE ID = ? LIMIT 1');
    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare password update query.'], 500);
    }
    $stmt->bind_param('si', $passwordHash, $accountId);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to update password.'], 500);
    }
    $stmt->close();

    invalidatePasswordResetTokens($conn, $accountId);
    $conn->close();
    jsonResponse(['success' => true, 'message' => 'Password reset successful. You can now sign in.']);
}

if ($action === 'changePassword' && $method === 'POST') {
    $session = sessionAccount($conn);
    if ($session === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Please sign in first.'], 401);
    }

    $payload = readJsonPayload();
    $currentPassword = (string) ($payload['currentPassword'] ?? '');
    $newPassword = (string) ($payload['newPassword'] ?? '');

    if ($currentPassword === '' || $newPassword === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Current password and new password are required.'], 422);
    }

    if (!verifyAccountPassword($session, $currentPassword)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Current password is incorrect.'], 401);
    }

    $passwordValidationMessage = passwordValidationError($newPassword);
    if ($passwordValidationMessage !== '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => $passwordValidationMessage], 422);
    }

    if (verifyAccountPassword($session, $newPassword)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'New password must be different from the current password.'], 422);
    }

    $passwordHash = createPasswordHash($newPassword);
    if ($passwordHash === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to secure password hash.'], 500);
    }

    $accountId = (int) ($session['ID'] ?? 0);
    $stmt = $conn->prepare('UPDATE accounts SET Password = ?, updated_at = NOW() WHERE ID = ? LIMIT 1');
    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare password update query.'], 500);
    }
    $stmt->bind_param('si', $passwordHash, $accountId);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to update password.'], 500);
    }
    $stmt->close();

    invalidatePasswordResetTokens($conn, $accountId);
    $conn->close();
    jsonResponse(['success' => true, 'message' => 'Password changed successfully.']);
}

if ($action === 'setUserPassword' && $method === 'POST') {
    $session = sessionAccount($conn);
    if ($session === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Please sign in first.'], 401);
    }

    $actor = buildUserPayload($conn, $session);
    if (!userHasPermission($actor, 'users.manage_accounts')) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Access denied for password updates.'], 403);
    }

    $payload = readJsonPayload();
    $targetUserId = (int) ($payload['userId'] ?? 0);
    $newPassword = (string) ($payload['newPassword'] ?? '');
    if ($targetUserId <= 0 || $newPassword === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'User and new password are required.'], 422);
    }

    $targetAccount = fetchAccountById($conn, $targetUserId);
    if ($targetAccount === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Target user was not found.'], 404);
    }

    $actorId = (int) ($actor['id'] ?? 0);
    $actorRole = normalizeRoleKey((string) ($actor['role'] ?? ''));
    $targetRole = normalizeRoleKey((string) ($targetAccount['role_key'] ?? ROLE_CARE_COORDINATOR));
    if ($targetUserId !== $actorId && !roleCanAssignRole($actorRole, $targetRole)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'You are not allowed to update this password.'], 403);
    }

    $passwordValidationMessage = passwordValidationError($newPassword);
    if ($passwordValidationMessage !== '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => $passwordValidationMessage], 422);
    }

    $passwordHash = createPasswordHash($newPassword);
    if ($passwordHash === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to secure password hash.'], 500);
    }

    $stmt = $conn->prepare('UPDATE accounts SET Password = ?, updated_at = NOW() WHERE ID = ? LIMIT 1');
    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare password update query.'], 500);
    }
    $stmt->bind_param('si', $passwordHash, $targetUserId);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to update password.'], 500);
    }
    $stmt->close();

    invalidatePasswordResetTokens($conn, $targetUserId);
    $conn->close();
    jsonResponse(['success' => true, 'message' => 'User password updated successfully.']);
}

if ($action === 'listUsers' && ($method === 'GET' || $method === 'POST')) {
    $session = sessionAccount($conn);
    if ($session === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Please sign in first.'], 401);
    }

    $actor = buildUserPayload($conn, $session);
    if (!userHasPermission($actor, 'users.manage_accounts')) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Access denied for user list.'], 403);
    }

    $result = $conn->query('SELECT ID, Email, full_name, username, role_key, is_active, created_at, updated_at FROM accounts ORDER BY ID DESC LIMIT 500');
    if ($result === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to load user list.'], 500);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $roleKey = normalizeRoleKey((string) ($row['role_key'] ?? ROLE_CARE_COORDINATOR));
        $rows[] = [
            'id' => (int) ($row['ID'] ?? 0),
            'fullName' => stringValue($row['full_name'] ?? ''),
            'email' => stringValue($row['Email'] ?? ''),
            'username' => stringValue($row['username'] ?? ''),
            'role' => $roleKey,
            'roleLabel' => roleLabels()[$roleKey] ?? 'Care Coordinator',
            'isActive' => ((int) ($row['is_active'] ?? 1)) === 1,
            'createdAt' => (string) ($row['created_at'] ?? ''),
            'updatedAt' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    $conn->close();
    jsonResponse(['success' => true, 'users' => $rows]);
}

if ($action === 'createUser' && $method === 'POST') {
    $session = sessionAccount($conn);
    if ($session === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Please sign in first.'], 401);
    }

    $actor = buildUserPayload($conn, $session);
    if (!userHasPermission($actor, 'users.manage_accounts')) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Access denied for user creation.'], 403);
    }

    $payload = readJsonPayload();
    $fullName = stringValue($payload['fullName'] ?? '');
    $email = strtolower(stringValue($payload['email'] ?? ''));
    $username = stringValue($payload['username'] ?? '');
    $password = (string) ($payload['password'] ?? '');
    $roleKey = normalizeRoleKey((string) ($payload['roleKey'] ?? ROLE_CARE_COORDINATOR));

    if ($fullName === '' || $email === '' || $password === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Full name, email, and password are required.'], 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Please provide a valid email address.'], 422);
    }

    $passwordValidationMessage = passwordValidationError($password);
    if ($passwordValidationMessage !== '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => $passwordValidationMessage], 422);
    }

    if ($username === '') {
        $username = explode('@', $email)[0];
    }

    if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $username)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Username must be 3-40 characters and use letters, numbers, dot, underscore, or hyphen.'], 422);
    }

    $actorRole = normalizeRoleKey((string) ($actor['role'] ?? ''));
    if (!roleCanAssignRole($actorRole, $roleKey)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'You are not allowed to create this role type.'], 403);
    }

    $existsStmt = $conn->prepare('SELECT ID FROM accounts WHERE Email = ? OR username = ? LIMIT 1');
    if ($existsStmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare account validation query.'], 500);
    }
    $existsStmt->bind_param('ss', $email, $username);
    if (!$existsStmt->execute()) {
        $existsStmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to validate existing account.'], 500);
    }
    $existsResult = $existsStmt->get_result();
    $existsRow = $existsResult ? $existsResult->fetch_assoc() : null;
    $existsStmt->close();
    if ($existsRow) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Email or username is already in use.'], 409);
    }

    $passwordHash = createPasswordHash($password);
    if ($passwordHash === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to secure password hash.'], 500);
    }

    $insertStmt = $conn->prepare('INSERT INTO accounts (Email, Password, full_name, username, role_key, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())');
    if ($insertStmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare account insert query.'], 500);
    }
    $insertStmt->bind_param('sssss', $email, $passwordHash, $fullName, $username, $roleKey);
    if (!$insertStmt->execute()) {
        $message = 'Failed to create user account.';
        if ((int) $conn->errno === 1062) {
            $message = 'Email or username is already in use.';
        }
        $insertStmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => $message], 409);
    }

    $newId = (int) $insertStmt->insert_id;
    $insertStmt->close();

    $newAccount = fetchAccountById($conn, $newId);
    if ($newAccount === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'User created but could not be loaded.'], 500);
    }

    $newRole = normalizeRoleKey((string) ($newAccount['role_key'] ?? ROLE_CARE_COORDINATOR));
    $conn->close();
    jsonResponse([
        'success' => true,
        'message' => 'User account created successfully.',
        'user' => [
            'id' => (int) ($newAccount['ID'] ?? 0),
            'fullName' => stringValue($newAccount['full_name'] ?? ''),
            'email' => stringValue($newAccount['Email'] ?? ''),
            'username' => stringValue($newAccount['username'] ?? ''),
            'role' => $newRole,
            'roleLabel' => roleLabels()[$newRole] ?? 'Care Coordinator',
        ],
    ]);
}

if ($action === 'updateUser' && $method === 'POST') {
    $session = sessionAccount($conn);
    if ($session === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Please sign in first.'], 401);
    }

    $actor = buildUserPayload($conn, $session);
    if (!userHasPermission($actor, 'users.manage_accounts')) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Access denied for account updates.'], 403);
    }

    $payload = readJsonPayload();
    $userId = (int) ($payload['userId'] ?? 0);
    if ($userId <= 0) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Invalid user identifier.'], 422);
    }

    $targetAccount = fetchAccountById($conn, $userId);
    if ($targetAccount === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Target user was not found.'], 404);
    }

    $actorId = (int) ($actor['id'] ?? 0);
    $actorRole = normalizeRoleKey((string) ($actor['role'] ?? ''));
    $existingRole = normalizeRoleKey((string) ($targetAccount['role_key'] ?? ROLE_CARE_COORDINATOR));
    $requestedRole = normalizeRoleKey((string) ($payload['roleKey'] ?? $existingRole));

    if ($userId !== $actorId && !roleCanAssignRole($actorRole, $existingRole)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'You are not allowed to edit this user.'], 403);
    }

    $canAssignRequestedRole = roleCanAssignRole($actorRole, $requestedRole);
    if ($userId !== $actorId && !$canAssignRequestedRole) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'You are not allowed to assign this role.'], 403);
    }
    if ($userId === $actorId && $requestedRole !== $existingRole && !$canAssignRequestedRole) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'You are not allowed to change your role.'], 403);
    }

    $fullName = stringValue($payload['fullName'] ?? ($targetAccount['full_name'] ?? ''));
    $email = strtolower(stringValue($payload['email'] ?? ($targetAccount['Email'] ?? '')));
    $username = stringValue($payload['username'] ?? ($targetAccount['username'] ?? ''));
    $isActive = boolValue($payload['isActive'] ?? ((int) ($targetAccount['is_active'] ?? 1) === 1), true) ? 1 : 0;

    if ($fullName === '' || $email === '' || $username === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Full name, email, and username are required.'], 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Please provide a valid email address.'], 422);
    }

    if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $username)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Username must be 3-40 characters and use letters, numbers, dot, underscore, or hyphen.'], 422);
    }

    if ($userId === $actorId && $isActive !== 1) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'You cannot deactivate your own account.'], 422);
    }

    $existsStmt = $conn->prepare('SELECT ID FROM accounts WHERE (Email = ? OR username = ?) AND ID <> ? LIMIT 1');
    if ($existsStmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to validate account uniqueness.'], 500);
    }
    $existsStmt->bind_param('ssi', $email, $username, $userId);
    if (!$existsStmt->execute()) {
        $existsStmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to validate existing accounts.'], 500);
    }
    $existsResult = $existsStmt->get_result();
    $duplicate = $existsResult ? $existsResult->fetch_assoc() : null;
    $existsStmt->close();
    if ($duplicate) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Email or username is already in use.'], 409);
    }

    $updateStmt = $conn->prepare('UPDATE accounts SET full_name = ?, Email = ?, username = ?, role_key = ?, is_active = ?, updated_at = NOW() WHERE ID = ? LIMIT 1');
    if ($updateStmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare account update query.'], 500);
    }
    $updateStmt->bind_param('ssssii', $fullName, $email, $username, $requestedRole, $isActive, $userId);
    if (!$updateStmt->execute()) {
        $updateStmt->close();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to update user account.'], 500);
    }
    $updateStmt->close();

    $updatedAccount = fetchAccountById($conn, $userId);
    if ($updatedAccount === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'User updated but could not be loaded.'], 500);
    }

    $updatedRole = normalizeRoleKey((string) ($updatedAccount['role_key'] ?? ROLE_CARE_COORDINATOR));
    $conn->close();
    jsonResponse([
        'success' => true,
        'message' => 'User account updated successfully.',
        'user' => [
            'id' => (int) ($updatedAccount['ID'] ?? 0),
            'fullName' => stringValue($updatedAccount['full_name'] ?? ''),
            'email' => stringValue($updatedAccount['Email'] ?? ''),
            'username' => stringValue($updatedAccount['username'] ?? ''),
            'role' => $updatedRole,
            'roleLabel' => roleLabels()[$updatedRole] ?? 'Care Coordinator',
            'isActive' => ((int) ($updatedAccount['is_active'] ?? 1)) === 1,
            'updatedAt' => (string) ($updatedAccount['updated_at'] ?? ''),
        ],
    ]);
}

if ($action === 'getRoleAccess' && ($method === 'GET' || $method === 'POST')) {
    $session = sessionAccount($conn);
    if ($session === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Please sign in first.'], 401);
    }

    $actor = buildUserPayload($conn, $session);
    if (!userHasPermission($actor, 'users.manage_permissions') && !userHasPermission($actor, 'users.manage_accounts')) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Access denied for role access view.'], 403);
    }

    $roles = [];
    foreach (roleLabels() as $roleKey => $label) {
        $roles[] = [
            'roleKey' => $roleKey,
            'roleLabel' => $label,
            'permissions' => fetchPermissionsForRole($conn, $roleKey),
        ];
    }

    $conn->close();
    jsonResponse([
        'success' => true,
        'roles' => $roles,
        'permissionCatalog' => permissionCatalog(),
    ]);
}

if ($action === 'getMessageRoutingSettings' && ($method === 'GET' || $method === 'POST')) {
    $session = sessionAccount($conn);
    if ($session === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Please sign in first.'], 401);
    }

    $actor = buildUserPayload($conn, $session);
    if (!userHasPermission($actor, MESSAGE_ROUTING_PERMISSION_KEY)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Access denied for inbox email routing.'], 403);
    }

    try {
        $settings = getMessageRoutingSettings($conn);
        $conn->close();
        jsonResponse([
            'success' => true,
            'settings' => $settings,
            'categories' => messageRoutingCatalog(),
        ]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to load inbox email routing settings.'], 500);
    }
}

if ($action === 'saveMessageRoutingSettings' && $method === 'POST') {
    $session = sessionAccount($conn);
    if ($session === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Please sign in first.'], 401);
    }

    $actor = buildUserPayload($conn, $session);
    if (!userHasPermission($actor, MESSAGE_ROUTING_PERMISSION_KEY)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Access denied for inbox email routing updates.'], 403);
    }

    $payload = readJsonPayload();
    $inputSettings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
    $validated = validateMessageRoutingInput($inputSettings);
    if (!empty($validated['errors'])) {
        $conn->close();
        jsonResponse([
            'success' => false,
            'message' => 'Please correct the email routing fields highlighted below.',
            'errors' => $validated['errors'],
        ], 422);
    }

    try {
        $settings = saveMessageRoutingSettings($conn, $inputSettings, (int) ($actor['id'] ?? 0));
        $conn->close();
        jsonResponse([
            'success' => true,
            'message' => 'Inbox email routing updated successfully.',
            'settings' => $settings,
        ]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to save inbox email routing settings.'], 500);
    }
}

if ($action === 'saveRoleAccess' && $method === 'POST') {
    $session = sessionAccount($conn);
    if ($session === null) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Please sign in first.'], 401);
    }

    $actor = buildUserPayload($conn, $session);
    if (!userHasPermission($actor, 'users.manage_permissions')) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Access denied for permission updates.'], 403);
    }

    $payload = readJsonPayload();
    $targetRole = normalizeRoleKey((string) ($payload['roleKey'] ?? ''));
    $permissions = normalizePermissionMap($payload['permissions'] ?? [], $targetRole);
    $actorRole = normalizeRoleKey((string) ($actor['role'] ?? ''));

    if (!roleCanEditRolePermissions($actorRole, $targetRole)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'You cannot edit this role.'], 403);
    }

    $actorId = (int) ($actor['id'] ?? 0);
    $stmt = $conn->prepare('INSERT INTO role_permissions (role_key, permission_key, is_allowed, updated_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE is_allowed = VALUES(is_allowed), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP');
    if ($stmt === false) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to prepare permission update query.'], 500);
    }

    foreach ($permissions as $permissionKey => $isAllowed) {
        $allowedInt = $isAllowed ? 1 : 0;
        $stmt->bind_param('ssii', $targetRole, $permissionKey, $allowedInt, $actorId);
        if (!$stmt->execute()) {
            $stmt->close();
            $conn->close();
            jsonResponse(['success' => false, 'message' => 'Failed to persist role permissions.'], 500);
        }
    }

    $stmt->close();

    $conn->close();
    jsonResponse([
        'success' => true,
        'message' => 'Role access updated.',
        'roleKey' => $targetRole,
        'permissions' => $permissions,
    ]);
}

$conn->close();
jsonResponse(['success' => false, 'message' => 'Invalid request.'], 400);
