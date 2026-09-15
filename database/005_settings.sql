USE ppcosta_store;

INSERT INTO settings (setting_key, setting_value, value_type, is_public) VALUES
('store_phone', '+351 910 000 000', 'string', 1),
('store_address', 'Morada da loja, Portugal', 'string', 1),
('store_vat_number', 'PT000000000', 'string', 1),
('default_tax_rate', '23', 'number', 0),
('invoice_prefix', 'FT', 'string', 0),
('bank_transfer_iban', 'PT50 0000 0000 0000 0000 0000 0', 'string', 0),
('mbway_phone', '+351 910 000 000', 'string', 0),
('payment_instructions', 'A encomenda avanca para producao apos confirmacao do pagamento.', 'string', 1),
('mail_from_email', 'geral@example.com', 'string', 0),
('mail_from_name', 'PPCosta', 'string', 0),
('mail_transport', 'log', 'string', 0),
('legal_terms', 'Termos e condicoes em preparacao.', 'string', 1),
('privacy_policy', 'Politica de privacidade em preparacao.', 'string', 1),
('returns_policy', 'Trocas e devolucoes analisadas caso a caso em produtos personalizados.', 'string', 1)
ON DUPLICATE KEY UPDATE
    value_type = VALUES(value_type),
    is_public = VALUES(is_public);

UPDATE shipping_methods
SET config_json = JSON_OBJECT(
    'price',
    CASE code
        WHEN 'ctt' THEN 4.90
        WHEN 'dpd' THEN 5.90
        WHEN 'mrw' THEN 5.90
        WHEN 'gls' THEN 5.90
        WHEN 'dhl' THEN 7.90
        WHEN 'ups' THEN 7.90
        ELSE 0.00
    END
)
WHERE code IN ('ctt', 'dpd', 'mrw', 'gls', 'dhl', 'ups', 'pickup', 'free_shipping');
