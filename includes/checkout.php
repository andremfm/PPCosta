<?php
declare(strict_types=1);

require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/auth.php';

function checkout_shipping_methods(): array
{
    return [
        'ctt' => ['name' => 'CTT', 'price' => 4.90],
        'dpd' => ['name' => 'DPD', 'price' => 5.90],
        'pickup' => ['name' => 'Levantamento em loja', 'price' => 0.00],
        'free_shipping' => ['name' => 'Portes gratuitos', 'price' => 0.00],
    ];
}

function checkout_payment_methods(): array
{
    return [
        'mbway' => 'MB Way',
        'multibanco' => 'Multibanco',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
        'card' => 'Cartao',
        'bank_transfer' => 'Transferencia Bancaria',
        'cash_on_delivery' => 'Contra Reembolso',
    ];
}

function checkout_validate(array $data): array
{
    $errors = [];
    $required = [
        'first_name' => 'Nome',
        'last_name' => 'Apelido',
        'email' => 'Email',
        'phone' => 'Telefone',
        'address_line_1' => 'Morada',
        'postal_code' => 'Codigo postal',
        'city' => 'Localidade',
        'shipping_method' => 'Metodo de envio',
        'payment_method' => 'Metodo de pagamento',
    ];

    foreach ($required as $key => $label) {
        if (trim((string) ($data[$key] ?? '')) === '') {
            $errors[] = $label . ' e obrigatorio.';
        }
    }

    if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Indica um email valido.';
    }

    if (empty($data['gdpr_accept'])) {
        $errors[] = 'E necessario aceitar a politica RGPD.';
    }

    if (!isset(checkout_shipping_methods()[$data['shipping_method'] ?? ''])) {
        $errors[] = 'Metodo de envio invalido.';
    }

    if (!isset(checkout_payment_methods()[$data['payment_method'] ?? ''])) {
        $errors[] = 'Metodo de pagamento invalido.';
    }

    return $errors;
}

