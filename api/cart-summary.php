<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/cart.php';

header('Content-Type: application/json; charset=utf-8');

$totals = cart_totals();

echo json_encode([
    'status' => 'ok',
    'units' => $totals['units'],
    'subtotal' => format_price($totals['subtotal']),
    'discount' => format_price($totals['discount']),
    'shipping' => $totals['shipping'] > 0 ? format_price($totals['shipping']) : 'Gratis',
    'total' => format_price($totals['total']),
], JSON_UNESCAPED_UNICODE);
