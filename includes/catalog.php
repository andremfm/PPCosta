<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

function catalog_fallback_categories(): array
{
    return [
        ['id' => 1, 'name' => 'Bebe', 'slug' => 'bebe'],
        ['id' => 2, 'name' => 'Roupa', 'slug' => 'roupa'],
        ['id' => 3, 'name' => 'Casa', 'slug' => 'casa'],
        ['id' => 4, 'name' => 'Canecas', 'slug' => 'canecas'],
        ['id' => 5, 'name' => 'Empresas', 'slug' => 'empresas'],
        ['id' => 6, 'name' => 'Brindes', 'slug' => 'brindes'],
    ];
}

function catalog_fallback_products(): array
{
    return [
        [
            'id' => 1,
            'name' => 'Body bebe bordado',
            'slug' => 'body-bebe-bordado',
            'short_description' => 'Body em algodao com nome bordado.',
            'long_description' => 'Body de bebe em algodao macio, ideal para nascimento, batizado e presentes personalizados. Pode ser bordado com nome, data ou pequena frase.',
            'sku' => 'BB-BODY-001',
            'price' => '24.90',
            'sale_price' => '19.90',
            'final_price' => '19.90',
            'stock' => 18,
            'is_new' => 1,
            'is_on_sale' => 1,
            'is_best_seller' => 1,
            'is_personalizable' => 1,
            'category_slug' => 'bebe',
            'category_name' => 'Bebe',
            'technique' => 'Bordado',
            'media_class' => 'product-baby',
        ],
        [
            'id' => 2,
            'name' => 'Caneca personalizada',
            'slug' => 'caneca-personalizada',
            'short_description' => 'Caneca com fotografia, frase ou logotipo.',
            'long_description' => 'Caneca em ceramica preparada para sublimacao de alta definicao. Recomendada para presentes, equipas e campanhas.',
            'sku' => 'CN-SUB-001',
            'price' => '12.50',
            'sale_price' => null,
            'final_price' => '12.50',
            'stock' => 42,
            'is_new' => 0,
            'is_on_sale' => 0,
            'is_best_seller' => 1,
            'is_personalizable' => 1,
            'category_slug' => 'canecas',
            'category_name' => 'Canecas',
            'technique' => 'Sublimacao',
            'media_class' => 'product-mug',
        ],
        [
            'id' => 3,
            'name' => 'Hoodie estampado',
            'slug' => 'hoodie-estampado',
            'short_description' => 'Hoodie com estampagem DTF ou vinil.',
            'long_description' => 'Hoodie confortavel com estampagem frontal, dorsal ou manga. Adequado para equipas, eventos e criadores.',
            'sku' => 'HD-DTF-001',
            'price' => '39.90',
            'sale_price' => '34.90',
            'final_price' => '34.90',
            'stock' => 12,
            'is_new' => 1,
            'is_on_sale' => 1,
            'is_best_seller' => 0,
            'is_personalizable' => 1,
            'category_slug' => 'roupa',
            'category_name' => 'Roupa',
            'technique' => 'DTF',
            'media_class' => 'product-hoodie',
        ],
        [
            'id' => 4,
            'name' => 'Saco algodao personalizado',
            'slug' => 'saco-algodao-personalizado',
            'short_description' => 'Saco para eventos, lojas e brindes.',
            'long_description' => 'Saco de algodao reutilizavel com logotipo, frase ou arte final. Boa escolha para empresas e eventos.',
            'sku' => 'SC-VIN-001',
            'price' => '8.90',
            'sale_price' => null,
            'final_price' => '8.90',
            'stock' => 65,
            'is_new' => 0,
            'is_on_sale' => 0,
            'is_best_seller' => 1,
            'is_personalizable' => 1,
            'category_slug' => 'empresas',
            'category_name' => 'Empresas',
            'technique' => 'Vinil',
            'media_class' => 'product-bag',
        ],
        [
            'id' => 5,
            'name' => 'Manta personalizada',
            'slug' => 'manta-personalizada',
            'short_description' => 'Manta com bordado lateral.',
            'long_description' => 'Manta suave com bordado de nome, monograma ou data. Recomendada para bebe e presentes de familia.',
            'sku' => 'MT-BOR-001',
            'price' => '29.90',
            'sale_price' => null,
            'final_price' => '29.90',
            'stock' => 9,
            'is_new' => 1,
            'is_on_sale' => 0,
            'is_best_seller' => 0,
            'is_personalizable' => 1,
            'category_slug' => 'casa',
            'category_name' => 'Casa',
            'technique' => 'Bordado',
            'media_class' => 'product-baby',
        ],
        [
            'id' => 6,
            'name' => 'Porta-chaves acrilico',
            'slug' => 'porta-chaves-acrilico',
            'short_description' => 'Brinde com impressao UV.',
            'long_description' => 'Porta-chaves em acrilico com impressao UV. Solucao acessivel para campanhas e lembrancas.',
            'sku' => 'PC-UV-001',
            'price' => '5.90',
            'sale_price' => null,
            'final_price' => '5.90',
            'stock' => 120,
            'is_new' => 1,
            'is_on_sale' => 0,
            'is_best_seller' => 0,
            'is_personalizable' => 1,
            'category_slug' => 'brindes',
            'category_name' => 'Brindes',
            'technique' => 'UV',
            'media_class' => 'product-bag',
        ],
    ];
}

