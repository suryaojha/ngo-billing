-- ============================================================================
-- migration_warehouse.sql   (OPTIONAL — inventory.php creates this table itself on first visit)
--
-- Log of stock the superadmin assigns from their own warehouse to admins.
-- Run in phpMyAdmin only if you prefer to create it by hand. Safe to re-run.
-- ============================================================================
CREATE TABLE IF NOT EXISTS stock_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    batch VARCHAR(50) DEFAULT NULL,
    expiry VARCHAR(50) DEFAULT NULL,
    qty INT NOT NULL,
    rate DECIMAL(10,2) DEFAULT NULL,
    to_admin INT NOT NULL,
    source_product_id INT DEFAULT NULL,
    target_product_id INT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT current_timestamp(),
    KEY idx_to_admin (to_admin),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- The superadmin's warehouse needs NO new table or column: it is simply the rows in `products`
-- whose admin_id is the superadmin's own user id.
