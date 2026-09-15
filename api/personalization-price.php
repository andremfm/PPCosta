<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/personalization.php';

header('Content-Type: application/json; charset=utf-8');

$payload = json_decode(file_get_contents('php://input') ?: '{}', true);
$productId = (int) ($payload['product_id'] ?? 0);
$product = catalog_product_by_id($productId);
$variationId = (int) ($payload['variation_id'] ?? 0);
$variation = $variationId ? catalog_product_variation($productId, $variationId) : null;
$basePrice = (float) ($product['final_price'] ?? 0) + (float) ($variation['price_delta'] ?? 0);
$values = is_array($payload['values'] ?? null) ? $payload['values'] : [];

if (!$product || ($variationId && !$variation)) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'Produto invalido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rules = !empty($product['is_personalizable']) ? personalization_rules_for_product($productId) : [];
$previewRules = array_map(static function (array $rule): array { $rule['is_required'] = 0; return $rule; }, $rules);
if (personalization_validate_values($previewRules, $values) !== []) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Opcoes de personalizacao invalidas.']);
    exit;
}
$extra = personalization_calculate_total($rules, $values);

echo json_encode([
    'status' => 'ok',
    'extra' => $extra,
    'extra_formatted' => format_price($extra),
    'total' => $basePrice + $extra,
    'total_formatted' => format_price($basePrice + $extra),
], JSON_UNESCAPED_UNICODE);
