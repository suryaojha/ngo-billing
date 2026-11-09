<?php
session_start();
require 'config.php';
require 'ui.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role     = $_SESSION['role'];
$is_super = ($role === 'superadmin');
$can_cancel = in_array($role, ['admin', 'superadmin'], true);

// Tenant scope: staff -> their admin, admin -> self, superadmin -> everyone (optionally filtered)
$scope_admin = null;
if ($role === 'staff') {
    $scope_admin = (int)($_SESSION['admin_id'] ?? 0);
    if (!$scope_admin) die('Your account is not linked to a business yet. Please ask your admin/super admin to fix your account.');
} elseif ($role === 'admin') {
    $scope_admin = (int)$_SESSION['user_id'];
}

$admins = [];
$filter_admin = 0;
if ($is_super) {
    $admins = $pdo->query("SELECT id, full_name, company_name FROM users WHERE role='admin' ORDER BY full_name")->fetchAll();
    $ids = array_map('intval', array_column($admins, 'id'));
    if (isset($_GET['admin']) && in_array((int)$_GET['admin'], $ids, true)) $filter_admin = (int)$_GET['admin'];
}
$list_admin = $is_super ? ($filter_admin ?: null) : $scope_admin;   // admin id used to filter the list (null = all)

/* ------------------------------------------------------------------
 * Cancel invoice: marks it cancelled AND puts the sold quantities back in stock.
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_invoice'])) {
    if (!$can_cancel) { flash_set('error', 'You are not allowed to cancel invoices.'); header("Location: reports.php"); exit; }
    $iid = (int)($_POST['id'] ?? 0);
    try {
        $pdo->beginTransaction();
        if ($is_super) {
            $q = $pdo->prepare("SELECT * FROM invoices WHERE id=? AND status=1 FOR UPDATE");
            $q->execute([$iid]);
        } else {
            $q = $pdo->prepare("SELECT * FROM invoices WHERE id=? AND status=1 AND admin_id=? FOR UPDATE");
            $q->execute([$iid, $scope_admin]);
        }
        $inv = $q->fetch();
        if (!$inv) {
            $pdo->rollBack();
            flash_set('error', 'Invoice not found or already cancelled.');
        } else {
            $it = $pdo->prepare("SELECT product_id, qty, product_name FROM invoice_items WHERE invoice_id=?");
            $it->execute([$iid]);
            $restored = 0; $skipped = 0;
            $back = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id=? AND admin_id <=> ?");
            foreach ($it->fetchAll() as $row) {
                if (!empty($row['product_id']) && (int)$row['qty'] > 0) {
                    $back->execute([(int)$row['qty'], $row['product_id'], $inv['admin_id']]);
                    if ($back->rowCount() > 0) $restored++; else $skipped++;   // product may have been deleted since
                } else {
                    $skipped++;   // old invoices without a product link can't be restored automatically
                }
            }
            $pdo->prepare("UPDATE invoices SET status=0 WHERE id=?")->execute([$iid]);
            $pdo->commit();
            flash_set('success', "Invoice #{$inv['invoice_no']} cancelled. Stock restored for $restored item(s)" . ($skipped ? " ($skipped could not be restored automatically — adjust those in Stock)." : '.'));
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Cancel invoice error: ' . $e->getMessage());
        flash_set('error', 'Could not cancel the invoice. Please try again.');
    }
    header("Location: reports.php" . ($filter_admin ? '?admin=' . $filter_admin : ''));
    exit;
}

/* ------------------------------------------------------------------
 * View one invoice
 * ------------------------------------------------------------------ */
$invoice = null; $items = [];
$view_id = (int)($_GET['view'] ?? 0);
if ($view_id) {
    if ($scope_admin) {
        // staff may only open invoices they made themselves; admins see their whole business
        $st = $pdo->prepare("SELECT i.*, u.full_name AS made_by FROM invoices i LEFT JOIN users u ON u.id=i.created_by WHERE i.id=? AND i.status=1 AND i.admin_id=?" . ($role === 'staff' ? " AND i.created_by=" . (int)$_SESSION['user_id'] : ""));
        $st->execute([$view_id, $scope_admin]);
    } else {
        $st = $pdo->prepare("SELECT i.*, u.full_name AS made_by FROM invoices i LEFT JOIN users u ON u.id=i.created_by WHERE i.id=? AND i.status=1");
        $st->execute([$view_id]);
    }
    $invoice = $st->fetch();
    if ($invoice) {
        $st = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY id");
        $st->execute([$view_id]);
        $items = $st->fetchAll();
    }
}
$logo_path = 'uploads/bill.png';
$company   = load_company($pdo, $invoice ? ($invoice['admin_id'] ?: $scope_admin) : $scope_admin);

