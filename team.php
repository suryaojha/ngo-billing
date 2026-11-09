<?php
session_start();
require 'config.php';
require 'ui.php';

// Only an ADMIN manages a team (superadmin creates admins; each admin creates their own billing staff).
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if ($_SESSION['role'] === 'superadmin') { header("Location: superadmin.php?tab=users"); exit; }
if ($_SESSION['role'] !== 'admin')      { header("Location: billing.php"); exit; }

$me = (int)$_SESSION['user_id'];

/* ---- helper: a staff row that really belongs to this admin ---- */
function my_staff(PDO $pdo, int $me, int $id) {
    $st = $pdo->prepare("SELECT * FROM users WHERE id=? AND role='staff' AND admin_id=?");
    $st->execute([$id, $me]);
    return $st->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CREATE staff
    if (isset($_POST['create_staff'])) {
        $uname = trim((string)($_POST['username'] ?? ''));
        $fname = trim((string)($_POST['full_name'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');
        $chk = $pdo->prepare("SELECT id FROM users WHERE username=?");
        $chk->execute([$uname]);
        if ($uname === '' || $fname === '' || $pass === '')            flash_set('error', 'Please fill in name, username and password.');
        elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $uname))        flash_set('error', 'Username must be 3–50 letters, numbers, dot, dash or underscore (no spaces).');
        elseif ($chk->fetch())                                          flash_set('error', 'That username is already taken. Please choose another.');
        else {
            $pdo->prepare("INSERT INTO users (username,full_name,password,role,admin_id) VALUES (?,?,?,'staff',?)")
                ->execute([$uname, $fname, $pass, $me]);
            flash_set('success', "Staff \"$fname\" created. They can log in with username \"$uname\" and will see only your stock.");
        }
        header("Location: team.php"); exit;
    }

    // ENABLE / DISABLE
    if (isset($_POST['toggle_staff'])) {
        if (my_staff($pdo, $me, (int)$_POST['id'])) {
            $pdo->prepare("UPDATE users SET IsActive = 1 - IsActive WHERE id=? AND admin_id=? AND role='staff'")->execute([(int)$_POST['id'], $me]);
            flash_set('success', 'Staff status updated.');
        }
        header("Location: team.php"); exit;
    }

    // RESET PASSWORD
    if (isset($_POST['reset_staff'])) {
        $pass = (string)($_POST['password'] ?? '');
        if ($pass === '')                                   flash_set('error', 'Please type the new password.');
        elseif (my_staff($pdo, $me, (int)$_POST['id'])) {
            $pdo->prepare("UPDATE users SET password=? WHERE id=? AND admin_id=? AND role='staff'")->execute([$pass, (int)$_POST['id'], $me]);
            flash_set('success', 'Password changed.');
        }
        header("Location: team.php"); exit;
    }

    // DELETE (only if they never made an invoice; otherwise disable them to keep history intact)
    if (isset($_POST['delete_staff'])) {
        $sid = (int)$_POST['id'];
        if (my_staff($pdo, $me, $sid)) {
            $c = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE created_by=?");
            $c->execute([$sid]);
            if ((int)$c->fetchColumn() > 0) {
                flash_set('error', 'This person already made invoices, so they cannot be deleted (it would break the invoice history). Disable them instead.');
            } else {
                $pdo->prepare("DELETE FROM users WHERE id=? AND admin_id=? AND role='staff'")->execute([$sid, $me]);
                flash_set('success', 'Staff deleted.');
            }
        }
        header("Location: team.php"); exit;
    }
}

// Staff list with simple performance numbers
$st = $pdo->prepare("
    SELECT u.id, u.full_name, u.username, u.IsActive, u.created_at,
           COUNT(i.id) AS inv_count,
           COALESCE(SUM(i.total_amount),0) AS inv_total,
           MAX(i.created_at) AS last_inv
    FROM users u
    LEFT JOIN invoices i ON i.created_by=u.id AND i.status=1
    WHERE u.role='staff' AND u.admin_id=?
    GROUP BY u.id, u.full_name, u.username, u.IsActive, u.created_at
    ORDER BY u.IsActive DESC, u.full_name");
$st->execute([$me]);
$team = $st->fetchAll();
$company = load_company($pdo, $me);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Team | Pharma Billing</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
<?php echo ui_css(); ?>
.page-head h1{margin:0;font-size:24px;font-weight:800}
.page-head p{margin:4px 0 18px;color:var(--muted);font-size:14px}
.how{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px}
.how div{background:#eff6ff;border:1px solid #bfdbfe;border-radius:14px;padding:12px 14px;font-size:13px;color:#1e3a8a}
.how b{display:block;margin-bottom:2px}
.act{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
.act form{display:inline}
@media(max-width:768px){.how{grid-template-columns:1fr}.act{justify-content:flex-end}}
</style>
</head>
<body>
<?php echo ui_nav('team'); ?>
<div class="container">
    <?php echo flash_show(); ?>
    <div class="page-head">
        <h1>👥 My Team</h1>
        <p>Add billing staff for <strong><?php echo e($company['name']); ?></strong>. They log in with their own username and can only bill from <em>your</em> stock. You can also bill yourself, without any staff.</p>
    </div>

    <div class="how">
        <div><b>1. Create</b>Give them a name, username and password.</div>
        <div><b>2. They bill</b>Staff see only your stock and only their own invoices.</div>
        <div><b>3. You control</b>You see every invoice they make. Disable anyone any time.</div>
    </div>

    <div class="card">
        <div class="card-title">➕ Add a staff member</div>
        <form method="POST" class="form-grid" autocomplete="off">
            <div><label>Full name</label><input type="text" name="full_name" placeholder="e.g. Rahul Sharma" required></div>
            <div><label>Username</label><input type="text" name="username" placeholder="e.g. rahul.billing" pattern="[A-Za-z0-9._-]{3,50}" title="3–50 letters, numbers, dot, dash or underscore" required></div>
            <div><label>Password</label><input type="text" name="password" placeholder="Set a password" required></div>
            <div style="display:flex;align-items:flex-end"><button class="btn btn-primary" style="width:100%" name="create_staff" value="1">✅ Create staff</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Your staff <span class="badge b-blue"><?php echo count($team); ?></span></div>
        <table class="data rt">
            <thead><tr><th>Name</th><th>Username</th><th>Invoices</th><th>Sales</th><th>Last invoice</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($team as $s): ?>
                <tr>
                    <td data-label="Name"><strong><?php echo e($s['full_name']); ?></strong></td>
                    <td data-label="Username">@<?php echo e($s['username']); ?></td>
                    <td data-label="Invoices"><?php echo (int)$s['inv_count']; ?></td>
                    <td data-label="Sales">₹<?php echo number_format((float)$s['inv_total'], 0); ?></td>
                    <td data-label="Last invoice"><?php echo $s['last_inv'] ? date('d-M-Y', strtotime($s['last_inv'])) : '—'; ?></td>
                    <td data-label="Status"><?php echo $s['IsActive'] ? '<span class="badge b-green">Active</span>' : '<span class="badge b-red">Disabled</span>'; ?></td>
                    <td data-label="Actions" class="act">
                        <button type="button" class="btn btn-light btn-sm" onclick='openReset(<?php echo (int)$s['id']; ?>, <?php echo json_encode($s['full_name'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG); ?>)'>🔑 Password</button>
                        <form method="POST"><input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                            <button class="btn btn-warn btn-sm" name="toggle_staff" value="1"><?php echo $s['IsActive'] ? 'Disable' : 'Enable'; ?></button></form>
                        <form method="POST" onsubmit="return confirm('Delete this staff member? This cannot be undone.')"><input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                            <button class="btn btn-danger btn-sm" name="delete_staff" value="1">Delete</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($team)): ?>
                <tr><td colspan="7" class="no-label" style="text-align:center;color:#94a3b8;padding:30px;">No staff yet. You can bill yourself, or add someone above.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-bg" id="resetModal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal" style="max-width:420px">
        <h2>🔑 Change password</h2>
        <div id="resetName" style="color:var(--muted);font-size:13px;margin:-8px 0 14px"></div>
        <form method="POST" class="form-grid" style="grid-template-columns:1fr">
            <input type="hidden" name="id" id="resetId">
            <div><label>New password</label><input type="text" name="password" required></div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn btn-light" onclick="document.getElementById('resetModal').classList.remove('open')">Cancel</button>
                <button class="btn btn-primary" name="reset_staff" value="1">Save</button>
            </div>
        </form>
    </div>
</div>
<script>
function openReset(id, name) {
    document.getElementById('resetId').value = id;
    document.getElementById('resetName').textContent = name;
    document.getElementById('resetModal').classList.add('open');
}
</script>
</body>
</html>
