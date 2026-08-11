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

const DEFAULT_GA4_PROPERTY_ID = '526215262';
const DEFAULT_LOCAL_GA4_KEY_FILE = 'C:\\Users\\hp\\Downloads\\facilitatecare-0a7d9448b8ae.json';
const LOCAL_GA4_CONFIG_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'ga4.local.php';
const GA_SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';
const GA_TOKEN_URL = 'https://oauth2.googleapis.com/token';
const GA_DATA_API_BASE = 'https://analyticsdata.googleapis.com/v1beta';

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function strValue($value): string
{
    return trim((string) $value);
}

function intValueLocal($value, int $default = 0): int
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return $default;
    }
    return (int) $value;
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
        return $all;
    }

    $all['cars.dashboard'] = true;
    return $all;
}

function rolePermissionsTableExists(mysqli $conn): bool
{
    $sql = "SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'role_permissions'
            LIMIT 1";
    $result = $conn->query($sql);
    return $result !== false && $result->num_rows > 0;
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
            $key = strValue($row['permission_key'] ?? '');
            if ($key !== '' && array_key_exists($key, $permissions)) {
                $permissions[$key] = ((int) ($row['is_allowed'] ?? 0)) === 1;
            }
        }
    }

    $stmt->close();
    return $permissions;
}

function findSessionAccount(mysqli $conn): ?array
{
    $accountId = (int) ($_SESSION['account_id'] ?? 0);
    if ($accountId > 0) {
        $stmt = $conn->prepare('SELECT ID, Email, role_key, is_active FROM accounts WHERE ID = ? LIMIT 1');
        if ($stmt !== false) {
            $stmt->bind_param('i', $accountId);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $row = $result ? $result->fetch_assoc() : null;
                $stmt->close();
                if ($row) {
                    return $row;
                }
            } else {
                $stmt->close();
            }
        }
    }

    $email = strValue($_SESSION['Email'] ?? '');
    if ($email === '') {
        return null;
    }

    $stmt = $conn->prepare('SELECT ID, Email, role_key, is_active FROM accounts WHERE Email = ? LIMIT 1');
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('s', $email);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function buildJwtAssertion(string $clientEmail, string $privateKey): string
{
    $now = time();
    $header = base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = base64UrlEncode(json_encode([
        'iss' => $clientEmail,
        'scope' => GA_SCOPE,
        'aud' => GA_TOKEN_URL,
        'exp' => $now + 3600,
        'iat' => $now,
    ]));
    $unsigned = $header . '.' . $payload;

    $signature = '';
    $private = openssl_pkey_get_private($privateKey);
    if ($private === false) {
        throw new RuntimeException('GA private key is invalid.');
    }

    $signed = openssl_sign($unsigned, $signature, $private, OPENSSL_ALGO_SHA256);
    if (!$signed) {
        throw new RuntimeException('Unable to sign GA JWT assertion.');
    }

    return $unsigned . '.' . base64UrlEncode($signature);
}

function httpPostViaStream(
    string $url,
    string $body,
    array $headers = [],
    int $timeoutSeconds = 20,
    bool $verifyTls = true,
    string $caBundle = ''
): array {
    $sslOptions = [
        'verify_peer' => $verifyTls,
        'verify_peer_name' => $verifyTls,
    ];

    if ($verifyTls && $caBundle !== '' && is_file($caBundle) && is_readable($caBundle)) {
        $sslOptions['cafile'] = $caBundle;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true,
        ],
        'ssl' => $sslOptions,
    ]);

    $responseBody = @file_get_contents($url, false, $context);
    $status = 0;
    if (isset($http_response_header) && is_array($http_response_header) && !empty($http_response_header[0])) {
        if (preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches) === 1) {
            $status = (int) $matches[1];
        }
    }

    if ($responseBody === false) {
        $lastError = error_get_last();
        $msg = strValue($lastError['message'] ?? '');
        throw new RuntimeException('HTTP request failed' . ($msg !== '' ? ': ' . $msg : '.'));
    }

    return ['status' => $status, 'body' => (string) $responseBody];
}

function httpPost(string $url, string $body, array $headers = [], int $timeoutSeconds = 20): array
{
    $caBundle = strValue(envValue('GA4_CA_BUNDLE', envValue('CURL_CA_BUNDLE', '')));

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);

        if ($caBundle !== '' && is_file($caBundle) && is_readable($caBundle)) {
            curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
        }

        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        // Local WAMP often lacks CA trust store. Retry insecure only for localhost development.
        if (
            $responseBody === false &&
            isLocalRequestHost() &&
            stripos((string) $curlError, 'SSL certificate problem') !== false
        ) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $responseBody = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
        }

        curl_close($ch);

        if ($responseBody === false) {
            if (isLocalRequestHost()) {
                // Final localhost fallback: use PHP stream transport with TLS verification disabled.
                try {
                    return httpPostViaStream($url, $body, $headers, $timeoutSeconds, false);
                } catch (Throwable $streamError) {
                    $streamMessage = strValue($streamError->getMessage());
                    if ($streamMessage !== '') {
                        throw new RuntimeException('HTTP request failed: ' . $curlError . ' | Stream fallback failed: ' . $streamMessage);
                    }
                }
            }
            throw new RuntimeException('HTTP request failed: ' . $curlError);
        }

        return ['status' => $httpCode, 'body' => (string) $responseBody];
    }

    return httpPostViaStream($url, $body, $headers, $timeoutSeconds, !isLocalRequestHost(), $caBundle);
}

