<?php
$pageTitle = 'Entrar | PPCosta';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('perfil.php');
}

if (request_method() === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    set_old(['email' => $email]);

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('login.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        flash('danger', 'Confirma o email e a password.');
        redirect('login.php');
    }

    try {
        if (login_user($email, $password)) {
            clear_old();
            flash('success', 'Sessao iniciada com sucesso.');
            redirect(has_role('admin') ? 'admin/' : 'perfil.php');
        }

        flash('danger', 'Credenciais invalidas ou conta bloqueada.');
        redirect('login.php');
    } catch (Throwable) {
        flash('danger', 'Nao foi possivel ligar a base de dados. Confirma a instalacao SQL.');
        redirect('login.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="section-pad">
    <div class="container">
        <div class="auth-panel mx-auto p-4 p-md-5" style="max-width: 520px;">
            <h1 class="h3 fw-bold mb-4">Entrar na conta</h1>
            <form method="post" action="<?= e(url('login.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?= e(old('email')) ?>" autocomplete="email" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="btn btn-dark w-100" type="submit">Entrar</button>
            </form>
            <p class="text-secondary mt-3 mb-0">Ainda nao tens conta? <a href="<?= e(url('register.php')) ?>">Criar conta</a></p>
            <p class="text-secondary mt-2 mb-0"><a href="<?= e(url('recuperar-password.php')) ?>">Recuperar password</a></p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
