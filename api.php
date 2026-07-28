<?php
/**
 * Adminto SaaS - Super Admin MySQL REST API
 * Supports CRUD operations for Operator Accounts & Database Projects
 */

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'adminto_saas_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
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

switch ($action) {
    // 1. GET ALL OPERATORS
    case 'get_operators':
        try {
            $stmt = $pdo->query("SELECT id, username, email, full_name as fullName, role, expiry_date as expiryDate, is_active as isActive, firebase_project_id as firebaseProject, firebase_api_key as firebaseApiKey, firebase_auth_domain as firebaseAuthDomain, storage_bucket as storageBucket, app_id as appId, org_id as orgId FROM operators ORDER BY created_at DESC");
            $operators = $stmt->fetchAll();
            echo json_encode(['success' => true, 'operators' => $operators]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // 2. ADD NEW OPERATOR
    case 'add_operator':
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $fullName = trim($input['fullName'] ?? $username);
        $role = trim($input['role'] ?? 'operator');
        $expiryDate = trim($input['expiryDate'] ?? '2026-12-31');
        $firebaseProject = trim($input['firebaseProject'] ?? 'adminto-op-custom');
        $firebaseApiKey = trim($input['firebaseApiKey'] ?? '');
        $firebaseAuthDomain = trim($input['firebaseAuthDomain'] ?? '');
        $storageBucket = trim($input['storageBucket'] ?? '');
        $appId = trim($input['appId'] ?? '');
        $orgId = trim($input['orgId'] ?? 'org_custom');

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Username and password required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO operators (username, email, password_hash, full_name, role, expiry_date, firebase_project_id, firebase_api_key, firebase_auth_domain, storage_bucket, app_id, org_id) VALUES (:u, :e, :p, :f, :r, :ex, :fp, :ak, :ad, :sb, :ai, :o)");
            $stmt->execute([
                'u' => $username,
                'e' => $username . '@adminto.com',
                'p' => $password,
                'f' => $fullName,
                'r' => $role,
                'ex' => $expiryDate,
                'fp' => $firebaseProject,
                'ak' => $firebaseApiKey,
                'ad' => $firebaseAuthDomain,
                'sb' => $storageBucket,
                'ai' => $appId,
                'o' => $orgId
            ]);
            echo json_encode(['success' => true, 'message' => 'Operator created successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // 2B. UPDATE FIREBASE CONFIG
    case 'update_firebase_config':
        $id = $input['id'] ?? null;
        $username = trim($input['username'] ?? '');
        $firebaseProject = trim($input['firebaseProject'] ?? '');
        $firebaseApiKey = trim($input['firebaseApiKey'] ?? '');
        $firebaseAuthDomain = trim($input['firebaseAuthDomain'] ?? '');
        $storageBucket = trim($input['storageBucket'] ?? '');
        $appId = trim($input['appId'] ?? '');

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE operators SET firebase_project_id = :fp, firebase_api_key = :ak, firebase_auth_domain = :ad, storage_bucket = :sb, app_id = :ai WHERE id = :id");
                $stmt->execute(['fp' => $firebaseProject, 'ak' => $firebaseApiKey, 'ad' => $firebaseAuthDomain, 'sb' => $storageBucket, 'ai' => $appId, 'id' => $id]);
            } else if ($username) {
                $stmt = $pdo->prepare("UPDATE operators SET firebase_project_id = :fp, firebase_api_key = :ak, firebase_auth_domain = :ad, storage_bucket = :sb, app_id = :ai WHERE LOWER(username) = LOWER(:u)");
                $stmt->execute(['fp' => $firebaseProject, 'ak' => $firebaseApiKey, 'ad' => $firebaseAuthDomain, 'sb' => $storageBucket, 'ai' => $appId, 'u' => $username]);
            }
            echo json_encode(['success' => true, 'message' => 'Firebase credentials updated']);
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