function requestGoogleAccessToken(string $clientEmail, string $privateKey): string
{
    $assertion = buildJwtAssertion($clientEmail, $privateKey);
    $body = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $assertion,
    ]);
    $headers = [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
    ];

    $response = httpPost(GA_TOKEN_URL, $body, $headers);
    $payload = json_decode($response['body'], true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid token response from Google OAuth.');
    }

    if (($response['status'] < 200 || $response['status'] >= 300) || empty($payload['access_token'])) {
        $message = strValue($payload['error_description'] ?? ($payload['error'] ?? 'Unable to get GA access token.'));
        throw new RuntimeException($message !== '' ? $message : 'Unable to get GA access token.');
    }

    return (string) $payload['access_token'];
}

function runGaReport(string $accessToken, string $propertyId, array $payload): array
{
    $url = GA_DATA_API_BASE . '/properties/' . rawurlencode($propertyId) . ':runReport';
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $accessToken,
    ];

    $response = httpPost($url, json_encode($payload), $headers);
    $decoded = json_decode($response['body'], true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid response from Google Analytics Data API.');
    }

    if ($response['status'] < 200 || $response['status'] >= 300) {
        $message = strValue($decoded['error']['message'] ?? 'Analytics API request failed.');
        throw new RuntimeException($message !== '' ? $message : 'Analytics API request failed.');
    }

    return $decoded;
}

function toFloat($value): float
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return 0.0;
    }
    return (float) $value;
}

function toInt($value): int
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return 0;
    }
    return (int) round((float) $value);
}

function formatGaDate(string $value): string
{
    if (preg_match('/^\d{8}$/', $value) !== 1) {
        return $value;
    }

    $year = substr($value, 0, 4);
    $month = substr($value, 4, 2);
    $day = substr($value, 6, 2);
    return $year . '-' . $month . '-' . $day;
}

function loadGaCredentials(): array
{
    $propertyId = strValue(envValue('GA4_PROPERTY_ID', envValue('GA_PROPERTY_ID', DEFAULT_GA4_PROPERTY_ID)));
    $clientEmail = strValue(envValue('GA4_CLIENT_EMAIL', ''));
    $privateKey = (string) envValue('GA4_PRIVATE_KEY', '');
    $privateKey = str_replace(["\r\n", '\n'], ["\n", "\n"], $privateKey);
    $serviceAccountFile = strValue(envValue('GA4_SERVICE_ACCOUNT_FILE', ''));

    if ($serviceAccountFile !== '' && (!is_file($serviceAccountFile) || !is_readable($serviceAccountFile))) {
        $serviceAccountFile = '';
    }

    if (is_file(LOCAL_GA4_CONFIG_FILE) && is_readable(LOCAL_GA4_CONFIG_FILE)) {
        $config = include LOCAL_GA4_CONFIG_FILE;
        if (is_array($config)) {
            if ($propertyId === '') {
                $propertyId = strValue($config['propertyId'] ?? ($config['GA4_PROPERTY_ID'] ?? ''));
            }
            if ($clientEmail === '') {
                $clientEmail = strValue($config['clientEmail'] ?? ($config['GA4_CLIENT_EMAIL'] ?? ''));
            }
            if ($privateKey === '') {
                $privateKey = strValue($config['privateKey'] ?? ($config['GA4_PRIVATE_KEY'] ?? ''));
            }
            if ($serviceAccountFile === '') {
                $serviceAccountFile = strValue($config['serviceAccountFile'] ?? ($config['GA4_SERVICE_ACCOUNT_FILE'] ?? ''));
            }
        }
    }

    $privateKey = str_replace(["\r\n", '\n'], ["\n", "\n"], $privateKey);
    if ($serviceAccountFile !== '' && (!is_file($serviceAccountFile) || !is_readable($serviceAccountFile))) {
        $serviceAccountFile = '';
    }

    if ($serviceAccountFile === '') {
        $googleAppCreds = strValue(envValue('GOOGLE_APPLICATION_CREDENTIALS', ''));
        if ($googleAppCreds !== '' && is_file($googleAppCreds) && is_readable($googleAppCreds)) {
            $serviceAccountFile = $googleAppCreds;
        }
    }

    if ($serviceAccountFile === '' && isLocalRequestHost() && is_file(DEFAULT_LOCAL_GA4_KEY_FILE) && is_readable(DEFAULT_LOCAL_GA4_KEY_FILE)) {
        $serviceAccountFile = DEFAULT_LOCAL_GA4_KEY_FILE;
    }

    if (($clientEmail === '' || $privateKey === '') && $serviceAccountFile !== '' && is_file($serviceAccountFile)) {
        $json = file_get_contents($serviceAccountFile);
        $decoded = json_decode((string) $json, true);
        if (is_array($decoded)) {
            if ($clientEmail === '') {
                $clientEmail = strValue($decoded['client_email'] ?? '');
            }
            if ($privateKey === '') {
                $privateKey = strValue($decoded['private_key'] ?? '');
            }
            if ($propertyId === '') {
                $propertyId = strValue($decoded['ga4_property_id'] ?? '');
            }
        }
    }

    return [
        'propertyId' => $propertyId,
        'clientEmail' => $clientEmail,
        'privateKey' => $privateKey,
    ];
}

