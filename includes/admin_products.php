<?php
declare(strict_types=1);

require_once __DIR__ . '/admin.php';

function admin_product_defaults(): array
{
    return [
        'id' => '',
        'name' => '',
        'slug' => '',
        'short_description' => '',
        'long_description' => '',
        'sku' => '',
        'ean' => '',
        'price' => '0.00',
        'sale_price' => '',
        'cost_price' => '',
        'stock' => '0',
        'stock_minimum' => '0',
        'weight_grams' => '',
        'width_mm' => '',
        'height_mm' => '',
        'depth_mm' => '',
        'category_slug' => '',
        'brand_name' => '',
        'supplier_name' => '',
        'video_url' => '',
        'meta_title' => '',
        'meta_description' => '',
        'is_active' => 1,
        'is_new' => 0,
        'is_featured' => 0,
        'is_on_sale' => 0,
        'is_best_seller' => 0,
        'is_personalizable' => 1,
        'techniques' => [],
    ];
}

function admin_session_products(): array
{
    if (!isset($_SESSION['admin_products'])) {
        $_SESSION['admin_products'] = catalog_fallback_products();
    }

    return $_SESSION['admin_products'];
}

function admin_save_session_products(array $products): void
{
    $_SESSION['admin_products'] = array_values($products);
}

function admin_products_all(): array
{
    try {
        if (db_available()) {
            $stmt = db()->query(
                'SELECT
                    p.id,
                    p.name,
                    p.slug,
                    p.short_description,
                    p.long_description,
                    p.sku,
                    p.price,
                    p.sale_price,
                    COALESCE(p.sale_price, p.price) AS final_price,
                    p.stock,
                    p.stock_minimum,
                    p.is_active,
                    p.is_new,
                    p.is_featured,
                    p.is_on_sale,
                    p.is_best_seller,
                    p.is_personalizable,
                    c.slug AS category_slug,
                    c.name AS category_name
                 FROM products p
                 LEFT JOIN product_categories pc ON pc.product_id = p.id AND pc.is_primary = 1
                 LEFT JOIN categories c ON c.id = pc.category_id
                 ORDER BY p.created_at DESC, p.id DESC'
            );

            return array_map('catalog_normalize_product', $stmt->fetchAll());
        }
    } catch (Throwable) {
    }

    return admin_session_products();
}

function admin_product_find(string|int $id): ?array
{
    try {
        if (db_available()) {
            $stmt = db()->prepare(
                'SELECT
                    p.*,
                    COALESCE(p.sale_price, p.price) AS final_price,
                    c.slug AS category_slug,
                    c.name AS category_name,
                    tr.rate AS tax_rate,
                    b.name AS brand_name,
                    s.name AS supplier_name
                 FROM products p
                 LEFT JOIN product_categories pc ON pc.product_id = p.id AND pc.is_primary = 1
                 LEFT JOIN categories c ON c.id = pc.category_id
                 LEFT JOIN tax_rates tr ON tr.id = p.tax_rate_id
                 LEFT JOIN brands b ON b.id = p.brand_id
                 LEFT JOIN suppliers s ON s.id = p.supplier_id
                 WHERE p.id = :id
                 LIMIT 1'
            );
            $stmt->execute(['id' => (int) $id]);
            $product = $stmt->fetch();

            if ($product) {
                return array_merge(admin_product_defaults(), catalog_normalize_product($product));
            }
        }
    } catch (Throwable) {
    }

    foreach (admin_session_products() as $product) {
        if ((string) $product['id'] === (string) $id) {
            return array_merge(admin_product_defaults(), $product);
        }
    }

    return null;
}

