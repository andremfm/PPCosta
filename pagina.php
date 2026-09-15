<?php
require_once __DIR__ . '/includes/content_pages.php';

$slug = (string) ($_GET['slug'] ?? '');
$contentPage = content_page_data($slug);

if ($contentPage === null) {
    http_response_code(404);
    $pageTitle = 'Pagina nao encontrada | PPCosta';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <section class="section-pad">
        <div class="container">
            <div class="empty-state">
                <h1 class="h3 fw-bold">Pagina nao encontrada</h1>
                <p class="text-secondary">A pagina pedida nao existe ou foi movida.</p>
                <a class="btn btn-dark" href="<?= e(url()) ?>">Voltar ao inicio</a>
            </div>
        </div>
    </section>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <?php return; ?>
<?php
}

$pageTitle = $contentPage['title'] . ' | PPCosta';
$pageDescription = $contentPage['description'];
$pageCanonical = content_page_url($slug);
$pageSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $contentPage['title'],
    'description' => $contentPage['description'],
    'url' => $pageCanonical,
];

require_once __DIR__ . '/includes/header.php';
?>
<section class="catalog-hero border-bottom">
    <div class="container">
        <p class="text-uppercase text-secondary fw-semibold small mb-2">Informacao</p>
        <h1 class="section-title mb-3"><?= e($contentPage['title']) ?></h1>
        <p class="text-secondary mb-0"><?= e($contentPage['lead']) ?></p>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <article class="content-page">
            <?= nl2br(e($contentPage['body'])) ?>
        </article>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
