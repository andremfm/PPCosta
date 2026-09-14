<?php
declare(strict_types=1);

require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/checkout.php';

function admin_order_statuses(): array
{
    return [
        'new' => 'Nova',
        'payment_pending' => 'Pagamento pendente',
        'in_production' => 'Em producao',
        'in_personalization' => 'Em personalizacao',
        'in_embroidery' => 'Em bordado',
        'in_printing' => 'Em estampagem',
        'ready' => 'Pronta',
        'shipped' => 'Enviada',
        'delivered' => 'Entregue',
        'cancelled' => 'Cancelada',
        'refunded' => 'Reembolsada',
    ];
}

function admin_orders_session_items(): array
{
    $orders = [];

    if (!empty($_SESSION['last_order'])) {
        $orders[] = admin_order_normalize_session($_SESSION['last_order']);
    }

    foreach ($_SESSION as $key => $value) {
        if (str_starts_with((string) $key, 'customer_') && str_ends_with((string) $key, '_orders') && is_array($value)) {
            foreach ($value as $order) {
                $orders[] = admin_order_normalize_session($order);
            }
        }
    }

    $unique = [];
    foreach ($orders as $order) {
        $unique[$order['order_number']] = $order;
    }

    return array_values($unique);
}

function admin_order_normalize_session(array $order): array
{
    return [
        'id' => $order['id'] ?? null,
        'order_number' => (string) ($order['order_number'] ?? 'Sessao'),
        'status' => (string) ($order['status'] ?? (($order['persisted'] ?? false) ? 'payment_pending' : 'new')),
        'customer_email' => (string) ($order['customer_email'] ?? ($order['customer']['email'] ?? '')),
        'customer_phone' => (string) ($order['customer_phone'] ?? ($order['customer']['phone'] ?? '')),
        'subtotal' => (float) ($order['subtotal'] ?? ($order['totals']['subtotal'] ?? 0)),
        'discount_total' => (float) ($order['discount_total'] ?? ($order['totals']['discount'] ?? 0)),
        'shipping_total' => (float) ($order['shipping_total'] ?? ($order['totals']['shipping'] ?? 0)),
        'tax_total' => (float) ($order['tax_total'] ?? ($order['totals']['tax'] ?? 0)),
        'grand_total' => (float) ($order['grand_total'] ?? ($order['totals']['total'] ?? 0)),
        'notes' => (string) ($order['notes'] ?? ''),
        'created_at' => (string) ($order['created_at'] ?? date(DATE_ATOM)),
        'items' => $order['items'] ?? [],
        'billing_address' => $order['billing_address'] ?? [],
        'shipping_address' => $order['shipping_address'] ?? [],
        'timeline' => $order['timeline'] ?? [],
    ];
}

function admin_orders_all(array $filters = []): array
{
    try {
        if (db_available()) {
            $where = [];
            $params = [];

            if (!empty($filters['q'])) {
                $where[] = '(o.order_number LIKE :q OR o.customer_email LIKE :q OR o.customer_phone LIKE :q)';
                $params['q'] = '%' . trim((string) $filters['q']) . '%';
            }

            if (!empty($filters['status'])) {
                $where[] = 'o.status = :status';
                $params['status'] = (string) $filters['status'];
            }

            $sql = 'SELECT o.*, u.first_name, u.last_name
                    FROM orders o
                    LEFT JOIN users u ON u.id = o.user_id';

            if ($where !== []) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $sql .= ' ORDER BY o.created_at DESC LIMIT 200';
            $stmt = db()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        }
    } catch (Throwable) {
    }

    $orders = admin_orders_session_items();
    $query = strtolower(trim((string) ($filters['q'] ?? '')));
    $status = trim((string) ($filters['status'] ?? ''));

    return array_values(array_filter($orders, static function (array $order) use ($query, $status): bool {
        $haystack = strtolower(($order['order_number'] ?? '') . ' ' . ($order['customer_email'] ?? '') . ' ' . ($order['customer_phone'] ?? ''));
        $matchesQuery = $query === '' || str_contains($haystack, $query);
        $matchesStatus = $status === '' || ($order['status'] ?? '') === $status;

        return $matchesQuery && $matchesStatus;
    }));
}

