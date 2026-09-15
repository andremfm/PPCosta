<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_products.php';
require_once __DIR__ . '/../includes/admin_product_personalization.php';
admin_require();
$productId = (int) ($_GET['product_id'] ?? 0);
$product = admin_product_find($productId);
if (!$product) {
    flash('danger', 'Produto nao encontrado.');
    redirect('admin/produtos.php');
}
$base = 'admin/produto-personalizacao.php?product_id=' . $productId;
$error = '';
if (request_method() === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $error = 'Sessao expirada. Atualiza a pagina e tenta novamente.';
    } else {
        try {
            $savedId = admin_product_personalization_save($productId, $_POST);
            flash('success', 'Personalizacao guardada.');
            redirect($base . '&edit=' . $savedId);
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log('Product personalization save: ' . $exception->getMessage());
            $error = 'Nao foi possivel guardar a personalizacao.';
        }
    }
}
$rules = admin_product_personalization_rules($productId);
$types = db()->query('SELECT * FROM personalization_types ORDER BY name, id')->fetchAll();
$editId = (int) ($_POST['id'] ?? $_GET['edit'] ?? 0);
$editing = null;
foreach ($rules as $rule) {
    if ((int) $rule['id'] === $editId) {
        $editing = $rule;
    }
}
$typeId = (int) ($editing['personalization_type_id'] ?? $_POST['personalization_type_id'] ?? $_GET['type'] ?? 0);
$type = null;
foreach ($types as $candidate) {
    if ((int) $candidate['id'] === $typeId) {
        $type = $candidate;
    }
}
$form = $editing ?? ['id' => 0, 'label' => $type['name'] ?? '', 'base_extra_price' => '0.00', 'min_length' => '', 'max_length' => '', 'sort_order' => 0, 'is_required' => 0, 'is_active' => 1, 'options_mode' => 'selected'];
$selected = [];
$prices = [];
if ($editing) {
    $stmt = db()->prepare('SELECT * FROM product_personalization_options WHERE product_personalization_id=?');
    $stmt->execute([$editId]);
    foreach ($stmt->fetchAll() as $mapping) {
        $selected[] = (string) $mapping['personalization_option_id'];
        $prices[(string) $mapping['personalization_option_id']] = $mapping['extra_price'] ?? '';
    }
    if ($form['options_mode'] === 'legacy') {
        $form['options_mode'] = $selected !== [] || ($type['slug'] ?? '') === 'tecnica' ? 'selected' : 'all';
    }
}
if ($error !== '' && request_method() === 'POST') {
    foreach (['label', 'base_extra_price', 'min_length', 'max_length', 'sort_order', 'options_mode'] as $field) {
        if (isset($_POST[$field]) && is_scalar($_POST[$field])) {
            $form[$field] = (string) $_POST[$field];
        }
    }
    $form['is_active'] = !empty($_POST['is_active']);
    $form['is_required'] = !empty($_POST['is_required']);
    $selected = array_values(array_filter(is_array($_POST['options'] ?? null) ? $_POST['options'] : [], 'is_scalar'));
    $prices = array_filter(is_array($_POST['prices'] ?? null) ? $_POST['prices'] : [], 'is_scalar');
}
$options = [];
if ($type) {
    $stmt = db()->prepare('SELECT * FROM personalization_options WHERE personalization_type_id=? ORDER BY sort_order,id');
    $stmt->execute([$typeId]);
    $options = $stmt->fetchAll();
}
$adminTitle = 'Personalizacoes do produto';
$adminSubtitle = $product['name'];
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap gap-2 mb-4">
    <a class="btn btn-outline-dark" href="<?= e(url('admin/produtos.php?edit=' . $productId)) ?>">Voltar ao produto</a>
    <a class="btn btn-outline-dark" href="<?= e(url('admin/personalizacao.php')) ?>">Tipos e opcoes globais</a>
</div>
<?php if ($error !== ''): ?><div role="alert" class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if (!$product['is_personalizable']): ?><div class="alert alert-warning">A personalizacao esta desativada neste produto.</div><?php endif; ?>
<section class="admin-panel mb-4">
    <h2 class="h5 mb-3">Campos do produto</h2>
    <?php if ($rules === []): ?><p class="text-secondary">Ainda nao existem campos associados.</p><?php endif; ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Campo</th><th>Tipo</th><th>Estado</th><th>Obrigatorio</th><th>Acrescimo base</th><th></th></tr></thead>
            <tbody><?php foreach ($rules as $rule): ?>
                <tr><td><?= e($rule['label']) ?></td><td><?= e($rule['type_name']) ?></td>
                    <td><?= $rule['is_active'] && $rule['type_active'] ? 'Ativo' : 'Inativo' ?></td>
                    <td><?= $rule['is_required'] ? 'Sim' : 'Nao' ?></td><td><?= e(format_price((float) $rule['base_extra_price'])) ?></td>
                    <td><a class="btn btn-sm btn-outline-dark" aria-label="Editar <?= e($rule['label']) ?>" href="<?= e(url($base . '&edit=' . $rule['id'])) ?>">Editar</a></td></tr>
            <?php endforeach; ?></tbody>
        </table>
    </div>
    <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="product_id" value="<?= $productId ?>">
        <div class="col-md-8"><label for="new-type" class="form-label">Adicionar campo</label><select class="form-select" id="new-type" name="type" required>
            <option value="">Selecionar tipo</option>
            <?php foreach ($types as $candidate): if (!$candidate['is_active'] || in_array((int) $candidate['id'], array_map('intval', array_column($rules, 'personalization_type_id')), true)) { continue; } ?>
                <option value="<?= (int) $candidate['id'] ?>"><?= e($candidate['name']) ?></option>
            <?php endforeach; ?>
        </select></div><div class="col-md-4"><button class="btn btn-dark" type="submit">Configurar campo</button></div>
    </form>
