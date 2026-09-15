<?php
declare(strict_types=1);

require_once __DIR__ . '/admin.php';

function admin_personalization_slugify(string $value): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;
    $value = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
    $value = trim($value, '-');

    return $value !== '' ? $value : 'personalizacao';
}

function admin_personalization_types(): array
{
    try {
        return db()->query('SELECT * FROM personalization_types ORDER BY id ASC')->fetchAll();
    } catch (Throwable) {
        return personalization_fallback_rules();
    }
}

function admin_personalization_options(): array
{
    try {
        return db()->query(
            'SELECT po.*, pt.name AS type_name, pt.slug AS type_slug
             FROM personalization_options po
             INNER JOIN personalization_types pt ON pt.id = po.personalization_type_id
             ORDER BY pt.id ASC, po.sort_order ASC, po.id ASC'
        )->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function admin_personalization_save_type(array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    $name = trim((string) ($data['name'] ?? ''));
    $slug = admin_personalization_slugify(trim((string) ($data['slug'] ?? $name)));
    $inputType = (string) ($data['input_type'] ?? 'text');
    $allowedTypes = ['text', 'textarea', 'select', 'color', 'font', 'file', 'position', 'technique'];

    if ($name === '' || !in_array($inputType, $allowedTypes, true)) {
        return;
    }

    if ($id > 0) {
        $stmt = db()->prepare(
            'UPDATE personalization_types
             SET name = :name, slug = :slug, input_type = :input_type, is_required = :is_required, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'input_type' => $inputType,
            'is_required' => !empty($data['is_required']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'id' => $id,
        ]);
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO personalization_types (name, slug, input_type, is_required, is_active)
         VALUES (:name, :slug, :input_type, :is_required, :is_active)'
    );
    $stmt->execute([
        'name' => $name,
        'slug' => $slug,
        'input_type' => $inputType,
        'is_required' => !empty($data['is_required']) ? 1 : 0,
        'is_active' => !empty($data['is_active']) ? 1 : 0,
    ]);
}

function admin_personalization_save_option(array $data): void
{
    $id = (int) ($data['id'] ?? 0);
    $typeId = (int) ($data['personalization_type_id'] ?? 0);
    $label = trim((string) ($data['label'] ?? ''));
    $value = trim((string) ($data['value'] ?? ''));

    if ($typeId <= 0 || $label === '' || $value === '') {
        return;
    }

    $params = [
        'personalization_type_id' => $typeId,
        'label' => $label,
        'value' => $value,
        'extra_price' => max(0, (float) ($data['extra_price'] ?? 0)),
        'color_hex' => trim((string) ($data['color_hex'] ?? '')) ?: null,
        'sort_order' => (int) ($data['sort_order'] ?? 0),
        'is_active' => !empty($data['is_active']) ? 1 : 0,
    ];

    if ($id > 0) {
        $stmt = db()->prepare(
            'UPDATE personalization_options
             SET personalization_type_id = :personalization_type_id, label = :label, value = :value,
                 extra_price = :extra_price, color_hex = :color_hex, sort_order = :sort_order, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute($params + ['id' => $id]);
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO personalization_options (personalization_type_id, label, value, extra_price, color_hex, sort_order, is_active)
         VALUES (:personalization_type_id, :label, :value, :extra_price, :color_hex, :sort_order, :is_active)'
    );
    $stmt->execute($params);
}

function admin_personalization_toggle(string $table, int $id): void
{
    if (!in_array($table, ['personalization_types', 'personalization_options'], true) || $id <= 0) {
        return;
    }

    db()->prepare("UPDATE {$table} SET is_active = 1 - is_active WHERE id = :id")->execute(['id' => $id]);
}
