<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/personalization.php';

function admin_require(): void
{
    require_role('admin');
}

function admin_metric_value(string $sql, float|int $fallback = 0): float|int
{
    try {
        $value = db()->query($sql)->fetchColumn();

        return is_numeric($value) ? $value + 0 : $fallback;
    } catch (Throwable) {
        return $fallback;
    }
}

function admin_dashboard_metrics(): array
{
    $fallbackProducts = catalog_fallback_products();
    $fallbackStock = array_sum(array_map(static fn (array $product): int => (int) $product['stock'], $fallbackProducts));

    return [
        ['label' => 'Total vendas', 'value' => format_price((float) admin_metric_value('SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE status NOT IN ("cancelled","refunded")', 0)), 'tone' => 'sales'],
        ['label' => 'Encomendas', 'value' => (string) admin_metric_value('SELECT COUNT(*) FROM orders', 0), 'tone' => 'orders'],
        ['label' => 'Clientes', 'value' => (string) admin_metric_value('SELECT COUNT(*) FROM users', 0), 'tone' => 'customers'],
        ['label' => 'Produtos', 'value' => (string) admin_metric_value('SELECT COUNT(*) FROM products', count($fallbackProducts)), 'tone' => 'products'],
        ['label' => 'Stock', 'value' => (string) admin_metric_value('SELECT COALESCE(SUM(stock), 0) FROM products', $fallbackStock), 'tone' => 'stock'],
        ['label' => 'Lucro estimado', 'value' => format_price((float) admin_metric_value('SELECT COALESCE(SUM((price - COALESCE(cost_price, 0)) * stock), 0) FROM products', 0)), 'tone' => 'profit'],
    ];
}

function admin_recent_orders(): array
{
    try {
        $stmt = db()->query(
            'SELECT order_number, customer_email, status, grand_total, created_at
             FROM orders
             ORDER BY created_at DESC
             LIMIT 6'
        );

        return $stmt->fetchAll();
    } catch (Throwable) {
        $lastOrder = $_SESSION['last_order'] ?? null;

        return $lastOrder ? [[
            'order_number' => $lastOrder['order_number'],
            'customer_email' => $lastOrder['customer']['email'],
            'status' => $lastOrder['persisted'] ? 'payment_pending' : 'session',
            'grand_total' => $lastOrder['totals']['total'],
            'created_at' => $lastOrder['created_at'],
        ]] : [];
    }
}

function admin_recent_customers(): array
{
    try {
        $stmt = db()->query(
            'SELECT first_name, last_name, email, status, created_at
             FROM users
             ORDER BY created_at DESC
             LIMIT 6'
        );

        return $stmt->fetchAll();
    } catch (Throwable) {
        $user = current_user();

        return $user ? [$user] : [];
    }
}

function admin_low_stock_products(): array
{
    try {
        $stmt = db()->query(
            'SELECT name, sku, stock, stock_minimum
             FROM products
             WHERE is_active = 1 AND stock <= stock_minimum
             ORDER BY stock ASC, name ASC
             LIMIT 8'
        );

        return $stmt->fetchAll();
    } catch (Throwable) {
        return array_values(array_filter(catalog_fallback_products(), static fn (array $product): bool => (int) $product['stock'] <= 12));
    }
}

function admin_best_sellers(): array
{
    try {
        $stmt = db()->query(
            'SELECT name, sku, units_sold, revenue
             FROM vw_best_sellers
             LIMIT 6'
        );

        return $stmt->fetchAll();
    } catch (Throwable) {
        return array_slice(catalog_fallback_products(), 0, 4);
    }
}

function admin_nav_items(): array
{
    return [
        ['label' => 'Dashboard', 'href' => 'admin/', 'match' => 'index.php'],
        ['label' => 'Produtos', 'href' => 'admin/produtos.php', 'match' => 'produtos.php'],
        ['label' => 'Clientes', 'href' => 'admin/clientes.php', 'match' => 'clientes.php'],
        ['label' => 'Encomendas', 'href' => 'admin/encomendas.php', 'match' => 'encomendas.php'],
        ['label' => 'Stock', 'href' => 'admin/stock.php', 'match' => 'stock.php'],
        ['label' => 'Relatorios', 'href' => 'admin/relatorios.php', 'match' => 'relatorios.php'],
        ['label' => 'Personalizacao', 'href' => 'admin/personalizacao.php', 'match' => 'personalizacao.php'],
    ];
}
