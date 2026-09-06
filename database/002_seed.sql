USE ppcosta_store;

INSERT INTO roles (name, slug) VALUES
('Administrador', 'admin'),
('Gestor', 'manager'),
('Cliente', 'customer');

INSERT INTO tax_rates (name, rate, country_code, is_default, is_active) VALUES
('IVA normal PT', 23.00, 'PT', 1, 1),
('IVA intermedio PT', 13.00, 'PT', 0, 1),
('IVA reduzido PT', 6.00, 'PT', 0, 1);

INSERT INTO payment_methods (name, code, sort_order, is_active) VALUES
('MB Way', 'mbway', 10, 1),
('Multibanco', 'multibanco', 20, 1),
('PayPal', 'paypal', 30, 1),
('Stripe', 'stripe', 40, 1),
('Cartao', 'card', 50, 1),
('Transferencia Bancaria', 'bank_transfer', 60, 1),
('Contra Reembolso', 'cash_on_delivery', 70, 1);

INSERT INTO shipping_methods (name, code, sort_order, is_active) VALUES
('CTT', 'ctt', 10, 1),
('DPD', 'dpd', 20, 1),
('MRW', 'mrw', 30, 1),
('GLS', 'gls', 40, 1),
('DHL', 'dhl', 50, 1),
('UPS', 'ups', 60, 1),
('Levantamento em loja', 'pickup', 70, 1),
('Portes gratuitos', 'free_shipping', 80, 1);

INSERT INTO categories (name, slug, sort_order, is_active) VALUES
('Bebe', 'bebe', 10, 1),
('Roupa', 'roupa', 20, 1),
('Casa', 'casa', 30, 1),
('Canecas', 'canecas', 40, 1),
('Empresas', 'empresas', 50, 1),
('Brindes', 'brindes', 60, 1);

INSERT INTO personalization_types (name, slug, input_type, is_required, is_active) VALUES
('Nome', 'nome', 'text', 0, 1),
('Texto', 'texto', 'textarea', 0, 1),
('Fonte', 'fonte', 'font', 0, 1),
('Cor', 'cor', 'color', 0, 1),
('Tamanho', 'tamanho', 'select', 0, 1),
('Posicao', 'posicao', 'position', 0, 1),
('Tecnica', 'tecnica', 'technique', 0, 1),
('Ficheiro', 'ficheiro', 'file', 0, 1);

INSERT INTO personalization_options (personalization_type_id, label, value, extra_price, color_hex, sort_order, is_active)
SELECT id, 'Preto', 'black', 0, '#111827', 10, 1 FROM personalization_types WHERE slug = 'cor'
UNION ALL SELECT id, 'Branco', 'white', 0, '#ffffff', 20, 1 FROM personalization_types WHERE slug = 'cor'
UNION ALL SELECT id, 'Dourado', 'gold', 1.50, '#b45309', 30, 1 FROM personalization_types WHERE slug = 'cor'
UNION ALL SELECT id, 'Bordado', 'embroidery', 4.00, NULL, 10, 1 FROM personalization_types WHERE slug = 'tecnica'
UNION ALL SELECT id, 'DTF', 'dtf', 3.00, NULL, 20, 1 FROM personalization_types WHERE slug = 'tecnica'
UNION ALL SELECT id, 'Vinil', 'vinyl', 2.50, NULL, 30, 1 FROM personalization_types WHERE slug = 'tecnica'
UNION ALL SELECT id, 'Sublimacao', 'sublimation', 3.50, NULL, 40, 1 FROM personalization_types WHERE slug = 'tecnica'
UNION ALL SELECT id, 'Laser', 'laser', 4.50, NULL, 50, 1 FROM personalization_types WHERE slug = 'tecnica'
UNION ALL SELECT id, 'UV', 'uv', 4.00, NULL, 60, 1 FROM personalization_types WHERE slug = 'tecnica';

INSERT INTO attributes (name, slug, type) VALUES
('Cor', 'cor', 'color'),
('Tamanho', 'tamanho', 'size'),
('Material', 'material', 'material');

INSERT INTO settings (setting_key, setting_value, value_type, is_public) VALUES
('store_name', 'PPCosta', 'string', 1),
('store_email', 'geral@example.com', 'string', 1),
('currency', 'EUR', 'string', 1),
('free_shipping_threshold', '75', 'number', 1),
('maintenance_mode', '0', 'boolean', 0);

INSERT INTO brands (name, slug, description, is_active) VALUES
('PPCosta Studio', 'ppcosta-studio', 'Marca propria de artigos personalizados.', 1);

INSERT INTO suppliers (name, contact_name, email, is_active) VALUES
('Fornecedor Base', 'Equipa Comercial', 'fornecedor@example.com', 1);

INSERT INTO products (
    brand_id,
    supplier_id,
    tax_rate_id,
    name,
    slug,
    short_description,
    long_description,
    sku,
    price,
    sale_price,
    cost_price,
    stock,
    stock_minimum,
    weight_grams,
    is_active,
    is_new,
    is_featured,
    is_on_sale,
    is_best_seller,
    is_personalizable,
    meta_title,
    meta_description
)
SELECT
    b.id,
    s.id,
    tr.id,
    seed.name,
    seed.slug,
    seed.short_description,
    seed.long_description,
    seed.sku,
    seed.price,
    seed.sale_price,
    seed.cost_price,
    seed.stock,
    seed.stock_minimum,
    seed.weight_grams,
    1,
    seed.is_new,
    seed.is_featured,
    seed.is_on_sale,
    seed.is_best_seller,
    1,
    seed.name,
    seed.short_description
