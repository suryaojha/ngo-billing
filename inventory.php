<?php
session_start();
require 'config.php';
require 'ui.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin', 'staff'], true)) {
    header("Location: login.php");
    exit;
}

$role     = $_SESSION['role'];
$is_super = ($role === 'superadmin');
$is_staff = ($role === 'staff');
// superadmin: every admin's stock · admin: own stock · staff: their admin's stock (read-only)
$admin_id = $is_super ? null : ($is_staff ? (int)($_SESSION['admin_id'] ?? 0) : (int)$_SESSION['user_id']);
if (!$is_super && !$admin_id) {
    die('Your account is not linked to a business yet. Please ask your admin to fix your account.');
}
$biz_name = '';
if (!$is_super) {
    $bq = $pdo->prepare("SELECT COALESCE(NULLIF(company_name,''), full_name) FROM users WHERE id=?");
    $bq->execute([$admin_id]);
    $biz_name = (string)$bq->fetchColumn();
}

// Active admins (businesses) — used by the superadmin forms/filters.
$admins = [];
if ($is_super) {
    $admins = $pdo->query("SELECT id, username, full_name, company_name, gstin, company_phone FROM users WHERE role='admin' AND IsActive=1 ORDER BY full_name")->fetchAll();
}
$admin_ids = array_map('intval', array_column($admins, 'id'));
$admin_names = [];
foreach ($admins as $a) { $admin_names[(int)$a['id']] = $a['full_name'] . ($a['company_name'] ? ' (' . $a['company_name'] . ')' : ''); }

// The superadmin owns a central WAREHOUSE: products whose admin_id is the superadmin's own user id.
// Stock is added there first, then assigned (transferred) from it to admins.
$my_id = $is_super ? (int)$_SESSION['user_id'] : 0;
if ($is_super) {
    $admin_names[$my_id] = 'My warehouse';
    try {   // transfer log; created automatically on first visit, harmless if it already exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS stock_transfers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_name VARCHAR(255) NOT NULL, batch VARCHAR(50) DEFAULT NULL, expiry VARCHAR(50) DEFAULT NULL,
            qty INT NOT NULL, rate DECIMAL(10,2) DEFAULT NULL,
            to_admin INT NOT NULL, source_product_id INT DEFAULT NULL, target_product_id INT DEFAULT NULL,
            created_by INT DEFAULT NULL, created_at TIMESTAMP NULL DEFAULT current_timestamp(),
            KEY idx_to_admin (to_admin), KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
    } catch (Exception $e) { error_log('stock_transfers: ' . $e->getMessage()); }
}

/* ------------------------------------------------------------------
 * Superadmin actions (all POST). Stock is validated so it can never be negative.
 * ------------------------------------------------------------------ */
