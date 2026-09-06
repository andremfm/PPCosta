<?php
$pageTitle = 'Carrinho | PPCosta';
require_once __DIR__ . '/includes/cart.php';

if (request_method() === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('carrinho.php');
    }

    $action = isset($_POST['remove_item']) ? 'remove' : ($_POST['action'] ?? 'add');

    if ($action === 'add') {
        $added = cart_add_item(
            (int) ($_POST['product_id'] ?? 0),
            max(1, (int) ($_POST['quantity'] ?? 1)),
            is_array($_POST['personalization'] ?? null) ? $_POST['personalization'] : [],
            trim($_POST['uploaded_personalization_file'] ?? '')
        );

        flash($added ? 'success' : 'danger', $added ? 'Produto adicionado ao carrinho.' : 'Nao foi possivel adicionar o produto.');
        redirect('carrinho.php');
    }

    if ($action === 'update') {
        cart_update_quantities(is_array($_POST['quantities'] ?? null) ? $_POST['quantities'] : []);
        cart_save_notes($_POST['notes'] ?? '', $_POST['shipping_postal_code'] ?? '');
        flash('success', 'Carrinho atualizado.');
        redirect('carrinho.php');
    }

    if ($action === 'remove') {
        cart_remove_item((string) ($_POST['remove_item'] ?? $_POST['item_key'] ?? ''));
        flash('success', 'Produto removido.');
        redirect('carrinho.php');
    }

    if ($action === 'coupon') {
        flash(cart_apply_coupon($_POST['coupon_code'] ?? '') ? 'success' : 'warning', 'Cupao processado.');
        redirect('carrinho.php');
    }

    if ($action === 'clear') {
        cart_clear();
        flash('success', 'Carrinho limpo.');
        redirect('carrinho.php');
    }
}

$cart = cart();
$totals = cart_totals();

require_once __DIR__ . '/includes/header.php';
?>
<section class="section-pad">
    <div class="container">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <p class="text-uppercase text-secondary fw-semibold small mb-2">Compra</p>
                <h1 class="section-title mb-0">Carrinho</h1>
            </div>
            <a class="btn btn-outline-dark align-self-lg-end" href="<?= e(url('produto.php')) ?>">Continuar a comprar</a>
        </div>

        <?php if ($cart['items'] === []): ?>
            <div class="empty-state">
                <h2 class="h4 fw-bold">O carrinho esta vazio</h2>
                <p class="text-secondary">Adiciona produtos personalizados para preparar a tua encomenda.</p>
                <a class="btn btn-dark" href="<?= e(url('produto.php')) ?>">Ver produtos</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-lg-8">
                    <form method="post" action="<?= e(url('carrinho.php')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update">
                        <div class="cart-items">
                            <?php foreach ($cart['items'] as $item): ?>
                                <article class="cart-item">
                                    <div class="cart-item-media"><?= e(substr($item['name'], 0, 1)) ?></div>
                                    <div class="cart-item-body">
                                        <div class="d-flex justify-content-between gap-3">
                                            <div>
                                                <h2 class="h5 fw-bold mb-1">
                                                    <a class="text-dark text-decoration-none" href="<?= e(url('produto-detalhe.php?slug=' . urlencode($item['slug']))) ?>"><?= e($item['name']) ?></a>
                                                </h2>
                                                <p class="text-secondary small mb-2">SKU: <?= e($item['sku']) ?></p>
                                            </div>
                                            <strong><?= e(format_price(((float) $item['unit_price'] + (float) $item['personalization_total']) * (int) $item['quantity'])) ?></strong>
                                        </div>
                                        <?php if ($item['personalization'] !== [] || $item['file_path'] !== ''): ?>
                                            <div class="cart-personalization">
                                                <?php foreach ($item['personalization'] as $label => $value): ?>
                                                    <span><?= e((string) $label) ?>: <?= e((string) $value) ?></span>
                                                <?php endforeach; ?>
                                                <?php if ($item['file_path'] !== ''): ?>
                                                    <span>Ficheiro: <?= e(basename($item['file_path'])) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                                            <label class="form-label mb-0" for="qty-<?= e($item['key']) ?>">Quantidade</label>
                                            <input class="form-control quantity-input" id="qty-<?= e($item['key']) ?>" name="quantities[<?= e($item['key']) ?>]" type="number" min="0" value="<?= e((string) $item['quantity']) ?>">
                                            <span class="text-secondary small">Personalizacao: <?= e(format_price((float) $item['personalization_total'])) ?>/un.</span>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger mt-3" name="remove_item" value="<?= e($item['key']) ?>" type="submit">Remover</button>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="cart-notes mt-4">
                            <label class="form-label" for="notes">Observacoes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Indica detalhes importantes da encomenda."><?= e($cart['notes']) ?></textarea>
                            <label class="form-label mt-3" for="shipping_postal_code">Codigo postal para estimar portes</label>
                            <input class="form-control" id="shipping_postal_code" name="shipping_postal_code" type="text" value="<?= e($cart['shipping_postal_code']) ?>" placeholder="Ex.: 1000-000">
                        </div>
                        <button class="btn btn-dark mt-4" type="submit">Atualizar carrinho</button>
                    </form>
                </div>
                <div class="col-lg-4">
                    <aside class="cart-summary">
                        <h2 class="h5 fw-bold mb-4">Resumo</h2>
                        <div class="summary-line"><span>Unidades</span><strong><?= e((string) $totals['units']) ?></strong></div>
                        <div class="summary-line"><span>Subtotal</span><strong><?= e(format_price($totals['subtotal'])) ?></strong></div>
                        <div class="summary-line"><span>Personalizacao</span><strong><?= e(format_price($totals['personalization_total'])) ?></strong></div>
                        <div class="summary-line"><span>Desconto</span><strong>-<?= e(format_price($totals['discount'])) ?></strong></div>
                        <div class="summary-line"><span>Portes</span><strong><?= e($totals['shipping'] > 0 ? format_price($totals['shipping']) : 'Gratis') ?></strong></div>
                        <div class="summary-line"><span>IVA incl.</span><strong><?= e(format_price($totals['tax'])) ?></strong></div>
                        <hr>
                        <div class="summary-total"><span>Total</span><strong><?= e(format_price($totals['total'])) ?></strong></div>

                        <form class="mt-4" method="post" action="<?= e(url('carrinho.php')) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="coupon">
                            <label class="form-label" for="coupon_code">Cupao</label>
                            <div class="input-group">
                                <input class="form-control" id="coupon_code" name="coupon_code" type="text" value="<?= e($cart['coupon']['code'] ?? '') ?>" placeholder="BEMVINDO10">
                                <button class="btn btn-outline-dark" type="submit">Aplicar</button>
                            </div>
                        </form>

                        <a class="btn btn-dark w-100 mt-4" href="<?= e(url('checkout.php')) ?>">Avancar para checkout</a>
                        <form method="post" action="<?= e(url('carrinho.php')) ?>" class="mt-2">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="clear">
                            <button class="btn btn-link text-secondary w-100" type="submit">Limpar carrinho</button>
                        </form>
                    </aside>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
