<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/checkout.php';

function customer_update_profile(int $userId, array $data): bool
{
    try {
        $stmt = db()->prepare(
            'UPDATE users
             SET first_name = :first_name, last_name = :last_name, phone = :phone, newsletter_opt_in = :newsletter_opt_in
             WHERE id = :id'
        );
        $stmt->execute([
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'phone' => trim($data['phone']) !== '' ? trim($data['phone']) : null,
            'newsletter_opt_in' => !empty($data['newsletter_opt_in']) ? 1 : 0,
            'id' => $userId,
        ]);

        return true;
    } catch (Throwable) {
        $_SESSION[customer_session_key('profile')] = [
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'phone' => trim($data['phone']),
            'newsletter_opt_in' => !empty($data['newsletter_opt_in']) ? 1 : 0,
        ];

        return true;
    }
}

function customer_change_password(int $userId, string $currentPassword, string $newPassword): bool
{
    try {
        $user = find_user_by_id($userId);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return false;
        }

        $stmt = db()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $stmt->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $userId,
        ]);

        return true;
    } catch (Throwable) {
        return false;
    }
}

function customer_addresses(int $userId): array
{
    try {
        $stmt = db()->prepare(
            'SELECT *
             FROM addresses
             WHERE user_id = :user_id
             ORDER BY is_default DESC, created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return $_SESSION[customer_session_key('addresses')] ?? [];
    }
}

function customer_save_address(int $userId, array $data): void
{
    $address = [
        'type' => $data['type'] ?? 'shipping',
        'label' => trim($data['label'] ?? ''),
        'company' => trim($data['company'] ?? ''),
        'tax_number' => trim($data['tax_number'] ?? ''),
        'first_name' => trim($data['first_name'] ?? ''),
        'last_name' => trim($data['last_name'] ?? ''),
        'phone' => trim($data['phone'] ?? ''),
        'address_line_1' => trim($data['address_line_1'] ?? ''),
        'address_line_2' => trim($data['address_line_2'] ?? ''),
        'postal_code' => trim($data['postal_code'] ?? ''),
        'city' => trim($data['city'] ?? ''),
        'district' => trim($data['district'] ?? ''),
        'country_code' => 'PT',
        'is_default' => !empty($data['is_default']) ? 1 : 0,
    ];

    try {
        $stmt = db()->prepare(
            'INSERT INTO addresses (
                user_id, type, label, company, tax_number, first_name, last_name, phone,
                address_line_1, address_line_2, postal_code, city, district, country_code, is_default
             ) VALUES (
                :user_id, :type, :label, :company, :tax_number, :first_name, :last_name, :phone,
                :address_line_1, :address_line_2, :postal_code, :city, :district, :country_code, :is_default
             )'
        );
        $stmt->execute(['user_id' => $userId] + $address);
    } catch (Throwable) {
        $addresses = $_SESSION[customer_session_key('addresses')] ?? [];
        $addresses[] = ['id' => count($addresses) + 1, 'created_at' => date(DATE_ATOM)] + $address;
        $_SESSION[customer_session_key('addresses')] = $addresses;
    }
}

function customer_orders(int $userId): array
{
    try {
        $stmt = db()->prepare(
            'SELECT *
             FROM orders
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT 12'
        );
        $stmt->execute(['user_id' => $userId]);
        $orders = $stmt->fetchAll();

        if ($orders !== []) {
            return $orders;
        }
    } catch (Throwable) {
    }

    $orders = $_SESSION[customer_session_key('orders')] ?? [];

    if (!empty($_SESSION['last_order'])) {
        $lastOrderNumber = $_SESSION['last_order']['order_number'] ?? null;
        $hasLastOrder = false;

        foreach ($orders as $order) {
            if (($order['order_number'] ?? null) === $lastOrderNumber) {
                $hasLastOrder = true;
                break;
            }
        }

        if (!$hasLastOrder) {
            $orders = array_merge([$_SESSION['last_order']], $orders);
        }
    }

    return $orders;
}

