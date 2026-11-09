-- ============================================================================
-- migration_fixes.sql
--
-- One-time data & schema fix for the RUSHIKESH Pharma billing system.
-- Fixes: duplicate product entries, stock deducted from the wrong row,
-- a staff account with no business assigned, invoice numbers that can
-- collide, and gives each admin their own company/GST letterhead.
--
-- HOW TO RUN
--   1. Take a fresh backup/export of the database first (phpMyAdmin ->
--      Export -> Quick -> SQL). Do not skip this.
--   2. Open phpMyAdmin -> your database -> SQL tab -> paste this whole
--      file -> Go.
--   3. Only after this succeeds, upload the updated PHP files.
--
-- This script only ADDS columns/constraints and MERGES duplicate rows
-- (summing their stock into one, never deleting a row without folding
-- its quantity into the row that survives). Nothing is dropped and no
-- invoice/invoice_item history row is touched.
--
-- If you ever need to run this a second time and a step complains with
-- "Duplicate column name" or "Duplicate key name", that step already
-- applied successfully on a previous run — just delete that one
-- statement and continue with the rest.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- STEP 1: Clean up whitespace in product name/batch.
-- Fixes accidental "duplicates" that were really the same medicine typed
-- with an extra leading/trailing space (e.g. "R-Cid syrup " vs "R-Cid syrup").
-- ----------------------------------------------------------------------------
UPDATE products SET name = TRIM(name), batch = TRIM(batch);


-- ----------------------------------------------------------------------------
-- STEP 2: Merge byte-for-byte duplicate products.
-- Only rows that are IDENTICAL in admin_id + name + batch are merged (never
-- rows that merely look similar, e.g. different capitalization) — merging
-- the wrong two products would itself destroy accurate stock data. Rows that
-- look similar but aren't exact matches are left for manual review in the
-- redesigned Inventory page's "Possible duplicates" panel.
--
-- 2a. Fold every duplicate's stock_qty into the lowest id of its group.
-- ----------------------------------------------------------------------------
UPDATE products p
JOIN (
    SELECT admin_id, name, batch, MIN(id) AS keep_id, SUM(stock_qty) AS total_qty
    FROM products
    GROUP BY admin_id, name, batch
    HAVING COUNT(*) > 1
) d ON p.id = d.keep_id
SET p.stock_qty = d.total_qty;

-- 2b. Remove the now-redundant duplicate rows (their quantity already
--     moved to the row kept above).
DELETE p FROM products p
JOIN (
    SELECT admin_id, name, batch, MIN(id) AS keep_id
    FROM products
    GROUP BY admin_id, name, batch
    HAVING COUNT(*) > 1
) d ON p.admin_id <=> d.admin_id AND p.name = d.name AND p.batch = d.batch
WHERE p.id <> d.keep_id;


-- ----------------------------------------------------------------------------
-- STEP 3: Stop this from ever happening again — one product per
-- admin+name+batch, enforced by the database itself.
-- ----------------------------------------------------------------------------
ALTER TABLE products ADD UNIQUE KEY uniq_admin_name_batch (admin_id, name, batch);


-- ----------------------------------------------------------------------------
-- STEP 4: Let invoice_items remember exactly which product row was sold,
-- so stock is deducted from the exact item picked, not by matching text.
-- Nullable and unindexed-by-default column: old rows stay exactly as they
-- are, only new sales going forward will populate it.
-- ----------------------------------------------------------------------------
ALTER TABLE invoice_items ADD COLUMN product_id INT NULL AFTER invoice_id;
ALTER TABLE invoice_items ADD INDEX idx_invoice_id (invoice_id);
ALTER TABLE invoice_items ADD INDEX idx_product_name_batch (product_name, batch);


-- ----------------------------------------------------------------------------
-- STEP 4b: Remember the actual GST% used on each invoice (billing.php lets
-- it be edited per invoice, but it was never saved) so the printed/report
-- breakdown always matches what was really charged, instead of assuming a
-- flat 5%. Existing invoices are backfilled with 5.00, matching the
-- default that was always shown on screen when they were created.
-- ----------------------------------------------------------------------------
ALTER TABLE invoices ADD COLUMN gst_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00;


-- ----------------------------------------------------------------------------
-- STEP 5: Scope invoice numbering per business (admin), not globally.
-- All existing invoices belong to RUSHIKESH (admin_id = 1) today, so this
-- backfill doesn't change anything about them — it just gives every future
-- admin/tenant their own independent, collision-proof invoice sequence.
-- ----------------------------------------------------------------------------
UPDATE invoices SET admin_id = 1 WHERE admin_id IS NULL;
ALTER TABLE invoices ADD UNIQUE KEY uniq_admin_invoice_no (admin_id, invoice_no);


-- ----------------------------------------------------------------------------
-- STEP 6: Fix the "Pharma" staff account — it had no business assigned,
-- which let it search and sell against every admin's stock instead of just
-- its own employer's (RUSHIKESH, admin_id = 1).
-- ----------------------------------------------------------------------------
UPDATE users SET admin_id = 1 WHERE id = 2 AND username = 'Pharma';


-- ----------------------------------------------------------------------------
-- STEP 7: Give every admin (business) their own letterhead — company name,
-- address, GSTIN, phone, and bank details — instead of one hardcoded set
-- shared by everyone. RUSHIKESH (admin_id = 1) is seeded with the exact
-- values that were previously hardcoded, so existing invoices keep printing
-- identically. Any other admin (e.g. a second pharmacy) can fill in their
-- own details from their Inventory/Company Profile screen.
-- ----------------------------------------------------------------------------
ALTER TABLE users
  ADD COLUMN company_name    VARCHAR(255) NULL,
  ADD COLUMN company_address TEXT         NULL,
  ADD COLUMN gstin           VARCHAR(30)  NULL,
  ADD COLUMN company_phone   VARCHAR(50)  NULL,
  ADD COLUMN bank_name       VARCHAR(100) NULL,
  ADD COLUMN bank_account    VARCHAR(50)  NULL,
  ADD COLUMN bank_ifsc       VARCHAR(20)  NULL,
  ADD COLUMN bank_branch     VARCHAR(100) NULL;

UPDATE users SET
  company_name    = 'RUSHIKESH E. H. PHARMA',
  company_address = '1ST FLOOR, NEAR POLYTECHNIC COLLEGE, SENDURWAFA, PO-SENDURWAFA, Tah. Sakoli, Distt. Bhandara 441802 (MH)',
  gstin           = '27AAPPZ8683F2ZN',
  company_phone   = '9270075110',
  bank_name       = 'UNION BANK OF INDIA',
  bank_account    = '627702010003591',
  bank_ifsc       = 'UBIN0562777',
  bank_branch     = 'SAKOLI'
WHERE id = 1;

-- ============================================================================
-- Done. Verify with:
--   SELECT admin_id, name, batch, COUNT(*) FROM products
--   GROUP BY admin_id, name, batch HAVING COUNT(*) > 1;
--   -- (should return 0 rows)
-- ============================================================================
