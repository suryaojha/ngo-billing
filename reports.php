<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
if ($role === 'staff') {
    $stock_admin_id = $_SESSION['admin_id'];
} elseif ($role === 'admin') {
    $stock_admin_id = $_SESSION['user_id'];
} else {
    $stock_admin_id = null; // superadmin sees all
}

$logo_path = 'uploads/bill.png';
$company = ['name' => 'RUSHIKESH E. H. PHARMA', 'address' => '', 'gstin' => '', 'phone' => '', 'bank_name' => '', 'bank_account' => '', 'bank_ifsc' => '', 'bank_branch' => ''];
if ($stock_admin_id) {
    $stmt_co = $pdo->prepare("SELECT company_name,company_address,gstin,company_phone,bank_name,bank_account,bank_ifsc,bank_branch FROM users WHERE id=?");
    $stmt_co->execute([$stock_admin_id]);
    if ($row_co = $stmt_co->fetch()) {
        $company = [
            'name' => $row_co['company_name'] ?: 'Company Name Not Set',
            'address' => $row_co['company_address'] ?? '',
            'gstin' => $row_co['gstin'] ?? '',
            'phone' => $row_co['company_phone'] ?? '',
            'bank_name' => $row_co['bank_name'] ?? '',
            'bank_account' => $row_co['bank_account'] ?? '',
            'bank_ifsc' => $row_co['bank_ifsc'] ?? '',
            'bank_branch' => $row_co['bank_branch'] ?? '',
        ];
    }
}
$company_name = $company['name'];

// Delete invoice (set status to 0) — admin/superadmin only, and only
// within their own tenant's invoices.
if (isset($_GET['delete'])) {
    if (!in_array($role, ['admin', 'superadmin'])) {
        header("Location: reports.php?err=forbidden");
        exit;
    }
    if ($role === 'admin') {
        $pdo->prepare("UPDATE invoices SET status = 0 WHERE id = ? AND admin_id = ?")->execute([$_GET['delete'], $stock_admin_id]);
    } else {
        $pdo->prepare("UPDATE invoices SET status = 0 WHERE id = ?")->execute([$_GET['delete']]);
    }
    header("Location: reports.php");
    exit;
}

// View invoice details
$view_id = $_GET['view'] ?? null;
$invoice = null;
$items = [];

if ($view_id) {
    if ($stock_admin_id) {
        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND status = 1 AND admin_id = ?");
        $stmt->execute([$view_id, $stock_admin_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND status = 1");
        $stmt->execute([$view_id]);
    }
    $invoice = $stmt->fetch();

    if ($invoice) {
        $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$view_id]);
        $items = $stmt->fetchAll();
    }
}

