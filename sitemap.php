<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/seo.php';

header('Content-Type: application/xml; charset=utf-8');

$urls = [
    ['loc' => url(), 'priority' => '1.0', 'changefreq' => 'daily'],
    ['loc' => url('produto.php'), 'priority' => '0.9', 'changefreq' => 'daily'],
    ['loc' => url('contactos'), 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => url('termos'), 'priority' => '0.4', 'changefreq' => 'monthly'],
    ['loc' => url('privacidade'), 'priority' => '0.4', 'changefreq' => 'monthly'],
    ['loc' => url('devolucoes'), 'priority' => '0.4', 'changefreq' => 'monthly'],
    ['loc' => url('login.php'), 'priority' => '0.3', 'changefreq' => 'monthly'],
    ['loc' => url('register.php'), 'priority' => '0.3', 'changefreq' => 'monthly'],
];

foreach (catalog_categories() as $category) {
    $urls[] = [
        'loc' => category_url((string) $category['slug']),
        'priority' => '0.8',
        'changefreq' => 'weekly',
    ];
}

foreach (catalog_products() as $product) {
    $urls[] = [
        'loc' => product_url((string) $product['slug']),
        'priority' => !empty($product['is_best_seller']) ? '0.9' : '0.7',
        'changefreq' => 'weekly',
    ];
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $item): ?>
    <url>
        <loc><?= htmlspecialchars($item['loc'], ENT_XML1, 'UTF-8') ?></loc>
        <changefreq><?= htmlspecialchars($item['changefreq'], ENT_XML1, 'UTF-8') ?></changefreq>
        <priority><?= htmlspecialchars($item['priority'], ENT_XML1, 'UTF-8') ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
