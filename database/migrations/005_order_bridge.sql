-- The admin schema stores subtotal. The older 006 migration expects the
-- checkout name subtotal_pkr; a generated alias preserves both without copying
-- or overwriting existing amounts. The checkout snapshot also needs its unit.
SET @bunat_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'subtotal_pkr'),
    'DO 0',
    'ALTER TABLE orders ADD COLUMN subtotal_pkr DECIMAL(12,2) GENERATED ALWAYS AS (subtotal) STORED'
);
PREPARE bunat_stmt FROM @bunat_ddl;
EXECUTE bunat_stmt;
DEALLOCATE PREPARE bunat_stmt;
SET @bunat_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'unit'),
    'DO 0',
    'ALTER TABLE order_items ADD COLUMN unit ENUM(''meter'',''yard'') NULL'
);
PREPARE bunat_stmt FROM @bunat_ddl;
EXECUTE bunat_stmt;
DEALLOCATE PREPARE bunat_stmt;
