<?php
require_once __DIR__ . '/includes/content_pages.php';

$settings = store_settings_all();
$pageTitle = 'Contactos | PPCosta';
$pageDescription = 'Contacta a PPCosta para artigos personalizados, bordados, estampagens, brindes e encomendas empresariais.';
$pageCanonical = url('contactos');
$pageSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'ContactPage',
    'name' => 'Contactos',
    'url' => $pageCanonical,
];

if (request_method() === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('contactos');
    }

    $errors = contact_validate($_POST);

    if ($errors !== []) {
        foreach ($errors as $error) {
            flash('danger', $error);
        }
        set_old($_POST);
        redirect('contactos');
    }

    try {
        contact_store_message($_POST);
    } catch (Throwable $exception) {
        error_log('Contact failed: ' . $exception->getMessage());
        set_old($_POST);
        flash('danger', 'Nao foi possivel enviar a mensagem. Tenta novamente.');
        redirect('contactos');
    }
    clear_old();
    flash('success', 'Mensagem enviada. Vamos responder assim que possivel.');
    redirect('contactos');
}

$old = $_SESSION['_old'] ?? [];

require_once __DIR__ . '/includes/header.php';
?>
<section class="catalog-hero border-bottom">
    <div class="container">
        <p class="text-uppercase text-secondary fw-semibold small mb-2">Apoio ao cliente</p>
        <h1 class="section-title mb-3">Contactos</h1>
        <p class="text-secondary mb-0">Fala connosco sobre encomendas, personalizacao, orcamentos empresariais ou pos-venda.</p>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="content-card h-100">
                    <h2 class="h5 fw-bold mb-3"><?= e($settings['store_name'] ?? APP_NAME) ?></h2>
                    <div class="contact-list">
                        <article>
                            <span>Email</span>
                            <a href="mailto:<?= e((string) ($settings['store_email'] ?? '')) ?>"><?= e((string) ($settings['store_email'] ?? '')) ?></a>
                        </article>
                        <article>
                            <span>Telefone</span>
                            <a href="tel:<?= e(preg_replace('/\s+/', '', (string) ($settings['store_phone'] ?? ''))) ?>"><?= e((string) ($settings['store_phone'] ?? '')) ?></a>
                        </article>
                        <article>
                            <span>Morada</span>
                            <strong><?= e((string) ($settings['store_address'] ?? '')) ?></strong>
                        </article>
                        <article>
                            <span>NIF/NIPC</span>
                            <strong><?= e((string) ($settings['store_vat_number'] ?? '')) ?></strong>
                        </article>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="content-card">
                    <h2 class="h5 fw-bold mb-3">Enviar mensagem</h2>
                    <form method="post" action="<?= e(url('contactos')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Nome</label>
                                <input class="form-control" id="name" name="name" type="text" value="<?= e((string) ($old['name'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input class="form-control" id="email" name="email" type="email" value="<?= e((string) ($old['email'] ?? '')) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="subject">Assunto</label>
                                <input class="form-control" id="subject" name="subject" type="text" value="<?= e((string) ($old['subject'] ?? '')) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="body">Mensagem</label>
                                <textarea class="form-control" id="body" name="body" rows="6" required><?= e((string) ($old['body'] ?? '')) ?></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" id="gdpr_accept" name="gdpr_accept" type="checkbox" value="1" required>
                                    <label class="form-check-label" for="gdpr_accept">Aceito que os dados sejam usados para responder a este pedido.</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-dark" type="submit">Enviar mensagem</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
