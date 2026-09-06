<?php
$adminTitle = 'Dashboard';
$adminSubtitle = 'Indicadores, alertas e atividade recente.';
require_once __DIR__ . '/includes/header.php';

$metrics = admin_dashboard_metrics();
$recentOrders = admin_recent_orders();
$recentCustomers = admin_recent_customers();
$lowStockProducts = admin_low_stock_products();
$bestSellers = admin_best_sellers();
?>
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

<div class="row g-4 mt-1">
    <div class="col-xl-8">
        <section class="admin-panel">
            <div class="d-flex justify-content-between gap-3 mb-3">
                <h3 class="h5 fw-bold mb-0">Ultimas encomendas</h3>
                <a href="<?= e(url('admin/encomendas.php')) ?>">Ver todas</a>
            </div>
            <div class="admin-table-wrap">
                <table class="table admin-table align-middle mb-0">
                    <thead><tr><th>Numero</th><th>Cliente</th><th>Estado</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><?= e($order['order_number']) ?></td>
                            <td><?= e($order['customer_email']) ?></td>
                            <td><span class="admin-status"><?= e($order['status']) ?></span></td>
                            <td><?= e(format_price((float) $order['grand_total'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($recentOrders === []): ?>
                        <tr><td colspan="4" class="text-secondary">Ainda nao existem encomendas.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Alertas de stock</h3>
            <div class="admin-list">
                <?php foreach ($lowStockProducts as $product): ?>
                    <article>
                        <strong><?= e($product['name']) ?></strong>
                        <span><?= e($product['sku']) ?> · stock <?= e((string) $product['stock']) ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-6">
        <section class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Produtos mais vendidos</h3>
            <div class="admin-list">
                <?php foreach ($bestSellers as $product): ?>
                    <article>
                        <strong><?= e($product['name']) ?></strong>
                        <span><?= e($product['sku']) ?> · <?= e(isset($product['units_sold']) ? (string) $product['units_sold'] . ' unidades' : 'produto em destaque') ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <div class="col-xl-6">
        <section class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Ultimos clientes</h3>
            <div class="admin-list">
                <?php foreach ($recentCustomers as $customer): ?>
                    <article>
                        <strong><?= e(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?></strong>
                        <span><?= e($customer['email'] ?? '') ?> · <?= e($customer['status'] ?? 'active') ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
