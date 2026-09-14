<?php
declare(strict_types=1);

require_once __DIR__ . '/catalog.php';

function seo_current_url(): string
{
    $uri = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?') ?: '/';

    return rtrim(APP_URL, '/') . $uri;
}

function seo_build_meta(array $data = []): array
{
    $title = trim((string) ($data['title'] ?? APP_NAME));
    $description = trim((string) ($data['description'] ?? 'Loja online de artigos personalizados, bordados e estampagens.'));

    return [
        'title' => $title,
        'description' => substr($description, 0, 165),
        'canonical' => $data['canonical'] ?? seo_current_url(),
        'image' => $data['image'] ?? asset('images/hero-pattern.svg'),
        'type' => $data['type'] ?? 'website',
        'robots' => $data['robots'] ?? 'index,follow',
        'schema' => $data['schema'] ?? null,
    ];
}

function seo_product_schema(array $product): array
{
    $price = (float) ($product['final_price'] ?? $product['price'] ?? 0);

    return [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product['name'] ?? '',
        'description' => $product['short_description'] ?? $product['long_description'] ?? '',
        'sku' => $product['sku'] ?? '',
        'brand' => [
            '@type' => 'Brand',
            'name' => $product['brand_name'] ?? APP_NAME,
        ],
        'offers' => [
            '@type' => 'Offer',
            'url' => product_url((string) $product['slug']),
            'priceCurrency' => 'EUR',
            'price' => number_format($price, 2, '.', ''),
            'availability' => ((int) ($product['stock'] ?? 0) > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
        ],
    ];
}

function seo_breadcrumb_schema(array $items): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(
            static fn (array $item, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ],
            $items,
            array_keys($items)
        ),
    ];
}

function seo_organization_schema(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => APP_NAME,
        'url' => APP_URL,
        'sameAs' => [
            'https://www.instagram.com/',
            'https://www.facebook.com/',
        ],
    ];
}