// Invoices are scoped to the logged-in tenant (admin/staff only see their
// own business's invoices) — superadmin already has a dedicated
// cross-tenant view in superadmin.php.
if ($stock_admin_id) {
    $recent_invoices = $pdo->prepare("SELECT i.*, COUNT(ii.id) as item_count FROM invoices i LEFT JOIN invoice_items ii ON i.id = ii.invoice_id WHERE i.status = 1 AND i.admin_id = ? GROUP BY i.id ORDER BY i.created_at DESC");
    $recent_invoices->execute([$stock_admin_id]);
    $recent_invoices = $recent_invoices->fetchAll();
} else {
    $recent_invoices = $pdo->query("SELECT i.*, COUNT(ii.id) as item_count FROM invoices i LEFT JOIN invoice_items ii ON i.id = ii.invoice_id WHERE i.status = 1 GROUP BY i.id ORDER BY i.created_at DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $invoice ? 'Invoice #'.$invoice['invoice_no'] : 'Reports'; ?> | RUSHIKESH PHARMA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --border-thick: 2px solid #000; --border-thin: 1px solid #000; --dark: #0f172a; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; margin: 0; padding: 0; color: #1e293b; }
        
        /* Navigation */
        nav { background: var(--dark); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; color: white; position: sticky; top: 0; z-index: 1000; }
        .nav-links a { color: #94a3b8; text-decoration: none; margin-left: 20px; font-size: 14px; font-weight: 500; transition: 0.3s; }
        .nav-links a.active { color: white; border-bottom: 2px solid var(--primary); padding-bottom: 5px; }
        .nav-links a:hover { color: white; }
        
        .page-content { padding: 20px; }
        .no-print-toolbar { max-width: 950px; margin: 0 auto 20px; background: #fff; padding: 15px 25px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); flex-wrap: wrap; gap: 15px; }
        .invoice-canvas { width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff; padding: 12mm; box-sizing: border-box; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .header-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .logo-box img { max-width: 150px; max-height: 80px; object-fit: contain; }
        .invoice-frame { border: var(--border-thick); }
        .row-split { display: grid; grid-template-columns: 1.2fr 0.8fr; border-bottom: var(--border-thick); }
        .col-box { padding: 12px; border-right: var(--border-thick); }
        .col-box:last-child { border-right: none; }
        table { width: 100%; border-collapse: collapse; }
        th { border-bottom: var(--border-thick); border-right: var(--border-thin); padding: 10px 5px; font-size: 11px; background: #f8fafc; text-align: center; }
        td { border-bottom: var(--border-thin); border-right: var(--border-thin); padding: 8px 5px; font-size: 12px; text-align: center; }
        .footer-summary { display: grid; grid-template-columns: 1.3fr 0.7fr; }
        .summary-left { padding: 15px; border-right: var(--border-thick); }
        .grand-total-line { border-top: var(--border-thick); padding: 12px 15px; font-size: 18px; background: #fdfdfd; display: flex; justify-content: space-between; font-weight: 800; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; white-space: nowrap; text-decoration: none; display: inline-block; color: white; }
        
        /* List View Styles */
        .list-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 30px; border: 1px solid #e2e8f0; }
        .section-title { font-size: 20px; font-weight: 700; margin-bottom: 25px; }
        .list-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .list-table th { background: #f1f5f9; padding: 15px; text-align: left; font-size: 12px; text-transform: uppercase; color: #475569; border: none; }
        .list-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .list-table tr:hover { background: #f8fafc; }
        
        @media screen and (max-width: 768px) {
            nav { padding: 15px 20px; flex-wrap: wrap; }
            .nav-links { width: 100%; margin-top: 10px; text-align: center; }
            .nav-links a { margin: 0 10px; font-size: 12px; }
            .page-content { padding: 10px; }
            body { padding: 0; }
            .no-print-toolbar { flex-direction: column; text-align: center; padding: 15px; }
            .no-print-toolbar h2 { font-size: 16px; }
            .no-print-toolbar > div { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
            .btn { font-size: 12px; padding: 10px 15px; }
            .invoice-canvas { width: 100%; min-height: auto; padding: 10px; box-shadow: none; }
            .header-section { flex-direction: column; align-items: center; text-align: center; }
            .company-header h1 { font-size: 24px; }
            .company-header div { font-size: 11px; }
            .logo-box { margin-top: 15px; width: 100%; text-align: center; }
            .row-split { grid-template-columns: 1fr; border-bottom: none; }
            .col-box { border-right: none; border-bottom: var(--border-thick); padding: 10px; }
            .col-box h3 { font-size: 12px; }
            .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-bottom: var(--border-thick); }
            table { min-width: 800px; font-size: 11px; }
            th, td { padding: 6px 3px; font-size: 10px; }
            .footer-summary { grid-template-columns: 1fr; }
            .summary-left { border-right: none; border-bottom: var(--border-thick); padding: 10px; font-size: 11px; }
            .summary-right { font-size: 12px; }
            .grand-total-line { font-size: 16px; padding: 10px; }
            .list-container { padding: 10px; }
            .card { padding: 15px; }
            .section-title { font-size: 16px; }
            .list-table { overflow-x: auto; display: block; }
            .list-table th, .list-table td { font-size: 11px; padding: 10px 8px; white-space: nowrap; }
        }
        
        @media print {
            nav, .no-print-toolbar { display: none !important; }
            body { background: white; padding: 0; }
            .invoice-canvas { box-shadow: none; border: none; margin: 0; width: 100%; padding: 5mm; }
            .table-responsive { overflow: visible; }
            table { min-width: 100%; }
            th:nth-child(5), td:nth-child(5) { display: none; }
        }
    </style>
</head>
<body>

<nav>
    <div style="font-weight: 800; font-size: 20px; letter-spacing: -1px;">RUSHIKESH PHARMA <span style="font-weight: 300; font-size: 14px; color: #94a3b8;">Admin Portal</span></div>
    <div class="nav-links">
        <a href="inventory.php">Inventory</a>
        <a href="billing.php">Billing Console</a>
        <a href="reports.php" class="active">Reports</a>
        <a href="logout.php" style="color: #f87171;">Logout</a>
    </div>
</nav>

<div class="page-content">
<?php if ($invoice): ?>
    <!-- Invoice View (Same as Billing) -->
    <div class="no-print-toolbar">
        <div>
            <h2 style="margin:0; font-size: 18px; color: var(--primary);">Invoice #<?php echo $invoice['invoice_no']; ?></h2>
            <span style="font-size: 11px; color: #64748b;">Saved Invoice</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn" style="background:#16a34a;" onclick="location.href='reports.php'">← Back to List</button>
            <button class="btn" style="background:var(--primary);" onclick="window.print()">🖨️ Print</button>
            <button class="btn" style="background:#ef4444;" onclick="if(confirm('Delete this invoice?')) location.href='?delete=<?= $invoice['id'] ?>'">🗑️ Delete</button>
        </div>
    </div>

    <div class="invoice-canvas">
        <div style="text-align: center; font-weight: 800; font-size: 14px; margin-bottom: 10px; text-decoration: underline;">GST INVOICE</div>
        
        <div class="header-section">
            <div class="company-header">
                <h1 style="margin:0; font-size: 32px; font-weight: 800;"><?php echo htmlspecialchars($company['name']); ?></h1>
                <div style="font-size: 12px; line-height: 1.4; margin-top: 5px;">
                    <?php echo nl2br(htmlspecialchars($company['address'])); ?><?php if($company['address']): ?><br><?php endif; ?>
                    <?php if($company['gstin']): ?><strong>GSTIN: <?php echo htmlspecialchars($company['gstin']); ?></strong><?php endif; ?>
                    <?php if($company['phone']): ?> | <strong>Phone:</strong> <?php echo htmlspecialchars($company['phone']); ?><?php endif; ?>
                </div>
            </div>
            <div class="logo-box">
                <?php if(file_exists($logo_path)): ?>
                    <img src="<?php echo $logo_path; ?>?v=<?php echo time(); ?>">
                <?php else: ?>
                    <div style="border: 1px dashed #ccc; font-size: 10px; padding: 10px;">LOGO</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="invoice-frame">
            <div class="row-split">
                <div class="col-box">
                    <h3>Billing Details (Bill To)</h3>
                    <div style="display: flex; margin-bottom: 2px;">
                        <span style="width: 70px; font-weight: bold;">Name:</span>
                        <span style="font-weight: 800;"><?php echo htmlspecialchars($invoice['customer_name']); ?></span>
                    </div>
                    <div style="display: flex; margin-bottom: 2px;">
                        <span style="width: 70px; font-weight: bold;">Address:</span>
                        <span><?php echo htmlspecialchars($invoice['customer_address']); ?></span>
                    </div>
                    <div style="display: flex; margin-bottom: 2px;">
                        <span style="width: 70px; font-weight: bold;">Phone:</span>
                        <span><?php echo htmlspecialchars($invoice['customer_phone']); ?></span>
                    </div>
                </div>
                <div class="col-box">
                    <div style="margin-bottom: 8px;">
                        <strong>Invoice No:</strong> <span style="font-weight: bold;"><?php echo $invoice['invoice_no']; ?></span>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong>Date:</strong> <?php echo date('d-m-Y', strtotime($invoice['created_at'])); ?>
                    </div>
                    <div>
                        <strong>Payment:</strong> Cash
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="30">SR</th>
                            <th>Product Information</th>
                            <th width="70">Batch</th>
                            <th width="100">Expiry</th>
                            <th width="80">Capacity</th>
                            <th width="80">Rate</th>
                            <th width="50">Qty</th>
                            <th width="50">Dis %</th>
                            <th width="100">Gross Amt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        $totalGross = 0;
                        $totalDiscount = 0;
                        foreach($items as $idx => $item): 
                            $subtotal += $item['gross_amount'];
                            $lineTotal = $item['rate'] * $item['qty'];
                            $totalGross += $lineTotal;
                            $totalDiscount += $lineTotal * ($item['discount'] / 100);
                        ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td style="text-align: left; padding-left: 10px;"><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['batch']); ?></td>
                            <td><?php echo htmlspecialchars($item['expiry'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['capacity'] ?? '-'); ?></td>
                            <td>₹<?php echo number_format($item['rate'], 2); ?></td>
                            <td><?php echo $item['qty']; ?></td>
                            <td><?php echo $item['discount']; ?>%</td>
                            <td style="font-weight: bold;">₹<?php echo number_format($item['gross_amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="footer-summary">
                <div class="summary-left">
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 11px;">
                        <span>E & O.E</span>
                        <span>Total Items: <?php echo count($items); ?></span>
                    </div>
                    <div style="margin-top: 15px;">
                        <span style="font-size: 10px; font-weight: bold; color: #64748b;">AMOUNT IN WORDS:</span>
                        <div style="font-weight: 800; text-transform: uppercase; font-size: 13px;">
                            <?php
                            function numToWords($n) {
                                $a = ['','One ','Two ','Three ','Four ', 'Five ','Six ','Seven ','Eight ','Nine ','Ten ','Eleven ','Twelve ','Thirteen ','Fourteen ','Fifteen ','Sixteen ','Seventeen ','Eighteen ','Nineteen '];
                                $b = ['', '', 'Twenty','Thirty','Forty','Fifty', 'Sixty','Seventy','Eighty','Ninety'];
                                $num = floor($n);
                                if ($num == 0) return 'Zero Rupees Only';
                                $str = '';
                                if ($num >= 10000000) { $str .= numToWords(floor($num / 10000000)) . 'Crore '; $num %= 10000000; }
                                if ($num >= 100000) { $str .= numToWords(floor($num / 100000)) . 'Lakh '; $num %= 100000; }
                                if ($num >= 1000) { $str .= numToWords(floor($num / 1000)) . 'Thousand '; $num %= 1000; }
                                if ($num >= 100) { $str .= $a[floor($num / 100)] . 'Hundred '; $num %= 100; }
                                if ($num >= 20) { $str .= $b[floor($num / 10)] . ' '; $num %= 10; }
                                if ($num > 0) $str .= $a[$num];
                                return trim($str) . ' Rupees Only';
                            }
                            echo numToWords(round($invoice['total_amount']));
                            ?>
                        </div>
                    </div>
                    <?php if($company['bank_name'] || $company['bank_account']): ?>
                    <div style="margin-top: 30px; font-size: 10px; border-top: 1px solid #eee; padding-top: 8px;">
                        <strong>BANK:</strong> <?php echo htmlspecialchars($company['bank_name']); ?> | <strong>A/C:</strong> <?php echo htmlspecialchars($company['bank_account']); ?><br>
                        <strong>IFSC:</strong> <?php echo htmlspecialchars($company['bank_ifsc']); ?> | <strong>BRANCH:</strong> <?php echo htmlspecialchars($company['bank_branch']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="summary-right">
                    <div style="padding: 10px 15px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>Total Gross:</span> <span>₹<?php echo number_format($totalGross, 2); ?></span></div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>Total Discount:</span> <span>₹<?php echo number_format($totalDiscount, 2); ?></span></div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:5px;"><span>Taxable Value:</span> <span>₹<?php echo number_format($subtotal, 2); ?></span></div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                            <span>GST (<?php echo rtrim(rtrim(number_format($invoice['gst_percent'] ?? 5, 2), '0'), '.'); ?>%)</span>
                            <span>₹<?php echo number_format($subtotal * (($invoice['gst_percent'] ?? 5) / 100), 2); ?></span>
                        </div>
                    </div>
                    <div class="grand-total-line">
                        <span>GRAND TOTAL</span>
                        <span>₹<?php echo number_format($invoice['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; margin-top: 80px;">
            <div style="text-align: center; border-top: 1px solid #000; width: 180px; font-size: 12px; font-weight: bold; padding-top: 5px;">RECEIVER'S SIGNATURE</div>
            <div style="text-align: center; border-top: 1px solid #000; width: 220px; font-size: 12px; font-weight: bold; padding-top: 5px;">FOR <?php echo $company_name; ?></div>
        </div>
    </div>

<?php else: ?>
    <!-- Invoice List -->
    <div class="list-container">
        <div class="card">
            <div class="section-title">📊 All Invoices</div>
            <table class="list-table">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_invoices as $inv): ?>
                    <tr>
                        <td><strong>#<?= $inv['invoice_no'] ?></strong></td>
                        <td><?= htmlspecialchars($inv['customer_name']) ?></td>
                        <td><?= $inv['item_count'] ?> items</td>
                        <td><strong>₹<?= number_format($inv['total_amount'], 2) ?></strong></td>
                        <td><?= date('d-M-Y', strtotime($inv['created_at'])) ?></td>
                        <td>
                            <a href="?view=<?= $inv['id'] ?>" class="btn" style="background: var(--primary); font-size: 12px; padding: 8px 15px;">View</a>
                            <a href="?delete=<?= $inv['id'] ?>" class="btn" style="background: #ef4444; font-size: 12px; padding: 8px 15px;" onclick="return confirm('Delete?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
</div>

</body>
</html>