function missingConfigKeys(array $config): array
{
    $missing = [];
    if (strValue($config['propertyId'] ?? '') === '') {
        $missing[] = 'GA4_PROPERTY_ID';
    }
    if (strValue($config['clientEmail'] ?? '') === '') {
        $missing[] = 'GA4_CLIENT_EMAIL';
    }
    if (strValue($config['privateKey'] ?? '') === '') {
        $missing[] = 'GA4_PRIVATE_KEY';
    }
    return $missing;
}

function gaDimensionValue(array $row, int $index, string $fallback = ''): string
{
    return (string) ($row['dimensionValues'][$index]['value'] ?? $fallback);
}

function gaMetricRaw(array $row, int $index, $fallback = 0)
{
    return $row['metricValues'][$index]['value'] ?? $fallback;
}

function gaMetricInt(array $row, int $index, int $fallback = 0): int
{
    return toInt(gaMetricRaw($row, $index, $fallback));
}

function gaMetricFloat(array $row, int $index, float $fallback = 0.0): float
{
    return toFloat(gaMetricRaw($row, $index, $fallback));
}

function mapGaFetchErrorMessage(string $detail): string
{
    if (stripos($detail, 'PERMISSION_DENIED') !== false || stripos($detail, 'does not have') !== false) {
        return 'Google denied access to this GA4 property. Add the service account as Viewer or Analyst in GA4 Property Access Management.';
    }
    if (stripos($detail, 'API has not been used') !== false || stripos($detail, 'not been used in project') !== false) {
        return 'Google Analytics Data API is not enabled in your Google Cloud project.';
    }
    if (stripos($detail, 'invalid_grant') !== false) {
        return 'Service account authentication failed. Check private key formatting and server date/time.';
    }
    if (stripos($detail, 'not found') !== false && stripos($detail, 'properties') !== false) {
        return 'GA4 property was not found. Confirm GA4_PROPERTY_ID is correct.';
    }
    return 'Unable to fetch Google Analytics data.';
}

function runGaRealtimeReport(string $accessToken, string $propertyId, array $payload): array
{
    $url = GA_DATA_API_BASE . '/properties/' . rawurlencode($propertyId) . ':runRealtimeReport';
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $accessToken,
    ];

    $response = httpPost($url, json_encode($payload), $headers);
    $decoded = json_decode($response['body'], true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid response from Google Analytics Realtime API.');
    }
    if ($response['status'] < 200 || $response['status'] >= 300) {
        $message = strValue($decoded['error']['message'] ?? 'Realtime API request failed.');
        throw new RuntimeException($message !== '' ? $message : 'Realtime API request failed.');
    }

    return $decoded;
}

function runGaReportWithFallback(
    string $accessToken,
    string $propertyId,
    array $primaryPayload,
    ?array $fallbackPayload = null
): array {
    try {
        return runGaReport($accessToken, $propertyId, $primaryPayload);
    } catch (Throwable $exception) {
        if ($fallbackPayload === null) {
            throw $exception;
        }
        return runGaReport($accessToken, $propertyId, $fallbackPayload);
    }
}

function buildOverviewSection(string $accessToken, string $propertyId, array $dateRange): array
{
    $summaryReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'metrics' => [
            ['name' => 'activeUsers'],
            ['name' => 'newUsers'],
            ['name' => 'sessions'],
            ['name' => 'engagedSessions'],
            ['name' => 'engagementRate'],
            ['name' => 'averageSessionDuration'],
            ['name' => 'screenPageViews'],
            ['name' => 'conversions'],
        ],
    ]);

    $timeSeriesReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'date']],
        'metrics' => [
            ['name' => 'sessions'],
            ['name' => 'activeUsers'],
            ['name' => 'conversions'],
        ],
        'orderBys' => [
            [
                'dimension' => [
                    'dimensionName' => 'date',
                    'orderType' => 'ALPHANUMERIC',
                ],
            ],
        ],
        'limit' => 400,
    ]);

    $sourcesReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'sessionSourceMedium']],
        'metrics' => [
            ['name' => 'sessions'],
            ['name' => 'activeUsers'],
            ['name' => 'conversions'],
        ],
        'orderBys' => [
            [
                'metric' => ['metricName' => 'sessions'],
                'desc' => true,
            ],
        ],
        'limit' => 10,
    ]);

    $pagesReport = runGaReportWithFallback(
        $accessToken,
        $propertyId,
        [
            'dateRanges' => $dateRange,
            'dimensions' => [['name' => 'landingPagePlusQueryString']],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'activeUsers'],
                ['name' => 'conversions'],
            ],
            'orderBys' => [
                [
                    'metric' => ['metricName' => 'sessions'],
                    'desc' => true,
                ],
            ],
            'limit' => 10,
        ],
        [
            'dateRanges' => $dateRange,
            'dimensions' => [['name' => 'pagePath']],
            'metrics' => [
                ['name' => 'screenPageViews'],
                ['name' => 'activeUsers'],
                ['name' => 'conversions'],
            ],
            'orderBys' => [
                [
                    'metric' => ['metricName' => 'screenPageViews'],
                    'desc' => true,
                ],
            ],
            'limit' => 10,
        ]
    );

    $summaryRow = $summaryReport['rows'][0] ?? [];
    $summary = [
        'activeUsers' => gaMetricInt($summaryRow, 0),
        'newUsers' => gaMetricInt($summaryRow, 1),
        'sessions' => gaMetricInt($summaryRow, 2),
        'engagedSessions' => gaMetricInt($summaryRow, 3),
        'engagementRate' => gaMetricFloat($summaryRow, 4),
        'averageSessionDuration' => gaMetricFloat($summaryRow, 5),
        'screenPageViews' => gaMetricInt($summaryRow, 6),
        'conversions' => gaMetricFloat($summaryRow, 7),
    ];

    $series = [];
    foreach (($timeSeriesReport['rows'] ?? []) as $row) {
        $series[] = [
            'date' => formatGaDate(gaDimensionValue($row, 0)),
            'sessions' => gaMetricInt($row, 0),
            'activeUsers' => gaMetricInt($row, 1),
            'conversions' => gaMetricFloat($row, 2),
        ];
    }

    $sources = [];
    foreach (($sourcesReport['rows'] ?? []) as $row) {
        $sources[] = [
            'sourceMedium' => gaDimensionValue($row, 0, '(not set)'),
            'sessions' => gaMetricInt($row, 0),
            'activeUsers' => gaMetricInt($row, 1),
            'conversions' => gaMetricFloat($row, 2),
        ];
    }

    $pages = [];
    foreach (($pagesReport['rows'] ?? []) as $row) {
        $pages[] = [
            'page' => gaDimensionValue($row, 0, '/'),
            'primaryMetric' => gaMetricInt($row, 0),
            'activeUsers' => gaMetricInt($row, 1),
            'conversions' => gaMetricFloat($row, 2),
        ];
    }

    return [
        'summary' => $summary,
        'series' => $series,
        'sources' => $sources,
        'pages' => $pages,
    ];
}

