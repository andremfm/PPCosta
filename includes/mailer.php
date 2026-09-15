<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function mailer_send(string $to, string $subject, string $html, string $text = ''): bool
{
    $to = trim($to);

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $text = $text !== '' ? $text : strip_tags(str_replace(['<br>', '<br />', '</p>'], "\n", $html));

    if (MAIL_TRANSPORT === 'mail' && APP_ENV === 'production') {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_EMAIL . '>',
            'Reply-To: ' . MAIL_FROM_EMAIL,
        ];

        return mail($to, $subject, $html, implode("\r\n", $headers));
    }

    return mailer_log($to, $subject, $html, $text);
}

function mailer_log(string $to, string $subject, string $html, string $text): bool
{
    $dir = MAIL_LOG_DIR;

    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $filename = $dir . '/' . date('Ymd_His') . '_' . substr(hash('sha256', $to . $subject . microtime(true)), 0, 12) . '.eml';
    $payload = [
        'to' => $to,
        'subject' => $subject,
        'created_at' => date(DATE_ATOM),
        'text' => $text,
        'html' => $html,
    ];

    return file_put_contents($filename, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;
}

function mailer_layout(string $title, string $intro, array $lines = [], ?string $actionLabel = null, ?string $actionUrl = null): string
{
    $lineHtml = '';

    foreach ($lines as $line) {
        $lineHtml .= '<p style="margin:0 0 10px;color:#475569;line-height:1.6;">' . e((string) $line) . '</p>';
    }

    $button = '';

    if ($actionLabel && $actionUrl) {
        $button = '<p style="margin:24px 0;"><a href="' . e($actionUrl) . '" style="display:inline-block;background:#0f172a;color:#fff;text-decoration:none;padding:12px 18px;border-radius:6px;font-weight:700;">' . e($actionLabel) . '</a></p>';
    }

    return '<!doctype html><html lang="pt-PT"><body style="margin:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">'
        . '<div style="max-width:640px;margin:0 auto;padding:32px 16px;">'
        . '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:28px;">'
        . '<p style="margin:0 0 12px;color:#64748b;font-size:13px;text-transform:uppercase;font-weight:700;">' . e(APP_NAME) . '</p>'
        . '<h1 style="margin:0 0 16px;font-size:24px;">' . e($title) . '</h1>'
        . '<p style="margin:0 0 16px;color:#334155;line-height:1.6;">' . e($intro) . '</p>'
        . $lineHtml
        . $button
        . '<p style="margin:24px 0 0;color:#94a3b8;font-size:12px;">Email automatico. Guarda esta mensagem para referencia futura.</p>'
        . '</div></div></body></html>';
}

function mailer_send_password_reset(string $email, string $token): bool
{
    $url = url('reset-password.php?token=' . urlencode($token));
    $html = mailer_layout(
        'Recuperar password',
        'Recebemos um pedido para definir uma nova password na tua conta.',
        ['Se nao foste tu, podes ignorar este email.', 'O link e temporario por motivos de seguranca.'],
        'Definir nova password',
        $url
    );

    return mailer_send($email, 'Recuperar password - ' . APP_NAME, $html);
}

function mailer_send_order_confirmation(array $order): bool
{
    $email = (string) ($order['customer']['email'] ?? $order['customer_email'] ?? '');
    $total = (float) ($order['totals']['total'] ?? $order['grand_total'] ?? 0);
    $html = mailer_layout(
        'Encomenda recebida',
        'A tua encomenda foi registada com sucesso.',
        [
            'Numero: ' . ($order['order_number'] ?? ''),
            'Total: ' . format_price($total),
            'Pagamento: ' . ($order['payment_method']['name'] ?? 'A confirmar'),
            'Referencia: ' . ($order['payment']['reference'] ?? 'A confirmar'),
            'Instrucoes: ' . ($order['payment']['instructions'] ?? 'A equipa enviara instrucoes se necessario.'),
            'Estado inicial: Pagamento pendente',
        ],
        'Ver area cliente',
        url('perfil.php#encomendas')
    );

    return mailer_send($email, 'Encomenda ' . ($order['order_number'] ?? '') . ' recebida - ' . APP_NAME, $html);
}

function mailer_send_order_status_update(array $order, string $statusLabel, string $note = ''): bool
{
    $lines = [
        'Numero: ' . ($order['order_number'] ?? ''),
        'Novo estado: ' . $statusLabel,
    ];

    if (trim($note) !== '') {
        $lines[] = 'Nota: ' . trim($note);
    }

    $html = mailer_layout(
        'Atualizacao da encomenda',
        'O estado da tua encomenda foi atualizado.',
        $lines,
        'Ver area cliente',
        url('perfil.php#encomendas')
    );

    return mailer_send((string) ($order['customer_email'] ?? ''), 'Atualizacao da encomenda ' . ($order['order_number'] ?? '') . ' - ' . APP_NAME, $html);
}
