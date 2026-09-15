<?php
$adminTitle = 'Personalizacao';
$adminSubtitle = 'Tipos, tecnicas, fontes, cores, posicoes e precos.';
require_once __DIR__ . '/../includes/admin_personalization.php';
admin_require();

if (request_method() === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('admin/personalizacao.php');
    }

    $action = $_POST['action'] ?? '';
    try {

    if ($action === 'save_type') {
        admin_personalization_save_type($_POST);
        flash('success', 'Tipo de personalizacao guardado.');
        redirect('admin/personalizacao.php');
    }

    if ($action === 'save_option') {
        admin_personalization_save_option($_POST);
        flash('success', 'Opcao de personalizacao guardada.');
        redirect('admin/personalizacao.php');
    }

    if ($action === 'toggle') {
        admin_personalization_toggle((string) ($_POST['table'] ?? ''), (int) ($_POST['id'] ?? 0));
        flash('success', 'Estado atualizado.');
        redirect('admin/personalizacao.php');
    }
    } catch (Throwable $exception) {
        error_log('Personalization save failed: ' . $exception->getMessage());
        flash('danger', 'Nao foi possivel guardar. Confirma os campos e se a opcao ja existe.');
        redirect('admin/personalizacao.php');
    }
}

$types = admin_personalization_types();
$options = admin_personalization_options();
$editingTypeId = (int) ($_GET['edit_type'] ?? 0);
$editingOptionId = (int) ($_GET['edit_option'] ?? 0);
$editingType = null;
$editingOption = null;

foreach ($types as $type) {
    if ((int) $type['id'] === $editingTypeId) {
        $editingType = $type;
        break;
    }
}