function buildAcquisitionSection(string $accessToken, string $propertyId, array $dateRange): array
{
    $channelsReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
        'metrics' => [
            ['name' => 'sessions'],
            ['name' => 'activeUsers'],
            ['name' => 'conversions'],
            ['name' => 'engagementRate'],
        ],
        'orderBys' => [[
            'metric' => ['metricName' => 'sessions'],
            'desc' => true,
        ]],
        'limit' => 12,
    ]);

    $sourcesReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'sessionSourceMedium']],
        'metrics' => [
            ['name' => 'sessions'],
            ['name' => 'activeUsers'],
            ['name' => 'conversions'],
        ],
        'orderBys' => [[
            'metric' => ['metricName' => 'sessions'],
            'desc' => true,
        ]],
        'limit' => 12,
    ]);

    $campaignsReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'sessionCampaignName']],
        'metrics' => [
            ['name' => 'sessions'],
            ['name' => 'activeUsers'],
            ['name' => 'conversions'],
        ],
        'orderBys' => [[
            'metric' => ['metricName' => 'sessions'],
            'desc' => true,
        ]],
        'limit' => 12,
    ]);

    $channels = [];
    foreach (($channelsReport['rows'] ?? []) as $row) {
        $channels[] = [
            'channel' => gaDimensionValue($row, 0, '(not set)'),
            'sessions' => gaMetricInt($row, 0),
            'activeUsers' => gaMetricInt($row, 1),
            'conversions' => gaMetricFloat($row, 2),
            'engagementRate' => gaMetricFloat($row, 3),
        ];
    }

    $sources = [];
    foreach (($sourcesReport['rows'] ?? []) as $row) {
        $sources[] = [
            'sourceMedium' => gaDimensionValue($row, 0, '(not set)'),
            'sessions' => gaMetricInt($row, 0),
            'activeUsers' => gaMetricInt($row, 1),
            'conversions' => gaMetricFloat($row, 2),
        ];
    }

    $campaigns = [];
    foreach (($campaignsReport['rows'] ?? []) as $row) {
        $campaignName = gaDimensionValue($row, 0, '(not set)');
        if (strtolower($campaignName) === '(not set)') {
            continue;
        }
        $campaigns[] = [
            'campaign' => $campaignName,
            'sessions' => gaMetricInt($row, 0),
            'activeUsers' => gaMetricInt($row, 1),
            'conversions' => gaMetricFloat($row, 2),
        ];
    }

    return [
        'channels' => $channels,
        'sources' => $sources,
        'campaigns' => $campaigns,
    ];
}

function buildPagesSection(string $accessToken, string $propertyId, array $dateRange): array
{
    $landingReport = runGaReportWithFallback(
        $accessToken,
        $propertyId,
        [
            'dateRanges' => $dateRange,
            'dimensions' => [['name' => 'landingPagePlusQueryString']],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'activeUsers'],
                ['name' => 'conversions'],
            ],
            'orderBys' => [[
                'metric' => ['metricName' => 'sessions'],
                'desc' => true,
            ]],
            'limit' => 15,
        ],
        [
            'dateRanges' => $dateRange,
            'dimensions' => [['name' => 'pagePath']],
            'metrics' => [
                ['name' => 'screenPageViews'],
                ['name' => 'activeUsers'],
                ['name' => 'conversions'],
            ],
            'orderBys' => [[
                'metric' => ['metricName' => 'screenPageViews'],
                'desc' => true,
            ]],
            'limit' => 15,
        ]
    );

    $contentReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'pagePath']],
        'metrics' => [
            ['name' => 'screenPageViews'],
            ['name' => 'activeUsers'],
            ['name' => 'averageSessionDuration'],
            ['name' => 'conversions'],
        ],
        'orderBys' => [[
            'metric' => ['metricName' => 'screenPageViews'],
            'desc' => true,
        ]],
        'limit' => 15,
    ]);

    $landingPages = [];
    foreach (($landingReport['rows'] ?? []) as $row) {
        $landingPages[] = [
            'page' => gaDimensionValue($row, 0, '/'),
            'sessions' => gaMetricInt($row, 0),
            'activeUsers' => gaMetricInt($row, 1),
            'conversions' => gaMetricFloat($row, 2),
        ];
    }

    $topContent = [];
    foreach (($contentReport['rows'] ?? []) as $row) {
        $topContent[] = [
            'page' => gaDimensionValue($row, 0, '/'),
            'views' => gaMetricInt($row, 0),
            'activeUsers' => gaMetricInt($row, 1),
            'averageSessionDuration' => gaMetricFloat($row, 2),
            'conversions' => gaMetricFloat($row, 3),
        ];
    }

    return [
        'landingPages' => $landingPages,
        'topContent' => $topContent,
    ];
}