function admin_product_validate(array $data): array
{
    $errors = [];

    foreach (['name' => 'Nome', 'sku' => 'SKU', 'price' => 'Preco'] as $field => $label) {
        if (trim((string) ($data[$field] ?? '')) === '') {
            $errors[] = $label . ' e obrigatorio.';
        }
    }

    if ((float) ($data['price'] ?? 0) < 0) {
        $errors[] = 'O preco nao pode ser negativo.';
    }

    if (($data['sale_price'] ?? '') !== '' && (float) $data['sale_price'] < 0) {
        $errors[] = 'O preco promocional nao pode ser negativo.';
    }

    if ((int) ($data['stock'] ?? 0) < 0) {
        $errors[] = 'O stock nao pode ser negativo.';
    }

    if (empty($data['id']) && !empty($data['is_personalizable']) && empty($data['techniques'])) {
        $errors[] = 'Escolhe pelo menos uma tecnica de personalizacao para produtos personalizaveis.';
    }

    return $errors;
}

function admin_product_payload(array $data): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $slug = trim((string) ($data['slug'] ?? ''));

    if ($slug === '') {
        $slug = slugify($name);
    }

    return array_merge(admin_product_defaults(), [
        'id' => trim((string) ($data['id'] ?? '')),
        'name' => $name,
        'slug' => $slug,
        'short_description' => trim((string) ($data['short_description'] ?? '')),
        'long_description' => trim((string) ($data['long_description'] ?? '')),
        'sku' => strtoupper(trim((string) ($data['sku'] ?? ''))),
        'ean' => trim((string) ($data['ean'] ?? '')),
        'price' => number_format((float) ($data['price'] ?? 0), 2, '.', ''),
        'sale_price' => trim((string) ($data['sale_price'] ?? '')),
        'cost_price' => trim((string) ($data['cost_price'] ?? '')),
        'final_price' => ($data['sale_price'] ?? '') !== '' ? (float) $data['sale_price'] : (float) ($data['price'] ?? 0),
        'stock' => max(0, (int) ($data['stock'] ?? 0)),
        'stock_minimum' => max(0, (int) ($data['stock_minimum'] ?? 0)),
        'weight_grams' => trim((string) ($data['weight_grams'] ?? '')),
        'width_mm' => trim((string) ($data['width_mm'] ?? '')),
        'height_mm' => trim((string) ($data['height_mm'] ?? '')),
        'depth_mm' => trim((string) ($data['depth_mm'] ?? '')),
        'category_slug' => trim((string) ($data['category_slug'] ?? '')),
        'category_name' => admin_category_name(trim((string) ($data['category_slug'] ?? ''))),
        'brand_name' => trim((string) ($data['brand_name'] ?? '')),
        'supplier_name' => trim((string) ($data['supplier_name'] ?? '')),
        'video_url' => trim((string) ($data['video_url'] ?? '')),
        'meta_title' => trim((string) ($data['meta_title'] ?? '')),
        'meta_description' => trim((string) ($data['meta_description'] ?? '')),
        'is_active' => !empty($data['is_active']) ? 1 : 0,
        'is_new' => !empty($data['is_new']) ? 1 : 0,
        'is_featured' => !empty($data['is_featured']) ? 1 : 0,
        'is_on_sale' => !empty($data['is_on_sale']) ? 1 : 0,
        'is_best_seller' => !empty($data['is_best_seller']) ? 1 : 0,
        'is_personalizable' => !empty($data['is_personalizable']) ? 1 : 0,
        'techniques' => is_array($data['techniques'] ?? null) ? array_values(array_filter(array_map('strval', $data['techniques']))) : [],
    ]);
}

function admin_category_name(string $slug): string
{
    foreach (catalog_categories() as $category) {
        if ($category['slug'] === $slug) {
            return $category['name'];
        }
    }

    return '';
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';

    return trim($value, '-') ?: bin2hex(random_bytes(4));
}

