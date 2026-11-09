-- =============================================
-- SUPERADMIN ROLE SYSTEM SETUP
-- Run this in phpMyAdmin on your database
-- =============================================

-- 1. Add columns to existing users table
ALTER TABLE `users` 
    ADD COLUMN IF NOT EXISTS `role` ENUM('superadmin','admin','staff') NOT NULL DEFAULT 'staff',
    ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `admin_id` INT(11) DEFAULT NULL COMMENT 'For staff: which admin they belong to';

-- 2. Insert superadmin (login: superadmin / Super@123)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `admin_id`) 
VALUES ('superadmin', 'Super@123', 'Super Admin', 'superadmin', NULL)
ON DUPLICATE KEY UPDATE role='superadmin', full_name='Super Admin';

-- 3. Insert sample admin (login: admin / Admin@123)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `admin_id`) 
VALUES ('admin', 'Admin@123', 'Admin One', 'admin', NULL)
ON DUPLICATE KEY UPDATE role='admin', full_name='Admin One';

-- 4. Add admin_id to products table
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `admin_id` INT(11) DEFAULT NULL;

-- 5. Assign existing products to the first admin
UPDATE `products` SET `admin_id` = (
    SELECT id FROM users WHERE role='admin' ORDER BY id LIMIT 1
) WHERE admin_id IS NULL;

-- 6. Add created_by to invoices table
ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `created_by` INT(11) DEFAULT NULL;

-- Done! Login credentials:
-- superadmin / Super@123  → superadmin.php
-- admin / Admin@123       → inventory.php (view only)
-- staff (create via superadmin panel) → billing.php
