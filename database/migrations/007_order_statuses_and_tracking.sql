ALTER TABLE orders
    MODIFY COLUMN status ENUM('pending', 'confirmed', 'processing', 'dispatched', 'delivered', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    ADD COLUMN order_number VARCHAR(50) NOT NULL DEFAULT '' AFTER id,
    ADD COLUMN tracking_number VARCHAR(100) NOT NULL DEFAULT '' AFTER shipping_address,
    ADD COLUMN admin_notes TEXT NULL AFTER tracking_number,
    ADD KEY orders_order_number (order_number);

UPDATE orders
SET order_number = CONCAT('BNT-', LPAD(id, 5, '0'))
WHERE order_number = '';
