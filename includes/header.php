<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/seo.php';

$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? 'Loja online de artigos personalizados, bordados e estampagens.';
send_security_headers();
$seoMeta = seo_build_meta([
    'title' => $pageTitle,
    'description' => $pageDescription,
    'canonical' => $pageCanonical ?? null,
    'image' => $pageImage ?? null,
    'type' => $pageType ?? null,
    'robots' => $pageRobots ?? null,
    'schema' => $pageSchema ?? null,
]);
$authUser = current_user();
$flashMessages = consume_flash();
$cartUnits = 0;

foreach (($_SESSION['cart']['items'] ?? []) as $cartItem) {
    $cartUnits += (int) ($cartItem['quantity'] ?? 0);
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($seoMeta['description']) ?>">
    <meta name="robots" content="<?= e($seoMeta['robots']) ?>">
    <meta name="theme-color" content="#0f172a">
    <link rel="canonical" href="<?= e($seoMeta['canonical']) ?>">
    <meta property="og:locale" content="pt_PT">
    <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
    <meta property="og:type" content="<?= e($seoMeta['type']) ?>">
    <meta property="og:title" content="<?= e($seoMeta['title']) ?>">
    <meta property="og:description" content="<?= e($seoMeta['description']) ?>">
    <meta property="og:url" content="<?= e($seoMeta['canonical']) ?>">
    <meta property="og:image" content="<?= e($seoMeta['image']) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($seoMeta['title']) ?>">
    <meta name="twitter:description" content="<?= e($seoMeta['description']) ?>">
    <meta name="twitter:image" content="<?= e($seoMeta['image']) ?>">
    <title><?= e($seoMeta['title']) ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(versioned_asset('css/main.css')) ?>">
    <?php if (!empty($seoMeta['schema'])): ?>
        <script type="application/ld+json"><?= json_encode($seoMeta['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>
</head>
<body>
<script>
    window.PPCOSTA_BASE_URL = '<?= e(APP_URL) ?>';
</script>
<header class="site-header">
    <nav class="navbar navbar-expand-lg bg-white border-bottom fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= e(url()) ?>"><?= e(APP_NAME) ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Abrir menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link <?= e(is_active('index.php')) ?>" href="<?= e(url()) ?>">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link <?= e(is_active('produto.php')) ?>" href="<?= e(url('produto.php')) ?>">Produtos</a></li>
                    <li class="nav-item"><a class="nav-link <?= e(is_active('carrinho.php')) ?>" href="<?= e(url('carrinho.php')) ?>">Carrinho<?= $cartUnits > 0 ? ' (' . e((string) $cartUnits) . ')' : '' ?></a></li>
                    <li class="nav-item"><a class="nav-link <?= e(is_active('perfil.php')) ?>" href="<?= e(url('perfil.php')) ?>">Conta</a></li>
                    <?php if ($authUser): ?>
                        <?php if (has_role('admin')): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/')) ?>">Admin</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="btn btn-outline-dark btn-sm px-3" href="<?= e(url('logout.php')) ?>">Sair</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-dark btn-sm px-3" href="<?= e(url('login.php')) ?>">Entrar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
<main class="site-main">
<?php if ($flashMessages !== []): ?>
    <div class="container pt-3">
        <?php foreach ($flashMessages as $message): ?>
            <div class="alert alert-<?= e($message['type']) ?> mb-3" role="alert">
                <?= e($message['message']) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
