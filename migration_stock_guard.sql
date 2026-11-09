-- ============================================================================
-- migration_stock_guard.sql
--
-- Run ONCE in phpMyAdmin (SQL tab) BEFORE uploading the new PHP files.
-- Take an export/backup first. Safe to re-run (uses IF NOT EXISTS / IF EXISTS;
-- MariaDB 10.5+ syntax, which is what the server uses).
--
--  1. Stock can never be negative at the DATABASE level (CHECK constraint).
--     The PHP already blocks it, this is the safety net.
--  2. Invoice numbers are per-admin: drops the old GLOBAL unique key on
--     invoices.invoice_no (it stops a second admin from having invoice 001).
--  3. Makes sure the columns/indexes the new PHP expects exist.
-- ============================================================================

-- STEP 0 — look for negative stock first. If this returns rows, fix them
-- (check the real shelf count!) before STEP 2, otherwise STEP 2 will fail.
SELECT id, admin_id, name, batch, stock_qty FROM products WHERE stock_qty < 0;

-- STEP 1 (only if STEP 0 returned rows, and only after you decided that 0 is right):
-- UPDATE products SET stock_qty = 0 WHERE stock_qty < 0;

-- STEP 2 — no more negative stock, ever.
ALTER TABLE products ADD CONSTRAINT IF NOT EXISTS chk_stock_nonneg CHECK (stock_qty >= 0);

-- STEP 3 — invoice numbers unique per admin (not globally).
ALTER TABLE invoices DROP INDEX IF EXISTS invoice_no;
ALTER TABLE invoices ADD UNIQUE KEY IF NOT EXISTS uniq_admin_invoice_no (admin_id, invoice_no);

-- STEP 4 — columns/indexes used by billing.php / reports.php / inventory.php
ALTER TABLE invoices      ADD COLUMN IF NOT EXISTS gst_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00;
ALTER TABLE invoices      ADD COLUMN IF NOT EXISTS created_by  INT(11) DEFAULT NULL;
ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS product_id  INT NULL AFTER invoice_id;
ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS created_at  TIMESTAMP NULL DEFAULT current_timestamp();
ALTER TABLE invoice_items ADD INDEX IF NOT EXISTS idx_product_id (product_id);
ALTER TABLE invoices      ADD INDEX IF NOT EXISTS idx_admin_status (admin_id, status);
ALTER TABLE products      ADD INDEX IF NOT EXISTS idx_admin_id (admin_id);

-- Verify:
--   SHOW CREATE TABLE products;   -- should list CONSTRAINT chk_stock_nonneg
--   SHOW INDEX FROM invoices;     -- no unique key on invoice_no alone
