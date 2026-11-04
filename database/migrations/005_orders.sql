CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    subtotal_pkr DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY orders_user (user_id, id),
    CONSTRAINT orders_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT orders_subtotal_check CHECK (subtotal_pkr >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_code VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    unit ENUM('meter', 'yard') NOT NULL,
    unit_price_pkr DECIMAL(12, 2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total_pkr DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY order_items_order (order_id, id),
    KEY order_items_product (product_id),
    CONSTRAINT order_items_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT order_items_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT order_items_quantity_check CHECK (quantity > 0),
    CONSTRAINT order_items_price_check CHECK (unit_price_pkr >= 0),
    CONSTRAINT order_items_total_check CHECK (line_total_pkr >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