function customer_wishlist(int $userId): array
{
    try {
        $stmt = db()->prepare(
            'SELECT p.name, p.slug, p.sku, COALESCE(p.sale_price, p.price) AS final_price
             FROM wishlists w
             INNER JOIN products p ON p.id = w.product_id
             WHERE w.user_id = :user_id
             ORDER BY w.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        $items = $stmt->fetchAll();

        if ($items !== []) {
            return $items;
        }
    } catch (Throwable) {
    }

    return array_slice(catalog_fallback_products(), 0, 3);
}

function customer_reviews(int $userId): array
{
    try {
        $stmt = db()->prepare(
            'SELECT r.*, p.name AS product_name
             FROM reviews r
             INNER JOIN products p ON p.id = r.product_id
             WHERE r.user_id = :user_id
             ORDER BY r.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return $_SESSION[customer_session_key('reviews')] ?? [];
    }
}

function customer_save_review(int $userId, array $data): void
{
    $review = [
        'product_name' => trim($data['product_name'] ?? ''),
        'rating' => max(1, min(5, (int) ($data['rating'] ?? 5))),
        'title' => trim($data['title'] ?? ''),
        'comment' => trim($data['comment'] ?? ''),
        'status' => 'pending',
        'created_at' => date(DATE_ATOM),
    ];

    $_SESSION[customer_session_key('reviews')][] = $review;
}

function customer_message_threads(int $userId): array
{
    try {
        $stmt = db()->prepare(
            'SELECT mt.*, MAX(m.created_at) AS last_message_at
             FROM message_threads mt
             LEFT JOIN messages m ON m.thread_id = mt.id
             WHERE mt.user_id = :user_id
             GROUP BY mt.id
             ORDER BY COALESCE(last_message_at, mt.created_at) DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return $_SESSION[customer_session_key('messages')] ?? [];
    }
}

function customer_send_message(int $userId, array $data): void
{
    $subject = trim($data['subject'] ?? '');
    $body = trim($data['body'] ?? '');

    try {
        $pdo = db();
        $pdo->beginTransaction();
        $threadStmt = $pdo->prepare('INSERT INTO message_threads (user_id, subject, status) VALUES (:user_id, :subject, :status)');
        $threadStmt->execute(['user_id' => $userId, 'subject' => $subject, 'status' => 'waiting_admin']);
        $threadId = (int) $pdo->lastInsertId();
        $messageStmt = $pdo->prepare('INSERT INTO messages (thread_id, sender_user_id, sender_type, body) VALUES (:thread_id, :sender_user_id, :sender_type, :body)');
        $messageStmt->execute(['thread_id' => $threadId, 'sender_user_id' => $userId, 'sender_type' => 'customer', 'body' => $body]);
        $pdo->commit();
    } catch (Throwable) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION[customer_session_key('messages')][] = [
            'subject' => $subject,
            'status' => 'waiting_admin',
            'created_at' => date(DATE_ATOM),
            'last_message_at' => date(DATE_ATOM),
        ];
    }
}

function customer_notifications(int $userId): array
{
    try {
        $stmt = db()->prepare(
            'SELECT *
             FROM notifications
             WHERE user_id = :user_id OR user_id IS NULL
             ORDER BY created_at DESC
             LIMIT 8'
        );
        $stmt->execute(['user_id' => $userId]);
        $notifications = $stmt->fetchAll();

        if ($notifications !== []) {
            return $notifications;
        }
    } catch (Throwable) {
    }

    return [
        ['title' => 'Conta ativa', 'body' => 'A tua area cliente esta pronta para acompanhar encomendas.', 'type' => 'info', 'created_at' => date(DATE_ATOM)],
        ['title' => 'Personalizacoes', 'body' => 'Os detalhes enviados ficam associados ao carrinho e a encomenda.', 'type' => 'info', 'created_at' => date(DATE_ATOM)],
    ];
}
