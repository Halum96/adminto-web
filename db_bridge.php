<?php
/**
 * Adminto SaaS - Database Connection Helper Bridge
 * ------------------------------------------------
 * Safe server-side proxy to bypass client-side CORS blocks, adblockers, and SSL negotiation errors.
 * Supports both cURL and stream wrappers (file_get_contents) for 100% server compatibility.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$url = $_GET['url'] ?? '';
$encUrl = $_GET['enc_url'] ?? '';

if (!empty($encUrl)) {
    $url = base64_decode($encUrl);
}

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL parameter required (raw or base64 encoded)']);
    exit;
}

// Security constraint: Only allow valid Firebase Realtime Database URLs (*.firebasedatabase.app or *.firebaseio.com)
if (!preg_match('/^https?:\/\/[a-zA-Z0-9\-\.]+\.(firebasedatabase\.app|firebaseio\.com)/i', $url)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Only valid Firebase Realtime Database domains (*.firebasedatabase.app or *.firebaseio.com) are permitted. Received URL: ' . $url]);
    exit;
}

// Normalize URL: append /.json if not present
if (strpos($url, '.json') === false) {
    if (substr($url, -1) === '/') {
        $url = substr($url, 0, -1);
    }
    $url .= '/.json';
}

// Method 1: cURL (Preferred)
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'User-Agent: Adminto-DBBridge/1.0'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if (!$curl_err && $http_code >= 200 && $http_code < 400) {
        echo $response;
        exit;
    }
}

// Method 2: file_get_contents fallback if cURL fails or is disabled
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\nUser-Agent: Adminto-DBBridge/1.0\r\n",
        'timeout' => 12,
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
];

$context = stream_context_create($opts);
$response = @file_get_contents($url, false, $context);

if ($response !== false) {
    echo $response;
    exit;
}

http_response_code(502);
echo json_encode([
    'error' => 'Gateway Error: Failed to reach Firebase server from backend.',
    'url' => $url,
    'curl_error' => $curl_err ?? 'cURL not available'
]);
