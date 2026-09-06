<?php
$adminTitle = 'Produtos';
$adminSubtitle = 'CRUD de catalogo, SEO, stock, flags comerciais e CSV.';
require_once __DIR__ . '/../includes/admin_products.php';
admin_require();

$editingProduct = null;

if (request_method() === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Sessao expirada. Tenta novamente.');
        redirect('admin/produtos.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $errors = admin_product_validate($_POST);

        if ($errors !== []) {
            foreach ($errors as $error) {
                flash('danger', $error);
            }
            set_old($_POST);
            redirect('admin/produtos.php' . (!empty($_POST['id']) ? '?edit=' . urlencode((string) $_POST['id']) : ''));
        }

        admin_product_save($_POST);
        clear_old();
        flash('success', 'Produto guardado.');
        redirect('admin/produtos.php');
    }

    if ($action === 'toggle') {
        admin_product_toggle((int) ($_POST['id'] ?? 0));
        flash('success', 'Estado do produto atualizado.');
        redirect('admin/produtos.php');
    }

    if ($action === 'duplicate') {
        admin_product_duplicate((int) ($_POST['id'] ?? 0));
        flash('success', 'Produto duplicado.');
        redirect('admin/produtos.php');
    }

    if ($action === 'delete') {
        admin_product_delete((int) ($_POST['id'] ?? 0));
        flash('success', 'Produto removido ou arquivado.');
        redirect('admin/produtos.php');
    }
}

if (!empty($_GET['edit'])) {
    $editingProduct = admin_product_find((string) $_GET['edit']);
}

$products = admin_products_all();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    admin_products_export_csv($products);
}

$formProduct = array_merge(admin_product_defaults(), $editingProduct ?? [], $_SESSION['_old'] ?? []);
$categories = catalog_categories();

