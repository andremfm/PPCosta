<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => 'ok',
    'app' => APP_NAME,
    'environment' => APP_ENV,
    'database' => db_available() ? 'ok' : 'unavailable',
    'uploads_writable' => is_writable(UPLOAD_DIR),
    'php_version' => PHP_VERSION,
    'time' => date(DATE_ATOM),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
