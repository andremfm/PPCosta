<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/settings.php';

function payment_method_labels(): array
{
    return [
        'mbway' => 'MB Way',
        'multibanco' => 'Multibanco',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
        'card' => 'Cartao',
        'bank_transfer' => 'Transferencia Bancaria',
        'cash_on_delivery' => 'Contra Reembolso',
    ];
}

function payment_active_methods(): array
{
    $methods = store_active_methods('payment_methods', array_map(
        static fn (string $label): array => ['name' => $label, 'price' => 0],
        payment_method_labels()
    ));

    return array_map(static fn (array $method): string => (string) $method['name'], $methods);
}

function payment_create_for_order(int $orderId, array $order): array
{
    $method = (string) ($order['payment_method']['code'] ?? '');
    $amount = (float) ($order['totals']['total'] ?? $order['grand_total'] ?? 0);
    $payment = payment_prepare_payload($method, $amount, (string) ($order['order_number'] ?? ''));

    try {
        $stmt = db()->prepare(
            'INSERT INTO payment_transactions (
                order_id, method_code, provider, status, amount, currency,
                reference, instructions, payload_json
             ) VALUES (
                :order_id, :method_code, :provider, :status, :amount, :currency,
                :reference, :instructions, :payload_json
             )'
        );
        $stmt->execute([
            'order_id' => $orderId,
            'method_code' => $method,
            'provider' => $payment['provider'],
            'status' => $payment['status'],
            'amount' => $amount,
            'currency' => 'EUR',
            'reference' => $payment['reference'],
            'instructions' => $payment['instructions'],
            'payload_json' => json_encode($payment['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $payment['id'] = (int) db()->lastInsertId();
    } catch (Throwable) {
        $payment['id'] = null;
    }

    return $payment;
}

function payment_prepare_payload(string $method, float $amount, string $orderNumber): array
{
    $referenceSeed = strtoupper(substr(hash('sha256', $orderNumber . $method), 0, 9));

    return match ($method) {
        'mbway' => [
            'provider' => 'manual',
            'status' => 'pending',
            'reference' => 'MBW-' . $referenceSeed,
            'instructions' => 'Receberas instrucoes MB Way para o numero ' . store_setting('mbway_phone') . '.',
            'payload' => ['amount' => $amount, 'expires_minutes' => 15],
        ],
        'multibanco' => [
            'provider' => 'manual',
            'status' => 'pending',
            'reference' => 'Entidade 12345 / Ref. ' . substr($referenceSeed, 0, 3) . ' ' . substr($referenceSeed, 3, 3) . ' ' . substr($referenceSeed, 6, 3),
            'instructions' => 'Usa a entidade, referencia e valor indicado para pagar por Multibanco.',
            'payload' => ['entity' => '12345', 'amount' => $amount],
        ],
        'bank_transfer' => [
            'provider' => 'manual',
            'status' => 'pending',
            'reference' => $orderNumber,
            'instructions' => 'Faz transferencia bancaria indicando o numero da encomenda na descricao. IBAN: ' . store_setting('bank_transfer_iban', env_value('BANK_TRANSFER_IBAN', 'PT50 0000 0000 0000 0000 0000 0')),
            'payload' => ['amount' => $amount],
        ],
        'cash_on_delivery' => [
            'provider' => 'manual',
            'status' => 'pending',
            'reference' => $orderNumber,
            'instructions' => 'Pagamento no momento da entrega, sujeito a confirmacao.',
            'payload' => ['amount' => $amount],
        ],
        'paypal', 'stripe', 'card' => [
            'provider' => $method === 'card' ? 'stripe' : $method,
            'status' => 'requires_configuration',
            'reference' => strtoupper($method) . '-' . $referenceSeed,
            'instructions' => 'Gateway preparado para integracao por API. Configura credenciais reais antes de aceitar pagamentos por este metodo.',
            'payload' => ['amount' => $amount, 'gateway' => $method],
        ],
        default => [
            'provider' => 'manual',
            'status' => 'pending',
            'reference' => $orderNumber,
            'instructions' => 'Pagamento pendente de confirmacao.',
            'payload' => ['amount' => $amount],
        ],
    };
}

function payment_for_order(int $orderId): ?array
{
    try {
        $stmt = db()->prepare(
            'SELECT *
             FROM payment_transactions
             WHERE order_id = :order_id
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute(['order_id' => $orderId]);
        $payment = $stmt->fetch();

        return $payment ?: null;
    } catch (Throwable) {
        return null;
    }
}

function payment_status_label(string $status): string
{
    return [
        'pending' => 'Pendente',
        'authorized' => 'Autorizado',
        'paid' => 'Pago',
        'failed' => 'Falhado',
        'cancelled' => 'Cancelado',
        'refunded' => 'Reembolsado',
        'requires_configuration' => 'Requer configuracao',
    ][$status] ?? $status;
}
