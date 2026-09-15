<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function store_setting_defaults(): array
{
    return [
        'store_name' => ['value' => 'PPCosta', 'type' => 'string', 'public' => 1],
        'store_email' => ['value' => 'geral@example.com', 'type' => 'string', 'public' => 1],
        'store_phone' => ['value' => '+351 910 000 000', 'type' => 'string', 'public' => 1],
        'store_address' => ['value' => 'Morada da loja, Portugal', 'type' => 'string', 'public' => 1],
        'store_vat_number' => ['value' => 'PT000000000', 'type' => 'string', 'public' => 1],
        'currency' => ['value' => 'EUR', 'type' => 'string', 'public' => 1],
        'default_tax_rate' => ['value' => '23', 'type' => 'number', 'public' => 0],
        'invoice_prefix' => ['value' => 'FT', 'type' => 'string', 'public' => 0],
        'free_shipping_threshold' => ['value' => '75', 'type' => 'number', 'public' => 1],
        'bank_transfer_iban' => ['value' => 'PT50 0000 0000 0000 0000 0000 0', 'type' => 'string', 'public' => 0],
        'mbway_phone' => ['value' => '+351 910 000 000', 'type' => 'string', 'public' => 0],
        'payment_instructions' => ['value' => 'A encomenda avanca para producao apos confirmacao do pagamento.', 'type' => 'string', 'public' => 1],
        'mail_from_email' => ['value' => 'geral@example.com', 'type' => 'string', 'public' => 0],
        'mail_from_name' => ['value' => 'PPCosta', 'type' => 'string', 'public' => 0],
        'mail_transport' => ['value' => 'log', 'type' => 'string', 'public' => 0],
        'legal_terms' => ['value' => 'Termos e condicoes em preparacao.', 'type' => 'string', 'public' => 1],
        'privacy_policy' => ['value' => 'Politica de privacidade em preparacao.', 'type' => 'string', 'public' => 1],
        'returns_policy' => ['value' => 'Trocas e devolucoes analisadas caso a caso em produtos personalizados.', 'type' => 'string', 'public' => 1],
        'maintenance_mode' => ['value' => '0', 'type' => 'boolean', 'public' => 0],
    ];
}

function store_settings_all(): array
{
    $settings = [];

    foreach (store_setting_defaults() as $key => $definition) {
        $settings[$key] = (string) $definition['value'];
    }

    try {
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();

        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
    } catch (Throwable) {
        return $settings;
    }

    return $settings;
}

function store_setting(string $key, ?string $fallback = null): string
{
    $defaults = store_setting_defaults();
    $fallback ??= isset($defaults[$key]) ? (string) $defaults[$key]['value'] : '';

    try {
        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = :setting_key LIMIT 1');
        $stmt->execute(['setting_key' => $key]);
        $value = $stmt->fetchColumn();

        return $value === false ? $fallback : (string) $value;
    } catch (Throwable) {
        return $fallback;
    }
}

function store_active_methods(string $table, array $fallback): array
{
    if (!in_array($table, ['payment_methods', 'shipping_methods'], true)) {
        return $fallback;
    }

    try {
        $rows = db()->query(
            "SELECT id, name, code, config_json, sort_order, is_active
             FROM {$table}
             WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC"
        )->fetchAll();
    } catch (Throwable) {
        return $fallback;
    }

    if ($rows === []) {
        return $fallback;
    }

    $methods = [];

    foreach ($rows as $row) {
        $code = (string) $row['code'];
        $config = json_decode((string) ($row['config_json'] ?? ''), true);
        $methods[$code] = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'price' => isset($config['price']) ? (float) $config['price'] : (float) ($fallback[$code]['price'] ?? 0),
        ];
    }

    return $methods;
}
