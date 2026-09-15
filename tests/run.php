<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/seo.php';

$passed = 0;
$failed = 0;

function test_assert(bool $condition, string $message): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "[OK] " . $message . PHP_EOL;
        return;
    }

    $failed++;
    echo "[FAIL] " . $message . PHP_EOL;
}

function test_same(mixed $expected, mixed $actual, string $message): void
{
    test_assert($expected === $actual, $message . ' | esperado: ' . var_export($expected, true) . ' obtido: ' . var_export($actual, true));
}

test_same('12,50 EUR', format_price(12.5), 'Formata preco em EUR');
test_same(APP_URL . '/produto/body-bebe-bordado', product_url('body-bebe-bordado'), 'Gera URL amigavel de produto');
test_same(APP_URL . '/categoria/bebe', category_url('bebe'), 'Gera URL amigavel de categoria');

$fallbackProducts = catalog_filter_fallback_products(['category' => 'bebe']);
test_assert(count($fallbackProducts) >= 1, 'Catalogo fallback filtra por categoria');
test_same('bebe', $fallbackProducts[0]['category_slug'], 'Produto filtrado pertence a categoria esperada');

$rules = [
    ['slug' => 'nome', 'base_extra_price' => 2.50, 'options' => []],
    ['slug' => 'fonte', 'base_extra_price' => 0, 'options' => [['value' => 'script', 'extra_price' => 1.25]]],
];
$personalizationTotal = personalization_calculate_total($rules, ['nome' => 'Mia', 'fonte' => 'script']);
test_same(3.75, $personalizationTotal, 'Calcula preco adicional de personalizacao');

cart_clear();
$_SESSION['cart']['items']['test'] = [
    'quantity' => 2,
    'unit_price' => 10.00,
    'personalization_total' => 2.50,
];
$totals = cart_totals();
test_same(2, $totals['units'], 'Carrinho conta unidades');
test_same(25.0, $totals['subtotal'], 'Carrinho calcula subtotal com personalizacao');
$_SESSION['cart']['coupon'] = ['code' => 'UNIT', 'type' => 'percent', 'value' => 10];
test_same(2.5, cart_totals()['discount'], 'Cupao percentual calcula desconto');
test_assert(!cart_apply_coupon('INVALIDO'), 'Cupao invalido e rejeitado');

$token = csrf_token();
test_assert(verify_csrf($token), 'Token CSRF valido');
test_assert(!verify_csrf('token-invalido'), 'Token CSRF invalido rejeitado');
rate_limit_clear('teste');
test_assert(rate_limit_allow('teste', 2, 60), 'Rate limit permite primeira tentativa');
test_assert(rate_limit_allow('teste', 2, 60), 'Rate limit permite segunda tentativa');
test_assert(!rate_limit_allow('teste', 2, 60), 'Rate limit bloqueia excesso de tentativas');
rate_limit_clear('teste');

$product = catalog_fallback_products()[0];
$schema = seo_product_schema($product);
test_same('Product', $schema['@type'], 'Schema.org de produto usa tipo Product');
test_same('EUR', $schema['offers']['priceCurrency'], 'Schema.org de produto usa EUR');

echo PHP_EOL . "Resultado: {$passed} passou, {$failed} falhou." . PHP_EOL;
exit($failed > 0 ? 1 : 0);
