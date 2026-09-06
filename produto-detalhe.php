<?php
require_once __DIR__ . '/includes/personalization.php';

$slug = trim($_GET['slug'] ?? '');
$product = $slug !== '' ? catalog_product_by_slug($slug) : null;

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Produto nao encontrado | PPCosta';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <section class="section-pad">
        <div class="container">
            <div class="empty-state">
                <h1 class="h3 fw-bold">Produto nao encontrado</h1>
                <p class="text-secondary">O produto pode estar indisponivel ou ter sido removido.</p>
                <a class="btn btn-dark" href="<?= e(url('produto.php')) ?>">Voltar ao catalogo</a>
            </div>
        </div>
    </section>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <?php
    exit;
}

$pageTitle = $product['name'] . ' | PPCosta';
$pageDescription = $product['short_description'] ?? 'Produto personalizado PPCosta.';
$personalizationRules = !empty($product['is_personalizable'])
    ? personalization_rules_for_product((int) $product['id'])
    : [];
require_once __DIR__ . '/includes/header.php';
?>
<section class="product-detail section-pad">
    <div class="container">
        <nav class="small mb-4" aria-label="Breadcrumb">
            <a class="text-secondary" href="<?= e(url()) ?>">Inicio</a>
            <span class="text-secondary mx-2">/</span>
            <a class="text-secondary" href="<?= e(url('produto.php')) ?>">Produtos</a>
            <span class="text-secondary mx-2">/</span>
            <span><?= e($product['name']) ?></span>
        </nav>
        <div class="row g-5">
            <div class="col-lg-6">
                <div class="product-gallery-main <?= e($product['media_class'] ?? 'product-mug') ?>" data-personalization-preview>
                    <span data-preview-product><?= e($product['category_name'] ?? 'Produto') ?></span>
                    <strong data-preview-text></strong>
                    <small data-preview-meta></small>
                </div>
                <div class="product-gallery-thumbs mt-3">
                    <button type="button" aria-label="Imagem principal"></button>
                    <button type="button" aria-label="Detalhe do produto"></button>
                    <button type="button" aria-label="Personalizacao"></button>
                </div>
            </div>
            <div class="col-lg-6">
                <p class="text-uppercase text-secondary fw-semibold small mb-2"><?= e($product['category_name'] ?? 'Catalogo') ?></p>
                <h1 class="section-title mb-3"><?= e($product['name']) ?></h1>
                <p class="text-secondary lead"><?= e($product['short_description'] ?? '') ?></p>
                <div class="d-flex align-items-center gap-2 mb-4">
                    <span class="price fs-3"><?= e(format_price((float) $product['final_price'])) ?></span>
                    <?php if (!empty($product['sale_price'])): ?>
                        <span class="old-price"><?= e(format_price((float) $product['price'])) ?></span>
                    <?php endif; ?>
                </div>
                <div class="product-meta mb-4">
                    <span>SKU: <?= e($product['sku']) ?></span>
                    <span>Stock: <?= e((string) $product['stock']) ?></span>
                    <span>IVA: <?= e((string) ($product['tax_rate'] ?? '23.00')) ?>%</span>
                </div>
                <?php if (!empty($product['is_personalizable'])): ?>
                    <div class="personalization-preview mb-4">
                        <h2 class="h5 fw-bold">Personalizacao disponivel</h2>
                        <p class="text-secondary mb-3">Este produto permite texto, cor, fonte, tecnica, posicao e ficheiros de apoio.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge badge-soft rounded-pill">Nome</span>
                            <span class="badge badge-soft rounded-pill">Texto</span>
                            <span class="badge badge-soft rounded-pill">Logotipo</span>
                            <span class="badge badge-soft rounded-pill"><?= e($product['technique'] ?? 'Tecnica') ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                <form class="product-buy-box" method="post" action="<?= e(url('carrinho.php')) ?>" enctype="multipart/form-data" data-personalization-form data-product-price="<?= e((string) $product['final_price']) ?>" data-product-id="<?= e((string) $product['id']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                    <input type="hidden" name="uploaded_personalization_file" value="" data-uploaded-personalization-file>
                    <?php if ($personalizationRules !== []): ?>
                        <div class="personalization-editor mb-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h2 class="h5 fw-bold mb-1">Configurar personalizacao</h2>
                                    <p class="text-secondary small mb-0">O preco adicional e atualizado automaticamente.</p>
                                </div>
                                <strong data-personalization-extra><?= e(format_price(0)) ?></strong>
                            </div>
                            <div class="row g-3">
                                <?php foreach ($personalizationRules as $rule): ?>
                                    <?php $fieldName = 'personalization[' . $rule['slug'] . ']'; ?>
                                    <div class="col-md-6">
                                        <label class="form-label" for="personalization-<?= e($rule['slug']) ?>">
                                            <?= e($rule['label']) ?>
                                            <?php if (!empty($rule['is_required'])): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                        </label>
                                        <?php if (in_array($rule['input_type'], ['select', 'font', 'position', 'technique'], true)): ?>
                                            <select class="form-select" id="personalization-<?= e($rule['slug']) ?>" name="<?= e($fieldName) ?>" data-personalization-field data-preview-field="<?= e($rule['slug']) ?>" <?= !empty($rule['is_required']) ? 'required' : '' ?>>
                                                <option value="">Escolher</option>
                                                <?php foreach ($rule['options'] as $option): ?>
                                                    <option value="<?= e($option['value']) ?>" data-extra-price="<?= e((string) $option['extra_price']) ?>">
                                                        <?= e($option['label']) ?>
                                                        <?php if ((float) $option['extra_price'] > 0): ?>
                                                            +<?= e(format_price((float) $option['extra_price'])) ?>
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php elseif ($rule['input_type'] === 'color'): ?>
                                            <div class="color-choice-group" data-personalization-field-group>
                                                <?php foreach ($rule['options'] as $index => $option): ?>
                                                    <label class="color-choice" title="<?= e($option['label']) ?>">
                                                        <input type="radio" name="<?= e($fieldName) ?>" value="<?= e($option['value']) ?>" data-personalization-field data-preview-field="<?= e($rule['slug']) ?>" data-extra-price="<?= e((string) $option['extra_price']) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                                        <span style="background: <?= e($option['value']) ?>"></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php elseif ($rule['input_type'] === 'textarea'): ?>
                                            <textarea class="form-control" id="personalization-<?= e($rule['slug']) ?>" name="<?= e($fieldName) ?>" rows="3" maxlength="<?= e((string) ($rule['max_length'] ?? 120)) ?>" data-personalization-field data-preview-field="<?= e($rule['slug']) ?>" data-base-extra-price="<?= e((string) $rule['base_extra_price']) ?>"></textarea>
                                        <?php elseif ($rule['input_type'] === 'file'): ?>
                                            <input class="form-control" id="personalization-<?= e($rule['slug']) ?>" name="personalization_file" type="file" accept=".png,.svg,.pdf,.jpg,.jpeg" data-personalization-file data-base-extra-price="<?= e((string) $rule['base_extra_price']) ?>">
                                            <div class="form-text">PNG, SVG, PDF ou JPG ate <?= e((string) round(((int) ($rule['max_file_bytes'] ?? MAX_UPLOAD_BYTES)) / 1024 / 1024)) ?> MB.</div>
                                            <div class="form-text" data-upload-message></div>
                                        <?php else: ?>
                                            <input class="form-control" id="personalization-<?= e($rule['slug']) ?>" name="<?= e($fieldName) ?>" type="text" maxlength="<?= e((string) ($rule['max_length'] ?? 80)) ?>" data-personalization-field data-preview-field="<?= e($rule['slug']) ?>" data-base-extra-price="<?= e((string) $rule['base_extra_price']) ?>" <?= !empty($rule['is_required']) ? 'required' : '' ?>>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex flex-column flex-sm-row gap-2 mb-4">
                        <input class="form-control quantity-input" name="quantity" type="number" min="1" value="1" aria-label="Quantidade">
                        <button class="btn btn-dark px-4" type="submit">Adicionar ao carrinho</button>
                        <button class="btn btn-outline-dark" type="button">Wishlist</button>
                    </div>
                    <div class="total-preview mb-4">
                        <span>Total estimado</span>
                        <strong data-personalization-total><?= e(format_price((float) $product['final_price'])) ?></strong>
                    </div>
                </form>
                <div class="product-description">
                    <h2 class="h5 fw-bold">Descricao</h2>
                    <p class="text-secondary mb-0"><?= e($product['long_description'] ?? $product['short_description'] ?? '') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