if ($is_super && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $back = 'inventory.php' . (!empty($_POST['back_admin']) ? '?admin=' . (int)$_POST['back_admin'] : '');

    // ADD STOCK TO MY WAREHOUSE (or top-up if the same product+batch is already in it).
    // This is the only place a medicine name / batch is typed. Giving stock to an admin is done by assign_stock below.
    if (isset($_POST['add_product'])) {
        $target = $my_id;
        $name   = trim((string)($_POST['name'] ?? ''));
        $batch  = trim((string)($_POST['batch'] ?? ''));
        $qty    = $_POST['stock_qty'] ?? '';
        $rate   = $_POST['rate'] ?? '';
        $gst    = $_POST['gst_percent'] ?? 5;

        if ($name === '' || $batch === '')                           flash_set('error', 'Medicine name and batch are required.');
        elseif (!is_numeric($qty) || (int)$qty != $qty || $qty < 0)  flash_set('error', 'Stock quantity must be a whole number, 0 or more.');
        elseif (!is_numeric($rate) || $rate < 0)                     flash_set('error', 'Rate cannot be negative.');
        elseif (!is_numeric($gst) || $gst < 0 || $gst > 100)         flash_set('error', 'GST % must be between 0 and 100.');
        else {
            $qty = (int)$qty;
            $ex = $pdo->prepare("SELECT id FROM products WHERE admin_id=? AND name=? AND batch=?");
            $ex->execute([$target, $name, $batch]);
            if ($row = $ex->fetch()) {
                $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ?, expiry=?, capacity=?, rate=?, gst_percent=? WHERE id=?")
                    ->execute([$qty, $_POST['expiry'] ?? '', $_POST['capacity'] ?? '', $rate, $gst, $row['id']]);
                flash_set('success', "Added $qty units to \"$name\" (batch $batch) in your warehouse.");
            } else {
                $pdo->prepare("INSERT INTO products (name,batch,expiry,capacity,rate,stock_qty,gst_percent,admin_id) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$name, $batch, $_POST['expiry'] ?? '', $_POST['capacity'] ?? '', $rate, $qty, $gst, $target]);
                flash_set('success', "\"$name\" (batch $batch) — $qty units added to your warehouse. You can now assign it to an admin.");
            }
        }
        // success -> show the warehouse list; a mistake -> stay on the Add form
        header('Location: ' . ((($_SESSION['flash']['type'] ?? '') === 'success') ? "inventory.php?admin=$my_id" : 'inventory.php#add')); exit;
    }

    // ASSIGN STOCK: move units from my warehouse to an admin. Everything is picked from dropdowns;
    // the quantity can never exceed what the warehouse has, so stock can't go negative.
    if (isset($_POST['assign_stock'])) {
        $srcId  = (int)($_POST['source_id'] ?? 0);
        $toAdm  = (int)($_POST['to_admin'] ?? 0);
        $qty    = $_POST['qty'] ?? '';
        $rate   = $_POST['rate'] ?? '';
        $err = '';
        if (!in_array($toAdm, $admin_ids, true))                      $err = 'Choose the admin to assign the stock to.';
        elseif ($srcId <= 0)                                          $err = 'Choose the medicine (and batch) from your warehouse.';
        elseif (!is_numeric($qty) || (int)$qty != $qty || $qty < 1)   $err = 'Quantity must be a whole number of at least 1.';
        elseif ($rate !== '' && (!is_numeric($rate) || $rate < 0))     $err = 'Rate cannot be negative.';
        if ($err === '') {
            $qty = (int)$qty;
            try {
                $pdo->beginTransaction();
                $s = $pdo->prepare("SELECT * FROM products WHERE id=? AND admin_id=? FOR UPDATE");
                $s->execute([$srcId, $my_id]);
                $src = $s->fetch();
                if (!$src) throw new Exception('That product is not in your warehouse.');
                if ($qty > (int)$src['stock_qty']) throw new Exception("Only {$src['stock_qty']} unit(s) of \"{$src['name']}\" (batch {$src['batch']}) are in your warehouse — you tried to assign $qty.");
                $newRate = ($rate === '') ? $src['rate'] : $rate;

                // 1) take it out of the warehouse (guarded: never below zero)
                $d = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id=? AND stock_qty >= ?");
                $d->execute([$qty, $srcId, $qty]);
                if ($d->rowCount() !== 1) throw new Exception('Not enough stock in the warehouse.');

                // 2) give it to the admin: same name + batch tops up their row, otherwise a new row is created
                $t = $pdo->prepare("SELECT id FROM products WHERE admin_id=? AND name=? AND batch=? FOR UPDATE");
                $t->execute([$toAdm, $src['name'], $src['batch']]);
                if ($trow = $t->fetch()) {
                    $targetId = (int)$trow['id'];
                    $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ?, rate=?, expiry=?, capacity=?, gst_percent=? WHERE id=?")
                        ->execute([$qty, $newRate, $src['expiry'], $src['capacity'], $src['gst_percent'], $targetId]);
                } else {
                    $pdo->prepare("INSERT INTO products (name,batch,expiry,capacity,rate,stock_qty,gst_percent,admin_id) VALUES (?,?,?,?,?,?,?,?)")
                        ->execute([$src['name'], $src['batch'], $src['expiry'], $src['capacity'], $newRate, $qty, $src['gst_percent'], $toAdm]);
                    $targetId = (int)$pdo->lastInsertId();
                }

                // 3) log it
                try {
                    $pdo->prepare("INSERT INTO stock_transfers (product_name,batch,expiry,qty,rate,to_admin,source_product_id,target_product_id,created_by) VALUES (?,?,?,?,?,?,?,?,?)")
                        ->execute([$src['name'], $src['batch'], $src['expiry'], $qty, $newRate, $toAdm, $srcId, $targetId, $my_id]);
                } catch (Exception $e) { error_log('transfer log: ' . $e->getMessage()); }

                $pdo->commit();
                flash_set('success', "Assigned $qty × \"{$src['name']}\" (batch {$src['batch']}) to {$admin_names[$toAdm]}. Warehouse now has " . ((int)$src['stock_qty'] - $qty) . " left.");
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash_set('error', $e->getMessage());
            }
        } else {
            flash_set('error', $err);
        }
        header('Location: ' . ((($_SESSION['flash']['type'] ?? '') === 'success') ? "inventory.php?admin=$toAdm" : 'inventory.php#give')); exit;
    }

    // EDIT PRODUCT (rate / expiry / capacity / GST / stock)
    if (isset($_POST['edit_product'])) {
        $pid  = (int)($_POST['id'] ?? 0);
        $qty  = $_POST['stock_qty'] ?? '';
        $rate = $_POST['rate'] ?? '';
        $gst  = $_POST['gst_percent'] ?? 5;
        if ($pid <= 0)                                               flash_set('error', 'Product not found.');
        elseif (!is_numeric($qty) || (int)$qty != $qty || $qty < 0)  flash_set('error', 'Stock cannot be negative and must be a whole number.');
        elseif (!is_numeric($rate) || $rate < 0)                     flash_set('error', 'Rate cannot be negative.');
        elseif (!is_numeric($gst) || $gst < 0 || $gst > 100)         flash_set('error', 'GST % must be between 0 and 100.');
        else {
            $pdo->prepare("UPDATE products SET rate=?, expiry=?, capacity=?, gst_percent=?, stock_qty=? WHERE id=?")
                ->execute([$rate, trim((string)($_POST['expiry'] ?? '')), trim((string)($_POST['capacity'] ?? '')), $gst, (int)$qty, $pid]);
            flash_set('success', 'Product updated.');
        }
        header("Location: $back"); exit;
    }

    // MERGE two duplicate products (stock of one is folded into the other)
    if (isset($_POST['merge_products'])) {
        $keepId  = (int)($_POST['keep_id'] ?? 0);
        $mergeId = (int)($_POST['merge_id'] ?? 0);
        if ($keepId && $mergeId && $keepId !== $mergeId) {
            $pdo->beginTransaction();
            try {
                $k = $pdo->prepare("SELECT * FROM products WHERE id=? FOR UPDATE"); $k->execute([$keepId]);  $keep  = $k->fetch();
                $m = $pdo->prepare("SELECT * FROM products WHERE id=? FOR UPDATE"); $m->execute([$mergeId]); $merge = $m->fetch();
                if ($keep && $merge && $keep['admin_id'] == $merge['admin_id']) {
                    $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id=?")->execute([max(0, (int)$merge['stock_qty']), $keepId]);
                    // keep sales history pointing at the surviving product
                    $pdo->prepare("UPDATE invoice_items SET product_id=? WHERE product_id=?")->execute([$keepId, $mergeId]);
                    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$mergeId]);
                    flash_set('success', 'Stock merged into the existing product.');
                } else {
                    flash_set('error', 'Those two products cannot be merged.');
                }
                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash_set('error', 'Merge failed.');
            }
        }
        header("Location: $back"); exit;
    }

    // DELETE PRODUCT
    if (isset($_POST['delete_product'])) {
        $pid = (int)($_POST['id'] ?? 0);
        if ($pid > 0) {
            $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$pid]);
            flash_set('success', 'Product deleted.');
        }
        header("Location: $back"); exit;
    }
}

/* ------------------------------------------------------------------
 * Data for the page
 * ------------------------------------------------------------------ */
$filter_admin = 0;   // 0 = everything · $my_id = my warehouse · else one admin
if ($is_super && isset($_GET['admin']) && in_array((int)$_GET['admin'], array_merge($admin_ids, [$my_id]), true)) {
    $filter_admin = (int)$_GET['admin'];
}
$in_warehouse = $is_super && $filter_admin === $my_id;

$filter_name = '';
foreach ($admins as $a) { if ((int)$a['id'] === $filter_admin) $filter_name = $a['full_name']; }
if ($in_warehouse) $filter_name = 'My warehouse';

if ($is_super) {
    if ($filter_admin) {
        $st = $pdo->prepare("SELECT p.*, u.full_name AS admin_name FROM products p LEFT JOIN users u ON p.admin_id=u.id WHERE p.admin_id=? ORDER BY p.name, p.expiry");
        $st->execute([$filter_admin]);
    } else {
        $st = $pdo->query("SELECT p.*, u.full_name AS admin_name FROM products p LEFT JOIN users u ON p.admin_id=u.id ORDER BY u.full_name, p.name, p.expiry");
    }
} else {
    $st = $pdo->prepare("SELECT p.* FROM products p WHERE p.admin_id=? ORDER BY p.name, p.expiry");
    $st->execute([$admin_id]);
}
$products = $st->fetchAll();

$total_items = count($products); $low_stock = 0; $out_stock = 0; $total_value = 0; $total_units = 0; $exp_count = 0;
foreach ($products as $p) {
    $q = (int)$p['stock_qty'];
    $total_units += max(0, $q);
    $total_value += max(0, $q) * (float)$p['rate'];
    if ($q <= 0) $out_stock++; elseif ($q <= LOW_STOCK_LIMIT) $low_stock++;
    if (in_array(expiry_state($p['expiry']), ['expired', 'soon'], true)) $exp_count++;
}

