<?php
$pageTitle = 'Criar conta | PPCosta';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('perfil.php');
}

if (request_method() === 'POST') {
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => (string) ($_POST['password'] ?? ''),
        'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
        'newsletter_opt_in' => isset($_POST['newsletter_opt_in']),
    ];

    set_old([
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
    ]);

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('register.php');
    }

    if ($data['first_name'] === '' || $data['last_name'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        flash('danger', 'Preenche nome, apelido e email valido.');
        redirect('register.php');
    }

    if (strlen($data['password']) < 8 || $data['password'] !== $data['password_confirmation']) {
        flash('danger', 'A password deve ter pelo menos 8 caracteres e coincidir com a confirmacao.');
        redirect('register.php');
    }

    try {
        if (find_user_by_email($data['email'])) {
            flash('danger', 'Ja existe uma conta com este email.');
            redirect('register.php');
        }

        register_user($data);
        clear_old();
        flash('success', 'Conta criada com sucesso. Ja podes iniciar sessao.');
        redirect('login.php');
    } catch (Throwable) {
        flash('danger', 'Nao foi possivel criar a conta. Confirma a instalacao da base de dados.');
        redirect('register.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="section-pad">
    <div class="container">
        <div class="auth-panel mx-auto p-4 p-md-5" style="max-width: 640px;">
            <h1 class="h3 fw-bold mb-4">Criar conta</h1>
            <form method="post" action="<?= e(url('register.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="first_name">Nome</label>
                        <input class="form-control" id="first_name" name="first_name" type="text" value="<?= e(old('first_name')) ?>" autocomplete="given-name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="last_name">Apelido</label>
                        <input class="form-control" id="last_name" name="last_name" type="text" value="<?= e(old('last_name')) ?>" autocomplete="family-name" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" id="email" name="email" type="email" value="<?= e(old('email')) ?>" autocomplete="email" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="phone">Telefone</label>
                        <input class="form-control" id="phone" name="phone" type="tel" value="<?= e(old('phone')) ?>" autocomplete="tel">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="password_confirmation">Confirmar password</label>
                        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" id="newsletter_opt_in" name="newsletter_opt_in" type="checkbox" value="1">
                            <label class="form-check-label" for="newsletter_opt_in">Quero receber novidades e promocoes.</label>
                        </div>
                    </div>
                </div>
                <button class="btn btn-dark w-100 mt-4" type="submit">Criar conta</button>
            </form>
            <p class="text-secondary mt-3 mb-0">Ja tens conta? <a href="<?= e(url('login.php')) ?>">Entrar</a></p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
