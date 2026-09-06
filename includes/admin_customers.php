<?php
declare(strict_types=1);

require_once __DIR__ . '/admin.php';

function admin_customer_defaults(): array
{
    return [
        'id' => '',
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'status' => 'active',
        'newsletter_opt_in' => 0,
        'created_at' => '',
        'last_login_at' => '',
    ];
}

function admin_customer_statuses(): array
{
    return [
        'active' => 'Ativo',
        'inactive' => 'Inativo',
        'blocked' => 'Bloqueado',
        'pending' => 'Pendente',
    ];
}

function admin_customer_session_items(): array
{
    if (!isset($_SESSION['admin_customers'])) {
        $user = current_user();
        $_SESSION['admin_customers'] = $user ? [$user] : [];
    }

    return $_SESSION['admin_customers'];
}

function admin_customer_save_session_items(array $customers): void
{
    $_SESSION['admin_customers'] = array_values($customers);
}

function admin_customers_all(array $filters = []): array
{
    try {
        if (db_available()) {
            $where = [];
            $params = [];

            if (!empty($filters['q'])) {
                $where[] = '(first_name LIKE :q OR last_name LIKE :q OR email LIKE :q OR phone LIKE :q)';
                $params['q'] = '%' . trim((string) $filters['q']) . '%';
            }

            if (!empty($filters['status'])) {
                $where[] = 'status = :status';
                $params['status'] = (string) $filters['status'];
            }

            $sql = 'SELECT u.*,
                    COALESCE(stats.orders_count, 0) AS orders_count,
                    COALESCE(stats.total_spent, 0) AS total_spent
                    FROM users u
                    LEFT JOIN (
                        SELECT user_id, COUNT(*) AS orders_count, SUM(grand_total) AS total_spent
                        FROM orders
                        GROUP BY user_id
                    ) stats ON stats.user_id = u.id';

            if ($where !== []) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $sql .= ' ORDER BY u.created_at DESC LIMIT 200';
            $stmt = db()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        }
    } catch (Throwable) {
    }

    $customers = admin_customer_session_items();
    $query = strtolower(trim((string) ($filters['q'] ?? '')));
    $status = trim((string) ($filters['status'] ?? ''));

    return array_values(array_filter($customers, static function (array $customer) use ($query, $status): bool {
        $haystack = strtolower(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '') . ' ' . ($customer['email'] ?? '') . ' ' . ($customer['phone'] ?? ''));
        $matchesQuery = $query === '' || str_contains($haystack, $query);
        $matchesStatus = $status === '' || ($customer['status'] ?? '') === $status;

        return $matchesQuery && $matchesStatus;
    }));
}

