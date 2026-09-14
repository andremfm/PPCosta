<?php
declare(strict_types=1);

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);

    return $value === false ? $default : $value;
}

define('APP_NAME', env_value('APP_NAME', 'PPCosta'));
define('APP_ENV', env_value('APP_ENV', 'development'));
define('APP_URL', rtrim(env_value('APP_URL', 'http://localhost:8000'), '/'));
define('APP_DEBUG', env_value('APP_DEBUG', 'false') === 'true');

define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) env_value('DB_PORT', '3306'));
define('DB_NAME', env_value('DB_NAME', 'ppcosta_store'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS', ''));
define('DB_CHARSET', env_value('DB_CHARSET', 'utf8mb4'));

define('UPLOAD_DIR', env_value('UPLOAD_DIR', __DIR__ . '/../uploads'));
define('MAX_UPLOAD_BYTES', (int) env_value('MAX_UPLOAD_BYTES', (string) (10 * 1024 * 1024)));

date_default_timezone_set(env_value('APP_TIMEZONE', 'Europe/Lisbon'));

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => APP_ENV === 'production' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
