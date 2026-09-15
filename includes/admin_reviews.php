<?php
declare(strict_types=1);

require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/reviews.php';

function admin_reviews_all(array $filters = []): array
{
    $where = [];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = 'r.status = :status';
        $params['status'] = $filters['status'];
    }

    if (!empty($filters['q'])) {
        $where[] = '(p.name LIKE :q OR p.sku LIKE :q_sku OR r.title LIKE :q_title OR r.comment LIKE :q_comment OR u.email LIKE :q_email)';
        $params['q_sku'] = $params['q_title'] = $params['q_comment'] = $params['q_email'] = '%' . $filters['q'] . '%';
        $params['q'] = '%' . $filters['q'] . '%';
    }

    $sql = 'SELECT r.*, p.name AS product_name, p.sku, p.slug, u.first_name, u.last_name, u.email
            FROM reviews r
            INNER JOIN products p ON p.id = r.product_id
            LEFT JOIN users u ON u.id = r.user_id';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY r.created_at DESC LIMIT 100';

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function admin_review_find(int $id): ?array
{
    try {
        $stmt = db()->prepare(
            'SELECT r.*, p.name AS product_name, p.sku, p.slug, u.first_name, u.last_name, u.email
             FROM reviews r
             INNER JOIN products p ON p.id = r.product_id
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $review = $stmt->fetch();

        return $review ?: null;
    } catch (Throwable) {
        return null;
    }
}

function admin_review_update(int $id, string $status, string $adminReply): void
{
    if (!isset(review_statuses()[$status])) {
        return;
    }

    $stmt = db()->prepare(
        'UPDATE reviews
         SET status = :status, admin_reply = :admin_reply
         WHERE id = :id'
    );
    $stmt->execute([
        'status' => $status,
        'admin_reply' => trim($adminReply) !== '' ? trim($adminReply) : null,
        'id' => $id,
    ]);
}

function admin_review_customer_label(array $review): string
{
    $name = trim((string) ($review['first_name'] ?? '') . ' ' . (string) ($review['last_name'] ?? ''));
    $email = trim((string) ($review['email'] ?? ''));

    if ($name !== '' && $email !== '') {
        return $name . ' · ' . $email;
    }

    return $email !== '' ? $email : 'Cliente';
}
