<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/database.php';

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$adminEmail = getenv('PPCOSTA_E2E_ADMIN_EMAIL') ?: 'qa.admin@ppcosta.local';
$adminPassword = getenv('PPCOSTA_E2E_ADMIN_PASSWORD') ?: 'PPCosta!2026Admin';
$customerEmail = 'qa.customer+' . date('YmdHis') . '@ppcosta.local';
$customerPassword = 'PPCosta!2026Cliente';
$cookieFile = tempnam(sys_get_temp_dir(), 'ppcosta_e2e_');
$adminCookieFile = tempnam(sys_get_temp_dir(), 'ppcosta_admin_');

function e2e_request(string $method, string $url, ?array $data, string $cookieFile): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_TIMEOUT => 15,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data ?? []));
    }

    $raw = curl_exec($ch);

    if ($raw === false) {
        throw new RuntimeException(curl_error($ch));
    }

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return [
        'status' => $status,
        'headers' => substr($raw, 0, $headerSize),
        'body' => substr($raw, $headerSize),
    ];
}

function e2e_token(string $html): string
{
    if (!preg_match('/name="csrf_token" value="([^"]+)"/', $html, $matches)) {
        throw new RuntimeException('Token CSRF nao encontrado.');
    }

    return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
}

function e2e_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }

    echo '[OK] ' . $message . PHP_EOL;
}

try {
    e2e_assert(db_available(), 'Base de dados disponivel');

    $register = e2e_request('GET', $baseUrl . '/register.php', null, $cookieFile);
    e2e_assert($register['status'] === 200, 'Pagina de registo responde');
    $registerToken = e2e_token($register['body']);

    $registerPost = e2e_request('POST', $baseUrl . '/register.php', [
        'csrf_token' => $registerToken,
        'first_name' => 'QA',
        'last_name' => 'Cliente',
        'email' => $customerEmail,
        'phone' => '910000000',
        'password' => $customerPassword,
        'password_confirmation' => $customerPassword,
        'newsletter_opt_in' => '1',
    ], $cookieFile);
    e2e_assert(in_array($registerPost['status'], [302, 303], true), 'Cliente QA registado');

    $login = e2e_request('GET', $baseUrl . '/login.php', null, $cookieFile);
    $loginToken = e2e_token($login['body']);
    $loginPost = e2e_request('POST', $baseUrl . '/login.php', [
        'csrf_token' => $loginToken,
        'email' => $customerEmail,
        'password' => $customerPassword,
    ], $cookieFile);
    e2e_assert(in_array($loginPost['status'], [302, 303], true), 'Cliente QA iniciou sessao');

    $product = e2e_request('GET', $baseUrl . '/produto/body-bebe-bordado', null, $cookieFile);
    e2e_assert($product['status'] === 200 && str_contains($product['body'], 'Body bebe bordado'), 'Produto amigavel abre corretamente');
    $productToken = e2e_token($product['body']);

    $cartPost = e2e_request('POST', $baseUrl . '/carrinho.php', [
        'csrf_token' => $productToken,
        'action' => 'add',
        'product_id' => '1',
        'quantity' => '1',
        'personalization' => [
            'nome' => 'Mia',
            'texto' => 'Primeiro Natal',
            'fonte' => 'script',
            'cor' => '#b91c1c',
            'posicao' => 'frente',
            'tecnica' => 'bordado',
        ],
        'uploaded_personalization_file' => '',
    ], $cookieFile);
    e2e_assert(in_array($cartPost['status'], [302, 303], true), 'Produto personalizado adicionado ao carrinho');

    $checkout = e2e_request('GET', $baseUrl . '/checkout.php', null, $cookieFile);
    e2e_assert($checkout['status'] === 200 && str_contains($checkout['body'], 'Finalizar encomenda'), 'Checkout abre com carrinho preenchido');
    $checkoutToken = e2e_token($checkout['body']);

    $checkoutPost = e2e_request('POST', $baseUrl . '/checkout.php', [
        'csrf_token' => $checkoutToken,
        'first_name' => 'QA',
        'last_name' => 'Cliente',
        'email' => $customerEmail,
        'phone' => '910000000',
        'company' => '',
        'tax_number' => '999999990',
        'address_line_1' => 'Rua de Teste 123',
        'address_line_2' => '',
        'postal_code' => '1000-001',
        'city' => 'Lisboa',
        'district' => 'Lisboa',
        'same_billing' => '1',
        'shipping_method' => 'ctt',
        'payment_method' => 'mbway',
        'notes' => 'Encomenda QA quase producao',
        'gdpr_accept' => '1',
    ], $cookieFile);
    e2e_assert(in_array($checkoutPost['status'], [302, 303], true), 'Checkout submetido');

    $stmt = db()->prepare('SELECT * FROM orders WHERE customer_email = :email ORDER BY id DESC LIMIT 1');
    $stmt->execute(['email' => $customerEmail]);
    $order = $stmt->fetch();
    e2e_assert((bool) $order, 'Encomenda gravada na base de dados');

    $itemStmt = db()->prepare('SELECT COUNT(*) FROM order_items WHERE order_id = :order_id');
    $itemStmt->execute(['order_id' => (int) $order['id']]);
    e2e_assert((int) $itemStmt->fetchColumn() > 0, 'Itens da encomenda gravados');

    $timelineStmt = db()->prepare('SELECT COUNT(*) FROM order_status_history WHERE order_id = :order_id');
    $timelineStmt->execute(['order_id' => (int) $order['id']]);
    e2e_assert((int) $timelineStmt->fetchColumn() > 0, 'Linha temporal da encomenda criada');

    $adminLogin = e2e_request('GET', $baseUrl . '/login.php', null, $adminCookieFile);
    $adminToken = e2e_token($adminLogin['body']);
    $adminLoginPost = e2e_request('POST', $baseUrl . '/login.php', [
        'csrf_token' => $adminToken,
        'email' => $adminEmail,
        'password' => $adminPassword,
    ], $adminCookieFile);
    e2e_assert(in_array($adminLoginPost['status'], [302, 303], true), 'Admin QA iniciou sessao');

    $adminOrder = e2e_request('GET', $baseUrl . '/admin/encomendas.php?view=' . urlencode((string) $order['id']), null, $adminCookieFile);
    e2e_assert($adminOrder['status'] === 200 && str_contains($adminOrder['body'], (string) $order['order_number']), 'Backoffice mostra detalhe da encomenda');

    echo PHP_EOL . 'Ciclo completo OK: ' . $order['order_number'] . ' | ' . $customerEmail . PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, '[ERRO] ' . $exception->getMessage() . PHP_EOL);
    exit(1);
} finally {
    if (is_string($cookieFile) && is_file($cookieFile)) {
        unlink($cookieFile);
    }
    if (is_string($adminCookieFile) && is_file($adminCookieFile)) {
        unlink($adminCookieFile);
    }
}