function buildAudienceSection(string $accessToken, string $propertyId, array $dateRange): array
{
    $countriesReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'country']],
        'metrics' => [
            ['name' => 'activeUsers'],
            ['name' => 'newUsers'],
            ['name' => 'sessions'],
            ['name' => 'conversions'],
        ],
        'orderBys' => [[
            'metric' => ['metricName' => 'activeUsers'],
            'desc' => true,
        ]],
        'limit' => 10,
    ]);

    $citiesReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'city'], ['name' => 'country']],
        'metrics' => [
            ['name' => 'activeUsers'],
            ['name' => 'sessions'],
        ],
        'orderBys' => [[
            'metric' => ['metricName' => 'activeUsers'],
            'desc' => true,
        ]],
        'limit' => 10,
    ]);

    $devicesReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'deviceCategory']],
        'metrics' => [
            ['name' => 'activeUsers'],
            ['name' => 'sessions'],
            ['name' => 'conversions'],
        ],
        'orderBys' => [[
            'metric' => ['metricName' => 'activeUsers'],
            'desc' => true,
        ]],
        'limit' => 10,
    ]);

    $newReturning = [];
    try {
        $newReturningReport = runGaReport($accessToken, $propertyId, [
            'dateRanges' => $dateRange,
            'dimensions' => [['name' => 'newVsReturning']],
            'metrics' => [
                ['name' => 'activeUsers'],
                ['name' => 'sessions'],
            ],
            'limit' => 5,
        ]);
        foreach (($newReturningReport['rows'] ?? []) as $row) {
            $newReturning[] = [
                'segment' => gaDimensionValue($row, 0, '(not set)'),
                'activeUsers' => gaMetricInt($row, 0),
                'sessions' => gaMetricInt($row, 1),
            ];
        }
    } catch (Throwable $exception) {
        $newReturning = [];
    }

    $countries = [];
    foreach (($countriesReport['rows'] ?? []) as $row) {
        $countries[] = [
            'country' => gaDimensionValue($row, 0, '(not set)'),
            'activeUsers' => gaMetricInt($row, 0),
            'newUsers' => gaMetricInt($row, 1),
            'sessions' => gaMetricInt($row, 2),
            'conversions' => gaMetricFloat($row, 3),
        ];
    }

    $cities = [];
    foreach (($citiesReport['rows'] ?? []) as $row) {
        $cities[] = [
            'city' => gaDimensionValue($row, 0, '(not set)'),
            'country' => gaDimensionValue($row, 1, '(not set)'),
            'activeUsers' => gaMetricInt($row, 0),
            'sessions' => gaMetricInt($row, 1),
        ];
    }

    $devices = [];
    foreach (($devicesReport['rows'] ?? []) as $row) {
        $devices[] = [
            'device' => gaDimensionValue($row, 0, '(not set)'),
            'activeUsers' => gaMetricInt($row, 0),
            'sessions' => gaMetricInt($row, 1),
            'conversions' => gaMetricFloat($row, 2),
        ];
    }

    return [
        'countries' => $countries,
        'cities' => $cities,
        'devices' => $devices,
        'newReturning' => $newReturning,
    ];
}

function buildConversionsSection(string $accessToken, string $propertyId, array $dateRange): array
{
    $summaryReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'metrics' => [
            ['name' => 'conversions'],
            ['name' => 'sessions'],
            ['name' => 'engagedSessions'],
        ],
    ]);

    $eventsReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'eventName']],
        'metrics' => [
            ['name' => 'eventCount'],
            ['name' => 'conversions'],
            ['name' => 'totalUsers'],
        ],
        'orderBys' => [[
            'metric' => ['metricName' => 'eventCount'],
            'desc' => true,
        ]],
        'limit' => 15,
    ]);

    $trendReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'date']],
        'metrics' => [
            ['name' => 'conversions'],
            ['name' => 'sessions'],
        ],
        'orderBys' => [[
            'dimension' => [
                'dimensionName' => 'date',
                'orderType' => 'ALPHANUMERIC',
            ],
        ]],
        'limit' => 400,
    ]);

    $summaryRow = $summaryReport['rows'][0] ?? [];
    $conversions = gaMetricFloat($summaryRow, 0);
    $sessions = gaMetricInt($summaryRow, 1);
    $engagedSessions = gaMetricInt($summaryRow, 2);

    $events = [];
    foreach (($eventsReport['rows'] ?? []) as $row) {
        $events[] = [
            'eventName' => gaDimensionValue($row, 0, '(not set)'),
            'eventCount' => gaMetricInt($row, 0),
            'conversions' => gaMetricFloat($row, 1),
            'users' => gaMetricInt($row, 2),
        ];
    }

    $series = [];
    foreach (($trendReport['rows'] ?? []) as $row) {
        $series[] = [
            'date' => formatGaDate(gaDimensionValue($row, 0)),
            'conversions' => gaMetricFloat($row, 0),
            'sessions' => gaMetricInt($row, 1),
        ];
    }

    return [
        'summary' => [
            'conversions' => $conversions,
            'sessions' => $sessions,
            'engagedSessions' => $engagedSessions,
            'conversionRate' => $sessions > 0 ? ($conversions / $sessions) : 0,
        ],
        'events' => $events,
        'series' => $series,
    ];
}

