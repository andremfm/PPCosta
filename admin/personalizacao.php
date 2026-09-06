<?php
$adminTitle = 'Personalizacao';
$adminSubtitle = 'Tipos, tecnicas, fontes, cores, posicoes e precos.';
require_once __DIR__ . '/includes/header.php';

$rules = personalization_fallback_rules();
?>
<section class="admin-panel">
    <div class="d-flex justify-content-between gap-3 mb-3">
        <h3 class="h5 fw-bold mb-0">Regras de personalizacao</h3>
        <button class="btn btn-dark btn-sm" type="button">Nova regra</button>
    </div>
    <div class="admin-table-wrap">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Regra</th><th>Tipo</th><th>Preco base</th><th>Opcoes</th></tr></thead>
            <tbody>
            <?php foreach ($rules as $rule): ?>
                <tr>
                    <td><?= e($rule['label']) ?></td>
                    <td><?= e($rule['input_type']) ?></td>
                    <td><?= e(format_price((float) $rule['base_extra_price'])) ?></td>
                    <td><?= e((string) count($rule['options'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
