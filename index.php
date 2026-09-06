<?php
$pageTitle = 'PPCosta | Artigos personalizados';
$pageDescription = 'Loja online de artigos personalizados, bordados, estampagens e brindes empresariais.';
require_once __DIR__ . '/includes/header.php';

$categories = [
    ['Bebe', 'Bodies, babetes, fraldas e mantas', 'body'],
    ['Roupa', 'T-shirts, sweatshirts e hoodies', 'shirt'],
    ['Casa', 'Toalhas, almofadas e mantas', 'home'],
    ['Canecas', 'Fotografia, frase ou logotipo', 'mug'],
    ['Empresas', 'Produtos e packs corporativos', 'business'],
    ['Brindes', 'Porta-chaves, sacos e mochilas', 'gift'],
];

$featuredProducts = [
    ['Body bebe bordado', 'Nome personalizado em algodao macio', '19,90 EUR', '24,90 EUR', 'Bebe', 'Bordado', 'product-baby'],
    ['Caneca personalizada', 'Fotografia, frase ou logotipo', '12,50 EUR', '', 'Canecas', 'Sublimacao', 'product-mug'],
    ['Hoodie estampado', 'DTF premium ou vinil textil', '34,90 EUR', '39,90 EUR', 'Roupa', 'DTF', 'product-hoodie'],
    ['Saco de algodao', 'Ideal para eventos e equipas', '8,90 EUR', '', 'Empresas', 'Vinil', 'product-bag'],
];

$newProducts = [
    ['Manta personalizada', 'Bordado lateral com nome', '29,90 EUR'],
    ['Porta-chaves acrilico', 'Corte e impressao UV', '5,90 EUR'],
    ['Toalha bordada', 'Monograma ou nome completo', '18,90 EUR'],
];

$bestSellers = [
    ['Bodies personalizados', '1.284 unidades'],
    ['Canecas com fotografia', '947 unidades'],
    ['T-shirts para eventos', '612 unidades'],
];

$reviews = [
    ['Maria F.', 'O body bordado ficou delicado e chegou muito bem embalado.', '5.0'],
    ['Rui P.', 'As t-shirts da equipa ficaram com excelente qualidade de impressao.', '4.9'],
    ['Sofia M.', 'Processo simples, preview claro e acabamento muito profissional.', '5.0'],
];
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
            <p class="text-secondary mb-0">Cada categoria aceita produtos simples, bordados ou estampados.</p>
        </div>
        <div class="row g-3">
            <?php foreach ($categories as $category): ?>
                <div class="col-6 col-lg-2">
                    <a class="category-tile h-100 p-3 p-md-4 text-decoration-none" href="<?= e(url('produto.php?categoria=' . urlencode(strtolower($category[0])))) ?>">
                        <span class="category-icon <?= e($category[2]) ?>"></span>
                        <h3 class="h6 fw-bold mb-1 text-dark"><?= e($category[0]) ?></h3>
                        <span class="text-secondary small"><?= e($category[1]) ?></span>
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
                        <div class="product-media <?= e($product[6]) ?>">
                            <span><?= e($product[4]) ?></span>
                        </div>
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <span class="badge badge-soft rounded-pill"><?= e($product[5]) ?></span>
                                <?php if ($product[3] !== ''): ?>
                                    <span class="badge text-bg-dark rounded-pill">Promo</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="h5 fw-bold"><?= e($product[0]) ?></h3>
                            <p class="text-secondary small"><?= e($product[1]) ?></p>
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="price"><?= e($product[2]) ?></span>
                                <?php if ($product[3] !== ''): ?>
                                    <span class="old-price"><?= e($product[3]) ?></span>
                                <?php endif; ?>
                            </div>
                            <a class="btn btn-sm btn-dark w-100" href="<?= e(url('produto.php')) ?>">Personalizar</a>
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
                <p class="text-secondary">A experiencia sera ligada ao editor visual na fase de personalizacao. A frente da loja ja prepara as opcoes que o cliente espera encontrar.</p>
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
                <p class="text-uppercase fw-semibold small mb-2">Campanha</p>
                <h2 class="section-title mb-3">Packs empresariais com desconto por quantidade</h2>
                <p class="mb-0">Ideal para equipas, eventos, brindes de marca, feiras e campanhas sazonais.</p>
            </div>
            <div class="col-lg-5">
                <div class="promo-panel">
                    <span>Desde</span>
                    <strong>25 unidades</strong>
                    <a class="btn btn-light w-100 mt-3" href="<?= e(url('produto.php?categoria=empresas')) ?>">Ver produtos empresariais</a>
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
                    <a class="list-card" href="<?= e(url('produto.php')) ?>">
                        <span>
                            <strong><?= e($product[0]) ?></strong>
                            <small><?= e($product[1]) ?></small>
                        </span>
                        <b><?= e($product[2]) ?></b>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-4">
                <h2 class="h3 fw-bold mb-4">Mais vendidos</h2>
                <?php foreach ($bestSellers as $product): ?>
                    <a class="list-card" href="<?= e(url('produto.php')) ?>">
                        <span>
                            <strong><?= e($product[0]) ?></strong>
                            <small><?= e($product[1]) ?></small>
                        </span>
                        <b>Top</b>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-4">
                <h2 class="h3 fw-bold mb-4">Avaliacoes</h2>
                <?php foreach ($reviews as $review): ?>
                    <article class="review-card">
                        <div class="d-flex justify-content-between gap-3 mb-2">
                            <strong><?= e($review[0]) ?></strong>
                            <span><?= e($review[2]) ?></span>
                        </div>
                        <p class="text-secondary mb-0"><?= e($review[1]) ?></p>
                    </article>
                <?php endforeach; ?>
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
                <p class="text-secondary mb-0">Conteudo comercial sem ruido, preparado para exportacao e templates de email.</p>
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