</section>
<?php if ($type): ?>
<section class="admin-panel mb-4">
    <h2 class="h5 mb-3"><?= e(($editing ? 'Editar: ' : 'Adicionar: ') . $type['name']) ?></h2>
    <?php if (!$type['is_active']): ?><div class="alert alert-warning">Este tipo esta desativado globalmente.</div><?php endif; ?>
    <form method="post" action="<?= e(url($base . ($editing ? '&edit=' . $editId : '&type=' . $typeId))) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
        <input type="hidden" name="personalization_type_id" value="<?= $typeId ?>">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="field-label">Nome do campo</label><input class="form-control" id="field-label" name="label" maxlength="160" required value="<?= e($form['label']) ?>"></div>
            <div class="col-md-3"><label class="form-label" for="base-price">Acrescimo base (EUR)</label><input class="form-control" type="number" id="base-price" name="base_extra_price" min="0" max="99999999.99" step="0.01" required value="<?= e((string) $form['base_extra_price']) ?>"></div>
            <div class="col-md-3"><label class="form-label" for="field-order">Ordem</label><input class="form-control" type="number" id="field-order" name="sort_order" min="0" max="10000" required value="<?= e((string) $form['sort_order']) ?>"></div>
            <?php if (in_array($type['input_type'], ['text', 'textarea'], true)): ?>
                <?php foreach (['min_length' => 'Minimo de caracteres', 'max_length' => 'Maximo de caracteres'] as $field => $label): ?>
                    <div class="col-md-6"><label class="form-label" for="<?= $field ?>"><?= $label ?></label><input class="form-control" type="number" id="<?= $field ?>" name="<?= $field ?>" min="0" max="10000" value="<?= e((string) $form[$field]) ?>"></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="col-12 d-flex flex-wrap gap-4">
                <label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?= $form['is_active'] ? 'checked' : '' ?>> Campo ativo</label>
                <label class="form-check"><input class="form-check-input" type="checkbox" name="is_required" value="1" <?= $form['is_required'] ? 'checked' : '' ?>> Obrigatorio</label>
            </div>
        </div>
        <?php if (in_array($type['input_type'], ['select', 'color', 'font', 'position', 'technique'], true)): ?>
            <fieldset class="mt-4"><legend class="h6">Opcoes disponiveis</legend>
                <label class="form-check"><input class="form-check-input" type="radio" name="options_mode" value="selected" <?= $form['options_mode'] === 'selected' ? 'checked' : '' ?>> Apenas as selecionadas, com precos deste produto</label>
                <label class="form-check"><input class="form-check-input" type="radio" name="options_mode" value="all" <?= $form['options_mode'] === 'all' ? 'checked' : '' ?>> Todas as opcoes ativas, com precos globais</label>
                <?php if ($options === []): ?><p class="text-secondary mt-3">Ainda nao existem opcoes neste tipo.</p><?php endif; ?>
                <div class="table-responsive mt-3"><table class="table align-middle">
                    <thead><tr><th>Opcao</th><th>Preco global</th><th>Preco neste produto (EUR, opcional)</th></tr></thead>
                    <tbody><?php foreach ($options as $option): $oid = (string) $option['id']; ?>
                        <tr><td><label class="form-check"><input class="form-check-input" type="checkbox" name="options[]" value="<?= e($oid) ?>" <?= in_array($oid, $selected, true) ? 'checked' : '' ?>> <?= e($option['label']) ?><?= !$option['is_active'] ? ' (inativa)' : '' ?></label></td>
                            <td><?= e(format_price((float) $option['extra_price'])) ?></td><td><input class="form-control" type="number" min="0" max="99999999.99" step="0.01" name="prices[<?= e($oid) ?>]" aria-label="Preco de <?= e($option['label']) ?>" value="<?= e((string) ($prices[$oid] ?? '')) ?>"></td></tr>
                    <?php endforeach; ?></tbody>
                </table></div>
            </fieldset>
        <?php else: ?><input type="hidden" name="options_mode" value="selected"><?php endif; ?>
        <div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-dark" type="submit">Guardar personalizacao</button><a class="btn btn-outline-dark" href="<?= e(url($base)) ?>">Cancelar</a></div>
    </form>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
