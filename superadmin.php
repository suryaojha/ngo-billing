<?php
session_start();
require 'config.php';

// Only superadmin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit;
}

$msg = '';

// ── CREATE ADMIN ──────────────────────────────────────────────
if (isset($_POST['create_admin'])) {
    $uname = trim($_POST['username']);
    $fname = trim($_POST['full_name']);
    $pass  = $_POST['password'];
    // The superadmin creates ADMINS only. Each admin creates their own staff (see team.php).
    $role  = 'admin';
    $aid   = null;

    $check = $pdo->prepare("SELECT id FROM users WHERE username=?");
    $check->execute([$uname]);
    if ($uname === '' || $fname === '' || $pass === '') {
        $msg = '<div class="alert alert-danger">Please fill in name, username and password.</div>';
    } elseif (trim($_POST['company_name'] ?? '') === '') {
        $msg = '<div class="alert alert-danger">Company name is required — it is printed on every invoice of this admin.</div>';
    } elseif ($check->fetch()) {
        $msg = '<div class="alert alert-danger">Username already exists.</div>';
    } else {
        $pdo->prepare("INSERT INTO users (username,full_name,password,role,admin_id,company_name,company_address,gstin,company_phone) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$uname, $fname, $pass, $role, $aid, trim($_POST['company_name']), $_POST['company_address'] ?? null, $_POST['gstin'] ?? null, $_POST['company_phone'] ?? null]);
        $msg = '<div class="alert alert-success">Admin "' . htmlspecialchars($fname) . '" created. They can now log in, and add their own staff from the Team page.</div>';
    }
}

// ── UPDATE COMPANY PROFILE (letterhead used on invoices) ───────
if (isset($_POST['update_company'])) {
    $uid = (int)$_POST['admin_user_id'];
    $pdo->prepare("UPDATE users SET company_name=?, company_address=?, gstin=?, company_phone=?, bank_name=?, bank_account=?, bank_ifsc=?, bank_branch=? WHERE id=? AND role='admin'")
        ->execute([
            $_POST['company_name'], $_POST['company_address'], $_POST['gstin'], $_POST['company_phone'],
            $_POST['bank_name'], $_POST['bank_account'], $_POST['bank_ifsc'], $_POST['bank_branch'], $uid
        ]);
    header("Location: superadmin.php?tab=users&saved=1");
    exit;
}

// ── TOGGLE USER ACTIVE ────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $uid = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE users SET IsActive = 1 - IsActive WHERE id=? AND role != 'superadmin'")->execute([$uid]);
    header("Location: superadmin.php?tab=users");
    exit;
}

// ── DELETE USER ───────────────────────────────────────────────
if (isset($_GET['del_user'])) {
    $uid = (int)$_GET['del_user'];
    // Never delete an admin who still owns staff, stock or invoices — that would orphan them. Disable instead.
    $dep = $pdo->prepare("SELECT (SELECT COUNT(*) FROM users WHERE admin_id=?) + (SELECT COUNT(*) FROM products WHERE admin_id=?) + (SELECT COUNT(*) FROM invoices WHERE admin_id=?)");
    $dep->execute([$uid, $uid, $uid]);
    if ((int)$dep->fetchColumn() > 0) {
        header("Location: superadmin.php?tab=users&blocked=1");
        exit;
    }
    $pdo->prepare("DELETE FROM users WHERE id=? AND role != 'superadmin'")->execute([$uid]);
    header("Location: superadmin.php?tab=users");
    exit;
}

// ── FETCH DATA ────────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'dashboard';

