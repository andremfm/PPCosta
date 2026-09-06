<?php
$adminTitle = 'Clientes';
$adminSubtitle = 'CRUD de clientes, historico de compras, enderecos, notas, newsletter e bloqueios.';
require_once __DIR__ . '/../includes/admin_customers.php';
admin_require();

$editingCustomer = null;
$viewCustomer = null;
$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];

if (request_method() === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('admin/clientes.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $errors = admin_customer_validate($_POST);

        if ($errors !== []) {
            foreach ($errors as $error) {
                flash('danger', $error);
            }
            set_old($_POST);
            redirect('admin/clientes.php' . (!empty($_POST['id']) ? '?edit=' . urlencode((string) $_POST['id']) : ''));
        }

        admin_customer_save($_POST);
        clear_old();
        flash('success', 'Cliente guardado.');
        redirect('admin/clientes.php');
    }

    if ($action === 'delete') {
        admin_customer_delete((int) ($_POST['id'] ?? 0));
        flash('success', 'Cliente removido ou bloqueado.');
        redirect('admin/clientes.php');
    }

    if ($action === 'note') {
        admin_customer_add_note((int) ($_POST['id'] ?? 0), (string) ($_POST['note'] ?? ''));
        flash('success', 'Nota adicionada ao cliente.');
        redirect('admin/clientes.php?view=' . urlencode((string) ($_POST['id'] ?? '')));
    }
}

if (!empty($_GET['edit'])) {
    $editingCustomer = admin_customer_find((int) $_GET['edit']);
}

if (!empty($_GET['view'])) {
    $viewCustomer = admin_customer_find((int) $_GET['view']);
}

$customers = admin_customers_all($filters);
$formCustomer = array_merge(admin_customer_defaults(), $editingCustomer ?? [], $_SESSION['_old'] ?? []);
$statuses = admin_customer_statuses();

require_once __DIR__ . '/includes/header.php';
?>
<section class="admin-panel mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-3">
        <div>
            <h3 class="h5 fw-bold mb-1"><?= $editingCustomer ? 'Editar cliente' : 'Novo cliente' ?></h3>
            <p class="text-secondary mb-0">Dados principais, estado de conta, newsletter e acesso.</p>
        </div>
        <?php if ($editingCustomer): ?>
            <a class="btn btn-outline-dark btn-sm align-self-xl-start" href="<?= e(url('admin/clientes.php')) ?>">Novo cliente</a>
        <?php endif; ?>
    </div>
    <form method="post" action="<?= e(url('admin/clientes.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e((string) $formCustomer['id']) ?>">

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="first_name">Primeiro nome</label>
                <input class="form-control" id="first_name" name="first_name" type="text" value="<?= e((string) $formCustomer['first_name']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="last_name">Apelido</label>
                <input class="form-control" id="last_name" name="last_name" type="text" value="<?= e((string) $formCustomer['last_name']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" id="email" name="email" type="email" value="<?= e((string) $formCustomer['email']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="phone">Telefone</label>
                <input class="form-control" id="phone" name="phone" type="tel" value="<?= e((string) $formCustomer['phone']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Estado</label>
                <select class="form-select" id="status" name="status">
                    <?php foreach ($statuses as $status => $label): ?>
                        <option value="<?= e($status) ?>" <?= $formCustomer['status'] === $status ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" id="password" name="password" type="password" minlength="8" placeholder="<?= $editingCustomer ? 'Manter atual' : 'Opcional' ?>">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <label class="form-check admin-inline-check">
                    <input class="form-check-input" name="newsletter_opt_in" type="checkbox" value="1" <?= !empty($formCustomer['newsletter_opt_in']) ? 'checked' : '' ?>>
                    <span class="form-check-label">Cliente subscrito na newsletter</span>
                </label>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <button class="btn btn-dark" type="submit">Guardar cliente</button>
            <?php if ($editingCustomer): ?>
                <a class="btn btn-outline-dark" href="<?= e(url('admin/clientes.php')) ?>">Cancelar edicao</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="admin-panel mb-4">
    <form class="row g-3 align-items-end" method="get" action="<?= e(url('admin/clientes.php')) ?>">
        <div class="col-md-6">
            <label class="form-label" for="q">Pesquisar</label>
            <input class="form-control" id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Nome, email ou telefone">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="filter_status">Estado</label>
            <select class="form-select" id="filter_status" name="status">
                <option value="">Todos</option>
                <?php foreach ($statuses as $status => $label): ?>
                    <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-dark flex-fill" type="submit">Filtrar</button>
            <a class="btn btn-outline-dark" href="<?= e(url('admin/clientes.php')) ?>">Limpar</a>
        </div>
    </form>
</section>

