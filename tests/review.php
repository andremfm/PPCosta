<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_products.php';
require_once __DIR__ . '/../includes/admin_orders.php';
require_once __DIR__ . '/../includes/admin_customers.php';
require_once __DIR__ . '/../includes/admin_reviews.php';
require_once __DIR__ . '/../includes/admin_messages.php';
require_once __DIR__ . '/../includes/customer.php';

if (APP_ENV === 'production' || MAIL_TRANSPORT !== 'log') {
    exit("Este teste exige ambiente local e email em modo log.\n");
}
$base = rtrim($argv[1] ?? 'http://localhost:8000', '/');
$tag = 'review-' . bin2hex(random_bytes(5));
$email = $tag . '@ppcosta.local';
$productId = $userId = 0;
$orders = [];
$uploadedPaths = [];
$optionId = 0;
$couponId = 0;
$cookies = tempnam(sys_get_temp_dir(), 'ppc_review_');
$mailBefore = glob(MAIL_LOG_DIR . '/*.eml') ?: [];
$passed = 0;

function check(bool $ok, string $label): void
{
    global $passed;
    if (!$ok) throw new RuntimeException($label);
    $passed++;
    echo '[OK] ' . $label . PHP_EOL;
}

function request_page(string $path, ?array $data = null): array
{
    global $base, $cookies;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $cookies, CURLOPT_COOKIEFILE => $cookies, CURLOPT_TIMEOUT => 15]);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        $multipart = count(array_filter($data, static fn ($value): bool => $value instanceof CURLFile)) > 0;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart ? $data : http_build_query($data));
    }
    $raw = curl_exec($ch);
    if ($raw === false) throw new RuntimeException(curl_error($ch));
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    return ['status' => $status, 'body' => substr($raw, $headerSize), 'headers' => substr($raw, 0, $headerSize)];
}

function token_from(array $response): string
{
    if (!preg_match('/name="csrf_token" value="([^"]+)"/', $response['body'], $match)) throw new RuntimeException('CSRF ausente');
    return $match[1];
}

