<?php
$pageTitle = 'Catalogo | PPCosta';
require_once __DIR__ . '/includes/catalog.php';

$filters = catalog_filters_from_request();
$categories = catalog_categories();
$products = catalog_products($filters);

require_once __DIR__ . '/includes/header.php';
?>
<section class="catalog-hero border-bottom">
    <div class="container">
        <p class="text-uppercase text-secondary fw-semibold small mb-2">Catalogo</p>
        <h1 class="section-title mb-3">Produtos personalizados</h1>
        <p class="text-secondary mb-0">Pesquisa, filtra e escolhe produtos preparados para bordado, estampagem ou venda simples.</p>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <form class="catalog-filters mb-4" method="get" action="<?= e(url('produto.php')) ?>">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label" for="q">Pesquisar</label>
                    <input class="form-control" id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Nome, SKU ou descricao">
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="categoria">Categoria</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category['slug']) ?>" <?= $filters['category'] === $category['slug'] ? 'selected' : '' ?>>
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-lg-1">
                    <label class="form-label" for="preco_min">Min.</label>
                    <input class="form-control" id="preco_min" name="preco_min" type="number" min="0" step="0.01" value="<?= e((string) $filters['min_price']) ?>">
                </div>
                <div class="col-6 col-lg-1">
                    <label class="form-label" for="preco_max">Max.</label>
                    <input class="form-control" id="preco_max" name="preco_max" type="number" min="0" step="0.01" value="<?= e((string) $filters['max_price']) ?>">
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="ordenar">Ordenar</label>
                    <select class="form-select" id="ordenar" name="ordenar">
                        <option value="recent" <?= $filters['sort'] === 'recent' ? 'selected' : '' ?>>Mais recentes</option>
                        <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Preco ascendente</option>
                        <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Preco descendente</option>
                        <option value="best_seller" <?= $filters['sort'] === 'best_seller' ? 'selected' : '' ?>>Mais vendidos</option>
                        <option value="name" <?= $filters['sort'] === 'name' ? 'selected' : '' ?>>Nome</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <div class="form-check mb-2">
                        <input class="form-check-input" id="personalizavel" name="personalizavel" type="checkbox" value="1" <?= $filters['personalization'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="personalizavel">Personalizavel</label>
                    </div>
                    <button class="btn btn-dark w-100" type="submit">Filtrar</button>
                </div>
            </div>
        </form>

        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-4">
            <span class="text-secondary"><?= count($products) ?> produto(s) encontrado(s)</span>
            <a class="text-secondary" href="<?= e(url('produto.php')) ?>">Limpar filtros</a>
        </div>

        <?php if ($products === []): ?>
            <div class="empty-state">
                <h2 class="h4 fw-bold">Sem resultados</h2>
                <p class="text-secondary mb-0">Experimenta remover filtros ou pesquisar por outra palavra.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                    <div class="col-sm-6 col-xl-3">
                        <article class="product-card h-100 overflow-hidden">
                            <a class="product-media <?= e($product['media_class'] ?? 'product-mug') ?>" href="<?= e(url('produto-detalhe.php?slug=' . urlencode($product['slug']))) ?>">
                                <span><?= e($product['category_name'] ?? 'Produto') ?></span>
                            </a>
                            <div class="p-3">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <?php if (!empty($product['is_personalizable'])): ?>
                                        <span class="badge badge-soft rounded-pill">Personalizavel</span>
                                    <?php endif; ?>
                                    <?php if (!empty($product['is_new'])): ?>
                                        <span class="badge text-bg-light rounded-pill">Novo</span>
                                    <?php endif; ?>
                                    <?php if (!empty($product['is_best_seller'])): ?>
                                        <span class="badge text-bg-dark rounded-pill">Top</span>
                                    <?php endif; ?>
                                </div>
                                <h2 class="h6 fw-bold">
                                    <a class="text-dark text-decoration-none" href="<?= e(url('produto-detalhe.php?slug=' . urlencode($product['slug']))) ?>">
                                        <?= e($product['name']) ?>
                                    </a>
                                </h2>
                                <p class="text-secondary small mb-3"><?= e($product['short_description']) ?></p>
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="price"><?= e(format_price((float) $product['final_price'])) ?></span>
                                    <?php if (!empty($product['sale_price'])): ?>
                                        <span class="old-price"><?= e(format_price((float) $product['price'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-dark flex-fill" href="<?= e(url('produto-detalhe.php?slug=' . urlencode($product['slug']))) ?>">Ver</a>
                                    <a class="btn btn-sm btn-dark flex-fill" href="<?= e(url('produto-detalhe.php?slug=' . urlencode($product['slug']))) ?>">Personalizar</a>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