$edit_company = null;
if (isset($_GET['edit_company'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=? AND role='admin'");
    $stmt->execute([(int)$_GET['edit_company']]);
    $edit_company = $stmt->fetch();
}

$admins = $pdo->query("SELECT * FROM users WHERE role='admin' AND IsActive=1 ORDER BY id DESC")->fetchAll();
$all_users = $pdo->query("SELECT u.*, a.full_name as admin_name FROM users u LEFT JOIN users a ON u.admin_id=a.id WHERE u.role != 'superadmin' ORDER BY u.role, u.id DESC")->fetchAll();

// Stats
$total_admins  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$total_staff   = $pdo->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetchColumn();
$total_products= $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_invoices= $pdo->query("SELECT COUNT(*) FROM invoices WHERE status=1")->fetchColumn();
$total_revenue = $pdo->query("SELECT IFNULL(SUM(total_amount),0) FROM invoices WHERE status=1")->fetchColumn();

$low_stock_count = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_qty <= 10")->fetchColumn();
$stock_value     = $pdo->query("SELECT IFNULL(SUM(GREATEST(stock_qty,0)*rate),0) FROM products")->fetchColumn();

// One query for every admin's staff / product / stock numbers (instead of 2 queries per admin)
$admin_stats = [];
foreach ($pdo->query("
    SELECT u.id,
           (SELECT COUNT(*) FROM users s WHERE s.admin_id=u.id AND s.role='staff') AS staff_count,
           (SELECT COUNT(*) FROM products p WHERE p.admin_id=u.id) AS prod_count,
           (SELECT IFNULL(SUM(GREATEST(p.stock_qty,0)),0) FROM products p WHERE p.admin_id=u.id) AS units,
           (SELECT IFNULL(SUM(GREATEST(p.stock_qty,0)*p.rate),0) FROM products p WHERE p.admin_id=u.id) AS val
    FROM users u WHERE u.role='admin'")->fetchAll() as $r) {
    $admin_stats[(int)$r['id']] = $r;
}

// All invoices
$all_invoices = $pdo->query("SELECT i.*, u.full_name as staff_name, a.full_name as admin_name FROM invoices i LEFT JOIN users u ON i.created_by=u.id LEFT JOIN users a ON i.admin_id=a.id WHERE i.status=1 ORDER BY i.created_at DESC LIMIT 100")->fetchAll();

// All products with admin name
$all_products = $pdo->query("SELECT p.*, u.full_name as admin_name FROM products p LEFT JOIN users u ON p.admin_id=u.id ORDER BY p.admin_id, p.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Panel | RUSHIKESH PHARMA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#7c3aed; --success:#16a34a; --danger:#ef4444; --warning:#d97706; --bg:#f1f5f9; --dark:#0f172a; --sidebar:#1e1b4b; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--dark); display:flex; min-height:100vh; }

        /* Sidebar */
        .sidebar { width:240px; background:var(--sidebar); color:white; display:flex; flex-direction:column; position:fixed; height:100vh; z-index:100; }
        .sidebar-brand { padding:24px 20px; border-bottom:1px solid rgba(255,255,255,0.1); }
        .sidebar-brand h2 { font-size:16px; font-weight:800; color:white; }
        .sidebar-brand p { font-size:11px; color:#a5b4fc; margin-top:4px; }
        .sidebar-nav { flex:1; padding:16px 0; overflow-y:auto; }
        .nav-item { display:block; padding:12px 20px; color:#c4b5fd; text-decoration:none; font-size:14px; font-weight:500; transition:0.2s; border-left:3px solid transparent; }
        .nav-item:hover, .nav-item.active { background:rgba(124,58,237,0.3); color:white; border-left-color:#7c3aed; }
        .nav-item span { margin-right:10px; }
        .sidebar-footer { padding:16px 20px; border-top:1px solid rgba(255,255,255,0.1); }
        .logout-btn { display:block; width:100%; padding:10px; background:#ef4444; color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:14px; text-align:center; text-decoration:none; }

        /* Main */
        .main { margin-left:240px; flex:1; padding:30px; }
        .page-header { margin-bottom:30px; }
        .page-header h1 { font-size:24px; font-weight:800; }
        .page-header p { color:#64748b; font-size:14px; margin-top:4px; }

        /* Stats */
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:20px; margin-bottom:30px; }
        .stat-card { background:white; padding:20px; border-radius:16px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05); border:1px solid #e2e8f0; }
        .stat-card .label { font-size:12px; color:#64748b; text-transform:uppercase; font-weight:600; }
        .stat-card .value { font-size:28px; font-weight:800; margin-top:8px; }

        /* Cards */
        .card { background:white; border-radius:16px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05); padding:24px; margin-bottom:24px; border:1px solid #e2e8f0; }
        .card-title { font-size:18px; font-weight:700; margin-bottom:20px; padding-bottom:12px; border-bottom:1px solid #f1f5f9; }

        /* Form */
        .form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:15px; }
        .form-group label { display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:6px; text-transform:uppercase; }
        .form-group input, .form-group select { width:100%; padding:10px 14px; border:1px solid #cbd5e1; border-radius:8px; font-family:inherit; font-size:14px; }
        .form-group input:focus, .form-group select:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(124,58,237,0.15); }

        /* Buttons */
        .btn { padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer; border:none; font-size:14px; transition:0.2s; text-decoration:none; display:inline-block; }
        .btn-primary { background:var(--primary); color:white; }
        .btn-primary:hover { background:#6d28d9; }
        .btn-success { background:var(--success); color:white; }
        .btn-danger { background:var(--danger); color:white; font-size:12px; padding:6px 12px; }
        .btn-warning { background:var(--warning); color:white; font-size:12px; padding:6px 12px; }

        /* Table */
        table { width:100%; border-collapse:collapse; }
        th { background:#f8fafc; padding:12px 15px; text-align:left; font-size:12px; text-transform:uppercase; color:#475569; font-weight:600; }
        td { padding:12px 15px; border-bottom:1px solid #f1f5f9; font-size:14px; }
        tr:hover td { background:#fafafa; }

        /* Badges */
        .badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-superadmin { background:#ede9fe; color:#5b21b6; }
        .badge-admin { background:#dbeafe; color:#1e40af; }
        .badge-staff { background:#dcfce7; color:#166534; }
        .badge-inactive { background:#fee2e2; color:#991b1b; }

        /* Alerts */
        .alert { padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; }
        .alert-success { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .alert-danger { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }

        /* Tab content */
        .tab-content { display:none; }
        .tab-content.active { display:block; }

        @media(max-width:768px){
            body{flex-direction:column;}
            .sidebar{width:100%;height:auto;position:relative;flex-direction:row;flex-wrap:wrap;align-items:center;justify-content:space-between;}
            .sidebar-brand{padding:12px 16px;border-bottom:none;}
            .sidebar-footer{padding:8px 12px;border-top:none;}
            .main{width:100%;}
            .sidebar-nav{display:flex;flex-wrap:wrap;padding:8px;}
            .nav-item{padding:8px 12px;font-size:12px;}
            .main{margin-left:0;padding:15px;}
            .stats-grid{grid-template-columns:1fr 1fr;}
            .stat-card .value{font-size:22px;}
            .card{padding:16px;}
            .form-grid input,.form-grid select{font-size:16px;}
            table.rt,table.rt tbody,table.rt tr,table.rt td{display:block;width:100%;}
            table.rt thead{display:none;}
            table.rt tr{border:1px solid #e2e8f0;border-radius:12px;margin-bottom:10px;padding:8px 12px;background:#fff;}
            table.rt td{border:none;padding:6px 0;display:flex;justify-content:space-between;align-items:center;gap:12px;text-align:right;}
            table.rt td::before{content:attr(data-label);font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;text-align:left;flex-shrink:0;}
            tr:hover td{background:transparent;}
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">
        <h2>⚡ Super Admin</h2>
        <p>RUSHIKESH PHARMA</p>
    </div>
    <nav class="sidebar-nav">
        <a href="?tab=dashboard" class="nav-item <?php echo $tab==='dashboard'?'active':''; ?>"><span>🏠</span> Dashboard</a>
        <a href="?tab=users" class="nav-item <?php echo $tab==='users'?'active':''; ?>"><span>👥</span> Manage Users</a>
        <a href="?tab=create" class="nav-item <?php echo $tab==='create'?'active':''; ?>"><span>➕</span> Create Admin</a>
        <a href="inventory.php" class="nav-item"><span>📦</span> All Stock</a>
        <a href="billing.php" class="nav-item"><span>🧾</span> Create Bill</a>
        <a href="reports.php" class="nav-item"><span>📚</span> All Invoices</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">

    <?php echo $msg; ?>
    <?php if(isset($_GET['saved'])): ?><div class="alert alert-success">✅ Company profile saved.</div><?php endif; ?>
    <?php if(isset($_GET['blocked'])): ?><div class="alert alert-danger">This admin still has staff, stock or invoices, so it can't be deleted. Use <strong>Disable</strong> instead.</div><?php endif; ?>

    <!-- DASHBOARD TAB -->
    <div class="tab-content <?php echo $tab==='dashboard'?'active':''; ?>">
        <div class="page-header">
            <h1>Dashboard Overview</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>
        <div class="stats-grid">
            <div class="stat-card"><div class="label">Total Admins</div><div class="value" style="color:var(--primary);"><?php echo $total_admins; ?></div></div>
            <div class="stat-card"><div class="label">Total Staff</div><div class="value" style="color:#0891b2;"><?php echo $total_staff; ?></div></div>
            <div class="stat-card"><div class="label">Total Products</div><div class="value" style="color:var(--warning);"><?php echo $total_products; ?></div></div>
            <div class="stat-card"><div class="label">Total Invoices</div><div class="value" style="color:var(--success);"><?php echo $total_invoices; ?></div></div>
            <div class="stat-card"><div class="label">Total Revenue</div><div class="value" style="color:var(--success);">₹<?php echo number_format($total_revenue,0); ?></div></div>
            <div class="stat-card"><div class="label">Stock Value</div><div class="value" style="color:#0891b2;">₹<?php echo number_format($stock_value,0); ?></div></div>
            <div class="stat-card"><div class="label">Low / Out of Stock</div><div class="value" style="color:var(--danger);"><?php echo $low_stock_count; ?></div></div>
        </div>

        <!-- Quick: Admins, their staff and stock -->
        <div class="card">
            <div class="card-title">👥 Admins, Staff &amp; Stock</div>
            <table class="rt">
                <thead><tr><th>Admin</th><th>Company / GSTIN</th><th>Staff</th><th>Products</th><th>Units</th><th>Stock value</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($admins as $a):
                        $s = $admin_stats[(int)$a['id']] ?? ['staff_count'=>0,'prod_count'=>0,'units'=>0,'val'=>0];
                    ?>
                    <tr>
                        <td data-label="Admin"><div><strong><?php echo htmlspecialchars($a['full_name']); ?></strong><br><small style="color:#64748b;">@<?php echo htmlspecialchars($a['username']); ?></small></div></td>
                        <td data-label="Company"><div><?php echo htmlspecialchars($a['company_name'] ?? '') ?: '<span style="color:#94a3b8;">Not set</span>'; ?><?php if(!empty($a['gstin'])): ?><br><small style="color:#64748b;"><?php echo htmlspecialchars($a['gstin']); ?></small><?php endif; ?></div></td>
                        <td data-label="Staff"><span class="badge badge-staff"><?php echo (int)$s['staff_count']; ?> staff</span></td>
                        <td data-label="Products"><span class="badge badge-admin"><?php echo (int)$s['prod_count']; ?></span></td>
                        <td data-label="Units"><?php echo number_format((int)$s['units']); ?></td>
                        <td data-label="Stock value">₹<?php echo number_format((float)$s['val'],0); ?></td>
                        <td data-label="Actions">
                            <a href="inventory.php?admin=<?php echo (int)$a['id']; ?>" class="btn" style="background:#e0e7ff;color:#3730a3;font-size:12px;padding:6px 12px;">📦 Stock</a>
                            <a href="billing.php?for=<?php echo (int)$a['id']; ?>" class="btn btn-primary" style="font-size:12px;padding:6px 12px;">🧾 Create bill</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($admins)): ?>
                        <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:30px;">No admins yet. <a href="?tab=create">Create one</a></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Invoices -->
        <div class="card">
            <div class="card-title">🧾 Recent Invoices</div>
            <table class="rt">
                <thead><tr><th>Invoice No</th><th>Admin</th><th>Customer</th><th>Amount</th><th>By</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach(array_slice($all_invoices,0,10) as $inv): ?>
                    <tr>
                        <td data-label="Invoice"><strong>#<?php echo htmlspecialchars($inv['invoice_no']); ?></strong></td>
                        <td data-label="Admin"><?php echo htmlspecialchars($inv['admin_name'] ?? '—'); ?></td>
                        <td data-label="Customer"><?php echo htmlspecialchars($inv['customer_name']); ?></td>
                        <td data-label="Amount"><strong>₹<?php echo number_format($inv['total_amount'],2); ?></strong></td>
                        <td data-label="By"><?php echo htmlspecialchars($inv['staff_name'] ?? 'N/A'); ?></td>
                        <td data-label="Date"><?php echo date('d-M-Y',strtotime($inv['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- USERS TAB -->
    <div class="tab-content <?php echo $tab==='users'?'active':''; ?>">
        <div class="page-header">
            <h1>Manage Users</h1>
            <p>All admins and staff members</p>
        </div>
        <div class="card">
            <div class="card-title">Admins &amp; their staff <a href="?tab=create" class="btn btn-primary" style="float:right;font-size:13px;">+ Create Admin</a></div>
            <table class="rt">
                <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Belongs To</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($all_users as $u): ?>
                    <tr>
                        <td data-label="Name"><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                        <td data-label="Username">@<?php echo htmlspecialchars($u['username']); ?></td>
                        <td data-label="Role"><span class="badge badge-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                        <td data-label="Belongs to"><?php echo $u['role']==='staff' ? htmlspecialchars($u['admin_name']??'—') : '—'; ?></td>
                        <td data-label="Status">
                            <?php if($u['IsActive']): ?>
                                <span class="badge badge-staff">Active</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Actions">
                            <?php if($u['role']==='admin'): ?>
                                <a href="?tab=users&edit_company=<?php echo $u['id']; ?>" class="btn" style="background:#e0e7ff;color:#3730a3;font-size:12px;padding:6px 12px;">Company Profile</a>
                            <?php endif; ?>
                            <a href="?toggle=<?php echo $u['id']; ?>" class="btn btn-warning"><?php echo $u['IsActive']?'Disable':'Enable'; ?></a>
                            <a href="?del_user=<?php echo $u['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($all_users)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:30px;">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($edit_company): ?>
        <div class="card" style="max-width:700px;">
            <div class="card-title">🏢 Company Profile — <?php echo htmlspecialchars($edit_company['full_name']); ?></div>
            <p style="color:#64748b;font-size:13px;margin-top:-15px;margin-bottom:20px;">This appears as the letterhead (name, GSTIN, bank details) on every invoice this admin and their staff create.</p>
            <form method="POST">
                <input type="hidden" name="admin_user_id" value="<?php echo $edit_company['id']; ?>">
                <div class="form-grid" style="margin-bottom:15px;">
                    <div class="form-group"><label>Company Name</label><input type="text" name="company_name" value="<?php echo htmlspecialchars($edit_company['company_name'] ?? ''); ?>" required></div>
                    <div class="form-group"><label>GSTIN</label><input type="text" name="gstin" value="<?php echo htmlspecialchars($edit_company['gstin'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="company_phone" value="<?php echo htmlspecialchars($edit_company['company_phone'] ?? ''); ?>"></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Address</label><input type="text" name="company_address" value="<?php echo htmlspecialchars($edit_company['company_address'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" value="<?php echo htmlspecialchars($edit_company['bank_name'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Account Number</label><input type="text" name="bank_account" value="<?php echo htmlspecialchars($edit_company['bank_account'] ?? ''); ?>"></div>
                    <div class="form-group"><label>IFSC</label><input type="text" name="bank_ifsc" value="<?php echo htmlspecialchars($edit_company['bank_ifsc'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Branch</label><input type="text" name="bank_branch" value="<?php echo htmlspecialchars($edit_company['bank_branch'] ?? ''); ?>"></div>
                </div>
                <button type="submit" name="update_company" class="btn btn-primary">💾 Save Company Profile</button>
                <a href="?tab=users" class="btn" style="background:#f1f5f9;">Cancel</a>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- CREATE USER TAB -->
    <div class="tab-content <?php echo $tab==='create'?'active':''; ?>">
        <div class="page-header">
            <h1>Create New Admin</h1>
            <p>Each admin is a separate business with its own stock, invoices and letterhead. Admins add their own billing staff.</p>
        </div>
        <div class="card" style="max-width:700px;">
            <div class="card-title">Admin Login</div>
            <form method="POST">
                <div class="form-grid" style="margin-bottom:15px;">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" placeholder="e.g. Rahul Sharma" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="e.g. rahul123" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="text" name="password" placeholder="Set password" required>
                    </div>
                </div>
                <div id="companyFields" class="form-grid" style="margin-bottom:15px;">
                    <div class="form-group"><label>Company Name *</label><input type="text" name="company_name" placeholder="e.g. Anjani E.H Pharma" required></div>
                    <div class="form-group"><label>GSTIN</label><input type="text" name="gstin" placeholder="Optional, can add later"></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="company_phone"></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Address</label><input type="text" name="company_address"></div>
                </div>
                <p style="color:#94a3b8;font-size:12px;margin-top:-10px;">This becomes the invoice letterhead. Bank details can be added afterwards from Manage Users → Company Profile.</p>
                <button type="submit" name="create_admin" class="btn btn-primary">✅ Create Admin</button>
            </form>
        </div>
    </div>

    <!-- INVOICES TAB -->
    <div class="tab-content <?php echo $tab==='invoices'?'active':''; ?>">
        <div class="page-header">
            <h1>All Invoices</h1>
            <p>Complete invoice history across all admins and staff</p>
        </div>
        <div class="card">
            <div class="card-title">Invoice Records</div>
            <table class="rt">
                <thead><tr><th>Invoice No</th><th>Admin</th><th>Customer</th><th>Phone</th><th>Amount</th><th>Created By</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach($all_invoices as $inv): ?>
                    <tr>
                        <td data-label="Invoice"><strong>#<?php echo htmlspecialchars($inv['invoice_no']); ?></strong></td>
                        <td data-label="Admin"><?php echo htmlspecialchars($inv['admin_name'] ?? '—'); ?></td>
                        <td data-label="Customer"><?php echo htmlspecialchars($inv['customer_name']); ?></td>
                        <td data-label="Phone"><?php echo htmlspecialchars($inv['customer_phone']); ?></td>
                        <td data-label="Amount"><strong>₹<?php echo number_format($inv['total_amount'],2); ?></strong></td>
                        <td data-label="Created by"><?php echo htmlspecialchars($inv['staff_name'] ?? 'N/A'); ?></td>
                        <td data-label="Date"><?php echo date('d-M-Y H:i',strtotime($inv['created_at'])); ?></td>
                        <td data-label="Action"><a href="reports.php?view=<?php echo (int)$inv['id']; ?>" class="btn btn-primary" style="font-size:12px;padding:6px 12px;">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($all_invoices)): ?>
                        <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:30px;">No invoices found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /main -->

</body>
</html>
