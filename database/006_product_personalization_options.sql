USE ppcosta_store;

CREATE TABLE IF NOT EXISTS product_personalization_options (
    product_personalization_id BIGINT UNSIGNED NOT NULL,
    personalization_option_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (product_personalization_id, personalization_option_id),
    CONSTRAINT fk_product_personalization_options_rule FOREIGN KEY (product_personalization_id) REFERENCES product_personalizations(id) ON DELETE CASCADE,
    CONSTRAINT fk_product_personalization_options_option FOREIGN KEY (personalization_option_id) REFERENCES personalization_options(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO product_personalization_options (product_personalization_id, personalization_option_id)
SELECT pp.id, po.id
FROM product_personalizations pp
INNER JOIN products p ON p.id = pp.product_id
INNER JOIN personalization_types pt ON pt.id = pp.personalization_type_id AND pt.slug = 'tecnica'
INNER JOIN product_categories pc ON pc.product_id = p.id AND pc.is_primary = 1
INNER JOIN categories c ON c.id = pc.category_id
INNER JOIN personalization_options po ON po.personalization_type_id = pt.id
WHERE po.is_active = 1
  AND (
    (c.slug = 'canecas' AND po.value = 'sublimation')
    OR (c.slug = 'bebe' AND po.value IN ('embroidery', 'dtf', 'vinyl'))
    OR (c.slug = 'casa' AND po.value IN ('embroidery', 'dtf', 'vinyl', 'sublimation'))
    OR (c.slug = 'roupa' AND po.value IN ('dtf', 'vinyl', 'sublimation'))
    OR (c.slug = 'empresas' AND po.value IN ('dtf', 'vinyl', 'sublimation', 'laser', 'uv'))
    OR (c.slug = 'brindes' AND po.value IN ('laser', 'uv', 'sublimation'))
  );