function admin_product_save(array $data): bool
{
    $payload = admin_product_payload($data);

    try {
        $pdo = db();
        $pdo->beginTransaction();
        $brandId = admin_find_or_create_brand($payload['brand_name']);
        $supplierId = admin_find_or_create_supplier($payload['supplier_name']);
        $taxRateId = (int) admin_metric_value('SELECT id FROM tax_rates WHERE is_default = 1 LIMIT 1', 1);

        if ($payload['id'] !== '') {
            $stmt = $pdo->prepare(
                'UPDATE products
                 SET brand_id = :brand_id, supplier_id = :supplier_id, tax_rate_id = :tax_rate_id,
                     name = :name, slug = :slug, short_description = :short_description,
                     long_description = :long_description, sku = :sku, ean = :ean, price = :price,
                     sale_price = :sale_price, cost_price = :cost_price, stock = :stock,
                     stock_minimum = :stock_minimum, weight_grams = :weight_grams,
                     width_mm = :width_mm, height_mm = :height_mm, depth_mm = :depth_mm,
                     video_url = :video_url, is_active = :is_active, is_new = :is_new,
                     is_featured = :is_featured, is_on_sale = :is_on_sale,
                     is_best_seller = :is_best_seller, is_personalizable = :is_personalizable,
                     meta_title = :meta_title, meta_description = :meta_description
                 WHERE id = :id'
            );
            $params = admin_product_db_params($payload) + ['id' => (int) $payload['id'], 'brand_id' => $brandId, 'supplier_id' => $supplierId, 'tax_rate_id' => $taxRateId];
            $stmt->execute($params);
            $productId = (int) $payload['id'];
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO products (
                    brand_id, supplier_id, tax_rate_id, name, slug, short_description,
                    long_description, sku, ean, price, sale_price, cost_price, stock,
                    stock_minimum, weight_grams, width_mm, height_mm, depth_mm, video_url,
                    is_active, is_new, is_featured, is_on_sale, is_best_seller,
                    is_personalizable, meta_title, meta_description
                 ) VALUES (
                    :brand_id, :supplier_id, :tax_rate_id, :name, :slug, :short_description,
                    :long_description, :sku, :ean, :price, :sale_price, :cost_price, :stock,
                    :stock_minimum, :weight_grams, :width_mm, :height_mm, :depth_mm, :video_url,
                    :is_active, :is_new, :is_featured, :is_on_sale, :is_best_seller,
                    :is_personalizable, :meta_title, :meta_description
                 )'
            );
            $params = admin_product_db_params($payload) + ['brand_id' => $brandId, 'supplier_id' => $supplierId, 'tax_rate_id' => $taxRateId];
            $stmt->execute($params);
            $productId = (int) $pdo->lastInsertId();
        }

        admin_product_sync_category($productId, $payload['category_slug']);
        if (!empty($data['copy_personalization_from']) && $payload['id'] === '') {
            require_once __DIR__ . '/admin_product_personalization.php';
            admin_product_personalization_copy((int) $data['copy_personalization_from'], $productId);
        } elseif ($payload['id'] === '') {
            admin_product_sync_techniques($productId, $payload['techniques'], (int) $payload['is_personalizable'] === 1);
        }
        $pdo->commit();

        return true;
    } catch (Throwable) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}

function admin_product_technique_options(): array
{
    return personalization_technique_options();
}

function admin_product_selected_techniques(int $productId): array
{
    if ($productId <= 0) {
        return [];
    }

    try {
        $stmt = db()->prepare(
            'SELECT po.value
             FROM product_personalizations pp
             INNER JOIN personalization_types pt ON pt.id = pp.personalization_type_id AND pt.slug = "tecnica"
             INNER JOIN product_personalization_options ppo ON ppo.product_personalization_id = pp.id
             INNER JOIN personalization_options po ON po.id = ppo.personalization_option_id
             WHERE pp.product_id = :product_id
             ORDER BY po.sort_order ASC, po.id ASC'
        );
        $stmt->execute(['product_id' => $productId]);
        $values = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map('strval', $values);
    } catch (Throwable) {
    }

    return [];
}

