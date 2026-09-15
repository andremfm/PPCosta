CREATE OR REPLACE VIEW vw_best_sellers AS
SELECT p.id, p.name, p.slug, p.sku,
       COALESCE(SUM(oi.quantity), 0) AS units_sold,
       COALESCE(SUM(oi.line_total), 0) AS revenue
FROM products p
LEFT JOIN (order_items oi INNER JOIN orders o
    ON o.id = oi.order_id AND o.status NOT IN ('cancelled', 'refunded'))
    ON oi.product_id = p.id
GROUP BY p.id, p.name, p.slug, p.sku
ORDER BY units_sold DESC, revenue DESC;
