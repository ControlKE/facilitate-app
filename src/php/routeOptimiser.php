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
    header_remove('Access-Control-Allow-Origin');
    header('Access-Control-Allow-Origin: http://localhost:5173');
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, X-Auth-Token, Authorization, Accept');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

require_once __DIR__ . '/db.php';

const ROLE_DIRECTOR = 'director';
const ROLE_MANAGER = 'manager';
const ROLE_CARE_COORDINATOR = 'care_coordinator';
const ROLE_CARER = 'carer';
const ROUTE_PERMISSION_KEY = 'routes.optimiser';
const DEFAULT_SHIFT_LABEL = 'Morning Run';

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

function intValue($value, int $default = 0): int
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return $default;
    }
    return (int) $value;
}

function nullableFloat($value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    if (!is_numeric($value)) {
        return null;
    }
    return (float) $value;
}

function normalizeRoleKey(string $value): string
{
    $normalized = strtolower(trim($value));
    $normalized = str_replace(['-', ' '], '_', $normalized);
    if ($normalized === 'carecoordinator') {
        $normalized = ROLE_CARE_COORDINATOR;
    }

    if (in_array($normalized, [ROLE_DIRECTOR, ROLE_MANAGER, ROLE_CARE_COORDINATOR, ROLE_CARER], true)) {
        return $normalized;
    }

    return ROLE_CARE_COORDINATOR;
}

function tableExists(mysqli $conn, string $tableName): bool
{
    $table = $conn->real_escape_string($tableName);
    $sql = "SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
            LIMIT 1";
    $result = $conn->query($sql);
    return $result !== false && $result->num_rows > 0;
}

function rolePermissionsTableExists(mysqli $conn): bool
{
    return tableExists($conn, 'role_permissions');
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
        throw new RuntimeException("Failed to add {$columnName} to {$tableName}: {$conn->error}");
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

function defaultPermissionsForRole(string $roleKey): array
{
    $all = [
        'dashboard.analytics' => false,
        'inbox.general_enquiries' => false,
        'inbox.complaints' => false,
        'inbox.care_thanks' => false,
        'cars.dashboard' => false,
        'cars.allocate' => false,
        'cars.maintenance' => false,
        'cars.directory' => false,
        'routes.optimiser' => false,
        'website.content' => false,
        'users.manage_accounts' => false,
        'users.manage_permissions' => false,
    ];

    $role = normalizeRoleKey($roleKey);
    if ($role === ROLE_DIRECTOR) {
        foreach ($all as $key => $value) {
            $all[$key] = true;
        }
        return $all;
    }

    if ($role === ROLE_MANAGER) {
        foreach ($all as $key => $value) {
            $all[$key] = true;
        }
        $all['users.manage_permissions'] = false;
        return $all;
    }

    if ($role === ROLE_CARE_COORDINATOR) {
        $all['dashboard.analytics'] = true;
        $all['inbox.general_enquiries'] = true;
        $all['inbox.complaints'] = true;
        $all['inbox.care_thanks'] = true;
        $all['cars.dashboard'] = true;
        $all['cars.allocate'] = true;
        $all['cars.maintenance'] = true;
        $all['cars.directory'] = true;
        $all['routes.optimiser'] = true;
        return $all;
    }

    $all['cars.dashboard'] = true;
    return $all;
}

function fetchPermissionsForRole(mysqli $conn, string $roleKey): array
{
    $permissions = defaultPermissionsForRole($roleKey);
    if (!rolePermissionsTableExists($conn)) {
        return $permissions;
    }

    $role = normalizeRoleKey($roleKey);
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
            $key = stringValue($row['permission_key'] ?? '');
            if ($key !== '' && array_key_exists($key, $permissions)) {
                $permissions[$key] = ((int) ($row['is_allowed'] ?? 0)) === 1;
            }
        }
    }

    $stmt->close();
    return $permissions;
}

function fetchAccountById(mysqli $conn, int $accountId): ?array
{
    if ($accountId <= 0 || !tableExists($conn, 'accounts')) {
        return null;
    }

    $stmt = $conn->prepare('SELECT ID, Email, full_name, username, role_key, is_active FROM accounts WHERE ID = ? LIMIT 1');
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
    if ($emailValue === '' || !tableExists($conn, 'accounts')) {
        return null;
    }

    $stmt = $conn->prepare('SELECT ID, Email, full_name, username, role_key, is_active FROM accounts WHERE Email = ? LIMIT 1');
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

function isAccountActive(array $account): bool
{
    if (!array_key_exists('is_active', $account)) {
        return true;
    }

    return ((int) $account['is_active']) === 1;
}

function buildUserPayload(mysqli $conn, array $account): array
{
    $roleKey = normalizeRoleKey((string) ($account['role_key'] ?? ROLE_CARE_COORDINATOR));
    $permissions = fetchPermissionsForRole($conn, $roleKey);

    return [
        'id' => (int) ($account['ID'] ?? 0),
        'name' => accountDisplayName($account),
        'email' => stringValue($account['Email'] ?? ''),
        'username' => stringValue($account['username'] ?? ''),
        'role' => $roleKey,
        'permissions' => $permissions,
    ];
}

function userHasPermission(array $user, string $permissionKey): bool
{
    if ($permissionKey === '') {
        return true;
    }

    if (normalizeRoleKey((string) ($user['role'] ?? '')) === ROLE_DIRECTOR) {
        return true;
    }

    $permissions = $user['permissions'] ?? [];
    return is_array($permissions) && !empty($permissions[$permissionKey]);
}

function requireRoutePermission(mysqli $conn): array
{
    $session = sessionAccount($conn);
    if ($session === null) {
        jsonResponse(['success' => false, 'message' => 'Please sign in first.'], 401);
    }

    if (!isAccountActive($session)) {
        jsonResponse(['success' => false, 'message' => 'This account is disabled.'], 403);
    }

    $actor = buildUserPayload($conn, $session);
    if (!userHasPermission($actor, ROUTE_PERMISSION_KEY)) {
        jsonResponse(['success' => false, 'message' => 'Access denied for route optimiser.'], 403);
    }

    return $actor;
}

function normalizePostcode($value): string
{
    $clean = strtoupper(preg_replace('/\s+/', '', stringValue($value)));
    if ($clean === '') {
        return '';
    }

    if (strlen($clean) > 3) {
        return substr($clean, 0, -3) . ' ' . substr($clean, -3);
    }

    return $clean;
}

function normalizeTextToken($value): string
{
    $normalized = strtolower(stringValue($value));
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    return trim((string) $normalized);
}

function outwardPostcode(string $postcode): string
{
    $normalized = normalizePostcode($postcode);
    if ($normalized === '') {
        return '';
    }

    $parts = explode(' ', $normalized, 2);
    if (!empty($parts[0])) {
        return $parts[0];
    }

    return strlen($normalized) > 3 ? substr($normalized, 0, -3) : $normalized;
}

function postcodeSector(string $postcode): string
{
    $normalized = normalizePostcode($postcode);
    if ($normalized === '') {
        return '';
    }

    $parts = explode(' ', $normalized, 2);
    $outward = outwardPostcode($normalized);
    $inward = $parts[1] ?? '';
    $sectorDigit = $inward !== '' ? substr($inward, 0, 1) : '';
    return trim($outward . ' ' . $sectorDigit);
}

function postcodeArea(string $postcode): string
{
    $outward = outwardPostcode($postcode);
    if ($outward === '') {
        return '';
    }

    if (preg_match('/^[A-Z]+/', $outward, $matches) === 1) {
        return $matches[0];
    }

    return $outward;
}

function safeLatitude($value): ?float
{
    $parsed = nullableFloat($value);
    if ($parsed === null || $parsed < -90 || $parsed > 90) {
        return null;
    }
    return $parsed;
}

function safeLongitude($value): ?float
{
    $parsed = nullableFloat($value);
    if ($parsed === null || $parsed < -180 || $parsed > 180) {
        return null;
    }
    return $parsed;
}

function nullableDecimalParam(?float $value): string
{
    if ($value === null) {
        return '';
    }

    return number_format($value, 7, '.', '');
}

function clientAddressLines(array $client): array
{
    return array_values(array_filter([
        stringValue($client['address_line_1'] ?? $client['addressLine1'] ?? ''),
        stringValue($client['address_line_2'] ?? $client['addressLine2'] ?? ''),
        stringValue($client['town_city'] ?? $client['townCity'] ?? ''),
        stringValue($client['county'] ?? $client['county'] ?? ''),
        normalizePostcode($client['postcode'] ?? ''),
    ], static function ($item) {
        return $item !== '';
    }));
}

function formattedAddress(array $client): string
{
    return implode(', ', clientAddressLines($client));
}

function legacyGoogleMapsApiKey(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = '';
    $indexPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_file($indexPath) || !is_readable($indexPath)) {
        return $cached;
    }

    $contents = file_get_contents($indexPath);
    if (!is_string($contents) || $contents === '') {
        return $cached;
    }

    if (preg_match('/maps\.googleapis\.com\/maps\/api\/js\?key=([A-Za-z0-9_\-]+)/i', $contents, $matches) === 1) {
        $cached = stringValue($matches[1] ?? '');
    }

    return $cached;
}

