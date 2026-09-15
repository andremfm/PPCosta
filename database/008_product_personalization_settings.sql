-- Apply once to existing installations, after 006 and 007.
ALTER TABLE product_personalizations
    ADD COLUMN options_mode ENUM('legacy','all','selected') NOT NULL DEFAULT 'legacy';
ALTER TABLE product_personalization_options
    ADD COLUMN extra_price DECIMAL(12,2) NULL DEFAULT NULL,
    ADD CONSTRAINT chk_product_option_price CHECK (extra_price IS NULL OR extra_price >= 0);
