<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_products.php';
require_once __DIR__ . '/../includes/admin_product_personalization.php';
require_once __DIR__ . '/../includes/cart.php';
if (APP_ENV === 'production') { exit("Executar apenas em desenvolvimento.\n"); }
$pdo = db();
$tag = 'personalization-test-' . bin2hex(random_bytes(5));
$products = $types = [];
$passed = 0;
function verify_personalization(bool $ok, string $label): void
{
    global $passed;
    if (!$ok) { throw new RuntimeException($label); }
    $passed++;
    echo "[OK] $label\n";
}
function reject_personalization(callable $operation, string $label): void
{
    try { $operation(); } catch (InvalidArgumentException) { verify_personalization(true, $label); return; }
    throw new RuntimeException($label);
}
try {
    $taxId = $pdo->query('SELECT id FROM tax_rates LIMIT 1')->fetchColumn();
    foreach (['a', 'b'] as $suffix) {
        $pdo->prepare('INSERT INTO products (tax_rate_id,name,slug,sku,price,stock,is_personalizable) VALUES (?,?,?,?,10,5,1)')->execute([$taxId,$tag.$suffix,$tag.$suffix,$tag.$suffix]);
        $products[] = (int) $pdo->lastInsertId();
    }
    foreach (['font', 'text'] as $input) {
        $pdo->prepare('INSERT INTO personalization_types (name,slug,input_type) VALUES (?,?,?)')->execute([$tag.$input,$tag.$input,$input]);
        $types[] = (int) $pdo->lastInsertId();
    }
    $pdo->prepare('INSERT INTO personalization_options (personalization_type_id,label,value,extra_price) VALUES (?,"Classic","classic",1.5),(?,"Modern","modern",3)')->execute([$types[0],$types[0]]);
    $stmt = $pdo->prepare('SELECT id FROM personalization_options WHERE personalization_type_id=? ORDER BY id');
    $stmt->execute([$types[0]]);
    [$classic,$modern] = array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN));
    $data = ['personalization_type_id'=>$types[0], 'label'=>'Fonte', 'base_extra_price'=>'2.00', 'sort_order'=>'10', 'options_mode'=>'selected', 'options'=>[(string)$classic], 'prices'=>[$classic=>'4.25'], 'is_required'=>1, 'is_active'=>1];
    $id = admin_product_personalization_save($products[0], $data);
    $rules = personalization_rules_for_product($products[0]);
    $slug = $tag.'font';
    verify_personalization(count($rules) === 1 && count($rules[0]['options']) === 1, 'Apenas opcoes selecionadas chegam a loja');
    verify_personalization(personalization_calculate_total($rules,[$slug=>'classic']) === 6.25, 'Preco soma base e acrescimo especifico');
    verify_personalization(personalization_validate_values($rules,[$slug=>'modern']) !== [], 'Rejeita opcao nao atribuida');
    verify_personalization(personalization_rules_for_product($products[1]) === [], 'Configuracao isolada por produto');
    verify_personalization(cart_add_item($products[0],1,[$slug=>'classic']), 'Carrinho aceita personalizacao configurada');
    $item = array_values(cart()['items'])[0];
    verify_personalization((float)$item['personalization_total'] === 6.25, 'Carrinho calcula o preco no servidor');
    cart_clear();
    $original = admin_product_find($products[0]);
    verify_personalization(admin_product_save($original), 'Edicao normal do produto funciona');
    verify_personalization(personalization_calculate_total(personalization_rules_for_product($products[0]),[$slug=>'classic']) === 6.25, 'Edicao nao perde regras ou precos');
    reject_personalization(fn()=>admin_product_personalization_save($products[1], ['id'=>$id]+$data), 'Rejeita regra pertencente a outro produto');
    reject_personalization(fn()=>admin_product_personalization_save($products[0], $data), 'Rejeita tipo duplicado no produto');
    reject_personalization(fn()=>admin_product_personalization_save($products[0], ['id'=>$id,'base_extra_price'=>'-1']+$data), 'Rejeita preco negativo');
    reject_personalization(fn()=>admin_product_personalization_save($products[0], ['id'=>$id,'options'=>['999999999']]+$data), 'Rejeita opcao desconhecida');
    verify_personalization(personalization_calculate_total(personalization_rules_for_product($products[0]),[$slug=>'classic']) === 6.25, 'Erros preservam configuracao anterior');
    admin_product_personalization_save($products[0], ['id'=>$id,'options'=>[]]+$data);
    verify_personalization(personalization_rules_for_product($products[0])[0]['options'] === [], 'Selecao vazia nao herda opcoes globais');
    admin_product_personalization_save($products[0], ['id'=>$id,'options_mode'=>'all']+$data);
    $rules = personalization_rules_for_product($products[0]);
    verify_personalization(count($rules[0]['options']) === 2 && personalization_calculate_total($rules,[$slug=>'classic']) === 3.5, 'Modo global herda opcoes e precos globais');
    admin_product_personalization_save($products[0], ['id'=>$id]+$data);
    $pdo->prepare('UPDATE personalization_options SET is_active=0 WHERE id=?')->execute([$classic]);
    verify_personalization(personalization_rules_for_product($products[0])[0]['options'] === [], 'Opcao global inativa nunca reaparece');
    $pdo->prepare('UPDATE personalization_options SET is_active=1 WHERE id=?')->execute([$classic]);
    $textData = ['personalization_type_id'=>$types[1], 'label'=>'Nome', 'base_extra_price'=>'1.00','sort_order'=>20,'options_mode'=>'selected','min_length'=>2,'max_length'=>5,'is_required'=>1,'is_active'=>1];
    $textId = admin_product_personalization_save($products[0],$textData);
    $rules = personalization_rules_for_product($products[0]);
    $textErrors = personalization_validate_values($rules,[$slug=>'classic',$tag.'text'=>'Andre']);
    verify_personalization($textErrors === [], 'Texto dentro dos limites e aceite: ' . implode('; ', $textErrors));
    verify_personalization(personalization_validate_values($rules,[$slug=>'classic',$tag.'text'=>'A']) !== [], 'Minimo de texto validado no servidor');
    verify_personalization(personalization_validate_values($rules,[$slug=>'classic',$tag.'text'=>'Demasiado longo']) !== [], 'Maximo de texto validado no servidor');
    reject_personalization(fn()=>admin_product_personalization_save($products[0],['id'=>$textId,'min_length'=>6]+$textData), 'Rejeita limites incoerentes');
    admin_product_personalization_save($products[0],['id'=>$textId,'is_active'=>0]+$textData);
    verify_personalization(count(personalization_rules_for_product($products[0])) === 1, 'Desativar campo remove-o da loja');
    admin_product_duplicate($products[0]);
    $stmt = $pdo->prepare('SELECT id FROM products WHERE name=? AND id NOT IN (?,?)');
    $stmt->execute([$original['name'].' copia',...$products]);
    $copyId = (int)$stmt->fetchColumn();
    if ($copyId) { $products[]=$copyId; }
    verify_personalization($copyId > 0, 'Duplicacao cria produto');
    verify_personalization(count(admin_product_personalization_rules($copyId)) === 2, 'Duplicacao preserva campos ativos e inativos');
    verify_personalization(personalization_calculate_total(personalization_rules_for_product($copyId),[$slug=>'classic']) === 6.25, 'Duplicacao preserva precos especificos');
    echo "Resultado: $passed verificacoes passaram.\n";
} finally {
    cart_clear();
    foreach ($products as $id) { $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]); }
    foreach ($types as $id) { $pdo->prepare('DELETE FROM personalization_types WHERE id=?')->execute([$id]); }
}
