<?php
$adminTitle = 'Stock';
$adminSubtitle = 'Inventario, alertas, reservas e movimentos.';
require_once __DIR__ . '/includes/header.php';

$lowStock = admin_low_stock_products();
?>
<section class="admin-panel">
    <div class="d-flex justify-content-between gap-3 mb-3">
        <h3 class="h5 fw-bold mb-0">Alertas de stock minimo</h3>
        <button class="btn btn-dark btn-sm" type="button">Registar movimento</button>
    </div>
    <div class="admin-list">
        <?php foreach ($lowStock as $product): ?>
            <article>
                <strong><?= e($product['name']) ?></strong>
                <span><?= e($product['sku']) ?> · stock <?= e((string) $product['stock']) ?> · minimo <?= e((string) ($product['stock_minimum'] ?? 0)) ?></span>
            </article>
        <?php endforeach; ?>
        <?php if ($lowStock === []): ?>
            <p class="text-secondary mb-0">Sem alertas de stock.</p>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
