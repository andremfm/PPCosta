<?php
declare(strict_types=1);

require_once __DIR__ . '/catalog.php';

function personalization_fallback_rules(): array
{
    return [
        [
            'slug' => 'nome',
            'label' => 'Nome',
            'input_type' => 'text',
            'is_required' => 0,
            'base_extra_price' => 2.50,
            'min_length' => 1,
            'max_length' => 28,
            'options' => [],
        ],
        [
            'slug' => 'texto',
            'label' => 'Texto adicional',
            'input_type' => 'textarea',
            'is_required' => 0,
            'base_extra_price' => 3.00,
            'min_length' => 0,
            'max_length' => 90,
            'options' => [],
        ],
        [
            'slug' => 'fonte',
            'label' => 'Fonte',
            'input_type' => 'font',
            'is_required' => 0,
            'base_extra_price' => 0.00,
            'options' => [
                ['label' => 'Classica', 'value' => 'classic', 'extra_price' => 0.00],
                ['label' => 'Script', 'value' => 'script', 'extra_price' => 1.00],
                ['label' => 'Moderna', 'value' => 'modern', 'extra_price' => 0.50],
            ],
        ],
        [
            'slug' => 'cor',
            'label' => 'Cor',
            'input_type' => 'color',
            'is_required' => 0,
            'base_extra_price' => 0.00,
            'options' => [
                ['label' => 'Preto', 'value' => '#111827', 'extra_price' => 0.00],
                ['label' => 'Branco', 'value' => '#ffffff', 'extra_price' => 0.00],
                ['label' => 'Dourado', 'value' => '#b45309', 'extra_price' => 1.50],
                ['label' => 'Rosa', 'value' => '#be185d', 'extra_price' => 0.50],
            ],
        ],
        [
            'slug' => 'tamanho',
            'label' => 'Tamanho da personalizacao',
            'input_type' => 'select',
            'is_required' => 0,
            'base_extra_price' => 0.00,
            'options' => [
                ['label' => 'Pequeno', 'value' => 'small', 'extra_price' => 0.00],
                ['label' => 'Medio', 'value' => 'medium', 'extra_price' => 1.50],
                ['label' => 'Grande', 'value' => 'large', 'extra_price' => 3.00],
            ],
        ],
        [
            'slug' => 'posicao',
            'label' => 'Posicao',
            'input_type' => 'position',
            'is_required' => 0,
            'base_extra_price' => 0.00,
            'options' => [
                ['label' => 'Frente', 'value' => 'front', 'extra_price' => 0.00],
                ['label' => 'Costas', 'value' => 'back', 'extra_price' => 2.00],
                ['label' => 'Manga', 'value' => 'sleeve', 'extra_price' => 1.50],
                ['label' => 'Canto inferior', 'value' => 'corner', 'extra_price' => 0.00],
            ],
        ],
        [
            'slug' => 'tecnica',
            'label' => 'Tecnica',
            'input_type' => 'technique',
            'is_required' => 1,
            'base_extra_price' => 0.00,
            'options' => [
                ['label' => 'Bordado', 'value' => 'embroidery', 'extra_price' => 4.00],
                ['label' => 'DTF', 'value' => 'dtf', 'extra_price' => 3.00],
                ['label' => 'Vinil', 'value' => 'vinyl', 'extra_price' => 2.50],
                ['label' => 'Sublimacao', 'value' => 'sublimation', 'extra_price' => 3.50],
                ['label' => 'Laser', 'value' => 'laser', 'extra_price' => 4.50],
                ['label' => 'UV', 'value' => 'uv', 'extra_price' => 4.00],
            ],
        ],
        [
            'slug' => 'ficheiro',
            'label' => 'Imagem, logotipo ou ficheiro',
            'input_type' => 'file',
            'is_required' => 0,
            'base_extra_price' => 2.00,
            'allowed_file_types' => 'png,svg,pdf,jpg,jpeg',
            'max_file_bytes' => MAX_UPLOAD_BYTES,
            'options' => [],
        ],
    ];
}

function personalization_rules_for_product(int $productId): array
{
    try {
        $stmt = db()->prepare(
            'SELECT
                pp.id,
                pt.slug,
                pp.label,
                pt.input_type,
                pp.is_required,
                pp.base_extra_price,
                pp.min_length,
                pp.max_length,
                pp.allowed_file_types,
                pp.max_file_bytes
             FROM product_personalizations pp
             INNER JOIN personalization_types pt ON pt.id = pp.personalization_type_id
             WHERE pp.product_id = :product_id
               AND pp.is_active = 1
               AND pt.is_active = 1
             ORDER BY pp.sort_order ASC, pp.id ASC'
        );
        $stmt->execute(['product_id' => $productId]);
        $rules = $stmt->fetchAll();

        if ($rules === []) {
            return personalization_fallback_rules();
        }

        foreach ($rules as &$rule) {
            $rule['options'] = personalization_options_for_type((string) $rule['slug']);
        }

        return $rules;
    } catch (Throwable) {
        return personalization_fallback_rules();
    }
}

function personalization_options_for_type(string $typeSlug): array
{
    try {
        $stmt = db()->prepare(
            'SELECT po.label, po.value, po.extra_price, po.color_hex
             FROM personalization_options po
             INNER JOIN personalization_types pt ON pt.id = po.personalization_type_id
             WHERE pt.slug = :slug
               AND po.is_active = 1
             ORDER BY po.sort_order ASC, po.id ASC'
        );
        $stmt->execute(['slug' => $typeSlug]);

        return $stmt->fetchAll();
    } catch (Throwable) {
        foreach (personalization_fallback_rules() as $rule) {
            if ($rule['slug'] === $typeSlug) {
                return $rule['options'];
            }
        }

        return [];
    }
}

function personalization_calculate_total(array $rules, array $values): float
{
    $total = 0.0;

    foreach ($rules as $rule) {
        $slug = (string) $rule['slug'];
        $value = $values[$slug] ?? null;

        if ($value === null || $value === '' || $value === []) {
            continue;
        }

        $total += (float) ($rule['base_extra_price'] ?? 0);

        foreach ($rule['options'] ?? [] as $option) {
            if ((string) $option['value'] === (string) $value) {
                $total += (float) ($option['extra_price'] ?? 0);
                break;
            }
        }
    }

    return $total;
}

function personalization_allowed_mime_types(): array
{
    return [
        'image/png',
        'image/svg+xml',
        'application/pdf',
        'image/jpeg',
    ];
}
