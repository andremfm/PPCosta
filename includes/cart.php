<?php
declare(strict_types=1);

require_once __DIR__ . '/personalization.php';

function cart_init(): void
{
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [
            'items' => [],
            'coupon' => null,
            'notes' => '',
            'shipping_postal_code' => '',
        ];
    }
}

function cart(): array
{
    cart_init();

    return $_SESSION['cart'];
}

function cart_save(array $cart): void
{
    $_SESSION['cart'] = $cart;
}

function cart_item_key(int $productId, array $personalization, string $filePath, int $variationId = 0): string
{
    ksort($personalization);

    return hash('sha256', json_encode([
        'product_id' => $productId,
        'variation_id' => $variationId,
        'personalization' => $personalization,
        'file' => $filePath,
    ]));
}

function cart_add_item(int $productId, int $quantity, array $personalization = [], string $filePath = '', int $variationId = 0): bool
{
    $product = catalog_product_by_id($productId);

    if (!$product || $quantity < 1) {
        return false;
    }

    $variation = $variationId > 0 ? catalog_product_variation($productId, $variationId) : null;

    if ($variationId > 0 && !$variation) {
        return false;
    }

    $rules = !empty($product['is_personalizable'])
        ? personalization_rules_for_product($productId)
        : [];

    if (personalization_validate_values($rules, $personalization) !== []) {
        return false;
    }

    $personalizationTotal = personalization_calculate_total($rules, $personalization);
    $key = cart_item_key($productId, $personalization, $filePath, $variation ? (int) $variation['id'] : 0);
    $cart = cart();

    if (isset($cart['items'][$key])) {
        $cart['items'][$key]['quantity'] += $quantity;
    } else {
        $variationLabel = trim((string) ($variation['attributes_label'] ?? ''));
        $cart['items'][$key] = [
            'key' => $key,
            'product_id' => $productId,
            'variation_id' => $variation ? (int) $variation['id'] : null,
            'variation_label' => $variationLabel,
            'name' => $product['name'],
            'slug' => $product['slug'],
            'sku' => $variation ? (string) $variation['sku'] : $product['sku'],
            'unit_price' => (float) $product['final_price'] + ($variation ? (float) $variation['price_delta'] : 0.0),
            'quantity' => $quantity,
            'personalization' => array_filter($personalization, static fn ($value): bool => $value !== '' && $value !== null),
            'personalization_total' => $personalizationTotal,
            'file_path' => $filePath,
        ];
    }

    cart_save($cart);

    return true;
}

function cart_update_quantities(array $quantities): void
{
    $cart = cart();

    foreach ($quantities as $key => $quantity) {
        if (!isset($cart['items'][$key])) {
            continue;
        }

        $quantity = max(0, (int) $quantity);

        if ($quantity === 0) {
            unset($cart['items'][$key]);
        } else {
            $cart['items'][$key]['quantity'] = $quantity;
        }
    }

    cart_save($cart);
}

function cart_remove_item(string $key): void
{
    $cart = cart();
    unset($cart['items'][$key]);
    cart_save($cart);
}

function cart_clear(): void
{
    $_SESSION['cart'] = [
        'items' => [],
        'coupon' => null,
        'notes' => '',
        'shipping_postal_code' => '',
    ];
}

function cart_apply_coupon(string $code): bool
{
    $code = strtoupper(trim($code));
    $cart = cart();
    $available = [
        'BEMVINDO10' => ['type' => 'percent', 'value' => 10, 'label' => '10% desconto'],
        'NATAL5' => ['type' => 'fixed', 'value' => 5, 'label' => '5 EUR desconto'],
        'PORTES' => ['type' => 'free_shipping', 'value' => 0, 'label' => 'Portes gratuitos'],
    ];

    if (!isset($available[$code])) {
        $cart['coupon'] = null;
        cart_save($cart);
        return false;
    }

    $cart['coupon'] = ['code' => $code] + $available[$code];
    cart_save($cart);

    return true;
}

function cart_save_notes(string $notes, string $postalCode): void
{
    $cart = cart();
    $cart['notes'] = trim($notes);
    $cart['shipping_postal_code'] = trim($postalCode);
    cart_save($cart);
}

function cart_totals(): array
{
    $cart = cart();
    $subtotal = 0.0;
    $personalizationTotal = 0.0;
    $units = 0;

    foreach ($cart['items'] as $item) {
        $units += (int) $item['quantity'];
        $subtotal += ((float) $item['unit_price'] + (float) $item['personalization_total']) * (int) $item['quantity'];
        $personalizationTotal += (float) $item['personalization_total'] * (int) $item['quantity'];
    }

    $shipping = $subtotal >= 75 || ($cart['coupon']['type'] ?? '') === 'free_shipping' || $subtotal === 0.0 ? 0.0 : 4.90;
    $discount = 0.0;

    if (!empty($cart['coupon'])) {
        if ($cart['coupon']['type'] === 'percent') {
            $discount = round($subtotal * ((float) $cart['coupon']['value'] / 100), 2);
        } elseif ($cart['coupon']['type'] === 'fixed') {
            $discount = min($subtotal, (float) $cart['coupon']['value']);
        }
    }

    $tax = round(($subtotal - $discount) - (($subtotal - $discount) / 1.23), 2);

    return [
        'units' => $units,
        'subtotal' => $subtotal,
        'personalization_total' => $personalizationTotal,
        'discount' => $discount,
        'shipping' => $shipping,
        'tax' => $tax,
        'total' => max(0, $subtotal - $discount + $shipping),
    ];
}
