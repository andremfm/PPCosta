<?php
declare(strict_types=1);

require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/payments.php';
require_once __DIR__ . '/settings.php';

function checkout_shipping_methods(): array
{
    $fallback = [
        'ctt' => ['name' => 'CTT', 'price' => 4.90],
        'dpd' => ['name' => 'DPD', 'price' => 5.90],
        'mrw' => ['name' => 'MRW', 'price' => 5.90],
        'gls' => ['name' => 'GLS', 'price' => 5.90],
        'dhl' => ['name' => 'DHL', 'price' => 7.90],
        'ups' => ['name' => 'UPS', 'price' => 7.90],
        'pickup' => ['name' => 'Levantamento em loja', 'price' => 0.00],
        'free_shipping' => ['name' => 'Portes gratuitos', 'price' => 0.00],
    ];

    $methods = store_active_methods('shipping_methods', $fallback);
    if (cart_totals()['subtotal'] < (float) store_setting('free_shipping_threshold', '75')
        && (cart()['coupon']['type'] ?? '') !== 'free_shipping') {
        unset($methods['free_shipping']);
    }
    return $methods;
}

function checkout_payment_methods(): array
{
    return payment_active_methods();
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

    if (empty($data['same_billing'])) {
        foreach (['first_name', 'last_name', 'address_line_1', 'postal_code', 'city'] as $field) {
            if (trim((string) ($data['shipping_' . $field] ?? '')) === '') {
                $errors[] = 'Preenche todos os dados da morada de entrega.';
                break;
            }
        }
    }
    $errors = array_merge($errors, cart_inventory_errors(cart()['items']));

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

    $freeShippingThreshold = (float) store_setting('free_shipping_threshold', '75');

    $totals['shipping'] = ($cart['coupon']['type'] ?? '') === 'free_shipping' || $totals['subtotal'] >= $freeShippingThreshold
        ? 0.0 : (float) $shippingMethod['price'];
    $totals['total'] = max(0, $totals['subtotal'] - $totals['discount'] + $totals['shipping']);

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
        if (!$pdo->query("SHOW TRIGGERS LIKE 'stock_movements'")->fetch()) {
            throw new RuntimeException('Stock trigger is missing');
        }

        if ($order['items'] === []) {
            throw new RuntimeException('Empty order');
        }
        // Lock in a stable order; each stock movement is applied by the database trigger.
        $stockItems = $order['items'];
        usort($stockItems, static fn (array $a, array $b): int => [$a['product_id'], $a['variation_id'] ?? 0] <=> [$b['product_id'], $b['variation_id'] ?? 0]);
        foreach ($stockItems as $item) {
            $lock = $pdo->prepare('SELECT id FROM products WHERE id = :id AND is_active = 1 FOR UPDATE');
            $lock->execute(['id' => $item['product_id']]);
            if (!$lock->fetchColumn()) {
                throw new DomainException('Um produto deixou de estar disponivel. Revê o carrinho.');
            }
            if (!empty($item['variation_id'])) {
                $lock = $pdo->prepare('SELECT id FROM product_variations WHERE id = :id AND product_id = :product_id AND is_active = 1 FOR UPDATE');
                $lock->execute(['id' => $item['variation_id'], 'product_id' => $item['product_id']]);
                if (!$lock->fetchColumn()) {
                    throw new DomainException('Uma opcao deixou de estar disponivel. Revê o carrinho.');
                }
            }
            $product = catalog_product_by_id((int) $item['product_id']);
            $variation = !empty($item['variation_id']) ? catalog_product_variation((int) $item['product_id'], (int) $item['variation_id']) : null;
            $rules = !empty($product['is_personalizable']) ? personalization_rules_for_product((int) $item['product_id']) : [];
            if (personalization_validate_values($rules, $item['personalization']) !== []
                || abs((float) $item['unit_price'] - ((float) $product['final_price'] + (float) ($variation['price_delta'] ?? 0))) > 0.001
                || abs((float) $item['personalization_total'] - personalization_calculate_total($rules, $item['personalization'])) > 0.001) {
                throw new DomainException('O preco ou a personalizacao mudou. Remove o artigo e adiciona-o novamente ao carrinho.');
            }
        }
        $inventoryErrors = cart_inventory_errors($stockItems);
        if ($inventoryErrors !== []) {
            throw new DomainException(implode(' ', $inventoryErrors));
        }

        $paymentId = checkout_lookup_id('payment_methods', $order['payment_method']['code']);
        $shippingId = checkout_lookup_id('shipping_methods', $order['shipping_method']['code']);
        if (!$paymentId || !$shippingId || !isset(checkout_payment_methods()[$order['payment_method']['code']])) {
            throw new DomainException('O metodo de pagamento ou envio deixou de estar disponivel. Escolhe outro.');
        }
        $couponId = null;
        if (!empty($order['coupon'])) {
            $coupon = cart_find_coupon($order['coupon']['code'], (float) $order['totals']['subtotal'], true);
            if (!$coupon || $coupon['type'] !== $order['coupon']['type'] || $coupon['value'] !== (float) $order['coupon']['value']) {
                throw new DomainException('O cupao ja nao e valido. Retira-o ou aplica outro no carrinho.');
            }
            $couponId = $coupon['id'];
            $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = :id')->execute(['id' => $couponId]);
        }

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
                order_id, product_id, variation_id, product_name, sku, quantity, unit_price,
                personalization_total, tax_rate, line_total
             ) VALUES (
                :order_id, :product_id, :variation_id, :product_name, :sku, :quantity, :unit_price,
                :personalization_total, :tax_rate, :line_total
             )'
        );

        foreach ($order['items'] as $item) {
            $lineTotal = ((float) $item['unit_price'] + (float) $item['personalization_total']) * (int) $item['quantity'];
            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'variation_id' => $item['variation_id'] ?? null,
                'product_name' => $item['name'],
                'sku' => $item['sku'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'personalization_total' => $item['personalization_total'],
                'tax_rate' => (float) ($item['tax_rate'] ?? 23),
                'line_total' => $lineTotal,
            ]);

            $orderItemId = (int) $pdo->lastInsertId();
            checkout_store_order_item_personalizations($orderItemId, $item);
            $movement = $pdo->prepare('INSERT INTO stock_movements (product_id, variation_id, user_id, type, quantity, reason, reference_type, reference_id) VALUES (:product, :variation, :user, "reservation", :quantity, "Reserva de encomenda", "order", :order_id)');
            $movement->execute(['product' => $item['product_id'], 'variation' => $item['variation_id'] ?? null, 'user' => $_SESSION['user_id'] ?? null, 'quantity' => -(int) $item['quantity'], 'order_id' => $orderId]);
        }

        $order['payment'] = payment_create_for_order($orderId, $order);
        $pdo->commit();
        $order['id'] = $orderId;
        $order['user_id'] = $_SESSION['user_id'] ?? null;
        $order['persisted'] = true;

        return $order;
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $order['id'] = null;
        $order['persisted'] = false;
        $order['error'] = $exception instanceof DomainException ? $exception->getMessage() : 'Nao foi possivel guardar a encomenda. O carrinho foi mantido. Tenta novamente.';
        error_log('Checkout failed: ' . $exception->getMessage());

        return $order;
    }
}

function checkout_lookup_id(string $table, string $code): ?int
{
    $allowed = ['payment_methods', 'shipping_methods'];

    if (!in_array($table, $allowed, true)) {
        return null;
    }

    $stmt = db()->prepare('SELECT id FROM ' . $table . ' WHERE code = :code AND is_active = 1 LIMIT 1');
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
