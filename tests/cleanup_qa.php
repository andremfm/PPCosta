<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/database.php';

if (!db_available()) {
    fwrite(STDERR, "Base de dados indisponivel.\n");
    exit(1);
}

$pdo = db();
$pdo->beginTransaction();

try {
    $emails = $pdo->query(
        'SELECT email
         FROM users
         WHERE email LIKE "qa.customer+%@ppcosta.local"
            OR email = "qa.admin@ppcosta.local"'
    )->fetchAll(PDO::FETCH_COLUMN);

    if ($emails !== []) {
        $placeholders = implode(',', array_fill(0, count($emails), '?'));
        $orders = $pdo->prepare('SELECT id FROM orders WHERE customer_email IN (' . $placeholders . ')');
        $orders->execute($emails);
        $orderIds = array_map('intval', $orders->fetchAll(PDO::FETCH_COLUMN));

        if ($orderIds !== []) {
            $orderPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));
            $pdo->prepare('DELETE FROM payment_transactions WHERE order_id IN (' . $orderPlaceholders . ')')->execute($orderIds);
            $pdo->prepare('DELETE FROM orders WHERE id IN (' . $orderPlaceholders . ')')->execute($orderIds);
        }

        $pdo->prepare('DELETE FROM newsletter_subscribers WHERE email IN (' . $placeholders . ')')->execute($emails);
        $pdo->prepare('DELETE FROM users WHERE email IN (' . $placeholders . ')')->execute($emails);
    }

    $pdo->commit();
    echo 'Dados QA removidos: ' . count($emails) . " utilizador(es).\n";
} catch (Throwable $exception) {
    $pdo->rollBack();
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
