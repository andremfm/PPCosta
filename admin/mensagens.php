<?php
$adminTitle = 'Mensagens';
$adminSubtitle = 'Pedidos de contacto, conversas de clientes, respostas e estados.';
require_once __DIR__ . '/../includes/admin_messages.php';
admin_require();

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];
$selectedThread = null;
$selectedMessages = [];

if (request_method() === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('admin/mensagens.php');
    }

    $threadId = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'reply') {
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($threadId <= 0 || $body === '') {
            flash('danger', 'Escreve uma resposta antes de enviar.');
            redirect('admin/mensagens.php' . ($threadId > 0 ? '?view=' . urlencode((string) $threadId) : ''));
        }

        admin_message_reply($threadId, (int) ($_SESSION['user_id'] ?? 0), $body);
        flash('success', 'Resposta enviada ao cliente.');
        redirect('admin/mensagens.php?view=' . urlencode((string) $threadId));
    }

    if ($action === 'status') {
        admin_message_update_status($threadId, (string) ($_POST['status'] ?? ''));
        flash('success', 'Estado da mensagem atualizado.');
        redirect('admin/mensagens.php?view=' . urlencode((string) $threadId));
    }
}

if (!empty($_GET['view'])) {
    $selectedThread = admin_message_find((int) $_GET['view']);
    $selectedMessages = $selectedThread ? admin_message_entries((int) $selectedThread['id']) : [];
}

$threads = admin_messages_all($filters);
$statuses = admin_message_statuses();

require_once __DIR__ . '/includes/header.php';
?>
<section class="admin-panel mb-4">
    <form class="row g-3 align-items-end" method="get" action="<?= e(url('admin/mensagens.php')) ?>">
        <div class="col-md-6">
            <label class="form-label" for="q">Pesquisar</label>
            <input class="form-control" id="q" name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Assunto, cliente, email ou mensagem">
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
            <a class="btn btn-outline-dark" href="<?= e(url('admin/mensagens.php')) ?>">Limpar</a>
        </div>
    </form>
</section>

<section class="admin-panel mb-4">
    <h3 class="h5 fw-bold mb-3">Pedidos recebidos</h3>
    <div class="admin-table-wrap">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Assunto</th><th>Cliente</th><th>Estado</th><th>Mensagens</th><th>Ultima atividade</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($threads as $thread): ?>
                <tr>
                    <td>
                        <strong><?= e((string) $thread['subject']) ?></strong>
                        <span class="d-block text-secondary small"><?= e(substr((string) ($thread['latest_body'] ?? ''), 0, 120)) ?></span>
                    </td>
                    <td><?= e(admin_message_customer_label($thread)) ?></td>
                    <td><span class="admin-status"><?= e($statuses[$thread['status']] ?? (string) $thread['status']) ?></span></td>
                    <td><?= e((string) ($thread['messages_count'] ?? 0)) ?></td>
                    <td><?= e((string) ($thread['last_message_at'] ?? $thread['created_at'] ?? '')) ?></td>
                    <td><a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/mensagens.php?view=' . urlencode((string) $thread['id']))) ?>">Ver detalhe</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($threads === []): ?>
                <tr><td colspan="6" class="text-secondary">Ainda nao existem mensagens para os filtros escolhidos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($selectedThread): ?>
    <section class="admin-grid-2">
        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Conversa</h3>
            <div class="admin-message-thread">
                <?php foreach ($selectedMessages as $message): ?>
                    <article class="admin-message-bubble <?= e((string) $message['sender_type']) ?>">
                        <div class="d-flex justify-content-between gap-3 mb-2">
                            <strong><?= e((string) $message['sender_type'] === 'admin' ? 'Administracao' : 'Cliente') ?></strong>
                            <span><?= e((string) $message['created_at']) ?></span>
                        </div>
                        <p><?= nl2br(e((string) $message['body'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Responder</h3>
            <div class="admin-list mb-3">
                <article><strong>Assunto</strong><span><?= e((string) $selectedThread['subject']) ?></span></article>
                <article><strong>Cliente</strong><span><?= e(admin_message_customer_label($selectedThread)) ?></span></article>
                <article><strong>Estado</strong><span><?= e($statuses[$selectedThread['status']] ?? (string) $selectedThread['status']) ?></span></article>
            </div>

            <form class="mb-4" method="post" action="<?= e(url('admin/mensagens.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="id" value="<?= e((string) $selectedThread['id']) ?>">
                <label class="form-label" for="body">Mensagem</label>
                <textarea class="form-control mb-3" id="body" name="body" rows="6" required></textarea>
                <button class="btn btn-dark" type="submit">Enviar resposta</button>
            </form>

            <form method="post" action="<?= e(url('admin/mensagens.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="id" value="<?= e((string) $selectedThread['id']) ?>">
                <label class="form-label" for="message_status">Alterar estado</label>
                <div class="d-flex gap-2">
                    <select class="form-select" id="message_status" name="status">
                        <?php foreach ($statuses as $status => $label): ?>
                            <option value="<?= e($status) ?>" <?= $selectedThread['status'] === $status ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-dark" type="submit">Atualizar</button>
                </div>
            </form>
        </div>
    </section>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
