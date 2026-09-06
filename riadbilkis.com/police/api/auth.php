<?php
require_once __DIR__ . '/config.php';

// ─── Simple JWT implementation ───────────────────────────────────────────────

function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/'));
}

function createJWT(array $payload): string {
    $header = base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['exp'] = time() + (JWT_EXPIRY_HOURS * 3600);
    $payload['iat'] = time();
    $body = base64UrlEncode(json_encode($payload));
    $signature = base64UrlEncode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$signature";
}

function verifyJWT(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $body, $signature] = $parts;
    $expectedSig = base64UrlEncode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));

    if (!hash_equals($expectedSig, $signature)) return null;

    $payload = json_decode(base64UrlDecode($body), true);
    if (!$payload || !isset($payload['exp'])) return null;
    if ($payload['exp'] < time()) return null;

    return $payload;
}

function getCurrentUser(PDO $pdo): ?array {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) return null;

    $payload = verifyJWT($matches[1]);
    if (!$payload || !isset($payload['sub'])) return null;

    $stmt = $pdo->prepare("SELECT * FROM staff_users WHERE id = ? AND is_active = 1");
    $stmt->execute([$payload['sub']]);
    return $stmt->fetch() ?: null;
}

function requireAuth(PDO $pdo): array {
    $user = getCurrentUser($pdo);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['detail' => 'Could not validate credentials']);
        exit;
    }
    return $user;
}

function requireAdmin(PDO $pdo): array {
    $user = requireAuth($pdo);
    if (!$user['is_admin']) {
        http_response_code(403);
        echo json_encode(['detail' => 'Admin access required']);
        exit;
    }
    return $user;
}
