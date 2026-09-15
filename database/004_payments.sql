USE ppcosta_store;

CREATE TABLE IF NOT EXISTS payment_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    method_code VARCHAR(80) NOT NULL,
    provider VARCHAR(80) NOT NULL,
    status ENUM('pending','authorized','paid','failed','cancelled','refunded','requires_configuration') NOT NULL DEFAULT 'pending',
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'EUR',
    reference VARCHAR(190) NULL,
    instructions TEXT NULL,
    payload_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_payment_transactions_order (order_id),
    KEY idx_payment_transactions_status (status),
    CONSTRAINT fk_payment_transactions_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CHECK (amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