/* ------------------------------------------------------------------
 * Invoice list
 * ------------------------------------------------------------------ */
$recent_invoices = [];
$inv_total = 0; $inv_sum = 0.0; $page = 1; $per = 25;
$fq = trim((string)($_GET['q'] ?? '')); $fd1 = (string)($_GET['d1'] ?? ''); $fd2 = (string)($_GET['d2'] ?? '');
if (!$invoice) {
    [$page, $per] = page_params(25);
    [$where, $params] = invoice_where($role, $list_admin, $_GET, true);   // search / date filters run in SQL, so they cover every page

    $cs = $pdo->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(i.total_amount),0) AS s FROM invoices i $where");
    $cs->execute($params);
    $row = $cs->fetch();
    $inv_total = (int)$row['c']; $inv_sum = (float)$row['s'];
    $page = min($page, max(1, (int)ceil($inv_total / $per)));
    $offset = ($page - 1) * $per;

    $st = $pdo->prepare("SELECT i.*, a.full_name AS admin_name, c.full_name AS made_by,
                                (SELECT COUNT(*) FROM invoice_items ii WHERE ii.invoice_id=i.id) AS item_count
                         FROM invoices i
                         LEFT JOIN users a ON a.id=i.admin_id
                         LEFT JOIN users c ON c.id=i.created_by
                         $where ORDER BY i.created_at DESC, i.id DESC LIMIT " . (int)$per . " OFFSET " . (int)$offset);
    $st->execute($params);
    $recent_invoices = $st->fetchAll();
}
// query string that carries the current filters into the Excel export
$export_qs = http_build_query(array_filter(['admin' => $filter_admin ?: '', 'q' => $fq, 'd1' => $fd1, 'd2' => $fd2], fn($v) => $v !== ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $invoice ? 'Invoice #' . e($invoice['invoice_no']) : 'Invoices'; ?> | Pharma Billing</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
<?php echo ui_css(); ?>
<?php echo ui_invoice_css(); ?>
.view-bar{max-width:210mm;margin:16px auto;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;padding:0 8px}
.view-bar h2{margin:0;font-size:18px;color:var(--primary)}
.filter-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.sumline{display:flex;gap:18px;flex-wrap:wrap;margin-bottom:14px;font-size:14px}
@media(max-width:768px){.filter-row>*{width:100%}.view-bar{padding:0 10px}}
</style>
</head>
<body>
<?php echo ui_nav('reports'); ?>

<?php if ($invoice):
    $totalGross = 0; $totalDiscount = 0;
    foreach ($items as $it) {
        $line = (float)$it['rate'] * (int)$it['qty'];
        $totalGross += $line;
        $totalDiscount += $line * ((float)$it['discount'] / 100);
    }
    $taxable = $totalGross - $totalDiscount;
    $gstPct  = (float)($invoice['gst_percent'] ?? 5);
?>
    <div class="view-bar no-print">
        <div>
            <h2>Invoice #<?php echo e($invoice['invoice_no']); ?></h2>
            <span style="font-size:12px;color:var(--muted);">Saved invoice · created by <?php echo e($invoice['made_by'] ?: 'N/A'); ?></span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a class="btn btn-light" href="reports.php<?php echo $filter_admin ? '?admin=' . $filter_admin : ''; ?>">← Back</a>
            <button class="btn btn-primary" onclick="window.print()">🖨️ Print</button>
            <?php if ($can_cancel): ?>
            <form method="POST" data-confirm="Cancel invoice #<?php echo e($invoice['invoice_no']); ?>?" data-confirm-text="The sold quantities will be added back to stock." data-confirm-btn="Yes, cancel invoice" data-danger style="display:inline">
                <input type="hidden" name="id" value="<?php echo (int)$invoice['id']; ?>">
                <button class="btn btn-danger" name="cancel_invoice" value="1">🗑️ Cancel invoice</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="invoice-canvas">
        <div style="text-align:center;font-weight:800;font-size:14px;margin-bottom:10px;text-decoration:underline;">GST INVOICE</div>
        <div class="inv-header">
            <div>
                <h1><?php echo e($company['name']); ?></h1>
                <div class="inv-sub">
                    <?php echo nl2br(e($company['address'])); ?><?php if ($company['address']): ?><br><?php endif; ?>
                    <?php if ($company['gstin']): ?><strong>GSTIN: <?php echo e($company['gstin']); ?></strong><?php endif; ?>
                    <?php if ($company['phone']): ?> | <strong>Phone:</strong> <?php echo e($company['phone']); ?><?php endif; ?>
                </div>
            </div>
            <div class="logo-box">
                <?php if (file_exists($logo_path)): ?><img src="<?php echo e($logo_path); ?>?v=<?php echo filemtime($logo_path); ?>" alt="logo"><?php endif; ?>
            </div>
        </div>

        <div class="invoice-frame">
            <div class="row-split">
                <div class="col-box">
                    <h3>Billing Details (Bill To)</h3>
                    <div class="kv"><span>Name:</span><span style="font-weight:800"><?php echo e($invoice['customer_name']); ?></span></div>
                    <div class="kv"><span>Address:</span><span><?php echo e($invoice['customer_address']); ?></span></div>
                    <div class="kv"><span>Phone:</span><span><?php echo e($invoice['customer_phone']); ?></span></div>
                </div>
                <div class="col-box">
                    <div class="kv"><span style="width:90px">Invoice No:</span><strong><?php echo e($invoice['invoice_no']); ?></strong></div>
                    <div class="kv"><span style="width:90px">Date:</span><span><?php echo date('d-m-Y', strtotime($invoice['created_at'])); ?></span></div>
                    <div class="kv"><span style="width:90px">Payment:</span><span>Cash</span></div>
                </div>
            </div>

            <table class="inv">
                <thead>
                    <tr><th width="30">SR</th><th>Product Information</th><th width="70">Batch</th><th width="90">Expiry</th><th width="80">Capacity</th><th width="80">Rate</th><th width="50">Qty</th><th width="55">Dis %</th><th width="100">Gross Amt</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $idx => $it): ?>
                    <tr>
                        <td class="c-sr" data-label="Sr"><?php echo $idx + 1; ?></td>
                        <td class="c-prod" data-label="Product"><strong><?php echo e($it['product_name']); ?></strong></td>
                        <td data-label="Batch"><?php echo e($it['batch']); ?></td>
                        <td data-label="Expiry"><?php echo e($it['expiry'] ?: '-'); ?></td>
                        <td data-label="Capacity"><?php echo e($it['capacity'] ?: '-'); ?></td>
                        <td data-label="Rate">₹<?php echo number_format((float)$it['rate'], 2); ?></td>
                        <td data-label="Qty"><?php echo (int)$it['qty']; ?></td>
                        <td data-label="Discount"><?php echo e(rtrim(rtrim(number_format((float)$it['discount'], 2), '0'), '.')); ?>%</td>
                        <td data-label="Amount" style="font-weight:700">₹<?php echo number_format((float)$it['rate'] * (int)$it['qty'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="footer-summary">
                <div class="summary-left">
                    <div style="display:flex;justify-content:space-between;font-weight:700;font-size:11px;"><span>E &amp; O.E</span><span>Total Items: <?php echo count($items); ?></span></div>
                    <div style="margin-top:14px;">
                        <span style="font-size:10px;font-weight:700;color:#64748b;">AMOUNT IN WORDS:</span>
                        <div style="font-weight:800;text-transform:uppercase;font-size:13px;"><?php echo e(num_to_words((int)round((float)$invoice['total_amount']))); ?></div>
                    </div>
                    <?php if ($company['bank_name'] || $company['bank_account']): ?>
                    <div style="margin-top:26px;font-size:10px;border-top:1px solid #eee;padding-top:8px;">
                        <strong>BANK:</strong> <?php echo e($company['bank_name']); ?> | <strong>A/C:</strong> <?php echo e($company['bank_account']); ?><br>
                        <strong>IFSC:</strong> <?php echo e($company['bank_ifsc']); ?> | <strong>BRANCH:</strong> <?php echo e($company['bank_branch']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div>
                    <div style="padding:10px 15px;">
                        <div class="sum-row"><span>Total Gross:</span><span>₹<?php echo number_format($totalGross, 2); ?></span></div>
                        <div class="sum-row"><span>Total Discount:</span><span>₹<?php echo number_format($totalDiscount, 2); ?></span></div>
                        <div class="sum-row"><span>Taxable Value:</span><span>₹<?php echo number_format($taxable, 2); ?></span></div>
                        <div class="sum-row"><span>GST (<?php echo e(rtrim(rtrim(number_format($gstPct, 2), '0'), '.')); ?>%)</span><span>₹<?php echo number_format($taxable * $gstPct / 100, 2); ?></span></div>
                    </div>
                    <div class="grand-total-line"><span>GRAND TOTAL</span><span>₹<?php echo number_format((float)$invoice['total_amount'], 2); ?></span></div>
                </div>
            </div>
        </div>

        <div class="sign-row">
            <div style="width:180px">RECEIVER'S SIGNATURE</div>
            <div style="width:230px">FOR <?php echo e($company['name']); ?></div>
        </div>
    </div>

<?php else: ?>
    <div class="container">
        <?php echo flash_show(); ?>
        <?php if ($view_id): ?><div class="alert alert-error">Invoice not found.</div><?php endif; ?>
        <div class="card">
            <div class="card-title">📊 Invoices
                <a class="btn btn-primary btn-sm" href="billing.php<?php echo $is_super && $filter_admin ? '?for=' . $filter_admin : ''; ?>">＋ New bill</a>
            </div>
            <form method="GET" class="filter-row">
                <input type="hidden" name="per" value="<?php echo (int)$per; ?>">
                <?php if ($is_super): ?>
                <select name="admin" onchange="this.form.submit()">
                    <option value="">All admins</option>
                    <?php foreach ($admins as $a): ?>
                        <option value="<?php echo (int)$a['id']; ?>" <?php echo $filter_admin === (int)$a['id'] ? 'selected' : ''; ?>><?php echo e($a['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                <input type="search" name="q" class="search-bar" value="<?php echo e($fq); ?>" placeholder="🔍 Customer / invoice no / phone">
                <input type="date" name="d1" value="<?php echo e($fd1); ?>" title="From date">
                <input type="date" name="d2" value="<?php echo e($fd2); ?>" title="To date">
                <button class="btn btn-primary btn-sm">Search</button>
                <?php if ($fq !== '' || $fd1 !== '' || $fd2 !== '' || $filter_admin): ?><a class="btn btn-light btn-sm" href="reports.php">Reset</a><?php endif; ?>
            </form>
            <div class="sumline">
                <span>Invoices: <strong><?php echo number_format($inv_total); ?></strong></span>
                <span>Total: <strong>₹<?php echo number_format($inv_sum, 2); ?></strong></span>
                <?php if ($can_cancel): ?>
                <span style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
                    <a class="btn btn-excel btn-sm" href="export.php?type=invoices<?php echo $export_qs ? '&' . e($export_qs) : ''; ?>" title="Download the invoices matching the filters above">⬇ Excel (this list)</a>
                    <a class="btn btn-light btn-sm" href="export.php?type=all<?php echo $filter_admin ? '&admin=' . $filter_admin : ''; ?>" title="Stock + every invoice + items">💾 Full backup</a>
                </span>
                <?php endif; ?>
            </div>

            <table class="data rt" id="invTable">
                <thead>
                    <tr><th>Invoice</th><?php if ($is_super): ?><th>Admin</th><?php endif; ?><th>Customer</th><?php if ($role !== 'staff'): ?><th>Made by</th><?php endif; ?><th>Items</th><th>Amount</th><th>Date</th><th style="text-align:right">Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($recent_invoices as $inv): ?>
                    <tr>
                        <td data-label="Invoice"><strong>#<?php echo e($inv['invoice_no']); ?></strong></td>
                        <?php if ($is_super): ?><td data-label="Admin"><span class="badge b-blue"><?php echo e($inv['admin_name'] ?? '—'); ?></span></td><?php endif; ?>
                        <td data-label="Customer"><?php echo e($inv['customer_name']); ?><?php if (!empty($inv['customer_phone'])): ?><br><small class="muted" style="color:var(--muted)"><?php echo e($inv['customer_phone']); ?></small><?php endif; ?></td>
                        <?php if ($role !== 'staff'): ?><td data-label="Made by"><?php echo e($inv['made_by'] ?: '—'); ?></td><?php endif; ?>
                        <td data-label="Items"><?php echo (int)$inv['item_count']; ?></td>
                        <td data-label="Amount"><strong>₹<?php echo number_format((float)$inv['total_amount'], 2); ?></strong></td>
                        <td data-label="Date"><?php echo date('d-M-Y', strtotime($inv['created_at'])); ?></td>
                        <td data-label="Action" style="text-align:right;white-space:nowrap">
                            <a class="btn btn-primary btn-sm" href="?view=<?php echo (int)$inv['id']; ?>">View</a>
                            <?php if ($can_cancel): ?>
                            <form method="POST" style="display:inline" data-confirm="Cancel invoice #<?php echo e($inv['invoice_no']); ?>?" data-confirm-text="Stock will be added back." data-confirm-btn="Yes, cancel invoice" data-danger>
                                <input type="hidden" name="id" value="<?php echo (int)$inv['id']; ?>">
                                <button class="btn btn-danger btn-sm" name="cancel_invoice" value="1">Cancel</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recent_invoices)): ?>
                    <tr><td colspan="8" class="no-label" style="text-align:center;color:#94a3b8;padding:30px;"><?php echo ($fq !== '' || $fd1 !== '' || $fd2 !== '') ? 'No invoices match these filters.' : 'No invoices yet.'; ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <?php echo pager($inv_total, $page, $per); ?>
        </div>
    </div>
<?php endif; ?>
</body>
</html>
