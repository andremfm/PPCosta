<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/catalog.php';

header('Content-Type: application/json; charset=utf-8');

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'category' => trim($_GET['categoria'] ?? ''),
    'min_price' => null,
    'max_price' => null,
    'personalization' => '',
    'sort' => 'recent',
];

$products = array_slice(catalog_products($filters), 0, 8);

echo json_encode([
    'status' => 'ok',
    'items' => array_map(static function (array $product): array {
        return [
            'name' => $product['name'],
            'slug' => $product['slug'],
            'sku' => $product['sku'],
            'price' => format_price((float) $product['final_price']),
            'url' => url('produto-detalhe.php?slug=' . urlencode($product['slug'])),
        ];
    }, $products),
], JSON_UNESCAPED_UNICODE);