<section class="admin-panel mb-4">
    <h3 class="h5 fw-bold mb-3">Clientes</h3>
    <div class="admin-table-wrap">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Cliente</th><th>Contacto</th><th>Compras</th><th>Total</th><th>Estado</th><th>Newsletter</th><th>Acoes</th></tr></thead>
            <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td>
                        <strong><?= e(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''))) ?></strong>
                        <span class="d-block text-secondary small">Registo: <?= e((string) ($customer['created_at'] ?? '-')) ?></span>
                    </td>
                    <td>
                        <?= e((string) ($customer['email'] ?? '')) ?>
                        <span class="d-block text-secondary small"><?= e((string) ($customer['phone'] ?? '')) ?></span>
                    </td>
                    <td><?= e((string) ($customer['orders_count'] ?? 0)) ?></td>
                    <td><?= e(format_price((float) ($customer['total_spent'] ?? 0))) ?></td>
                    <td><span class="admin-status"><?= e($statuses[$customer['status'] ?? 'active'] ?? (string) ($customer['status'] ?? 'active')) ?></span></td>
                    <td><?= !empty($customer['newsletter_opt_in']) ? 'Sim' : 'Nao' ?></td>
                    <td>
                        <div class="admin-actions">
                            <a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/clientes.php?view=' . urlencode((string) $customer['id']))) ?>">Ver</a>
                            <a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/clientes.php?edit=' . urlencode((string) $customer['id']))) ?>">Editar</a>
                            <form method="post" action="<?= e(url('admin/clientes.php')) ?>" onsubmit="return confirm('Remover ou bloquear este cliente?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $customer['id']) ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Remover</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($customers === []): ?>
                <tr><td colspan="7" class="text-secondary">Ainda nao existem clientes para os filtros escolhidos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($viewCustomer): ?>
    <?php
    $orders = admin_customer_orders((int) $viewCustomer['id']);
    $addresses = admin_customer_addresses((int) $viewCustomer['id']);
    $notes = admin_customer_notes((int) $viewCustomer['id']);
    ?>
    <section class="admin-grid-2">
        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Historico de compras</h3>
            <div class="admin-list">
                <?php foreach ($orders as $order): ?>
                    <article>
                        <div>
                            <strong><?= e((string) ($order['order_number'] ?? 'Encomenda')) ?></strong>
                            <span class="d-block"><?= e((string) ($order['status'] ?? '')) ?> &middot; <?= e((string) ($order['created_at'] ?? '')) ?></span>
                        </div>
                        <strong><?= e(format_price((float) ($order['grand_total'] ?? ($order['totals']['total'] ?? 0)))) ?></strong>
                    </article>
                <?php endforeach; ?>
                <?php if ($orders === []): ?>
                    <p class="text-secondary mb-0">Sem encomendas registadas.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Enderecos</h3>
            <div class="admin-list">
                <?php foreach ($addresses as $address): ?>
                    <article>
                        <div>
                            <strong><?= e((string) ($address['label'] ?? ucfirst((string) ($address['type'] ?? 'Endereco')))) ?></strong>
                            <span class="d-block"><?= e((string) ($address['address_line_1'] ?? '')) ?>, <?= e((string) ($address['postal_code'] ?? '')) ?> <?= e((string) ($address['city'] ?? '')) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if ($addresses === []): ?>
                    <p class="text-secondary mb-0">Sem enderecos registados.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Notas administrativas</h3>
            <form method="post" action="<?= e(url('admin/clientes.php')) ?>" class="mb-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="note">
                <input type="hidden" name="id" value="<?= e((string) $viewCustomer['id']) ?>">
                <textarea class="form-control mb-2" name="note" rows="3" placeholder="Adicionar nota interna"></textarea>
                <button class="btn btn-dark btn-sm" type="submit">Guardar nota</button>
            </form>
            <div class="admin-list">
                <?php foreach ($notes as $note): ?>
                    <article>
                        <div>
                            <strong><?= e((string) ($note['created_at'] ?? '')) ?></strong>
                            <span class="d-block"><?= e(admin_customer_note_text($note)) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if ($notes === []): ?>
                    <p class="text-secondary mb-0">Sem notas internas.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Resumo</h3>
            <div class="admin-status-grid">
                <span><?= e((string) ($viewCustomer['email'] ?? '')) ?></span>
                <span><?= e($statuses[$viewCustomer['status'] ?? 'active'] ?? 'Ativo') ?></span>
                <span><?= !empty($viewCustomer['newsletter_opt_in']) ? 'Newsletter ativa' : 'Sem newsletter' ?></span>
                <span><?= e((string) ($viewCustomer['last_login_at'] ?? 'Sem login recente')) ?></span>
            </div>
        </div>
    </section>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
