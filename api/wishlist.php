<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/customer.php';
require_auth();
if (request_method() !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    exit('Sessao expirada. Atualiza a pagina.');
}
$productId = (int) ($_POST['product_id'] ?? 0);
$product = catalog_product_by_id($productId);
try {
    $added = customer_toggle_wishlist((int) $_SESSION['user_id'], $productId);
    flash('success', $added ? 'Produto adicionado aos favoritos.' : 'Produto removido dos favoritos.');
} catch (Throwable $exception) {
    error_log('Wishlist failed: ' . $exception->getMessage());
    flash('danger', 'Nao foi possivel atualizar os favoritos.');
}
redirect(($_POST['return_to'] ?? '') === 'perfil' || !$product ? 'perfil.php#wishlist' : 'produto/' . rawurlencode($product['slug']));
