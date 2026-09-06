<?php
$adminTitle = 'Encomendas';
$adminSubtitle = 'Estados, linha temporal e producao.';
require_once __DIR__ . '/includes/header.php';

$orders = admin_recent_orders();
$statuses = ['Nova', 'Pagamento pendente', 'Em producao', 'Em personalizacao', 'Em bordado', 'Em estampagem', 'Pronta', 'Enviada', 'Entregue', 'Cancelada', 'Reembolsada'];
?>
<section class="admin-panel mb-4">
    <h3 class="h5 fw-bold mb-3">Estados de producao</h3>
    <div class="admin-status-grid">
        <?php foreach ($statuses as $status): ?>
            <span><?= e($status) ?></span>
        <?php endforeach; ?>
    </div>
</section>
<section class="admin-panel">
    <h3 class="h5 fw-bold mb-3">Ultimas encomendas</h3>
    <div class="admin-table-wrap">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Numero</th><th>Cliente</th><th>Estado</th><th>Total</th><th>Data</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= e($order['order_number']) ?></td>
                    <td><?= e($order['customer_email']) ?></td>
                    <td><span class="admin-status"><?= e($order['status']) ?></span></td>
                    <td><?= e(format_price((float) $order['grand_total'])) ?></td>
                    <td><?= e($order['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($orders === []): ?>
                <tr><td colspan="5" class="text-secondary">Ainda nao existem encomendas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
