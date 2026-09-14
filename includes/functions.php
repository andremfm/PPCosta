<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function asset(string $path): string
{
    return APP_URL . '/assets/' . ltrim($path, '/');
}

function versioned_asset(string $path): string
{
    $relativePath = ltrim($path, '/');
    $absolutePath = __DIR__ . '/../assets/' . $relativePath;
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : '1';

    return asset($relativePath) . '?v=' . rawurlencode($version);
}

function url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

function product_url(string $slug): string
{
    return url('produto/' . rawurlencode($slug));
}

function category_url(string $slug): string
{
    return url('categoria/' . rawurlencode($slug));
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

function format_price(float $value): string
{
    return number_format($value, 2, ',', ' ') . ' EUR';
}

function customer_session_key(string $suffix): string
{
    return 'customer_' . (int) ($_SESSION['user_id'] ?? 0) . '_' . $suffix;
}

function current_page(): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
}

function is_active(string $page): string
{
    return current_page() === $page ? 'active' : '';
}

function redirect(string $path = '')
{
    header('Location: ' . url($path));
    exit;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function old(string $key, string $default = ''): string
{
    return $_SESSION['_old'][$key] ?? $default;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consume_flash(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return $messages;
}

function set_old(array $values): void
{
    $_SESSION['_old'] = $values;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function rate_limit_allow(string $key, int $maxAttempts = 8, int $windowSeconds = 900): bool
{
    $now = time();
    $bucketKey = 'rate_limit_' . hash('sha256', $key);
    $bucket = $_SESSION[$bucketKey] ?? ['count' => 0, 'reset_at' => $now + $windowSeconds];

    if (($bucket['reset_at'] ?? 0) <= $now) {
        $bucket = ['count' => 0, 'reset_at' => $now + $windowSeconds];
    }

    $bucket['count']++;
    $_SESSION[$bucketKey] = $bucket;

    return $bucket['count'] <= $maxAttempts;
}

function rate_limit_clear(string $key): void
{
    unset($_SESSION['rate_limit_' . hash('sha256', $key)]);
}
