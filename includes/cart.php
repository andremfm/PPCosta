<?php
declare(strict_types=1);

require_once __DIR__ . '/personalization.php';
require_once __DIR__ . '/settings.php';

function cart_inventory_errors(array $items): array
{
    $errors = [];
    $quantities = [];
    foreach ($items as $item) {
        $productId = (int) $item['product_id'];
        $variationId = (int) ($item['variation_id'] ?? 0);
        $product = catalog_product_by_id($productId);
        $variation = $variationId ? catalog_product_variation($productId, $variationId) : null;
        $key = $productId . ':' . $variationId;
        $quantities[$key] = ($quantities[$key] ?? 0) + (int) $item['quantity'];
        if (!$product || ($variationId && !$variation)
            || (!$variationId && catalog_product_variations($productId) !== [])) {
            $errors[] = 'Confirma a opcao de ' . ($item['name'] ?? 'produto') . ' no catalogo.';
        } elseif ((int) $item['quantity'] < 1 || $quantities[$key] > (int) ($variation['stock'] ?? $product['stock'])) {
            $errors[] = 'Stock insuficiente para ' . $product['name'] . '.';
        }
    }
    return array_unique($errors);
}

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

    if (!db_available() || !$product || $quantity < 1) {
        return false;
    }

    $variation = $variationId > 0 ? catalog_product_variation($productId, $variationId) : null;

    if ($variationId > 0 && !$variation) {
        return false;
    }

    $rules = !empty($product['is_personalizable'])
        ? personalization_rules_for_product($productId)
        : [];

    if (!empty($product['is_personalizable']) && $rules === []) {
        return false;
    }
    if ($filePath !== '') {
        $upload = $_SESSION['personalization_uploads'][$filePath] ?? null;
        $fileRules = array_values(array_filter($rules, static fn (array $rule): bool => $rule['input_type'] === 'file'));
        if (!$upload || $fileRules === [] || !is_file(__DIR__ . '/../' . $filePath)
            || $upload['size'] > (int) ($fileRules[0]['max_file_bytes'] ?? MAX_UPLOAD_BYTES)) {
            return false;
        }
        $personalization[$fileRules[0]['slug']] = basename($filePath);
    } else {
        foreach ($rules as $rule) {
            if ($rule['input_type'] === 'file') {
                unset($personalization[$rule['slug']]);
            }
        }
    }

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
            'tax_rate' => (float) ($product['tax_rate'] ?? 23),
            'quantity' => $quantity,
            'personalization' => array_filter($personalization, static fn ($value): bool => $value !== '' && $value !== null),
            'personalization_total' => $personalizationTotal,
            'file_path' => $filePath,
        ];
    }

    if (cart_inventory_errors($cart['items']) !== []) {
        return false;
    }
    cart_save($cart);

    return true;
}

function cart_update_quantities(array $quantities): bool
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

    if (cart_inventory_errors($cart['items']) !== []) {
        return false;
    }
    cart_save($cart);
    return true;
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
    $subtotal = array_sum(array_map(static fn (array $item): float => ((float) $item['unit_price'] + (float) $item['personalization_total']) * (int) $item['quantity'], $cart['items']));
    $coupon = cart_find_coupon($code, $subtotal);
    if (!$coupon) {
        $cart['coupon'] = null;
        cart_save($cart);
        return $code === '';
    }

    $cart['coupon'] = $coupon;
    cart_save($cart);

    return true;
}

function cart_find_coupon(string $code, float $subtotal, bool $lock = false): ?array
{
    try {
        $stmt = db()->prepare('SELECT * FROM coupons WHERE code = :code AND is_active = 1 AND minimum_order_value <= :subtotal AND (starts_at IS NULL OR starts_at <= CURRENT_TIMESTAMP) AND (ends_at IS NULL OR ends_at >= CURRENT_TIMESTAMP) AND (usage_limit IS NULL OR used_count < usage_limit)' . ($lock ? ' FOR UPDATE' : ''));
        $stmt->execute(['code' => $code, 'subtotal' => $subtotal]);
        $coupon = $stmt->fetch();
        return $coupon ? ['id' => (int) $coupon['id'], 'code' => $coupon['code'], 'type' => $coupon['discount_type'], 'value' => (float) $coupon['discount_value'], 'label' => $coupon['name']] : null;
    } catch (Throwable $exception) {
        if ($lock) throw $exception;
        return null;
    }
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

    $shipping = $subtotal >= (float) store_setting('free_shipping_threshold', '75') || ($cart['coupon']['type'] ?? '') === 'free_shipping' || $subtotal === 0.0 ? 0.0 : 4.90;
    $discount = 0.0;

    if (!empty($cart['coupon'])) {
        if ($cart['coupon']['type'] === 'percent') {
            $discount = round($subtotal * (min(100, (float) $cart['coupon']['value']) / 100), 2);
        } elseif ($cart['coupon']['type'] === 'fixed') {
            $discount = min($subtotal, (float) $cart['coupon']['value']);
        }
    }

    $tax = 0.0;
    foreach ($cart['items'] as $item) {
        $line = ((float) $item['unit_price'] + (float) $item['personalization_total']) * (int) $item['quantity'];
        $line *= $subtotal > 0 ? (1 - $discount / $subtotal) : 0;
        $tax += $line - $line / (1 + (float) ($item['tax_rate'] ?? 23) / 100);
    }
    $tax = round($tax, 2);

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
