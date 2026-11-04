ALTER TABLE products
    ADD COLUMN price_pkr DECIMAL(12, 2) NOT NULL DEFAULT 0.00 AFTER unit,
    ADD CONSTRAINT products_price_check CHECK (price_pkr >= 0);
