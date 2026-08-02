<?php
/**
 * Adminto SaaS - Firebase RTDB CORS Proxy
 * ---------------------------------------
 * Safe server-side proxy to bypass client-side CORS blocks, adblockers, and SSL negotiation errors.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$url = $_GET['url'] ?? '';
if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL parameter required']);
    exit;
}

// Security constraint: Only allow valid Firebase Realtime Database URLs
if (!preg_match('/^https?:\/\/[a-zA-Z0-9\-\.]+\.firebasedatabase\.app/i', $url)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Only Firebase Realtime Database domains are permitted.']);
    exit;
}

// Normalize URL: append /.json if not present
if (strpos($url, '.json') === false) {
    if (substr($url, -1) === '/') {
        $url = substr($url, 0, -1);
    }
    $url .= '/.json';
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 12);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Gateway Error: Failed to reach Firebase',
        'details' => curl_error($ch)
    ]);
} else if ($http_code >= 400) {
    http_response_code($http_code);
    echo $response;
} else {
    echo $response;
}
curl_close($ch);
