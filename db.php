<?php
/**
 * Adminto SaaS - Shared Database Connection Helper (PDO MySQL)
 * ─────────────────────────────────────────────────────────────
 * Include this file to get a $pdo connection object.
 * Does NOT send any HTTP headers — safe to include from any context.
 * Returns $pdo = null silently if DB is unavailable.
 */

$_db_host   = getenv('DB_HOST')     ?: 'localhost';
$_db_dbname = getenv('DB_NAME')     ?: 'adminto_saas_db';
$_db_user   = getenv('DB_USER')     ?: 'root';
$_db_pass   = getenv('DB_PASSWORD') ?: 'admin123';
$_db_port   = getenv('DB_PORT')     ?: '3306';

try {
    $dsn = "mysql:host={$_db_host};port={$_db_port};dbname={$_db_dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $_db_user, $_db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false, // Fresh connection per request (safe for web)
    ]);
} catch (PDOException $e) {
    $pdo = null;
    $db_error = $e->getMessage();
}

// Clean up connection variables from global scope
unset($_db_host, $_db_dbname, $_db_user, $_db_pass, $_db_port, $dsn);