function admin_customer_find(int $id): ?array
{
    try {
        if (db_available()) {
            $stmt = db()->prepare(
                'SELECT u.*,
                    COALESCE(stats.orders_count, 0) AS orders_count,
                    COALESCE(stats.total_spent, 0) AS total_spent
                 FROM users u
                 LEFT JOIN (
                    SELECT user_id, COUNT(*) AS orders_count, SUM(grand_total) AS total_spent
                    FROM orders
                    GROUP BY user_id
                 ) stats ON stats.user_id = u.id
                 WHERE u.id = :id
                 LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $customer = $stmt->fetch();

            if ($customer) {
                return array_merge(admin_customer_defaults(), $customer);
            }
        }
    } catch (Throwable) {
    }

    foreach (admin_customer_session_items() as $customer) {
        if ((int) ($customer['id'] ?? 0) === $id) {
            return array_merge(admin_customer_defaults(), $customer);
        }
    }

    return null;
}

function admin_customer_validate(array $data): array
{
    $errors = [];

    if (trim((string) ($data['first_name'] ?? '')) === '') {
        $errors[] = 'O primeiro nome e obrigatorio.';
    }

    if (trim((string) ($data['last_name'] ?? '')) === '') {
        $errors[] = 'O apelido e obrigatorio.';
    }

    if (!filter_var((string) ($data['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Indica um email valido.';
    }

    if (($data['password'] ?? '') !== '' && strlen((string) $data['password']) < 8) {
        $errors[] = 'A password deve ter pelo menos 8 caracteres.';
    }

    if (!array_key_exists((string) ($data['status'] ?? ''), admin_customer_statuses())) {
        $errors[] = 'Estado de cliente invalido.';
    }

    return $errors;
}

function admin_customer_payload(array $data): array
{
    return array_merge(admin_customer_defaults(), [
        'id' => trim((string) ($data['id'] ?? '')),
        'first_name' => trim((string) ($data['first_name'] ?? '')),
        'last_name' => trim((string) ($data['last_name'] ?? '')),
        'email' => strtolower(trim((string) ($data['email'] ?? ''))),
        'phone' => trim((string) ($data['phone'] ?? '')),
        'status' => (string) ($data['status'] ?? 'active'),
        'newsletter_opt_in' => !empty($data['newsletter_opt_in']) ? 1 : 0,
        'password' => (string) ($data['password'] ?? ''),
    ]);
}

function admin_customer_save(array $data): bool
{
    $payload = admin_customer_payload($data);

    try {
        $pdo = db();
        $pdo->beginTransaction();

        if ($payload['id'] !== '') {
            $params = [
                'first_name' => $payload['first_name'],
                'last_name' => $payload['last_name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'] !== '' ? $payload['phone'] : null,
                'status' => $payload['status'],
                'newsletter_opt_in' => $payload['newsletter_opt_in'],
                'id' => (int) $payload['id'],
            ];
            $passwordSql = '';

            if ($payload['password'] !== '') {
                $passwordSql = ', password_hash = :password_hash';
                $params['password_hash'] = password_hash($payload['password'], PASSWORD_DEFAULT);
            }

            $stmt = $pdo->prepare(
                'UPDATE users
                 SET first_name = :first_name, last_name = :last_name, email = :email,
                     phone = :phone, status = :status, newsletter_opt_in = :newsletter_opt_in' . $passwordSql . '
                 WHERE id = :id'
            );
            $stmt->execute($params);
            $customerId = (int) $payload['id'];
        } else {
            $password = $payload['password'] !== '' ? $payload['password'] : bin2hex(random_bytes(6));
            $stmt = $pdo->prepare(
                'INSERT INTO users (first_name, last_name, email, phone, password_hash, status, newsletter_opt_in)
                 VALUES (:first_name, :last_name, :email, :phone, :password_hash, :status, :newsletter_opt_in)'
            );
            $stmt->execute([
                'first_name' => $payload['first_name'],
                'last_name' => $payload['last_name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'] !== '' ? $payload['phone'] : null,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'status' => $payload['status'],
                'newsletter_opt_in' => $payload['newsletter_opt_in'],
            ]);
            $customerId = (int) $pdo->lastInsertId();
            admin_customer_assign_role($customerId, 'customer');
        }

        admin_customer_sync_newsletter($payload);
        admin_customer_log('customer_saved', $customerId, ['email' => $payload['email'], 'status' => $payload['status']]);
        $pdo->commit();

        return true;
    } catch (Throwable) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return admin_customer_save_session($payload);
    }
}

function admin_customer_assign_role(int $customerId, string $roleSlug): void
{
    $stmt = db()->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $roleSlug]);
    $roleId = $stmt->fetchColumn();

    if ($roleId) {
        db()->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)')
            ->execute(['user_id' => $customerId, 'role_id' => (int) $roleId]);
    }
}

