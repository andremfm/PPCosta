<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/personalization.php';

header('Content-Type: application/json; charset=utf-8');

$payload = json_decode(file_get_contents('php://input') ?: '{}', true);
$productId = (int) ($payload['product_id'] ?? 0);
$basePrice = (float) ($payload['base_price'] ?? 0);
$values = is_array($payload['values'] ?? null) ? $payload['values'] : [];

if ($productId <= 0) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'Produto invalido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rules = personalization_rules_for_product($productId);
$extra = personalization_calculate_total($rules, $values);

echo json_encode([
    'status' => 'ok',
    'extra' => $extra,
    'extra_formatted' => format_price($extra),
    'total' => $basePrice + $extra,
    'total_formatted' => format_price($basePrice + $extra),
], JSON_UNESCAPED_UNICODE);

