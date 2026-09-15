<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_auth();
$stmt = db()->prepare('SELECT p.file_path, o.user_id FROM order_item_personalizations p INNER JOIN order_items i ON i.id = p.order_item_id INNER JOIN orders o ON o.id = i.order_id WHERE p.id = :id');
$stmt->execute(['id' => (int) ($_GET['id'] ?? 0)]);
$file = $stmt->fetch();
if (!$file || (!has_role('admin') && (int) $file['user_id'] !== (int) $_SESSION['user_id'])
    || !preg_match('#^uploads/personalizations/[a-f0-9]{32}\.(png|jpg|jpeg|svg|pdf)$#', (string) $file['file_path'])) {
    http_response_code(404);
    exit('Ficheiro nao encontrado.');
}
$path = UPLOAD_DIR . '/personalizations/' . basename($file['file_path']);
if (!is_file($path)) {
    http_response_code(404);
    exit('Ficheiro nao encontrado.');
}
header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: sandbox; default-src 'none'");
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-store');
readfile($path);
