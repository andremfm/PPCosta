<?php
declare(strict_types=1);

const APP_NAME = 'PPCosta';
const APP_ENV = 'development';
const APP_URL = 'http://localhost:8000';

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'ppcosta_store';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

const UPLOAD_DIR = __DIR__ . '/../uploads';
const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;

date_default_timezone_set('Europe/Lisbon');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