FROM (
    SELECT 'Body bebe bordado' AS name, 'body-bebe-bordado' AS slug, 'Body em algodao com nome bordado.' AS short_description, 'Body de bebe em algodao macio, ideal para nascimento, batizado e presentes personalizados.' AS long_description, 'BB-BODY-001' AS sku, 24.90 AS price, 19.90 AS sale_price, 8.50 AS cost_price, 18 AS stock, 3 AS stock_minimum, 180 AS weight_grams, 1 AS is_new, 1 AS is_featured, 1 AS is_on_sale, 1 AS is_best_seller, 'bebe' AS category_slug
    UNION ALL SELECT 'Caneca personalizada', 'caneca-personalizada', 'Caneca com fotografia, frase ou logotipo.', 'Caneca em ceramica preparada para sublimacao de alta definicao.', 'CN-SUB-001', 12.50, NULL, 3.20, 42, 8, 360, 0, 1, 0, 1, 'canecas'
    UNION ALL SELECT 'Hoodie estampado', 'hoodie-estampado', 'Hoodie com estampagem DTF ou vinil.', 'Hoodie confortavel com estampagem frontal, dorsal ou manga.', 'HD-DTF-001', 39.90, 34.90, 16.00, 12, 4, 650, 1, 1, 1, 0, 'roupa'
    UNION ALL SELECT 'Saco algodao personalizado', 'saco-algodao-personalizado', 'Saco para eventos, lojas e brindes.', 'Saco de algodao reutilizavel com logotipo, frase ou arte final.', 'SC-VIN-001', 8.90, NULL, 2.10, 65, 15, 120, 0, 1, 0, 1, 'empresas'
    UNION ALL SELECT 'Manta personalizada', 'manta-personalizada', 'Manta com bordado lateral.', 'Manta suave com bordado de nome, monograma ou data.', 'MT-BOR-001', 29.90, NULL, 12.00, 9, 3, 480, 1, 0, 0, 0, 'casa'
    UNION ALL SELECT 'Porta-chaves acrilico', 'porta-chaves-acrilico', 'Brinde com impressao UV.', 'Porta-chaves em acrilico com impressao UV para campanhas e lembrancas.', 'PC-UV-001', 5.90, NULL, 1.10, 120, 30, 35, 1, 0, 0, 0, 'brindes'
) seed
CROSS JOIN brands b
CROSS JOIN suppliers s
CROSS JOIN tax_rates tr
WHERE b.slug = 'ppcosta-studio'
  AND s.name = 'Fornecedor Base'
  AND tr.is_default = 1;

INSERT INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
INNER JOIN (
    SELECT 'BB-BODY-001' AS sku, 'bebe' AS category_slug
    UNION ALL SELECT 'CN-SUB-001', 'canecas'
    UNION ALL SELECT 'HD-DTF-001', 'roupa'
    UNION ALL SELECT 'SC-VIN-001', 'empresas'
    UNION ALL SELECT 'MT-BOR-001', 'casa'
    UNION ALL SELECT 'PC-UV-001', 'brindes'
) map ON map.sku = p.sku
INNER JOIN categories c ON c.slug = map.category_slug;

INSERT INTO product_personalizations (
    product_id,
    personalization_type_id,
    label,
    min_length,
    max_length,
    allowed_file_types,
    max_file_bytes,
    base_extra_price,
    sort_order,
    is_required,
    is_active
)
SELECT
    p.id,
    pt.id,
    rules.label,
    rules.min_length,
    rules.max_length,
    rules.allowed_file_types,
    rules.max_file_bytes,
    rules.base_extra_price,
    rules.sort_order,
    rules.is_required,
    1
FROM products p
INNER JOIN (
    SELECT 'nome' AS slug, 'Nome' AS label, 1 AS min_length, 28 AS max_length, NULL AS allowed_file_types, NULL AS max_file_bytes, 2.50 AS base_extra_price, 10 AS sort_order, 0 AS is_required
    UNION ALL SELECT 'texto', 'Texto adicional', 0, 90, NULL, NULL, 3.00, 20, 0
    UNION ALL SELECT 'fonte', 'Fonte', NULL, NULL, NULL, NULL, 0.00, 30, 0
    UNION ALL SELECT 'cor', 'Cor', NULL, NULL, NULL, NULL, 0.00, 40, 0
    UNION ALL SELECT 'tamanho', 'Tamanho da personalizacao', NULL, NULL, NULL, NULL, 0.00, 50, 0
    UNION ALL SELECT 'posicao', 'Posicao', NULL, NULL, NULL, NULL, 0.00, 60, 0
    UNION ALL SELECT 'tecnica', 'Tecnica', NULL, NULL, NULL, NULL, 0.00, 70, 1
    UNION ALL SELECT 'ficheiro', 'Imagem, logotipo ou ficheiro', NULL, NULL, 'png,svg,pdf,jpg,jpeg', 10485760, 2.00, 80, 0
) rules
INNER JOIN personalization_types pt ON pt.slug = rules.slug
WHERE p.is_personalizable = 1;