function admin_customer_sync_newsletter(array $payload): void
{
    if ((int) $payload['newsletter_opt_in'] === 1) {
        db()->prepare(
            'INSERT INTO newsletter_subscribers (email, name, status)
             VALUES (:email, :name, "subscribed")
             ON DUPLICATE KEY UPDATE name = VALUES(name), status = "subscribed", unsubscribed_at = NULL'
        )->execute([
            'email' => $payload['email'],
            'name' => trim($payload['first_name'] . ' ' . $payload['last_name']),
        ]);
        return;
    }

    db()->prepare(
        'UPDATE newsletter_subscribers
         SET status = "unsubscribed", unsubscribed_at = CURRENT_TIMESTAMP
         WHERE email = :email'
    )->execute(['email' => $payload['email']]);
}

function admin_customer_save_session(array $payload): bool
{
    $customers = admin_customer_session_items();

    if ($payload['id'] === '') {
        $payload['id'] = (string) (($customers === [] ? 0 : max(array_map(static fn (array $customer): int => (int) ($customer['id'] ?? 0), $customers))) + 1);
        $payload['created_at'] = date(DATE_ATOM);
        $customers[] = $payload;
    } else {
        foreach ($customers as $index => $customer) {
            if ((int) ($customer['id'] ?? 0) === (int) $payload['id']) {
                $customers[$index] = array_merge($customer, $payload);
                admin_customer_save_session_items($customers);
                return true;
            }
        }

        $customers[] = $payload;
    }

    admin_customer_save_session_items($customers);

    return true;
}

function admin_customer_delete(int $id): void
{
    if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
        return;
    }

    try {
        db()->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
        return;
    } catch (Throwable) {
        try {
            db()->prepare('UPDATE users SET status = "blocked" WHERE id = :id')->execute(['id' => $id]);
            return;
        } catch (Throwable) {
        }
    }

    $customers = array_filter(
        admin_customer_session_items(),
        static fn (array $customer): bool => (int) ($customer['id'] ?? 0) !== $id
    );

    admin_customer_save_session_items($customers);
}

function admin_customer_orders(int $id): array
{
    try {
        $stmt = db()->prepare(
            'SELECT order_number, status, grand_total, created_at
             FROM orders
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT 8'
        );
        $stmt->execute(['user_id' => $id]);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return $_SESSION['customer_' . $id . '_orders'] ?? [];
    }
}

function admin_customer_addresses(int $id): array
{
    try {
        $stmt = db()->prepare(
            'SELECT *
             FROM addresses
             WHERE user_id = :user_id
             ORDER BY is_default DESC, created_at DESC
             LIMIT 6'
        );
        $stmt->execute(['user_id' => $id]);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return $_SESSION['customer_' . $id . '_addresses'] ?? [];
    }
}

function admin_customer_notes(int $id): array
{
    try {
        $stmt = db()->prepare(
            'SELECT *
             FROM audit_logs
             WHERE entity_type = "customer" AND entity_id = :entity_id AND action = "customer_note"
             ORDER BY created_at DESC
             LIMIT 8'
        );
        $stmt->execute(['entity_id' => $id]);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return $_SESSION['customer_' . $id . '_notes'] ?? [];
    }
}

function admin_customer_add_note(int $id, string $note): void
{
    $note = trim($note);

    if ($note === '') {
        return;
    }

    try {
        admin_customer_log('customer_note', $id, ['note' => $note]);
        return;
    } catch (Throwable) {
    }

    $_SESSION['customer_' . $id . '_notes'][] = [
        'payload_json' => json_encode(['note' => $note], JSON_UNESCAPED_UNICODE),
        'created_at' => date(DATE_ATOM),
    ];
}

function admin_customer_log(string $action, int $customerId, array $payload): void
{
    db()->prepare(
        'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, payload_json)
         VALUES (:user_id, :action, "customer", :entity_id, :ip_address, :user_agent, :payload_json)'
    )->execute([
        'user_id' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
        'action' => $action,
        'entity_id' => $customerId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
}

function admin_customer_note_text(array $note): string
{
    $payload = json_decode((string) ($note['payload_json'] ?? ''), true);

    return is_array($payload) ? (string) ($payload['note'] ?? '') : '';
}
