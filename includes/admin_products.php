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
            return catalog_products(['sort' => 'recent']);
        }
    } catch (Throwable) {
    }

    return admin_session_products();
}

function admin_product_find(string|int $id): ?array
{
    try {
        if (db_available()) {
            $product = catalog_product_by_id((int) $id);

            if ($product) {
                return array_merge(admin_product_defaults(), $product);
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
        $pdo->commit();

        return true;
    } catch (Throwable) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return admin_product_save_session($payload);
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
    try {
        db()->prepare('UPDATE products SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id')->execute(['id' => $id]);
        return;
    } catch (Throwable) {
    }

    $products = admin_session_products();

    foreach ($products as &$product) {
        if ((int) $product['id'] === $id) {
            $product['is_active'] = empty($product['is_active']) ? 1 : 0;
            break;
        }
    }

    admin_save_session_products($products);
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
    $product['sku'] .= '-COPY';
    admin_product_save($product);
}

function admin_product_delete(int $id): void
{
    try {
        $stmt = db()->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return;
    } catch (Throwable) {
        try {
            db()->prepare('UPDATE products SET is_active = 0 WHERE id = :id')->execute(['id' => $id]);
            return;
        } catch (Throwable) {
        }
    }

    $products = array_filter(
        admin_session_products(),
        static fn (array $product): bool => (int) $product['id'] !== $id
    );

    admin_save_session_products($products);
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