function checkout_order_number(): string
{
    return 'PPC-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

function checkout_prepare_order(array $data): array
{
    $cart = cart();
    $totals = cart_totals();
    $shippingMethods = checkout_shipping_methods();
    $shippingMethod = $shippingMethods[$data['shipping_method']];

    if (($cart['coupon']['type'] ?? '') !== 'free_shipping' && $totals['subtotal'] < 75) {
        $totals['shipping'] = (float) $shippingMethod['price'];
        $totals['total'] = max(0, $totals['subtotal'] - $totals['discount'] + $totals['shipping']);
    }

    return [
        'order_number' => checkout_order_number(),
        'customer' => [
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => trim($data['phone']),
            'company' => trim($data['company'] ?? ''),
            'tax_number' => trim($data['tax_number'] ?? ''),
        ],
        'billing_address' => checkout_address_from_data($data, 'billing'),
        'shipping_address' => !empty($data['same_billing'])
            ? checkout_address_from_data($data, 'billing')
            : checkout_address_from_data($data, 'shipping'),
        'shipping_method' => [
            'code' => $data['shipping_method'],
            'name' => $shippingMethod['name'],
        ],
        'payment_method' => [
            'code' => $data['payment_method'],
            'name' => checkout_payment_methods()[$data['payment_method']],
        ],
        'items' => array_values($cart['items']),
        'coupon' => $cart['coupon'],
        'notes' => trim($data['notes'] ?? $cart['notes'] ?? ''),
        'totals' => $totals,
        'created_at' => date(DATE_ATOM),
    ];
}

function checkout_address_from_data(array $data, string $prefix): array
{
    $sourcePrefix = $prefix === 'shipping' ? 'shipping_' : '';

    return [
        'company' => trim($data['company'] ?? ''),
        'tax_number' => trim($data['tax_number'] ?? ''),
        'first_name' => trim($data[$sourcePrefix . 'first_name'] ?? $data['first_name'] ?? ''),
        'last_name' => trim($data[$sourcePrefix . 'last_name'] ?? $data['last_name'] ?? ''),
        'phone' => trim($data[$sourcePrefix . 'phone'] ?? $data['phone'] ?? ''),
        'address_line_1' => trim($data[$sourcePrefix . 'address_line_1'] ?? $data['address_line_1'] ?? ''),
        'address_line_2' => trim($data[$sourcePrefix . 'address_line_2'] ?? $data['address_line_2'] ?? ''),
        'postal_code' => trim($data[$sourcePrefix . 'postal_code'] ?? $data['postal_code'] ?? ''),
        'city' => trim($data[$sourcePrefix . 'city'] ?? $data['city'] ?? ''),
        'district' => trim($data[$sourcePrefix . 'district'] ?? $data['district'] ?? ''),
        'country_code' => 'PT',
    ];
}

function checkout_store_order(array $order): array
{
    try {
        $pdo = db();
        $pdo->beginTransaction();

        $paymentId = checkout_lookup_id('payment_methods', $order['payment_method']['code']);
        $shippingId = checkout_lookup_id('shipping_methods', $order['shipping_method']['code']);
        $couponId = null;

        $stmt = $pdo->prepare(
            'INSERT INTO orders (
                user_id, coupon_id, payment_method_id, shipping_method_id, order_number, status,
                customer_email, customer_phone, subtotal, discount_total, shipping_total,
                tax_total, grand_total, notes, gdpr_accepted_at
             ) VALUES (
                :user_id, :coupon_id, :payment_method_id, :shipping_method_id, :order_number, :status,
                :customer_email, :customer_phone, :subtotal, :discount_total, :shipping_total,
                :tax_total, :grand_total, :notes, CURRENT_TIMESTAMP
             )'
        );
        $stmt->execute([
            'user_id' => $_SESSION['user_id'] ?? null,
            'coupon_id' => $couponId,
            'payment_method_id' => $paymentId,
            'shipping_method_id' => $shippingId,
            'order_number' => $order['order_number'],
            'status' => 'payment_pending',
            'customer_email' => $order['customer']['email'],
            'customer_phone' => $order['customer']['phone'],
            'subtotal' => $order['totals']['subtotal'],
            'discount_total' => $order['totals']['discount'],
            'shipping_total' => $order['totals']['shipping'],
            'tax_total' => $order['totals']['tax'],
            'grand_total' => $order['totals']['total'],
            'notes' => $order['notes'],
        ]);

        $orderId = (int) $pdo->lastInsertId();
        checkout_store_order_address($orderId, 'billing', $order['billing_address']);
        checkout_store_order_address($orderId, 'shipping', $order['shipping_address']);

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (
                order_id, product_id, product_name, sku, quantity, unit_price,
                personalization_total, tax_rate, line_total
             ) VALUES (
                :order_id, :product_id, :product_name, :sku, :quantity, :unit_price,
                :personalization_total, :tax_rate, :line_total
             )'
        );

        foreach ($order['items'] as $item) {
            $lineTotal = ((float) $item['unit_price'] + (float) $item['personalization_total']) * (int) $item['quantity'];
            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'product_name' => $item['name'],
                'sku' => $item['sku'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'personalization_total' => $item['personalization_total'],
                'tax_rate' => 23.00,
                'line_total' => $lineTotal,
            ]);

            $orderItemId = (int) $pdo->lastInsertId();
            checkout_store_order_item_personalizations($orderItemId, $item);
        }

        $pdo->commit();
        $order['id'] = $orderId;
        $order['persisted'] = true;

        return $order;
    } catch (Throwable) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $order['id'] = null;
        $order['persisted'] = false;

        return $order;
    }
}

function checkout_lookup_id(string $table, string $code): ?int
{
    $allowed = ['payment_methods', 'shipping_methods'];

    if (!in_array($table, $allowed, true)) {
        return null;
    }

    $stmt = db()->prepare('SELECT id FROM ' . $table . ' WHERE code = :code LIMIT 1');
    $stmt->execute(['code' => $code]);
    $id = $stmt->fetchColumn();

    return $id ? (int) $id : null;
}

function checkout_store_order_address(int $orderId, string $type, array $address): void
{
    $stmt = db()->prepare(
        'INSERT INTO order_addresses (
            order_id, type, company, tax_number, first_name, last_name, phone,
            address_line_1, address_line_2, postal_code, city, district, country_code
         ) VALUES (
            :order_id, :type, :company, :tax_number, :first_name, :last_name, :phone,
            :address_line_1, :address_line_2, :postal_code, :city, :district, :country_code
         )'
    );
    $stmt->execute(['order_id' => $orderId, 'type' => $type] + $address);
}

function checkout_store_order_item_personalizations(int $orderItemId, array $item): void
{
    $stmt = db()->prepare(
        'INSERT INTO order_item_personalizations (order_item_id, label, value_text, file_path, extra_price)
         VALUES (:order_item_id, :label, :value_text, :file_path, :extra_price)'
    );

    foreach ($item['personalization'] as $label => $value) {
        $stmt->execute([
            'order_item_id' => $orderItemId,
            'label' => (string) $label,
            'value_text' => (string) $value,
            'file_path' => null,
            'extra_price' => 0,
        ]);
    }

    if ($item['file_path'] !== '') {
        $stmt->execute([
            'order_item_id' => $orderItemId,
            'label' => 'ficheiro',
            'value_text' => basename($item['file_path']),
            'file_path' => $item['file_path'],
            'extra_price' => 0,
        ]);
    }
}
