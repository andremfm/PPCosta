<?php
require_once __DIR__ . '/includes/routing.php';

$currentPath = route_normalize_path((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
if (route_is_private($currentPath)) {
    http_response_code(404);
    exit('Pagina nao encontrada.');
}

if ($currentPath !== '/' && route_dispatch_pretty_path($currentPath)) {
    return;
}
if (!in_array($currentPath, ['/', '/index.php'], true)) {
    http_response_code(404);
    require __DIR__ . '/pagina.php';
    return;
}

require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/reviews.php';

$pageTitle = 'PPCosta | Artigos personalizados';
$pageDescription = 'Loja online de artigos personalizados, bordados, estampagens e brindes empresariais.';
$pageCanonical = url();
$pageSchema = seo_organization_schema();
require_once __DIR__ . '/includes/header.php';

$categories = array_slice(catalog_categories(), 0, 6);
$homeProducts = catalog_products(['sort' => 'recent']);
$featuredProducts = home_take_products($homeProducts, static fn (array $product): bool => !empty($product['is_featured']) || !empty($product['is_best_seller']) || !empty($product['is_on_sale']), 4);
$newProducts = home_take_products($homeProducts, static fn (array $product): bool => !empty($product['is_new']), 3);
$bestSellers = home_take_products(catalog_products(['sort' => 'best_seller']), static fn (array $product): bool => !empty($product['is_best_seller']), 3);
$reviews = reviews_recent_approved(3);

function home_take_products(array $products, callable $filter, int $limit): array
{
    $filtered = array_values(array_filter($products, $filter));

    if ($filtered === []) {
        $filtered = $products;
    }

    return array_slice($filtered, 0, $limit);
}
?>
<section class="hero hero-home">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <p class="badge badge-soft rounded-pill px-3 py-2 mb-3">Bordados, estampagens e presentes personalizados</p>
                <h1 class="hero-title mb-4">PPCosta</h1>
                <p class="hero-copy mb-4">Artigos personalizados para bebe, casa, equipas e empresas, com personalizacao por texto, imagem, logotipo, bordado ou estampagem.</p>
                <form class="hero-search mb-4" action="<?= e(url('produto.php')) ?>" method="get">
                    <label class="visually-hidden" for="home-search">Pesquisar produto</label>
                    <input id="home-search" class="form-control form-control-lg" name="q" type="search" placeholder="Pesquisar bodies, canecas, hoodies..." autocomplete="off">
                    <button class="btn btn-dark btn-lg" type="submit">Pesquisar</button>
                </form>
                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-dark btn-lg px-4" href="<?= e(url('produto.php')) ?>">Ver produtos</a>
                    <a class="btn btn-outline-dark btn-lg px-4" href="#personalizacao">Personalizar</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-showcase" aria-label="Exemplos de artigos personalizados">
                    <div class="showcase-item item-large">
                        <span>Body bordado</span>
                        <strong>Nome do bebe</strong>
                    </div>
                    <div class="showcase-grid">
                        <div class="showcase-item item-mug">Caneca</div>
                        <div class="showcase-item item-shirt">T-shirt</div>
                        <div class="showcase-item item-bag">Saco</div>
                        <div class="showcase-item item-key">Brinde</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="trust-strip border-bottom">
    <div class="container">
        <div class="row g-0 text-center">
            <div class="col-6 col-lg-3 trust-item">Preview antes de comprar</div>
            <div class="col-6 col-lg-3 trust-item">Bordado e estampagem</div>
            <div class="col-6 col-lg-3 trust-item">Producoes particulares e empresas</div>
            <div class="col-6 col-lg-3 trust-item">Envio nacional</div>
        </div>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="text-uppercase text-secondary fw-semibold small mb-2">Categorias</p>
                <h2 class="section-title mb-0">Escolhe o ponto de partida</h2>
            </div>
            <p class="text-secondary mb-0">Artigos para oferecer, usar e dar a conhecer a tua marca.</p>
        </div>
        <div class="row g-3">
            <?php foreach ($categories as $category): ?>
                <div class="col-6 col-lg-2">
                    <a class="category-tile h-100 p-3 p-md-4 text-decoration-none" href="<?= e(category_url((string) $category['slug'])) ?>">
                        <span class="category-icon <?= e((string) $category['icon_class']) ?>"></span>
                        <h3 class="h6 fw-bold mb-1 text-dark"><?= e((string) $category['name']) ?></h3>
                        <span class="text-secondary small"><?= e((string) $category['description']) ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-pad surface-section border-top border-bottom">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="text-uppercase text-secondary fw-semibold small mb-2">Destaques</p>
                <h2 class="section-title mb-0">Produtos em destaque</h2>
            </div>
            <a class="btn btn-outline-dark align-self-lg-end" href="<?= e(url('produto.php')) ?>">Abrir catalogo</a>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="col-sm-6 col-xl-3">
                    <article class="product-card h-100 overflow-hidden">
                        <a class="product-media <?= e($product['media_class'] ?? 'product-mug') ?>" href="<?= e(product_url((string) $product['slug'])) ?>">
                            <?php if (!empty($product['image_path'])): ?>
                                <img src="<?= e(url((string) $product['image_path'])) ?>" alt="<?= e((string) $product['name']) ?>">
                            <?php else: ?>
                                <span><?= e((string) ($product['category_name'] ?? 'Produto')) ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <span class="badge badge-soft rounded-pill"><?= !empty($product['is_personalizable']) ? 'Personalizavel' : 'Produto' ?></span>
                                <?php if (!empty($product['sale_price'])): ?>
                                    <span class="badge text-bg-dark rounded-pill">Promo</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="h5 fw-bold"><?= e((string) $product['name']) ?></h3>
                            <p class="text-secondary small"><?= e((string) $product['short_description']) ?></p>
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="price"><?= e(format_price((float) $product['final_price'])) ?></span>
                                <?php if (!empty($product['sale_price'])): ?>
                                    <span class="old-price"><?= e(format_price((float) $product['price'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <a class="btn btn-sm btn-dark w-100" href="<?= e(product_url((string) $product['slug'])) ?>">Personalizar</a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="personalizacao" class="section-pad">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <p class="text-uppercase text-secondary fw-semibold small mb-2">Personalizacao</p>
                <h2 class="section-title mb-3">Da ideia ao artigo final em poucos passos</h2>
                <p class="text-secondary">Nomes, frases e imagens que tornam cada artigo especial.</p>
            </div>
            <div class="col-lg-7">
                <div class="process-grid">
                    <?php foreach (['Escolher produto', 'Inserir nome ou texto', 'Enviar imagem ou logotipo', 'Selecionar tecnica', 'Validar preview', 'Finalizar encomenda'] as $index => $step): ?>
                        <div class="process-step">
                            <span><?= e((string) ($index + 1)) ?></span>
                            <strong><?= e($step) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-pad promo-band">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-7">
                <p class="text-uppercase fw-semibold small mb-2">Empresas</p>
                <h2 class="section-title mb-3">Artigos personalizados para a tua equipa</h2>
                <p class="mb-0">Ideal para equipas, eventos, brindes de marca, feiras e campanhas sazonais.</p>
            </div>
            <div class="col-lg-5">
                <div class="promo-panel">
                    <span>A tua marca</span>
                    <strong>Em cada detalhe</strong>
                    <a class="btn btn-light w-100 mt-3" href="<?= e(category_url('empresas')) ?>">Ver produtos empresariais</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h2 class="h3 fw-bold mb-4">Novidades</h2>
                <?php foreach ($newProducts as $product): ?>
                    <a class="list-card" href="<?= e(product_url((string) $product['slug'])) ?>">
                        <span>
                            <strong><?= e((string) $product['name']) ?></strong>
                            <small><?= e((string) $product['short_description']) ?></small>
                        </span>
                        <b><?= e(format_price((float) $product['final_price'])) ?></b>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-4">
                <h2 class="h3 fw-bold mb-4">Mais vendidos</h2>
                <?php foreach ($bestSellers as $product): ?>
                    <a class="list-card" href="<?= e(product_url((string) $product['slug'])) ?>">
                        <span>
                            <strong><?= e((string) $product['name']) ?></strong>
                            <small><?= e((string) ($product['category_name'] ?? 'Produto')) ?></small>
                        </span>
                        <b><?= e(format_price((float) $product['final_price'])) ?></b>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-4">
                <h2 class="h3 fw-bold mb-4">Avaliacoes</h2>
                <?php foreach ($reviews as $review): ?>
                    <article class="review-card">
                        <div class="d-flex justify-content-between gap-3 mb-2">
                            <strong><?= e(trim((string) ($review['first_name'] ?? '') . ' ' . (string) ($review['last_name'] ?? '')) ?: 'Cliente') ?></strong>
                            <span class="review-stars"><?= e(review_rating_label((int) $review['rating'])) ?></span>
                        </div>
                        <p class="text-secondary small mb-2"><?= e((string) ($review['product_name'] ?? 'Produto')) ?></p>
                        <p class="text-secondary mb-0"><?= e((string) ($review['comment'] ?? '')) ?></p>
                    </article>
                <?php endforeach; ?>
                <?php if ($reviews === []): ?>
                    <div class="empty-state bg-white">
                        <h3 class="h5 fw-bold">Ainda sem avaliacoes aprovadas</h3>
                        <p class="text-secondary mb-0">As opinioes publicadas aparecem aqui apos moderacao.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="newsletter-section border-top">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <p class="text-uppercase text-secondary fw-semibold small mb-2">Newsletter</p>
                <h2 class="h3 fw-bold mb-2">Recebe novidades, campanhas e ideias de personalizacao</h2>
                <p class="text-secondary mb-0">Novas colecoes e ideias para os teus proximos presentes.</p>
            </div>
            <div class="col-lg-6">
                <form class="newsletter-form" data-newsletter-form>
                    <label class="visually-hidden" for="newsletter-home-email">Email</label>
                    <div class="input-group input-group-lg">
                        <input id="newsletter-home-email" class="form-control" type="email" name="email" placeholder="O teu email" required>
                        <button class="btn btn-dark" type="submit">Subscrever</button>
                    </div>
                    <div class="form-text" data-newsletter-message></div>
                </form>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
