<?php
$adminTitle = 'Avaliacoes';
$adminSubtitle = 'Moderacao de classificacoes, comentarios e respostas publicas.';
require_once __DIR__ . '/../includes/admin_reviews.php';
admin_require();

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];
$selectedReview = null;

if (request_method() === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('admin/avaliacoes.php');
    }

    if (($_POST['action'] ?? '') === 'save') {
        $reviewId = (int) ($_POST['id'] ?? 0);
        admin_review_update($reviewId, (string) ($_POST['status'] ?? ''), (string) ($_POST['admin_reply'] ?? ''));
        flash('success', 'Avaliacao atualizada.');
        redirect('admin/avaliacoes.php?view=' . urlencode((string) $reviewId));
    }
}

if (!empty($_GET['view'])) {
    $selectedReview = admin_review_find((int) $_GET['view']);
}

$reviews = admin_reviews_all($filters);
$statuses = review_statuses();

require_once __DIR__ . '/includes/header.php';
?>
<section class="admin-panel mb-4">
    <form class="row g-3 align-items-end" method="get" action="<?= e(url('admin/avaliacoes.php')) ?>">
        <div class="col-md-6">
            <label class="form-label" for="q">Pesquisar</label>
            <input class="form-control" id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Produto, SKU, cliente ou comentario">
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
            <a class="btn btn-outline-dark" href="<?= e(url('admin/avaliacoes.php')) ?>">Limpar</a>
        </div>
    </form>
</section>

<section class="admin-panel mb-4">
    <h3 class="h5 fw-bold mb-3">Avaliacoes recebidas</h3>
    <div class="admin-table-wrap">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Produto</th><th>Cliente</th><th>Nota</th><th>Estado</th><th>Data</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($reviews as $review): ?>
                <tr>
                    <td>
                        <strong><?= e((string) $review['product_name']) ?></strong>
                        <span class="d-block text-secondary small"><?= e((string) $review['sku']) ?> · <?= e((string) ($review['title'] ?? '')) ?></span>
                    </td>
                    <td><?= e(admin_review_customer_label($review)) ?></td>
                    <td><span class="review-stars"><?= e(review_rating_label((int) $review['rating'])) ?></span></td>
                    <td><span class="admin-status"><?= e($statuses[$review['status']] ?? (string) $review['status']) ?></span></td>
                    <td><?= e((string) $review['created_at']) ?></td>
                    <td><a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/avaliacoes.php?view=' . urlencode((string) $review['id']))) ?>">Moderar</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($reviews === []): ?>
                <tr><td colspan="6" class="text-secondary">Ainda nao existem avaliacoes para os filtros escolhidos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($selectedReview): ?>
    <section class="admin-grid-2">
        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Detalhe da avaliacao</h3>
            <div class="admin-list mb-3">
                <article><strong>Produto</strong><span><?= e((string) $selectedReview['product_name']) ?></span></article>
                <article><strong>Cliente</strong><span><?= e(admin_review_customer_label($selectedReview)) ?></span></article>
                <article><strong>Nota</strong><span class="review-stars"><?= e(review_rating_label((int) $selectedReview['rating'])) ?></span></article>
                <article><strong>Estado</strong><span><?= e($statuses[$selectedReview['status']] ?? (string) $selectedReview['status']) ?></span></article>
            </div>
            <h4 class="h6 fw-bold"><?= e((string) ($selectedReview['title'] ?? 'Sem titulo')) ?></h4>
            <p class="text-secondary mb-0"><?= nl2br(e((string) ($selectedReview['comment'] ?? ''))) ?></p>
        </div>

        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Moderacao</h3>
            <form method="post" action="<?= e(url('admin/avaliacoes.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= e((string) $selectedReview['id']) ?>">
                <label class="form-label" for="review_status">Estado</label>
                <select class="form-select mb-3" id="review_status" name="status">
                    <?php foreach ($statuses as $status => $label): ?>
                        <option value="<?= e($status) ?>" <?= $selectedReview['status'] === $status ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label" for="admin_reply">Resposta publica da administracao</label>
                <textarea class="form-control mb-3" id="admin_reply" name="admin_reply" rows="5"><?= e((string) ($selectedReview['admin_reply'] ?? '')) ?></textarea>
                <button class="btn btn-dark" type="submit">Guardar moderacao</button>
            </form>
        </div>
    </section>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
