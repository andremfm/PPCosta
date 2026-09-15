<?php
declare(strict_types=1);

require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/settings.php';

function admin_settings_groups(): array
{
    return [
        'Dados da loja' => [
            'store_name' => ['label' => 'Nome da loja', 'type' => 'text'],
            'store_email' => ['label' => 'Email geral', 'type' => 'email'],
            'store_phone' => ['label' => 'Telefone', 'type' => 'text'],
            'store_address' => ['label' => 'Morada', 'type' => 'textarea'],
            'store_vat_number' => ['label' => 'NIF/NIPC', 'type' => 'text'],
        ],
        'Fiscal e encomendas' => [
            'currency' => ['label' => 'Moeda', 'type' => 'text'],
            'default_tax_rate' => ['label' => 'IVA por defeito (%)', 'type' => 'number'],
            'invoice_prefix' => ['label' => 'Prefixo de fatura', 'type' => 'text'],
            'free_shipping_threshold' => ['label' => 'Portes gratis a partir de', 'type' => 'number'],
        ],
        'Pagamentos e emails' => [
            'bank_transfer_iban' => ['label' => 'IBAN transferencia bancaria', 'type' => 'text'],
            'mbway_phone' => ['label' => 'Telefone MB Way', 'type' => 'text'],
            'payment_instructions' => ['label' => 'Instrucao geral de pagamento', 'type' => 'textarea'],
            'mail_from_email' => ['label' => 'Email remetente', 'type' => 'email'],
            'mail_from_name' => ['label' => 'Nome remetente', 'type' => 'text'],
            'mail_transport' => ['label' => 'Envio de email', 'type' => 'select', 'options' => ['log' => 'Registar em ficheiro', 'mail' => 'Enviar por mail()']],
        ],
        'Textos legais' => [
            'legal_terms' => ['label' => 'Termos e condicoes', 'type' => 'textarea'],
            'privacy_policy' => ['label' => 'Politica de privacidade', 'type' => 'textarea'],
            'returns_policy' => ['label' => 'Politica de trocas/devolucoes', 'type' => 'textarea'],
        ],
    ];
}

function admin_settings_save(array $data): void
{
    $definitions = store_setting_defaults();
    $stmt = db()->prepare(
        'INSERT INTO settings (setting_key, setting_value, value_type, is_public)
         VALUES (:setting_key, :setting_value, :value_type, :is_public)
         ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            value_type = VALUES(value_type),
            is_public = VALUES(is_public)'
    );

    foreach ($definitions as $key => $definition) {
        if (!array_key_exists($key, $data)) {
            continue;
        }

        $stmt->execute([
            'setting_key' => $key,
            'setting_value' => trim((string) $data[$key]),
            'value_type' => $definition['type'],
            'is_public' => (int) $definition['public'],
        ]);
    }
}

function admin_config_methods(string $table): array
{
    if (!in_array($table, ['payment_methods', 'shipping_methods'], true)) {
        return [];
    }

    try {
        return db()->query(
            "SELECT id, name, code, sort_order, is_active
             FROM {$table}
             ORDER BY sort_order ASC, name ASC"
        )->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function admin_config_toggle_method(string $table, int $id): void
{
    if (!in_array($table, ['payment_methods', 'shipping_methods'], true) || $id <= 0) {
        return;
    }

    $stmt = db()->prepare("UPDATE {$table} SET is_active = 1 - is_active WHERE id = :id");
    $stmt->execute(['id' => $id]);
}
