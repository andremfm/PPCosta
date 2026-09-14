<?php
$adminTitle = 'Relatorios';
$adminSubtitle = 'Vendas, IVA, lucro, margens, metodos e performance.';
require_once __DIR__ . '/../includes/admin_reports.php';
admin_require();

$summary = admin_report_summary();
$monthlySales = admin_report_monthly_sales();
$statusBreakdown = admin_report_status_breakdown();
$paymentMethods = admin_report_methods('payment');
$shippingMethods = admin_report_methods('shipping');
$productMargins = admin_report_product_margins();
$lowStock = admin_low_stock_products();

if (isset($_GET['export']) && $_GET['export'] === 'monthly') {
    admin_reports_export_csv($monthlySales, ['period', 'orders_count', 'revenue'], 'relatorio-vendas-mensais.csv');
}

$maxMonthlyRevenue = admin_report_max_value($monthlySales, 'revenue');
$maxStatusOrders = admin_report_max_value($statusBreakdown, 'orders_count');

require_once __DIR__ . '/includes/header.php';
?>
<section class="admin-panel mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
        <div>
            <h3 class="h5 fw-bold mb-1">Resumo financeiro</h3>
            <p class="text-secondary mb-0">Valores calculados sem encomendas canceladas ou reembolsadas.</p>
        </div>
        <a class="btn btn-outline-dark btn-sm align-self-md-start" href="<?= e(url('admin/relatorios.php?export=monthly')) ?>">Exportar vendas CSV</a>
    </div>
    <div class="row g-4">
        <div class="col-md-6 col-xl-4"><div class="admin-metric sales"><span>Vendas</span><strong><?= e(format_price((float) $summary['revenue'])) ?></strong></div></div>
        <div class="col-md-6 col-xl-4"><div class="admin-metric orders"><span>Encomendas</span><strong><?= e((string) $summary['orders_count']) ?></strong></div></div>
        <div class="col-md-6 col-xl-4"><div class="admin-metric profit"><span>Lucro estimado</span><strong><?= e(format_price((float) $summary['profit'])) ?></strong></div></div>
        <div class="col-md-6 col-xl-4"><div class="admin-metric stock"><span>IVA</span><strong><?= e(format_price((float) $summary['tax'])) ?></strong></div></div>
        <div class="col-md-6 col-xl-4"><div class="admin-metric customers"><span>Descontos</span><strong><?= e(format_price((float) $summary['discounts'])) ?></strong></div></div>
        <div class="col-md-6 col-xl-4"><div class="admin-metric products"><span>Portes</span><strong><?= e(format_price((float) $summary['shipping'])) ?></strong></div></div>
    </div>
</section>

<section class="admin-grid-2 mb-4">
    <div class="admin-panel">
        <h3 class="h5 fw-bold mb-3">Vendas mensais</h3>
        <div class="admin-bar-chart">
            <?php foreach ($monthlySales as $row): ?>
                <?php $width = ((float) ($row['revenue'] ?? 0) / $maxMonthlyRevenue) * 100; ?>
                <div class="admin-bar-row">
                    <span><?= e((string) ($row['period'] ?? '-')) ?></span>
                    <div><i style="width: <?= e((string) $width) ?>%"></i></div>
                    <strong><?= e(format_price((float) ($row['revenue'] ?? 0))) ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if ($monthlySales === []): ?>
                <p class="text-secondary mb-0">Sem vendas registadas.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-panel">
        <h3 class="h5 fw-bold mb-3">Estados de encomenda</h3>
        <div class="admin-bar-chart">
            <?php foreach ($statusBreakdown as $row): ?>
                <?php $width = ((float) ($row['orders_count'] ?? 0) / $maxStatusOrders) * 100; ?>
                <div class="admin-bar-row">
                    <span><?= e(admin_order_status_label((string) ($row['status'] ?? '-'))) ?></span>
                    <div><i style="width: <?= e((string) $width) ?>%"></i></div>
                    <strong><?= e((string) ($row['orders_count'] ?? 0)) ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if ($statusBreakdown === []): ?>
                <p class="text-secondary mb-0">Sem estados para apresentar.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="admin-grid-2 mb-4">
    <div class="admin-panel">
        <h3 class="h5 fw-bold mb-3">Metodos de pagamento</h3>
        <div class="admin-list">
            <?php foreach ($paymentMethods as $method): ?>
                <article><strong><?= e((string) $method['label']) ?></strong><span><?= e((string) $method['orders_count']) ?> encomendas &middot; <?= e(format_price((float) $method['revenue'])) ?></span></article>
            <?php endforeach; ?>
            <?php if ($paymentMethods === []): ?>
                <p class="text-secondary mb-0">Sem dados de pagamento.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-panel">
        <h3 class="h5 fw-bold mb-3">Metodos de envio</h3>
        <div class="admin-list">
            <?php foreach ($shippingMethods as $method): ?>
                <article><strong><?= e((string) $method['label']) ?></strong><span><?= e((string) $method['orders_count']) ?> encomendas &middot; <?= e(format_price((float) $method['revenue'])) ?></span></article>
            <?php endforeach; ?>
            <?php if ($shippingMethods === []): ?>
                <p class="text-secondary mb-0">Sem dados de envio.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="admin-grid-2">
    <div class="admin-panel">
        <h3 class="h5 fw-bold mb-3">Margem por produto</h3>
        <div class="admin-table-wrap">
            <table class="table admin-table align-middle mb-0">
                <thead><tr><th>Produto</th><th>SKU</th><th>Unid.</th><th>Receita</th><th>Lucro</th></tr></thead>
                <tbody>
                <?php foreach ($productMargins as $product): ?>
                    <tr>
                        <td><?= e((string) $product['name']) ?></td>
                        <td><?= e((string) $product['sku']) ?></td>
                        <td><?= e((string) $product['units_sold']) ?></td>
                        <td><?= e(format_price((float) $product['revenue'])) ?></td>
                        <td><?= e(format_price((float) $product['profit'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-panel">
        <h3 class="h5 fw-bold mb-3">Alertas de stock</h3>
        <div class="admin-list">
            <?php foreach ($lowStock as $product): ?>
                <article><strong><?= e((string) $product['name']) ?></strong><span><?= e((string) $product['sku']) ?> &middot; stock <?= e((string) $product['stock']) ?> / minimo <?= e((string) ($product['stock_minimum'] ?? 0)) ?></span></article>
            <?php endforeach; ?>
            <?php if ($lowStock === []): ?>
                <p class="text-secondary mb-0">Sem alertas de stock.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
