<?php
$adminTitle = 'Relatorios';
$adminSubtitle = 'Vendas, lucro, margens e performance.';
require_once __DIR__ . '/includes/header.php';

$metrics = admin_dashboard_metrics();
?>
<section class="admin-panel">
    <h3 class="h5 fw-bold mb-3">Resumo financeiro</h3>
    <div class="row g-4">
        <?php foreach ($metrics as $metric): ?>
            <div class="col-md-6 col-xl-4">
                <div class="admin-metric <?= e($metric['tone']) ?>">
                    <span><?= e($metric['label']) ?></span>
                    <strong><?= e($metric['value']) ?></strong>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
