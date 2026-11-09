<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','superadmin'])) {
    header("Location: login.php");
    exit;
}

$role     = $_SESSION['role'];
$admin_id = ($role === 'admin') ? $_SESSION['user_id'] : null;

// ADD PRODUCT — superadmin only
if ($role === 'superadmin' && isset($_POST['add_product'])) {
    $target_admin = (int)$_POST['target_admin_id'];
    $name  = trim($_POST['name']);
    $batch = trim($_POST['batch']);
    $addQty = (int)$_POST['stock_qty'];

    // Same product+batch already exists for this admin? Add to its stock
    // instead of creating a duplicate row.
    $existing = $pdo->prepare("SELECT id, stock_qty FROM products WHERE admin_id=? AND name=? AND batch=?");
    $existing->execute([$target_admin, $name, $batch]);
    $existingRow = $existing->fetch();

    if ($existingRow) {
        $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ?, expiry=?, capacity=?, rate=?, gst_percent=? WHERE id=?")
            ->execute([$addQty, $_POST['expiry'], $_POST['capacity'], $_POST['rate'], $_POST['gst_percent'], $existingRow['id']]);
        header("Location: inventory.php?merged=1");
    } else {
        $pdo->prepare("INSERT INTO products (name,batch,expiry,capacity,rate,stock_qty,gst_percent,admin_id) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$name,$batch,$_POST['expiry'],$_POST['capacity'],$_POST['rate'],$addQty,$_POST['gst_percent'],$target_admin]);
        header("Location: inventory.php?success=1");
    }
    exit;
}

// MERGE two products (fold source's stock into target, remove source) — superadmin only
if ($role === 'superadmin' && isset($_POST['merge_products'])) {
    $keepId = (int)$_POST['keep_id'];
    $mergeId = (int)$_POST['merge_id'];
    if ($keepId && $mergeId && $keepId !== $mergeId) {
        $pdo->beginTransaction();
        try {
            $keep = $pdo->prepare("SELECT * FROM products WHERE id=?"); $keep->execute([$keepId]); $keep = $keep->fetch();
            $merge = $pdo->prepare("SELECT * FROM products WHERE id=?"); $merge->execute([$mergeId]); $merge = $merge->fetch();
            if ($keep && $merge && $keep['admin_id'] == $merge['admin_id']) {
                $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id=?")->execute([$merge['stock_qty'], $keepId]);
                $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$mergeId]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    header("Location: inventory.php?merged=1");
    exit;
}

// DELETE PRODUCT — superadmin only
if ($role === 'superadmin' && isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$_GET['delete']]);
    header("Location: inventory.php?deleted=1");
    exit;
}

// VIEW DISTRIBUTION HISTORY
$view_history = null;
$distribution_data = [];
if (isset($_GET['view_history'])) {
    $pid = (int)$_GET['view_history'];
    $q = ($role === 'admin')
        ? $pdo->prepare("SELECT * FROM products WHERE id=? AND admin_id=?")
        : $pdo->prepare("SELECT * FROM products WHERE id=?");
    $role === 'admin' ? $q->execute([$pid, $admin_id]) : $q->execute([$pid]);
    $view_history = $q->fetch();
    if ($view_history) {
        $stmt = $pdo->prepare("SELECT ii.qty,ii.created_at,i.invoice_no,i.customer_name FROM invoice_items ii JOIN invoices i ON ii.invoice_id=i.id WHERE ii.product_name=? AND ii.batch=? AND i.status=1 ORDER BY ii.created_at DESC");
        $stmt->execute([$view_history['name'],$view_history['batch']]);
        $distribution_data = $stmt->fetchAll();
    }
}

// FETCH PRODUCTS
if ($role === 'admin') {
    $products = $pdo->prepare("SELECT * FROM products WHERE admin_id=? ORDER BY id DESC");
    $products->execute([$admin_id]);
    $products = $products->fetchAll();
} else {
    // superadmin sees all, grouped by admin
    $products = $pdo->query("SELECT p.*, u.full_name as admin_name FROM products p LEFT JOIN users u ON p.admin_id=u.id ORDER BY p.admin_id, p.id DESC")->fetchAll();
}

// Stats
$total_items = count($products);
$low_stock = 0; $total_value = 0;
foreach($products as $p) {
    if($p['stock_qty'] < 10) $low_stock++;
    $total_value += ($p['rate'] * $p['stock_qty']);
}

// POSSIBLE DUPLICATES — same admin + same name/batch once case & spacing
// are ignored, but not byte-identical (so not already merged automatically).
// Superadmin reviews these and merges manually so two genuinely different
// products never get combined by mistake.
$possible_duplicates = [];
if ($role === 'superadmin') {
    $dupRows = $pdo->query("
        SELECT admin_id, LOWER(TRIM(name)) AS norm_name, LOWER(TRIM(batch)) AS norm_batch, COUNT(*) AS cnt
        FROM products
        GROUP BY admin_id, norm_name, norm_batch
        HAVING COUNT(*) > 1
    ")->fetchAll();
    foreach ($dupRows as $d) {
        $grp = $pdo->prepare("SELECT p.*, u.full_name AS admin_name FROM products p LEFT JOIN users u ON p.admin_id=u.id WHERE p.admin_id <=> ? AND LOWER(TRIM(p.name))=? AND LOWER(TRIM(p.batch))=? ORDER BY p.id");
        $grp->execute([$d['admin_id'], $d['norm_name'], $d['norm_batch']]);
        $possible_duplicates[] = $grp->fetchAll();
    }
}

// For superadmin add form: list of admins
$admins = [];
if ($role === 'superadmin') {
    $admins = $pdo->query("SELECT id,full_name FROM users WHERE role='admin' AND IsActive=1")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | RUSHIKESH PHARMA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#2563eb; --success:#16a34a; --danger:#ef4444; --bg:#f8fafc; --dark:#0f172a; }
        body { font-family:'Inter',sans-serif; margin:0; background:var(--bg); color:var(--dark); }
        nav { background:var(--dark); padding:15px 40px; display:flex; justify-content:space-between; align-items:center; color:white; position:sticky; top:0; z-index:1000; }
        .nav-links a { color:#94a3b8; text-decoration:none; margin-left:20px; font-size:14px; font-weight:500; transition:0.3s; }
        .nav-links a.active { color:white; border-bottom:2px solid var(--primary); padding-bottom:5px; }
        .nav-links a:hover { color:white; }
        .container { max-width:1200px; margin:40px auto; padding:0 20px; }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; margin-bottom:30px; }
        .stat-card { background:white; padding:25px; border-radius:16px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05); border:1px solid #e2e8f0; }
        .stat-card h3 { margin:0; font-size:13px; color:#64748b; text-transform:uppercase; }
        .stat-card p { margin:10px 0 0; font-size:28px; font-weight:800; }
        .card { background:white; border-radius:16px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.05); padding:30px; margin-bottom:30px; border:1px solid #e2e8f0; }
        .section-title { font-size:20px; font-weight:700; margin-bottom:25px; display:flex; justify-content:space-between; align-items:center; }
        .form-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:15px; }
        input,select { padding:12px; border:1px solid #cbd5e1; border-radius:8px; font-family:inherit; font-size:14px; }
        .btn { padding:12px 20px; border-radius:8px; font-weight:600; cursor:pointer; border:none; transition:0.2s; }
        .btn-primary { background:var(--primary); color:white; }
        .btn-primary:hover { background:#1d4ed8; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th { background:#f1f5f9; padding:15px; text-align:left; font-size:12px; text-transform:uppercase; color:#475569; }
        td { padding:15px; border-bottom:1px solid #f1f5f9; font-size:14px; }
        tr:hover { background:#f8fafc; }
        .badge { padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700; }
        .badge-success { background:#dcfce7; color:#166534; }
        .badge-danger { background:#fee2e2; color:#991b1b; }
        .badge-info { background:#dbeafe; color:#1e40af; }
        .search-bar { width:100%; max-width:300px; margin-bottom:20px; padding:10px; border:1px solid #e2e8f0; border-radius:8px; }
        .readonly-notice { background:#fef9c3; border:1px solid #fde047; padding:12px 20px; border-radius:10px; margin-bottom:20px; font-size:14px; color:#713f12; }
        @media(max-width:768px){
            nav{padding:15px 20px;flex-wrap:wrap;}
            .nav-links{width:100%;text-align:center;margin-top:8px;}
            .nav-links a{margin:0 8px;font-size:12px;}
            .container{padding:0 10px;margin:20px auto;}
            .stats-grid{grid-template-columns:1fr;}
            .form-grid{grid-template-columns:1fr;}
            table{display:block;overflow-x:auto;}
            th,td{padding:10px 8px;font-size:11px;white-space:nowrap;}
        }
    </style>
</head>
<body>
<nav>
    <div style="font-weight:800;font-size:20px;letter-spacing:-1px;">
        RUSHIKESH PHARMA
        <span style="font-weight:300;font-size:14px;color:#94a3b8;">
            <?php echo $role === 'superadmin' ? 'Super Admin' : 'Admin Portal'; ?>
        </span>
    </div>
    <div class="nav-links">
        <?php if($role === 'superadmin'): ?>
            <a href="superadmin.php">Dashboard</a>
        <?php endif; ?>
        <a href="inventory.php" class="active">Inventory</a>
        <?php if($role === 'admin'): ?>
            <a href="billing.php">Billing</a>
            <a href="reports.php">Reports</a>
        <?php endif; ?>
        <a href="logout.php" style="color:#f87171;">Logout</a>
    </div>
</nav>

<div class="container">

    <?php if($role === 'admin'): ?>
    <div class="readonly-notice">
        📦 <strong>Stock View Only</strong> — You can view your inventory. Contact Super Admin to add or remove products.
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['success'])): ?>
        <div style="background:#dcfce7;border:1px solid #86efac;padding:12px 20px;border-radius:10px;margin-bottom:20px;color:#166534;">✅ Product added successfully!</div>
    <?php endif; ?>
    <?php if(isset($_GET['merged'])): ?>
        <div style="background:#dcfce7;border:1px solid #86efac;padding:12px 20px;border-radius:10px;margin-bottom:20px;color:#166534;">✅ Stock merged into the existing product — no duplicate row created.</div>
    <?php endif; ?>

    <?php if($role === 'superadmin' && !empty($possible_duplicates)): ?>
    <div class="card" style="border:1px solid #fde047;background:#fffbeb;">
        <div class="section-title">⚠️ Possible Duplicate Products (<?php echo count($possible_duplicates); ?>)</div>
        <p style="color:#713f12;font-size:13px;margin-top:-15px;margin-bottom:20px;">These look like the same medicine/batch but aren't an exact text match (different spelling or capitalization), so they were <strong>not</strong> merged automatically. Review each group and merge only if they really are the same product.</p>
        <?php foreach($possible_duplicates as $grp): ?>
            <table style="margin-bottom:20px;">
                <thead><tr><th>Admin</th><th>Name</th><th>Batch</th><th>Stock</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach($grp as $i => $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['admin_name'] ?? 'Unassigned'); ?></td>
                        <td><strong>"<?php echo htmlspecialchars($p['name']); ?>"</strong></td>
                        <td>"<?php echo htmlspecialchars($p['batch']); ?>"</td>
                        <td><?php echo $p['stock_qty']; ?></td>
                        <td>
                            <?php if($i === 0): ?>
                                <span style="color:#64748b;font-size:12px;">Keep this one</span>
                            <?php else: ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Merge stock of &quot;<?php echo htmlspecialchars(addslashes($p['name'])); ?>&quot; into &quot;<?php echo htmlspecialchars(addslashes($grp[0]['name'])); ?>&quot;? This cannot be undone.');">
                                    <input type="hidden" name="keep_id" value="<?php echo $grp[0]['id']; ?>">
                                    <input type="hidden" name="merge_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" name="merge_products" class="btn" style="background:#d97706;color:white;font-size:12px;padding:6px 12px;">Merge into "<?php echo htmlspecialchars($grp[0]['name']); ?>"</button>
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

    <div class="stats-grid">
        <div class="stat-card"><h3>Total Products</h3><p><?php echo $total_items; ?></p></div>
        <div class="stat-card"><h3>Low Stock Items</h3><p style="color:var(--danger);"><?php echo $low_stock; ?></p></div>
        <div class="stat-card"><h3>Inventory Value</h3><p style="color:var(--success);">₹<?php echo number_format($total_value,2); ?></p></div>
    </div>

    <?php if($role === 'superadmin'): ?>
    <!-- ADD PRODUCT FORM — superadmin only -->
    <div class="card">
        <div class="section-title">Add New Inventory Item</div>
        <form method="POST" class="form-grid">
            <select name="target_admin_id" required>
                <option value="">-- Assign to Admin --</option>
                <?php foreach($admins as $a): ?>
                    <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="name" placeholder="Medicine Name" required>
            <input type="text" name="batch" placeholder="Batch Number" required>
            <input type="month" name="expiry" required>
            <input type="text" name="capacity" placeholder="Capacity (e.g. 10 Tabs)" required>
            <input type="number" step="0.01" name="rate" placeholder="Unit Rate (₹)" required>
            <input type="number" name="stock_qty" placeholder="Stock Quantity" required>
            <select name="gst_percent">
                <option value="5">5% GST</option>
                <option value="12">12% GST</option>
                <option value="18">18% GST</option>
                <option value="0">Exempted</option>
            </select>
            <button type="submit" name="add_product" class="btn btn-primary">+ Add Product</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- PRODUCT TABLE -->
    <div class="card">
        <div class="section-title">
            <?php echo $role === 'superadmin' ? 'All Stock (All Admins)' : 'Your Stock Levels'; ?>
            <input type="text" id="tableSearch" class="search-bar" placeholder="Filter medicines..." onkeyup="filterTable()">
        </div>
        <table id="inventoryTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <?php if($role === 'superadmin'): ?><th>Admin</th><?php endif; ?>
                    <th>Product Name</th>
                    <th>Batch</th>
                    <th>Expiry</th>
                    <th>Rate</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($products as $p): ?>
                <tr>
                    <td>#<?php echo $p['id']; ?></td>
                    <?php if($role === 'superadmin'): ?>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($p['admin_name'] ?? 'Unassigned'); ?></span></td>
                    <?php endif; ?>
                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong><br><small style="color:#64748b"><?php echo $p['capacity']; ?></small></td>
                    <td><?php echo $p['batch']; ?></td>
                    <td><?php echo $p['expiry']; ?></td>
                    <td>₹<?php echo $p['rate']; ?></td>
                    <td><strong><?php echo $p['stock_qty']; ?></strong></td>
                    <td>
                        <?php if($p['stock_qty'] > 10): ?>
                            <span class="badge badge-success">In Stock</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Low Stock</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?view_history=<?php echo $p['id']; ?>" style="color:var(--primary);text-decoration:none;margin-right:10px;" title="Distribution History">📊</a>
                        <?php if($role === 'superadmin'): ?>
                            <a href="?delete=<?php echo $p['id']; ?>" onclick="return confirm('Confirm Delete?')" style="color:var(--danger);font-size:18px;text-decoration:none;">&times;</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterTable() {
    let filter = document.getElementById("tableSearch").value.toUpperCase();
    let tr = document.getElementById("inventoryTable").getElementsByTagName("tr");
    for (let i = 1; i < tr.length; i++) {
        let td = tr[i].getElementsByTagName("td")[<?php echo $role === 'superadmin' ? 2 : 1; ?>];
        if (td) tr[i].style.display = td.textContent.toUpperCase().indexOf(filter) > -1 ? "" : "none";
    }
}
</script>

<?php if($view_history): ?>
<div style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;">
    <div class="card" style="max-width:800px;width:90%;max-height:80vh;overflow-y:auto;margin:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="margin:0;font-size:20px;">📊 Distribution History</h2>
            <a href="inventory.php" style="font-size:24px;text-decoration:none;color:#64748b;">&times;</a>
        </div>
        <div style="background:#f8fafc;padding:15px;border-radius:8px;margin-bottom:20px;">
            <strong><?php echo htmlspecialchars($view_history['name']); ?></strong><br>
            <span style="color:#64748b;font-size:13px;">Batch: <?php echo $view_history['batch']; ?> | Current Stock: <strong><?php echo $view_history['stock_qty']; ?></strong></span>
        </div>
        <?php if(count($distribution_data) > 0): ?>
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr style="background:#f1f5f9;">
                    <th style="padding:12px;text-align:left;">Invoice No</th>
                    <th style="padding:12px;text-align:left;">Customer</th>
                    <th style="padding:12px;text-align:center;">Qty Sold</th>
                    <th style="padding:12px;text-align:left;">Date</th>
                </tr></thead>
                <tbody>
                    <?php $total_dist=0; foreach($distribution_data as $d): $total_dist+=$d['qty']; ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px;"><strong>#<?php echo $d['invoice_no']; ?></strong></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($d['customer_name']); ?></td>
                        <td style="padding:12px;text-align:center;font-weight:bold;color:var(--danger);"><?php echo $d['qty']; ?></td>
                        <td style="padding:12px;"><?php echo date('d-M-Y',strtotime($d['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr style="background:#f8fafc;font-weight:bold;">
                    <td colspan="2" style="padding:12px;border-top:2px solid #e2e8f0;">Total Distributed</td>
                    <td style="padding:12px;text-align:center;border-top:2px solid #e2e8f0;color:var(--danger);"><?php echo $total_dist; ?></td>
                    <td style="padding:12px;border-top:2px solid #e2e8f0;"></td>
                </tr></tfoot>
            </table>
        <?php else: ?>
            <div style="text-align:center;padding:40px;color:#94a3b8;"><div style="font-size:48px;">📦</div><p>No distribution history found.</p></div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
</body>
</html>
