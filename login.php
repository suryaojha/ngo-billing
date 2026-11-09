<?php
session_start();
require 'config.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND IsActive = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['admin_id']  = $user['admin_id'];

            if ($user['role'] === 'superadmin') {
                header("Location: superadmin.php");
            } elseif ($user['role'] === 'admin') {
                header("Location: inventory.php");
            } else {
                header("Location: billing.php");
            }
            exit;
        } else {
            $error = "Invalid Credentials. Access Denied.";
        }
    } else {
        $error = "Please enter both username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login | RUSHIKESH E. H. PHARMA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --accent: #3b82f6; --bg: #020617; --card-bg: rgba(15,23,42,0.8); }
        body { font-family:'Inter',sans-serif; background-color:var(--bg); background-image:radial-gradient(at 0% 0%,rgba(59,130,246,0.15) 0px,transparent 50%),radial-gradient(at 100% 100%,rgba(29,78,216,0.15) 0px,transparent 50%); margin:0; display:flex; justify-content:center; align-items:center; height:100vh; color:#f8fafc; }
        .login-card { width:100%; max-width:400px; background:var(--card-bg); padding:40px; border-radius:24px; border:1px solid rgba(255,255,255,0.1); box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); backdrop-filter:blur(10px); }
        .header { text-align:center; margin-bottom:35px; }
        .header h1 { font-size:22px; font-weight:800; letter-spacing:-1px; margin:0; text-transform:uppercase; background:linear-gradient(to right,#fff,#94a3b8); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .header p { color:#64748b; font-size:14px; margin-top:8px; }
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; font-size:12px; font-weight:600; color:#94a3b8; margin-bottom:8px; text-transform:uppercase; }
        .form-group input { width:100%; padding:14px; background:rgba(2,6,23,0.5); border:1px solid #334155; border-radius:12px; color:#fff; font-size:15px; box-sizing:border-box; transition:0.3s; }
        .form-group input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 4px rgba(59,130,246,0.2); }
        .btn-submit { width:100%; padding:14px; background:var(--accent); color:white; border:none; border-radius:12px; font-weight:700; font-size:15px; cursor:pointer; transition:0.3s; margin-top:10px; }
        .btn-submit:hover { background:#2563eb; transform:translateY(-2px); box-shadow:0 10px 20px -5px rgba(59,130,246,0.5); }
        .error-box { background:rgba(239,68,68,0.15); color:#f87171; padding:12px; border-radius:10px; font-size:13px; text-align:center; margin-bottom:20px; border:1px solid rgba(239,68,68,0.3); }
        .footer-note { text-align:center; font-size:11px; color:#475569; margin-top:30px; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="header">
        <h1>Rushikesh Pharma</h1>
        <p>Enterprise Resource Planning</p>
    </div>
    <?php if($error): ?>
        <div class="error-box"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="e.g. admin" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-submit">Sign In to Dashboard</button>
    </form>
    <div class="footer-note">&copy; <?php echo date('Y'); ?> Rushikesh Pharma | Secure Portal</div>
</div>
</body>
</html>
