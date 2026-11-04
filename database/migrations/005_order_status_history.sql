CREATE TABLE IF NOT EXISTS order_status_history (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 old_status VARCHAR(20) NOT NULL,
 new_status VARCHAR(20) NOT NULL,
 changed_by INT UNSIGNED NULL,
 admin_name VARCHAR(100) NOT NULL,
 changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY order_history_order (order_id, id),
 CONSTRAINT order_history_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
 CONSTRAINT order_history_admin_fk FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
