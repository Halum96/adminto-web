<?php
/**
 * Adminto SaaS - High-Performance Database Connection Bridge
 * -----------------------------------------------------------
 * Optimized server-side proxy to bypass client-side CORS blocks, adblockers, and SSL negotiation errors.
 * Dual-engine architecture (cURL + Stream Context) with tuned connection timeouts and no-cache headers.
 */

// Prevent stale HTTP response caching for real-time telemetry
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 1. Sanitize & Decode Target URL
$url = trim($_GET['url'] ?? '');
$encUrl = trim($_GET['enc_url'] ?? '');

if (!empty($encUrl)) {
    $decoded = base64_decode($encUrl, true);
    if ($decoded !== false) {
        $url = trim($decoded);
    }
}

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL parameter required (raw or base64 encoded)']);
    exit;
}

// 2. Strict Security Domain Filter (*.firebasedatabase.app or *.firebaseio.com)
if (!preg_match('/^https?:\/\/[a-zA-Z0-9\-\.]+\.(firebasedatabase\.app|firebaseio\.com)/i', $url)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Only valid Firebase Realtime Database domains are permitted. Received URL: ' . $url]);
    exit;
}

// 3. Normalize JSON Endpoint Path
if (strpos($url, '.json') === false) {
    if (substr($url, -1) === '/') {
        $url = substr($url, 0, -1);
    }
    $url .= '/.json';
}

// 4. Primary Engine: Fast cURL with Tuned Connection Timeouts
$curlError = null;

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 4,  // Fast 4s connect timeout
        CURLOPT_TIMEOUT        => 8,  // 8s total timeout
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'User-Agent: Adminto-DBBridge/2.0'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if (!$curlError && $httpCode >= 200 && $httpCode < 400) {
        echo $response;
        exit;
    }
}

// 5. Secondary Fail-safe Engine: PHP Stream Context Fallback
$opts = [
    'http' => [
        'method'        => 'GET',
        'header'        => "Accept: application/json\r\nUser-Agent: Adminto-DBBridge/2.0\r\n",
        'timeout'       => 8,
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false
    ]
];

$context = stream_context_create($opts);
$response = @file_get_contents($url, false, $context);

if ($response !== false) {
    echo $response;
    exit;
}

// 6. Gateway Error Diagnostics
http_response_code(502);
echo json_encode([
    'error'      => 'Gateway Error: Failed to reach Firebase server from backend.',
    'url'        => $url,
    'curl_error' => $curlError ?? 'cURL not available'
]);
