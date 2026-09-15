<?php
declare(strict_types=1);

require_once __DIR__ . '/catalog.php';

function review_statuses(): array
{
    return [
        'pending' => 'Pendente',
        'approved' => 'Aprovada',
        'rejected' => 'Rejeitada',
    ];
}

function review_rating_label(int $rating): string
{
    $rating = max(1, min(5, $rating));

    return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
}

function reviews_for_product(int $productId): array
{
    try {
        $stmt = db()->prepare(
            'SELECT r.*, u.first_name, u.last_name
             FROM reviews r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.product_id = :product_id AND r.status = "approved"
             ORDER BY r.created_at DESC
             LIMIT 12'
        );
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function reviews_recent_approved(int $limit = 3): array
{
    try {
        $stmt = db()->prepare(
            'SELECT r.*, p.name AS product_name, p.slug AS product_slug, u.first_name, u.last_name
             FROM reviews r
             INNER JOIN products p ON p.id = r.product_id
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.status = "approved" AND p.is_active = 1
             ORDER BY r.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function review_summary_for_product(int $productId): array
{
    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) AS reviews_count, COALESCE(AVG(rating), 0) AS average_rating
             FROM reviews
             WHERE product_id = :product_id AND status = "approved"'
        );
        $stmt->execute(['product_id' => $productId]);
        $summary = $stmt->fetch() ?: [];

        return [
            'count' => (int) ($summary['reviews_count'] ?? 0),
            'average' => round((float) ($summary['average_rating'] ?? 0), 1),
        ];
    } catch (Throwable) {
        return ['count' => 0, 'average' => 0.0];
    }
}

function review_find_product_id(string $value): ?int
{
    $value = trim($value);

    if ($value === '') {
        return null;
    }

    try {
        $stmt = db()->prepare(
            'SELECT id
             FROM products
             WHERE id = :id OR sku = :value OR slug = :value OR name LIKE :name
             ORDER BY is_active DESC, id ASC
             LIMIT 1'
        );
        $stmt->execute([
            'id' => ctype_digit($value) ? (int) $value : 0,
            'value' => $value,
            'name' => '%' . $value . '%',
        ]);
        $id = $stmt->fetchColumn();

        return $id ? (int) $id : null;
    } catch (Throwable) {
        foreach (catalog_fallback_products() as $product) {
            if (strcasecmp((string) $product['name'], $value) === 0 || strcasecmp((string) $product['sku'], $value) === 0) {
                return (int) $product['id'];
            }
        }
    }

    return null;
}
