USE ppcosta_store;

DROP TRIGGER IF EXISTS trg_orders_after_insert_status;
DROP TRIGGER IF EXISTS trg_orders_after_update_status;
DROP TRIGGER IF EXISTS trg_stock_movements_after_insert;
DROP PROCEDURE IF EXISTS sp_create_stock_movement;
DROP PROCEDURE IF EXISTS sp_recalculate_order_totals;

CREATE OR REPLACE VIEW vw_active_products AS
SELECT
    p.id,
    p.name,
    p.slug,
    p.sku,
    p.price,
    p.sale_price,
    COALESCE(p.sale_price, p.price) AS final_price,
    p.stock,
    p.is_new,
    p.is_featured,
    p.is_on_sale,
    p.is_best_seller,
    p.is_personalizable,
    b.name AS brand_name,
    tr.rate AS tax_rate
FROM products p
LEFT JOIN brands b ON b.id = p.brand_id
INNER JOIN tax_rates tr ON tr.id = p.tax_rate_id
WHERE p.is_active = 1;

CREATE OR REPLACE VIEW vw_order_summary AS
SELECT
    o.id,
    o.order_number,
    o.status,
    o.customer_email,
    o.subtotal,
    o.discount_total,
    o.shipping_total,
    o.tax_total,
    o.grand_total,
    o.created_at,
    COUNT(oi.id) AS total_items,
    COALESCE(SUM(oi.quantity), 0) AS total_units
FROM orders o
LEFT JOIN order_items oi ON oi.order_id = o.id
GROUP BY
    o.id,
    o.order_number,
    o.status,
    o.customer_email,
    o.subtotal,
    o.discount_total,
    o.shipping_total,
    o.tax_total,
    o.grand_total,
    o.created_at;

CREATE OR REPLACE VIEW vw_best_sellers AS
SELECT
    p.id,
    p.name,
    p.slug,
    p.sku,
    COALESCE(SUM(oi.quantity), 0) AS units_sold,
    COALESCE(SUM(oi.line_total), 0) AS revenue
FROM products p
LEFT JOIN order_items oi ON oi.product_id = p.id
LEFT JOIN orders o ON o.id = oi.order_id AND o.status NOT IN ('cancelled','refunded')
GROUP BY p.id, p.name, p.slug, p.sku
ORDER BY units_sold DESC, revenue DESC;

CREATE OR REPLACE VIEW vw_low_stock AS
SELECT
    id,
    name,
    sku,
    stock,
    stock_minimum
FROM products
WHERE stock <= stock_minimum
  AND is_active = 1;

DELIMITER $$

CREATE TRIGGER trg_orders_after_insert_status
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    INSERT INTO order_status_history (order_id, status, note)
    VALUES (NEW.id, NEW.status, 'Encomenda criada');
END$$

CREATE TRIGGER trg_orders_after_update_status
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status <> OLD.status THEN
        INSERT INTO order_status_history (order_id, status, note)
        VALUES (NEW.id, NEW.status, 'Estado atualizado');
    END IF;
END$$

CREATE TRIGGER trg_stock_movements_after_insert
AFTER INSERT ON stock_movements
FOR EACH ROW
BEGIN
    IF NEW.variation_id IS NULL THEN
        UPDATE products
        SET stock = stock + NEW.quantity
        WHERE id = NEW.product_id;
    ELSE
        UPDATE product_variations
        SET stock = stock + NEW.quantity
        WHERE id = NEW.variation_id;
    END IF;
END$$

CREATE PROCEDURE sp_create_stock_movement (
    IN p_product_id BIGINT UNSIGNED,
    IN p_variation_id BIGINT UNSIGNED,
    IN p_user_id BIGINT UNSIGNED,
    IN p_type VARCHAR(30),
    IN p_quantity INT,
    IN p_reason VARCHAR(190),
    IN p_reference_type VARCHAR(80),
    IN p_reference_id BIGINT UNSIGNED
)
BEGIN
    IF p_quantity = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A quantidade nao pode ser zero';
    END IF;

    INSERT INTO stock_movements (
        product_id,
        variation_id,
        user_id,
        type,
        quantity,
        reason,
        reference_type,
        reference_id
    ) VALUES (
        p_product_id,
        p_variation_id,
        p_user_id,
        p_type,
        p_quantity,
        p_reason,
        p_reference_type,
        p_reference_id
    );
END$$

CREATE PROCEDURE sp_recalculate_order_totals (
    IN p_order_id BIGINT UNSIGNED
)
BEGIN
    DECLARE v_subtotal DECIMAL(12,2) DEFAULT 0;
    DECLARE v_tax_total DECIMAL(12,2) DEFAULT 0;

    SELECT
        COALESCE(SUM(line_total), 0),
        COALESCE(SUM(line_total - (line_total / (1 + (tax_rate / 100)))), 0)
    INTO v_subtotal, v_tax_total
    FROM order_items
    WHERE order_id = p_order_id;

    UPDATE orders
    SET
        subtotal = v_subtotal,
        tax_total = v_tax_total,
        grand_total = v_subtotal - discount_total + shipping_total
    WHERE id = p_order_id;
END$$

DELIMITER ;
