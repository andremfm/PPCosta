<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin.php';
admin_require();

$adminTitle = $adminTitle ?? 'Dashboard';
$adminSubtitle = $adminSubtitle ?? 'Gestao operacional da loja.';
$adminUser = current_user();
?>
<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($adminTitle) ?> | Administracao <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
</head>
<body class="admin-layout">
<div class="container-fluid">
    <div class="row">
        <aside class="col-lg-2 admin-sidebar p-3">
            <h1 class="h5 fw-bold px-2 mb-4"><?= e(APP_NAME) ?> Admin</h1>
            <?php foreach (admin_nav_items() as $item): ?>
                <a class="<?= e(current_page() === $item['match'] ? 'active' : '') ?>" href="<?= e(url($item['href'])) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
            <a href="<?= e(url()) ?>">Ver loja</a>
        </aside>
        <main class="col-lg-10 p-4 p-lg-5">
            <div class="admin-topbar mb-4">
                <div>
                    <p class="text-uppercase text-secondary fw-semibold small mb-2">BackOffice</p>
                    <h2 class="section-title mb-0"><?= e($adminTitle) ?></h2>
                    <p class="text-secondary mb-0"><?= e($adminSubtitle) ?></p>
                </div>
                <div class="admin-topbar-actions">
                    <span><?= e($adminUser['first_name'] ?? 'Admin') ?></span>
                    <button class="btn btn-dark" type="button" data-admin-theme-toggle>Modo escuro</button>
                </div>
            </div>
