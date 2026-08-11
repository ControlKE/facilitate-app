<?php

function envValue(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return (string) $value;
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }

    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return (string) $_SERVER[$key];
    }

    return $default;
}

function envInt(string $key, int $default): int
{
    $raw = envValue($key, null);
    if ($raw === null) {
        return $default;
    }

    $parsed = filter_var($raw, FILTER_VALIDATE_INT);
    if ($parsed === false) {
        return $default;
    }

    return (int) $parsed;
}

/**
 * Load credentials from env.local.php, a file kept out of version control.
 * Real environment variables always win, so servers can set them directly.
 * See env.local.example.php for the supported keys.
 */
function loadLocalEnvOverrides(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $file = __DIR__ . DIRECTORY_SEPARATOR . 'env.local.php';
    if (!is_file($file)) {
        return;
    }

    $values = require $file;
    if (!is_array($values)) {
        return;
    }

    foreach ($values as $key => $value) {
        if (!is_string($key) || $key === '' || is_array($value)) {
            continue;
        }

        $existing = getenv($key);
        if ($existing !== false && $existing !== '') {
            continue;
        }

        $_ENV[$key] = (string) $value;
    }
}

loadLocalEnvOverrides();

function isLocalRequestHost(): bool
{
    $hostHeader = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($hostHeader === '') {
        return php_sapi_name() === 'cli';
    }

    return strpos($hostHeader, 'localhost') !== false || strpos($hostHeader, '127.0.0.1') !== false;
}

function getDatabaseConfigs(): array
{
    $defaultRemoteHost = envValue('MYSQLHOST', envValue('DB_HOST', ''));
    $defaultRemoteUser = envValue('MYSQLUSER', envValue('DB_USER', ''));
    $defaultRemotePass = envValue('MYSQLPASSWORD', envValue('DB_PASS', ''));
    $defaultRemoteName = envValue('MYSQLDATABASE', envValue('DB_NAME', ''));
    $defaultRemotePort = envInt('MYSQLPORT', envInt('DB_PORT', 3306));

    return [
        'local' => [
            'host' => (string) envValue('DB_LOCAL_HOST', 'localhost'),
            'user' => (string) envValue('DB_LOCAL_USER', 'root'),
            'pass' => (string) envValue('DB_LOCAL_PASS', ''),
            'name' => (string) envValue('DB_LOCAL_NAME', 'facilitate'),
            'port' => envInt('DB_LOCAL_PORT', 3306),
        ],
        'remote' => [
            'host' => (string) envValue('DB_REMOTE_HOST', $defaultRemoteHost ?? 'localhost'),
            'user' => (string) envValue('DB_REMOTE_USER', $defaultRemoteUser ?? ''),
            'pass' => (string) envValue('DB_REMOTE_PASS', $defaultRemotePass ?? ''),
            'name' => (string) envValue('DB_REMOTE_NAME', $defaultRemoteName ?? ''),
            'port' => envInt('DB_REMOTE_PORT', $defaultRemotePort),
        ],
    ];
}

function normalizeDbSource(string $source): string
{
    $normalized = strtolower(trim($source));
    if (in_array($normalized, ['local', 'remote', 'both', 'auto'], true)) {
        return $normalized;
    }

    return 'auto';
}

function resolveReadSources(string $source = 'auto'): array
{
    $normalized = normalizeDbSource($source);

    if ($normalized === 'both') {
        return ['local', 'remote'];
    }

    if ($normalized === 'local' || $normalized === 'remote') {
        return [$normalized];
    }

    return [isLocalRequestHost() ? 'local' : 'remote'];
}

function resolveWriteSource(string $source = 'auto'): string
{
    $normalized = normalizeDbSource($source);
    if ($normalized === 'both') {
        return isLocalRequestHost() ? 'local' : 'remote';
    }

    if ($normalized === 'local' || $normalized === 'remote') {
        return $normalized;
    }

    return isLocalRequestHost() ? 'local' : 'remote';
}

function connectBySource(string $source): mysqli
{
    $configs = getDatabaseConfigs();
    if (!isset($configs[$source])) {
        throw new RuntimeException('Unknown database source.');
    }

    $cfg = $configs[$source];
    $host = stringValueFromConfig($cfg['host'] ?? '');
    $user = stringValueFromConfig($cfg['user'] ?? '');
    $pass = stringValueFromConfig($cfg['pass'] ?? '');
    $name = stringValueFromConfig($cfg['name'] ?? '');
    $port = intValueFromConfig($cfg['port'] ?? 3306, 3306);

    if ($host === '' || $name === '' || $user === '') {
        throw new RuntimeException(sprintf(
            'Connection failed for source %s: missing DB credentials. Set DB_%s_HOST, DB_%s_USER, DB_%s_PASS, DB_%s_NAME.',
            $source,
            strtoupper($source),
            strtoupper($source),
            strtoupper($source),
            strtoupper($source)
        ));
    }

    $conn = mysqli_init();
    if ($conn === false) {
        throw new RuntimeException(sprintf('Failed to initialize connection for source %s.', $source));
    }

    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    $connectionWarning = '';
    set_error_handler(static function ($severity, $message) use (&$connectionWarning) {
        $connectionWarning = (string) $message;
        return true;
    });
    try {
        $connected = $conn->real_connect($host, $user, $pass, $name, $port);
    } finally {
        restore_error_handler();
    }

    if ($connected === false) {
        $detail = $conn->connect_error ?: $connectionWarning ?: 'Unknown error';
        throw new RuntimeException(sprintf('Connection failed for source %s: %s', $source, $detail));
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}

function createDatabaseConnection(string $source = 'auto'): mysqli
{
    $writeSource = resolveWriteSource($source);
    return connectBySource($writeSource);
}

function createDatabaseConnectionsForRead(string $source = 'auto'): array
{
    $connections = [];
    $errors = [];

    foreach (resolveReadSources($source) as $name) {
        try {
            $connections[$name] = connectBySource($name);
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    if (empty($connections)) {
        $details = empty($errors) ? '' : ' ' . implode(' | ', $errors);
        throw new RuntimeException('Failed to connect to any database source.' . $details);
    }

    return $connections;
}

function stringValueFromConfig($value): string
{
    return trim((string) $value);
}

function intValueFromConfig($value, int $default = 3306): int
{
    $parsed = filter_var($value, FILTER_VALIDATE_INT);
    if ($parsed === false) {
        return $default;
    }

    return (int) $parsed;
}

?>
