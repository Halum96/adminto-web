<?php
/**
 * Adminto SaaS - Operator MySQL Login & License Validation API
 */

// Include DB connection
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
    // Fallback response if DB is offline
    echo json_encode(['success' => false, 'error' => 'Database Offline: ' . $e->getMessage()]);
    exit;
}

// Read raw JSON input
$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? $_POST['username'] ?? '');
$password = trim($input['password'] ?? $_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username and password required.']);
    exit;
}

try {
    // Query operator by username or email
    $stmt = $pdo->prepare('SELECT * FROM operators WHERE LOWER(username) = LOWER(:u) OR LOWER(email) = LOWER(:u) LIMIT 1');
    $stmt->execute(['u' => $username]);
    $operator = $stmt->fetch();

    if (!$operator) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid credentials! Operator account not found.']);
        exit;
    }

    // Password check (plain match or bcrypt verification)
    $passValid = false;
    if ($operator['password_hash'] === $password) {
        $passValid = true;
    } elseif (password_verify($password, $operator['password_hash'])) {
        $passValid = true;
    }

    if (!$passValid) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid password! Access denied.']);
        exit;
    }

    // Check account active status
    if (isset($operator['is_active']) && !$operator['is_active']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Account disabled by Super Admin.']);
        exit;
    }

    // Check account expiration date
    $today = date('Y-m-d');
    $expiryDate = date('Y-m-d', strtotime($operator['expiry_date']));

    if ($expiryDate < $today) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => "Account Expired on {$expiryDate}. Contact Super Admin to extend access."]);
        exit;
    }

    // Success response with assigned Firebase parameters
    echo json_encode([
        'success' => true,
        'operator' => [
            'id' => (string)$operator['id'],
            'username' => $operator['username'],
            'email' => $operator['email'],
            'fullName' => $operator['full_name'],
            'role' => $operator['role'],
            'expiryDate' => $expiryDate,
            'firebaseConfig' => [
                'projectId' => $operator['firebase_project_id'],
                'apiKey' => $operator['firebase_api_key'] ?? 'AIzaSyDefaultKey',
                'authDomain' => $operator['firebase_auth_domain'] ?? "{$operator['firebase_project_id']}.firebaseapp.com",
                'storageBucket' => $operator['storage_bucket'] ?? "{$operator['firebase_project_id']}.appspot.com",
                'appId' => $operator['app_id'] ?? '1:109823746501:web:default',
                'orgId' => $operator['org_id']
            ]
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Query error: ' . $e->getMessage()]);
}
