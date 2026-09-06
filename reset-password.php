<?php
$pageTitle = 'Definir nova password | PPCosta';
require_once __DIR__ . '/includes/auth.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');

if ($token === '') {
    flash('danger', 'Token de recuperacao invalido.');
    redirect('login.php');
}

if (request_method() === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('reset-password.php?token=' . urlencode($token));
    }

    if (strlen($password) < 8 || $password !== $passwordConfirmation) {
        flash('danger', 'A password deve ter pelo menos 8 caracteres e coincidir com a confirmacao.');
        redirect('reset-password.php?token=' . urlencode($token));
    }

    try {
        if (reset_user_password($token, $password)) {
            flash('success', 'Password atualizada. Ja podes iniciar sessao.');
            redirect('login.php');
        }

        flash('danger', 'Token invalido ou expirado.');
        redirect('recuperar-password.php');
    } catch (Throwable) {
        flash('danger', 'Nao foi possivel atualizar a password. Confirma a base de dados.');
        redirect('reset-password.php?token=' . urlencode($token));
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="section-pad">
    <div class="container">
        <div class="auth-panel mx-auto p-4 p-md-5" style="max-width: 520px;">
            <h1 class="h3 fw-bold mb-4">Definir nova password</h1>
            <form method="post" action="<?= e(url('reset-password.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="mb-3">
                    <label class="form-label" for="password">Nova password</label>
                    <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Confirmar password</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </div>
                <button class="btn btn-dark w-100" type="submit">Atualizar password</button>
            </form>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
