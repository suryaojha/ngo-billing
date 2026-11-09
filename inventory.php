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
        header("Location: inventory.php?admin=$my_id"); exit;
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
        header("Location: inventory.php?admin=$toAdm"); exit;
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
        $recent_transfers = $pdo->query("SELECT t.*, u.full_name AS admin_name FROM stock_transfers t LEFT JOIN users u ON u.id=t.to_admin ORDER BY t.created_at DESC, t.id DESC LIMIT 12")->fetchAll();
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
html{scroll-behavior:smooth}
.page-head{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px}
.page-head h1{margin:0;font-size:24px;font-weight:800}
.page-head p{margin:4px 0 0;color:var(--muted);font-size:14px}
.page-head .ph-acts{display:flex;gap:8px;flex-wrap:wrap}
.admin-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:14px;margin-bottom:22px}
.admin-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:16px;transition:.15s}
.admin-card:hover{border-color:#93c5fd;box-shadow:0 10px 24px -12px rgba(37,99,235,.35)}
.admin-card h4{margin:0 0 2px;font-size:16px}.admin-card small{color:var(--muted);display:block}
.admin-card .nums{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin:12px 0;text-align:center;background:#f8fafc;border-radius:10px;padding:10px 4px}
.admin-card .nums b{display:block;font-size:17px}.admin-card .nums span{font-size:10px;color:var(--muted);text-transform:uppercase}
.admin-card .acts{display:flex;gap:8px;flex-wrap:wrap}
.avatar{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px;flex-shrink:0}

/* assign-stock card */
.assign-card{border:1px solid #bfdbfe;background:linear-gradient(180deg,#f8fbff,#fff 120px)}
.step{display:flex;align-items:center;gap:10px;font-weight:700;margin:6px 0 10px;font-size:15px}
.step b{width:24px;height:24px;border-radius:50%;background:var(--primary);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:12px}
#f_admin{width:100%;max-width:520px;font-weight:600}
.adm-panel{margin:12px 0 18px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:14px;padding:14px}
.adm-top{display:flex;gap:12px;align-items:center;font-size:13px;line-height:1.5}
.adm-top small{color:var(--muted)}
.adm-nums{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:12px;text-align:center}
.adm-nums div{background:#fff;border-radius:10px;padding:8px 4px}
.adm-nums b{display:block;font-size:16px}.adm-nums span{font-size:10px;color:var(--muted);text-transform:uppercase}
.note{background:#fef9c3;border:1px solid #fde047;color:#713f12;border-radius:10px;padding:10px 14px;margin-top:12px;font-size:13px}
.sum-bar{margin-top:14px;padding:12px 14px;border-radius:12px;background:#0f172a;color:#e2e8f0;font-size:14px}
.sum-bar b{color:#fff}
.add-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:14px}

.filter-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.act a,.act button.link{background:none;border:none;cursor:pointer;font-size:17px;text-decoration:none;padding:4px 6px;border-radius:8px}
.act a:hover,.act button.link:hover{background:#f1f5f9}
.stock-num{font-weight:800;font-size:15px}
.pname{font-weight:700;color:#0f172a;text-decoration:none}.pname:hover{color:var(--primary)}
.muted{color:var(--muted)}
.dgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:14px 0}
.dcell{background:#f8fafc;border-radius:10px;padding:10px 12px}
.dcell small{display:block;font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:2px}
.dcell b{font-size:15px}
.fab{display:none}
@media(min-width:769px){table.data thead th{position:sticky;top:57px;z-index:5}}
@media(max-width:768px){
 .page-head h1{font-size:20px}
 .page-head .ph-acts{width:100%}.page-head .ph-acts .btn{flex:1;text-align:center}
 .filter-row select,.filter-row input{width:100%}
 .act{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
 .adm-nums{grid-template-columns:1fr 1fr}
 .dgrid{grid-template-columns:1fr 1fr}
 #f_admin{max-width:none}
 .fab{display:flex;position:fixed;right:16px;bottom:18px;z-index:1500;width:56px;height:56px;border-radius:50%;background:var(--primary);color:#fff;font-size:28px;align-items:center;justify-content:center;text-decoration:none;box-shadow:0 10px 24px rgba(37,99,235,.5)}
 .add-actions .btn{width:100%}
}
</style>
</head>
<body>
<?php echo ui_nav('stock'); ?>
<div class="container">
    <?php echo flash_show(); ?>

    <div class="page-head">
        <div>
            <h1>📦 <?php echo $is_super ? 'Stock — all admins' : ($is_staff ? 'Available stock' : 'Your stock'); ?></h1>
            <p><?php echo $is_super ? 'Add stock to your own warehouse, then assign it to admins. Track levels and expiry, and bill on their behalf.' : ($is_staff ? 'Stock you can bill from — ' . e($biz_name) . '.' : 'Live stock levels, expiry and sales for ' . e($biz_name) . '.'); ?></p>
        </div>
        <?php if ($is_super): ?>
        <div class="ph-acts">
            <a class="btn btn-primary" href="#addForm" onclick="setTimeout(()=>document.getElementById('f_name').focus(),400)">＋ Add to warehouse</a>
            <a class="btn btn-success" href="#assignForm">🚚 Assign to admin</a>
            <a class="btn btn-light" href="billing.php<?php echo ($filter_admin && !$in_warehouse) ? '?for=' . $filter_admin : ''; ?>">🧾 Create bill</a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$is_super): ?>
        <div class="alert alert-info"><?php echo $is_staff ? 'View only. This is your admin\'s stock — it goes down automatically as you make invoices.' : 'View only. To add or change products, contact the Super Admin.'; ?></div>
    <?php endif; ?>

    <?php if ($is_super && !empty($possible_duplicates)): ?>
    <div class="card" style="border-color:#fde047;background:#fffbeb;">
        <div class="card-title">⚠️ Possible duplicate products (<?php echo count($possible_duplicates); ?>)</div>
        <p style="color:#713f12;font-size:13px;margin-top:-6px;">Same medicine/batch but spelled or capitalised differently. Merge only if they really are the same product.</p>
        <?php foreach ($possible_duplicates as $grp): ?>
            <table class="data rt" style="margin-bottom:14px;">
                <thead><tr><th>Admin</th><th>Name</th><th>Batch</th><th>Stock</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($grp as $i => $p): ?>
                    <tr>
                        <td data-label="Admin"><?php echo e($p['admin_name'] ?? 'Unassigned'); ?></td>
                        <td data-label="Name"><strong>"<?php echo e($p['name']); ?>"</strong></td>
                        <td data-label="Batch">"<?php echo e($p['batch']); ?>"</td>
                        <td data-label="Stock"><?php echo (int)$p['stock_qty']; ?></td>
                        <td data-label="Action" class="act">
                            <?php if ($i === 0): ?><span class="muted" style="font-size:12px;">Keep this one</span>
                            <?php else: ?>
                                <form method="POST" onsubmit="return confirm('Merge this stock into the first row? This cannot be undone.');">
                                    <input type="hidden" name="keep_id" value="<?php echo (int)$grp[0]['id']; ?>">
                                    <input type="hidden" name="merge_id" value="<?php echo (int)$p['id']; ?>">
                                    <input type="hidden" name="back_admin" value="<?php echo $filter_admin; ?>">
                                    <button class="btn btn-warn btn-sm" name="merge_products" value="1">Merge into first</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($is_super && !$filter_admin): ?>
    <div class="admin-cards">
        <div class="admin-card" style="border-color:#86efac;background:linear-gradient(180deg,#f0fdf4,#fff 90px)">
            <div style="display:flex;gap:12px;align-items:center;">
                <div class="avatar" style="background:linear-gradient(135deg,#16a34a,#0d9488)">🏭</div>
                <div style="min-width:0"><h4>My warehouse</h4><small>Your own stock — assign it to admins</small></div>
            </div>
            <div class="nums">
                <div><b><?php echo (int)$warehouse['prod_count']; ?></b><span>Products</span></div>
                <div><b><?php echo number_format((int)$warehouse['units']); ?></b><span>Units</span></div>
                <div><b style="color:<?php echo $warehouse['low'] > 0 ? 'var(--danger)' : 'var(--success)'; ?>"><?php echo (int)$warehouse['low']; ?></b><span>Low / out</span></div>
            </div>
            <div style="font-size:13px;margin-bottom:12px;">Stock value: <strong>₹<?php echo number_format((float)$warehouse['val'], 0); ?></strong></div>
            <div class="acts">
                <a class="btn btn-light btn-sm" href="inventory.php?admin=<?php echo $my_id; ?>">View warehouse</a>
                <a class="btn btn-success btn-sm" href="#assignForm">🚚 Assign</a>
            </div>
        </div>
        <?php foreach ($overview as $o): $ai = $admin_info[(int)$o['id']] ?? null; ?>
        <div class="admin-card">
            <div style="display:flex;gap:12px;align-items:center;">
                <div class="avatar"><?php echo e(strtoupper(substr($o['full_name'], 0, 1))); ?></div>
                <div style="min-width:0">
                    <h4><?php echo e($o['full_name']); ?></h4>
                    <small><?php echo e($o['company_name'] ?: 'Company not set'); ?></small>
                    <?php if ($ai && $ai['gstin']): ?><small>GSTIN <?php echo e($ai['gstin']); ?></small><?php endif; ?>
                </div>
            </div>
            <div class="nums">
                <div><b><?php echo (int)$o['prod_count']; ?></b><span>Products</span></div>
                <div><b><?php echo number_format((int)$o['units']); ?></b><span>Units</span></div>
                <div><b style="color:<?php echo $o['low'] > 0 ? 'var(--danger)' : 'var(--success)'; ?>"><?php echo (int)$o['low']; ?></b><span>Low / out</span></div>
            </div>
            <div style="font-size:13px;margin-bottom:12px;">Stock value: <strong>₹<?php echo number_format((float)$o['val'], 0); ?></strong></div>
            <div class="acts">
                <a class="btn btn-light btn-sm" href="inventory.php?admin=<?php echo (int)$o['id']; ?>">View stock</a>
                <a class="btn btn-primary btn-sm" href="billing.php?for=<?php echo (int)$o['id']; ?>">🧾 Create bill</a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($overview)): ?><div class="alert alert-info">No active admins yet.</div><?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card"><h3>Products</h3><p><?php echo $total_items; ?></p></div>
        <div class="stat-card"><h3>Total units</h3><p><?php echo number_format($total_units); ?></p></div>
        <div class="stat-card"><h3>Low stock (≤<?php echo LOW_STOCK_LIMIT; ?>)</h3><p style="color:var(--warn);"><?php echo $low_stock; ?></p></div>
        <div class="stat-card"><h3>Out of stock</h3><p style="color:var(--danger);"><?php echo $out_stock; ?></p></div>
        <div class="stat-card"><h3>Expired / ≤90 days</h3><p style="color:var(--danger);"><?php echo $exp_count; ?></p></div>
        <?php if (!$is_staff): ?><div class="stat-card"><h3>Stock value</h3><p style="color:var(--success);">₹<?php echo number_format($total_value, 0); ?></p></div><?php endif; ?>
    </div>

    <?php if ($is_super): ?>
    <!-- 1) add stock to MY warehouse: the only place a medicine name / batch is typed -->
    <div class="card assign-card" id="addForm">
        <div class="card-title">📥 Add stock to my warehouse</div>
        <form method="POST" id="addStockForm" autocomplete="off">
            <input type="hidden" name="back_admin" value="<?php echo $filter_admin; ?>">
            <div class="form-grid">
                <div><label>Medicine name</label><input type="text" name="name" id="f_name" list="nameList" placeholder="e.g. Paracetamol 500" required oninput="checkMatch();updateSummary()"></div>
                <div><label>Batch no.</label><input type="text" name="batch" id="f_batch" placeholder="Batch number" required oninput="checkMatch();updateSummary()"></div>
                <div><label>Expiry</label><input type="month" name="expiry" id="f_expiry" required></div>
                <div><label>Capacity / pack</label><input type="text" name="capacity" id="f_cap" placeholder="e.g. 10 Tabs"></div>
                <div><label>Rate per unit (₹)</label><input type="number" step="0.01" min="0" name="rate" id="f_rate" placeholder="0.00" required oninput="updateSummary()"></div>
                <div><label>Quantity to add</label><input type="number" min="0" step="1" name="stock_qty" id="f_qty" placeholder="0" inputmode="numeric" required oninput="updateSummary()"></div>
                <div><label>GST</label>
                    <select name="gst_percent" id="f_gst"><option value="5">5% GST</option><option value="12">12% GST</option><option value="18">18% GST</option><option value="0">Exempt</option></select></div>
            </div>
            <datalist id="nameList"></datalist>
            <div id="matchNote" class="note" style="display:none"></div>
            <div class="sum-bar" id="sumBar">Fill in the medicine to add it to your warehouse.</div>
            <div class="add-actions">
                <button type="submit" name="add_product" value="1" class="btn btn-primary" id="addBtn">＋ Add to warehouse</button>
                <span class="muted" style="font-size:12px;">Same name + batch already in your warehouse? The quantity is added to it. Then use “Assign to an admin” below to give it out.</span>
            </div>
        </form>
    </div>

    <!-- 2) assign from the warehouse to an admin: dropdowns only, nothing typed -->
    <div class="card assign-card" id="assignForm" style="border-color:#86efac;background:linear-gradient(180deg,#f0fdf4,#fff 120px)">
        <div class="card-title">🚚 Assign stock to an admin</div>
        <?php if (empty($my_products)): ?>
            <div class="alert alert-info" style="margin:0">Your warehouse is empty. Add stock above first, then you can assign it to an admin from here.</div>
        <?php elseif (empty($admins)): ?>
            <div class="alert alert-info" style="margin:0">No active admins yet. <a href="superadmin.php?tab=create">Create an admin</a> first.</div>
        <?php else: ?>
        <form method="POST" id="assignStockForm" autocomplete="off">
            <div class="step"><b>1</b> Pick the medicine &amp; batch from your warehouse</div>
            <select name="source_id" id="a_prod" required onchange="onAssignProduct()" style="width:100%;max-width:720px;font-weight:600">
                <option value="">— Select medicine · batch —</option>
                <?php foreach ($my_products as $mp):
                    $lbl = $mp['name'] . '  —  Batch ' . $mp['batch'] . ($mp['expiry'] ? '  —  Exp ' . $mp['expiry'] : '') . ($mp['capacity'] ? '  —  ' . $mp['capacity'] : '') . '  —  ' . (int)$mp['stock_qty'] . ' in stock'; ?>
                    <option value="<?php echo (int)$mp['id']; ?>" data-stock="<?php echo (int)$mp['stock_qty']; ?>" data-rate="<?php echo e($mp['rate']); ?>" data-name="<?php echo e($mp['name']); ?>" data-batch="<?php echo e($mp['batch']); ?>"><?php echo e($lbl); ?></option>
                <?php endforeach; ?>
            </select>

            <div class="step" style="margin-top:18px"><b>2</b> Which admin gets it?</div>
            <select name="to_admin" id="a_admin" required onchange="onAssignAdmin()" style="width:100%;max-width:720px;font-weight:600">
                <option value="">— Select the admin —</option>
                <?php foreach ($admins as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>" <?php echo ($filter_admin === (int)$a['id']) ? 'selected' : ''; ?>><?php echo e($a['full_name'] . ($a['company_name'] ? ' — ' . $a['company_name'] : '')); ?></option>
                <?php endforeach; ?>
            </select>
            <div id="adminInfo" class="adm-panel" style="display:none"></div>

            <div class="step" style="margin-top:18px"><b>3</b> How many?</div>
            <div class="form-grid" style="max-width:720px">
                <div><label>Quantity <span id="a_max" class="muted"></span></label><input type="number" name="qty" id="a_qty" min="1" step="1" inputmode="numeric" placeholder="0" required oninput="onAssignQty()"></div>
                <div><label>Rate for this admin (₹)</label><input type="number" name="rate" id="a_rate" min="0" step="0.01" placeholder="from warehouse" oninput="updateAssignSummary()"></div>
            </div>
            <div id="a_note" class="note" style="display:none"></div>
            <div class="sum-bar" id="a_sum">Pick a medicine and an admin.</div>
            <div class="add-actions">
                <button type="submit" name="assign_stock" value="1" class="btn btn-success" id="a_btn">🚚 Assign stock</button>
                <span class="muted" style="font-size:12px;">The quantity is taken out of your warehouse and added to the admin's stock. It can't exceed what you have.</span>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <?php if (!empty($recent_transfers)): ?>
    <div class="card">
        <div class="card-title">🕑 Recent assignments</div>
        <table class="data rt">
            <thead><tr><th>Date</th><th>Medicine</th><th>Batch</th><th>Qty</th><th>Rate</th><th>Assigned to</th></tr></thead>
            <tbody>
            <?php foreach ($recent_transfers as $t): ?>
                <tr>
                    <td data-label="Date"><?php echo e(date('d-M-Y H:i', strtotime($t['created_at']))); ?></td>
                    <td data-label="Medicine"><strong><?php echo e($t['product_name']); ?></strong></td>
                    <td data-label="Batch"><?php echo e($t['batch']); ?></td>
                    <td data-label="Qty"><strong><?php echo (int)$t['qty']; ?></strong></td>
                    <td data-label="Rate">₹<?php echo e($t['rate']); ?></td>
                    <td data-label="Assigned to"><span class="badge b-blue"><?php echo e($t['admin_name'] ?? '—'); ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">
            <span><?php echo $is_super ? ($in_warehouse ? '🏭 My warehouse' : ($filter_admin ? 'Stock — ' . e($filter_name) : 'All products (warehouse + all admins)')) : 'Your products'; ?></span>
            <?php if ($is_super && $filter_admin): ?>
                <span>
                    <?php if ($in_warehouse): ?><a class="btn btn-success btn-sm" href="#assignForm">🚚 Assign to an admin</a>
                    <?php else: ?><a class="btn btn-primary btn-sm" href="billing.php?for=<?php echo $filter_admin; ?>">🧾 Create bill for this admin</a><?php endif; ?>
                    <a class="btn btn-light btn-sm" href="inventory.php">← Everything</a>
                </span>
            <?php endif; ?>
        </div>
        <form method="GET" class="filter-row">
            <input type="hidden" name="per" value="<?php echo (int)$per; ?>">
            <?php if ($is_super): ?>
            <select name="admin" onchange="this.form.submit()">
                <option value="">Warehouse + all admins</option>
                <option value="<?php echo $my_id; ?>" <?php echo $in_warehouse ? 'selected' : ''; ?>>🏭 My warehouse</option>
                <?php foreach ($admins as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>" <?php echo $filter_admin === (int)$a['id'] ? 'selected' : ''; ?>><?php echo e($a['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <input type="search" name="q" value="<?php echo e($fq); ?>" class="search-bar" placeholder="🔍 Search medicine / batch...">
            <select name="status" onchange="this.form.submit()">
                <option value="">All status</option>
                <option value="out" <?php echo $fstatus === 'out' ? 'selected' : ''; ?>>Out of stock</option>
                <option value="low" <?php echo $fstatus === 'low' ? 'selected' : ''; ?>>Low stock</option>
                <option value="exp" <?php echo $fstatus === 'exp' ? 'selected' : ''; ?>>Expired / expiring</option>
            </select>
            <button class="btn btn-primary btn-sm">Search</button>
            <?php if ($fq !== '' || $fstatus !== ''): ?><a class="btn btn-light btn-sm" href="inventory.php<?php echo $filter_admin ? '?admin=' . $filter_admin : ''; ?>">Reset</a><?php endif; ?>
            <?php if (!$is_staff): ?>
            <span style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
                <a class="btn btn-excel btn-sm" href="export.php?type=stock<?php echo $export_qs ? '&' . e($export_qs) : ''; ?>" title="Download this stock list (with the search/filter applied)">⬇ Excel (this list)</a>
                <a class="btn btn-light btn-sm" href="export.php?type=all<?php echo $filter_admin ? '&admin=' . $filter_admin : ''; ?>" title="Stock + every invoice + items<?php echo $is_super ? ' + users' : ''; ?>">💾 Full backup</a>
            </span>
            <?php endif; ?>
        </form>
        <table class="data rt" id="inventoryTable">
            <thead><tr>
                <?php if ($is_super): ?><th>Admin</th><?php endif; ?>
                <th>Product</th><th>Batch</th><th>Expiry</th><th>Rate</th><th>Stock</th><?php if (!$is_staff): ?><th>Value</th><?php endif; ?><th>Status</th><th style="text-align:right">Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($page_products as $p):
                $q = (int)$p['stock_qty']; $ex = expiry_state($p['expiry']);
                $flag = ($q <= 0 ? 'out ' : ($q <= LOW_STOCK_LIMIT ? 'low ' : '')) . (in_array($ex, ['expired', 'soon'], true) ? 'exp' : '');
                $sv = $sold[(int)$p['id']] ?? null;
                $ai = $admin_info[(int)$p['admin_id']] ?? null;
                $detail = [
                    'id' => (int)$p['id'], 'admin_id' => (int)$p['admin_id'], 'warehouse' => ($is_super && (int)$p['admin_id'] === $my_id),
                    'admin' => ($is_super && (int)$p['admin_id'] === $my_id) ? 'My warehouse' : ($p['admin_name'] ?? ''),
                    'company' => $ai['company'] ?? '', 'gstin' => $ai['gstin'] ?? '',
                    'name' => $p['name'], 'batch' => $p['batch'], 'capacity' => $p['capacity'], 'expiry' => $p['expiry'], 'exp' => $ex,
                    'rate' => $p['rate'], 'gst' => $p['gst_percent'], 'stock' => max(0, $q),
                    'value' => $is_staff ? 0 : max(0, $q) * (float)$p['rate'],
                    'sold' => (int)($sv['sold'] ?? 0), 'last' => $sv ? date('d-M-Y', strtotime($sv['last_sold'])) : '—',
                ];
                $j = json_encode($detail, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG);
            ?>
                <tr data-flags="<?php echo trim($flag); ?>">
                    <?php if ($is_super): ?><td data-label="Admin"><?php if ((int)$p['admin_id'] === $my_id): ?><span class="badge b-green">🏭 Warehouse</span><?php else: ?><span class="badge b-blue"><?php echo e($p['admin_name'] ?? 'Unassigned'); ?></span><?php endif; ?></td><?php endif; ?>
                    <td data-label="Product"><div style="text-align:inherit"><a href="#" class="pname" onclick='openDetail(<?php echo $j; ?>);return false;'><?php echo e($p['name']); ?></a><br><small class="muted"><?php echo e($p['capacity']); ?></small></div></td>
                    <td data-label="Batch"><?php echo e($p['batch']); ?></td>
                    <td data-label="Expiry"><?php echo e($p['expiry']); ?>
                        <?php if ($ex === 'expired'): ?> <span class="badge b-red">Expired</span><?php elseif ($ex === 'soon'): ?> <span class="badge b-amber">Expiring</span><?php endif; ?></td>
                    <td data-label="Rate">₹<?php echo e($p['rate']); ?> <small class="muted">· GST <?php echo e(rtrim(rtrim(number_format((float)$p['gst_percent'], 2), '0'), '.')); ?>%</small></td>
                    <td data-label="Stock"><span class="stock-num"><?php echo max(0, $q); ?></span></td>
                    <?php if (!$is_staff): ?><td data-label="Value">₹<?php echo number_format(max(0, $q) * (float)$p['rate'], 0); ?></td><?php endif; ?>
                    <td data-label="Status">
                        <?php if ($q <= 0): ?><span class="badge b-red">Out of stock</span>
                        <?php elseif ($q <= LOW_STOCK_LIMIT): ?><span class="badge b-amber">Low stock</span>
                        <?php else: ?><span class="badge b-green">In stock</span><?php endif; ?>
                    </td>
                    <td data-label="Actions" class="act" style="text-align:right">
                        <button type="button" class="link" title="Details" onclick='openDetail(<?php echo $j; ?>)'>👁️</button>
                        <a href="?view_history=<?php echo (int)$p['id'] . $qs_admin; ?>" title="Sales history">📊</a>
                        <?php if ($is_super): ?>
                            <?php if ((int)$p['admin_id'] === $my_id && $q > 0): ?><button type="button" class="link" title="Assign to an admin" onclick="assignFromRow(<?php echo (int)$p['id']; ?>)">🚚</button><?php endif; ?>
                            <button type="button" class="link" title="Edit" onclick='openEdit(<?php echo $j; ?>)'>✏️</button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this product permanently?')">
                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                <input type="hidden" name="back_admin" value="<?php echo $filter_admin; ?>">
                                <button class="link" name="delete_product" value="1" title="Delete" style="color:var(--danger)">🗑️</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($page_products)): ?>
                <tr><td colspan="10" class="no-label" style="text-align:center;color:#94a3b8;padding:30px;"><?php echo ($fq !== '' || $fstatus !== '') ? 'No products match this search.' : 'No products found.'; ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php echo pager($total_rows, $page, $per); ?>
    </div>
</div>

<?php if ($is_super): ?>
<a href="#addForm" class="fab" title="Add stock to warehouse" onclick="setTimeout(()=>document.getElementById('f_name').focus(),400)">＋</a>

<div class="modal-bg" id="editModal" onclick="if(event.target===this)closeEdit()">
    <div class="modal">
        <h2>✏️ Edit product</h2>
        <div id="editTitle" class="muted" style="font-size:13px;margin:-8px 0 14px;"></div>
        <form method="POST" class="form-grid" style="grid-template-columns:1fr 1fr;">
            <input type="hidden" name="id" id="e_id">
            <input type="hidden" name="back_admin" value="<?php echo $filter_admin; ?>">
            <div><label>Stock quantity</label><input type="number" name="stock_qty" id="e_stock" min="0" step="1" inputmode="numeric" required></div>
            <div><label>Rate (₹)</label><input type="number" name="rate" id="e_rate" min="0" step="0.01" required></div>
            <div><label>Expiry</label><input type="month" name="expiry" id="e_expiry"></div>
            <div><label>GST %</label><input type="number" name="gst_percent" id="e_gst" min="0" max="100" step="0.01"></div>
            <div style="grid-column:1/-1"><label>Capacity / pack</label><input type="text" name="capacity" id="e_cap"></div>
            <div style="grid-column:1/-1;display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-light" onclick="closeEdit()">Cancel</button>
                <button type="submit" name="edit_product" value="1" class="btn btn-primary">💾 Save</button>
            </div>
        </form>
        <p class="muted" style="font-size:12px;margin:10px 0 0;">Stock can't be set below 0. Changing stock here does not create an invoice.</p>
    </div>
</div>
<?php endif; ?>

<div class="modal-bg" id="detailModal" onclick="if(event.target===this)closeDetail()">
    <div class="modal" style="max-width:600px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
            <div><h2 id="d_title" style="margin:0"></h2><div id="d_sub" class="muted" style="font-size:13px;margin-top:4px;"></div></div>
            <a href="#" onclick="closeDetail();return false;" style="font-size:26px;text-decoration:none;color:#64748b;line-height:1">&times;</a>
        </div>
        <div id="d_status" style="margin-top:10px"></div>
        <div class="dgrid" id="d_grid"></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap" id="d_acts"></div>
    </div>
</div>

<?php if ($view_history): ?>
<div class="modal-bg open">
    <div class="modal" style="max-width:760px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h2 style="margin:0">📊 Sales history</h2>
            <a href="inventory.php<?php echo $filter_admin ? '?admin=' . $filter_admin : ''; ?>" style="font-size:26px;text-decoration:none;color:#64748b;">&times;</a>
        </div>
        <div style="background:#f8fafc;padding:12px;border-radius:8px;margin:14px 0;">
            <strong><?php echo e($view_history['name']); ?></strong><br>
            <span class="muted" style="font-size:13px;">Batch: <?php echo e($view_history['batch']); ?> · Current stock: <strong><?php echo max(0, (int)$view_history['stock_qty']); ?></strong></span>
        </div>
        <?php if ($distribution_data): $total_dist = 0; ?>
            <table class="data rt">
                <thead><tr><th>Invoice</th><th>Customer</th><th>Qty sold</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($distribution_data as $d): $total_dist += (int)$d['qty']; ?>
                    <tr>
                        <td data-label="Invoice"><strong>#<?php echo e($d['invoice_no']); ?></strong></td>
                        <td data-label="Customer"><?php echo e($d['customer_name']); ?></td>
                        <td data-label="Qty sold" style="color:var(--danger);font-weight:700"><?php echo (int)$d['qty']; ?></td>
                        <td data-label="Date"><?php echo date('d-M-Y', strtotime($d['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="2" class="no-label"><strong>Total sold</strong></td><td class="no-label" style="color:var(--danger);font-weight:800"><?php echo $total_dist; ?></td><td></td></tr></tfoot>
            </table>
        <?php else: ?>
            <div style="text-align:center;padding:30px;color:#94a3b8;"><div style="font-size:42px;">📦</div>No sales yet for this product.</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money = n => '₹' + Number(n || 0).toLocaleString('en-IN', {maximumFractionDigits: 2});
const IS_SUPER = <?php echo $is_super ? 'true' : 'false'; ?>;
const IS_STAFF = <?php echo $is_staff ? 'true' : 'false'; ?>;
const QS_ADMIN = <?php echo json_encode($qs_admin); ?>;
const ADMINS = <?php echo json_encode((object)$admin_info, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const PRODS  = <?php echo json_encode($all_products_min, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

/* ---------- 1) add stock to MY warehouse ---------- */
const MY_ID = <?php echo (int)$my_id; ?>;
let matched = null;
const norm = s => String(s || '').trim().toLowerCase();

function fillNames() {
    const dl = $('nameList'), seen = new Set();
    dl.innerHTML = '';
    PRODS.filter(p => +p.admin_id === MY_ID).forEach(p => {
        if (seen.has(p.name)) return;
        seen.add(p.name);
        const o = document.createElement('option'); o.value = p.name; dl.appendChild(o);
    });
}

function checkMatch() {
    const n = norm($('f_name').value), b = norm($('f_batch').value), note = $('matchNote');
    matched = (n && b) ? (PRODS.find(p => +p.admin_id === MY_ID && norm(p.name) === n && norm(p.batch) === b) || null) : null;
    if (matched) {
        note.style.display = 'block';
        note.innerHTML = `ℹ️ This product + batch is already in your warehouse (current stock <b>${matched.stock_qty}</b>). The quantity below will be <b>added</b> to it, and rate / expiry / GST will be updated.`;
        if (!$('f_rate').value) $('f_rate').value = matched.rate;
        if (!$('f_cap').value) $('f_cap').value = matched.capacity || '';
        if (!$('f_expiry').value && /^\d{4}-\d{2}/.test(matched.expiry || '')) $('f_expiry').value = matched.expiry.substring(0, 7);
        const g = String(parseFloat(matched.gst_percent));
        if ([...$('f_gst').options].some(o => o.value === g)) $('f_gst').value = g;
    } else {
        note.style.display = 'none';
    }
}

function updateSummary() {
    const q = parseInt($('f_qty').value) || 0, r = parseFloat($('f_rate').value) || 0;
    $('sumBar').innerHTML = $('f_name').value.trim()
        ? `Adding <b>${q}</b> × ${money(r)} = <b>${money(q * r)}</b> to <b>your warehouse</b>` + (matched ? ` · new warehouse stock will be <b>${(+matched.stock_qty) + q}</b>` : ' · new product')
        : 'Fill in the medicine to add it to your warehouse.';
}

/* ---------- 2) assign from the warehouse to an admin (dropdowns only) ---------- */
function adminPanel(a) {
    return `<div class="adm-top"><div class="avatar">${esc(a.name.charAt(0).toUpperCase())}</div>
           <div><strong>${esc(a.name)}</strong> <small>@${esc(a.username)}</small><br>
           <span>${esc(a.company || 'Company not set')}</span>${a.gstin ? ' · GSTIN ' + esc(a.gstin) : ''}${a.phone ? ' · 📞 ' + esc(a.phone) : ''}</div></div>
         <div class="adm-nums">
           <div><b>${a.products}</b><span>Products</span></div>
           <div><b>${Number(a.units).toLocaleString('en-IN')}</b><span>Units</span></div>
           <div><b>${money(a.value)}</b><span>Stock value</span></div>
           <div><b style="color:${a.low ? '#dc2626' : '#16a34a'}">${a.low}</b><span>Low / out</span></div>
         </div>`;
}

function selProd() {
    const s = $('a_prod'); if (!s) return null;
    const o = s.selectedOptions[0];
    return (o && o.value) ? {id: +o.value, stock: +o.dataset.stock, rate: o.dataset.rate, name: o.dataset.name, batch: o.dataset.batch} : null;
}

function onAssignProduct() {
    const p = selProd(), q = $('a_qty');
    if (p) {
        q.max = p.stock;
        $('a_max').textContent = '(max ' + p.stock + ')';
        $('a_rate').value = p.rate;
        if ((+q.value || 0) > p.stock) q.value = p.stock;
    } else {
        q.removeAttribute('max'); $('a_max').textContent = ''; $('a_rate').value = '';
    }
    updateAssignSummary();
}

function onAssignAdmin() {
    const a = ADMINS[$('a_admin').value], box = $('adminInfo');
    if (a) { box.innerHTML = adminPanel(a); box.style.display = 'block'; } else { box.style.display = 'none'; box.innerHTML = ''; }
    updateAssignSummary();
}

function onAssignQty() {
    const p = selProd(), q = $('a_qty');
    if (p && (+q.value || 0) > p.stock) { q.value = p.stock; }
    if ((+q.value || 0) < 0) q.value = '';
    updateAssignSummary();
}

function updateAssignSummary() {
    const p = selProd(), a = ADMINS[$('a_admin').value], q = parseInt($('a_qty').value) || 0;
    const note = $('a_note'), bar = $('a_sum'), btn = $('a_btn');
    note.style.display = 'none';
    if (!p || !a) {
        bar.innerHTML = 'Pick a medicine and an admin.';
        btn.textContent = '🚚 Assign stock';
        return;
    }
    const ex = PRODS.find(x => +x.admin_id === +$('a_admin').value && norm(x.name) === norm(p.name) && norm(x.batch) === norm(p.batch));
    if (ex) {
        note.style.display = 'block';
        note.innerHTML = `ℹ️ ${esc(a.name)} already has <b>${ex.stock_qty}</b> unit(s) of this batch. The quantity will be <b>added</b> to it.`;
    }
    const r = parseFloat($('a_rate').value);
    bar.innerHTML = `Assigning <b>${q}</b> × <b>${esc(p.name)}</b> (batch ${esc(p.batch)}) to <b>${esc(a.name)}</b>${a.company ? ' (' + esc(a.company) + ')' : ''}` +
        ` · warehouse <b>${p.stock} → ${p.stock - q}</b>` + (ex ? ` · ${esc(a.name)} <b>${ex.stock_qty} → ${(+ex.stock_qty) + q}</b>` : '') +
        (isNaN(r) ? '' : ` · rate ${money(r)}`);
    btn.textContent = '🚚 Assign to ' + a.name;
}

if (IS_SUPER) {
    fillNames(); updateSummary();
    $('addStockForm').addEventListener('submit', e => {
        const q = parseInt($('f_qty').value) || 0;
        if (!confirm(`Add ${q} units of "${$('f_name').value.trim()}" (batch ${$('f_batch').value.trim()}) to your warehouse?`)) e.preventDefault();
    });
    const af = $('assignStockForm');
    if (af) {
        af.addEventListener('submit', e => {
            const p = selProd(), a = ADMINS[$('a_admin').value], q = parseInt($('a_qty').value) || 0;
            if (!p || !a) { e.preventDefault(); alert('Please pick the medicine and the admin.'); return; }
            if (q < 1 || q > p.stock) { e.preventDefault(); alert('Quantity must be between 1 and ' + p.stock + '.'); return; }
            if (!confirm(`Assign ${q} × ${p.name} (batch ${p.batch}) to ${a.name}${a.company ? ' — ' + a.company : ''}?`)) e.preventDefault();
        });
        onAssignAdmin();
    }
}
/* “Assign” shortcut from a warehouse row: pre-selects that product and jumps to the form */
function assignFromRow(id) {
    const s = $('a_prod'); if (!s) return;
    s.value = String(id); onAssignProduct();
    $('assignForm').scrollIntoView({behavior: 'smooth'});
    setTimeout(() => ($('a_admin').value ? $('a_qty') : $('a_admin')).focus(), 400);
}

/* ---------- edit modal ---------- */
function openEdit(p) {
    $('e_id').value = p.id;
    $('editTitle').textContent = p.name + ' · batch ' + p.batch + (p.admin ? ' · ' + p.admin : '');
    $('e_stock').value = p.stock;
    $('e_rate').value = p.rate;
    $('e_expiry').value = /^\d{4}-\d{2}/.test(p.expiry || '') ? p.expiry.substring(0, 7) : '';
    $('e_gst').value = p.gst;
    $('e_cap').value = p.capacity || '';
    closeDetail();
    $('editModal').classList.add('open');
}
function closeEdit() { $('editModal').classList.remove('open'); }

/* ---------- details modal ---------- */
function openDetail(p) {
    $('d_title').textContent = p.name;
    $('d_sub').textContent = 'Batch ' + p.batch + (p.capacity ? ' · ' + p.capacity : '') + ' · #' + p.id;
    let st = p.stock <= 0 ? '<span class="badge b-red">Out of stock</span>'
           : p.stock <= <?php echo LOW_STOCK_LIMIT; ?> ? '<span class="badge b-amber">Low stock</span>' : '<span class="badge b-green">In stock</span>';
    if (p.exp === 'expired') st += ' <span class="badge b-red">Expired</span>';
    else if (p.exp === 'soon') st += ' <span class="badge b-amber">Expiring within 90 days</span>';
    $('d_status').innerHTML = st;
    const cells = [
        ['Stock on hand', p.stock + ' units'], ['Rate / unit', money(p.rate)],
        ['GST', parseFloat(p.gst) + '%'], ['Expiry', p.expiry || '—'],
        ['Units sold (all time)', p.sold], ['Last sold', p.last],
    ];
    if (!IS_STAFF) cells.splice(1, 0, ['Stock value', money(p.value)]);
    if (p.admin) { cells.unshift([p.warehouse ? 'Location' : 'Assigned to', p.admin + (p.company ? ' — ' + p.company : '')]); if (p.gstin) cells.push(['Admin GSTIN', p.gstin]); }
    $('d_grid').innerHTML = cells.map(c => `<div class="dcell"><small>${esc(c[0])}</small><b>${esc(c[1])}</b></div>`).join('');
    let acts = `<a class="btn btn-light btn-sm" href="?view_history=${p.id}${QS_ADMIN}">📊 Sales history</a>`;
    if (IS_SUPER) {
        acts += ` <button class="btn btn-primary btn-sm" type="button" id="d_edit">✏️ Edit</button>`;
        if (p.warehouse) { if (p.stock > 0) acts += ` <button class="btn btn-success btn-sm" type="button" onclick="closeDetail();assignFromRow(${p.id})">🚚 Assign to an admin</button>`; }
        else if (p.admin_id) acts += ` <a class="btn btn-success btn-sm" href="billing.php?for=${p.admin_id}">🧾 Create bill for ${esc(p.admin)}</a>`;
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