function admin_product_sync_techniques(int $productId, array $techniques, bool $isPersonalizable): void
{
    if ($productId <= 0) {
        return;
    }

    $pdo = db();
    $typeId = (int) $pdo->query('SELECT id FROM personalization_types WHERE slug = "tecnica" LIMIT 1')->fetchColumn();

    if ($typeId <= 0) {
        return;
    }

    $ruleStmt = $pdo->prepare('SELECT id FROM product_personalizations WHERE product_id = :product_id AND personalization_type_id = :type_id LIMIT 1');
    $ruleStmt->execute(['product_id' => $productId, 'type_id' => $typeId]);
    $ruleId = (int) $ruleStmt->fetchColumn();

    if ($ruleId <= 0) {
        $insertRule = $pdo->prepare(
            'INSERT INTO product_personalizations (
                product_id, personalization_type_id, label, base_extra_price, sort_order, is_required, is_active
             ) VALUES (
                :product_id, :type_id, :label, 0.00, 70, 1, :is_active
             )'
        );
        $insertRule->execute([
            'product_id' => $productId,
            'type_id' => $typeId,
            'label' => 'Tecnica',
            'is_active' => $isPersonalizable ? 1 : 0,
        ]);
        $ruleId = (int) $pdo->lastInsertId();
    } else {
        $pdo->prepare('UPDATE product_personalizations SET is_required = 1, is_active = :is_active WHERE id = :id')
            ->execute(['is_active' => $isPersonalizable ? 1 : 0, 'id' => $ruleId]);
    }

    $pdo->prepare('DELETE FROM product_personalization_options WHERE product_personalization_id = :rule_id')->execute(['rule_id' => $ruleId]);

    if (!$isPersonalizable || $techniques === []) {
        return;
    }

    $optionStmt = $pdo->prepare(
        'SELECT po.id
         FROM personalization_options po
         INNER JOIN personalization_types pt ON pt.id = po.personalization_type_id
         WHERE pt.slug = "tecnica" AND po.value = :value AND po.is_active = 1
         LIMIT 1'
    );
    $insertOption = $pdo->prepare(
        'INSERT IGNORE INTO product_personalization_options (product_personalization_id, personalization_option_id)
         VALUES (:rule_id, :option_id)'
    );

    foreach (array_unique($techniques) as $technique) {
        $optionStmt->execute(['value' => (string) $technique]);
        $optionId = (int) $optionStmt->fetchColumn();

        if ($optionId > 0) {
            $insertOption->execute(['rule_id' => $ruleId, 'option_id' => $optionId]);
        }
    }
}

function admin_product_db_params(array $payload): array
{
    return [
        'name' => $payload['name'],
        'slug' => $payload['slug'],
        'short_description' => $payload['short_description'],
        'long_description' => $payload['long_description'],
        'sku' => $payload['sku'],
        'ean' => $payload['ean'] !== '' ? $payload['ean'] : null,
        'price' => (float) $payload['price'],
        'sale_price' => $payload['sale_price'] !== '' ? (float) $payload['sale_price'] : null,
        'cost_price' => $payload['cost_price'] !== '' ? (float) $payload['cost_price'] : null,
        'stock' => (int) $payload['stock'],
        'stock_minimum' => (int) $payload['stock_minimum'],
        'weight_grams' => $payload['weight_grams'] !== '' ? (int) $payload['weight_grams'] : null,
        'width_mm' => $payload['width_mm'] !== '' ? (int) $payload['width_mm'] : null,
        'height_mm' => $payload['height_mm'] !== '' ? (int) $payload['height_mm'] : null,
        'depth_mm' => $payload['depth_mm'] !== '' ? (int) $payload['depth_mm'] : null,
        'video_url' => $payload['video_url'] !== '' ? $payload['video_url'] : null,
        'is_active' => (int) $payload['is_active'],
        'is_new' => (int) $payload['is_new'],
        'is_featured' => (int) $payload['is_featured'],
        'is_on_sale' => (int) $payload['is_on_sale'],
        'is_best_seller' => (int) $payload['is_best_seller'],
        'is_personalizable' => (int) $payload['is_personalizable'],
        'meta_title' => $payload['meta_title'],
        'meta_description' => $payload['meta_description'],
    ];
}