function googleMapsApiKey(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = stringValue(
        envValue(
            'GOOGLE_MAPS_API_KEY',
            envValue(
                'GOOGLE_GEOCODING_API_KEY',
                envValue('GOOGLE_MAPS_SERVER_KEY', legacyGoogleMapsApiKey())
            )
        )
    );

    return $cached;
}

function googleMapsBrowserApiKey(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = stringValue(
        envValue(
            'GOOGLE_MAPS_BROWSER_KEY',
            legacyGoogleMapsApiKey()
        )
    );

    return $cached;
}

function googleGeocodingConfigured(): bool
{
    return googleMapsApiKey() !== '';
}

function googleMapsBrowserConfigured(): bool
{
    return googleMapsBrowserApiKey() !== '';
}

function curlCaBundlePath(): string
{
    $path = stringValue(
        envValue(
            'CURL_CA_BUNDLE',
            envValue('SSL_CERT_FILE', '')
        )
    );

    if ($path === '' || !is_file($path) || !is_readable($path)) {
        return '';
    }

    return $path;
}

function fetchJsonFromUrl(string $url): array
{
    $responseBody = '';
    $httpStatus = 0;

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Failed to initialize the address lookup request.');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: FacilitateRouteOptimiser/1.0',
            ],
        ]);

        $caBundle = curlCaBundlePath();
        if ($caBundle !== '') {
            curl_setopt($curl, CURLOPT_CAINFO, $caBundle);
        }

        $responseBody = curl_exec($curl);
        if ($responseBody === false) {
            $error = stringValue(curl_error($curl));

            if (isLocalRequestHost() && stripos($error, 'SSL certificate problem') !== false) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                $responseBody = curl_exec($curl);
                if ($responseBody !== false) {
                    $httpStatus = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
                    curl_close($curl);
                    $decoded = json_decode((string) $responseBody, true);
                    if (!is_array($decoded)) {
                        throw new RuntimeException('Google returned an unreadable address lookup response.');
                    }
                    return $decoded;
                }

                $error = stringValue(curl_error($curl));
            }

            curl_close($curl);
            throw new RuntimeException($error !== '' ? $error : 'The address lookup request did not complete.');
        }

        $httpStatus = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: FacilitateRouteOptimiser/1.0\r\n",
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);
        if ($responseBody === false) {
            throw new RuntimeException('The address lookup request could not reach Google.');
        }

        if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', (string) $http_response_header[0], $matches) === 1) {
            $httpStatus = (int) $matches[1];
        }
    }

    if ($httpStatus >= 400) {
        throw new RuntimeException('Google address lookup failed with HTTP ' . $httpStatus . '.');
    }

    $decoded = json_decode((string) $responseBody, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Google returned an unreadable address lookup response.');
    }

    return $decoded;
}

function geocodeAddressWithGoogle(array $client): array
{
    $apiKey = googleMapsApiKey();
    if ($apiKey === '') {
        throw new RuntimeException('Google address lookup is not configured. Set GOOGLE_MAPS_API_KEY to enable it.');
    }

    $addressQuery = formattedAddress($client);
    if ($addressQuery === '') {
        throw new RuntimeException('Enter the address before searching the map.');
    }

    $query = http_build_query([
        'address' => $addressQuery,
        'components' => 'country:GB',
        'region' => 'gb',
        'key' => $apiKey,
    ], '', '&', PHP_QUERY_RFC3986);

    $response = fetchJsonFromUrl('https://maps.googleapis.com/maps/api/geocode/json?' . $query);
    $status = stringValue($response['status'] ?? '');

    if ($status !== 'OK') {
        if ($status === 'ZERO_RESULTS') {
            throw new RuntimeException('No map match was found for that address. Check the postcode or refine the address and try again.');
        }
        if ($status === 'REQUEST_DENIED') {
            throw new RuntimeException('Google denied the address lookup. Check the API key, enabled APIs, and Google restrictions.');
        }
        if ($status === 'OVER_QUERY_LIMIT') {
            throw new RuntimeException('Google address lookup quota has been reached. Please try again later.');
        }
        if ($status === 'INVALID_REQUEST') {
            throw new RuntimeException('Google could not process that address lookup request.');
        }
        if ($status === 'UNKNOWN_ERROR') {
            throw new RuntimeException('Google had a temporary address lookup issue. Please try again.');
        }

        $errorMessage = stringValue($response['error_message'] ?? '');
        throw new RuntimeException($errorMessage !== '' ? $errorMessage : 'Google did not return a successful address lookup result.');
    }

    $results = $response['results'] ?? [];
    if (!is_array($results) || empty($results[0]) || !is_array($results[0])) {
        throw new RuntimeException('Google did not return a usable address result.');
    }

    $result = $results[0];
    $location = $result['geometry']['location'] ?? ($result['location'] ?? []);
    $latitude = safeLatitude($location['lat'] ?? ($location['latitude'] ?? null));
    $longitude = safeLongitude($location['lng'] ?? ($location['longitude'] ?? null));

    if ($latitude === null || $longitude === null) {
        throw new RuntimeException('Google returned the address without usable coordinates.');
    }

    return [
        'addressQuery' => $addressQuery,
        'formattedAddress' => stringValue($result['formatted_address'] ?? ''),
        'placeId' => stringValue($result['place_id'] ?? ($result['placeId'] ?? '')),
        'locationType' => stringValue($result['geometry']['location_type'] ?? ($result['granularity'] ?? '')),
        'partialMatch' => boolValue($result['partial_match'] ?? false, false),
        'latitude' => $latitude,
        'longitude' => $longitude,
    ];
}

function mapClientRow(array $row): array
{
    $postcode = normalizePostcode($row['postcode'] ?? '');
    $client = [
        'id' => (int) ($row['id'] ?? 0),
        'fullName' => stringValue($row['full_name'] ?? ''),
        'addressLine1' => stringValue($row['address_line_1'] ?? ''),
        'addressLine2' => stringValue($row['address_line_2'] ?? ''),
        'townCity' => stringValue($row['town_city'] ?? ''),
        'county' => stringValue($row['county'] ?? ''),
        'postcode' => $postcode,
        'notes' => stringValue($row['notes'] ?? ''),
        'preferredCallType' => stringValue($row['preferred_call_type'] ?? ''),
        'areaZone' => stringValue($row['area_zone'] ?? ''),
        'latitude' => safeLatitude($row['latitude'] ?? null),
        'longitude' => safeLongitude($row['longitude'] ?? null),
        'isActive' => ((int) ($row['is_active'] ?? 1)) === 1,
        'createdAt' => (string) ($row['created_at'] ?? ''),
        'updatedAt' => (string) ($row['updated_at'] ?? ''),
    ];
    $client['fullAddress'] = formattedAddress($client);
    return $client;
}