function buildRealtimeSection(string $accessToken, string $propertyId): array
{
    $summaryReport = runGaRealtimeReport($accessToken, $propertyId, [
        'metrics' => [['name' => 'activeUsers']],
    ]);

    $summaryRow = $summaryReport['rows'][0] ?? [];
    $activeUsers = gaMetricInt($summaryRow, 0);

    $topPages = [];
    try {
        $pagesReport = runGaRealtimeReport($accessToken, $propertyId, [
            'dimensions' => [['name' => 'unifiedScreenName']],
            'metrics' => [['name' => 'activeUsers']],
            'limit' => 10,
        ]);
        foreach (($pagesReport['rows'] ?? []) as $row) {
            $topPages[] = [
                'label' => gaDimensionValue($row, 0, '(not set)'),
                'activeUsers' => gaMetricInt($row, 0),
            ];
        }
    } catch (Throwable $exception) {
        $topPages = [];
    }

    $topCountries = [];
    try {
        $countriesReport = runGaRealtimeReport($accessToken, $propertyId, [
            'dimensions' => [['name' => 'country']],
            'metrics' => [['name' => 'activeUsers']],
            'limit' => 10,
        ]);
        foreach (($countriesReport['rows'] ?? []) as $row) {
            $topCountries[] = [
                'country' => gaDimensionValue($row, 0, '(not set)'),
                'activeUsers' => gaMetricInt($row, 0),
            ];
        }
    } catch (Throwable $exception) {
        $topCountries = [];
    }

    $topDevices = [];
    try {
        $devicesReport = runGaRealtimeReport($accessToken, $propertyId, [
            'dimensions' => [['name' => 'deviceCategory']],
            'metrics' => [['name' => 'activeUsers']],
            'limit' => 10,
        ]);
        foreach (($devicesReport['rows'] ?? []) as $row) {
            $topDevices[] = [
                'device' => gaDimensionValue($row, 0, '(not set)'),
                'activeUsers' => gaMetricInt($row, 0),
            ];
        }
    } catch (Throwable $exception) {
        $topDevices = [];
    }

    return [
        'activeUsers' => $activeUsers,
        'topPages' => $topPages,
        'topCountries' => $topCountries,
        'topDevices' => $topDevices,
    ];
}

function buildSeoSection(string $accessToken, string $propertyId, array $dateRange): array
{
    $channelMixReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
        'metrics' => [
            ['name' => 'sessions'],
            ['name' => 'activeUsers'],
            ['name' => 'conversions'],
        ],
        'orderBys' => [[
            'metric' => ['metricName' => 'sessions'],
            'desc' => true,
        ]],
        'limit' => 12,
    ]);

    $organicPagesReport = runGaReportWithFallback(
        $accessToken,
        $propertyId,
        [
            'dateRanges' => $dateRange,
            'dimensions' => [['name' => 'landingPagePlusQueryString'], ['name' => 'sessionDefaultChannelGroup']],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'activeUsers'],
                ['name' => 'conversions'],
            ],
            'orderBys' => [[
                'metric' => ['metricName' => 'sessions'],
                'desc' => true,
            ]],
            'limit' => 35,
        ],
        [
            'dateRanges' => $dateRange,
            'dimensions' => [['name' => 'pagePath'], ['name' => 'sessionDefaultChannelGroup']],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'activeUsers'],
                ['name' => 'conversions'],
            ],
            'orderBys' => [[
                'metric' => ['metricName' => 'sessions'],
                'desc' => true,
            ]],
            'limit' => 35,
        ]
    );

    $channelMix = [];
    $organicSummary = [
        'sessions' => 0,
        'activeUsers' => 0,
        'conversions' => 0.0,
    ];

    foreach (($channelMixReport['rows'] ?? []) as $row) {
        $entry = [
            'channel' => gaDimensionValue($row, 0, '(not set)'),
            'sessions' => gaMetricInt($row, 0),
            'activeUsers' => gaMetricInt($row, 1),
            'conversions' => gaMetricFloat($row, 2),
        ];
        $channelMix[] = $entry;
        if (strcasecmp($entry['channel'], 'Organic Search') === 0) {
            $organicSummary = [
                'sessions' => $entry['sessions'],
                'activeUsers' => $entry['activeUsers'],
                'conversions' => $entry['conversions'],
            ];
        }
    }

    $organicPages = [];
    foreach (($organicPagesReport['rows'] ?? []) as $row) {
        $channel = gaDimensionValue($row, 1, '(not set)');
        if (strcasecmp($channel, 'Organic Search') !== 0) {
            continue;
        }
        $organicPages[] = [
            'page' => gaDimensionValue($row, 0, '/'),
            'sessions' => gaMetricInt($row, 0),
            'activeUsers' => gaMetricInt($row, 1),
            'conversions' => gaMetricFloat($row, 2),
        ];
        if (count($organicPages) >= 10) {
            break;
        }
    }

    return [
        'organicSummary' => $organicSummary,
        'channelMix' => $channelMix,
        'organicPages' => $organicPages,
    ];
}

