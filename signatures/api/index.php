<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function jsonOut(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function requireAdmin(): void {
    $sent = $_SERVER['HTTP_X_ADMIN_PASSWORD'] ?? ($_GET['password'] ?? '');
    if (!is_string($sent) || !hash_equals(ADMIN_PASSWORD, $sent)) {
        jsonOut(['error' => 'كلمة السر غير صحيحة.'], 401);
    }
}

/** Coupe une chaîne UTF-8 sans dépendre de l'extension mbstring. */
function cutText(string $value, int $maxLength): string {
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }
    $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return implode('', array_slice($chars, 0, $maxLength));
}

function cleanText(?string $value, int $maxLength): string {
    $value = trim((string) $value);
    $value = preg_replace('/\s+/u', ' ', $value);
    return cutText($value, $maxLength);
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDB();

    if ($action === 'count') {
        $count = (int) $pdo->query('SELECT COUNT(*) AS c FROM signatures')->fetch()['c'];
        jsonOut(['count' => $count]);
    }

    if ($action === 'submit') {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            jsonOut(['error' => 'طريقة غير مسموحة.'], 405);
        }
        $body = jsonBody();
        $firstName = cleanText($body['first_name'] ?? '', 80);
        $lastName = cleanText($body['last_name'] ?? '', 80);
        $cin = strtoupper(cleanText($body['cin'] ?? '', 20));
        $cin = str_replace(' ', '', $cin);
        $phone = cleanText($body['phone'] ?? '', 20);
        $signature = (string) ($body['signature'] ?? '');

        if ($firstName === '' || $lastName === '' || $cin === '') {
            jsonOut(['error' => 'المرجو تعبئة الاسم والنسب ورقم البطاقة الوطنية.'], 422);
        }
        $arabicOnly = '/^[\x{0621}-\x{063A}\x{0641}-\x{065F}\x{066E}-\x{06D3}\x{06FA}-\x{06FF}\s\'\x{2019}-]+$/u';
        if (!preg_match($arabicOnly, $firstName) || !preg_match($arabicOnly, $lastName)) {
            jsonOut(['error' => 'المرجو كتابة الاسم والنسب بالحروف العربية فقط.'], 422);
        }
        if (!preg_match('/^[A-Z0-9]{4,20}$/', $cin)) {
            jsonOut(['error' => 'رقم البطاقة الوطنية غير صحيح (مثال: JB123456).'], 422);
        }
        if (!preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#', $signature)) {
            jsonOut(['error' => 'التوقيع غير صالح، المرجو إعادة التوقيع.'], 422);
        }
        if (strlen($signature) > MAX_SIGNATURE_BYTES) {
            jsonOut(['error' => 'حجم التوقيع كبير جدًا، المرجو إعادة التوقيع.'], 413);
        }
        if (UNIQUE_CIN) {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM signatures WHERE cin = ?');
            $stmt->execute([$cin]);
            if ((int) $stmt->fetch()['c'] > 0) {
                jsonOut(['error' => 'تم تسجيل توقيع بهذا الرقم الوطني من قبل.'], 409);
            }
        }

        $stmt = $pdo->prepare('INSERT INTO signatures
            (first_name, last_name, cin, phone, signature, ip, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $firstName,
            $lastName,
            $cin,
            $phone,
            $signature,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            date('c'),
        ]);

        jsonOut(['ok' => true, 'id' => (int) $pdo->lastInsertId()], 201);
    }

    if ($action === 'list') {
        requireAdmin();
        $rows = $pdo->query('SELECT id, first_name, last_name, cin, phone, signature, created_at
                             FROM signatures ORDER BY id ASC')->fetchAll();
        jsonOut(['signatures' => $rows]);
    }

    if ($action === 'delete') {
        requireAdmin();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            jsonOut(['error' => 'طريقة غير مسموحة.'], 405);
        }
        $id = (int) (jsonBody()['id'] ?? 0);
        if ($id <= 0) {
            jsonOut(['error' => 'معرّف غير صحيح.'], 422);
        }
        $stmt = $pdo->prepare('DELETE FROM signatures WHERE id = ?');
        $stmt->execute([$id]);
        jsonOut(['ok' => true, 'deleted' => $stmt->rowCount()]);
    }

    jsonOut(['error' => 'طلب غير معروف.'], 404);
} catch (Throwable $error) {
    error_log('[signatures] ' . $error->getMessage());
    jsonOut(['error' => 'خطأ في الخادم، المرجو المحاولة مرة أخرى.'], 500);
}
