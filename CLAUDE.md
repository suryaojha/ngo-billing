# CLAUDE.md — ngo-billing

Plain-PHP (no framework, no Composer, no build step) project that hosts **two separate apps side by side** in one cPanel `public_html` and one MySQL database:

1. **ReliefWave Foundation NGO website** — public site + CMS admin panel.
2. **RUSHIKESH E. H. PHARMA billing / inventory system** — multi-tenant GST invoicing and stock, with superadmin / admin / staff roles.

They share only `config.php` (PDO connection) and the database. They have **separate login systems and separate user tables** — don't confuse them (see "Gotchas").

> The parent folder `SPO final/` also contains an unrelated Node/ESP32 energy-meter app (`server.js`, `models/`, `public/`) and `ai-job-search/`. **Ignore them** — not part of this project. This repo's root is `ngo-billing/`.

## Stack & environment

- PHP 8.x (prod was 8.4) + PDO/MySQL (MariaDB 10.11), `utf8mb4` connection, server-rendered pages with inline CSS/JS (Inter font from Google Fonts).
- Production: cPanel shared hosting, files in `/home/reli446436/public_html/`, DB `reli446436_ReliefWaveDB`.
- Payment: Razorpay (website donations) + UPI QR flow.
- No tests, no linter, no package manager. Verify by `php -l file.php` and by running in a local PHP server (`php -S localhost:8000` from this folder + local MySQL imported from `localhost.sql`).
- Windows dev machine; shell is PowerShell. `git` may not be on PATH in the integrated terminal.

## File map

| File | App | Purpose / who can open |
|---|---|---|
| `config.php` | shared | PDO `$pdo` + `safeQuery()` helper. Logs errors to `error.log`, `display_errors=0`. Holds DB credentials in plaintext. |
| `index.php` | NGO site | Public landing page (redesigned, mobile-first): hero + live stats strip, about/mission/vision + counters, impact band, campaigns (progress, read-more, donate), gallery grid + lightbox, partners marquee, testimonials ("Stories"), footer with real social links, floating WhatsApp/Instagram, mobile sticky Donate bar, event card. One donate sheet: Razorpay or UPI-QR. All content/theme read from DB (`AdminSettings`, `ThemeSettings`, …); section order/visibility still controlled from admin.php. **Donations are post→redirect→get; Razorpay payments are verified server-side against the Razorpay API (amount taken from Razorpay, not the browser); unreachable API ⇒ saved as Pending.** `insertDonation()` only writes columns that exist (works with or without `PaymentMethod`/`TransactionID`). |
| `admin.php` | NGO site | CMS panel (UI refreshed via an appended CSS block at the end of the `<style>`; logic untouched). Login via `Users` table (capital U) by **Email**; sets `$_SESSION['admin']`. Tabs: donations (approve/reject), media/gallery, campaigns, events, partners, testimonials, about, contact, settings (QR/UPI), theme (colors, logo, bg, hero video, section order/visibility). AJAX actions via `$_POST['ajax']` + `action`. "Delete" is a soft delete (`IsActive=0`). |
| `login.php` | Billing | Login via `users` table (lowercase) by **username**; sets `user_id, username, role, full_name, admin_id`. Redirects: superadmin→`superadmin.php`, admin→`inventory.php`, staff→`billing.php`. |
| `logout.php` | Billing | Destroys session → `login.php`. |
| `export.php` + `xlsx.php` | Billing | Excel export/backup (no libraries; real `.xlsx` via ZipArchive, falls back to an Excel-2003 XML `.xls` if the zip extension is missing). `?type=stock` (filters q/status/admin), `?type=invoices` (filters q/d1/d2/admin; Invoices + Invoice Items sheets), `?type=all` = full backup (Stock, all invoices incl. cancelled, all items, + Users sheet for superadmin, never passwords). Admin = own business only; superadmin = all or `?admin=`. Staff cannot export. `admin.php?export=donations|backup` does the same for the NGO website tables. |
| `team.php` | Billing | **Admin-only** "My Team": an admin creates/disables/deletes (blocked if they made invoices)/resets password of their own billing staff (`role='staff'`, `admin_id` = that admin). Superadmin cannot create staff — `superadmin.php` creates **admins only**. |
| `ui.php` | Billing | Shared helpers for the billing pages: responsive top nav (`ui_nav`), base CSS (`ui_css`), invoice sheet CSS (`ui_invoice_css`, desktop A4 + mobile cards + print), `e()` escape, flash messages, `expiry_state()`, `num_to_words()`, `load_company()`. `LOW_STOCK_LIMIT = 10`. |
| `billing.php` | Billing | Invoice builder (A4 print on desktop, card rows + sticky Save bar on mobile). Staff/admin/superadmin. **Superadmin picks an admin ("bill on behalf of"; stored in `$_SESSION['bill_for']`, switch with `?for=ID`)**: stock is deducted from and the invoice filed under that admin, `created_by` = superadmin. AJAX `?action=search`; POST `save_invoice`. |
| `inventory.php` | Billing | Stock page. Admin: view own stock + sales history (read-only). Superadmin: all-admins overview cards, filter by admin (`?admin=ID`), 2-step "Add stock" form (pick admin → live info panel with company/GSTIN/current stock, live duplicate-batch detection, summary bar + confirm before assigning), per-product details modal (value, units sold, last sold), **edit** product (stock/rate/expiry/GST), merge duplicates, delete (POST), "Create bill" shortcut per admin. |
| `reports.php` | Billing | Invoice list (search + date filter, superadmin admin filter), view/print, **cancel** (POST) which restores stock. Scoped to tenant; superadmin sees all. |
| `superadmin.php` | Billing | Superadmin panel: create admin/staff (staff must have an admin), company letterhead, enable/disable, delete user (blocked if the admin still has staff/stock/invoices), global stats incl. stock value, per-admin stock + "Create bill" buttons, all invoices. Own sidebar layout (not `ui.php`). |
| `migration_stock_guard.sql` | Billing | One-time, run before deploying: CHECK `stock_qty >= 0`, per-admin unique invoice numbers (drops global `invoice_no` unique key), ensures needed columns/indexes. |
| `database.sql` | NGO site | Schema + seed for the **NGO tables only** (Dec 2025 dump). |
| `localhost.sql` | both | Full phpMyAdmin dump incl. billing tables (`users`, `products`, `invoices`, `invoice_items`) — the most complete/current schema + real data. |
| `superadmin_setup.sql` | Billing | One-time: adds `role/full_name/admin_id`, `products.admin_id`, `invoices.created_by`, seeds superadmin/admin. |
| `migration_fixes.sql` | Billing | One-time: dedupe products + unique key, `invoice_items.product_id`, `invoices.gst_percent`, per-admin invoice number unique key, company/bank columns on `users`. Back up before running. |
| `uploads/` | both | User-uploaded images (`logo.jpg`, `qr-code.png`, gallery/partner/testimonial/campaign images) and **`bill.png`** (invoice header logo). |
| `error.log`, `error_log` | — | Production PHP error logs copied down (not source code). `billing/` is an empty nested git repo (only `.git`). |

