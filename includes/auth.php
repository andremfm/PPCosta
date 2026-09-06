<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => strtolower(trim($email))]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function find_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function user_roles(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT r.slug
         FROM roles r
         INNER JOIN user_roles ur ON ur.role_id = r.id
         WHERE ur.user_id = :user_id'
    );
    $stmt->execute(['user_id' => $userId]);

    return array_column($stmt->fetchAll(), 'slug');
}

function register_user(array $data): int
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $countStmt = $pdo->query('SELECT COUNT(*) FROM users');
        $isFirstUser = ((int) $countStmt->fetchColumn()) === 0;

        $stmt = $pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, phone, password_hash, status, newsletter_opt_in)
             VALUES (:first_name, :last_name, :email, :phone, :password_hash, :status, :newsletter_opt_in)'
        );
        $stmt->execute([
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'] !== '' ? trim($data['phone']) : null,
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'status' => 'active',
            'newsletter_opt_in' => !empty($data['newsletter_opt_in']) ? 1 : 0,
        ]);

        $userId = (int) $pdo->lastInsertId();
        $roleSlugs = $isFirstUser ? ['customer', 'admin'] : ['customer'];
        $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1');
        $assignStmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');

        foreach ($roleSlugs as $slug) {
            $roleStmt->execute(['slug' => $slug]);
            $roleId = $roleStmt->fetchColumn();

            if ($roleId) {
                $assignStmt->execute(['user_id' => $userId, 'role_id' => (int) $roleId]);
            }
        }

        if (!empty($data['newsletter_opt_in'])) {
            $newsletterStmt = $pdo->prepare(
                'INSERT INTO newsletter_subscribers (email, name, status)
                 VALUES (:email, :name, :status)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status), unsubscribed_at = NULL'
            );
            $newsletterStmt->execute([
                'email' => strtolower(trim($data['email'])),
                'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                'status' => 'subscribed',
            ]);
        }

        $pdo->commit();
        return $userId;
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function login_user(string $email, string $password): bool
{
    $user = find_user_by_email($email);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    if (!in_array($user['status'], ['active', 'pending'], true)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_roles'] = user_roles((int) $user['id']);

    $stmt = db()->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute(['id' => (int) $user['id']]);

    return true;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

function create_password_reset(string $email): ?string
{
    $user = find_user_by_email($email);

    if (!$user) {
        return null;
    }

    $token = bin2hex(random_bytes(32));
    $stmt = db()->prepare(
        'UPDATE users
         SET reset_token_hash = :token_hash, reset_token_expires_at = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR)
         WHERE id = :id'
    );
    $stmt->execute([
        'token_hash' => password_hash($token, PASSWORD_DEFAULT),
        'id' => (int) $user['id'],
    ]);

    return $token;
}

function find_user_by_reset_token(string $token): ?array
{
    $stmt = db()->query(
        'SELECT *
         FROM users
         WHERE reset_token_hash IS NOT NULL
           AND reset_token_expires_at > CURRENT_TIMESTAMP'
    );

    foreach ($stmt->fetchAll() as $user) {
        if (password_verify($token, $user['reset_token_hash'])) {
            return $user;
        }
    }

    return null;
}

function reset_user_password(string $token, string $password): bool
{
    $user = find_user_by_reset_token($token);

    if (!$user) {
        return false;
    }

    $stmt = db()->prepare(
        'UPDATE users
         SET password_hash = :password_hash, reset_token_hash = NULL, reset_token_expires_at = NULL
         WHERE id = :id'
    );
    $stmt->execute([
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'id' => (int) $user['id'],
    ]);

    return true;
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    try {
        return find_user_by_id((int) $_SESSION['user_id']);
    } catch (Throwable) {
        return null;
    }
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(string $role): bool
{
    return in_array($role, $_SESSION['user_roles'] ?? [], true);
}

function require_auth(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Inicia sessao para continuar.');
        redirect('login.php');
    }
}

function require_role(string $role): void
{
    require_auth();

    if (!has_role($role)) {
        http_response_code(403);
        exit('Acesso reservado.');
    }
}
