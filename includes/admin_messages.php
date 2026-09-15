<?php
declare(strict_types=1);

require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/mailer.php';

function admin_message_statuses(): array
{
    return [
        'open' => 'Aberta',
        'waiting_admin' => 'A aguardar admin',
        'waiting_customer' => 'A aguardar cliente',
        'closed' => 'Fechada',
    ];
}

function admin_messages_all(array $filters = []): array
{
    $where = [];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = 'mt.status = :status';
        $params['status'] = $filters['status'];
    }

    if (!empty($filters['q'])) {
        $where[] = '(mt.subject LIKE :q OR u.email LIKE :q_email OR u.first_name LIKE :q_first OR u.last_name LIKE :q_last OR latest.body LIKE :q_body)';
        $params['q_email'] = $params['q_first'] = $params['q_last'] = $params['q_body'] = '%' . $filters['q'] . '%';
        $params['q'] = '%' . $filters['q'] . '%';
    }

    $sql = 'SELECT mt.*, u.first_name, u.last_name, u.email,
                COUNT(m.id) AS messages_count,
                MAX(m.created_at) AS last_message_at,
                latest.body AS latest_body
            FROM message_threads mt
            LEFT JOIN users u ON u.id = mt.user_id
            LEFT JOIN messages m ON m.thread_id = mt.id
            LEFT JOIN messages latest ON latest.id = (
                SELECT m2.id
                FROM messages m2
                WHERE m2.thread_id = mt.id
                ORDER BY m2.created_at DESC, m2.id DESC
                LIMIT 1
            )';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' GROUP BY mt.id, u.id, latest.id ORDER BY COALESCE(MAX(m.created_at), mt.created_at) DESC LIMIT 80';

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function admin_message_find(int $threadId): ?array
{
    try {
        $stmt = db()->prepare(
            'SELECT mt.*, u.first_name, u.last_name, u.email
             FROM message_threads mt
             LEFT JOIN users u ON u.id = mt.user_id
             WHERE mt.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $threadId]);
        $thread = $stmt->fetch();

        return $thread ?: null;
    } catch (Throwable) {
        return null;
    }
}

function admin_message_entries(int $threadId): array
{
    try {
        $stmt = db()->prepare(
            'SELECT m.*, u.first_name, u.last_name, u.email
             FROM messages m
             LEFT JOIN users u ON u.id = m.sender_user_id
             WHERE m.thread_id = :thread_id
             ORDER BY m.created_at ASC, m.id ASC'
        );
        $stmt->execute(['thread_id' => $threadId]);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function admin_message_update_status(int $threadId, string $status): void
{
    if (!isset(admin_message_statuses()[$status])) {
        return;
    }

    $stmt = db()->prepare('UPDATE message_threads SET status = :status WHERE id = :id');
    $stmt->execute(['status' => $status, 'id' => $threadId]);
}

function admin_message_reply(int $threadId, int $adminUserId, string $body): void
{
    $body = trim($body);

    if ($body === '') {
        return;
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('INSERT INTO messages (thread_id, sender_user_id, sender_type, body) VALUES (:thread_id, :sender_user_id, :sender_type, :body)');
        $stmt->execute([
            'thread_id' => $threadId,
            'sender_user_id' => $adminUserId > 0 ? $adminUserId : null,
            'sender_type' => 'admin',
            'body' => $body,
        ]);

        $statusStmt = $pdo->prepare('UPDATE message_threads SET status = :status WHERE id = :id');
        $statusStmt->execute(['status' => 'waiting_customer', 'id' => $threadId]);

        $thread = admin_message_find($threadId);

        if ($thread && !empty($thread['user_id'])) {
            $notificationStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, body, type) VALUES (:user_id, :title, :body, :type)');
            $notificationStmt->execute([
                'user_id' => (int) $thread['user_id'],
                'title' => 'Nova resposta da equipa',
                'body' => (string) $thread['subject'],
                'type' => 'message',
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }

    admin_message_notify_customer($threadId, $body);
}

function admin_message_customer_label(array $thread): string
{
    $name = trim((string) ($thread['first_name'] ?? '') . ' ' . (string) ($thread['last_name'] ?? ''));
    $email = trim((string) ($thread['email'] ?? ''));

    if ($name !== '' && $email !== '') {
        return $name . ' · ' . $email;
    }

    return $email !== '' ? $email : admin_message_guest_email_from_thread((int) $thread['id'], 'Contacto sem conta');
}

function admin_message_guest_email_from_thread(int $threadId, string $fallback = ''): string
{
    foreach (admin_message_entries($threadId) as $message) {
        if (preg_match('/Email:\s*([^\s]+)/i', (string) ($message['body'] ?? ''), $matches)) {
            return trim($matches[1]);
        }
    }

    return $fallback;
}

function admin_message_notify_customer(int $threadId, string $body): void
{
    $thread = admin_message_find($threadId);

    if (!$thread) {
        return;
    }

    $email = trim((string) ($thread['email'] ?? ''));

    if ($email === '') {
        $email = admin_message_guest_email_from_thread($threadId);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $html = mailer_layout(
        'Resposta da equipa',
        'Respondemos ao teu pedido de contacto.',
        [
            'Assunto: ' . (string) $thread['subject'],
            'Resposta: ' . $body,
        ],
        'Ver area cliente',
        url('perfil.php#mensagens')
    );

    mailer_send($email, 'Resposta ao pedido - ' . APP_NAME, $html);
}
