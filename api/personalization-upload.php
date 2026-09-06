<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/personalization.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'Nenhum ficheiro recebido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['file'];
$mime = mime_content_type($file['tmp_name']) ?: '';

if ($file['size'] > MAX_UPLOAD_BYTES || !in_array($mime, personalization_allowed_mime_types(), true)) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'Ficheiro invalido. Usa PNG, SVG, PDF ou JPG dentro do limite permitido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$targetDir = UPLOAD_DIR . '/personalizations';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$filename = bin2hex(random_bytes(16)) . '.' . $extension;
$targetPath = $targetDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Nao foi possivel guardar o ficheiro.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'status' => 'ok',
    'path' => 'uploads/personalizations/' . $filename,
    'message' => 'Ficheiro carregado com sucesso.',
], JSON_UNESCAPED_UNICODE);
