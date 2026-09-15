<?php
declare(strict_types=1);

require_once __DIR__ . '/admin.php';

function admin_product_personalization_rules(int $productId): array
{
    $stmt = db()->prepare('SELECT pp.*, pt.name AS type_name, pt.input_type, pt.is_active AS type_active
        FROM product_personalizations pp JOIN personalization_types pt ON pt.id = pp.personalization_type_id
        WHERE pp.product_id = ? ORDER BY pp.sort_order, pp.id');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function admin_product_personalization_price(mixed $value): string
{
    if (!is_scalar($value) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', trim((string) $value))) {
        throw new InvalidArgumentException('Indica um preco positivo ou zero, com ate duas casas decimais.');
    }
    return number_format((float) $value, 2, '.', '');
}

function admin_product_personalization_save(int $productId, array $data): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Serialize changes for this product and validate ownership before replacing options.
        $lock = $pdo->prepare('SELECT id FROM products WHERE id = ? FOR UPDATE');
        $lock->execute([$productId]);
        if (!$lock->fetchColumn()) {
            throw new InvalidArgumentException('Produto inexistente.');
        }
        $id = (int) ($data['id'] ?? 0);
        $existing = null;
        foreach (admin_product_personalization_rules($productId) as $rule) {
            if ((int) $rule['id'] === $id) {
                $existing = $rule;
            }
        }
        if ($id && !$existing) {
            throw new InvalidArgumentException('Personalizacao invalida para este produto.');
        }
        $typeId = (int) ($existing['personalization_type_id'] ?? $data['personalization_type_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM personalization_types WHERE id = ?');
        $stmt->execute([$typeId]);
        $type = $stmt->fetch();
        if (!$type || (!$existing && !$type['is_active'])) {
            throw new InvalidArgumentException('Seleciona um tipo de personalizacao ativo.');
        }
        $stmt = $pdo->prepare('SELECT id FROM product_personalizations WHERE product_id = ? AND personalization_type_id = ? AND id <> ?');
        $stmt->execute([$productId, $typeId, $id]);
        if ($stmt->fetchColumn()) {
            throw new InvalidArgumentException('Este tipo ja existe no produto. Edita a configuracao existente.');
        }
        $label = trim((string) ($data['label'] ?? ''));
        if ($label === '' || mb_strlen($label) > 160) {
            throw new InvalidArgumentException('O nome do campo deve ter entre 1 e 160 caracteres.');
        }
        $text = in_array($type['input_type'], ['text', 'textarea'], true);
        $bounds = [];
        foreach (['min_length', 'max_length', 'sort_order'] as $field) {
            $value = $data[$field] ?? '';
            if ($value === '' && $field !== 'sort_order') {
                $bounds[$field] = null;
            } elseif (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 10000]]) === false) {
                throw new InvalidArgumentException('Limites e ordem devem ser numeros inteiros entre 0 e 10000.');
            } else {
                $bounds[$field] = (int) $value;
            }
        }
        if ($text && $bounds['max_length'] !== null && ($bounds['min_length'] ?? 0) > $bounds['max_length']) {
            throw new InvalidArgumentException('O limite minimo nao pode exceder o maximo.');
        }
        $mode = $data['options_mode'] ?? 'selected';
        if (!in_array($mode, ['all', 'selected'], true)) {
            throw new InvalidArgumentException('Modo de opcoes invalido.');
        }
        $choices = $data['options'] ?? [];
        $prices = $data['prices'] ?? [];
        if (!is_array($choices) || !is_array($prices)) {
            throw new InvalidArgumentException('Opcoes invalidas.');
        }
        $stmt = $pdo->prepare('SELECT id FROM personalization_options WHERE personalization_type_id = ?');
        $stmt->execute([$typeId]);
        $allowed = array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $selected = [];
        foreach ($choices as $choice) {
            if (!is_scalar($choice) || !in_array((string) $choice, $allowed, true)) {
                throw new InvalidArgumentException('Uma das opcoes nao pertence a este tipo.');
            }
            $raw = $prices[(string) $choice] ?? '';
            $selected[(int) $choice] = $raw === '' ? null : admin_product_personalization_price($raw);
        }
        if ($type['input_type'] === 'file' && !empty($data['is_active'])) {
            $stmt = $pdo->prepare("SELECT pp.id FROM product_personalizations pp JOIN personalization_types pt ON pt.id = pp.personalization_type_id WHERE pp.product_id = ? AND pp.id <> ? AND pp.is_active = 1 AND pt.input_type = 'file'");
            $stmt->execute([$productId, $id]);
            if ($stmt->fetchColumn()) {
                throw new InvalidArgumentException('So pode existir um campo de ficheiro ativo por produto.');
            }
        }
        $params = [$label, $text ? $bounds['min_length'] : null, $text ? $bounds['max_length'] : null,
            admin_product_personalization_price($data['base_extra_price'] ?? '0'), $bounds['sort_order'],
            !empty($data['is_required']) ? 1 : 0, !empty($data['is_active']) ? 1 : 0, $mode];
        if ($existing) {
            $pdo->prepare('UPDATE product_personalizations SET label=?, min_length=?, max_length=?, base_extra_price=?, sort_order=?, is_required=?, is_active=?, options_mode=? WHERE id=? AND product_id=?')
                ->execute([...$params, $id, $productId]);
        } else {
            $pdo->prepare('INSERT INTO product_personalizations (label,min_length,max_length,base_extra_price,sort_order,is_required,is_active,options_mode,product_id,personalization_type_id,allowed_file_types,max_file_bytes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([...$params, $productId, $typeId, $type['input_type'] === 'file' ? 'png,svg,pdf,jpg,jpeg' : null, $type['input_type'] === 'file' ? MAX_UPLOAD_BYTES : null]);
            $id = (int) $pdo->lastInsertId();
        }
        $pdo->prepare('DELETE FROM product_personalization_options WHERE product_personalization_id=?')->execute([$id]);
        if ($mode === 'selected') {
            $insert = $pdo->prepare('INSERT INTO product_personalization_options (product_personalization_id,personalization_option_id,extra_price) VALUES (?,?,?)');
            foreach ($selected as $optionId => $price) {
                $insert->execute([$id, $optionId, $price]);
            }
        }
        $pdo->commit();
        return $id;
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

// Called inside the product duplication transaction.
function admin_product_personalization_copy(int $source, int $target): void
{
    $pdo = db();
    foreach (admin_product_personalization_rules($source) as $rule) {
        $pdo->prepare('INSERT INTO product_personalizations (product_id,personalization_type_id,label,min_length,max_length,allowed_file_types,max_file_bytes,base_extra_price,sort_order,is_required,is_active,options_mode) SELECT ?,personalization_type_id,label,min_length,max_length,allowed_file_types,max_file_bytes,base_extra_price,sort_order,is_required,is_active,options_mode FROM product_personalizations WHERE id=?')->execute([$target, $rule['id']]);
        $newId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO product_personalization_options (product_personalization_id,personalization_option_id,extra_price) SELECT ?,personalization_option_id,extra_price FROM product_personalization_options WHERE product_personalization_id=?')->execute([$newId, $rule['id']]);
    }
}
