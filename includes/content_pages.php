<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/settings.php';

function content_page_data(string $slug): ?array
{
    $settings = store_settings_all();
    $storeName = $settings['store_name'] ?? APP_NAME;

    $pages = [
        'termos' => [
            'title' => 'Termos e condicoes',
            'description' => 'Termos e condicoes de compra da loja ' . $storeName . '.',
            'setting' => 'legal_terms',
            'lead' => 'Condicoes gerais aplicaveis a encomendas, personalizacao, producao e entrega.',
        ],
        'privacidade' => [
            'title' => 'Politica de privacidade',
            'description' => 'Politica de privacidade e tratamento de dados pessoais da loja ' . $storeName . '.',
            'setting' => 'privacy_policy',
            'lead' => 'Informacao sobre tratamento de dados, RGPD, comunicações e direitos do cliente.',
        ],
        'devolucoes' => [
            'title' => 'Trocas e devolucoes',
            'description' => 'Politica de trocas, devolucoes e produtos personalizados da loja ' . $storeName . '.',
            'setting' => 'returns_policy',
            'lead' => 'Regras de apoio ao cliente para trocas, devolucoes e artigos feitos por medida.',
        ],
    ];

    if (!isset($pages[$slug])) {
        return null;
    }

    $page = $pages[$slug];
    $page['body'] = (string) ($settings[$page['setting']] ?? '');
    $page['updated'] = date('Y-m-d');

    return $page;
}

function content_page_url(string $slug): string
{
    return url($slug);
}

function contact_validate(array $data): array
{
    $errors = [];

    if (trim((string) ($data['name'] ?? '')) === '') {
        $errors[] = 'Indica o teu nome.';
    }

    if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Indica um email valido.';
    }

    if (trim((string) ($data['subject'] ?? '')) === '') {
        $errors[] = 'Indica o assunto.';
    }

    if (trim((string) ($data['body'] ?? '')) === '') {
        $errors[] = 'Escreve a tua mensagem.';
    }

    if (empty($data['gdpr_accept'])) {
        $errors[] = 'E necessario aceitar o tratamento dos dados para responder ao pedido.';
    }

    return $errors;
}

function contact_store_message(array $data): void
{
    $user = current_user();
    $name = trim((string) ($data['name'] ?? ''));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $subject = trim((string) ($data['subject'] ?? ''));
    $body = trim((string) ($data['body'] ?? ''));
    $messageBody = "Nome: {$name}\nEmail: {$email}\n\n{$body}";

    try {
        $pdo = db();
        $pdo->beginTransaction();

        $threadStmt = $pdo->prepare('INSERT INTO message_threads (user_id, subject, status) VALUES (:user_id, :subject, :status)');
        $threadStmt->execute([
            'user_id' => $user ? (int) $user['id'] : null,
            'subject' => $subject,
            'status' => 'waiting_admin',
        ]);

        $messageStmt = $pdo->prepare('INSERT INTO messages (thread_id, sender_user_id, sender_type, body) VALUES (:thread_id, :sender_user_id, :sender_type, :body)');
        $messageStmt->execute([
            'thread_id' => (int) $pdo->lastInsertId(),
            'sender_user_id' => $user ? (int) $user['id'] : null,
            'sender_type' => 'customer',
            'body' => $messageBody,
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}