## Billing system — domain model

**Multi-tenant by `admin_id`.** One "admin" = one business (pharmacy). Everything is scoped to it:

- `users` — `role` ∈ `superadmin | admin | staff`. Staff have `admin_id` → their employer admin. Admins hold the company letterhead in their own row (`company_name, company_address, gstin, company_phone, bank_name, bank_account, bank_ifsc, bank_branch`). `IsActive` flag (capital I, historical).
- `products` — stock rows: `name, batch, expiry, capacity, rate, stock_qty, gst_percent, admin_id`. Unique `(admin_id, name, batch)` (after `migration_fixes.sql`). Low-stock threshold in UI is `< 10`.
- `invoices` — `invoice_no` (zero-padded string, e.g. `007`), `customer_*`, `total_amount`, `gst_percent`, `admin_id`, `created_by`, `status` (1 = live, 0 = soft-deleted). Unique `(admin_id, invoice_no)`. (`staff_id` column also exists but is unused.)
- `invoice_items` — `invoice_id` (FK, ON DELETE CASCADE), `product_id` (nullable for legacy rows), `product_name, batch, expiry, capacity, qty, rate, discount, gross_amount`.

**Pagination:** `page_params()` + `pager()` in `ui.php` (10/25/50/100 rows, page links keep all other filters). Invoices (`reports.php`) and donations (`admin.php`) paginate and filter **in SQL**; stock (`inventory.php`) filters/paginates in PHP via `filter_products()` while the stat cards always cover the whole stock. Shared filter builder for invoices: `invoice_where()`.

