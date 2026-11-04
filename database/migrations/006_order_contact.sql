ALTER TABLE orders
    ADD COLUMN contact_phone VARCHAR(30) NOT NULL DEFAULT '' AFTER subtotal_pkr,
    ADD COLUMN shipping_address VARCHAR(500) NOT NULL DEFAULT '' AFTER contact_phone;