function mapRunSummaryRow(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'runName' => stringValue($row['run_name'] ?? ''),
        'runDate' => (string) ($row['run_date'] ?? ''),
        'shiftLabel' => stringValue($row['shift_label'] ?? ''),
        'assignedCarerAccountId' => $row['assigned_carer_account_id'] !== null ? (int) $row['assigned_carer_account_id'] : null,
        'assignedCarerName' => stringValue($row['assigned_carer_name'] ?? ''),
        'notes' => stringValue($row['notes'] ?? ''),
        'firstCallClientId' => $row['first_call_client_id'] !== null ? (int) $row['first_call_client_id'] : null,
        'firstCallName' => stringValue($row['first_call_name'] ?? ''),
        'optimisationMethod' => stringValue($row['optimisation_method'] ?? ''),
        'generatedAt' => (string) ($row['generated_at'] ?? ''),
        'manualOverride' => ((int) ($row['manual_override'] ?? 0)) === 1,
        'stopCount' => (int) ($row['stop_count'] ?? 0),
        'createdAt' => (string) ($row['created_at'] ?? ''),
        'updatedAt' => (string) ($row['updated_at'] ?? ''),
    ];
}

function fetchClients(mysqli $conn): array
{
    $result = $conn->query('SELECT * FROM route_clients ORDER BY is_active DESC, full_name ASC, id ASC');
    if ($result === false) {
        throw new RuntimeException('Failed to load clients.');
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = mapClientRow($row);
    }

    return $rows;
}

function fetchClientsByIds(mysqli $conn, array $clientIds): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $clientIds), static function ($value) {
        return $value > 0;
    })));
    if (empty($ids)) {
        return [];
    }

    $sql = 'SELECT * FROM route_clients WHERE id IN (' . implode(',', $ids) . ')';
    $result = $conn->query($sql);
    if ($result === false) {
        throw new RuntimeException('Failed to load selected clients.');
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = mapClientRow($row);
    }

    return $rows;
}

