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
    try {

    if ($action === 'save') {
        $errors = admin_product_validate($_POST);

        if ($errors !== []) {
            foreach ($errors as $error) {
                flash('danger', $error);
            }
            set_old($_POST);
            redirect('admin/produtos.php' . (!empty($_POST['id']) ? '?edit=' . urlencode((string) $_POST['id']) : ''));
        }

        if (!admin_product_save($_POST)) {
            set_old($_POST);
            flash('danger', 'Nao foi possivel guardar. Confirma se o SKU e o endereco do produto sao unicos.');
            redirect('admin/produtos.php' . (!empty($_POST['id']) ? '?edit=' . (int) $_POST['id'] : ''));
        }
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

    if ($action === 'image_upload') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $ok = admin_product_save_image($productId, $_FILES['image'] ?? [], (string) ($_POST['alt_text'] ?? ''), (int) ($_POST['sort_order'] ?? 0), !empty($_POST['is_primary']));
        flash($ok ? 'success' : 'danger', $ok ? 'Imagem adicionada.' : 'Nao foi possivel adicionar a imagem.');
        redirect('admin/produtos.php?edit=' . urlencode((string) $productId));
    }

    if ($action === 'image_primary') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        admin_product_set_primary_image($productId, (int) ($_POST['image_id'] ?? 0));
        flash('success', 'Imagem principal atualizada.');
        redirect('admin/produtos.php?edit=' . urlencode((string) $productId));
    }

    if ($action === 'image_delete') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        admin_product_delete_image($productId, (int) ($_POST['image_id'] ?? 0));
        flash('success', 'Imagem removida.');
        redirect('admin/produtos.php?edit=' . urlencode((string) $productId));
    }

    if ($action === 'variation_save') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        admin_product_save_variation($productId, $_POST);
        flash('success', 'Variacao guardada.');
        redirect('admin/produtos.php?edit=' . urlencode((string) $productId));
    }

    if ($action === 'variation_delete') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        admin_product_delete_variation($productId, (int) ($_POST['variation_id'] ?? 0));
        flash('success', 'Variacao arquivada.');
        redirect('admin/produtos.php?edit=' . urlencode((string) $productId));
    }
    } catch (Throwable $exception) {
        error_log('Product media/variation failed: ' . $exception->getMessage());
        flash('danger', 'Nao foi possivel guardar a alteracao. Confirma os dados e se o SKU ja existe.');
        redirect('admin/produtos.php?edit=' . (int) ($_POST['product_id'] ?? 0));
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
$techniqueOptions = admin_product_technique_options();
$selectedTechniques = is_array($formProduct['techniques'] ?? null) && $formProduct['techniques'] !== []
    ? $formProduct['techniques']
    : admin_product_selected_techniques((int) ($formProduct['id'] ?? 0));
$productImages = $editingProduct ? admin_product_images((int) $formProduct['id']) : [];
$productVariations = $editingProduct ? admin_product_variations((int) $formProduct['id']) : [];
$editingVariation = $editingProduct && !empty($_GET['variation']) ? admin_product_variation_find((int) $formProduct['id'], (int) $_GET['variation']) : null;

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
            <div class="col-12">
                <div class="admin-subpanel">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                        <div>
                            <h4 class="h6 fw-bold mb-1">Tecnicas disponiveis neste produto</h4>
                            <p class="text-secondary small mb-0">Define o que o cliente pode escolher na personalizacao. Ex.: canecas apenas Sublimacao.</p>
                        </div>
                    </div>
                    <?php if (!empty($formProduct['id'])): ?>
                        <a class="btn btn-outline-dark" href="<?= e(url('admin/produto-personalizacao.php?product_id=' . (int) $formProduct['id'])) ?>">Configurar personalizacoes</a>
                    <?php else: ?>
                    <div class="admin-check-grid">
                        <?php foreach ($techniqueOptions as $option): ?>
                            <label class="form-check">
                                <input class="form-check-input" name="techniques[]" type="checkbox" value="<?= e((string) $option['value']) ?>" <?= in_array((string) $option['value'], $selectedTechniques, true) ? 'checked' : '' ?>>
                                <span class="form-check-label">
                                    <?= e((string) $option['label']) ?>
                                    <?php if ((float) $option['extra_price'] > 0): ?>
                                        <small class="d-block text-secondary">+<?= e(format_price((float) $option['extra_price'])) ?></small>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($techniqueOptions === []): ?>
                        <p class="text-secondary mb-0">Ainda nao existem tecnicas configuradas.</p>
                    <?php endif; ?>
                    <?php endif; ?>
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

<?php if ($editingProduct): ?>
    <section class="admin-grid-2 mb-4">
        <div class="admin-panel">
            <h3 class="h5 fw-bold mb-3">Galeria de imagens</h3>
            <form class="mb-4" method="post" action="<?= e(url('admin/produtos.php')) ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="image_upload">
                <input type="hidden" name="product_id" value="<?= e((string) $formProduct['id']) ?>">
                <label class="form-label" for="image">Imagem</label>
                <input class="form-control mb-2" id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,.gif" required>
                <div class="row g-2">
                    <div class="col-md-8"><input class="form-control" name="alt_text" type="text" placeholder="Texto alternativo"></div>
                    <div class="col-md-4"><input class="form-control" name="sort_order" type="number" value="0" placeholder="Ordem"></div>
                </div>
                <label class="form-check mt-2"><input class="form-check-input" name="is_primary" type="checkbox" value="1"><span class="form-check-label">Imagem principal</span></label>
                <button class="btn btn-dark btn-sm mt-3" type="submit">Adicionar imagem</button>
            </form>
            <div class="admin-list">
                <?php foreach ($productImages as $image): ?>
                    <article>
                        <div>
                            <strong><?= e((string) basename((string) $image['path'])) ?></strong>
                            <span class="d-block"><?= !empty($image['is_primary']) ? 'Principal' : 'Galeria' ?> · ordem <?= e((string) $image['sort_order']) ?></span>
                        </div>
                        <div class="admin-actions">
                            <form method="post" action="<?= e(url('admin/produtos.php')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="image_primary">
                                <input type="hidden" name="product_id" value="<?= e((string) $formProduct['id']) ?>">
                                <input type="hidden" name="image_id" value="<?= e((string) $image['id']) ?>">
                                <button class="btn btn-sm btn-outline-dark" type="submit">Principal</button>
                            </form>
                            <form method="post" action="<?= e(url('admin/produtos.php')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="image_delete">
                                <input type="hidden" name="product_id" value="<?= e((string) $formProduct['id']) ?>">
                                <input type="hidden" name="image_id" value="<?= e((string) $image['id']) ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Remover</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if ($productImages === []): ?><p class="text-secondary mb-0">Sem imagens.</p><?php endif; ?>
            </div>
        </div>

        <div class="admin-panel" id="variacoes">
            <h3 class="h5 fw-bold mb-3"><?= $editingVariation ? 'Editar variacao' : 'Variacoes' ?></h3>
            <form class="mb-4" method="post" action="<?= e(url('admin/produtos.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="variation_save">
                <input type="hidden" name="variation_id" value="<?= (int) ($editingVariation['id'] ?? 0) ?>">
                <input type="hidden" name="product_id" value="<?= e((string) $formProduct['id']) ?>">
                <div class="row g-2">
                    <?php foreach (['sku' => 'SKU', 'ean' => 'EAN', 'size' => 'Tamanho', 'color' => 'Cor', 'material' => 'Material', 'price_delta' => 'Acrescimo de preco', 'stock' => 'Stock', 'weight_grams' => 'Peso (g)'] as $field => $label): ?>
                        <?php $numeric = in_array($field, ['price_delta', 'stock', 'weight_grams'], true); ?>
                        <div class="col-md-6">
                            <label class="form-label" for="variation-<?= e($field) ?>"><?= e($label) ?></label>
                            <input class="form-control" id="variation-<?= e($field) ?>" name="<?= e($field) ?>" type="<?= $numeric ? 'number' : 'text' ?>" value="<?= e((string) ($editingVariation[$field] ?? ($numeric ? 0 : ''))) ?>" <?= $field === 'sku' ? 'required' : '' ?> <?= $field === 'price_delta' ? 'step="0.01"' : ($numeric ? 'min="0" step="1"' : '') ?>>
                        </div>
                    <?php endforeach; ?>
                </div>
                <label class="form-check mt-2"><input class="form-check-input" name="is_active" type="checkbox" value="1" <?= !$editingVariation || !empty($editingVariation['is_active']) ? 'checked' : '' ?>><span class="form-check-label">Ativa</span></label>
                <button class="btn btn-dark btn-sm mt-3" type="submit"><?= $editingVariation ? 'Guardar variacao' : 'Adicionar variacao' ?></button>
                <?php if ($editingVariation): ?><a class="btn btn-outline-dark btn-sm mt-3" href="<?= e(url('admin/produtos.php?edit=' . (int) $formProduct['id'] . '#variacoes')) ?>">Cancelar</a><?php endif; ?>
            </form>
            <div class="admin-list">
                <?php foreach ($productVariations as $variation): ?>
                    <article>
                        <div>
                            <strong><?= e((string) $variation['sku']) ?></strong>
                            <small><?= !empty($variation['is_active']) ? 'Ativa' : 'Arquivada' ?></small>
                            <span class="d-block"><?= e((string) ($variation['attributes_label'] ?? 'Sem atributos')) ?> · <?= e(format_price((float) $variation['price_delta'])) ?> · stock <?= e((string) $variation['stock']) ?></span>
                        </div>
                        <a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/produtos.php?edit=' . (int) $formProduct['id'] . '&variation=' . (int) $variation['id'] . '#variacoes')) ?>">Editar</a>
                        <form method="post" action="<?= e(url('admin/produtos.php')) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="variation_delete">
                            <input type="hidden" name="product_id" value="<?= e((string) $formProduct['id']) ?>">
                            <input type="hidden" name="variation_id" value="<?= e((string) $variation['id']) ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit" <?= empty($variation['is_active']) ? 'disabled' : '' ?>>Arquivar</button>
                        </form>
                    </article>
                <?php endforeach; ?>
                <?php if ($productVariations === []): ?><p class="text-secondary mb-0">Sem variacoes.</p><?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="admin-panel">
    <h3 class="h5 fw-bold mb-3">Produtos</h3>
    <div class="admin-table-wrap">
        <table class="table admin-table align-middle mb-0">
            <thead><tr><th>Produto</th><th>SKU</th><th>Categoria</th><th>Preco</th><th>Stock</th><th>Estado</th><th>Acoes</th></tr></thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><a href="<?= e(product_url($product['slug'])) ?>"><?= e($product['name']) ?></a></td>
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
                                <button class="btn btn-sm <?= !empty($product['is_active']) ? 'btn-outline-warning' : 'btn-outline-success' ?>" type="submit"><?= !empty($product['is_active']) ? 'Inativar' : 'Ativar' ?></button>
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