function buildAlertsSection(string $accessToken, string $propertyId, array $dateRange, int $days): array
{
    $currentSummaryReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'metrics' => [
            ['name' => 'sessions'],
            ['name' => 'activeUsers'],
            ['name' => 'conversions'],
            ['name' => 'engagementRate'],
        ],
    ]);

    $previousDateRange = [[
        'startDate' => ($days * 2) . 'daysAgo',
        'endDate' => ($days + 1) . 'daysAgo',
    ]];
    $previousSummaryReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $previousDateRange,
        'metrics' => [
            ['name' => 'sessions'],
            ['name' => 'activeUsers'],
            ['name' => 'conversions'],
            ['name' => 'engagementRate'],
        ],
    ]);

    $channelsReport = runGaReport($accessToken, $propertyId, [
        'dateRanges' => $dateRange,
        'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
        'metrics' => [['name' => 'sessions']],
        'orderBys' => [[
            'metric' => ['metricName' => 'sessions'],
            'desc' => true,
        ]],
        'limit' => 8,
    ]);

    $currentRow = $currentSummaryReport['rows'][0] ?? [];
    $previousRow = $previousSummaryReport['rows'][0] ?? [];

    $current = [
        'sessions' => gaMetricInt($currentRow, 0),
        'activeUsers' => gaMetricInt($currentRow, 1),
        'conversions' => gaMetricFloat($currentRow, 2),
        'engagementRate' => gaMetricFloat($currentRow, 3),
    ];
    $previous = [
        'sessions' => gaMetricInt($previousRow, 0),
        'activeUsers' => gaMetricInt($previousRow, 1),
        'conversions' => gaMetricFloat($previousRow, 2),
        'engagementRate' => gaMetricFloat($previousRow, 3),
    ];

    $percentChange = static function (float $currentValue, float $previousValue): float {
        if ($previousValue == 0.0) {
            return $currentValue > 0 ? 100.0 : 0.0;
        }
        return (($currentValue - $previousValue) / $previousValue) * 100.0;
    };

    $sessionChange = $percentChange((float) $current['sessions'], (float) $previous['sessions']);
    $userChange = $percentChange((float) $current['activeUsers'], (float) $previous['activeUsers']);
    $conversionChange = $percentChange((float) $current['conversions'], (float) $previous['conversions']);

    $topChannels = [];
    $totalChannelSessions = 0;
    foreach (($channelsReport['rows'] ?? []) as $row) {
        $sessions = gaMetricInt($row, 0);
        $entry = [
            'channel' => gaDimensionValue($row, 0, '(not set)'),
            'sessions' => $sessions,
        ];
        $topChannels[] = $entry;
        $totalChannelSessions += $sessions;
    }

    $alerts = [];
    if ($current['sessions'] <= 0) {
        $alerts[] = [
            'id' => 'sessions_zero',
            'severity' => 'RED',
            'title' => 'No sessions detected',
            'message' => 'Current period has zero sessions.',
            'evidence' => ['sessions' => $current['sessions']],
        ];
    } elseif ($sessionChange <= -30) {
        $alerts[] = [
            'id' => 'sessions_drop_critical',
            'severity' => 'RED',
            'title' => 'Sessions dropped sharply',
            'message' => 'Sessions dropped by more than 30% compared to the previous window.',
            'evidence' => ['changePercent' => round($sessionChange, 1)],
        ];
    } elseif ($sessionChange <= -15) {
        $alerts[] = [
            'id' => 'sessions_drop_warning',
            'severity' => 'AMBER',
            'title' => 'Sessions trending down',
            'message' => 'Sessions dropped by more than 15% compared to the previous window.',
            'evidence' => ['changePercent' => round($sessionChange, 1)],
        ];
    }

    if ($conversionChange <= -40) {
        $alerts[] = [
            'id' => 'conversions_drop_critical',
            'severity' => 'RED',
            'title' => 'Conversions dropped sharply',
            'message' => 'Conversions dropped by more than 40% versus the previous period.',
            'evidence' => ['changePercent' => round($conversionChange, 1)],
        ];
    } elseif ($conversionChange <= -20) {
        $alerts[] = [
            'id' => 'conversions_drop_warning',
            'severity' => 'AMBER',
            'title' => 'Conversions trending down',
            'message' => 'Conversions dropped by more than 20% versus the previous period.',
            'evidence' => ['changePercent' => round($conversionChange, 1)],
        ];
    }

    if ($current['engagementRate'] < 0.45) {
        $alerts[] = [
            'id' => 'engagement_low',
            'severity' => 'AMBER',
            'title' => 'Engagement rate is low',
            'message' => 'Engagement rate is below 45% for the selected period.',
            'evidence' => ['engagementRate' => round($current['engagementRate'] * 100, 1) . '%'],
        ];
    }

    if ($userChange <= -25) {
        $alerts[] = [
            'id' => 'active_users_drop',
            'severity' => 'AMBER',
            'title' => 'Active users dropped',
            'message' => 'Active users declined by more than 25% versus the previous period.',
            'evidence' => ['changePercent' => round($userChange, 1)],
        ];
    }

    if ($totalChannelSessions > 0 && !empty($topChannels)) {
        $topShare = ($topChannels[0]['sessions'] / $totalChannelSessions);
        if ($topShare >= 0.75) {
            $alerts[] = [
                'id' => 'channel_concentration',
                'severity' => 'AMBER',
                'title' => 'Traffic source concentration',
                'message' => 'A single channel is contributing over 75% of sessions.',
                'evidence' => [
                    'channel' => $topChannels[0]['channel'],
                    'sharePercent' => round($topShare * 100, 1) . '%',
                ],
            ];
        }
    }

    if (empty($alerts)) {
        $alerts[] = [
            'id' => 'all_clear',
            'severity' => 'PASS',
            'title' => 'No major anomalies detected',
            'message' => 'Current period is stable based on configured alert thresholds.',
            'evidence' => [
                'sessionChangePercent' => round($sessionChange, 1),
                'conversionChangePercent' => round($conversionChange, 1),
            ],
        ];
    }

    return [
        'alerts' => $alerts,
        'comparison' => [
            'current' => $current,
            'previous' => $previous,
            'sessionChangePercent' => round($sessionChange, 1),
            'activeUsersChangePercent' => round($userChange, 1),
            'conversionsChangePercent' => round($conversionChange, 1),
        ],
        'topChannels' => $topChannels,
    ];
}

