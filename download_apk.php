<?php
/**
 * Adminto SaaS - APK Download Server Endpoint
 * Serves the compiled 12.2 MB Android APK (app-debug.apk) directly
 */

$apkFile = __DIR__ . '/app-debug.apk';

if (!file_exists($apkFile)) {
    http_response_code(404);
    echo "APK file not found on server.";
    exit;
}

$proj = $_GET['project'] ?? 'adminto-operator';
$downloadFileName = "Adminto-Operator-{$proj}.apk";

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $downloadFileName . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($apkFile));

readfile($apkFile);
exit;
