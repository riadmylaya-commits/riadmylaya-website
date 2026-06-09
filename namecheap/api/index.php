<?php
// ─── Riad Mylaya API Router ──────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/email.php';

// Initialize database tables on first request
initDatabase();

// Parse the request path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
// Remove /api prefix if present
$path = preg_replace('#^/api#', '', $path);
$method = $_SERVER['REQUEST_METHOD'];

// Get JSON body
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$pdo = getDB();

// ─── Routing ─────────────────────────────────────────────────────────────────

try {
    // Health check
    if ($path === '/health' && $method === 'GET') {
        echo json_encode(['status' => 'ok']);
        exit;
    }

    // ─── Registration routes ────────────────────────────────────────────────

    if ($path === '/registrations' && $method === 'POST') {
        handleCreateRegistration($pdo, $body);
    }
    elseif ($path === '/registrations' && $method === 'GET') {
        $user = requireAuth($pdo);
        handleListRegistrations($pdo);
    }
    elseif ($path === '/registrations/stats/today' && $method === 'GET') {
        $user = requireAuth($pdo);
        handleStats($pdo);
    }
    elseif (preg_match('#^/registrations/([a-f0-9\-]+)$#', $path, $m) && $method === 'GET') {
        $user = requireAuth($pdo);
        handleGetRegistration($pdo, $m[1]);
    }
    elseif (preg_match('#^/registrations/([a-f0-9\-]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth($pdo);
        handleDeleteRegistration($pdo, $m[1]);
    }

    // ─── Auth routes ────────────────────────────────────────────────────────

    elseif ($path === '/auth/login' && $method === 'POST') {
        handleLogin($pdo, $body);
    }
    elseif ($path === '/auth/me' && $method === 'GET') {
        $user = requireAuth($pdo);
        echo json_encode(formatUser($user));
    }
    elseif ($path === '/auth/change-password' && $method === 'POST') {
        $user = requireAuth($pdo);
        handleChangePassword($pdo, $user, $body);
    }
    elseif ($path === '/auth/forgot-password' && $method === 'POST') {
        handleForgotPassword($pdo, $body);
    }
    elseif ($path === '/auth/reset-password' && $method === 'POST') {
        handleResetPassword($pdo, $body);
    }
    elseif ($path === '/auth/staff' && $method === 'POST') {
        $admin = requireAdmin($pdo);
        handleCreateStaff($pdo, $body);
    }
    elseif ($path === '/auth/staff' && $method === 'GET') {
        $admin = requireAdmin($pdo);
        handleListStaff($pdo);
    }
    elseif (preg_match('#^/auth/staff/([a-f0-9\-]+)$#', $path, $m) && $method === 'DELETE') {
        $admin = requireAdmin($pdo);
        handleDeleteStaff($pdo, $admin, $m[1]);
    }
    else {
        http_response_code(404);
        echo json_encode(['detail' => 'Not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['detail' => 'Database error']);
    error_log("DB Error: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['detail' => 'Internal server error']);
    error_log("Error: " . $e->getMessage());
}

// ─── Registration Handlers ───────────────────────────────────────────────────

function handleCreateRegistration(PDO $pdo, array $body): void {
    $id = generateUUID();
    $stmt = $pdo->prepare("INSERT INTO registrations 
        (id, room, last_name, first_name, date_of_birth, place_of_birth, nationality, 
         occupation, cin_number, morocco_entry_number, arrival_date, departure_date, 
         accompanying_children, coming_from, going_to, passport_number, passport_issue_date, 
         passport_issue_place, permanent_address, passport_photo, signature, registration_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $id,
        $body['room'] ?? '',
        $body['lastName'] ?? '',
        $body['firstName'] ?? '',
        $body['dateOfBirth'] ?? '',
        $body['placeOfBirth'] ?? '',
        $body['nationality'] ?? '',
        $body['occupation'] ?? '',
        $body['cinNumber'] ?? '',
        $body['moroccoEntryNumber'] ?? '',
        $body['arrivalDate'] ?? '',
        $body['departureDate'] ?? '',
        (int)($body['accompanyingChildren'] ?? 0),
        $body['comingFrom'] ?? '',
        $body['goingTo'] ?? '',
        $body['passportNumber'] ?? '',
        $body['passportIssueDate'] ?? '',
        $body['passportIssuePlace'] ?? '',
        $body['permanentAddress'] ?? '',
        $body['passportPhoto'] ?? '',
        $body['signature'] ?? '',
        $body['registrationDate'] ?? '',
    ]);

    // Send email notification in background (non-blocking on error)
    $regData = $body;
    $regData['id'] = $id;
    sendRegistrationEmail($regData);

    // Return the created registration
    $stmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ?");
    $stmt->execute([$id]);
    $reg = $stmt->fetch();

    http_response_code(201);
    echo json_encode(formatRegistration($reg));
}

function handleListRegistrations(PDO $pdo): void {
    $search = $_GET['search'] ?? '';
    $date = $_GET['date'] ?? '';

    $sql = "SELECT * FROM registrations WHERE 1=1";
    $params = [];

    if ($search) {
        $sql .= " AND (LOWER(first_name) LIKE ? OR LOWER(last_name) LIKE ?)";
        $like = '%' . strtolower($search) . '%';
        $params[] = $like;
        $params[] = $like;
    }
    if ($date) {
        $sql .= " AND arrival_date = ?";
        $params[] = $date;
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $results = array_map('formatRegistration', $stmt->fetchAll());
    echo json_encode($results);
}

function handleStats(PDO $pdo): void {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM registrations WHERE arrival_date = ?");
    $stmt->execute([$today]);
    $todayCount = (int)$stmt->fetch()['cnt'];

    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM registrations");
    $totalCount = (int)$stmt->fetch()['cnt'];

    echo json_encode([
        'todayArrivals' => $todayCount,
        'totalRegistrations' => $totalCount,
    ]);
}

function handleGetRegistration(PDO $pdo, string $id): void {
    $stmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ?");
    $stmt->execute([$id]);
    $reg = $stmt->fetch();

    if (!$reg) {
        http_response_code(404);
        echo json_encode(['detail' => 'Not found']);
        return;
    }
    echo json_encode(formatRegistration($reg));
}

function handleDeleteRegistration(PDO $pdo, string $id): void {
    $stmt = $pdo->prepare("SELECT id FROM registrations WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['detail' => 'Not found']);
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM registrations WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['message' => 'Deleted']);
}

// ─── Auth Handlers ───────────────────────────────────────────────────────────

function handleLogin(PDO $pdo, array $body): void {
    $username = $body['username'] ?? '';
    $password = $body['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM staff_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['hashed_password'])) {
        http_response_code(401);
        echo json_encode(['detail' => 'Incorrect username or password']);
        return;
    }
    if (!$user['is_active']) {
        http_response_code(403);
        echo json_encode(['detail' => 'Account deactivated']);
        return;
    }

    $token = createJWT(['sub' => $user['id']]);
    echo json_encode([
        'access_token' => $token,
        'token_type' => 'bearer',
        'user' => formatUser($user),
    ]);
}

function handleChangePassword(PDO $pdo, array $user, array $body): void {
    $current = $body['current_password'] ?? '';
    $new = $body['new_password'] ?? '';

    if (!password_verify($current, $user['hashed_password'])) {
        http_response_code(400);
        echo json_encode(['detail' => 'Current password is incorrect']);
        return;
    }

    $hash = password_hash($new, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE staff_users SET hashed_password = ? WHERE id = ?");
    $stmt->execute([$hash, $user['id']]);
    echo json_encode(['message' => 'Password changed']);
}

function handleForgotPassword(PDO $pdo, array $body): void {
    $email = $body['email'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM staff_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $id = generateUUID();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (id, user_id, token, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $user['id'], $token, $expiresAt]);

        $resetUrl = SITE_URL . "/reset-password?token=$token";
        sendPasswordResetEmail($email, $user['username'], $resetUrl);
    }

    echo json_encode(['message' => 'If the email exists, a reset link has been sent']);
}

function handleResetPassword(PDO $pdo, array $body): void {
    $token = $body['token'] ?? '';
    $newPassword = $body['new_password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0");
    $stmt->execute([$token]);
    $rt = $stmt->fetch();

    if (!$rt) {
        http_response_code(400);
        echo json_encode(['detail' => 'Invalid or expired reset token']);
        return;
    }

    if (strtotime($rt['expires_at']) < time()) {
        http_response_code(400);
        echo json_encode(['detail' => 'Reset token has expired']);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM staff_users WHERE id = ?");
    $stmt->execute([$rt['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(400);
        echo json_encode(['detail' => 'User not found']);
        return;
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE staff_users SET hashed_password = ? WHERE id = ?");
    $stmt->execute([$hash, $user['id']]);

    $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
    $stmt->execute([$rt['id']]);

    echo json_encode(['message' => 'Password reset successfully']);
}

function handleCreateStaff(PDO $pdo, array $body): void {
    $username = $body['username'] ?? '';
    $email = $body['email'] ?? '';
    $password = $body['password'] ?? '';
    $isAdmin = (bool)($body['is_admin'] ?? false);

    $stmt = $pdo->prepare("SELECT id FROM staff_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['detail' => 'Username or email already exists']);
        return;
    }

    $id = generateUUID();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO staff_users (id, username, email, hashed_password, is_admin) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$id, $username, $email, $hash, $isAdmin ? 1 : 0]);

    $stmt = $pdo->prepare("SELECT * FROM staff_users WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(formatUser($stmt->fetch()));
}

function handleListStaff(PDO $pdo): void {
    $stmt = $pdo->query("SELECT * FROM staff_users");
    $users = array_map('formatUser', $stmt->fetchAll());
    echo json_encode($users);
}

function handleDeleteStaff(PDO $pdo, array $admin, string $uid): void {
    if ($uid === $admin['id']) {
        http_response_code(400);
        echo json_encode(['detail' => 'Cannot delete yourself']);
        return;
    }

    $stmt = $pdo->prepare("SELECT id FROM staff_users WHERE id = ?");
    $stmt->execute([$uid]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['detail' => 'Not found']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM staff_users WHERE id = ?");
    $stmt->execute([$uid]);
    echo json_encode(['message' => 'Deleted']);
}

// ─── Formatters ──────────────────────────────────────────────────────────────

function formatRegistration(array $row): array {
    return [
        'id' => $row['id'],
        'room' => $row['room'],
        'lastName' => $row['last_name'],
        'firstName' => $row['first_name'],
        'dateOfBirth' => $row['date_of_birth'],
        'placeOfBirth' => $row['place_of_birth'],
        'nationality' => $row['nationality'],
        'occupation' => $row['occupation'],
        'cinNumber' => $row['cin_number'],
        'moroccoEntryNumber' => $row['morocco_entry_number'],
        'arrivalDate' => $row['arrival_date'],
        'departureDate' => $row['departure_date'],
        'accompanyingChildren' => (int)$row['accompanying_children'],
        'comingFrom' => $row['coming_from'],
        'goingTo' => $row['going_to'],
        'passportNumber' => $row['passport_number'],
        'passportIssueDate' => $row['passport_issue_date'],
        'passportIssuePlace' => $row['passport_issue_place'],
        'permanentAddress' => $row['permanent_address'],
        'passportPhoto' => $row['passport_photo'] ?? '',
        'signature' => $row['signature'] ?? '',
        'registrationDate' => $row['registration_date'],
        'createdAt' => $row['created_at'] ?? '',
    ];
}

function formatUser(array $row): array {
    return [
        'id' => $row['id'],
        'username' => $row['username'],
        'email' => $row['email'],
        'is_admin' => (bool)$row['is_admin'],
        'is_active' => (bool)$row['is_active'],
    ];
}
