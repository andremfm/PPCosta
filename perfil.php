<?php
$pageTitle = 'Area cliente | PPCosta';
require_once __DIR__ . '/includes/customer.php';
require_auth();

$user = current_user();
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (request_method() === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('perfil.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        customer_update_profile($userId, $_POST);
        flash('success', 'Perfil atualizado.');
        redirect('perfil.php#perfil');
    }

    if ($action === 'password') {
        if (strlen((string) ($_POST['new_password'] ?? '')) < 8 || ($_POST['new_password'] ?? '') !== ($_POST['new_password_confirmation'] ?? '')) {
            flash('danger', 'A nova password deve ter pelo menos 8 caracteres e coincidir com a confirmacao.');
        } elseif (customer_change_password($userId, (string) ($_POST['current_password'] ?? ''), (string) $_POST['new_password'])) {
            flash('success', 'Password alterada.');
        } else {
            flash('danger', 'Nao foi possivel alterar a password. Confirma a password atual.');
        }
        redirect('perfil.php#seguranca');
    }

    if ($action === 'address') {
        customer_save_address($userId, $_POST);
        flash('success', 'Morada guardada.');
        redirect('perfil.php#moradas');
    }

    if ($action === 'message') {
        customer_send_message($userId, $_POST);
        flash('success', 'Mensagem enviada.');
        redirect('perfil.php#mensagens');
    }

    if ($action === 'review') {
        customer_save_review($userId, $_POST);
        flash('success', 'Avaliacao recebida e pendente de moderacao.');
        redirect('perfil.php#avaliacoes');
    }
}

$sessionProfile = $_SESSION[customer_session_key('profile')] ?? [];
$displayUser = array_merge($user ?? [], $sessionProfile);
$addresses = customer_addresses($userId);
$orders = customer_orders($userId);
$wishlist = customer_wishlist($userId);
$reviews = customer_reviews($userId);
$messages = customer_message_threads($userId);
$notifications = customer_notifications($userId);

