<?php
$pageTitle = 'Encomenda concluida | PPCosta';
require_once __DIR__ . '/includes/checkout.php';
require_once __DIR__ . '/includes/payments.php';

$order = $_SESSION['last_order'] ?? null;

if (!$order || empty($order['persisted'])) {
    redirect('produto.php');
}

require_once __DIR__ . '/includes/header.php';
$payment = $order['payment'] ?? (!empty($order['id']) ? payment_for_order((int) $order['id']) : null);
?>
<section class="section-pad">
    <div class="container">
        <div class="order-success">
            <p class="text-uppercase text-secondary fw-semibold small mb-2">Confirmacao</p>
            <h1 class="section-title mb-3">Encomenda recebida</h1>
            <p class="text-secondary mb-4">A tua encomenda foi registada com o numero <strong><?= e($order['order_number']) ?></strong>.</p>
            <?php if (empty($order['persisted'])): ?>
                <div class="alert alert-warning">A encomenda ficou guardada nesta sessao. Quando a base de dados estiver ativa, sera persistida automaticamente.</div>
            <?php endif; ?>
            <div class="row g-4 text-start">
                <div class="col-md-4">
                    <div class="stat-card p-4 h-100">
                        <h2 class="h6 fw-bold">Pagamento</h2>
                        <p class="text-secondary mb-0"><?= e($order['payment_method']['name']) ?></p>
                        <?php if ($payment): ?>
                            <small class="text-secondary d-block mt-2"><?= e(payment_status_label((string) $payment['status'])) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card p-4 h-100">
                        <h2 class="h6 fw-bold">Envio</h2>
                        <p class="text-secondary mb-0"><?= e($order['shipping_method']['name']) ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card p-4 h-100">
                        <h2 class="h6 fw-bold">Total</h2>
                        <p class="text-secondary mb-0"><?= e(format_price((float) $order['totals']['total'])) ?></p>
                    </div>
                </div>
            </div>
            <?php if ($payment): ?>
                <div class="alert alert-light border text-start mt-4">
                    <strong>Instrucao de pagamento</strong>
                    <p class="mb-1 mt-2"><?= e((string) $payment['instructions']) ?></p>
                    <?php if (!empty($payment['reference'])): ?>
                        <p class="mb-0"><strong>Referencia:</strong> <?= e((string) $payment['reference']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                <a class="btn btn-dark" href="<?= e(url('produto.php')) ?>">Continuar a comprar</a>
                <a class="btn btn-outline-dark" href="<?= e(url('perfil.php')) ?>">Area cliente</a>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