$action = strValue($_GET['action'] ?? 'overview');
$days = intValueLocal($_GET['days'] ?? 30, 30);
$days = max(1, min(365, $days));
$source = normalizeDbSource((string) ($_GET['source'] ?? ($_POST['source'] ?? 'auto')));
$allowedActions = ['overview', 'acquisition', 'pages', 'audience', 'conversions', 'realtime', 'seo', 'alerts'];

if (!in_array($action, $allowedActions, true)) {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid analytics action.',
        'allowedActions' => $allowedActions,
    ], 400);
}

try {
    $conn = createDatabaseConnection($source);
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed.'], 500);
}

$account = findSessionAccount($conn);
if ($account === null) {
    $conn->close();
    jsonResponse(['success' => false, 'message' => 'Please sign in first.'], 401);
}

if (((int) ($account['is_active'] ?? 1)) !== 1) {
    $conn->close();
    jsonResponse(['success' => false, 'message' => 'This account is disabled.'], 403);
}

$role = normalizeRoleKey((string) ($account['role_key'] ?? ROLE_CARE_COORDINATOR));
$permissions = fetchPermissionsForRole($conn, $role);
$canView = $role === ROLE_DIRECTOR || !empty($permissions['dashboard.analytics']);
$conn->close();

if (!$canView) {
    jsonResponse(['success' => false, 'message' => 'Access denied for analytics dashboard.'], 403);
}

$gaConfig = loadGaCredentials();
$missing = missingConfigKeys($gaConfig);
if (!empty($missing)) {
    jsonResponse([
        'success' => true,
        'configured' => false,
        'message' => 'Google Analytics is not configured yet.',
        'missingConfig' => $missing,
        'hint' => 'Set GA4_SERVICE_ACCOUNT_FILE or create src/php/ga4.local.php with propertyId, clientEmail, and privateKey.',
    ]);
}

$propertyId = (string) $gaConfig['propertyId'];
$clientEmail = (string) $gaConfig['clientEmail'];
$privateKey = (string) $gaConfig['privateKey'];
$dateRange = [['startDate' => $days . 'daysAgo', 'endDate' => 'today']];

try {
    $accessToken = requestGoogleAccessToken($clientEmail, $privateKey);

    switch ($action) {
        case 'overview':
            $sectionPayload = buildOverviewSection($accessToken, $propertyId, $dateRange);
            break;
        case 'acquisition':
            $sectionPayload = buildAcquisitionSection($accessToken, $propertyId, $dateRange);
            break;
        case 'pages':
            $sectionPayload = buildPagesSection($accessToken, $propertyId, $dateRange);
            break;
        case 'audience':
            $sectionPayload = buildAudienceSection($accessToken, $propertyId, $dateRange);
            break;
        case 'conversions':
            $sectionPayload = buildConversionsSection($accessToken, $propertyId, $dateRange);
            break;
        case 'realtime':
            $sectionPayload = buildRealtimeSection($accessToken, $propertyId);
            break;
        case 'seo':
            $sectionPayload = buildSeoSection($accessToken, $propertyId, $dateRange);
            break;
        case 'alerts':
            $sectionPayload = buildAlertsSection($accessToken, $propertyId, $dateRange, $days);
            break;
        default:
            $sectionPayload = [];
            break;
    }
} catch (Throwable $exception) {
    $detail = strValue($exception->getMessage());
    jsonResponse([
        'success' => false,
        'configured' => true,
        'message' => mapGaFetchErrorMessage($detail),
        'detail' => $detail,
    ], 502);
}

$baseResponse = [
    'success' => true,
    'configured' => true,
    'fetchedAt' => gmdate('c'),
    'days' => $days,
    'section' => $action,
    'data' => $sectionPayload,
];

if ($action === 'overview') {
    jsonResponse(array_merge($baseResponse, $sectionPayload));
}

jsonResponse($baseResponse);