foreach ($options as $option) {
    if ((int) $option['id'] === $editingOptionId) {
        $editingOption = $option;
        break;
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="admin-grid-2 mb-4">
    <div class="admin-panel">
        <h3 class="h5 fw-bold mb-3"><?= $editingType ? 'Editar tipo' : 'Novo tipo' ?></h3>
        <form method="post" action="<?= e(url('admin/personalizacao.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_type">
            <input type="hidden" name="id" value="<?= e((string) ($editingType['id'] ?? 0)) ?>">
            <label class="form-label" for="type_name">Nome</label>
            <input class="form-control mb-2" id="type_name" name="name" type="text" value="<?= e((string) ($editingType['name'] ?? '')) ?>" required>
            <label class="form-label" for="type_slug">Slug</label>
            <input class="form-control mb-2" id="type_slug" name="slug" type="text" value="<?= e((string) ($editingType['slug'] ?? '')) ?>">
            <label class="form-label" for="input_type">Tipo de campo</label>
            <select class="form-select mb-3" id="input_type" name="input_type">
                <?php foreach (['text', 'textarea', 'select', 'color', 'font', 'file', 'position', 'technique'] as $inputType): ?>
                    <option value="<?= e($inputType) ?>" <?= ($editingType['input_type'] ?? '') === $inputType ? 'selected' : '' ?>><?= e($inputType) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="admin-check-grid mb-3">
                <label class="form-check"><input class="form-check-input" name="is_required" type="checkbox" value="1" <?= !empty($editingType['is_required']) ? 'checked' : '' ?>><span class="form-check-label">Obrigatorio</span></label>
                <label class="form-check"><input class="form-check-input" name="is_active" type="checkbox" value="1" <?= $editingType === null || !empty($editingType['is_active']) ? 'checked' : '' ?>><span class="form-check-label">Ativo</span></label>
            </div>
            <button class="btn btn-dark" type="submit">Guardar tipo</button>
            <?php if ($editingType): ?>
                <a class="btn btn-outline-dark ms-2" href="<?= e(url('admin/personalizacao.php')) ?>">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="admin-panel">
        <h3 class="h5 fw-bold mb-3"><?= $editingOption ? 'Editar opcao' : 'Nova opcao' ?></h3>
        <form method="post" action="<?= e(url('admin/personalizacao.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_option">
            <input type="hidden" name="id" value="<?= e((string) ($editingOption['id'] ?? 0)) ?>">
            <label class="form-label" for="personalization_type_id">Tipo</label>
            <select class="form-select mb-2" id="personalization_type_id" name="personalization_type_id" required>
                <?php foreach ($types as $type): ?>
                    <option value="<?= e((string) $type['id']) ?>" <?= (int) ($editingOption['personalization_type_id'] ?? 0) === (int) $type['id'] ? 'selected' : '' ?>><?= e((string) $type['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label">Label</label><input class="form-control" name="label" type="text" value="<?= e((string) ($editingOption['label'] ?? '')) ?>" required></div>
                <div class="col-md-6"><label class="form-label">Valor</label><input class="form-control" name="value" type="text" value="<?= e((string) ($editingOption['value'] ?? '')) ?>" required></div>
                <div class="col-md-4"><label class="form-label">Preco extra</label><input class="form-control" name="extra_price" type="number" min="0" step="0.01" value="<?= e((string) ($editingOption['extra_price'] ?? '0')) ?>"></div>
                <div class="col-md-4"><label class="form-label">Cor HEX</label><input class="form-control" name="color_hex" type="text" value="<?= e((string) ($editingOption['color_hex'] ?? '')) ?>" placeholder="#111827"></div>
                <div class="col-md-4"><label class="form-label">Ordem</label><input class="form-control" name="sort_order" type="number" value="<?= e((string) ($editingOption['sort_order'] ?? '0')) ?>"></div>
            </div>
            <label class="form-check mt-3"><input class="form-check-input" name="is_active" type="checkbox" value="1" <?= $editingOption === null || !empty($editingOption['is_active']) ? 'checked' : '' ?>><span class="form-check-label">Ativa</span></label>
            <button class="btn btn-dark mt-3" type="submit">Guardar opcao</button>
            <?php if ($editingOption): ?>
                <a class="btn btn-outline-dark mt-3 ms-2" href="<?= e(url('admin/personalizacao.php')) ?>">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>
</section>

<section class="admin-panel mb-4">
    <h3 class="h5 fw-bold mb-3">Tipos de personalizacao</h3>
    <div class="admin-table-wrap">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Nome</th><th>Slug</th><th>Campo</th><th>Obrig.</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($types as $type): ?>
                <tr>
                    <td><?= e((string) $type['name']) ?></td>
                    <td><?= e((string) $type['slug']) ?></td>
                    <td><?= e((string) $type['input_type']) ?></td>
                    <td><?= !empty($type['is_required']) ? 'Sim' : 'Nao' ?></td>
                    <td><span class="admin-status"><?= !empty($type['is_active']) ? 'Ativo' : 'Inativo' ?></span></td>
                    <td>
                        <div class="admin-actions">
                            <a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/personalizacao.php?edit_type=' . urlencode((string) $type['id']))) ?>">Editar</a>
                            <form method="post" action="<?= e(url('admin/personalizacao.php')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="table" value="personalization_types">
                                <input type="hidden" name="id" value="<?= e((string) $type['id']) ?>">
                                <button class="btn btn-sm btn-outline-dark" type="submit">Alternar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="admin-panel">
    <h3 class="h5 fw-bold mb-3">Opcoes</h3>
    <div class="admin-table-wrap">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Tipo</th><th>Label</th><th>Valor</th><th>Preco</th><th>Cor</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($options as $option): ?>
                <tr>
                    <td><?= e((string) $option['type_name']) ?></td>
                    <td><?= e((string) $option['label']) ?></td>
                    <td><?= e((string) $option['value']) ?></td>
                    <td><?= e(format_price((float) $option['extra_price'])) ?></td>
                    <td><?= e((string) ($option['color_hex'] ?? '')) ?></td>
                    <td><span class="admin-status"><?= !empty($option['is_active']) ? 'Ativa' : 'Inativa' ?></span></td>
                    <td>
                        <div class="admin-actions">
                            <a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/personalizacao.php?edit_option=' . urlencode((string) $option['id']))) ?>">Editar</a>
                            <form method="post" action="<?= e(url('admin/personalizacao.php')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="table" value="personalization_options">
                                <input type="hidden" name="id" value="<?= e((string) $option['id']) ?>">
                                <button class="btn btn-sm btn-outline-dark" type="submit">Alternar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