require_once __DIR__ . '/includes/header.php';
?>
<section class="admin-panel mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
        <h3 class="h5 fw-bold mb-0"><?= $editingProduct ? 'Editar produto' : 'Novo produto' ?></h3>
        <a class="btn btn-outline-dark btn-sm align-self-md-start" href="<?= e(url('admin/produtos.php?export=csv')) ?>">Exportar CSV</a>
    </div>
    <form method="post" action="<?= e(url('admin/produtos.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e((string) $formProduct['id']) ?>">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Nome</label>
                <input class="form-control" id="name" name="name" type="text" value="<?= e((string) $formProduct['name']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="slug">URL amigavel</label>
                <input class="form-control" id="slug" name="slug" type="text" value="<?= e((string) $formProduct['slug']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="sku">SKU</label>
                <input class="form-control" id="sku" name="sku" type="text" value="<?= e((string) $formProduct['sku']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="ean">EAN</label>
                <input class="form-control" id="ean" name="ean" type="text" value="<?= e((string) $formProduct['ean']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="category_slug">Categoria</label>
                <select class="form-select" id="category_slug" name="category_slug">
                    <option value="">Sem categoria</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['slug']) ?>" <?= $formProduct['category_slug'] === $category['slug'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="brand_name">Marca</label>
                <input class="form-control" id="brand_name" name="brand_name" type="text" value="<?= e((string) $formProduct['brand_name']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="supplier_name">Fornecedor</label>
                <input class="form-control" id="supplier_name" name="supplier_name" type="text" value="<?= e((string) $formProduct['supplier_name']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="price">Preco</label>
                <input class="form-control" id="price" name="price" type="number" min="0" step="0.01" value="<?= e((string) $formProduct['price']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="sale_price">Preco promocao</label>
                <input class="form-control" id="sale_price" name="sale_price" type="number" min="0" step="0.01" value="<?= e((string) $formProduct['sale_price']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="cost_price">Custo</label>
                <input class="form-control" id="cost_price" name="cost_price" type="number" min="0" step="0.01" value="<?= e((string) $formProduct['cost_price']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="stock">Stock</label>
                <input class="form-control" id="stock" name="stock" type="number" min="0" value="<?= e((string) $formProduct['stock']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="stock_minimum">Stock minimo</label>
                <input class="form-control" id="stock_minimum" name="stock_minimum" type="number" min="0" value="<?= e((string) $formProduct['stock_minimum']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="weight_grams">Peso g</label>
                <input class="form-control" id="weight_grams" name="weight_grams" type="number" min="0" value="<?= e((string) $formProduct['weight_grams']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="width_mm">Largura mm</label>
                <input class="form-control" id="width_mm" name="width_mm" type="number" min="0" value="<?= e((string) $formProduct['width_mm']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="height_mm">Altura mm</label>
                <input class="form-control" id="height_mm" name="height_mm" type="number" min="0" value="<?= e((string) $formProduct['height_mm']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="depth_mm">Profundidade mm</label>
                <input class="form-control" id="depth_mm" name="depth_mm" type="number" min="0" value="<?= e((string) $formProduct['depth_mm']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="short_description">Descricao curta</label>
                <textarea class="form-control" id="short_description" name="short_description" rows="2"><?= e((string) $formProduct['short_description']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="long_description">Descricao longa</label>
                <textarea class="form-control" id="long_description" name="long_description" rows="4"><?= e((string) $formProduct['long_description']) ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="video_url">Video</label>
                <input class="form-control" id="video_url" name="video_url" type="url" value="<?= e((string) $formProduct['video_url']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="meta_title">Meta title</label>
                <input class="form-control" id="meta_title" name="meta_title" type="text" value="<?= e((string) $formProduct['meta_title']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="meta_description">Meta description</label>
                <input class="form-control" id="meta_description" name="meta_description" type="text" value="<?= e((string) $formProduct['meta_description']) ?>">
            </div>
            <div class="col-12">
                <div class="admin-check-grid">
                    <?php foreach (['is_active' => 'Ativo', 'is_new' => 'Novo', 'is_featured' => 'Destaque', 'is_on_sale' => 'Promocao', 'is_best_seller' => 'Mais vendido', 'is_personalizable' => 'Personalizavel'] as $field => $label): ?>
                        <label class="form-check">
                            <input class="form-check-input" name="<?= e($field) ?>" type="checkbox" value="1" <?= !empty($formProduct[$field]) ? 'checked' : '' ?>>
                            <span class="form-check-label"><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4">
            <button class="btn btn-dark" type="submit">Guardar produto</button>
            <?php if ($editingProduct): ?>
                <a class="btn btn-outline-dark" href="<?= e(url('admin/produtos.php')) ?>">Cancelar edicao</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="admin-panel">
    <h3 class="h5 fw-bold mb-3">Produtos</h3>
    <div class="admin-table-wrap">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Produto</th><th>SKU</th><th>Categoria</th><th>Preco</th><th>Stock</th><th>Estado</th><th>Acoes</th></tr></thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><a href="<?= e(url('produto-detalhe.php?slug=' . urlencode($product['slug']))) ?>"><?= e($product['name']) ?></a></td>
                    <td><?= e($product['sku']) ?></td>
                    <td><?= e($product['category_name'] ?? '-') ?></td>
                    <td><?= e(format_price((float) ($product['final_price'] ?? $product['price']))) ?></td>
                    <td><?= e((string) $product['stock']) ?></td>
                    <td><span class="admin-status"><?= !empty($product['is_active']) ? 'Ativo' : 'Inativo' ?></span></td>
                    <td>
                        <div class="admin-actions">
                            <a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/produtos.php?edit=' . urlencode((string) $product['id']))) ?>">Editar</a>
                            <form method="post" action="<?= e(url('admin/produtos.php')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="duplicate">
                                <input type="hidden" name="id" value="<?= e((string) $product['id']) ?>">
                                <button class="btn btn-sm btn-outline-dark" type="submit">Duplicar</button>
                            </form>
                            <form method="post" action="<?= e(url('admin/produtos.php')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= e((string) $product['id']) ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit"><?= !empty($product['is_active']) ? 'Inativar' : 'Ativar' ?></button>
                            </form>
                            <form method="post" action="<?= e(url('admin/produtos.php')) ?>" onsubmit="return confirm('Remover este produto?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e((string) $product['id']) ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Remover</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
