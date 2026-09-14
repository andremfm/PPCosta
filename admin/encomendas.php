<?php
$adminTitle = 'Encomendas';
$adminSubtitle = 'Estados, linha temporal, producao, cliente, moradas e itens.';
require_once __DIR__ . '/../includes/admin_orders.php';
admin_require();

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];
$selectedOrder = null;

if (request_method() === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('admin/encomendas.php');
    }

    if (($_POST['action'] ?? '') === 'status') {
        $orderId = (string) ($_POST['id'] ?? '');
        admin_order_update_status($orderId, (string) ($_POST['status'] ?? ''), (string) ($_POST['note'] ?? ''));
        flash('success', 'Estado da encomenda atualizado.');
        redirect('admin/encomendas.php?view=' . urlencode((string) $orderId));
    }
}

if (!empty($_GET['view'])) {
    $selectedOrder = admin_order_find((string) $_GET['view']);
}

$orders = admin_orders_all($filters);
$statuses = admin_order_statuses();

require_once __DIR__ . '/includes/header.php';
?>
<section class="admin-panel mb-4">
    <h3 class="h5 fw-bold mb-3">Estados de producao</h3>
    <div class="admin-status-grid">
        <?php foreach ($statuses as $status): ?>
            <span><?= e($status) ?></span>
        <?php endforeach; ?>
    </div>
</section>

<section class="admin-panel mb-4">
    <form class="row g-3 align-items-end" method="get" action="<?= e(url('admin/encomendas.php')) ?>">
        <div class="col-md-6">
            <label class="form-label" for="q">Pesquisar</label>
            <input class="form-control" id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Numero, email ou telefone">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="status">Estado</label>
            <select class="form-select" id="status" name="status">
                <option value="">Todos</option>
                <?php foreach ($statuses as $status => $label): ?>
                    <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-dark flex-fill" type="submit">Filtrar</button>
            <a class="btn btn-outline-dark" href="<?= e(url('admin/encomendas.php')) ?>">Limpar</a>
        </div>
    </form>
</section>

<section class="admin-panel mb-4">
    <h3 class="h5 fw-bold mb-3">Encomendas</h3>
    <div class="admin-table-wrap">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Numero</th><th>Cliente</th><th>Estado</th><th>Total</th><th>Data</th><th>Acoes</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong><?= e((string) $order['order_number']) ?></strong></td>
                    <td>
                        <?= e((string) $order['customer_email']) ?>
                        <span class="d-block text-secondary small"><?= e((string) ($order['customer_phone'] ?? '')) ?></span>
                    </td>
                    <td><span class="admin-status"><?= e(admin_order_status_label((string) $order['status'])) ?></span></td>
                    <td><?= e(format_price((float) $order['grand_total'])) ?></td>
                    <td><?= e((string) $order['created_at']) ?></td>
                    <td><a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/encomendas.php?view=' . urlencode((string) ($order['id'] ?? $order['order_number'])))) ?>">Ver detalhe</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($orders === []): ?>
                <tr><td colspan="6" class="text-secondary">Ainda nao existem encomendas para os filtros escolhidos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($selectedOrder): ?>
    <?php
    $orderId = $selectedOrder['id'] ?? $selectedOrder['order_number'];
    $items = admin_order_items($orderId);
    $addresses = admin_order_addresses($orderId);
    $timeline = admin_order_timeline($orderId);
    ?>
    <section class="admin-grid-2 mb-4">
        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Resumo da encomenda</h3>
            <div class="admin-status-grid mb-3">
                <span><?= e((string) $selectedOrder['order_number']) ?></span>
                <span><?= e(admin_order_status_label((string) $selectedOrder['status'])) ?></span>
                <span><?= e(format_price((float) $selectedOrder['grand_total'])) ?></span>
            </div>
            <form method="post" action="<?= e(url('admin/encomendas.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="id" value="<?= e((string) $orderId) ?>">
                <label class="form-label" for="order_status">Atualizar estado</label>
                <select class="form-select mb-2" id="order_status" name="status">
                    <?php foreach ($statuses as $status => $label): ?>
                        <option value="<?= e($status) ?>" <?= $selectedOrder['status'] === $status ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea class="form-control mb-2" name="note" rows="3" placeholder="Nota para a linha temporal"></textarea>
                <button class="btn btn-dark btn-sm" type="submit">Atualizar encomenda</button>
            </form>
        </div>

        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Cliente e valores</h3>
            <div class="admin-list">
                <article><strong>Email</strong><span><?= e((string) $selectedOrder['customer_email']) ?></span></article>
                <article><strong>Telefone</strong><span><?= e((string) ($selectedOrder['customer_phone'] ?? '-')) ?></span></article>
                <article><strong>Subtotal</strong><span><?= e(format_price((float) $selectedOrder['subtotal'])) ?></span></article>
                <article><strong>Descontos</strong><span><?= e(format_price((float) $selectedOrder['discount_total'])) ?></span></article>
                <article><strong>Portes</strong><span><?= e(format_price((float) $selectedOrder['shipping_total'])) ?></span></article>
                <article><strong>IVA</strong><span><?= e(format_price((float) $selectedOrder['tax_total'])) ?></span></article>
            </div>
        </div>
    </section>

    <section class="admin-grid-2">
        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Itens</h3>
            <div class="admin-table-wrap">
                <table class="table admin-table align-middle mb-0">
                    <thead><tr><th>Produto</th><th>SKU</th><th>Qtd</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e((string) ($item['product_name'] ?? $item['name'] ?? 'Produto')) ?></td>
                            <td><?= e((string) ($item['sku'] ?? '')) ?></td>
                            <td><?= e((string) ($item['quantity'] ?? 1)) ?></td>
                            <td><?= e(format_price((float) ($item['line_total'] ?? (((float) ($item['unit_price'] ?? 0) + (float) ($item['personalization_total'] ?? 0)) * (int) ($item['quantity'] ?? 1))))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($items === []): ?>
                        <tr><td colspan="4" class="text-secondary">Sem itens registados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Moradas</h3>
            <div class="admin-list">
                <?php foreach ($addresses as $address): ?>
                    <article>
                        <div>
                            <strong><?= e(($address['type'] ?? '') === 'billing' ? 'Faturacao' : 'Entrega') ?></strong>
                            <span class="d-block"><?= e((string) ($address['first_name'] ?? '')) ?> <?= e((string) ($address['last_name'] ?? '')) ?></span>
                            <span class="d-block"><?= e((string) ($address['address_line_1'] ?? '')) ?>, <?= e((string) ($address['postal_code'] ?? '')) ?> <?= e((string) ($address['city'] ?? '')) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if ($addresses === []): ?>
                    <p class="text-secondary mb-0">Sem moradas registadas.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Linha temporal</h3>
            <div class="admin-list">
                <?php foreach ($timeline as $entry): ?>
                    <article>
                        <div>
                            <strong><?= e(admin_order_status_label((string) ($entry['status'] ?? 'new'))) ?></strong>
                            <span class="d-block"><?= e((string) ($entry['note'] ?? '')) ?></span>
                            <span class="d-block"><?= e((string) ($entry['created_at'] ?? '')) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Observacoes</h3>
            <p class="text-secondary mb-0"><?= e((string) ($selectedOrder['notes'] ?? 'Sem observacoes.')) ?></p>
        </div>
    </section>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