function catalog_categories(): array
{
    try {
        $stmt = db()->query(
            'SELECT id, name, slug
             FROM categories
             WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC'
        );

        return $stmt->fetchAll();
    } catch (Throwable) {
        return catalog_fallback_categories();
    }
}

function catalog_products(array $filters = []): array
{
    $filters = array_merge([
        'q' => '',
        'category' => '',
        'min_price' => null,
        'max_price' => null,
        'personalization' => '',
        'sort' => 'recent',
    ], $filters);

    try {
        $where = ['p.is_active = 1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(p.name LIKE :q OR p.short_description LIKE :q OR p.sku LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['category'])) {
            $where[] = 'c.slug = :category';
            $params['category'] = $filters['category'];
        }

        if (!empty($filters['personalization'])) {
            $where[] = 'p.is_personalizable = 1';
        }

        if ($filters['min_price'] !== null && $filters['min_price'] !== '') {
            $where[] = 'COALESCE(p.sale_price, p.price) >= :min_price';
            $params['min_price'] = (float) $filters['min_price'];
        }

        if ($filters['max_price'] !== null && $filters['max_price'] !== '') {
            $where[] = 'COALESCE(p.sale_price, p.price) <= :max_price';
            $params['max_price'] = (float) $filters['max_price'];
        }

        $sortSql = match ($filters['sort'] ?? 'recent') {
            'price_asc' => 'final_price ASC, p.name ASC',
            'price_desc' => 'final_price DESC, p.name ASC',
            'best_seller' => 'p.is_best_seller DESC, p.created_at DESC',
            'name' => 'p.name ASC',
            default => 'p.created_at DESC',
        };

        $sql = '
            SELECT
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
                p.is_new,
                p.is_on_sale,
                p.is_best_seller,
                p.is_personalizable,
                c.slug AS category_slug,
                c.name AS category_name,
                COALESCE(pi.path, "") AS image_path,
                "Personalizavel" AS technique,
                "product-mug" AS media_class
            FROM products p
            LEFT JOIN product_categories pc ON pc.product_id = p.id AND pc.is_primary = 1
            LEFT JOIN categories c ON c.id = pc.category_id
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $sortSql;

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return catalog_filter_fallback_products($filters);
    }
}

