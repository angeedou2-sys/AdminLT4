<?php
function jwtSecret(): string {
    return $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: 'change_me_in_env';
}

function jwtEncode(array $payload): string {
    $header  = rtrim(strtr(base64_encode('{"alg":"HS256","typ":"JWT"}'), '+/', '-_'), '=');
    $payload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    $sig     = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$payload", jwtSecret(), true)), '+/', '-_'), '=');
    return "$header.$payload.$sig";
}

function jwtDecode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $payload, $sig] = $parts;
    $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$payload", jwtSecret(), true)), '+/', '-_'), '=');
    if (!hash_equals($expected, $sig)) return null;
    $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    if (!$data || (isset($data['exp']) && $data['exp'] < time())) return null;
    return $data;
}

function setAuthCookie(array $user): void {
    $token = jwtEncode([
        'sub'   => $user['id'],
        'email' => $user['email'],
        'exp'   => time() + 86400 * 7,
    ]);
    setcookie('auth_token', $token, [
        'expires'  => time() + 86400 * 7,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

function getAuthUser(): ?array {
    $token = $_COOKIE['auth_token'] ?? null;
    if (!$token) return null;
    return jwtDecode($token);
}

function requireAuth(): array {
    $user = getAuthUser();
    if (!$user) {
        header('Location: /api/login.php');
        exit;
    }
    return $user;
}
