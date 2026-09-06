<?php
$pageTitle = 'Checkout | PPCosta';
require_once __DIR__ . '/includes/checkout.php';

$cart = cart();

if ($cart['items'] === []) {
    flash('warning', 'O carrinho esta vazio.');
    redirect('carrinho.php');
}

if (request_method() === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('checkout.php');
    }

    $errors = checkout_validate($_POST);

    if ($errors !== []) {
        set_old($_POST);
        foreach ($errors as $error) {
            flash('danger', $error);
        }
        redirect('checkout.php');
    }

    $order = checkout_store_order(checkout_prepare_order($_POST));
    $_SESSION['last_order'] = $order;
    if (!empty($_SESSION['user_id'])) {
        $_SESSION[customer_session_key('orders')][] = $order;
    }
    clear_old();
    cart_clear();
    redirect('encomenda-concluida.php');
}

$user = current_user();
$totals = cart_totals();
$shippingMethods = checkout_shipping_methods();
$paymentMethods = checkout_payment_methods();

require_once __DIR__ . '/includes/header.php';
?>
<section class="section-pad checkout-page">
    <div class="container">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <p class="text-uppercase text-secondary fw-semibold small mb-2">Checkout</p>
                <h1 class="section-title mb-0">Finalizar encomenda</h1>
            </div>
            <a class="btn btn-outline-dark align-self-lg-end" href="<?= e(url('carrinho.php')) ?>">Voltar ao carrinho</a>
        </div>

        <div class="checkout-steps mb-4">
            <?php foreach (['Dados', 'Morada', 'Envio', 'Pagamento', 'Resumo', 'RGPD'] as $index => $step): ?>
                <span><b><?= e((string) ($index + 1)) ?></b><?= e($step) ?></span>
            <?php endforeach; ?>
        </div>

        <form method="post" action="<?= e(url('checkout.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="row g-4">
                <div class="col-lg-8">
                    <section class="checkout-panel">
                        <h2 class="h5 fw-bold mb-3">Dados pessoais</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">Nome</label>
                                <input class="form-control" id="first_name" name="first_name" type="text" value="<?= e(old('first_name', $user['first_name'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">Apelido</label>
                                <input class="form-control" id="last_name" name="last_name" type="text" value="<?= e(old('last_name', $user['last_name'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input class="form-control" id="email" name="email" type="email" value="<?= e(old('email', $user['email'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Telefone</label>
                                <input class="form-control" id="phone" name="phone" type="tel" value="<?= e(old('phone', $user['phone'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="company">Empresa</label>
                                <input class="form-control" id="company" name="company" type="text" value="<?= e(old('company')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tax_number">NIF</label>
                                <input class="form-control" id="tax_number" name="tax_number" type="text" value="<?= e(old('tax_number')) ?>">
                            </div>
                        </div>
                    </section>

                    <section class="checkout-panel">
                        <h2 class="h5 fw-bold mb-3">Morada de faturacao</h2>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="address_line_1">Morada</label>
                                <input class="form-control" id="address_line_1" name="address_line_1" type="text" value="<?= e(old('address_line_1')) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="address_line_2">Complemento</label>
                                <input class="form-control" id="address_line_2" name="address_line_2" type="text" value="<?= e(old('address_line_2')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="postal_code">Codigo postal</label>
                                <input class="form-control" id="postal_code" name="postal_code" type="text" value="<?= e(old('postal_code', $cart['shipping_postal_code'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="city">Localidade</label>
                                <input class="form-control" id="city" name="city" type="text" value="<?= e(old('city')) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="district">Distrito</label>
                                <input class="form-control" id="district" name="district" type="text" value="<?= e(old('district')) ?>">
                            </div>
                        </div>
                    </section>

                    <section class="checkout-panel">
                        <div class="form-check mb-3">
                            <input class="form-check-input" id="same_billing" name="same_billing" type="checkbox" value="1" checked>
                            <label class="form-check-label fw-semibold" for="same_billing">Morada de entrega igual a faturacao</label>
                        </div>
                        <div class="checkout-delivery-grid">
                            <div>
                                <label class="form-label" for="shipping_first_name">Nome entrega</label>
                                <input class="form-control" id="shipping_first_name" name="shipping_first_name" type="text" value="<?= e(old('shipping_first_name')) ?>">
                            </div>
                            <div>
                                <label class="form-label" for="shipping_last_name">Apelido entrega</label>
                                <input class="form-control" id="shipping_last_name" name="shipping_last_name" type="text" value="<?= e(old('shipping_last_name')) ?>">
                            </div>
                            <div class="grid-wide">
                                <label class="form-label" for="shipping_address_line_1">Morada entrega</label>
                                <input class="form-control" id="shipping_address_line_1" name="shipping_address_line_1" type="text" value="<?= e(old('shipping_address_line_1')) ?>">
                            </div>
                            <div>
                                <label class="form-label" for="shipping_postal_code">Codigo postal entrega</label>
                                <input class="form-control" id="shipping_postal_code" name="shipping_postal_code" type="text" value="<?= e(old('shipping_postal_code')) ?>">
                            </div>
                            <div>
                                <label class="form-label" for="shipping_city">Localidade entrega</label>
                                <input class="form-control" id="shipping_city" name="shipping_city" type="text" value="<?= e(old('shipping_city')) ?>">
                            </div>
                        </div>
                    </section>

                    <section class="checkout-panel">
                        <h2 class="h5 fw-bold mb-3">Envio</h2>
                        <div class="checkout-options">
                            <?php foreach ($shippingMethods as $code => $method): ?>
                                <label class="checkout-option">
                                    <input type="radio" name="shipping_method" value="<?= e($code) ?>" <?= $code === 'ctt' ? 'checked' : '' ?>>
                                    <span>
                                        <strong><?= e($method['name']) ?></strong>
                                        <small><?= e($method['price'] > 0 ? format_price($method['price']) : 'Gratis') ?></small>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="checkout-panel">
                        <h2 class="h5 fw-bold mb-3">Pagamento</h2>
                        <div class="checkout-options">
                            <?php foreach ($paymentMethods as $code => $name): ?>
                                <label class="checkout-option">
                                    <input type="radio" name="payment_method" value="<?= e($code) ?>" <?= $code === 'mbway' ? 'checked' : '' ?>>
                                    <span><strong><?= e($name) ?></strong><small>Integracao API preparada</small></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="checkout-panel">
                        <h2 class="h5 fw-bold mb-3">Observacoes e RGPD</h2>
                        <label class="form-label" for="notes">Observacoes</label>
                        <textarea class="form-control mb-3" id="notes" name="notes" rows="3"><?= e(old('notes', $cart['notes'] ?? '')) ?></textarea>
                        <div class="form-check">
                            <input class="form-check-input" id="gdpr_accept" name="gdpr_accept" type="checkbox" value="1" required>
                            <label class="form-check-label" for="gdpr_accept">Aceito o tratamento dos dados necessario para processar a encomenda.</label>
                        </div>
                    </section>
                </div>

                <div class="col-lg-4">
                    <aside class="cart-summary checkout-summary">
                        <h2 class="h5 fw-bold mb-4">Resumo da encomenda</h2>
                        <?php foreach ($cart['items'] as $item): ?>
                            <div class="checkout-summary-item">
                                <span><?= e($item['name']) ?> x<?= e((string) $item['quantity']) ?></span>
                                <strong><?= e(format_price(((float) $item['unit_price'] + (float) $item['personalization_total']) * (int) $item['quantity'])) ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="summary-line"><span>Subtotal</span><strong><?= e(format_price($totals['subtotal'])) ?></strong></div>
                        <div class="summary-line"><span>Desconto</span><strong>-<?= e(format_price($totals['discount'])) ?></strong></div>
                        <div class="summary-line"><span>Portes estimados</span><strong><?= e($totals['shipping'] > 0 ? format_price($totals['shipping']) : 'Gratis') ?></strong></div>
                        <div class="summary-line"><span>IVA incl.</span><strong><?= e(format_price($totals['tax'])) ?></strong></div>
                        <hr>
                        <div class="summary-total"><span>Total</span><strong><?= e(format_price($totals['total'])) ?></strong></div>
                        <button class="btn btn-dark w-100 mt-4" type="submit">Concluir compra</button>
                    </aside>
                </div>
            </div>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