function catalog_filter_fallback_products(array $filters): array
{
    $filters = array_merge([
        'q' => '',
        'category' => '',
        'min_price' => null,
        'max_price' => null,
        'personalization' => '',
        'sort' => 'recent',
    ], $filters);

    $products = catalog_fallback_products();

    $filtered = array_values(array_filter($products, static function (array $product) use ($filters): bool {
        if (!empty($filters['q'])) {
            $needle = strtolower($filters['q']);
            $haystack = strtolower($product['name'] . ' ' . $product['short_description'] . ' ' . $product['sku']);

            if (!str_contains($haystack, $needle)) {
                return false;
            }
        }

        if (!empty($filters['category']) && $product['category_slug'] !== $filters['category']) {
            return false;
        }

        if (!empty($filters['personalization']) && (int) $product['is_personalizable'] !== 1) {
            return false;
        }

        if ($filters['min_price'] !== null && $filters['min_price'] !== '' && (float) $product['final_price'] < (float) $filters['min_price']) {
            return false;
        }

        if ($filters['max_price'] !== null && $filters['max_price'] !== '' && (float) $product['final_price'] > (float) $filters['max_price']) {
            return false;
        }

        return true;
    }));

    usort($filtered, static function (array $a, array $b) use ($filters): int {
        return match ($filters['sort'] ?? 'recent') {
            'price_asc' => (float) $a['final_price'] <=> (float) $b['final_price'],
            'price_desc' => (float) $b['final_price'] <=> (float) $a['final_price'],
            'best_seller' => (int) $b['is_best_seller'] <=> (int) $a['is_best_seller'],
            'name' => strcmp($a['name'], $b['name']),
            default => (int) $b['id'] <=> (int) $a['id'],
        };
    });

    return $filtered;
}

function catalog_product_by_slug(string $slug): ?array
{
    try {
        $stmt = db()->prepare(
            'SELECT
                p.*,
                COALESCE(p.sale_price, p.price) AS final_price,
                c.slug AS category_slug,
                c.name AS category_name,
                COALESCE(pi.path, "") AS image_path,
                tr.rate AS tax_rate,
                b.name AS brand_name,
                s.name AS supplier_name
             FROM products p
             LEFT JOIN product_categories pc ON pc.product_id = p.id AND pc.is_primary = 1
             LEFT JOIN categories c ON c.id = pc.category_id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
             LEFT JOIN tax_rates tr ON tr.id = p.tax_rate_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.slug = :slug AND p.is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $product = $stmt->fetch();

        return $product ?: null;
    } catch (Throwable) {
        foreach (catalog_fallback_products() as $product) {
            if ($product['slug'] === $slug) {
                return $product;
            }
        }

        return null;
    }
}

function catalog_product_by_id(int $id): ?array
{
    try {
        $stmt = db()->prepare(
            'SELECT
                p.*,
                COALESCE(p.sale_price, p.price) AS final_price,
                c.slug AS category_slug,
                c.name AS category_name,
                tr.rate AS tax_rate
             FROM products p
             LEFT JOIN product_categories pc ON pc.product_id = p.id AND pc.is_primary = 1
             LEFT JOIN categories c ON c.id = pc.category_id
             LEFT JOIN tax_rates tr ON tr.id = p.tax_rate_id
             WHERE p.id = :id AND p.is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();

        return $product ?: null;
    } catch (Throwable) {
        foreach (catalog_fallback_products() as $product) {
            if ((int) $product['id'] === $id) {
                return $product;
            }
        }

        return null;
    }
}

function catalog_filters_from_request(): array
{
    return [
        'q' => trim($_GET['q'] ?? ''),
        'category' => trim($_GET['categoria'] ?? ''),
        'min_price' => $_GET['preco_min'] ?? null,
        'max_price' => $_GET['preco_max'] ?? null,
        'personalization' => isset($_GET['personalizavel']) ? '1' : '',
        'sort' => trim($_GET['ordenar'] ?? 'recent'),
    ];
}
