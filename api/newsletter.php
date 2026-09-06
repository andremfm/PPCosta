<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json; charset=utf-8');

$payload = json_decode(file_get_contents('php://input') ?: '{}', true);
$email = trim((string) ($payload['email'] ?? $_POST['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'Indica um email valido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = db()->prepare(
        'INSERT INTO newsletter_subscribers (email, status)
         VALUES (:email, :status)
         ON DUPLICATE KEY UPDATE status = VALUES(status), unsubscribed_at = NULL'
    );
    $stmt->execute([
        'email' => strtolower($email),
        'status' => 'subscribed',
    ]);

    echo json_encode([
        'status' => 'ok',
        'message' => 'Subscricao registada com sucesso.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable) {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'message' => 'A newsletter fica ativa depois da base de dados estar importada.',
    ], JSON_UNESCAPED_UNICODE);
}