function admin_order_find(string|int $id): ?array
{
    try {
        if (db_available()) {
            $field = is_numeric((string) $id) ? 'id' : 'order_number';
            $stmt = db()->prepare('SELECT * FROM orders WHERE ' . $field . ' = :value LIMIT 1');
            $stmt->execute(['value' => $id]);
            $order = $stmt->fetch();

            return $order ?: null;
        }
    } catch (Throwable) {
    }

    foreach (admin_orders_session_items() as $order) {
        if ((string) ($order['id'] ?? '') === (string) $id || (string) $order['order_number'] === (string) $id) {
            return $order;
        }
    }

    return null;
}

function admin_order_items(string|int $id): array
{
    try {
        if (db_available() && is_numeric((string) $id)) {
            $stmt = db()->prepare(
                'SELECT oi.*
                 FROM order_items oi
                 WHERE oi.order_id = :order_id
                 ORDER BY oi.id ASC'
            );
            $stmt->execute(['order_id' => (int) $id]);

            return $stmt->fetchAll();
        }
    } catch (Throwable) {
    }

    $order = admin_order_find($id);

    return $order['items'] ?? [];
}

function admin_order_addresses(string|int $id): array
{
    try {
        if (db_available() && is_numeric((string) $id)) {
            $stmt = db()->prepare(
                'SELECT *
                 FROM order_addresses
                 WHERE order_id = :order_id
                 ORDER BY FIELD(type, "billing", "shipping")'
            );
            $stmt->execute(['order_id' => (int) $id]);

            return $stmt->fetchAll();
        }
    } catch (Throwable) {
    }

    $order = admin_order_find($id);
    $addresses = [];

    if (!empty($order['billing_address'])) {
        $addresses[] = ['type' => 'billing'] + $order['billing_address'];
    }

    if (!empty($order['shipping_address'])) {
        $addresses[] = ['type' => 'shipping'] + $order['shipping_address'];
    }

    return $addresses;
}

function admin_order_timeline(string|int $id): array
{
    try {
        if (db_available() && is_numeric((string) $id)) {
            $stmt = db()->prepare(
                'SELECT osh.*, u.first_name, u.last_name
                 FROM order_status_history osh
                 LEFT JOIN users u ON u.id = osh.user_id
                 WHERE osh.order_id = :order_id
                 ORDER BY osh.created_at DESC'
            );
            $stmt->execute(['order_id' => (int) $id]);

            return $stmt->fetchAll();
        }
    } catch (Throwable) {
    }

    $order = admin_order_find($id);

    if (!empty($order['timeline'])) {
        return $order['timeline'];
    }

    return [[
        'status' => $order['status'] ?? 'new',
        'note' => 'Encomenda registada em sessao.',
        'created_at' => $order['created_at'] ?? date(DATE_ATOM),
    ]];
}

function admin_order_update_status(string|int $id, string $status, string $note = ''): void
{
    if (!array_key_exists($status, admin_order_statuses())) {
        return;
    }

    try {
        if (!is_numeric((string) $id)) {
            throw new RuntimeException('Order id is not numeric.');
        }

        $orderId = (int) $id;
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id')->execute(['status' => $status, 'id' => $orderId]);

        if (trim($note) !== '') {
            $pdo->prepare(
                'INSERT INTO order_status_history (order_id, user_id, status, note)
                 VALUES (:order_id, :user_id, :status, :note)'
            )->execute([
                'order_id' => $orderId,
                'user_id' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
                'status' => $status,
                'note' => trim($note),
            ]);
        }

        $pdo->commit();
        return;
    } catch (Throwable) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    if (!empty($_SESSION['last_order']) && ((string) ($_SESSION['last_order']['id'] ?? '') === (string) $id || (string) ($_SESSION['last_order']['order_number'] ?? '') === (string) $id)) {
        $_SESSION['last_order']['status'] = $status;
        $_SESSION['last_order']['timeline'][] = [
            'status' => $status,
            'note' => trim($note),
            'created_at' => date(DATE_ATOM),
        ];
    }

    foreach ($_SESSION as $key => $value) {
        if (!str_starts_with((string) $key, 'customer_') || !str_ends_with((string) $key, '_orders') || !is_array($value)) {
            continue;
        }

        foreach ($value as $index => $order) {
            if ((string) ($order['id'] ?? '') === (string) $id || (string) ($order['order_number'] ?? '') === (string) $id) {
                $_SESSION[$key][$index]['status'] = $status;
                $_SESSION[$key][$index]['timeline'][] = [
                    'status' => $status,
                    'note' => trim($note),
                    'created_at' => date(DATE_ATOM),
                ];
            }
        }
    }
}

function admin_order_status_label(string $status): string
{
    $statuses = admin_order_statuses();

    return $statuses[$status] ?? $status;
}
