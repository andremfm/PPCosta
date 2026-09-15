<?php
$adminTitle = 'Configuracoes';
$adminSubtitle = 'Dados da loja, fiscalidade, envios, pagamentos, emails e textos legais.';
require_once __DIR__ . '/../includes/admin_settings.php';
admin_require();

if (request_method() === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('admin/configuracoes.php');
    }

    $action = $_POST['action'] ?? '';
    try {

    if ($action === 'save_settings') {
        admin_settings_save($_POST);
        flash('success', 'Configuracoes guardadas.');
        redirect('admin/configuracoes.php');
    }

    if ($action === 'toggle_method') {
        admin_config_toggle_method((string) ($_POST['table'] ?? ''), (int) ($_POST['id'] ?? 0));
        flash('success', 'Metodo atualizado.');
        redirect('admin/configuracoes.php');
    }
    } catch (Throwable $exception) {
        flash('danger', $exception instanceof DomainException ? $exception->getMessage() : 'Nao foi possivel guardar as configuracoes.');
        redirect('admin/configuracoes.php');
    }
}

$settings = store_settings_all();
$groups = admin_settings_groups();
$paymentMethods = admin_config_methods('payment_methods');
$shippingMethods = admin_config_methods('shipping_methods');

require_once __DIR__ . '/includes/header.php';
?>
<section class="admin-panel mb-4">
    <form method="post" action="<?= e(url('admin/configuracoes.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_settings">

        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div>
                <h3 class="h5 fw-bold mb-1">Configuracao global</h3>
                <p class="text-secondary mb-0">Estes dados alimentam checkout, pagamentos, contactos e operacao diaria.</p>
            </div>
            <button class="btn btn-dark align-self-md-start" type="submit">Guardar configuracoes</button>
        </div>

        <?php foreach ($groups as $groupTitle => $fields): ?>
            <div class="admin-settings-group mb-4">
                <h4 class="h6 fw-bold mb-3"><?= e($groupTitle) ?></h4>
                <div class="row g-3">
                    <?php foreach ($fields as $key => $field): ?>
                        <div class="<?= $field['type'] === 'textarea' ? 'col-12' : 'col-md-6 col-xl-4' ?>">
                            <label class="form-label" for="<?= e($key) ?>"><?= e($field['label']) ?></label>
                            <?php if ($field['type'] === 'textarea'): ?>
                                <textarea class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" rows="4"><?= e((string) ($settings[$key] ?? '')) ?></textarea>
                            <?php elseif ($field['type'] === 'select'): ?>
                                <select class="form-select" id="<?= e($key) ?>" name="<?= e($key) ?>">
                                    <?php foreach (($field['options'] ?? []) as $value => $label): ?>
                                        <option value="<?= e((string) $value) ?>" <?= ($settings[$key] ?? '') === (string) $value ? 'selected' : '' ?>><?= e((string) $label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" type="<?= e($field['type']) ?>" step="<?= $field['type'] === 'number' ? '0.01' : '' ?>" value="<?= e((string) ($settings[$key] ?? '')) ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </form>
</section>

<section class="admin-grid-2">
    <div class="admin-panel">
        <h3 class="h5 fw-bold mb-3">Metodos de pagamento</h3>
        <div class="admin-table-wrap">
            <table class="table admin-table align-middle mb-0">
                <thead><tr><th>Metodo</th><th>Codigo</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($paymentMethods as $method): ?>
                    <tr>
                        <td><?= e((string) $method['name']) ?></td>
                        <td><?= e((string) $method['code']) ?></td>
                        <td><span class="admin-status"><?= (int) $method['is_active'] === 1 ? 'Ativo' : 'Inativo' ?></span></td>
                        <td class="text-end">
                            <form method="post" action="<?= e(url('admin/configuracoes.php')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle_method">
                                <input type="hidden" name="table" value="payment_methods">
                                <input type="hidden" name="id" value="<?= e((string) $method['id']) ?>">
                                <button class="btn btn-outline-dark btn-sm" type="submit"><?= (int) $method['is_active'] === 1 ? 'Desativar' : 'Ativar' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-panel">
        <h3 class="h5 fw-bold mb-3">Metodos de envio</h3>
        <div class="admin-table-wrap">
            <table class="table admin-table align-middle mb-0">
                <thead><tr><th>Metodo</th><th>Codigo</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($shippingMethods as $method): ?>
                    <tr>
                        <td><?= e((string) $method['name']) ?></td>
                        <td><?= e((string) $method['code']) ?></td>
                        <td><span class="admin-status"><?= (int) $method['is_active'] === 1 ? 'Ativo' : 'Inativo' ?></span></td>
                        <td class="text-end">
                            <form method="post" action="<?= e(url('admin/configuracoes.php')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle_method">
                                <input type="hidden" name="table" value="shipping_methods">
                                <input type="hidden" name="id" value="<?= e((string) $method['id']) ?>">
                                <button class="btn btn-outline-dark btn-sm" type="submit"><?= (int) $method['is_active'] === 1 ? 'Desativar' : 'Ativar' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