**Warehouse → admin flow (stock model):** the superadmin owns a central **warehouse** = rows in `products` whose `admin_id` is the superadmin's own user id (no new column). The superadmin *adds* stock only to their own warehouse (`add_product`, the one place a name/batch is typed). Giving stock to an admin is `assign_stock` in `inventory.php`: dropdown of warehouse products ("Name — Batch — Exp — N in stock"), dropdown of admins, quantity (≤ warehouse stock, rate defaults to the warehouse rate) → in one transaction: guarded decrement of the warehouse row, top-up (same name+batch) or create the admin's row, and a row in `stock_transfers` (auto-created; see `migration_warehouse.sql`). Admins never get stock any other way.

**Role rules (agreed with the owner):**
- **superadmin** — sees ALL stock (own warehouse + every admin's) and ALL invoices (with admin / customer / made-by), adds stock to the warehouse, assigns it to admins, edits/deletes stock, creates **admins only**, may bill on behalf of an admin.
- **admin** — sees only their own stock, staff and invoices; bills directly; creates their own staff in `team.php`; stock is view-only (superadmin supplies it).
- **staff** — sees (read-only) and bills from their admin's stock only; sees only invoices they made themselves; stock *value* is hidden; cannot log in if their admin is disabled.

**Tenant resolution:**
`staff → $_SESSION['admin_id']`, `admin → $_SESSION['user_id']`, `superadmin → null (sees all)` for reports/inventory; in `billing.php` the superadmin must choose an admin, so billing always has a concrete `$stock_admin_id`. Queries use `admin_id <=> ?` (null-safe). **Every new billing query must apply this scope.**

**Save-invoice flow (`billing.php`):** in a transaction → next number from `MAX(CAST(invoice_no AS UNSIGNED))` for that admin (cancelled invoices count, so numbers are never reused) → lock the product rows (`FOR UPDATE`), read the real rate/stock from the DB (the browser only sends product id, whole-number qty ≥ 1 and discount) → check total requested per product ≤ stock → **totals are computed on the server** → insert invoice + items → guarded `UPDATE … stock_qty = stock_qty - ? WHERE … AND stock_qty >= ?` (rowCount must be 1, else rollback). Retries up to 5× on duplicate invoice number (SQLSTATE 23000).

**Stock never goes negative:** enforced in `billing.php` (above), `inventory.php` (add/edit reject negative or fractional), the browser (qty capped to stock, counting the same product on other rows) and, after `migration_stock_guard.sql`, by a DB CHECK constraint.

**Cancelling an invoice** (`reports.php`, POST) sets `status=0` and adds the sold qty back to each linked product. Legacy items without `product_id` (or whose product was deleted) can't be restored automatically — the flash message says how many.

`invoice_items.gross_amount` stores `rate × qty` **before** discount; taxable = gross − discount, GST on taxable, grand total = round(taxable + GST).

## NGO website — domain model

Tables (mixed-case, `IsActive` soft-delete everywhere): `AboutInfo, AdminLogs, AdminSettings` (key/value store — theme, logo path, QR, UPI id, stats, `SectionOrder_*`, `ImageOpacity`), `Benefits, Campaigns` (`CurrentAmount` is incremented on donation approval), `ContactInfo, Donations` (`Status`: Pending / Approved / Rejected; Razorpay donations are inserted as Approved, QR ones as Pending with screenshot), `Events, Gallery, MediaGallery, Partners, TeamMembers, Testimonials, ThemeSettings` (single row, `SettingID=1`), `Users` (website admin accounts).

Uploads use `finfo_file` MIME checks and a 5 MB cap, saved to `uploads/` with timestamp names. `admin.php` whitelists table/column names for AJAX delete/toggle — keep that pattern.

## Gotchas (read before editing)

- **Two different user tables:** `Users` (NGO admin, `Email`/`Password`) vs `users` (billing, `username`/`password`). On Linux MySQL table names are case-sensitive; on Windows/XAMPP they are not, so locally they can collide. `localhost.sql` defines both.
- **Collation mismatch:** tables are `latin1_swedish_ci` but the connection is `utf8mb4` → prod logged `Illegal mix of collations … for operation 'like'` on product search. Non-ASCII (₹, Hindi) data in latin1 columns will also be mangled. Fix by converting tables to utf8mb4 or adding `COLLATE` in queries.
- **`error.log` history** (use as bug list): 538× `Undefined array key "Email"` + `htmlspecialchars(null)` at `superadmin.php` lines ~206/253 (last seen 2026-09-10; the local `superadmin.php` has no `Email` reference, so the **deployed copy differs from this repo** — likely a `Users`-vs-`users` mixup in an older upload; compare prod vs local before editing); 3× `Unknown column 'ii.created_at'` in `inventory.php` (column now exists in `localhost.sql`; confirm prod DB has it); a `billing.php` parse error ("expecting elseif/else/endif", line 434) from a bad upload on 2026-03-10 — keep `php -l` clean before uploading.
- Sales history in `inventory.php` matches by `product_id`, falling back to name+batch only for old items without it.
- The invoice logo is hard-coded `uploads/bill.png` for every tenant (billing.php + reports.php).
- **Code in this repo was written without a PHP runtime available — nothing has been executed or linted yet.** Run `php -l` on `ui.php billing.php inventory.php reports.php superadmin.php` and click through all three roles before deploying.
- Dates in `database.sql` header show UTC; `invoices.created_at` is server time.
- Deployment is manual: edit locally → upload files via cPanel/FTP → run SQL by hand in phpMyAdmin. `migration_fixes.sql` says: back up first, run SQL **before** uploading new PHP.

## Known security problems (not yet fixed — ask before changing behavior)

1. **Plaintext passwords** — `login.php` compares `$password === $user['password']`; `superadmin.php` stores raw passwords; `admin.php` accepts plaintext or `password_verify`. Fix: `password_hash()` + `password_verify()` + a one-time rehash-on-login migration.
2. **Credentials committed in repo** — DB user/password in `config.php`; real user passwords and customer data in `localhost.sql`/`database.sql`; setup SQL shows default superadmin/admin passwords. Move DB creds out of the web root / into env, rotate all passwords, and keep dumps out of git and out of `public_html` (these `.sql` and `.log` files are web-downloadable if uploaded!).
3. **No CSRF protection** on any form/AJAX.
4. **State-changing GET links without CSRF or confirmation:** `reports.php?delete=`, `inventory.php?delete=`, `superadmin.php?toggle=` and `?del_user=`.
5. **`inventory.php?delete=`** deletes by raw `$_GET['delete']` with no `(int)` cast (superadmin only, but still).
6. **No session hardening:** no `session_regenerate_id()` on login, no cookie flags, no login rate-limiting/lockout.
7. **Authorization gaps:** `reports.php?delete` for admin is scoped, but inventory `merge_products` / add rely on superadmin role only; `billing.php` search for superadmin spans all tenants. Staff `admin_id` missing ⇒ blocked in billing but not in reports.
8. Public `index.php` donation endpoints accept any POST: the Razorpay handler trusts `razorpay_payment_id` from the client and marks the donation **Approved** with no server-side signature/payment verification — anyone can fake an approved donation and inflate campaign totals.
9. Log files (`error.log`, `error_log`) live in the web root and are publicly fetchable on the server; set `error_log` outside `public_html` or deny via `.htaccess`.

## Conventions when changing code

- Use PDO prepared statements (already the norm); cast ints; whitelist any dynamic table/column names.
- Escape output with `htmlspecialchars` (several places don't, e.g. `<?php echo $company_name; ?>` in `billing.php`; fix when touching).
- Keep each page self-contained (inline CSS/JS) unless refactoring is requested; don't introduce Composer/frameworks without asking.
- Schema changes go in a new dated `.sql` migration file (idempotent where possible) and must also be reflected in `localhost.sql`; tell the user to run the SQL in phpMyAdmin before uploading the PHP.
- Never touch or delete `invoices` / `invoice_items` history rows; use `status=0` soft-delete.
- Don't print or copy real passwords/keys from `config.php` or the SQL dumps into chat, commits, or docs.

## Suggested fix priority (when the user says "solve")

1. Fix the live errors: sync prod vs local `superadmin.php` (`Email` key warnings); confirm `invoice_items.created_at` in prod; collation/utf8mb4 conversion; re-lint `billing.php`.
2. Security: password hashing, move creds + dumps/logs out of web root, CSRF tokens, POST-only deletes, server-side payment verification.
3. Data integrity: per-tenant logo; collation/utf8mb4 conversion. (Stock restore on cancel, server-side totals and `product_id` history are done.)
4. Cleanup: move `superadmin.php` onto `ui.php`; remove the empty `billing/` nested repo.

Owner's note: password hashing/security hardening is intentionally deferred for now ("ignore password type, I use my own") — don't change login/password handling unless asked.