require_once __DIR__ . '/includes/header.php';
?>
<section class="section-pad account-page">
    <div class="container">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <p class="text-uppercase text-secondary fw-semibold small mb-2">Conta</p>
                <h1 class="section-title mb-0">Area cliente</h1>
            </div>
            <div class="align-self-lg-end text-secondary">
                <?= e(($displayUser['first_name'] ?? '') . ' ' . ($displayUser['last_name'] ?? '')) ?>
            </div>
        </div>

        <div class="row g-4">
            <aside class="col-lg-3">
                <nav class="account-nav">
                    <a href="#perfil">Perfil</a>
                    <a href="#seguranca">Password</a>
                    <a href="#moradas">Moradas</a>
                    <a href="#encomendas">Encomendas</a>
                    <a href="#downloads">Downloads</a>
                    <a href="#wishlist">Wishlist</a>
                    <a href="#avaliacoes">Avaliacoes</a>
                    <a href="#mensagens">Mensagens</a>
                    <a href="#notificacoes">Notificacoes</a>
                </nav>
            </aside>

            <div class="col-lg-9">
                <section id="perfil" class="account-panel">
                    <h2 class="h4 fw-bold mb-3">Perfil</h2>
                    <form method="post" action="<?= e(url('perfil.php')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="profile">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">Nome</label>
                                <input class="form-control" id="first_name" name="first_name" type="text" value="<?= e($displayUser['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">Apelido</label>
                                <input class="form-control" id="last_name" name="last_name" type="text" value="<?= e($displayUser['last_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input class="form-control" id="email" type="email" value="<?= e($displayUser['email'] ?? '') ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Telefone</label>
                                <input class="form-control" id="phone" name="phone" type="tel" value="<?= e($displayUser['phone'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" id="newsletter_opt_in" name="newsletter_opt_in" type="checkbox" value="1" <?= !empty($displayUser['newsletter_opt_in']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="newsletter_opt_in">Receber novidades e promocoes.</label>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-dark mt-4" type="submit">Guardar perfil</button>
                    </form>
                </section>

                <section id="seguranca" class="account-panel">
                    <h2 class="h4 fw-bold mb-3">Alterar password</h2>
                    <form method="post" action="<?= e(url('perfil.php')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="password">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="current_password">Password atual</label>
                                <input class="form-control" id="current_password" name="current_password" type="password" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="new_password">Nova password</label>
                                <input class="form-control" id="new_password" name="new_password" type="password" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="new_password_confirmation">Confirmar</label>
                                <input class="form-control" id="new_password_confirmation" name="new_password_confirmation" type="password" required>
                            </div>
                        </div>
                        <button class="btn btn-dark mt-4" type="submit">Alterar password</button>
                    </form>
                </section>

                <section id="moradas" class="account-panel">
                    <h2 class="h4 fw-bold mb-3">Moradas</h2>
                    <div class="account-list mb-4">
                        <?php foreach ($addresses as $address): ?>
                            <article>
                                <strong><?= e($address['label'] ?: ucfirst($address['type'])) ?></strong>
                                <span><?= e($address['address_line_1']) ?>, <?= e($address['postal_code']) ?> <?= e($address['city']) ?></span>
                            </article>
                        <?php endforeach; ?>
                        <?php if ($addresses === []): ?>
                            <p class="text-secondary mb-0">Ainda nao existem moradas guardadas.</p>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="<?= e(url('perfil.php')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="address">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="type">Tipo</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="shipping">Entrega</option>
                                    <option value="billing">Faturacao</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="label">Etiqueta</label>
                                <input class="form-control" id="label" name="label" type="text" placeholder="Casa, empresa...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="address_phone">Telefone</label>
                                <input class="form-control" id="address_phone" name="phone" type="tel">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="address_first_name">Nome</label>
                                <input class="form-control" id="address_first_name" name="first_name" type="text" value="<?= e($displayUser['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="address_last_name">Apelido</label>
                                <input class="form-control" id="address_last_name" name="last_name" type="text" value="<?= e($displayUser['last_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="address_line_1">Morada</label>
                                <input class="form-control" id="address_line_1" name="address_line_1" type="text" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="postal_code">Codigo postal</label>
                                <input class="form-control" id="postal_code" name="postal_code" type="text" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="city">Localidade</label>
                                <input class="form-control" id="city" name="city" type="text" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="district">Distrito</label>
                                <input class="form-control" id="district" name="district" type="text">
                            </div>
                        </div>
                        <button class="btn btn-dark mt-4" type="submit">Guardar morada</button>
                    </form>
                </section>

                <section id="encomendas" class="account-panel">
                    <h2 class="h4 fw-bold mb-3">Encomendas</h2>
                    <div class="account-list">
                        <?php foreach ($orders as $order): ?>
                            <article>
                                <strong><?= e($order['order_number'] ?? 'Encomenda') ?></strong>
                                <span><?= e($order['status'] ?? 'Recebida') ?> · <?= e(format_price((float) ($order['grand_total'] ?? $order['totals']['total'] ?? 0))) ?></span>
                            </article>
                        <?php endforeach; ?>
                        <?php if ($orders === []): ?>
                            <p class="text-secondary mb-0">Ainda nao existem encomendas.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="downloads" class="account-panel">
                    <h2 class="h4 fw-bold mb-3">Downloads</h2>
                    <div class="account-list">
                        <article>
                            <strong>Ficheiros finais</strong>
                            <span>Os ficheiros digitais e provas de producao ficarao disponiveis aqui.</span>
                        </article>
                    </div>
                </section>

                <section id="wishlist" class="account-panel">
                    <h2 class="h4 fw-bold mb-3">Wishlist</h2>
                    <div class="row g-3">
                        <?php foreach ($wishlist as $item): ?>
                            <div class="col-md-4">
                                <a class="list-card h-100" href="<?= e(url('produto-detalhe.php?slug=' . urlencode($item['slug']))) ?>">
                                    <span><strong><?= e($item['name']) ?></strong><small><?= e($item['sku']) ?></small></span>
                                    <b><?= e(format_price((float) $item['final_price'])) ?></b>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section id="avaliacoes" class="account-panel">
                    <h2 class="h4 fw-bold mb-3">Avaliacoes</h2>
                    <div class="account-list mb-4">
                        <?php foreach ($reviews as $review): ?>
                            <article>
                                <strong><?= e($review['product_name'] ?? $review['title'] ?? 'Avaliacao') ?></strong>
                                <span><?= e((string) $review['rating']) ?>/5 · <?= e($review['status'] ?? 'pending') ?></span>
                            </article>
                        <?php endforeach; ?>
                        <?php if ($reviews === []): ?>
                            <p class="text-secondary mb-0">Ainda nao submeteste avaliacoes.</p>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="<?= e(url('perfil.php')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="review">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label" for="product_name">Produto</label>
                                <input class="form-control" id="product_name" name="product_name" type="text" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="rating">Nota</label>
                                <input class="form-control" id="rating" name="rating" type="number" min="1" max="5" value="5" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="review_title">Titulo</label>
                                <input class="form-control" id="review_title" name="title" type="text">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="comment">Comentario</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                            </div>
                        </div>
                        <button class="btn btn-dark mt-4" type="submit">Enviar avaliacao</button>
                    </form>
                </section>

                <section id="mensagens" class="account-panel">
                    <h2 class="h4 fw-bold mb-3">Mensagens</h2>
                    <div class="account-list mb-4">
                        <?php foreach ($messages as $message): ?>
                            <article>
                                <strong><?= e($message['subject']) ?></strong>
                                <span><?= e($message['status']) ?> · <?= e($message['last_message_at'] ?? $message['created_at'] ?? '') ?></span>
                            </article>
                        <?php endforeach; ?>
                        <?php if ($messages === []): ?>
                            <p class="text-secondary mb-0">Ainda nao existem mensagens.</p>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="<?= e(url('perfil.php')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="message">
                        <label class="form-label" for="subject">Assunto</label>
                        <input class="form-control mb-3" id="subject" name="subject" type="text" required>
                        <label class="form-label" for="body">Mensagem</label>
                        <textarea class="form-control" id="body" name="body" rows="4" required></textarea>
                        <button class="btn btn-dark mt-4" type="submit">Enviar mensagem</button>
                    </form>
                </section>

                <section id="notificacoes" class="account-panel">
                    <h2 class="h4 fw-bold mb-3">Notificacoes</h2>
                    <div class="account-list">
                        <?php foreach ($notifications as $notification): ?>
                            <article>
                                <strong><?= e($notification['title']) ?></strong>
                                <span><?= e($notification['body'] ?? '') ?></span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