function admin_find_or_create_brand(string $name): ?int
{
    if ($name === '') {
        return null;
    }

    $slug = slugify($name);
    $stmt = db()->prepare('SELECT id FROM brands WHERE slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    $id = $stmt->fetchColumn();

    if ($id) {
        return (int) $id;
    }

    $stmt = db()->prepare('INSERT INTO brands (name, slug) VALUES (:name, :slug)');
    $stmt->execute(['name' => $name, 'slug' => $slug]);

    return (int) db()->lastInsertId();
}

function admin_find_or_create_supplier(string $name): ?int
{
    if ($name === '') {
        return null;
    }

    $stmt = db()->prepare('SELECT id FROM suppliers WHERE name = :name LIMIT 1');
    $stmt->execute(['name' => $name]);
    $id = $stmt->fetchColumn();

    if ($id) {
        return (int) $id;
    }

    $stmt = db()->prepare('INSERT INTO suppliers (name) VALUES (:name)');
    $stmt->execute(['name' => $name]);

    return (int) db()->lastInsertId();
}

function admin_product_sync_category(int $productId, string $categorySlug): void
{
    db()->prepare('DELETE FROM product_categories WHERE product_id = :product_id')->execute(['product_id' => $productId]);

    if ($categorySlug === '') {
        return;
    }

    $stmt = db()->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $categorySlug]);
    $categoryId = $stmt->fetchColumn();

    if ($categoryId) {
        db()->prepare('INSERT INTO product_categories (product_id, category_id, is_primary) VALUES (:product_id, :category_id, 1)')
            ->execute(['product_id' => $productId, 'category_id' => (int) $categoryId]);
    }
}

function admin_product_save_session(array $payload): bool
{
    $products = admin_session_products();

    if ($payload['id'] === '') {
        $payload['id'] = (string) (($products === [] ? 0 : max(array_map(static fn (array $product): int => (int) $product['id'], $products))) + 1);
        $products[] = $payload;
    } else {
        foreach ($products as $index => $product) {
            if ((string) $product['id'] === (string) $payload['id']) {
                $products[$index] = array_merge($product, $payload);
                admin_save_session_products($products);
                return true;
            }
        }

        $products[] = $payload;
    }

    admin_save_session_products($products);

    return true;
}

function admin_product_toggle(int $id): void
{
    db()->prepare('UPDATE products SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id')->execute(['id' => $id]);
}

function admin_product_duplicate(int $id): void
{
    $product = admin_product_find($id);

    if (!$product) {
        return;
    }

    $product['id'] = '';
    $product['name'] .= ' copia';
    $product['slug'] = slugify($product['name'] . '-' . bin2hex(random_bytes(2)));
    $product['sku'] .= '-COPY-' . strtoupper(bin2hex(random_bytes(3)));
    $product['techniques'] = admin_product_selected_techniques($id);
    $product['copy_personalization_from'] = $id;
    if (!admin_product_save($product)) throw new RuntimeException('Nao foi possivel duplicar o produto.');
}

function admin_product_delete(int $id): void
{
    db()->prepare('UPDATE products SET is_active = 0 WHERE id = :id')->execute(['id' => $id]);
}