try {
    $pdo = db();
    $taxId = (int) $pdo->query('SELECT id FROM tax_rates ORDER BY is_default DESC LIMIT 1')->fetchColumn();
    $pdo->prepare('INSERT INTO products (tax_rate_id, name, slug, sku, price, stock) VALUES (:tax, :name, :slug, :sku, 10, 3)')->execute(['tax' => $taxId, 'name' => $tag, 'slug' => $tag, 'sku' => strtoupper($tag)]);
    $productId = (int) $pdo->lastInsertId();
    $userId = register_user(['first_name' => 'Review', 'last_name' => $tag, 'email' => $email, 'phone' => '', 'password' => 'Review-Test!2026']);
    check(!in_array('admin', user_roles($userId), true), 'Registo publico nao concede administracao');
    check(count(catalog_products(['q' => $tag])) === 1, 'Pesquisa do catalogo encontra registo real');
    check(count(admin_customers_all(['q' => $tag])) === 1, 'Pesquisa de clientes funciona');
    check(review_find_product_id(product_url($tag)) === $productId, 'Avaliacao encontra produto pelo URL');
    check(customer_wishlist($userId) === [], 'Wishlist vazia nao inventa favoritos');
    check(customer_toggle_wishlist($userId, $productId), 'Adiciona favorito real');
    check(count(customer_wishlist($userId)) === 1, 'Favorito aparece na conta');
    check(!customer_toggle_wishlist($userId, $productId), 'Remove favorito real');
    check(personalization_rules_for_product($productId) === [], 'Produto sem regras nao recebe regras ficticias');
    $typeId = (int) $pdo->query('SELECT id FROM personalization_types WHERE slug = "tecnica"')->fetchColumn();
    $pdo->prepare('INSERT INTO personalization_options (personalization_type_id, label, value, extra_price, is_active) VALUES (:type, :label, :value, 1, 1)')->execute(['type' => $typeId, 'label' => $tag, 'value' => $tag]);
    $optionId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO product_personalizations (product_id, personalization_type_id, label, is_required, is_active) VALUES (:product, :type, "Tecnica", 1, 1)')->execute(['product' => $productId, 'type' => $typeId]);
    $ruleId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO product_personalization_options (product_personalization_id, personalization_option_id) VALUES (:rule, :option)')->execute(['rule' => $ruleId, 'option' => $optionId]);
    check(count(personalization_rules_for_product($productId)[0]['options']) === 1, 'Produto apresenta apenas tecnica atribuida');
    $pdo->prepare('UPDATE personalization_options SET is_active = 0 WHERE id = :id')->execute(['id' => $optionId]);
    $restricted = personalization_rules_for_product($productId);
    check($restricted[0]['options'] === [], 'Desativar ultima tecnica nao reativa outras');
    check(personalization_validate_values($restricted, ['tecnica' => 'embroidery']) !== [], 'Servidor rejeita tecnica fora das regras');
    $pdo->prepare('DELETE FROM product_personalizations WHERE id = :id')->execute(['id' => $ruleId]);
    check(personalization_validate_values([], ['tecnica' => 'embroidery']) !== [], 'Rejeita personalizacao nao autorizada');
    check(personalization_validate_values([['slug' => 'texto', 'label' => 'Texto', 'input_type' => 'text', 'max_length' => 3]], ['texto' => 'texto longo']) !== [], 'Valida comprimento no servidor');
    check(personalization_validate_values([['slug' => 'texto', 'label' => 'Texto']], ['texto' => ['array']]) !== [], 'Rejeita valores estruturados inesperados');
    cart_clear();
    check(cart_add_item($productId, 2), 'Adiciona quantidade disponivel');
    check(!cart_add_item($productId, 2), 'Soma do carrinho respeita stock');
    $key = array_key_first(cart()['items']);
    check(!cart_update_quantities([$key => 4]), 'Atualizacao nao permite ultrapassar stock');
    check(cart()['items'][$key]['quantity'] === 2, 'Falha preserva quantidade anterior');
    $pdo->prepare('INSERT INTO coupons (code, name, discount_type, discount_value, usage_limit) VALUES (:code, "Review", "percent", 10, 1)')->execute(['code' => strtoupper($tag)]);
    $couponId = (int) $pdo->lastInsertId();
    check(cart_apply_coupon($tag), 'Cupao real aceite');
    check(cart_totals()['discount'] === 2.0, 'Desconto real calculado');
    check(cart_apply_coupon(''), 'Cupao removido');
    $pdo->prepare('UPDATE coupons SET used_count = 1 WHERE id = :id')->execute(['id' => $couponId]);
    check(!cart_apply_coupon($tag), 'Cupao esgotado rejeitado');
    check(!cart_add_item($productId, 1, [], 'uploads/personalizations/foreign.pdf'), 'Rejeita ficheiro sem propriedade da sessao');
    check(!isset(checkout_shipping_methods()['free_shipping']), 'Portes gratuitos indisponiveis abaixo do limiar');
    $data = ['first_name' => 'QA', 'last_name' => 'Review', 'email' => $email, 'phone' => '910000000', 'address_line_1' => 'Rua de teste', 'postal_code' => '1000-001', 'city' => 'Lisboa', 'same_billing' => '1', 'shipping_method' => 'ctt', 'payment_method' => 'cash_on_delivery', 'gdpr_accept' => '1'];
    check(checkout_validate($data) === [], 'Checkout valido aceite');
    check(checkout_validate(array_replace($data, ['same_billing' => ''])) !== [], 'Entrega separada exige morada');
    $pickup = checkout_prepare_order(array_replace($data, ['shipping_method' => 'pickup']));
    check($pickup['totals']['shipping'] === 0.0, 'Levantamento sem portes');
    $order = checkout_store_order(checkout_prepare_order($data));
    check($order['persisted'], 'Encomenda persistida com pagamento');
    $orders[] = $order['id'];
    check((int) catalog_product_by_id($productId)['stock'] === 1, 'Stock reservado pela encomenda');
    $failedOrder = checkout_store_order(checkout_prepare_order($data));
    check(!$failedOrder['persisted'], 'Segunda compra sem stock rejeitada');
    check((int) catalog_product_by_id($productId)['stock'] === 1, 'Rollback conserva stock');
    admin_order_update_status($order['id'], 'cancelled');
    check((int) catalog_product_by_id($productId)['stock'] === 3, 'Cancelamento repoe stock');
    admin_order_update_status($order['id'], 'cancelled');
    check((int) catalog_product_by_id($productId)['stock'] === 3, 'Repetir cancelamento nao duplica stock');
    check(count(admin_orders_all(['q' => $email])) === 1, 'Pesquisa de encomendas funciona');
    $pdo->prepare('INSERT INTO reviews (product_id, user_id, rating, title, comment) VALUES (:product, :user, 5, :title, "Teste")')->execute(['product' => $productId, 'user' => $userId, 'title' => $tag]);
    check(count(admin_reviews_all(['q' => $tag])) === 1, 'Pesquisa de avaliacoes funciona');
    customer_send_message($userId, ['subject' => $tag, 'body' => 'Mensagem de teste']);
    check(count(admin_messages_all(['q' => $tag])) === 1, 'Pesquisa de mensagens funciona');
    admin_product_save_variation($productId, ['sku' => $tag . '-v', 'stock' => 1, 'price_delta' => 2, 'is_active' => 1]);
    $variationId = (int) admin_product_variations($productId)[0]['id'];
    admin_product_save_variation($productId, ['variation_id' => $variationId, 'sku' => $tag . '-edited', 'stock' => 1, 'price_delta' => 2, 'is_active' => 1]);
    check(count(admin_product_variations($productId)) === 1 && str_ends_with(admin_product_variation_find($productId, $variationId)['sku'], '-EDITED'), 'Editar variacao conserva identificador e registo');
    cart_clear();
    check(!cart_add_item($productId, 1), 'Produto com variacoes exige escolha');
    check(!cart_add_item($productId, 2, [], '', $variationId), 'Stock por variacao validado');
    check(cart_add_item($productId, 1, [], '', $variationId), 'Variacao disponivel aceite');
    check(cart_totals()['subtotal'] === 12.0, 'Preco da variacao aplicado');

    foreach (['/' => 200, '/index.php' => 200, '/produto.php' => 200, '/produto/' . $tag => 200, '/categoria/bebe' => 200, '/login.php' => 200, '/register.php' => 200, '/recuperar-password.php' => 200, '/reset-password.php' => 302, '/carrinho.php' => 200, '/checkout.php' => 302, '/encomenda-concluida.php' => 302, '/perfil.php' => 302, '/contactos' => 200, '/termos' => 200, '/privacidade' => 200, '/devolucoes' => 200, '/sitemap.xml' => 200, '/api/products.php?q=' . $tag => 200, '/api/cart-summary.php' => 200, '/nao-existe-review' => 404, '/produto/nao-existe-review' => 404, '/database/001_schema.sql' => 404, '/includes/config.local.php' => 404, '/uploads/mail/test.eml' => 404] as $path => $status) {
        $response = request_page($path);
        check($response['status'] === $status && !preg_match('/(?:Fatal error|Warning|Parse error):/', $response['body']), 'HTTP ' . $status . ' ' . $path);
    }
    $login = request_page('/login.php');
    $uploadTemp = tempnam(sys_get_temp_dir(), 'ppc_upload_');
    file_put_contents($uploadTemp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQAAAABJRU5ErkJggg=='));
    try {
        $uploadResponse = request_page('/api/personalization-upload.php', ['csrf_token' => token_from($login), 'file' => new CURLFile($uploadTemp, 'image/png', 'unsafe.php')]);
        $upload = json_decode($uploadResponse['body'], true);
        check($uploadResponse['status'] === 200 && str_ends_with($upload['path'] ?? '', '.png'), 'Upload recebe extensao segura definida pelo conteudo');
        $uploadedPaths[] = dirname(__DIR__) . '/' . $upload['path'];
        check(request_page('/' . $upload['path'])['status'] === 404, 'Upload privado nao e servido diretamente');
    } finally {
        unlink($uploadTemp);
    }
    request_page('/login.php', ['csrf_token' => token_from($login), 'email' => $email, 'password' => 'Review-Test!2026']);
    check(request_page('/perfil.php')['status'] === 200, 'Area cliente autenticada responde');
    check(request_page('/admin/produtos.php')['status'] === 403, 'Cliente nao acede ao painel');
    check(request_page('/admin/produto-personalizacao.php?product_id=' . $productId)['status'] === 403, 'Cliente nao altera regras de personalizacao');
    check(request_page('/admin/')['status'] === 403, 'Entrada curta do painel respeita permissoes');
    $productPage = request_page('/produto/' . $tag);
    request_page('/carrinho.php', ['csrf_token' => token_from($productPage), 'action' => 'add', 'product_id' => $productId, 'variation_id' => $variationId, 'quantity' => 1]);
    $checkoutPage = request_page('/checkout.php');
    check($checkoutPage['status'] === 200 && str_contains($checkoutPage['body'], $tag), 'HTTP checkout contem produto escolhido');
    $pdo->prepare('UPDATE product_variations SET stock = 0 WHERE id = :id')->execute(['id' => $variationId]);
    $failedCheckout = request_page('/checkout.php', $data + ['csrf_token' => token_from($checkoutPage)]);
    check(str_contains($failedCheckout['headers'], '/checkout.php'), 'HTTP falha de stock nao confirma compra');
    $cartSummary = json_decode(request_page('/api/cart-summary.php')['body'], true);
    check($cartSummary['units'] === 1, 'HTTP falha conserva carrinho');
    $pdo->prepare('UPDATE product_variations SET stock = 1 WHERE id = :id')->execute(['id' => $variationId]);
    $completed = request_page('/checkout.php', $data + ['csrf_token' => token_from($checkoutPage)]);
    check(str_contains($completed['headers'], '/encomenda-concluida.php'), 'HTTP ciclo de compra concluido');
    $last = $pdo->prepare('SELECT id FROM orders WHERE customer_email = :email ORDER BY id DESC LIMIT 1');
    $last->execute(['email' => $email]);
    $orders[] = (int) $last->fetchColumn();
    check(request_page('/encomenda-concluida.php')['status'] === 200, 'HTTP confirmacao responde');
    check((int) catalog_product_variation($productId, $variationId)['stock'] === 0, 'HTTP encomenda reserva stock da variacao');
    $pdo->prepare('INSERT INTO user_roles (user_id, role_id) SELECT :id, id FROM roles WHERE slug = "admin"')->execute(['id' => $userId]);
    check(request_page('/admin/')['status'] === 200, 'Entrada curta do painel abre dashboard');
    foreach (['index', 'produtos', 'personalizacao', 'clientes', 'encomendas', 'stock', 'relatorios', 'avaliacoes', 'mensagens', 'configuracoes'] as $page) {
        $response = request_page('/admin/' . $page . '.php');
        check($response['status'] === 200 && !preg_match('/(?:Fatal error|Warning|Parse error):/', $response['body']), 'Painel: ' . $page);
    }
    $response = request_page('/admin/produtos.php?edit=' . $productId);
    $personalizationPage = request_page('/admin/produto-personalizacao.php?product_id=' . $productId . '&type=' . $typeId);
    check($personalizationPage['status'] === 200 && str_contains($personalizationPage['body'], 'Guardar personalizacao'), 'Admin abre editor de personalizacao por produto');
    check(request_page('/admin/produto-personalizacao.php?product_id=' . $productId, ['id' => 0])['status'] === 403, 'Editor rejeita POST sem CSRF');
    check($response['status'] === 200 && str_contains($response['body'], strtoupper($tag)), 'Edicao do produto responde');
    request_page('/admin/produtos.php', ['csrf_token' => token_from($response), 'action' => 'toggle', 'id' => $productId]);
    $stmt = $pdo->prepare('SELECT is_active FROM products WHERE id = :id');
    $stmt->execute(['id' => $productId]);
    check($stmt->fetchColumn() === 0, 'Botao ativar/desativar conserva o registo');
    $pdo->prepare('UPDATE users SET status = "blocked" WHERE id = :id')->execute(['id' => $userId]);
    check(request_page('/admin/index.php')['status'] === 302, 'Bloqueio revoga acesso de sessao existente');
    echo "\nResultado: {$passed} verificacoes passaram.\n";
} finally {
    if (isset($pdo)) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($orders as $id) $pdo->prepare('DELETE FROM orders WHERE id = :id')->execute(['id' => $id]);
        if ($productId) $pdo->prepare('DELETE FROM products WHERE id = :id')->execute(['id' => $productId]);
        if ($optionId) $pdo->prepare('DELETE FROM personalization_options WHERE id = :id')->execute(['id' => $optionId]);
        if ($couponId) $pdo->prepare('DELETE FROM coupons WHERE id = :id')->execute(['id' => $couponId]);
        $pdo->prepare('DELETE FROM message_threads WHERE user_id = :user AND subject = :subject')->execute(['user' => $userId, 'subject' => $tag]);
        if ($userId) $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $userId]);
        $pdo->prepare('DELETE FROM newsletter_subscribers WHERE email = :email')->execute(['email' => $email]);
    }
    foreach (array_diff(glob(MAIL_LOG_DIR . '/*.eml') ?: [], $mailBefore) as $path) {
        if (str_contains((string) file_get_contents($path), $email)) unlink($path);
    }
    if (is_file($cookies)) unlink($cookies);
    foreach ($uploadedPaths as $path) if (is_file($path)) unlink($path);
    cart_clear();
}