// Search + status filter, then pagination (stats above always cover the whole stock, not just this page)
$fq = trim((string)($_GET['q'] ?? ''));
$fstatus = in_array($_GET['status'] ?? '', ['out', 'low', 'exp'], true) ? $_GET['status'] : '';
$filtered = filter_products($products, $fq, $fstatus);
[$page, $per] = page_params(25);
$total_rows = count($filtered);
$page = min($page, max(1, (int)ceil($total_rows / $per)));
$page_products = array_slice($filtered, ($page - 1) * $per, $per);
$export_qs = http_build_query(array_filter(['admin' => $filter_admin ?: '', 'q' => $fq, 'status' => $fstatus], fn($v) => $v !== ''));

// Per-admin overview (superadmin): cards in the "all admins" view + live info panel in the add-stock form
$overview = [];
if ($is_super) {
    $overview = $pdo->query("
        SELECT u.id, u.full_name, u.company_name,
               COUNT(p.id) AS prod_count,
               COALESCE(SUM(GREATEST(p.stock_qty,0)),0) AS units,
               COALESCE(SUM(GREATEST(p.stock_qty,0)*p.rate),0) AS val,
               COALESCE(SUM(p.stock_qty <= " . LOW_STOCK_LIMIT . "),0) AS low
        FROM users u LEFT JOIN products p ON p.admin_id=u.id
        WHERE u.role='admin' AND u.IsActive=1
        GROUP BY u.id, u.full_name, u.company_name ORDER BY u.full_name")->fetchAll();
}

// Warehouse numbers, the dropdown source for "assign", and recent transfers (superadmin)
$warehouse = ['prod_count' => 0, 'units' => 0, 'val' => 0, 'low' => 0];
$my_products = []; $recent_transfers = [];
if ($is_super) {
    $w = $pdo->prepare("SELECT COUNT(*) AS prod_count, COALESCE(SUM(GREATEST(stock_qty,0)),0) AS units,
                               COALESCE(SUM(GREATEST(stock_qty,0)*rate),0) AS val, COALESCE(SUM(stock_qty <= " . LOW_STOCK_LIMIT . "),0) AS low
                        FROM products WHERE admin_id=?");
    $w->execute([$my_id]);
    $warehouse = $w->fetch() ?: $warehouse;

    $mp = $pdo->prepare("SELECT id, name, batch, expiry, capacity, rate, gst_percent, stock_qty FROM products WHERE admin_id=? AND stock_qty>0 ORDER BY name, expiry, batch");
    $mp->execute([$my_id]);
    $my_products = $mp->fetchAll();

    try {
        $recent_transfers = $pdo->query("SELECT t.*, u.full_name AS admin_name FROM stock_transfers t LEFT JOIN users u ON u.id=t.to_admin ORDER BY t.created_at DESC, t.id DESC LIMIT 30")->fetchAll();
    } catch (Exception $e) { $recent_transfers = []; }
}

// Info about each admin for the "assign to" panel, and light product list for the live duplicate check
$admin_info = []; $all_products_min = [];
if ($is_super) {
    $ov = [];
    foreach ($overview as $o) $ov[(int)$o['id']] = $o;
    foreach ($admins as $a) {
        $o = $ov[(int)$a['id']] ?? [];
        $admin_info[(int)$a['id']] = [
            'name' => $a['full_name'], 'username' => $a['username'], 'company' => $a['company_name'] ?: '',
            'gstin' => $a['gstin'] ?: '', 'phone' => $a['company_phone'] ?: '',
            'products' => (int)($o['prod_count'] ?? 0), 'units' => (int)($o['units'] ?? 0),
            'value' => (float)($o['val'] ?? 0), 'low' => (int)($o['low'] ?? 0),
        ];
    }
    $all_products_min = $pdo->query("SELECT admin_id, name, batch, expiry, capacity, rate, gst_percent, stock_qty FROM products")->fetchAll();
}

// Units sold + last sale date per product (for the details view)
$sold = [];
$sst = $pdo->prepare("SELECT ii.product_id, SUM(ii.qty) AS sold, MAX(ii.created_at) AS last_sold
                      FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id
                      WHERE i.status=1 AND ii.product_id IS NOT NULL" . ($is_super ? "" : " AND i.admin_id=?") . "
                      GROUP BY ii.product_id");
$sst->execute($is_super ? [] : [$admin_id]);
foreach ($sst->fetchAll() as $r) $sold[(int)$r['product_id']] = $r;

// Possible duplicates (superadmin)
$possible_duplicates = [];
if ($is_super) {
    $dupRows = $pdo->query("
        SELECT admin_id, LOWER(TRIM(name)) AS norm_name, LOWER(TRIM(batch)) AS norm_batch, COUNT(*) AS cnt
        FROM products GROUP BY admin_id, norm_name, norm_batch HAVING COUNT(*) > 1")->fetchAll();
    foreach ($dupRows as $d) {
        $g = $pdo->prepare("SELECT p.*, u.full_name AS admin_name FROM products p LEFT JOIN users u ON p.admin_id=u.id WHERE p.admin_id <=> ? AND LOWER(TRIM(p.name))=? AND LOWER(TRIM(p.batch))=? ORDER BY p.id");
        $g->execute([$d['admin_id'], $d['norm_name'], $d['norm_batch']]);
        $possible_duplicates[] = $g->fetchAll();
    }
}

// Sales history for one product
$view_history = null; $distribution_data = [];
if (isset($_GET['view_history'])) {
    $pid = (int)$_GET['view_history'];
    if ($is_super) { $q = $pdo->prepare("SELECT * FROM products WHERE id=?"); $q->execute([$pid]); }
    else           { $q = $pdo->prepare("SELECT * FROM products WHERE id=? AND admin_id=?"); $q->execute([$pid, $admin_id]); }
    $view_history = $q->fetch();
    if ($view_history) {
        $h = $pdo->prepare("SELECT ii.qty, ii.created_at, i.invoice_no, i.customer_name
                            FROM invoice_items ii JOIN invoices i ON ii.invoice_id=i.id
                            WHERE i.status=1 AND i.admin_id <=> ?" . ($is_staff ? " AND i.created_by=" . (int)$_SESSION['user_id'] : "") . "
                              AND (ii.product_id = ? OR (ii.product_id IS NULL AND ii.product_name=? AND ii.batch=?))
                            ORDER BY ii.created_at DESC");
        $h->execute([$view_history['admin_id'], $pid, $view_history['name'], $view_history['batch']]);
        $distribution_data = $h->fetchAll();
    }
}
$qs_admin = $filter_admin ? '&admin=' . $filter_admin : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock | Pharma Billing</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
<?php echo ui_css(); ?>
.page-head h1{margin:0;font-size:24px;font-weight:800}
.page-head p{margin:4px 0 16px;color:var(--muted);font-size:14px}
.tabs2{display:flex;gap:6px;overflow-x:auto;margin-bottom:18px;padding-bottom:2px;scrollbar-width:none}
.tabs2::-webkit-scrollbar{display:none}
.tabs2 button{flex-shrink:0;border:1px solid var(--line);background:#fff;color:#334155;padding:11px 18px;border-radius:12px;font:inherit;font-weight:700;font-size:14px;cursor:pointer}
.tabs2 button.on{background:var(--primary);border-color:var(--primary);color:#fff;box-shadow:0 8px 18px -10px rgba(37,99,235,.7)}
.panel{display:none}.panel.on{display:block}
.chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.chip{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:99px;border:1px solid var(--line);background:#fff;color:#334155;text-decoration:none;font-weight:600;font-size:13px}
.chip small{background:#f1f5f9;border-radius:99px;padding:1px 8px;font-size:11px;color:var(--muted)}
.chip:hover{border-color:var(--primary)}
.chip.on{background:#0f172a;border-color:#0f172a;color:#fff}.chip.on small{background:rgba(255,255,255,.2);color:#fff}
.toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
.toolbar .right{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}
.hint{color:var(--muted);font-size:14px;margin:-6px 0 18px}
.simple-form{max-width:760px}
.simple-form .f{margin-bottom:16px}
.simple-form label{display:block;font-weight:700;font-size:14px;margin-bottom:6px;color:#0f172a}
.simple-form label small{font-weight:400;color:var(--muted)}
.simple-form input,.simple-form select{width:100%;font-size:16px;padding:13px 14px}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.row3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
.who{display:none;margin-top:8px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:10px 14px;font-size:14px;color:#1e3a8a}
.say{display:none;margin:4px 0 16px;padding:12px 14px;border-radius:12px;background:#fefce8;border:1px solid #fde047;color:#713f12;font-size:14px}
.say.good{background:#f0fdf4;border-color:#86efac;color:#14532d}
details.more{margin:6px 0 16px;border:1px dashed #cbd5e1;border-radius:12px;padding:10px 14px}
details.more summary{cursor:pointer;font-weight:600;color:#475569}
details.more[open] summary{margin-bottom:12px}
.big-btn{width:100%;padding:15px;font-size:16px}
.empty{text-align:center;padding:34px 10px;color:#64748b}
.empty .ico{font-size:44px}
.stock-num{font-weight:800;font-size:15px}
.pname{font-weight:700;color:#0f172a;text-decoration:none}.pname:hover{color:var(--primary)}
.btn-line{background:#fff;border:1px solid #cbd5e1;color:#0f172a}
.btn-line.red{color:#dc2626;border-color:#fecaca}
.acts{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
.acts form{display:inline}
.dgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:14px 0}
.dcell{background:#f8fafc;border-radius:10px;padding:10px 12px}
.dcell small{display:block;font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:2px}
.dcell b{font-size:15px}
@media(min-width:769px){table.data thead th{position:sticky;top:57px;z-index:5}}
@media(max-width:768px){
 .page-head h1{font-size:20px}
 .row2,.row3{grid-template-columns:1fr}
 .toolbar select,.toolbar input{width:100%}.toolbar .right{margin-left:0;width:100%}
 .tabs2 button{padding:10px 14px;font-size:13px}
}
</style>
</head>
<body>
<?php echo ui_nav('stock'); ?>
<div class="container">
    <?php echo flash_show(); ?>

    <div class="page-head">
        <h1>📦 <?php echo $is_super ? 'Stock' : ($is_staff ? 'Available stock' : 'Your stock'); ?></h1>
        <p><?php
            if ($is_super)      echo 'See everyone’s stock, add medicines to your own warehouse, and give them to admins.';
            elseif ($is_staff)  echo 'Medicines you can bill from — ' . e($biz_name) . '. View only.';
            else                echo 'Your medicines for ' . e($biz_name) . '. View only — the Super Admin adds stock for you.';
        ?></p>
    </div>

    <?php if ($is_super): ?>
    <div class="tabs2" id="tabs">
        <button type="button" data-tab="stock" class="on">📦 Stock list</button>
        <button type="button" data-tab="add">📥 Add stock</button>
        <button type="button" data-tab="give">🚚 Give to admin</button>
        <button type="button" data-tab="history">🕑 History</button>
    </div>
    <?php endif; ?>

    <!-- ===================== STOCK LIST ===================== -->
    <section class="panel on" id="panel-stock">

        <div class="stats-grid">
            <div class="stat-card"><h3>Medicines</h3><p><?php echo $total_items; ?></p></div>
            <div class="stat-card"><h3>Running low</h3><p style="color:var(--warn)"><?php echo $low_stock; ?></p></div>
            <div class="stat-card"><h3>Out of stock</h3><p style="color:var(--danger)"><?php echo $out_stock; ?></p></div>
            <div class="stat-card"><h3>Expired / expiring</h3><p style="color:var(--danger)"><?php echo $exp_count; ?></p></div>
        </div>

        <?php if ($is_super && !empty($possible_duplicates)): ?>
        <details class="card" style="border-color:#fde047;background:#fffbeb;padding:14px 18px">
            <summary style="cursor:pointer;font-weight:700">⚠️ <?php echo count($possible_duplicates); ?> medicine(s) may be entered twice — tap to check</summary>
            <p style="color:#713f12;font-size:13px;margin:10px 0">Same medicine and batch spelled slightly differently. Merge them only if they really are the same.</p>
            <?php foreach ($possible_duplicates as $grp): ?>
                <table class="data rt" style="margin-bottom:12px">
                    <thead><tr><th>Whose</th><th>Name</th><th>Batch</th><th>Stock</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($grp as $i => $p): ?>
                        <tr>
                            <td data-label="Whose"><?php echo ((int)$p['admin_id'] === $my_id) ? '🏭 Warehouse' : e($p['admin_name'] ?? 'Unassigned'); ?></td>
                            <td data-label="Name"><strong><?php echo e($p['name']); ?></strong></td>
                            <td data-label="Batch"><?php echo e($p['batch']); ?></td>
                            <td data-label="Stock"><?php echo (int)$p['stock_qty']; ?></td>
                            <td data-label="" class="no-label"><div class="acts">
                                <?php if ($i === 0): ?><span class="muted" style="font-size:12px;color:var(--muted)">Keep this one</span>
                                <?php else: ?>
                                    <form method="POST" data-confirm="Merge these two?" data-confirm-text="The stock of this row is added to the first row, and this row is removed. This cannot be undone." data-confirm-btn="Yes, merge">
                                        <input type="hidden" name="keep_id" value="<?php echo (int)$grp[0]['id']; ?>">
                                        <input type="hidden" name="merge_id" value="<?php echo (int)$p['id']; ?>">
                                        <input type="hidden" name="back_admin" value="<?php echo $filter_admin; ?>">
                                        <button class="btn btn-warn btn-sm" name="merge_products" value="1">Merge into first</button>
                                    </form>
                                <?php endif; ?>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </details>
        <?php endif; ?>

        <div class="card">
            <?php if ($is_super): ?>
            <div class="chips">
                <a class="chip <?php echo !$filter_admin ? 'on' : ''; ?>" href="inventory.php">All <small><?php echo count($all_products_min); ?></small></a>
                <a class="chip <?php echo $in_warehouse ? 'on' : ''; ?>" href="inventory.php?admin=<?php echo $my_id; ?>">🏭 My warehouse <small><?php echo (int)$warehouse['prod_count']; ?></small></a>
                <?php foreach ($overview as $o): ?>
                    <a class="chip <?php echo $filter_admin === (int)$o['id'] ? 'on' : ''; ?>" href="inventory.php?admin=<?php echo (int)$o['id']; ?>"><?php echo e($o['full_name']); ?> <small><?php echo (int)$o['prod_count']; ?></small></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="GET" class="toolbar">
                <input type="hidden" name="per" value="<?php echo (int)$per; ?>">
                <?php if ($filter_admin): ?><input type="hidden" name="admin" value="<?php echo $filter_admin; ?>"><?php endif; ?>
                <input type="search" name="q" value="<?php echo e($fq); ?>" class="search-bar" placeholder="🔍 Search medicine or batch">
                <select name="status" onchange="this.form.submit()">
                    <option value="">Show all</option>
                    <option value="out" <?php echo $fstatus === 'out' ? 'selected' : ''; ?>>Out of stock</option>
                    <option value="low" <?php echo $fstatus === 'low' ? 'selected' : ''; ?>>Running low</option>
                    <option value="exp" <?php echo $fstatus === 'exp' ? 'selected' : ''; ?>>Expired / expiring</option>
                </select>
                <button class="btn btn-primary btn-sm">Search</button>
                <?php if ($fq !== '' || $fstatus !== ''): ?><a class="btn btn-light btn-sm" href="inventory.php<?php echo $filter_admin ? '?admin=' . $filter_admin : ''; ?>">Clear</a><?php endif; ?>
                <?php if (!$is_staff): ?>
                <span class="right">
                    <a class="btn btn-excel btn-sm" href="export.php?type=stock<?php echo $export_qs ? '&' . e($export_qs) : ''; ?>" title="Download this list as Excel">⬇ Excel</a>
                    <a class="btn btn-light btn-sm" href="export.php?type=all<?php echo $filter_admin ? '&admin=' . $filter_admin : ''; ?>" title="Download everything as a backup">💾 Backup</a>
                </span>
                <?php endif; ?>
            </form>

            <table class="data rt" id="inventoryTable">
                <thead><tr>
                    <?php if ($is_super): ?><th>Whose</th><?php endif; ?>
                    <th>Medicine</th><th>Batch</th><th>Expiry</th><th>In stock</th><th>Rate</th><th>Status</th><th style="text-align:right"></th>
                </tr></thead>
                <tbody>
                <?php foreach ($page_products as $p):
                    $q = (int)$p['stock_qty']; $ex = expiry_state($p['expiry']);
                    $sv = $sold[(int)$p['id']] ?? null;
                    $ai = $admin_info[(int)$p['admin_id']] ?? null;
                    $isWh = ($is_super && (int)$p['admin_id'] === $my_id);
                    $detail = [
                        'id' => (int)$p['id'], 'admin_id' => (int)$p['admin_id'], 'warehouse' => $isWh,
                        'admin' => $isWh ? 'My warehouse' : ($p['admin_name'] ?? ''),
                        'company' => $ai['company'] ?? '',
                        'name' => $p['name'], 'batch' => $p['batch'], 'capacity' => $p['capacity'], 'expiry' => $p['expiry'], 'exp' => $ex,
                        'rate' => $p['rate'], 'gst' => $p['gst_percent'], 'stock' => max(0, $q),
                        'value' => $is_staff ? 0 : max(0, $q) * (float)$p['rate'],
                        'sold' => (int)($sv['sold'] ?? 0), 'last' => $sv ? date('d-M-Y', strtotime($sv['last_sold'])) : '—',
                    ];
                    $j = json_encode($detail, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG);
                ?>
                    <tr>
                        <?php if ($is_super): ?><td data-label="Whose"><?php if ($isWh): ?><span class="badge b-green">🏭 Warehouse</span><?php else: ?><span class="badge b-blue"><?php echo e($p['admin_name'] ?? 'Unassigned'); ?></span><?php endif; ?></td><?php endif; ?>
                        <td data-label="Medicine"><div style="text-align:inherit"><a href="#" class="pname" onclick='openDetail(<?php echo $j; ?>);return false;'><?php echo e($p['name']); ?></a><?php if ($p['capacity']): ?><br><small style="color:var(--muted)"><?php echo e($p['capacity']); ?></small><?php endif; ?></div></td>
                        <td data-label="Batch"><?php echo e($p['batch']); ?></td>
                        <td data-label="Expiry"><?php echo e($p['expiry']); ?><?php if ($ex === 'expired'): ?> <span class="badge b-red">Expired</span><?php elseif ($ex === 'soon'): ?> <span class="badge b-amber">Expiring</span><?php endif; ?></td>
                        <td data-label="In stock"><span class="stock-num"><?php echo max(0, $q); ?></span></td>
                        <td data-label="Rate">₹<?php echo e($p['rate']); ?></td>
                        <td data-label="Status"><?php if ($q <= 0): ?><span class="badge b-red">Out of stock</span><?php elseif ($q <= LOW_STOCK_LIMIT): ?><span class="badge b-amber">Running low</span><?php else: ?><span class="badge b-green">In stock</span><?php endif; ?></td>
                        <td data-label="" class="no-label"><div class="acts">
                            <button type="button" class="btn btn-line btn-sm" onclick='openDetail(<?php echo $j; ?>)'>Details</button>
                            <?php if ($is_super): ?>
                                <?php if ($isWh && $q > 0): ?><button type="button" class="btn btn-success btn-sm" onclick="giveFromRow(<?php echo (int)$p['id']; ?>)">Give</button><?php endif; ?>
                                <button type="button" class="btn btn-line btn-sm" onclick='openEdit(<?php echo $j; ?>)'>Edit</button>
                                <form method="POST" data-confirm="Delete “<?php echo e($p['name']); ?>” (batch <?php echo e($p['batch']); ?>)?" data-confirm-text="This removes it from the stock list. Old invoices are not affected." data-confirm-btn="Yes, delete" data-danger>
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                    <input type="hidden" name="back_admin" value="<?php echo $filter_admin; ?>">
                                    <button class="btn btn-line red btn-sm" name="delete_product" value="1">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($page_products)): ?>
                    <tr><td colspan="9" class="no-label"><div class="empty"><div class="ico">📦</div><?php echo ($fq !== '' || $fstatus !== '') ? 'Nothing matches your search.' : ($in_warehouse ? 'Your warehouse is empty. Open “Add stock” to put medicines in it.' : 'No medicines here yet.'); ?></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <?php echo pager($total_rows, $page, $per); ?>
        </div>
    </section>

    <?php if ($is_super): ?>
    <!-- ===================== ADD STOCK (my warehouse) ===================== -->
    <section class="panel" id="panel-add">
        <div class="card">
            <div class="card-title">📥 Add stock to my warehouse</div>
            <p class="hint">Type the medicine here once. Later you choose it from a list when giving it to an admin.</p>
            <form method="POST" id="addStockForm" class="simple-form" autocomplete="off">
                <input type="hidden" name="back_admin" value="<?php echo $filter_admin; ?>">
                <div class="row2">
                    <div class="f"><label>Medicine name</label><input type="text" name="name" id="f_name" list="nameList" placeholder="e.g. Paracetamol 500" required oninput="addCheck()"></div>
                    <div class="f"><label>Batch number</label><input type="text" name="batch" id="f_batch" placeholder="e.g. B1234" required oninput="addCheck()"></div>
                </div>
                <div class="row3">
                    <div class="f"><label>Expiry <small>(month)</small></label><input type="month" name="expiry" id="f_expiry" required></div>
                    <div class="f"><label>Quantity</label><input type="number" name="stock_qty" id="f_qty" min="0" step="1" inputmode="numeric" placeholder="0" required oninput="addCheck()"></div>
                    <div class="f"><label>Rate <small>(₹ per unit)</small></label><input type="number" name="rate" id="f_rate" min="0" step="0.01" inputmode="decimal" placeholder="0.00" required oninput="addCheck()"></div>
                </div>
                <details class="more">
                    <summary>More details (pack size, GST) — optional</summary>
                    <div class="row2">
                        <div class="f"><label>Pack size</label><input type="text" name="capacity" id="f_cap" placeholder="e.g. 10 Tabs"></div>
                        <div class="f"><label>GST</label><select name="gst_percent" id="f_gst"><option value="5">5%</option><option value="12">12%</option><option value="18">18%</option><option value="0">Exempt</option></select></div>
                    </div>
                </details>
                <datalist id="nameList"></datalist>
                <div id="addSay" class="say"></div>
                <button type="submit" name="add_product" value="1" class="btn btn-primary big-btn" id="addBtn">＋ Add to my warehouse</button>
            </form>
        </div>
    </section>

    <!-- ===================== GIVE TO ADMIN ===================== -->
    <section class="panel" id="panel-give">
        <div class="card">
            <div class="card-title">🚚 Give stock to an admin</div>
            <p class="hint">Pick from your warehouse list — there is nothing to type.</p>
            <?php if (empty($my_products)): ?>
                <div class="empty"><div class="ico">🏭</div>Your warehouse is empty.<br><br><button type="button" class="btn btn-primary" onclick="showTab('add')">📥 Add stock first</button></div>
            <?php elseif (empty($admins)): ?>
                <div class="empty"><div class="ico">👤</div>There are no active admins yet.<br><br><a class="btn btn-primary" href="superadmin.php?tab=create">Create an admin</a></div>
            <?php else: ?>
            <form method="POST" id="giveForm" class="simple-form" autocomplete="off">
                <div class="f">
                    <label>1. Which medicine?</label>
                    <select name="source_id" id="a_prod" required onchange="giveProduct()">
                        <option value="">Choose from your warehouse…</option>
                        <?php foreach ($my_products as $mp):
                            $lbl = $mp['name'] . '  ·  Batch ' . $mp['batch'] . ($mp['expiry'] ? '  ·  Exp ' . $mp['expiry'] : '') . '  ·  ' . (int)$mp['stock_qty'] . ' in stock'; ?>
                            <option value="<?php echo (int)$mp['id']; ?>" data-stock="<?php echo (int)$mp['stock_qty']; ?>" data-rate="<?php echo e($mp['rate']); ?>" data-name="<?php echo e($mp['name']); ?>" data-batch="<?php echo e($mp['batch']); ?>"><?php echo e($lbl); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="f">
                    <label>2. Which admin?</label>
                    <select name="to_admin" id="a_admin" required onchange="giveAdmin()">
                        <option value="">Choose an admin…</option>
                        <?php foreach ($admins as $a): ?>
                            <option value="<?php echo (int)$a['id']; ?>" <?php echo ($filter_admin === (int)$a['id']) ? 'selected' : ''; ?>><?php echo e($a['full_name'] . ($a['company_name'] ? ' — ' . $a['company_name'] : '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="who" id="whoBox"></div>
                </div>
                <div class="f">
                    <label>3. How many? <small id="a_max"></small></label>
                    <input type="number" name="qty" id="a_qty" min="1" step="1" inputmode="numeric" placeholder="0" required oninput="giveQty()">
                </div>
                <details class="more">
                    <summary>Change the rate for this admin — optional</summary>
                    <div class="f" style="margin:0"><label>Rate <small>(₹ per unit — leave as it is to keep the warehouse rate)</small></label><input type="number" name="rate" id="a_rate" min="0" step="0.01" inputmode="decimal" oninput="giveSay()"></div>
                </details>
                <div id="giveSay" class="say"></div>
                <button type="submit" name="assign_stock" value="1" class="btn btn-success big-btn" id="a_btn">🚚 Give stock</button>
            </form>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===================== HISTORY ===================== -->
    <section class="panel" id="panel-history">
        <div class="card">
            <div class="card-title">🕑 Stock you gave to admins</div>
            <?php if (empty($recent_transfers)): ?>
                <div class="empty"><div class="ico">🕑</div>Nothing yet. After you give stock to an admin it is listed here.</div>
            <?php else: ?>
            <table class="data rt">
                <thead><tr><th>When</th><th>Medicine</th><th>Batch</th><th>Quantity</th><th>Given to</th></tr></thead>
                <tbody>
                <?php foreach ($recent_transfers as $t): ?>
                    <tr>
                        <td data-label="When"><?php echo e(date('d-M-Y H:i', strtotime($t['created_at']))); ?></td>
                        <td data-label="Medicine"><strong><?php echo e($t['product_name']); ?></strong></td>
                        <td data-label="Batch"><?php echo e($t['batch']); ?></td>
                        <td data-label="Quantity"><strong><?php echo (int)$t['qty']; ?></strong></td>
                        <td data-label="Given to"><span class="badge b-blue"><?php echo e($t['admin_name'] ?? '—'); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php if ($is_super): ?>
<div class="modal-bg" id="editModal" onclick="if(event.target===this)closeEdit()">
    <div class="modal">
        <h2>✏️ Edit medicine</h2>
        <div id="editTitle" style="color:var(--muted);font-size:13px;margin:-8px 0 14px;"></div>
        <form method="POST" class="simple-form" style="max-width:none">
            <input type="hidden" name="id" id="e_id">
            <input type="hidden" name="back_admin" value="<?php echo $filter_admin; ?>">
            <div class="row2">
                <div class="f"><label>In stock</label><input type="number" name="stock_qty" id="e_stock" min="0" step="1" inputmode="numeric" required></div>
                <div class="f"><label>Rate (₹)</label><input type="number" name="rate" id="e_rate" min="0" step="0.01" required></div>
            </div>
            <div class="row2">
                <div class="f"><label>Expiry</label><input type="month" name="expiry" id="e_expiry"></div>
                <div class="f"><label>GST %</label><input type="number" name="gst_percent" id="e_gst" min="0" max="100" step="0.01"></div>
            </div>
            <div class="f"><label>Pack size</label><input type="text" name="capacity" id="e_cap"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn btn-light" onclick="closeEdit()">Cancel</button>
                <button type="submit" name="edit_product" value="1" class="btn btn-primary">💾 Save</button>
            </div>
        </form>
        <p style="font-size:12px;color:var(--muted);margin:10px 0 0">Stock can’t go below 0. Changing it here does not create an invoice.</p>
    </div>
</div>
<?php endif; ?>

<div class="modal-bg" id="detailModal" onclick="if(event.target===this)closeDetail()">
    <div class="modal" style="max-width:560px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
            <div><h2 id="d_title" style="margin:0"></h2><div id="d_sub" style="color:var(--muted);font-size:13px;margin-top:4px;"></div></div>
            <a href="#" onclick="closeDetail();return false;" style="font-size:26px;text-decoration:none;color:#64748b;line-height:1">&times;</a>
        </div>
        <div id="d_status" style="margin-top:10px"></div>
        <div class="dgrid" id="d_grid"></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap" id="d_acts"></div>
    </div>
</div>

<?php if ($view_history): ?>
<div class="modal-bg open">
    <div class="modal" style="max-width:720px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h2 style="margin:0">📊 Sales of this medicine</h2>
            <a href="inventory.php<?php echo $filter_admin ? '?admin=' . $filter_admin : ''; ?>" style="font-size:26px;text-decoration:none;color:#64748b;">&times;</a>
        </div>
        <div style="background:#f8fafc;padding:12px;border-radius:8px;margin:14px 0;">
            <strong><?php echo e($view_history['name']); ?></strong><br>
            <span style="color:var(--muted);font-size:13px;">Batch <?php echo e($view_history['batch']); ?> · In stock: <strong><?php echo max(0, (int)$view_history['stock_qty']); ?></strong></span>
        </div>
        <?php if ($distribution_data): $total_dist = 0; ?>
            <table class="data rt">
                <thead><tr><th>Invoice</th><th>Customer</th><th>Quantity sold</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($distribution_data as $d): $total_dist += (int)$d['qty']; ?>
                    <tr>
                        <td data-label="Invoice"><strong>#<?php echo e($d['invoice_no']); ?></strong></td>
                        <td data-label="Customer"><?php echo e($d['customer_name']); ?></td>
                        <td data-label="Quantity sold" style="color:var(--danger);font-weight:700"><?php echo (int)$d['qty']; ?></td>
                        <td data-label="Date"><?php echo date('d-M-Y', strtotime($d['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="2" class="no-label"><strong>Total sold</strong></td><td class="no-label" style="color:var(--danger);font-weight:800"><?php echo $total_dist; ?></td><td></td></tr></tfoot>
            </table>
        <?php else: ?>
            <div class="empty"><div class="ico">📦</div>No sales yet for this medicine.</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money = n => '₹' + Number(n || 0).toLocaleString('en-IN', {maximumFractionDigits: 2});
const norm = s => String(s || '').trim().toLowerCase();
const IS_SUPER = <?php echo $is_super ? 'true' : 'false'; ?>;
const IS_STAFF = <?php echo $is_staff ? 'true' : 'false'; ?>;
const MY_ID = <?php echo (int)$my_id; ?>;
const QS_ADMIN = <?php echo json_encode($qs_admin); ?>;
const LOW = <?php echo (int)LOW_STOCK_LIMIT; ?>;
const ADMINS = <?php echo json_encode((object)$admin_info, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const PRODS  = <?php echo json_encode($all_products_min, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

/* ---------- tabs (superadmin) ---------- */
function showTab(t) {
    if (!$('panel-' + t)) t = 'stock';
    document.querySelectorAll('.panel').forEach(p => p.classList.toggle('on', p.id === 'panel-' + t));
    document.querySelectorAll('#tabs button').forEach(b => b.classList.toggle('on', b.dataset.tab === t));
    try { history.replaceState(null, '', '#' + t); } catch (e) {}
    window.scrollTo({top: 0, behavior: 'smooth'});
}
if (IS_SUPER) {
    document.querySelectorAll('#tabs button').forEach(b => b.addEventListener('click', () => showTab(b.dataset.tab)));
    const h = location.hash.replace('#', '');
    if (['add', 'give', 'history'].includes(h)) showTab(h);
}

/* ---------- add stock to my warehouse ---------- */
let matched = null;
function addCheck() {
    const n = norm($('f_name').value), b = norm($('f_batch').value), say = $('addSay'), f = $('addStockForm');
    matched = (n && b) ? (PRODS.find(p => +p.admin_id === MY_ID && norm(p.name) === n && norm(p.batch) === b) || null) : null;
    if (matched) {
        if (!$('f_rate').value) $('f_rate').value = matched.rate;
        if (!$('f_cap').value) $('f_cap').value = matched.capacity || '';
        if (!$('f_expiry').value && /^\d{4}-\d{2}/.test(matched.expiry || '')) $('f_expiry').value = matched.expiry.substring(0, 7);
        const g = String(parseFloat(matched.gst_percent));
        if ([...$('f_gst').options].some(o => o.value === g)) $('f_gst').value = g;
        say.className = 'say good'; say.style.display = 'block';
        say.innerHTML = `✔ You already have <b>${matched.stock_qty}</b> of this batch. What you enter is <b>added</b> to it.`;
    } else { say.style.display = 'none'; }
    const q = parseInt($('f_qty').value) || 0, name = $('f_name').value.trim(), batch = $('f_batch').value.trim();
    f.dataset.confirm = `Add ${q} × ${name || 'this medicine'} to your warehouse?`;
    f.dataset.confirmText = `Batch <b>${esc(batch)}</b> · ${money($('f_rate').value)} per unit` + (matched ? `<br>Warehouse will then have <b>${(+matched.stock_qty) + q}</b>` : '');
    f.dataset.confirmBtn = 'Yes, add it';
}
function fillNames() {
    const dl = $('nameList'), seen = new Set();
    dl.innerHTML = '';
    PRODS.filter(p => +p.admin_id === MY_ID).forEach(p => {
        if (seen.has(p.name)) return; seen.add(p.name);
        const o = document.createElement('option'); o.value = p.name; dl.appendChild(o);
    });
}

/* ---------- give stock to an admin (dropdowns only) ---------- */
function gProd() {
    const o = $('a_prod') ? $('a_prod').selectedOptions[0] : null;
    return (o && o.value) ? {id: +o.value, stock: +o.dataset.stock, rate: o.dataset.rate, name: o.dataset.name, batch: o.dataset.batch} : null;
}
function giveProduct() {
    const p = gProd(), q = $('a_qty');
    if (p) { q.max = p.stock; $('a_max').textContent = '(you have ' + p.stock + ')'; $('a_rate').value = p.rate; if ((+q.value || 0) > p.stock) q.value = p.stock; }
    else { q.removeAttribute('max'); $('a_max').textContent = ''; $('a_rate').value = ''; }
    giveSay();
}
function giveAdmin() {
    const a = ADMINS[$('a_admin').value], box = $('whoBox');
    if (a) {
        box.style.display = 'block';
        box.innerHTML = `<b>${esc(a.name)}</b>${a.company ? ' — ' + esc(a.company) : ''}<br>Currently has <b>${a.products}</b> medicines (${Number(a.units).toLocaleString('en-IN')} units)`;
    } else { box.style.display = 'none'; }
    giveSay();
}
function giveQty() {
    const p = gProd(), q = $('a_qty');
    if (p && (+q.value || 0) > p.stock) { q.value = p.stock; uiToast('warning', 'You only have ' + p.stock + ' in your warehouse'); }
    if ((+q.value || 0) < 0) q.value = '';
    giveSay();
}
function giveSay() {
    const p = gProd(), a = ADMINS[$('a_admin').value], q = parseInt($('a_qty').value) || 0, say = $('giveSay'), f = $('giveForm');
    if (!p || !a || q < 1) { say.style.display = 'none'; f.dataset.confirm = ''; return; }
    const ex = PRODS.find(x => +x.admin_id === +$('a_admin').value && norm(x.name) === norm(p.name) && norm(x.batch) === norm(p.batch));
    say.className = 'say good'; say.style.display = 'block';
    say.innerHTML = `<b>${q}</b> × ${esc(p.name)} will move from your warehouse (<b>${p.stock} → ${p.stock - q}</b>) to <b>${esc(a.name)}</b>` +
        (ex ? ` — they already have ${ex.stock_qty}, so they will have <b>${(+ex.stock_qty) + q}</b>.` : '.');
    f.dataset.confirm = `Give ${q} × ${p.name} to ${a.name}?`;
    f.dataset.confirmText = `Batch <b>${esc(p.batch)}</b><br>Your warehouse: ${p.stock} → <b>${p.stock - q}</b>` + (ex ? `<br>${esc(a.name)}: ${ex.stock_qty} → <b>${(+ex.stock_qty) + q}</b>` : '');
    f.dataset.confirmBtn = 'Yes, give it';
}
function giveFromRow(id) {
    showTab('give');
    const s = $('a_prod'); if (!s) return;
    s.value = String(id); giveProduct();
    setTimeout(() => ($('a_admin').value ? $('a_qty') : $('a_admin')).focus(), 350);
}
if (IS_SUPER) {
    fillNames(); addCheck();
    const gf = $('giveForm');
    if (gf) {
        gf.__check = () => {
            const p = gProd(), a = ADMINS[$('a_admin').value], q = parseInt($('a_qty').value) || 0;
            if (!p) { uiAlert('warning', 'Choose a medicine', 'Pick the medicine from your warehouse list.'); return false; }
            if (!a) { uiAlert('warning', 'Choose an admin', 'Pick who should get this stock.'); return false; }
            if (q < 1 || q > p.stock) { uiAlert('warning', 'Check the quantity', 'Enter a number between 1 and ' + p.stock + '.'); return false; }
            return true;
        };
        giveAdmin();
    }
}

/* ---------- edit / details popups ---------- */
function openEdit(p) {
    $('e_id').value = p.id;
    $('editTitle').textContent = p.name + ' · batch ' + p.batch + (p.admin ? ' · ' + p.admin : '');
    $('e_stock').value = p.stock; $('e_rate').value = p.rate;
    $('e_expiry').value = /^\d{4}-\d{2}/.test(p.expiry || '') ? p.expiry.substring(0, 7) : '';
    $('e_gst').value = p.gst; $('e_cap').value = p.capacity || '';
    closeDetail();
    $('editModal').classList.add('open');
}
function closeEdit() { $('editModal').classList.remove('open'); }

function openDetail(p) {
    $('d_title').textContent = p.name;
    $('d_sub').textContent = 'Batch ' + p.batch + (p.capacity ? ' · ' + p.capacity : '');
    let st = p.stock <= 0 ? '<span class="badge b-red">Out of stock</span>' : p.stock <= LOW ? '<span class="badge b-amber">Running low</span>' : '<span class="badge b-green">In stock</span>';
    if (p.exp === 'expired') st += ' <span class="badge b-red">Expired</span>';
    else if (p.exp === 'soon') st += ' <span class="badge b-amber">Expiring within 90 days</span>';
    $('d_status').innerHTML = st;
    const cells = [['In stock', p.stock + ' units'], ['Rate', money(p.rate)], ['Expiry', p.expiry || '—'], ['GST', parseFloat(p.gst) + '%'], ['Sold so far', p.sold + ' units'], ['Last sold', p.last]];
    if (!IS_STAFF) cells.splice(2, 0, ['Stock worth', money(p.value)]);
    if (p.admin) cells.unshift([p.warehouse ? 'Where' : 'Belongs to', p.admin + (p.company ? ' — ' + p.company : '')]);
    $('d_grid').innerHTML = cells.map(c => `<div class="dcell"><small>${esc(c[0])}</small><b>${esc(c[1])}</b></div>`).join('');
    let acts = `<a class="btn btn-light btn-sm" href="?view_history=${p.id}${QS_ADMIN}">📊 See sales</a>`;
    if (IS_SUPER) {
        acts += ` <button class="btn btn-line btn-sm" type="button" id="d_edit">✏️ Edit</button>`;
        if (p.warehouse) { if (p.stock > 0) acts += ` <button class="btn btn-success btn-sm" type="button" onclick="closeDetail();giveFromRow(${p.id})">🚚 Give to an admin</button>`; }
        else if (p.admin_id) acts += ` <a class="btn btn-primary btn-sm" href="billing.php?for=${p.admin_id}">🧾 Bill for ${esc(p.admin)}</a>`;
    }
    $('d_acts').innerHTML = acts;
    if (IS_SUPER) $('d_edit').onclick = () => openEdit(p);
    $('detailModal').classList.add('open');
}
function closeDetail() { $('detailModal').classList.remove('open'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeDetail(); if (IS_SUPER) closeEdit(); } });
</script>
</body>
</html>

