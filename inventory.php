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

/* ------------------------------------------------------------------
 * Superadmin actions (all POST). Stock is validated so it can never be negative.
 * ------------------------------------------------------------------ */
if ($is_super && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $back = 'inventory.php' . (!empty($_POST['back_admin']) ? '?admin=' . (int)$_POST['back_admin'] : '');

    // ADD PRODUCT (or top-up stock if the same product+batch already exists for that admin)
    if (isset($_POST['add_product'])) {
        $target = (int)($_POST['target_admin_id'] ?? 0);
        $name   = trim((string)($_POST['name'] ?? ''));
        $batch  = trim((string)($_POST['batch'] ?? ''));
        $qty    = $_POST['stock_qty'] ?? '';
        $rate   = $_POST['rate'] ?? '';
        $gst    = $_POST['gst_percent'] ?? 5;

        if (!in_array($target, $admin_ids, true))                    flash_set('error', 'Choose a valid admin to assign this stock to.');
        elseif ($name === '' || $batch === '')                       flash_set('error', 'Medicine name and batch are required.');
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
                flash_set('success', "Added $qty units to the existing stock of \"$name\" (batch $batch) for {$admin_names[$target]}.");
            } else {
                $pdo->prepare("INSERT INTO products (name,batch,expiry,capacity,rate,stock_qty,gst_percent,admin_id) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$name, $batch, $_POST['expiry'] ?? '', $_POST['capacity'] ?? '', $rate, $qty, $gst, $target]);
                flash_set('success', "\"$name\" (batch $batch) — $qty units assigned to {$admin_names[$target]}.");
            }
        }
        header("Location: $back"); exit;
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
$filter_admin = 0;
if ($is_super && isset($_GET['admin']) && in_array((int)$_GET['admin'], $admin_ids, true)) {
    $filter_admin = (int)$_GET['admin'];
}

$filter_name = '';
foreach ($admins as $a) { if ((int)$a['id'] === $filter_admin) $filter_name = $a['full_name']; }

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
            <p><?php echo $is_super ? 'Assign stock to admins, track levels and expiry, and bill on their behalf.' : ($is_staff ? 'Stock you can bill from — ' . e($biz_name) . '.' : 'Live stock levels, expiry and sales for ' . e($biz_name) . '.'); ?></p>
        </div>
        <?php if ($is_super): ?>
        <div class="ph-acts">
            <a class="btn btn-primary" href="#addForm" onclick="setTimeout(()=>document.getElementById('f_admin').focus(),400)">＋ Add stock</a>
            <a class="btn btn-light" href="billing.php<?php echo $filter_admin ? '?for=' . $filter_admin : ''; ?>">🧾 Create bill</a>
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
    <div class="card assign-card" id="addForm">
        <div class="card-title">➕ Add stock to an admin</div>
        <form method="POST" id="addStockForm" autocomplete="off">
            <input type="hidden" name="back_admin" value="<?php echo $filter_admin; ?>">

            <div class="step"><b>1</b> Who is this stock for?</div>
            <select name="target_admin_id" id="f_admin" required onchange="onAdminChange()">
                <option value="">— Select the admin to assign this stock to —</option>
                <?php foreach ($admins as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>" <?php echo $filter_admin === (int)$a['id'] ? 'selected' : ''; ?>>
                        <?php echo e($a['full_name'] . ($a['company_name'] ? ' — ' . $a['company_name'] : '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div id="adminInfo" class="adm-panel" style="display:none"></div>

            <div class="step" style="margin-top:18px"><b>2</b> Product details</div>
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
            <div class="sum-bar" id="sumBar">Choose the admin first, then fill in the product.</div>
            <div class="add-actions">
                <button type="submit" name="add_product" value="1" class="btn btn-primary" id="addBtn">＋ Assign stock</button>
                <span class="muted" style="font-size:12px;">Same name + batch for the same admin tops up the existing stock instead of creating a duplicate.</span>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">
            <span><?php echo $is_super ? ($filter_admin ? 'Stock — ' . e($filter_name) : 'All products (all admins)') : 'Your products'; ?></span>
            <?php if ($is_super && $filter_admin): ?>
                <span><a class="btn btn-primary btn-sm" href="billing.php?for=<?php echo $filter_admin; ?>">🧾 Create bill for this admin</a>
                      <a class="btn btn-light btn-sm" href="inventory.php">← All admins</a></span>
            <?php endif; ?>
        </div>
        <div class="filter-row">
            <?php if ($is_super): ?>
            <select onchange="location.href='inventory.php'+(this.value?'?admin='+this.value:'')">
                <option value="">All admins</option>
                <?php foreach ($admins as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>" <?php echo $filter_admin === (int)$a['id'] ? 'selected' : ''; ?>><?php echo e($a['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <input type="search" id="tableSearch" class="search-bar" placeholder="🔍 Search medicine / batch..." oninput="filterTable()">
            <select id="statusFilter" onchange="filterTable()">
                <option value="">All status</option><option value="out">Out of stock</option><option value="low">Low stock</option><option value="exp">Expired / expiring</option>
            </select>
            <span class="muted" style="font-size:13px;" id="rowCount"></span>
        </div>
        <table class="data rt" id="inventoryTable">
            <thead><tr>
                <?php if ($is_super): ?><th>Admin</th><?php endif; ?>
                <th>Product</th><th>Batch</th><th>Expiry</th><th>Rate</th><th>Stock</th><?php if (!$is_staff): ?><th>Value</th><?php endif; ?><th>Status</th><th style="text-align:right">Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($products as $p):
                $q = (int)$p['stock_qty']; $ex = expiry_state($p['expiry']);
                $flag = ($q <= 0 ? 'out ' : ($q <= LOW_STOCK_LIMIT ? 'low ' : '')) . (in_array($ex, ['expired', 'soon'], true) ? 'exp' : '');
                $sv = $sold[(int)$p['id']] ?? null;
                $ai = $admin_info[(int)$p['admin_id']] ?? null;
                $detail = [
                    'id' => (int)$p['id'], 'admin_id' => (int)$p['admin_id'], 'admin' => $p['admin_name'] ?? '',
                    'company' => $ai['company'] ?? '', 'gstin' => $ai['gstin'] ?? '',
                    'name' => $p['name'], 'batch' => $p['batch'], 'capacity' => $p['capacity'], 'expiry' => $p['expiry'], 'exp' => $ex,
                    'rate' => $p['rate'], 'gst' => $p['gst_percent'], 'stock' => max(0, $q),
                    'value' => $is_staff ? 0 : max(0, $q) * (float)$p['rate'],
                    'sold' => (int)($sv['sold'] ?? 0), 'last' => $sv ? date('d-M-Y', strtotime($sv['last_sold'])) : '—',
                ];
                $j = json_encode($detail, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG);
            ?>
                <tr data-flags="<?php echo trim($flag); ?>">
                    <?php if ($is_super): ?><td data-label="Admin"><span class="badge b-blue"><?php echo e($p['admin_name'] ?? 'Unassigned'); ?></span></td><?php endif; ?>
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
            <?php if (empty($products)): ?>
                <tr><td colspan="10" class="no-label" style="text-align:center;color:#94a3b8;padding:30px;">No products found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($is_super): ?>
<a href="#addForm" class="fab" title="Add stock" onclick="setTimeout(()=>document.getElementById('f_admin').focus(),400)">＋</a>

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

/* ---------- table filter ---------- */
function filterTable() {
    const q = $('tableSearch').value.toLowerCase(), f = $('statusFilter').value;
    let n = 0;
    document.querySelectorAll('#inventoryTable tbody tr[data-flags]').forEach(tr => {
        const ok = tr.textContent.toLowerCase().includes(q) && (!f || (tr.dataset.flags || '').split(' ').includes(f));
        tr.style.display = ok ? '' : 'none';
        if (ok) n++;
    });
    $('rowCount').textContent = n + ' shown';
}
filterTable();

/* ---------- add-stock form (superadmin) ---------- */
let matched = null;
const norm = s => String(s || '').trim().toLowerCase();

function onAdminChange() {
    const a = ADMINS[$('f_admin').value], box = $('adminInfo');
    if (!a) { box.style.display = 'none'; box.innerHTML = ''; fillNames(); checkMatch(); updateSummary(); return; }
    box.innerHTML =
        `<div class="adm-top"><div class="avatar">${esc(a.name.charAt(0).toUpperCase())}</div>
           <div><strong>${esc(a.name)}</strong> <small>@${esc(a.username)}</small><br>
           <span>${esc(a.company || 'Company not set')}</span>${a.gstin ? ' · GSTIN ' + esc(a.gstin) : ''}${a.phone ? ' · 📞 ' + esc(a.phone) : ''}</div></div>
         <div class="adm-nums">
           <div><b>${a.products}</b><span>Products</span></div>
           <div><b>${Number(a.units).toLocaleString('en-IN')}</b><span>Units</span></div>
           <div><b>${money(a.value)}</b><span>Stock value</span></div>
           <div><b style="color:${a.low ? '#dc2626' : '#16a34a'}">${a.low}</b><span>Low / out</span></div>
         </div>`;
    box.style.display = 'block';
    fillNames(); checkMatch(); updateSummary();
}

function fillNames() {
    const id = +$('f_admin').value, dl = $('nameList'), seen = new Set();
    dl.innerHTML = '';
    PRODS.filter(p => +p.admin_id === id).forEach(p => {
        if (seen.has(p.name)) return;
        seen.add(p.name);
        const o = document.createElement('option'); o.value = p.name; dl.appendChild(o);
    });
}

function checkMatch() {
    const id = +$('f_admin').value, n = norm($('f_name').value), b = norm($('f_batch').value), note = $('matchNote');
    matched = (id && n && b) ? (PRODS.find(p => +p.admin_id === id && norm(p.name) === n && norm(p.batch) === b) || null) : null;
    if (matched) {
        note.style.display = 'block';
        note.innerHTML = `ℹ️ This product + batch already exists for this admin (current stock <b>${matched.stock_qty}</b>). The quantity below will be <b>added</b> to it, and rate / expiry / GST will be updated.`;
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
    const a = ADMINS[$('f_admin').value];
    const q = parseInt($('f_qty').value) || 0, r = parseFloat($('f_rate').value) || 0;
    $('sumBar').innerHTML = a
        ? `Adding <b>${q}</b> × ${money(r)} = <b>${money(q * r)}</b> to <b>${esc(a.name)}</b>${a.company ? ' (' + esc(a.company) + ')' : ''}` +
          (matched ? ` · new stock will be <b>${(+matched.stock_qty) + q}</b>` : ' · new product')
        : 'Choose the admin first, then fill in the product.';
    $('addBtn').textContent = a ? '＋ Assign to ' + a.name : '＋ Assign stock';
}

if (IS_SUPER) {
    $('addStockForm').addEventListener('submit', e => {
        const a = ADMINS[$('f_admin').value];
        if (!a) { e.preventDefault(); alert('Please choose the admin first.'); return; }
        const q = parseInt($('f_qty').value) || 0;
        if (!confirm(`Add ${q} units of "${$('f_name').value.trim()}" (batch ${$('f_batch').value.trim()}) to ${a.name}${a.company ? ' — ' + a.company : ''}?`)) e.preventDefault();
    });
    onAdminChange();
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
    if (p.admin) { cells.unshift(['Assigned to', p.admin + (p.company ? ' — ' + p.company : '')]); if (p.gstin) cells.push(['Admin GSTIN', p.gstin]); }
    $('d_grid').innerHTML = cells.map(c => `<div class="dcell"><small>${esc(c[0])}</small><b>${esc(c[1])}</b></div>`).join('');
    let acts = `<a class="btn btn-light btn-sm" href="?view_history=${p.id}${QS_ADMIN}">📊 Sales history</a>`;
    if (IS_SUPER) {
        acts += ` <button class="btn btn-primary btn-sm" type="button" id="d_edit">✏️ Edit</button>`;
        if (p.admin_id) acts += ` <a class="btn btn-success btn-sm" href="billing.php?for=${p.admin_id}">🧾 Create bill for ${esc(p.admin)}</a>`;
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
