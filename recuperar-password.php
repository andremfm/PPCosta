<?php
$pageTitle = 'Recuperar password | PPCosta';
require_once __DIR__ . '/includes/auth.php';

if (request_method() === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('recuperar-password.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('danger', 'Indica um email valido.');
        redirect('recuperar-password.php');
    }

    try {
        $token = create_password_reset($email);
        flash('success', 'Se o email existir, receberas instrucoes para definir uma nova password.');

        if ($token && APP_ENV === 'development') {
            flash('info', 'Link de desenvolvimento: ' . url('reset-password.php?token=' . urlencode($token)));
        }
    } catch (Throwable) {
        flash('danger', 'Nao foi possivel processar o pedido. Confirma a base de dados.');
    }

    redirect('recuperar-password.php');
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="section-pad">
    <div class="container">
        <div class="auth-panel mx-auto p-4 p-md-5" style="max-width: 520px;">
            <h1 class="h3 fw-bold mb-4">Recuperar password</h1>
            <p class="text-secondary">Indica o email da tua conta para receberes instrucoes de recuperacao.</p>
            <form method="post" action="<?= e(url('recuperar-password.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label class="form-label" for="email">Email</label>
                <input class="form-control mb-4" id="email" name="email" type="email" autocomplete="email" required>
                <button class="btn btn-dark w-100" type="submit">Enviar instrucao</button>
            </form>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