function fetchClientById(mysqli $conn, int $clientId): ?array
{
    if ($clientId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT * FROM route_clients WHERE id = ? LIMIT 1');
    if ($stmt === false) {
        return null;
    }

    $stmt->bind_param('i', $clientId);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ? mapClientRow($row) : null;
}

function fetchRunSummaries(mysqli $conn): array
{
    $sql = <<<SQL
SELECT
    r.id,
    r.run_name,
    r.run_date,
    r.shift_label,
    r.assigned_carer_account_id,
    r.assigned_carer_name,
    r.notes,
    r.first_call_client_id,
    r.optimisation_method,
    r.generated_at,
    r.manual_override,
    r.created_at,
    r.updated_at,
    fc.full_name AS first_call_name,
    COUNT(s.id) AS stop_count
FROM route_runs r
LEFT JOIN route_clients fc ON fc.id = r.first_call_client_id
LEFT JOIN route_run_stops s ON s.run_id = r.id
GROUP BY
    r.id,
    r.run_name,
    r.run_date,
    r.shift_label,
    r.assigned_carer_account_id,
    r.assigned_carer_name,
    r.notes,
    r.first_call_client_id,
    r.optimisation_method,
    r.generated_at,
    r.manual_override,
    r.created_at,
    r.updated_at,
    fc.full_name
ORDER BY r.run_date DESC, r.updated_at DESC, r.id DESC
LIMIT 250
SQL;

    $result = $conn->query($sql);
    if ($result === false) {
        throw new RuntimeException('Failed to load saved runs.');
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = mapRunSummaryRow($row);
    }

    return $rows;
}

function fetchRunDetail(mysqli $conn, int $runId): ?array
{
    if ($runId <= 0) {
        return null;
    }

    $summarySql = <<<SQL
SELECT
    r.id,
    r.run_name,
    r.run_date,
    r.shift_label,
    r.assigned_carer_account_id,
    r.assigned_carer_name,
    r.notes,
    r.first_call_client_id,
    r.optimisation_method,
    r.generated_at,
    r.manual_override,
    r.created_at,
    r.updated_at,
    fc.full_name AS first_call_name,
    COUNT(s.id) AS stop_count
FROM route_runs r
LEFT JOIN route_clients fc ON fc.id = r.first_call_client_id
LEFT JOIN route_run_stops s ON s.run_id = r.id
WHERE r.id = ?
GROUP BY
    r.id,
    r.run_name,
    r.run_date,
    r.shift_label,
    r.assigned_carer_account_id,
    r.assigned_carer_name,
    r.notes,
    r.first_call_client_id,
    r.optimisation_method,
    r.generated_at,
    r.manual_override,
    r.created_at,
    r.updated_at,
    fc.full_name
LIMIT 1
SQL;

    $summaryStmt = $conn->prepare($summarySql);
    if ($summaryStmt === false) {
        return null;
    }

    $summaryStmt->bind_param('i', $runId);
    if (!$summaryStmt->execute()) {
        $summaryStmt->close();
        return null;
    }

    $summaryResult = $summaryStmt->get_result();
    $runRow = $summaryResult ? $summaryResult->fetch_assoc() : null;
    $summaryStmt->close();
    if (!$runRow) {
        return null;
    }

    $stopsStmt = $conn->prepare('SELECT s.*, c.full_name, c.address_line_1, c.address_line_2, c.town_city, c.county, c.postcode, c.notes, c.preferred_call_type, c.area_zone, c.latitude, c.longitude, c.is_active, c.created_at AS client_created_at, c.updated_at AS client_updated_at FROM route_run_stops s INNER JOIN route_clients c ON c.id = s.client_id WHERE s.run_id = ? ORDER BY s.route_order ASC, s.id ASC');
    if ($stopsStmt === false) {
        return null;
    }

    $stopsStmt->bind_param('i', $runId);
    if (!$stopsStmt->execute()) {
        $stopsStmt->close();
        return null;
    }

    $stopsResult = $stopsStmt->get_result();
    $stops = [];
    if ($stopsResult !== false) {
        while ($row = $stopsResult->fetch_assoc()) {
            $client = mapClientRow([
                'id' => $row['client_id'],
                'full_name' => $row['full_name'],
                'address_line_1' => $row['address_line_1'],
                'address_line_2' => $row['address_line_2'],
                'town_city' => $row['town_city'],
                'county' => $row['county'],
                'postcode' => $row['postcode'],
                'notes' => $row['notes'],
                'preferred_call_type' => $row['preferred_call_type'],
                'area_zone' => $row['area_zone'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'is_active' => $row['is_active'],
                'created_at' => $row['client_created_at'],
                'updated_at' => $row['client_updated_at'],
            ]);

            $stops[] = array_merge($client, [
                'stopId' => (int) ($row['id'] ?? 0),
                'routeOrder' => (int) ($row['route_order'] ?? 0),
                'isFirstCall' => ((int) ($row['is_first_call'] ?? 0)) === 1,
                'manualOverride' => ((int) ($row['manual_override'] ?? 0)) === 1,
                'segmentMethod' => stringValue($row['segment_method'] ?? ''),
                'segmentLabel' => stringValue($row['segment_label'] ?? ''),
                'segmentDistanceKm' => $row['segment_distance_km'] !== null ? round((float) $row['segment_distance_km'], 2) : null,
                'segmentScore' => $row['segment_score'] !== null ? round((float) $row['segment_score'], 2) : null,
            ]);
        }
    }

    $stopsStmt->close();

    return [
        'run' => mapRunSummaryRow($runRow),
        'stops' => $stops,
    ];
}

function fetchCarerOptions(mysqli $conn): array
{
    if (!tableExists($conn, 'accounts')) {
        return [];
    }

    $columns = ['ID', 'Email', 'full_name', 'username', 'role_key', 'is_active'];
    foreach ($columns as $column) {
        if (!columnExists($conn, 'accounts', $column)) {
            return [];
        }
    }

    $result = $conn->query("SELECT ID, Email, full_name, username FROM accounts WHERE role_key = 'carer' AND is_active = 1 ORDER BY full_name ASC, username ASC, Email ASC");
    if ($result === false) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $label = stringValue($row['full_name'] ?? '');
        if ($label === '') {
            $label = stringValue($row['username'] ?? '');
        }
        if ($label === '') {
            $label = stringValue($row['Email'] ?? '');
        }

        $rows[] = [
            'id' => (int) ($row['ID'] ?? 0),
            'label' => $label,
            'email' => stringValue($row['Email'] ?? ''),
        ];
    }

    return $rows;
}

function ensureRouteOptimiserTables(mysqli $conn): void
{
    $clientsSql = <<<SQL
CREATE TABLE IF NOT EXISTS route_clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(160) NOT NULL,
    address_line_1 VARCHAR(190) NOT NULL,
    address_line_2 VARCHAR(190) NOT NULL DEFAULT '',
    town_city VARCHAR(120) NOT NULL,
    county VARCHAR(120) NOT NULL DEFAULT '',
    postcode VARCHAR(24) NOT NULL,
    notes TEXT NULL,
    preferred_call_type VARCHAR(80) NOT NULL DEFAULT '',
    area_zone VARCHAR(80) NOT NULL DEFAULT '',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    $runsSql = <<<SQL
CREATE TABLE IF NOT EXISTS route_runs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    run_name VARCHAR(160) NOT NULL,
    run_date DATE NOT NULL,
    shift_label VARCHAR(80) NOT NULL DEFAULT '',
    assigned_carer_account_id INT UNSIGNED NULL,
    assigned_carer_name VARCHAR(160) NOT NULL DEFAULT '',
    notes TEXT NULL,
    first_call_client_id INT UNSIGNED NULL,
    optimisation_method VARCHAR(40) NOT NULL DEFAULT 'postcode_heuristic',
    generated_at DATETIME NULL,
    manual_override TINYINT(1) NOT NULL DEFAULT 0,
    created_by_account_id INT UNSIGNED NULL,
    updated_by_account_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_route_runs_first_call FOREIGN KEY (first_call_client_id) REFERENCES route_clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    $stopsSql = <<<SQL
CREATE TABLE IF NOT EXISTS route_run_stops (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    run_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    route_order INT UNSIGNED NOT NULL,
    is_first_call TINYINT(1) NOT NULL DEFAULT 0,
    manual_override TINYINT(1) NOT NULL DEFAULT 0,
    segment_method VARCHAR(40) NOT NULL DEFAULT '',
    segment_label VARCHAR(190) NOT NULL DEFAULT '',
    segment_distance_km DECIMAL(10,2) NULL,
    segment_score DECIMAL(10,2) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY route_run_stops_run_order_unique (run_id, route_order),
    UNIQUE KEY route_run_stops_run_client_unique (run_id, client_id),
    CONSTRAINT fk_route_run_stops_run FOREIGN KEY (run_id) REFERENCES route_runs(id) ON DELETE CASCADE,
    CONSTRAINT fk_route_run_stops_client FOREIGN KEY (client_id) REFERENCES route_clients(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($clientsSql) || !$conn->query($runsSql) || !$conn->query($stopsSql)) {
        throw new RuntimeException('Failed to initialize route optimiser tables.');
    }

    addColumnIfMissing($conn, 'route_clients', 'preferred_call_type', "`preferred_call_type` VARCHAR(80) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'route_clients', 'area_zone', "`area_zone` VARCHAR(80) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'route_clients', 'latitude', "`latitude` DECIMAL(10,7) NULL");
    addColumnIfMissing($conn, 'route_clients', 'longitude', "`longitude` DECIMAL(10,7) NULL");
    addColumnIfMissing($conn, 'route_runs', 'assigned_carer_account_id', "`assigned_carer_account_id` INT UNSIGNED NULL");
    addColumnIfMissing($conn, 'route_runs', 'assigned_carer_name', "`assigned_carer_name` VARCHAR(160) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'route_runs', 'optimisation_method', "`optimisation_method` VARCHAR(40) NOT NULL DEFAULT 'postcode_heuristic'");
    addColumnIfMissing($conn, 'route_runs', 'generated_at', "`generated_at` DATETIME NULL");
    addColumnIfMissing($conn, 'route_runs', 'manual_override', "`manual_override` TINYINT(1) NOT NULL DEFAULT 0");
    addColumnIfMissing($conn, 'route_runs', 'created_by_account_id', "`created_by_account_id` INT UNSIGNED NULL");
    addColumnIfMissing($conn, 'route_runs', 'updated_by_account_id', "`updated_by_account_id` INT UNSIGNED NULL");
    addColumnIfMissing($conn, 'route_run_stops', 'segment_method', "`segment_method` VARCHAR(40) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'route_run_stops', 'segment_label', "`segment_label` VARCHAR(190) NOT NULL DEFAULT ''");
    addColumnIfMissing($conn, 'route_run_stops', 'segment_distance_km', "`segment_distance_km` DECIMAL(10,2) NULL");
    addColumnIfMissing($conn, 'route_run_stops', 'segment_score', "`segment_score` DECIMAL(10,2) NULL");

    addIndexIfMissing($conn, 'route_clients', 'route_clients_name_idx', 'ALTER TABLE `route_clients` ADD KEY `route_clients_name_idx` (`full_name`)');
    addIndexIfMissing($conn, 'route_clients', 'route_clients_postcode_idx', 'ALTER TABLE `route_clients` ADD KEY `route_clients_postcode_idx` (`postcode`)');
    addIndexIfMissing($conn, 'route_clients', 'route_clients_active_idx', 'ALTER TABLE `route_clients` ADD KEY `route_clients_active_idx` (`is_active`)');
    addIndexIfMissing($conn, 'route_clients', 'route_clients_zone_idx', 'ALTER TABLE `route_clients` ADD KEY `route_clients_zone_idx` (`area_zone`)');
    addIndexIfMissing($conn, 'route_runs', 'route_runs_date_idx', 'ALTER TABLE `route_runs` ADD KEY `route_runs_date_idx` (`run_date`)');
    addIndexIfMissing($conn, 'route_runs', 'route_runs_shift_idx', 'ALTER TABLE `route_runs` ADD KEY `route_runs_shift_idx` (`shift_label`)');
    addIndexIfMissing($conn, 'route_runs', 'route_runs_first_call_idx', 'ALTER TABLE `route_runs` ADD KEY `route_runs_first_call_idx` (`first_call_client_id`)');
    addIndexIfMissing($conn, 'route_runs', 'route_runs_carer_idx', 'ALTER TABLE `route_runs` ADD KEY `route_runs_carer_idx` (`assigned_carer_account_id`)');
    addIndexIfMissing($conn, 'route_run_stops', 'route_run_stops_client_idx', 'ALTER TABLE `route_run_stops` ADD KEY `route_run_stops_client_idx` (`client_id`)');
}

function ensureDemoClients(mysqli $conn): void
{
    $result = $conn->query('SELECT COUNT(*) AS total FROM route_clients');
    if ($result === false) {
        return;
    }

    $row = $result->fetch_assoc();
    $count = (int) ($row['total'] ?? 0);
    if ($count > 0) {
        return;
    }

    $seedRows = [
        ['Joan McCulloch', '14 Smith Street', '', 'Warwick', 'Warwickshire', 'CV34 4JA', 'Demo seed client for morning medication support.', 'Medication', 'Warwick Central', 52.2818600, -1.5843500],
        ['Eric Savage', '22 Market Place', '', 'Warwick', 'Warwickshire', 'CV34 4SA', 'Demo seed client for lunchtime welfare check.', 'Welfare Check', 'Warwick Central', 52.2834300, -1.5871200],
        ['Margaret Cater', '8 Jury Street', '', 'Warwick', 'Warwickshire', 'CV34 4EH', 'Demo seed client for personal care visit.', 'Personal Care', 'Warwick East', 52.2823800, -1.5862400],
        ['Peter / Barbara Champion', '3 The Square', '', 'Leek Wootton', 'Warwickshire', 'CV35 7LN', 'Demo seed couple for afternoon companionship visit.', 'Companionship', 'Leek Wootton', 52.3187200, -1.6008700],
    ];

    $stmt = $conn->prepare('INSERT INTO route_clients (full_name, address_line_1, address_line_2, town_city, county, postcode, notes, preferred_call_type, area_zone, latitude, longitude, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
    if ($stmt === false) {
        return;
    }

    foreach ($seedRows as $rowData) {
        $fullName = $rowData[0];
        $addressLine1 = $rowData[1];
        $addressLine2 = $rowData[2];
        $townCity = $rowData[3];
        $county = $rowData[4];
        $postcode = normalizePostcode($rowData[5]);
        $notes = $rowData[6];
        $preferredCallType = $rowData[7];
        $areaZone = $rowData[8];
        $latitude = $rowData[9];
        $longitude = $rowData[10];
        $stmt->bind_param('sssssssssdd', $fullName, $addressLine1, $addressLine2, $townCity, $county, $postcode, $notes, $preferredCallType, $areaZone, $latitude, $longitude);
        $stmt->execute();
    }

    $stmt->close();
}

function addressSimilarity(array $from, array $to): float
{
    $fromText = normalizeTextToken(formattedAddress($from));
    $toText = normalizeTextToken(formattedAddress($to));
    if ($fromText === '' || $toText === '') {
        return 0.0;
    }

    $percent = 0.0;
    similar_text($fromText, $toText, $percent);
    return (float) $percent;
}

function haversineDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadius = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2)
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

function estimateHeuristicSegment(array $from, array $to): array
{
    $fromPostcode = normalizePostcode($from['postcode'] ?? '');
    $toPostcode = normalizePostcode($to['postcode'] ?? '');

    $samePostcode = $fromPostcode !== '' && $fromPostcode === $toPostcode;
    $sameSector = !$samePostcode && postcodeSector($fromPostcode) !== '' && postcodeSector($fromPostcode) === postcodeSector($toPostcode);
    $sameOutward = !$samePostcode && !$sameSector && outwardPostcode($fromPostcode) !== '' && outwardPostcode($fromPostcode) === outwardPostcode($toPostcode);
    $sameArea = !$samePostcode && !$sameSector && !$sameOutward && postcodeArea($fromPostcode) !== '' && postcodeArea($fromPostcode) === postcodeArea($toPostcode);
    $sameTown = normalizeTextToken($from['townCity'] ?? '') !== '' && normalizeTextToken($from['townCity'] ?? '') === normalizeTextToken($to['townCity'] ?? '');
    $sameCounty = normalizeTextToken($from['county'] ?? '') !== '' && normalizeTextToken($from['county'] ?? '') === normalizeTextToken($to['county'] ?? '');
    $sameZone = normalizeTextToken($from['areaZone'] ?? '') !== '' && normalizeTextToken($from['areaZone'] ?? '') === normalizeTextToken($to['areaZone'] ?? '');

    if ($samePostcode) {
        $score = 0.35;
        $primaryHint = 'Same postcode';
    } elseif ($sameSector) {
        $score = 1.15;
        $primaryHint = 'Same postcode sector';
    } elseif ($sameOutward) {
        $score = 3.25;
        $primaryHint = 'Same outward postcode';
    } elseif ($sameArea) {
        $score = 7.80;
        $primaryHint = 'Same postcode area';
    } else {
        $score = 16.00;
        $primaryHint = 'Postcode heuristic';
    }

    $secondaryHints = [];
    if ($sameZone) {
        $score -= 1.50;
        $secondaryHints[] = 'same zone';
    }
    if ($sameTown) {
        $score -= 2.00;
        $secondaryHints[] = 'same town';
    }
    if ($sameCounty) {
        $score -= 0.80;
    }

    $similarity = addressSimilarity($from, $to);
    $score += (100 - $similarity) / 35.0;

    if (!$sameArea && !$sameTown && !$sameCounty) {
        $score += 6.00;
    }

    $score = max(0.40, round($score, 2));

    $label = $primaryHint;
    if (!empty($secondaryHints)) {
        $label .= ' (' . implode(', ', $secondaryHints) . ')';
    }

    return [
        'method' => 'postcode_heuristic',
        'score' => $score,
        'distanceKm' => null,
        'label' => $label,
    ];
}

function estimateSegment(array $from, array $to): array
{
    $fromLat = safeLatitude($from['latitude'] ?? null);
    $fromLng = safeLongitude($from['longitude'] ?? null);
    $toLat = safeLatitude($to['latitude'] ?? null);
    $toLng = safeLongitude($to['longitude'] ?? null);

    if ($fromLat !== null && $fromLng !== null && $toLat !== null && $toLng !== null) {
        $distanceKm = round(haversineDistanceKm($fromLat, $fromLng, $toLat, $toLng), 2);
        return [
            'method' => 'coordinates',
            'score' => $distanceKm,
            'distanceKm' => $distanceKm,
            'label' => $distanceKm < 1 ? 'Approx under 1 km' : 'Approx ' . number_format($distanceKm, 1) . ' km',
        ];
    }

    return estimateHeuristicSegment($from, $to);
}

function compareSegmentChoice(array $candidateSegment, array $candidateClient, array $bestSegment, array $bestClient): bool
{
    $candidateScore = (float) ($candidateSegment['score'] ?? 999999);
    $bestScore = (float) ($bestSegment['score'] ?? 999999);

    if ($candidateScore < $bestScore - 0.0001) {
        return true;
    }

    if ($candidateScore > $bestScore + 0.0001) {
        return false;
    }

    return strcasecmp(stringValue($candidateClient['fullName'] ?? ''), stringValue($bestClient['fullName'] ?? '')) < 0;
}

function generateOrderedStops(array $clients, int $firstCallClientId): array
{
    $clientMap = [];
    foreach ($clients as $client) {
        $clientMap[(int) ($client['id'] ?? 0)] = $client;
    }

    if (!isset($clientMap[$firstCallClientId])) {
        throw new RuntimeException('Selected first call was not found in the current run.');
    }

    $firstClient = $clientMap[$firstCallClientId];
    $remaining = [];
    foreach ($clients as $client) {
        if ((int) ($client['id'] ?? 0) !== $firstCallClientId) {
            $remaining[] = $client;
        }
    }

    $ordered = [[
        'id' => (int) $firstClient['id'],
        'clientId' => (int) $firstClient['id'],
        'routeOrder' => 1,
        'isFirstCall' => true,
        'manualOverride' => false,
        'segmentMethod' => 'fixed_first_call',
        'segmentLabel' => 'Fixed first call',
        'segmentDistanceKm' => null,
        'segmentScore' => null,
    ] + $firstClient];

    $current = $firstClient;
    $methodsUsed = [];

    while (!empty($remaining)) {
        $bestIndex = null;
        $bestSegment = null;
        $bestClient = null;

        foreach ($remaining as $index => $candidate) {
            $segment = estimateSegment($current, $candidate);
            if ($bestSegment === null || compareSegmentChoice($segment, $candidate, $bestSegment, $bestClient)) {
                $bestIndex = $index;
                $bestSegment = $segment;
                $bestClient = $candidate;
            }
        }

        if ($bestIndex === null || $bestClient === null || $bestSegment === null) {
            break;
        }

        unset($remaining[$bestIndex]);
        $remaining = array_values($remaining);
        $methodsUsed[$bestSegment['method']] = true;

        $ordered[] = [[
            'id' => (int) $bestClient['id'],
            'clientId' => (int) $bestClient['id'],
            'routeOrder' => count($ordered) + 1,
            'isFirstCall' => false,
            'manualOverride' => false,
            'segmentMethod' => $bestSegment['method'],
            'segmentLabel' => $bestSegment['label'],
            'segmentDistanceKm' => $bestSegment['distanceKm'] !== null ? round((float) $bestSegment['distanceKm'], 2) : null,
            'segmentScore' => round((float) ($bestSegment['score'] ?? 0), 2),
        ] + $bestClient];

        $current = $bestClient;
    }

    $methodKeys = array_keys($methodsUsed);
    if (empty($methodKeys)) {
        $optimisationMethod = 'fixed_first_call';
    } elseif (count($methodKeys) > 1) {
        $optimisationMethod = 'mixed';
    } else {
        $optimisationMethod = $methodKeys[0];
    }

    return [
        'stops' => $ordered,
        'optimisationMethod' => $optimisationMethod,
    ];
}

function normalizeClientIds($value): array
{
    if (!is_array($value)) {
        return [];
    }

    $ids = [];
    foreach ($value as $item) {
        $id = intValue($item, 0);
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function normalizeStopsInput($value, int $firstCallClientId): array
{
    $rows = is_array($value) ? $value : [];
    $normalized = [];
    $seenClientIds = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $clientId = intValue($row['clientId'] ?? $row['id'] ?? 0, 0);
        if ($clientId <= 0 || isset($seenClientIds[$clientId])) {
            continue;
        }

        $seenClientIds[$clientId] = true;
        $normalized[] = [
            'clientId' => $clientId,
            'routeOrder' => count($normalized) + 1,
            'isFirstCall' => $clientId === $firstCallClientId,
            'manualOverride' => boolValue($row['manualOverride'] ?? false),
            'segmentMethod' => stringValue($row['segmentMethod'] ?? ''),
            'segmentLabel' => stringValue($row['segmentLabel'] ?? ''),
            'segmentDistanceKm' => nullableFloat($row['segmentDistanceKm'] ?? null),
            'segmentScore' => nullableFloat($row['segmentScore'] ?? null),
        ];
    }

    if (empty($normalized)) {
        return [];
    }

    $firstIndex = null;
    foreach ($normalized as $index => $row) {
        if ($row['clientId'] === $firstCallClientId) {
            $firstIndex = $index;
            break;
        }
    }

    if ($firstIndex === null) {
        return [];
    }

    if ($firstIndex !== 0) {
        $first = $normalized[$firstIndex];
        unset($normalized[$firstIndex]);
        array_unshift($normalized, $first);
        $normalized = array_values($normalized);
    }

    foreach ($normalized as $index => &$row) {
        $row['routeOrder'] = $index + 1;
        $row['isFirstCall'] = $index === 0;
        if ($index === 0) {
            $row['segmentMethod'] = 'fixed_first_call';
            $row['segmentLabel'] = 'Fixed first call';
            $row['segmentDistanceKm'] = null;
            $row['segmentScore'] = null;
        }
    }
    unset($row);

    return $normalized;
}

function duplicateClientExists(mysqli $conn, string $fullName, string $addressLine1, string $postcode, int $excludeId = 0): bool
{
    $sql = 'SELECT id FROM route_clients WHERE full_name = ? AND address_line_1 = ? AND postcode = ?';
    if ($excludeId > 0) {
        $sql .= ' AND id <> ?';
    }
    $sql .= ' LIMIT 1';

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return false;
    }

    if ($excludeId > 0) {
        $stmt->bind_param('sssi', $fullName, $addressLine1, $postcode, $excludeId);
    } else {
        $stmt->bind_param('sss', $fullName, $addressLine1, $postcode);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();
    $duplicate = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return (bool) $duplicate;
}

function countStopsUsingClient(mysqli $conn, int $clientId): int
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM route_run_stops WHERE client_id = ?');
    if ($stmt === false) {
        return 0;
    }

    $stmt->bind_param('i', $clientId);
    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return (int) ($row['total'] ?? 0);
}

function findCarerNameById(mysqli $conn, int $accountId): string
{
    $account = fetchAccountById($conn, $accountId);
    if ($account === null) {
        return '';
    }

    return accountDisplayName($account);
}

function buildBootstrapPayload(mysqli $conn): array
{
    $clients = fetchClients($conn);
    $runs = fetchRunSummaries($conn);

    return [
        'clients' => $clients,
        'runs' => $runs,
        'carers' => fetchCarerOptions($conn),
        'mapLookup' => [
            'enabled' => googleMapsBrowserConfigured() || googleGeocodingConfigured(),
            'provider' => googleMapsBrowserConfigured() ? 'google_maps_js' : (googleGeocodingConfigured() ? 'google_geocoding' : 'manual_pin_only'),
            'country' => 'GB',
            'googleJsEnabled' => googleMapsBrowserConfigured(),
            'browserApiKey' => googleMapsBrowserApiKey(),
        ],
        'stats' => [
            'totalClients' => count($clients),
            'activeClients' => count(array_filter($clients, static function ($item) {
                return !empty($item['isActive']);
            })),
            'inactiveClients' => count(array_filter($clients, static function ($item) {
                return empty($item['isActive']);
            })),
            'savedRuns' => count($runs),
            'manualOverrideRuns' => count(array_filter($runs, static function ($item) {
                return !empty($item['manualOverride']);
            })),
            'upcomingRuns' => count(array_filter($runs, static function ($item) {
                return stringValue($item['runDate'] ?? '') >= date('Y-m-d');
            })),
        ],
    ];
}

try {
    $conn = createDatabaseConnection();
    ensureRouteOptimiserTables($conn);
    ensureDemoClients($conn);
} catch (Throwable $exception) {
    jsonResponse([
        'success' => false,
        'message' => 'Failed to initialize route optimiser service.',
        'detail' => $exception->getMessage(),
    ], 500);
}

$action = stringValue($_GET['action'] ?? 'getBootstrap');
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($action === 'getBootstrap' && ($method === 'GET' || $method === 'POST')) {
    requireRoutePermission($conn);

    try {
        jsonResponse([
            'success' => true,
            'message' => 'Route optimiser loaded.',
        ] + buildBootstrapPayload($conn));
    } catch (Throwable $exception) {
        jsonResponse([
            'success' => false,
            'message' => 'Failed to load route optimiser data.',
            'detail' => $exception->getMessage(),
        ], 500);
    }
}

if ($action === 'getRun' && ($method === 'GET' || $method === 'POST')) {
    requireRoutePermission($conn);

    $payload = $method === 'POST' ? readJsonPayload() : $_GET;
    $runId = intValue($payload['id'] ?? 0, 0);
    if ($runId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid run identifier.'], 422);
    }

    $detail = fetchRunDetail($conn, $runId);
    if ($detail === null) {
        jsonResponse(['success' => false, 'message' => 'Run not found.'], 404);
    }

    jsonResponse([
        'success' => true,
        'run' => $detail['run'],
        'stops' => $detail['stops'],
    ]);
}

if ($action === 'lookupAddress' && $method === 'POST') {
    requireRoutePermission($conn);

    $payload = readJsonPayload();
    $addressLine1 = stringValue($payload['addressLine1'] ?? '');
    $townCity = stringValue($payload['townCity'] ?? '');
    $postcode = normalizePostcode($payload['postcode'] ?? '');

    if ($addressLine1 === '' || $townCity === '' || $postcode === '') {
        jsonResponse(['success' => false, 'message' => 'Enter address line 1, town/city, and postcode before searching the map.'], 422);
    }

    try {
        $lookup = geocodeAddressWithGoogle([
            'addressLine1' => $addressLine1,
            'addressLine2' => stringValue($payload['addressLine2'] ?? ''),
            'townCity' => $townCity,
            'county' => stringValue($payload['county'] ?? ''),
            'postcode' => $postcode,
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Address found. Review the pin and confirm the visit location.',
            'lookup' => $lookup,
        ]);
    } catch (Throwable $exception) {
        jsonResponse([
            'success' => false,
            'message' => 'Address lookup failed.',
            'detail' => $exception->getMessage(),
        ], 422);
    }
}

if ($action === 'saveClient' && $method === 'POST') {
    requireRoutePermission($conn);

    $payload = readJsonPayload();
    $clientId = intValue($payload['id'] ?? 0, 0);
    $fullName = stringValue($payload['fullName'] ?? '');
    $addressLine1 = stringValue($payload['addressLine1'] ?? '');
    $addressLine2 = stringValue($payload['addressLine2'] ?? '');
    $townCity = stringValue($payload['townCity'] ?? '');
    $county = stringValue($payload['county'] ?? '');
    $postcode = normalizePostcode($payload['postcode'] ?? '');
    $notes = stringValue($payload['notes'] ?? '');
    $preferredCallType = stringValue($payload['preferredCallType'] ?? '');
    $areaZone = stringValue($payload['areaZone'] ?? '');
    $latitude = safeLatitude($payload['latitude'] ?? null);
    $longitude = safeLongitude($payload['longitude'] ?? null);
    $latitudeParam = nullableDecimalParam($latitude);
    $longitudeParam = nullableDecimalParam($longitude);
    $isActive = boolValue($payload['isActive'] ?? true, true) ? 1 : 0;

    if ($fullName === '' || $addressLine1 === '' || $townCity === '' || $postcode === '') {
        jsonResponse(['success' => false, 'message' => 'Full name, address line 1, town/city, and postcode are required.'], 422);
    }

    if (duplicateClientExists($conn, $fullName, $addressLine1, $postcode, $clientId)) {
        jsonResponse(['success' => false, 'message' => 'A client with the same name and address already exists.'], 409);
    }

    if ($clientId > 0) {
        $stmt = $conn->prepare("UPDATE route_clients SET full_name = ?, address_line_1 = ?, address_line_2 = ?, town_city = ?, county = ?, postcode = ?, notes = ?, preferred_call_type = ?, area_zone = ?, latitude = NULLIF(?, ''), longitude = NULLIF(?, ''), is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? LIMIT 1");
        if ($stmt === false) {
            jsonResponse(['success' => false, 'message' => 'Failed to prepare client update query.'], 500);
        }
        $stmt->bind_param('sssssssssssii', $fullName, $addressLine1, $addressLine2, $townCity, $county, $postcode, $notes, $preferredCallType, $areaZone, $latitudeParam, $longitudeParam, $isActive, $clientId);
        if (!$stmt->execute()) {
            $stmt->close();
            jsonResponse(['success' => false, 'message' => 'Failed to update client.'], 500);
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO route_clients (full_name, address_line_1, address_line_2, town_city, county, postcode, notes, preferred_call_type, area_zone, latitude, longitude, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?)");
        if ($stmt === false) {
            jsonResponse(['success' => false, 'message' => 'Failed to prepare client insert query.'], 500);
        }
        $stmt->bind_param('sssssssssssi', $fullName, $addressLine1, $addressLine2, $townCity, $county, $postcode, $notes, $preferredCallType, $areaZone, $latitudeParam, $longitudeParam, $isActive);
        if (!$stmt->execute()) {
            $stmt->close();
            jsonResponse(['success' => false, 'message' => 'Failed to save client.'], 500);
        }
        $clientId = (int) $stmt->insert_id;
        $stmt->close();
    }

    $client = fetchClientById($conn, $clientId);
    if ($client === null) {
        jsonResponse(['success' => false, 'message' => 'Client saved but could not be reloaded.'], 500);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Client saved successfully.',
        'client' => $client,
    ]);
}

if ($action === 'deleteClient' && $method === 'POST') {
    requireRoutePermission($conn);

    $payload = readJsonPayload();
    $clientId = intValue($payload['id'] ?? 0, 0);
    if ($clientId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid client identifier.'], 422);
    }

    $client = fetchClientById($conn, $clientId);
    if ($client === null) {
        jsonResponse(['success' => false, 'message' => 'Client not found.'], 404);
    }

    if (countStopsUsingClient($conn, $clientId) > 0) {
        jsonResponse([
            'success' => false,
            'message' => 'This client is already linked to saved runs. Set the client to inactive instead of deleting it.',
        ], 409);
    }

    $stmt = $conn->prepare('DELETE FROM route_clients WHERE id = ? LIMIT 1');
    if ($stmt === false) {
        jsonResponse(['success' => false, 'message' => 'Failed to prepare client delete query.'], 500);
    }
    $stmt->bind_param('i', $clientId);
    if (!$stmt->execute()) {
        $stmt->close();
        jsonResponse(['success' => false, 'message' => 'Failed to delete client.'], 500);
    }
    $stmt->close();

    jsonResponse([
        'success' => true,
        'message' => 'Client deleted successfully.',
        'deletedId' => $clientId,
    ]);
}

if ($action === 'generateRoute' && $method === 'POST') {
    requireRoutePermission($conn);

    $payload = readJsonPayload();
    $clientIds = normalizeClientIds($payload['clientIds'] ?? []);
    $firstCallClientId = intValue($payload['firstCallClientId'] ?? 0, 0);

    if (empty($clientIds)) {
        jsonResponse(['success' => false, 'message' => 'Select at least one client for the run.'], 422);
    }

    if ($firstCallClientId <= 0) {
        $firstCallClientId = $clientIds[0];
    }

    if (!in_array($firstCallClientId, $clientIds, true)) {
        jsonResponse(['success' => false, 'message' => 'First call must be one of the selected clients.'], 422);
    }

    $clients = fetchClientsByIds($conn, $clientIds);
    if (count($clients) !== count($clientIds)) {
        jsonResponse(['success' => false, 'message' => 'One or more selected clients could not be found.'], 404);
    }

    $orderedResult = generateOrderedStops($clients, $firstCallClientId);
    $stops = $orderedResult['stops'];

    $coordinateSegments = 0;
    $heuristicSegments = 0;
    foreach ($stops as $stop) {
        if (($stop['segmentMethod'] ?? '') === 'coordinates') {
            $coordinateSegments++;
        }
        if (($stop['segmentMethod'] ?? '') === 'postcode_heuristic') {
            $heuristicSegments++;
        }
    }

    jsonResponse([
        'success' => true,
        'message' => 'Route suggestion generated.',
        'stops' => $stops,
        'optimisationMethod' => $orderedResult['optimisationMethod'],
        'summary' => [
            'stopCount' => count($stops),
            'coordinateSegments' => $coordinateSegments,
            'heuristicSegments' => $heuristicSegments,
        ],
    ]);
}

if ($action === 'saveRun' && $method === 'POST') {
    $actor = requireRoutePermission($conn);

    $payload = readJsonPayload();
    $runId = intValue($payload['id'] ?? 0, 0);
    $runName = stringValue($payload['runName'] ?? '');
    $runDate = stringValue($payload['runDate'] ?? '');
    $shiftLabel = stringValue($payload['shiftLabel'] ?? DEFAULT_SHIFT_LABEL);
    $assignedCarerAccountId = intValue($payload['assignedCarerAccountId'] ?? 0, 0);
    $assignedCarerName = stringValue($payload['assignedCarerName'] ?? '');
    $notes = stringValue($payload['notes'] ?? '');
    $firstCallClientId = intValue($payload['firstCallClientId'] ?? 0, 0);
    $stops = normalizeStopsInput($payload['stops'] ?? [], $firstCallClientId);
    $manualOverride = boolValue($payload['manualOverride'] ?? false);
    $optimisationMethod = stringValue($payload['optimisationMethod'] ?? '');

    if ($runName === '' || $runDate === '' || $shiftLabel === '') {
        jsonResponse(['success' => false, 'message' => 'Run name, date, and shift/time period are required.'], 422);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $runDate)) {
        jsonResponse(['success' => false, 'message' => 'Run date must use YYYY-MM-DD format.'], 422);
    }

    if (empty($stops)) {
        jsonResponse(['success' => false, 'message' => 'Generate a route before saving the run.'], 422);
    }

    $stopClientIds = array_map(static function ($stop) {
        return (int) ($stop['clientId'] ?? 0);
    }, $stops);
    $stopClientIds = array_values(array_unique(array_filter($stopClientIds, static function ($value) {
        return $value > 0;
    })));

    if ($firstCallClientId <= 0 || !in_array($firstCallClientId, $stopClientIds, true)) {
        jsonResponse(['success' => false, 'message' => 'First call must be part of the selected run stops.'], 422);
    }

    $clients = fetchClientsByIds($conn, $stopClientIds);
    if (count($clients) !== count($stopClientIds)) {
        jsonResponse(['success' => false, 'message' => 'One or more selected clients could not be found.'], 404);
    }

    if ($assignedCarerAccountId > 0 && $assignedCarerName === '') {
        $assignedCarerName = findCarerNameById($conn, $assignedCarerAccountId);
    }

    if ($optimisationMethod === '') {
        $optimisationMethod = 'manual';
    }

    foreach ($stops as $stop) {
        if (!empty($stop['manualOverride'])) {
            $manualOverride = true;
            break;
        }
    }

    $actorId = (int) ($actor['id'] ?? 0);

    $conn->begin_transaction();
    try {
        if ($runId > 0) {
            $updateStmt = $conn->prepare('UPDATE route_runs SET run_name = ?, run_date = ?, shift_label = ?, assigned_carer_account_id = ?, assigned_carer_name = ?, notes = ?, first_call_client_id = ?, optimisation_method = ?, generated_at = NOW(), manual_override = ?, updated_by_account_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? LIMIT 1');
            if ($updateStmt === false) {
                throw new RuntimeException('Failed to prepare run update query.');
            }
            $updateStmt->bind_param('sssissisiii', $runName, $runDate, $shiftLabel, $assignedCarerAccountId, $assignedCarerName, $notes, $firstCallClientId, $optimisationMethod, $manualOverride, $actorId, $runId);
            if (!$updateStmt->execute()) {
                $updateStmt->close();
                throw new RuntimeException('Failed to update run.');
            }
            $updateStmt->close();
        } else {
            $insertStmt = $conn->prepare('INSERT INTO route_runs (run_name, run_date, shift_label, assigned_carer_account_id, assigned_carer_name, notes, first_call_client_id, optimisation_method, generated_at, manual_override, created_by_account_id, updated_by_account_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)');
            if ($insertStmt === false) {
                throw new RuntimeException('Failed to prepare run insert query.');
            }
            $insertStmt->bind_param('sssissisiii', $runName, $runDate, $shiftLabel, $assignedCarerAccountId, $assignedCarerName, $notes, $firstCallClientId, $optimisationMethod, $manualOverride, $actorId, $actorId);
            if (!$insertStmt->execute()) {
                $insertStmt->close();
                throw new RuntimeException('Failed to save run.');
            }
            $runId = (int) $insertStmt->insert_id;
            $insertStmt->close();
        }

        $deleteStopsStmt = $conn->prepare('DELETE FROM route_run_stops WHERE run_id = ?');
        if ($deleteStopsStmt === false) {
            throw new RuntimeException('Failed to prepare existing stop delete query.');
        }
        $deleteStopsStmt->bind_param('i', $runId);
        if (!$deleteStopsStmt->execute()) {
            $deleteStopsStmt->close();
            throw new RuntimeException('Failed to replace existing route stops.');
        }
        $deleteStopsStmt->close();

        $insertStopStmt = $conn->prepare('INSERT INTO route_run_stops (run_id, client_id, route_order, is_first_call, manual_override, segment_method, segment_label, segment_distance_km, segment_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if ($insertStopStmt === false) {
            throw new RuntimeException('Failed to prepare route stop insert query.');
        }

        foreach ($stops as $stop) {
            $clientId = (int) $stop['clientId'];
            $routeOrder = (int) $stop['routeOrder'];
            $isFirstCall = !empty($stop['isFirstCall']) ? 1 : 0;
            $stopManualOverride = !empty($stop['manualOverride']) ? 1 : 0;
            $segmentMethod = stringValue($stop['segmentMethod'] ?? '');
            $segmentLabel = stringValue($stop['segmentLabel'] ?? '');
            $segmentDistanceKm = nullableFloat($stop['segmentDistanceKm'] ?? null);
            $segmentScore = nullableFloat($stop['segmentScore'] ?? null);

            $insertStopStmt->bind_param('iiiiissdd', $runId, $clientId, $routeOrder, $isFirstCall, $stopManualOverride, $segmentMethod, $segmentLabel, $segmentDistanceKm, $segmentScore);
            if (!$insertStopStmt->execute()) {
                $insertStopStmt->close();
                throw new RuntimeException('Failed to save route stops.');
            }
        }

        $insertStopStmt->close();
        $conn->commit();
    } catch (Throwable $exception) {
        $conn->rollback();
        jsonResponse([
            'success' => false,
            'message' => 'Failed to save run.',
            'detail' => $exception->getMessage(),
        ], 500);
    }

    $detail = fetchRunDetail($conn, $runId);
    if ($detail === null) {
        jsonResponse(['success' => false, 'message' => 'Run saved but could not be reloaded.'], 500);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Run saved successfully.',
        'run' => $detail['run'],
        'stops' => $detail['stops'],
    ]);
}

if ($action === 'deleteRun' && $method === 'POST') {
    requireRoutePermission($conn);

    $payload = readJsonPayload();
    $runId = intValue($payload['id'] ?? 0, 0);
    if ($runId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid run identifier.'], 422);
    }

    $detail = fetchRunDetail($conn, $runId);
    if ($detail === null) {
        jsonResponse(['success' => false, 'message' => 'Run not found.'], 404);
    }

    $stmt = $conn->prepare('DELETE FROM route_runs WHERE id = ? LIMIT 1');
    if ($stmt === false) {
        jsonResponse(['success' => false, 'message' => 'Failed to prepare run delete query.'], 500);
    }

    $stmt->bind_param('i', $runId);
    if (!$stmt->execute()) {
        $stmt->close();
        jsonResponse(['success' => false, 'message' => 'Failed to delete run.'], 500);
    }
    $stmt->close();

    jsonResponse([
        'success' => true,
        'message' => 'Run deleted successfully.',
        'deletedId' => $runId,
    ]);
}

$conn->close();
jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
