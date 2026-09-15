<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_orders.php';

function admin_report_summary(): array
{
    try {
        if (db_available()) {
            $row = db()->query(
                'SELECT
                    COUNT(*) AS orders_count,
                    COALESCE(SUM(grand_total), 0) AS revenue,
                    COALESCE(SUM(discount_total), 0) AS discounts,
                    COALESCE(SUM(shipping_total), 0) AS shipping,
                    COALESCE(SUM(tax_total), 0) AS tax
                 FROM orders
                 WHERE status NOT IN ("cancelled", "refunded")'
            )->fetch();

            $profit = admin_metric_value(
                'SELECT COALESCE(SUM((oi.unit_price - COALESCE(p.cost_price, 0)) * oi.quantity), 0)
                 FROM order_items oi
                 LEFT JOIN products p ON p.id = oi.product_id
                 INNER JOIN orders o ON o.id = oi.order_id
                 WHERE o.status NOT IN ("cancelled", "refunded")',
                0
            );

            return [
                'orders_count' => (int) ($row['orders_count'] ?? 0),
                'revenue' => (float) ($row['revenue'] ?? 0),
                'discounts' => (float) ($row['discounts'] ?? 0),
                'shipping' => (float) ($row['shipping'] ?? 0),
                'tax' => (float) ($row['tax'] ?? 0),
                'profit' => (float) $profit,
            ];
        }
    } catch (Throwable) {
    }

    $orders = admin_orders_session_items();
    $summary = ['orders_count' => 0, 'revenue' => 0.0, 'discounts' => 0.0, 'shipping' => 0.0, 'tax' => 0.0, 'profit' => 0.0];

    foreach ($orders as $order) {
        if (in_array($order['status'], ['cancelled', 'refunded'], true)) {
            continue;
        }

        $summary['orders_count']++;
        $summary['revenue'] += (float) $order['grand_total'];
        $summary['discounts'] += (float) $order['discount_total'];
        $summary['shipping'] += (float) $order['shipping_total'];
        $summary['tax'] += (float) $order['tax_total'];
        $summary['profit'] += (float) $order['grand_total'] * 0.35;
    }

    return $summary;
}

function admin_report_monthly_sales(): array
{
    try {
        if (db_available()) {
            $stmt = db()->query(
                'SELECT DATE_FORMAT(created_at, "%Y-%m") AS period,
                    COUNT(*) AS orders_count,
                    COALESCE(SUM(grand_total), 0) AS revenue
                 FROM orders
                 WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                   AND status NOT IN ("cancelled", "refunded")
                 GROUP BY period
                 ORDER BY period ASC'
            );

            return $stmt->fetchAll();
        }
    } catch (Throwable) {
    }

    return admin_report_group_session_orders(static fn (array $order): string => substr((string) $order['created_at'], 0, 7));
}

function admin_report_status_breakdown(): array
{
    try {
        if (db_available()) {
            $stmt = db()->query(
                'SELECT status, COUNT(*) AS orders_count, COALESCE(SUM(grand_total), 0) AS revenue
                 FROM orders
                 GROUP BY status
                 ORDER BY orders_count DESC'
            );

            return $stmt->fetchAll();
        }
    } catch (Throwable) {
    }

    return admin_report_group_session_orders(static fn (array $order): string => (string) $order['status'], 'status');
}

function admin_report_methods(string $type): array
{
    $allowed = [
        'payment' => ['table' => 'payment_methods', 'column' => 'payment_method_id'],
        'shipping' => ['table' => 'shipping_methods', 'column' => 'shipping_method_id'],
    ];

    if (!isset($allowed[$type])) {
        return [];
    }

    try {
        if (db_available()) {
            $table = $allowed[$type]['table'];
            $column = $allowed[$type]['column'];
            $stmt = db()->query(
                'SELECT COALESCE(m.name, "Sem metodo") AS label,
                    COUNT(o.id) AS orders_count,
                    COALESCE(SUM(o.grand_total), 0) AS revenue
                 FROM orders o
                 LEFT JOIN ' . $table . ' m ON m.id = o.' . $column . '
                 GROUP BY label
                 ORDER BY orders_count DESC'
            );

            return $stmt->fetchAll();
        }
    } catch (Throwable) {
    }

    return [];
}

function admin_report_product_margins(): array
{
    try {
        if (db_available()) {
            $stmt = db()->query(
                'SELECT
                    p.name,
                    p.sku,
                    COALESCE(SUM(oi.quantity), 0) AS units_sold,
                    COALESCE(SUM(oi.line_total), 0) AS revenue,
                    COALESCE(SUM((oi.unit_price - COALESCE(p.cost_price, 0)) * oi.quantity), 0) AS profit
                 FROM products p
                 LEFT JOIN (order_items oi INNER JOIN orders o ON o.id = oi.order_id AND o.status NOT IN ("cancelled", "refunded")) ON oi.product_id = p.id
                 GROUP BY p.id, p.name, p.sku
                 ORDER BY revenue DESC
                 LIMIT 10'
            );

            return $stmt->fetchAll();
        }
    } catch (Throwable) {
    }

    return array_map(static function (array $product): array {
        $revenue = (float) ($product['final_price'] ?? $product['price'] ?? 0) * 3;

        return [
            'name' => $product['name'],
            'sku' => $product['sku'],
            'units_sold' => !empty($product['is_best_seller']) ? 8 : 3,
            'revenue' => $revenue,
            'profit' => $revenue * 0.35,
        ];
    }, array_slice(catalog_fallback_products(), 0, 8));
}

function admin_report_group_session_orders(callable $keyResolver, string $keyName = 'period'): array
{
    $groups = [];

    foreach (admin_orders_session_items() as $order) {
        $key = (string) $keyResolver($order);

        if (!isset($groups[$key])) {
            $groups[$key] = [$keyName => $key, 'orders_count' => 0, 'revenue' => 0.0];
        }

        $groups[$key]['orders_count']++;
        $groups[$key]['revenue'] += (float) $order['grand_total'];
    }

    ksort($groups);

    return array_values($groups);
}

function admin_report_max_value(array $rows, string $field): float
{
    $values = array_map(static fn (array $row): float => (float) ($row[$field] ?? 0), $rows);

    return $values === [] ? 1.0 : max(1.0, max($values));
}

function admin_reports_export_csv(array $rows, array $headers, string $filename): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);

    foreach ($rows as $row) {
        fputcsv($output, array_map(static fn (string $key): mixed => $row[$key] ?? '', $headers));
    }

    exit;
}