function admin_product_images(int $productId): array
{
    if ($productId <= 0) {
        return [];
    }

    try {
        $stmt = db()->prepare('SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, sort_order ASC, id ASC');
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function admin_product_save_image(int $productId, array $file, string $altText, int $sortOrder, bool $isPrimary): bool
{
    if ($productId <= 0 || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }

    $mime = mime_content_type($file['tmp_name']) ?: '';
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    if (!isset($allowed[$mime]) || (int) ($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        return false;
    }

    $dir = UPLOAD_DIR . '/products';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = 'product_' . $productId . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $target = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return false;
    }

    $path = 'uploads/products/' . $filename;

    $isPrimary = $isPrimary || admin_product_images($productId) === [];
    if ($isPrimary) {
        db()->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id')->execute(['product_id' => $productId]);
    }

    $stmt = db()->prepare(
        'INSERT INTO product_images (product_id, path, alt_text, sort_order, is_primary)
         VALUES (:product_id, :path, :alt_text, :sort_order, :is_primary)'
    );
    $stmt->execute([
        'product_id' => $productId,
        'path' => $path,
        'alt_text' => trim($altText) !== '' ? trim($altText) : null,
        'sort_order' => $sortOrder,
        'is_primary' => $isPrimary ? 1 : 0,
    ]);

    return true;
}

function admin_product_set_primary_image(int $productId, int $imageId): void
{
    $stmt = db()->prepare('SELECT id FROM product_images WHERE id = :id AND product_id = :product_id');
    $stmt->execute(['id' => $imageId, 'product_id' => $productId]);
    if (!$stmt->fetchColumn()) throw new DomainException('Imagem inexistente.');
    db()->prepare('UPDATE product_images SET is_primary = (id = :id) WHERE product_id = :product_id')->execute(['id' => $imageId, 'product_id' => $productId]);
}

function admin_product_delete_image(int $productId, int $imageId): void
{
    $stmt = db()->prepare('SELECT path FROM product_images WHERE id = :id AND product_id = :product_id LIMIT 1');
    $stmt->execute(['id' => $imageId, 'product_id' => $productId]);
    $path = (string) ($stmt->fetchColumn() ?: '');
    db()->prepare('DELETE FROM product_images WHERE id = :id AND product_id = :product_id')->execute(['id' => $imageId, 'product_id' => $productId]);
    $remaining = admin_product_images($productId);
    if ($remaining !== [] && empty($remaining[0]['is_primary'])) {
        admin_product_set_primary_image($productId, (int) $remaining[0]['id']);
    }

    $absolute = __DIR__ . '/../' . $path;
    if (preg_match('#^uploads/products/product_[0-9]+_[a-f0-9]+\.(png|jpg|webp|gif)$#', $path) && is_file($absolute)) {
        unlink($absolute);
    }
}

function admin_product_variations(int $productId): array
{
    if ($productId <= 0) {
        return [];
    }

    try {
        $stmt = db()->prepare(
            'SELECT pv.*,
                GROUP_CONCAT(CONCAT(a.name, ": ", av.value) ORDER BY a.id SEPARATOR " · ") AS attributes_label
             FROM product_variations pv
             LEFT JOIN variation_attribute_values vav ON vav.variation_id = pv.id
             LEFT JOIN attribute_values av ON av.id = vav.attribute_value_id
             LEFT JOIN attributes a ON a.id = av.attribute_id
             WHERE pv.product_id = :product_id
             GROUP BY pv.id
             ORDER BY pv.id DESC'
        );
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function admin_attribute_id(string $slug, string $name, string $type): int
{
    $stmt = db()->prepare('SELECT id FROM attributes WHERE slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    $id = (int) $stmt->fetchColumn();

    if ($id > 0) {
        return $id;
    }

    $stmt = db()->prepare('INSERT INTO attributes (name, slug, type) VALUES (:name, :slug, :type)');
    $stmt->execute(['name' => $name, 'slug' => $slug, 'type' => $type]);
    return (int) db()->lastInsertId();
}

function admin_attribute_value_id(string $attributeSlug, string $attributeName, string $type, string $value): ?int
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $attributeId = admin_attribute_id($attributeSlug, $attributeName, $type);
    $slug = slugify($value);
    $stmt = db()->prepare('SELECT id FROM attribute_values WHERE attribute_id = :attribute_id AND slug = :slug LIMIT 1');
    $stmt->execute(['attribute_id' => $attributeId, 'slug' => $slug]);
    $id = (int) $stmt->fetchColumn();

    if ($id > 0) {
        return $id;
    }

    $stmt = db()->prepare('INSERT INTO attribute_values (attribute_id, value, slug) VALUES (:attribute_id, :value, :slug)');
    $stmt->execute(['attribute_id' => $attributeId, 'value' => $value, 'slug' => $slug]);
    return (int) db()->lastInsertId();
}

function admin_product_save_variation(int $productId, array $data): void
{
    if ($productId <= 0 || trim((string) ($data['sku'] ?? '')) === '') {
        throw new DomainException('Indica o SKU da variacao.');
    }

    $product = admin_product_find($productId);
    if (!$product || (float) ($product['sale_price'] ?? $product['price']) + (float) ($data['price_delta'] ?? 0) < 0 || (int) ($data['stock'] ?? 0) < 0) {
        throw new DomainException('Preco ou stock da variacao invalido.');
    }
    $variationId = (int) ($data['variation_id'] ?? 0);

    $pdo = db();
    $pdo->beginTransaction();

    try {
        if ($variationId > 0) {
            if (!admin_product_variation_find($productId, $variationId)) throw new DomainException('Variacao inexistente.');
            $stmt = $pdo->prepare('UPDATE product_variations SET sku = :sku, ean = :ean, price_delta = :price_delta, stock = :stock, weight_grams = :weight_grams, is_active = :is_active WHERE id = :id AND product_id = :product_id');
        } else {
            $stmt = $pdo->prepare(
            'INSERT INTO product_variations (product_id, sku, ean, price_delta, stock, weight_grams, is_active)
             VALUES (:product_id, :sku, :ean, :price_delta, :stock, :weight_grams, :is_active)'
            );
        }
        $stmt->execute([
            'product_id' => $productId,
            'sku' => strtoupper(trim((string) $data['sku'])),
            'ean' => trim((string) ($data['ean'] ?? '')) ?: null,
            'price_delta' => (float) ($data['price_delta'] ?? 0),
            'stock' => max(0, (int) ($data['stock'] ?? 0)),
            'weight_grams' => trim((string) ($data['weight_grams'] ?? '')) !== '' ? (int) $data['weight_grams'] : null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ] + ($variationId > 0 ? ['id' => $variationId] : []));
        $variationId = $variationId ?: (int) $pdo->lastInsertId();
        $pdo->prepare('DELETE vav FROM variation_attribute_values vav INNER JOIN attribute_values av ON av.id = vav.attribute_value_id INNER JOIN attributes a ON a.id = av.attribute_id WHERE vav.variation_id = :id AND a.slug IN ("size", "cor", "material")')->execute(['id' => $variationId]);

        foreach ([
            ['size', 'Tamanho', 'size', $data['size'] ?? ''],
            ['cor', 'Cor', 'color', $data['color'] ?? ''],
            ['material', 'Material', 'material', $data['material'] ?? ''],
        ] as [$slug, $name, $type, $value]) {
            $valueId = admin_attribute_value_id($slug, $name, $type, (string) $value);
            if ($valueId) {
                $pdo->prepare('INSERT IGNORE INTO variation_attribute_values (variation_id, attribute_value_id) VALUES (:variation_id, :value_id)')
                    ->execute(['variation_id' => $variationId, 'value_id' => $valueId]);
            }
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function admin_product_variation_find(int $productId, int $variationId): ?array
{
    foreach (admin_product_variations($productId) as $variation) {
        if ((int) $variation['id'] !== $variationId) continue;
        $stmt = db()->prepare('SELECT a.slug, av.value FROM variation_attribute_values vav INNER JOIN attribute_values av ON av.id = vav.attribute_value_id INNER JOIN attributes a ON a.id = av.attribute_id WHERE vav.variation_id = :id');
        $stmt->execute(['id' => $variationId]);
        foreach ($stmt->fetchAll() as $attribute) {
            $field = ['size' => 'size', 'cor' => 'color', 'material' => 'material'][$attribute['slug']] ?? null;
            if ($field) $variation[$field] = $attribute['value'];
        }
        return $variation;
    }
    return null;
}

function admin_product_delete_variation(int $productId, int $variationId): void
{
    db()->prepare('UPDATE product_variations SET is_active = 0 WHERE id = :id AND product_id = :product_id')->execute(['id' => $variationId, 'product_id' => $productId]);
}

function admin_products_export_csv(array $products)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=produtos.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['id', 'nome', 'sku', 'categoria', 'preco', 'preco_promocao', 'stock', 'ativo']);

    foreach ($products as $product) {
        fputcsv($output, [
            $product['id'] ?? '',
            $product['name'] ?? '',
            $product['sku'] ?? '',
            $product['category_name'] ?? '',
            $product['price'] ?? '',
            $product['sale_price'] ?? '',
            $product['stock'] ?? '',
            $product['is_active'] ?? 1,
        ]);
    }

    exit;
}
