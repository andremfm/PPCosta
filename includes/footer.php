<?php
declare(strict_types=1);

require_once __DIR__ . '/settings.php';

$footerSettings = store_settings_all();
$footerStoreName = $footerSettings['store_name'] ?? APP_NAME;
?>
</main>
<footer class="footer-section border-top">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-md-3">
                <h2 class="h5 fw-bold"><?= e($footerStoreName) ?></h2>
                <p class="text-secondary mb-2">Artigos personalizados para bebe, casa, empresas e presentes especiais.</p>
                <p class="text-secondary small mb-0"><?= e((string) ($footerSettings['store_address'] ?? '')) ?></p>
            </div>
            <div class="col-md-2">
                <h3 class="h6 fw-semibold">Loja</h3>
                <a href="<?= e(url('produto.php')) ?>" class="footer-link">Produtos</a>
                <a href="<?= e(url('carrinho.php')) ?>" class="footer-link">Carrinho</a>
                <a href="<?= e(url('checkout.php')) ?>" class="footer-link">Checkout</a>
            </div>
            <div class="col-md-2">
                <h3 class="h6 fw-semibold">Cliente</h3>
                <a href="<?= e(url('login.php')) ?>" class="footer-link">Login</a>
                <a href="<?= e(url('register.php')) ?>" class="footer-link">Criar conta</a>
                <a href="<?= e(url('perfil.php')) ?>" class="footer-link">Area cliente</a>
            </div>
            <div class="col-md-2">
                <h3 class="h6 fw-semibold">Ajuda</h3>
                <a href="<?= e(url('contactos')) ?>" class="footer-link">Contactos</a>
                <a href="<?= e(url('termos')) ?>" class="footer-link">Termos</a>
                <a href="<?= e(url('privacidade')) ?>" class="footer-link">Privacidade</a>
                <a href="<?= e(url('devolucoes')) ?>" class="footer-link">Trocas e devolucoes</a>
            </div>
            <div class="col-md-3">
                <h3 class="h6 fw-semibold">Newsletter</h3>
                <p class="text-secondary small mb-2"><?= e((string) ($footerSettings['store_email'] ?? '')) ?> · <?= e((string) ($footerSettings['store_phone'] ?? '')) ?></p>
                <form class="newsletter-form" data-newsletter-form>
                    <label class="visually-hidden" for="newsletter-email">Email</label>
                    <div class="input-group">
                        <input id="newsletter-email" class="form-control" type="email" name="email" placeholder="O teu email" required>
                        <button class="btn btn-dark" type="submit">Subscrever</button>
                    </div>
                    <div class="form-text" data-newsletter-message></div>
                </form>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2 pt-4 mt-4 border-top">
            <span>&copy; <?= date('Y') ?> <?= e($footerStoreName) ?>. Todos os direitos reservados.</span>
            <span><a href="<?= e(url('privacidade')) ?>">RGPD</a> | <a href="<?= e(url('termos')) ?>">Termos</a> | <a href="<?= e(url('privacidade')) ?>">Privacidade</a></span>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="<?= e(versioned_asset('js/app.js')) ?>" defer></script>
</body>
</html>
