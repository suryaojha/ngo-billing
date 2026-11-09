<?php
session_start();
require 'config.php';
require 'ui.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'], true)) {
    header("Location: login.php");
    exit;
}

$role     = $_SESSION['role'];
$is_super = ($role === 'superadmin');
$admin_id = $is_super ? null : (int)$_SESSION['user_id'];

// Active admins (businesses) — used by the superadmin forms/filters.
$admins = [];
if ($is_super) {
    $admins = $pdo->query("SELECT id, full_name, company_name FROM users WHERE role='admin' AND IsActive=1 ORDER BY full_name")->fetchAll();
}
$admin_ids = array_map('intval', array_column($admins, 'id'));

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
                flash_set('success', "Added $qty to the existing stock of \"$name\" (batch $batch).");
            } else {
                $pdo->prepare("INSERT INTO products (name,batch,expiry,capacity,rate,stock_qty,gst_percent,admin_id) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$name, $batch, $_POST['expiry'] ?? '', $_POST['capacity'] ?? '', $rate, $qty, $gst, $target]);
                flash_set('success', "Product \"$name\" added.");
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

// Per-admin overview (superadmin, "all admins" view)
$overview = [];
if ($is_super && !$filter_admin) {
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
                            WHERE i.status=1 AND i.admin_id <=> ?
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
.admin-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;margin-bottom:22px}
.admin-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px}
.admin-card h4{margin:0 0 2px;font-size:16px}.admin-card small{color:var(--muted)}
.admin-card .nums{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin:12px 0;text-align:center}
.admin-card .nums b{display:block;font-size:17px}.admin-card .nums span{font-size:10px;color:var(--muted);text-transform:uppercase}
.admin-card .acts{display:flex;gap:8px;flex-wrap:wrap}
.filter-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.act a,.act button.link{background:none;border:none;cursor:pointer;font-size:17px;text-decoration:none;padding:4px 6px}
.stock-num{font-weight:800;font-size:15px}
@media(max-width:768px){
 .filter-row select,.filter-row input{width:100%}
 .act{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
}
</style>
</head>
<body>
<?php echo ui_nav('stock'); ?>
<div class="container">
    <?php echo flash_show(); ?>

    <?php if (!$is_super): ?>
        <div class="alert alert-info">📦 <strong>Your stock.</strong> To add or change products, contact the Super Admin.</div>
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
                            <?php if ($i === 0): ?><span style="color:var(--muted);font-size:12px;">Keep this one</span>
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
    <h3 style="margin:0 0 12px;">All admins — stock overview</h3>
    <div class="admin-cards">
        <?php foreach ($overview as $o): ?>
        <div class="admin-card">
            <h4><?php echo e($o['full_name']); ?></h4>
            <small><?php echo e($o['company_name'] ?: 'Company not set'); ?></small>
            <div class="nums">
                <div><b><?php echo (int)$o['prod_count']; ?></b><span>Products</span></div>
                <div><b><?php echo number_format((int)$o['units']); ?></b><span>Units</span></div>
                <div><b style="color:<?php echo $o['low'] > 0 ? 'var(--danger)' : 'var(--success)'; ?>"><?php echo (int)$o['low']; ?></b><span>Low/Out</span></div>
            </div>
            <div style="font-size:13px;margin-bottom:10px;">Stock value: <strong>₹<?php echo number_format((float)$o['val'], 0); ?></strong></div>
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
        <div class="stat-card"><h3>Stock value</h3><p style="color:var(--success);">₹<?php echo number_format($total_value, 0); ?></p></div>
    </div>

    <?php if ($is_super): ?>
    <div class="card">
        <div class="card-title">➕ Add stock to an admin</div>
        <form method="POST" class="form-grid">
            <input type="hidden" name="back_admin" value="<?php echo $filter_admin; ?>">
            <div><label>Admin</label>
                <select name="target_admin_id" required>
                    <option value="">-- Assign to admin --</option>
                    <?php foreach ($admins as $a): ?>
                        <option value="<?php echo (int)$a['id']; ?>" <?php echo $filter_admin === (int)$a['id'] ? 'selected' : ''; ?>><?php echo e($a['full_name']); ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Medicine name</label><input type="text" name="name" placeholder="Medicine name" required></div>
            <div><label>Batch</label><input type="text" name="batch" placeholder="Batch number" required></div>
            <div><label>Expiry</label><input type="month" name="expiry" required></div>
            <div><label>Capacity / pack</label><input type="text" name="capacity" placeholder="e.g. 10 Tabs"></div>
            <div><label>Rate (₹)</label><input type="number" step="0.01" min="0" name="rate" placeholder="0.00" required></div>
            <div><label>Quantity</label><input type="number" min="0" step="1" name="stock_qty" placeholder="0" inputmode="numeric" required></div>
            <div><label>GST</label>
                <select name="gst_percent"><option value="5">5% GST</option><option value="12">12% GST</option><option value="18">18% GST</option><option value="0">Exempt</option></select></div>
            <div style="grid-column:1/-1;"><button type="submit" name="add_product" value="1" class="btn btn-primary">＋ Add / top-up stock</button>
                <span style="font-size:12px;color:var(--muted);margin-left:8px;">Same name + batch for the same admin adds to the existing stock.</span></div>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">
            <span><?php echo $is_super ? ($filter_admin ? 'Stock — ' . e($filter_name) : 'All stock (all admins)') : 'Your stock levels'; ?></span>
            <?php if ($is_super && $filter_admin): ?>
                <span><a class="btn btn-primary btn-sm" href="billing.php?for=<?php echo $filter_admin; ?>">🧾 Create bill for this admin</a></span>
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
        </div>
        <table class="data rt" id="inventoryTable">
            <thead><tr>
                <?php if ($is_super): ?><th>Admin</th><?php endif; ?>
                <th>Product</th><th>Batch</th><th>Expiry</th><th>Rate</th><th>Stock</th><th>Status</th><th style="text-align:right">Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($products as $p):
                $q = (int)$p['stock_qty']; $ex = expiry_state($p['expiry']);
                $flag = ($q <= 0 ? 'out ' : ($q <= LOW_STOCK_LIMIT ? 'low ' : '')) . (in_array($ex, ['expired', 'soon'], true) ? 'exp' : '');
            ?>
                <tr data-flags="<?php echo trim($flag); ?>">
                    <?php if ($is_super): ?><td data-label="Admin"><span class="badge b-blue"><?php echo e($p['admin_name'] ?? 'Unassigned'); ?></span></td><?php endif; ?>
                    <td data-label="Product"><div style="text-align:right;text-align:inherit"><strong><?php echo e($p['name']); ?></strong><br><small style="color:var(--muted)"><?php echo e($p['capacity']); ?></small></div></td>
                    <td data-label="Batch"><?php echo e($p['batch']); ?></td>
                    <td data-label="Expiry"><?php echo e($p['expiry']); ?>
                        <?php if ($ex === 'expired'): ?> <span class="badge b-red">Expired</span><?php elseif ($ex === 'soon'): ?> <span class="badge b-amber">Expiring</span><?php endif; ?></td>
                    <td data-label="Rate">₹<?php echo e($p['rate']); ?></td>
                    <td data-label="Stock"><span class="stock-num"><?php echo max(0, $q); ?></span></td>
                    <td data-label="Status">
                        <?php if ($q <= 0): ?><span class="badge b-red">Out of stock</span>
                        <?php elseif ($q <= LOW_STOCK_LIMIT): ?><span class="badge b-amber">Low stock</span>
                        <?php else: ?><span class="badge b-green">In stock</span><?php endif; ?>
                    </td>
                    <td data-label="Actions" class="act" style="text-align:right">
                        <a href="?view_history=<?php echo (int)$p['id'] . $qs_admin; ?>" title="Sales history">📊</a>
                        <?php if ($is_super): ?>
                            <button type="button" class="link" title="Edit" onclick='openEdit(<?php echo json_encode(['id' => (int)$p['id'], 'name' => $p['name'], 'batch' => $p['batch'], 'expiry' => $p['expiry'], 'capacity' => $p['capacity'], 'rate' => $p['rate'], 'gst' => $p['gst_percent'], 'stock' => max(0, $q)], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG); ?>)'>✏️</button>
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
                <tr><td colspan="9" class="no-label" style="text-align:center;color:#94a3b8;padding:30px;">No products found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($is_super): ?>
<div class="modal-bg" id="editModal" onclick="if(event.target===this)closeEdit()">
    <div class="modal">
        <h2>✏️ Edit product</h2>
        <div id="editTitle" style="color:var(--muted);font-size:13px;margin:-8px 0 14px;"></div>
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
        <p style="font-size:12px;color:var(--muted);margin:10px 0 0;">Stock can't be set below 0. Changing stock here does not create an invoice.</p>
    </div>
</div>
<?php endif; ?>

<?php if ($view_history): ?>
<div class="modal-bg open">
    <div class="modal" style="max-width:760px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h2 style="margin:0">📊 Sales history</h2>
            <a href="inventory.php<?php echo $filter_admin ? '?admin=' . $filter_admin : ''; ?>" style="font-size:26px;text-decoration:none;color:#64748b;">&times;</a>
        </div>
        <div style="background:#f8fafc;padding:12px;border-radius:8px;margin:14px 0;">
            <strong><?php echo e($view_history['name']); ?></strong><br>
            <span style="color:var(--muted);font-size:13px;">Batch: <?php echo e($view_history['batch']); ?> · Current stock: <strong><?php echo max(0, (int)$view_history['stock_qty']); ?></strong></span>
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
function filterTable() {
    const q = document.getElementById('tableSearch').value.toLowerCase();
    const f = document.getElementById('statusFilter').value;
    document.querySelectorAll('#inventoryTable tbody tr').forEach(tr => {
        const flags = (tr.dataset.flags || '');
        const okText = tr.textContent.toLowerCase().includes(q);
        const okFlag = !f || flags.split(' ').includes(f);
        tr.style.display = (okText && okFlag) ? '' : 'none';
    });
}
function openEdit(p) {
    document.getElementById('e_id').value = p.id;
    document.getElementById('editTitle').textContent = p.name + ' · batch ' + p.batch;
    document.getElementById('e_stock').value = p.stock;
    document.getElementById('e_rate').value = p.rate;
    document.getElementById('e_expiry').value = /^\d{4}-\d{2}/.test(p.expiry || '') ? p.expiry.substring(0, 7) : '';
    document.getElementById('e_gst').value = p.gst;
    document.getElementById('e_cap').value = p.capacity || '';
    document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
</script>
</body>
</html>
