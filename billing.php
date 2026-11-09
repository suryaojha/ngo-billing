<?php
session_start();
require 'config.php';
require 'ui.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role    = $_SESSION['role'];
$user_id = (int)$_SESSION['user_id'];

/* ------------------------------------------------------------------
 * Which business (admin) are we billing for?
 *   staff       -> the admin they belong to
 *   admin       -> themselves
 *   superadmin  -> any active admin they pick ("bill on behalf of");
 *                  stock is deducted from, and the invoice is filed under,
 *                  THAT admin. created_by still records the superadmin.
 * ------------------------------------------------------------------ */
$stock_admin_id = null;
$bill_admins    = [];   // superadmin: list of admins to choose from
$need_pick      = false;

if ($role === 'staff') {
    $stock_admin_id = (int)($_SESSION['admin_id'] ?? 0) ?: null;
    if (!$stock_admin_id) {
        die('Your account is not linked to a business yet. Please ask your admin/super admin to fix your account before billing.');
    }
} elseif ($role === 'admin') {
    $stock_admin_id = $user_id;
} elseif ($role === 'superadmin') {
    $bill_admins = $pdo->query("SELECT id, full_name, company_name FROM users WHERE role='admin' AND IsActive=1 ORDER BY full_name")->fetchAll();
    $valid_ids   = array_map('intval', array_column($bill_admins, 'id'));

    $req = $_POST['for_admin'] ?? $_GET['for'] ?? null;
    if ($req !== null && $req !== '') {
        $req = (int)$req;
        if ($req > 0 && in_array($req, $valid_ids, true)) {
            $_SESSION['bill_for'] = $req;
        } else {
            unset($_SESSION['bill_for']);   // ?for=0 -> "change admin"
        }
    }
    $cur = (int)($_SESSION['bill_for'] ?? 0);
    if ($cur && in_array($cur, $valid_ids, true)) {
        $stock_admin_id = $cur;
    } elseif (count($valid_ids) === 1) {
        $stock_admin_id = $valid_ids[0];
        $_SESSION['bill_for'] = $stock_admin_id;
    } else {
        $need_pick = true;
    }
}

/* ------------------------------------------------------------------
 * AJAX: product search (only this admin's in-stock products)
 * ------------------------------------------------------------------ */
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    header('Content-Type: application/json');
    if (!$stock_admin_id) { echo '[]'; exit; }
    // Medicine names are ASCII; dropping other chars avoids latin1/utf8mb4 collation errors on LIKE.
    $term = preg_replace('/[^\x20-\x7E]/', '', (string)($_GET['term'] ?? ''));
    $like = '%' . addcslashes(trim($term), '%_\\') . '%';
    try {
        $stmt = $pdo->prepare("SELECT id,name,batch,expiry,capacity,rate,stock_qty,gst_percent FROM products
                               WHERE admin_id=? AND stock_qty>0 AND (name LIKE ? OR batch LIKE ?)
                               ORDER BY name, expiry LIMIT 30");
        $stmt->execute([$stock_admin_id, $like, $like]);
        echo json_encode($stmt->fetchAll());
    } catch (Exception $e) {
        error_log('Product search error: ' . $e->getMessage());
        echo '[]';
    }
    exit;
}

/* ------------------------------------------------------------------
 * AJAX: save invoice  (stock can never go below zero)
 * ------------------------------------------------------------------ */
class BillError extends Exception {}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_invoice'])) {
    header('Content-Type: application/json');
    if (!$stock_admin_id) {
        echo json_encode(['status' => 'error', 'msg' => 'Choose which admin you are billing for first.']);
        exit;
    }
    $data = json_decode($_POST['invoice_data'] ?? '', true);
    if (!is_array($data) || empty($data['items']) || !is_array($data['items'])) {
        echo json_encode(['status' => 'error', 'msg' => 'No items in invoice.']);
        exit;
    }
    $custName = trim((string)($data['customer_name'] ?? ''));
    if ($custName === '') {
        echo json_encode(['status' => 'error', 'msg' => 'Please enter the customer name.']);
        exit;
    }
    $custAddr  = trim((string)($data['customer_address'] ?? ''));
    $custPhone = trim((string)($data['customer_phone'] ?? ''));
    $gstPercent = (isset($data['gst_percent']) && is_numeric($data['gst_percent'])) ? (float)$data['gst_percent'] : 5.0;
    $gstPercent = max(0, min(100, $gstPercent));

    // Normalise requested lines: product id, whole-number qty >= 1, discount 0-100.
    $req_lines = [];
    foreach ($data['items'] as $it) {
        $pid = (int)($it['id'] ?? 0);
        $qty = $it['qty'] ?? 0;
        if ($pid <= 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Please pick every product from the search list so stock can be tracked.']);
            exit;
        }
        if (!is_numeric($qty) || (int)$qty != $qty || (int)$qty < 1) {
            echo json_encode(['status' => 'error', 'msg' => 'Quantity must be a whole number of at least 1.']);
            exit;
        }
        $dis = (isset($it['dis']) && is_numeric($it['dis'])) ? (float)$it['dis'] : 0.0;
        $req_lines[] = ['pid' => $pid, 'qty' => (int)$qty, 'dis' => max(0, min(100, $dis))];
    }

    $maxAttempts = 5;
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        try {
            $pdo->beginTransaction();

            // Next invoice number for THIS admin's own sequence.
            $r = $pdo->prepare("SELECT MAX(CAST(invoice_no AS UNSIGNED)) AS m FROM invoices WHERE admin_id <=> ? FOR UPDATE");
            $r->execute([$stock_admin_id]);
            $mx = (int)($r->fetch()['m'] ?? 0);
            $invoice_no = str_pad((string)($mx + 1), 3, '0', STR_PAD_LEFT);

            // Lock every product row involved (sorted ids -> no deadlocks) and read the TRUE price/stock from the DB.
            $pids = array_values(array_unique(array_column($req_lines, 'pid')));
            sort($pids);
            $products = [];
            $pq = $pdo->prepare("SELECT id,name,batch,expiry,capacity,rate,stock_qty FROM products WHERE id=? AND admin_id=? FOR UPDATE");
            foreach ($pids as $pid) {
                $pq->execute([$pid, $stock_admin_id]);
                $p = $pq->fetch();
                if (!$p) throw new BillError('A selected product no longer exists in this stock. Please re-select it.');
                $products[$pid] = $p;
            }

            // Total requested per product must fit in stock (handles the same product on two rows).
            $need = [];
            foreach ($req_lines as $l) $need[$l['pid']] = ($need[$l['pid']] ?? 0) + $l['qty'];
            foreach ($need as $pid => $q) {
                if ($q > (int)$products[$pid]['stock_qty']) {
                    throw new BillError("Insufficient stock for {$products[$pid]['name']} (Batch: {$products[$pid]['batch']}). Available: " . (int)$products[$pid]['stock_qty'] . ", requested: $q.");
                }
            }

            // Totals computed on the server (the browser total is only a preview).
            $totalGross = 0.0; $totalDisc = 0.0; $lines = [];
            foreach ($req_lines as $l) {
                $p = $products[$l['pid']];
                $gross = round((float)$p['rate'] * $l['qty'], 2);
                $totalGross += $gross;
                $totalDisc  += $gross * ($l['dis'] / 100);
                $lines[] = $l + ['p' => $p, 'gross' => $gross];
            }
            $taxable = $totalGross - $totalDisc;
            $grand   = round($taxable + $taxable * ($gstPercent / 100));

            $ins = $pdo->prepare("INSERT INTO invoices (invoice_no,customer_name,customer_address,customer_phone,total_amount,gst_percent,admin_id,created_by) VALUES (?,?,?,?,?,?,?,?)");
            $ins->execute([$invoice_no, $custName, $custAddr, $custPhone, $grand, $gstPercent, $stock_admin_id, $user_id]);
            $invoice_db_id = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id,product_id,product_name,batch,expiry,capacity,qty,rate,discount,gross_amount) VALUES (?,?,?,?,?,?,?,?,?,?)");
            // Guarded update: only succeeds while enough stock remains -> never negative.
            $upd = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id=? AND admin_id <=> ? AND stock_qty >= ?");
            foreach ($lines as $l) {
                $p = $l['p'];
                $itemStmt->execute([$invoice_db_id, $p['id'], $p['name'], $p['batch'], $p['expiry'] ?? '', $p['capacity'] ?? '', $l['qty'], $p['rate'], $l['dis'], $l['gross']]);
                $upd->execute([$l['qty'], $p['id'], $stock_admin_id, $l['qty']]);
                if ($upd->rowCount() !== 1) {
                    throw new BillError("Insufficient stock for {$p['name']} (Batch: {$p['batch']}).");
                }
            }
            $pdo->commit();
            echo json_encode([
                'status' => 'success', 'msg' => "Invoice #$invoice_no saved & stock updated!",
                'invoice_no' => $invoice_no, 'invoice_id' => $invoice_db_id, 'grand' => $grand,
            ]);
            exit;
        } catch (BillError $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            // 23000 = duplicate invoice number picked by another tab/user a split second earlier -> retry with the next number
            if ($e->getCode() == 23000 && $attempt < $maxAttempts - 1) continue;
            error_log('Invoice save error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'msg' => 'Could not save invoice. Please try again.']);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Invoice save error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'msg' => 'Could not save invoice. Please try again.']);
            exit;
        }
    }
    echo json_encode(['status' => 'error', 'msg' => 'Could not save invoice after several attempts. Please try again.']);
    exit;
}

/* ------------------------------------------------------------------
 * Page data
 * ------------------------------------------------------------------ */
$company      = load_company($pdo, $stock_admin_id);
$company_name = $company['name'];
$logo_path    = 'uploads/bill.png';
$next_inv_no  = '001';
if ($stock_admin_id) {
    $q = $pdo->prepare("SELECT MAX(CAST(invoice_no AS UNSIGNED)) AS m FROM invoices WHERE admin_id <=> ?");
    $q->execute([$stock_admin_id]);
    $next_inv_no = str_pad((string)(((int)($q->fetch()['m'] ?? 0)) + 1), 3, '0', STR_PAD_LEFT);
}
$bill_for_name = '';
foreach ($bill_admins as $a) if ((int)$a['id'] === (int)$stock_admin_id) $bill_for_name = $a['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billing Console - <?php echo e($company_name); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
<?php echo ui_css(); ?>
<?php echo ui_invoice_css(); ?>
.bill-page{max-width:1000px;margin:18px auto;padding:0 14px}
.bill-toolbar{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;box-shadow:0 4px 10px -4px rgba(0,0,0,.06)}
.bill-toolbar h2{margin:0;font-size:18px;color:var(--primary)}
.bill-actions{display:flex;gap:8px;flex-wrap:wrap}
.for-box{background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:13px}
.for-box select{min-width:200px}
input.edit-field{border:1px solid transparent;background:transparent;font-family:inherit;font-size:inherit;color:inherit;width:100%;padding:3px 4px;border-radius:4px;outline:none}
input.edit-field:hover{background:#f8fafc;border-bottom:1px dashed #cbd5e1}
input.edit-field:focus{background:#fff;border:1px solid var(--primary)}
input.edit-field[readonly]{cursor:default}
.add-row-btn{padding:12px;text-align:center;background:#f0fdf4;color:#16a34a;font-weight:700;cursor:pointer;border-bottom:2px solid #000;user-select:none}
.add-row-btn:hover{background:#dcfce7}
.search-results{position:absolute;background:#fff;border:1px solid #cbd5e1;z-index:3000;box-shadow:0 12px 28px rgba(0,0,0,.18);border-radius:10px;display:none;text-align:left;max-height:50vh;overflow:auto;min-width:280px}
.search-item{padding:10px 12px;cursor:pointer;border-bottom:1px solid #f1f5f9;font-size:13px}
.search-item:last-child{border-bottom:none}
.search-item:hover,.search-item.hl{background:#eff6ff}
.search-item small{color:#64748b;display:block;margin-top:2px}
.c-del{cursor:pointer;color:#ef4444;font-weight:700;font-size:16px}
.mbar{display:none}
.pick-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px}
.pick-card{display:block;background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;text-decoration:none;color:inherit}
.pick-card:hover{border-color:var(--primary);box-shadow:0 8px 20px -8px rgba(37,99,235,.35)}
.pick-card strong{display:block;font-size:16px}.pick-card small{color:var(--muted)}
@media screen and (max-width:768px){
 .bill-page{padding:0 8px;margin:10px auto}
 .bill-toolbar{padding:12px}
 .bill-toolbar .bill-actions{display:none}
 input.edit-field{border:1px solid #cbd5e1;background:#f8fafc;padding:9px 10px;font-size:16px;border-radius:8px}
 .kv{flex-direction:column;align-items:stretch}.kv>span:first-child{width:auto;font-size:11px;color:#64748b;text-transform:uppercase}
 .mbar{display:flex;position:fixed;left:0;right:0;bottom:0;z-index:2000;background:#fff;border-top:1px solid var(--line);padding:10px 14px;gap:10px;align-items:center;justify-content:space-between;box-shadow:0 -6px 20px rgba(0,0,0,.1)}
 .mbar small{display:block;font-size:10px;color:var(--muted);text-transform:uppercase;font-weight:700}
 .mbar b{font-size:20px}
 .mbar .btn{padding:13px 20px}
 .add-row-btn{border-radius:10px;margin:8px;border:1px dashed #16a34a}
 .toast{bottom:90px}
}
</style>
</head>
<body>
<?php echo ui_nav('billing'); ?>

<div class="bill-page">
<?php if ($need_pick): ?>
    <div class="card">
        <div class="card-title">🧾 Who are you billing for?</div>
        <p style="color:var(--muted);margin-top:-6px;">As Super Admin you create bills on behalf of an admin. Stock is deducted from that admin's inventory and the invoice is filed under their business.</p>
        <?php if (empty($bill_admins)): ?>
            <div class="alert alert-info">No active admins yet. <a href="superadmin.php?tab=create">Create an admin</a> first.</div>
        <?php else: ?>
        <div class="pick-grid">
            <?php foreach ($bill_admins as $a): ?>
                <a class="pick-card" href="billing.php?for=<?php echo (int)$a['id']; ?>">
                    <strong><?php echo e($a['full_name']); ?></strong>
                    <small><?php echo e($a['company_name'] ?: 'Company not set'); ?></small>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div></body></html>
<?php exit; endif; ?>

    <?php if ($role === 'superadmin'): ?>
    <div class="for-box no-print">
        <strong>📌 Billing on behalf of:</strong>
        <select onchange="location.href='billing.php?for='+this.value">
            <?php foreach ($bill_admins as $a): ?>
                <option value="<?php echo (int)$a['id']; ?>" <?php echo (int)$a['id'] === (int)$stock_admin_id ? 'selected' : ''; ?>>
                    <?php echo e($a['full_name'] . ($a['company_name'] ? ' — ' . $a['company_name'] : '')); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span style="color:#1e40af;">Stock is taken from this admin's inventory.</span>
    </div>
    <?php endif; ?>

    <div class="bill-toolbar no-print">
        <div>
            <h2>New Invoice</h2>
            <span style="font-size:12px;color:var(--muted);">Next invoice no: <strong id="nextNo"><?php echo e($next_inv_no); ?></strong> · by <?php echo e($_SESSION['full_name']); ?></span>
        </div>
        <div class="bill-actions">
            <button class="btn btn-success js-save" onclick="saveInvoiceToDB()">💾 Save &amp; Update Stock</button>
            <button class="btn btn-primary js-print" style="display:none" onclick="doPrint()">🖨️ Print</button>
            <button class="btn btn-light js-new" style="display:none" onclick="location.href='billing.php'">➕ New Bill</button>
        </div>
    </div>

    <div class="invoice-canvas" id="invoiceCanvas">
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
                <?php if (file_exists($logo_path)): ?>
                    <img src="<?php echo e($logo_path); ?>?v=<?php echo filemtime($logo_path); ?>" alt="logo">
                <?php endif; ?>
            </div>
        </div>

        <div class="invoice-frame">
            <div class="row-split">
                <div class="col-box">
                    <h3>Billing Details (Bill To)</h3>
                    <div class="kv"><span>Name:</span><input type="text" id="cust_name" class="edit-field" style="font-weight:800;" placeholder="Customer name *" autocomplete="off"></div>
                    <div class="kv"><span>Address:</span><input type="text" id="cust_addr" class="edit-field" placeholder="Address" autocomplete="off"></div>
                    <div class="kv"><span>Phone:</span><input type="tel" id="cust_phone" class="edit-field" placeholder="Phone" inputmode="tel" autocomplete="off"></div>
                </div>
                <div class="col-box">
                    <div class="kv"><span style="width:90px">Invoice No:</span><strong id="inv_no"><?php echo e($next_inv_no); ?> <small style="color:#64748b;font-weight:400">(auto)</small></strong></div>
                    <div class="kv"><span style="width:90px">Date:</span><span style="font-weight:400"><?php echo date('d-m-Y'); ?></span></div>
                    <div class="kv"><span style="width:90px">Payment:</span><input type="text" class="edit-field" style="width:110px" value="Cash"></div>
                </div>
            </div>

            <table class="inv">
                <thead>
                    <tr>
                        <th width="30">SR</th><th>Product Information</th><th width="70">Batch</th><th width="90">Expiry</th>
                        <th width="80">Capacity</th><th width="80">Rate</th><th width="60">Qty</th><th width="60">Dis %</th><th width="100">Gross Amt</th><th class="no-print" width="30"></th>
                    </tr>
                </thead>
                <tbody id="invoice_body"></tbody>
            </table>

            <div class="add-row-btn no-print" onclick="addNewRow(true)">＋ ADD PRODUCT</div>

            <div class="footer-summary">
                <div class="summary-left">
                    <div style="display:flex;justify-content:space-between;font-weight:700;font-size:11px;"><span>E &amp; O.E</span><span>Total Items: <span id="ui_item_count">0</span></span></div>
                    <div style="margin-top:14px;">
                        <span style="font-size:10px;font-weight:700;color:#64748b;">AMOUNT IN WORDS:</span>
                        <div id="ui_amount_words" style="font-weight:800;text-transform:uppercase;font-size:13px;">Zero Rupees Only</div>
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
                        <div class="sum-row"><span>Total Gross:</span><span id="ui_total_gross">0.00</span></div>
                        <div class="sum-row"><span>Total Discount:</span><span id="ui_total_discount">0.00</span></div>
                        <div class="sum-row"><span>Taxable Value:</span><span id="ui_taxable">0.00</span></div>
                        <div class="sum-row"><span>GST (<input type="number" id="ui_gst_per" value="5" min="0" max="100" step="0.01" oninput="calc()" style="width:52px;padding:2px 4px;border:1px solid #cbd5e1;border-radius:4px;font-weight:700">%)</span><span id="ui_gst_amt">0.00</span></div>
                    </div>
                    <div class="grand-total-line"><span>GRAND TOTAL</span><span id="ui_grand">0.00</span></div>
                </div>
            </div>
        </div>

        <div class="sign-row">
            <div style="width:180px">RECEIVER'S SIGNATURE</div>
            <div style="width:230px">FOR <?php echo e($company_name); ?></div>
        </div>
    </div>
</div>

<div class="mbar no-print">
    <div><small>Grand Total</small><b id="m_grand">₹0</b></div>
    <button class="btn btn-success js-save" onclick="saveInvoiceToDB()">💾 Save</button>
    <button class="btn btn-primary js-print" style="display:none" onclick="doPrint()">🖨️ Print</button>
    <button class="btn btn-light js-new" style="display:none" onclick="location.href='billing.php'">➕ New</button>
</div>

<div id="searchResults" class="search-results"></div>
<div id="toast" class="toast"></div>

<script>
const FOR_ADMIN = <?php echo json_encode($role === 'superadmin' ? (int)$stock_admin_id : null); ?>;
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let activeRow = null, results = [], hl = -1, searchTimer = null, saved = false;

function toast(msg, kind) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = 'toast ' + (kind || '');
    t.style.display = 'block';
    clearTimeout(toast._t); toast._t = setTimeout(() => t.style.display = 'none', 4200);
}

function numToWords(n) {
    const a=['','One ','Two ','Three ','Four ','Five ','Six ','Seven ','Eight ','Nine ','Ten ','Eleven ','Twelve ','Thirteen ','Fourteen ','Fifteen ','Sixteen ','Seventeen ','Eighteen ','Nineteen '];
    const b=['','','Twenty ','Thirty ','Forty ','Fifty ','Sixty ','Seventy ','Eighty ','Ninety '];
    n = Math.floor(n);
    if (n <= 0) return 'Zero Rupees Only';
    if (n > 999999999) return 'Too Large';
    const w = x => {
        let s = '';
        if (x >= 10000000) { s += w(Math.floor(x/10000000)) + 'Crore '; x %= 10000000; }
        if (x >= 100000)   { s += w(Math.floor(x/100000)) + 'Lakh '; x %= 100000; }
        if (x >= 1000)     { s += w(Math.floor(x/1000)) + 'Thousand '; x %= 1000; }
        if (x >= 100)      { s += a[Math.floor(x/100)] + 'Hundred '; x %= 100; }
        if (x >= 20)       { s += b[Math.floor(x/10)]; x %= 10; }
        if (x > 0)         { s += a[x]; }
        return s;
    };
    return w(n).trim() + ' Rupees Only';
}

function addNewRow(focus) {
    const tbody = document.getElementById('invoice_body');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td class="c-sr" data-label="Sr"></td>
        <td class="c-prod" data-label="Product (type name or batch)"><input type="text" class="edit-field p-info" placeholder="Search product..." autocomplete="off" data-pid=""></td>
        <td data-label="Batch"><input type="text" class="edit-field p-batch" readonly tabindex="-1"></td>
        <td data-label="Expiry"><input type="text" class="edit-field p-exp" readonly tabindex="-1"></td>
        <td data-label="Capacity"><input type="text" class="edit-field p-cap" readonly tabindex="-1"></td>
        <td data-label="Rate (₹)"><input type="number" class="edit-field p-rate" value="0" readonly tabindex="-1"></td>
        <td data-label="Qty"><input type="number" class="edit-field p-qty" inputmode="numeric" min="0" step="1" value="" placeholder="0" data-stock="0"></td>
        <td data-label="Discount %"><input type="number" class="edit-field p-dis" inputmode="decimal" min="0" max="100" step="0.01" value="0"></td>
        <td class="p-gross" data-label="Amount" style="font-weight:700">0.00</td>
        <td class="c-del no-print" title="Remove">✕</td>`;
    const info = row.querySelector('.p-info'), qty = row.querySelector('.p-qty');
    info.addEventListener('focus', () => queueSearch(info, 0));
    info.addEventListener('input', () => { clearRowProduct(row); queueSearch(info, 180); });
    info.addEventListener('keydown', onSearchKey);
    qty.addEventListener('input', () => { checkStock(qty); calc(); });
    row.querySelector('.p-dis').addEventListener('input', () => {
        const d = row.querySelector('.p-dis'); let v = parseFloat(d.value);
        if (v > 100) d.value = 100; if (v < 0) d.value = 0; calc();
    });
    row.querySelector('.c-del').addEventListener('click', () => { row.remove(); calc(); });
    tbody.appendChild(row);
    calc();
    if (focus) info.focus();
}

function clearRowProduct(row) {
    row.querySelector('.p-info').dataset.pid = '';
    ['.p-batch','.p-exp','.p-cap'].forEach(s => row.querySelector(s).value = '');
    row.querySelector('.p-rate').value = 0;
    row.querySelector('.p-qty').dataset.stock = 0;
    calc();
}

function queueSearch(input, delay) {
    activeRow = input.closest('tr');
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => runSearch(input), delay);
}

function runSearch(input) {
    const url = `billing.php?action=search&term=${encodeURIComponent(input.value || '')}` + (FOR_ADMIN ? `&for=${FOR_ADMIN}` : '');
    fetch(url).then(r => r.json()).then(data => {
        results = data; hl = -1;
        const box = document.getElementById('searchResults');
        box.innerHTML = '';
        if (!data.length) {
            box.innerHTML = '<div class="search-item" style="color:#94a3b8">No in-stock products found</div>';
        }
        data.forEach((p, i) => {
            const d = document.createElement('div');
            d.className = 'search-item';
            const st = (typeof p.expiry === 'string') ? p.expiry : '';
            d.innerHTML = `<strong>${esc(p.name)}</strong><small>Batch ${esc(p.batch)} · Exp ${esc(st)} · ₹${esc(p.rate)} · <b>Stock ${esc(p.stock_qty)}</b></small>`;
            d.addEventListener('mousedown', ev => { ev.preventDefault(); pickProduct(p); });
            box.appendChild(d);
        });
        const rect = input.getBoundingClientRect();
        box.style.left = Math.max(4, rect.left + window.scrollX) + 'px';
        box.style.top = (rect.bottom + window.scrollY + 2) + 'px';
        box.style.width = Math.max(rect.width, 280) + 'px';
        box.style.maxWidth = (window.innerWidth - 12) + 'px';
        box.style.display = 'block';
    }).catch(() => {});
}

function onSearchKey(e) {
    const box = document.getElementById('searchResults');
    const items = box.querySelectorAll('.search-item');
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        if (!results.length) return;
        hl = (hl + (e.key === 'ArrowDown' ? 1 : -1) + results.length) % results.length;
        items.forEach((el, i) => el.classList.toggle('hl', i === hl));
        items[hl] && items[hl].scrollIntoView({block: 'nearest'});
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (results.length) pickProduct(results[hl >= 0 ? hl : 0]);
    } else if (e.key === 'Escape') {
        box.style.display = 'none';
    }
}

function pickProduct(p) {
    if (!activeRow) return;
    const stock = Math.max(0, parseInt(p.stock_qty) || 0);
    if (stock <= 0) { toast('Out of stock', 'err'); return; }
    const r = activeRow;
    r.querySelector('.p-info').value = p.name;
    r.querySelector('.p-info').dataset.pid = p.id;
    r.querySelector('.p-batch').value = p.batch || '';
    r.querySelector('.p-exp').value = p.expiry || '';
    r.querySelector('.p-cap').value = p.capacity || '';
    r.querySelector('.p-rate').value = p.rate;
    const q = r.querySelector('.p-qty');
    q.value = ''; q.dataset.stock = stock;
    document.getElementById('searchResults').style.display = 'none';
    calc();
    q.focus();
}

/* Qty can never exceed what is in stock, counting the same product on other rows too. */
function checkStock(input) {
    const row = input.closest('tr');
    const pid = row.querySelector('.p-info').dataset.pid;
    const stock = parseInt(input.dataset.stock) || 0;
    let q = parseInt(input.value);
    if (isNaN(q) || q < 0) q = 0;
    let others = 0;
    document.querySelectorAll('#invoice_body tr').forEach(r => {
        if (r !== row && pid && r.querySelector('.p-info').dataset.pid === pid)
            others += parseInt(r.querySelector('.p-qty').value) || 0;
    });
    const max = Math.max(0, stock - others);
    if (q > max) { toast(`⚠️ Only ${max} in stock for this product`, 'err'); q = max; }
    if (input.value !== '' || q) input.value = q || '';
}

function calc() {
    let totalGross = 0, totalDiscount = 0, count = 0;
    const rows = document.querySelectorAll('#invoice_body tr');
    const gstP = Math.max(0, Math.min(100, parseFloat(document.getElementById('ui_gst_per').value) || 0));
    rows.forEach((r, idx) => {
        r.querySelector('.c-sr').innerText = idx + 1;
        const has = !!r.querySelector('.p-info').value.trim();
        r.classList.toggle('blank-row', !has);
        if (has) count++;
        const rate = parseFloat(r.querySelector('.p-rate').value) || 0;
        const qty = Math.max(0, parseFloat(r.querySelector('.p-qty').value) || 0);
        const dis = Math.max(0, Math.min(100, parseFloat(r.querySelector('.p-dis').value) || 0));
        const gross = rate * qty;
        r.querySelector('.p-gross').innerText = gross.toFixed(2);
        totalGross += gross; totalDiscount += gross * (dis / 100);
    });
    const taxable = totalGross - totalDiscount;
    const gstAmt = taxable * (gstP / 100);
    const grand = Math.round(taxable + gstAmt);
    document.getElementById('ui_item_count').innerText = count;
    document.getElementById('ui_total_gross').innerText = totalGross.toFixed(2);
    document.getElementById('ui_total_discount').innerText = totalDiscount.toFixed(2);
    document.getElementById('ui_taxable').innerText = taxable.toFixed(2);
    document.getElementById('ui_gst_amt').innerText = gstAmt.toFixed(2);
    document.getElementById('ui_grand').innerText = grand.toFixed(2);
    document.getElementById('m_grand').innerText = '₹' + grand.toLocaleString('en-IN');
    document.getElementById('ui_amount_words').innerText = numToWords(grand);
}

function setSaving(on) {
    document.querySelectorAll('.js-save').forEach(b => { b.disabled = on; b.textContent = on ? 'Saving...' : (b.closest('.mbar') ? '💾 Save' : '💾 Save & Update Stock'); });
}

function saveInvoiceToDB() {
    if (saved) return;
    const items = []; let bad = '';
    document.querySelectorAll('#invoice_body tr').forEach(r => {
        const info = r.querySelector('.p-info');
        const name = info.value.trim();
        if (!name) return;
        const qty = parseInt(r.querySelector('.p-qty').value) || 0;
        if (!info.dataset.pid) bad = `Pick "${name}" from the suggestions list.`;
        else if (qty < 1) bad = `Enter a quantity for "${name}".`;
        items.push({id: info.dataset.pid, qty, dis: r.querySelector('.p-dis').value});
    });
    if (!items.length) { toast('Add at least one product', 'err'); return; }
    if (bad) { toast(bad, 'err'); return; }
    const cust = document.getElementById('cust_name').value.trim();
    if (!cust) { toast('Enter the customer name', 'err'); document.getElementById('cust_name').focus(); return; }

    setSaving(true);
    const data = {
        customer_name: cust,
        customer_address: document.getElementById('cust_addr').value,
        customer_phone: document.getElementById('cust_phone').value,
        gst_percent: document.getElementById('ui_gst_per').value,
        items
    };
    const fd = new FormData();
    fd.append('save_invoice', '1');
    if (FOR_ADMIN) fd.append('for_admin', FOR_ADMIN);
    fd.append('invoice_data', JSON.stringify(data));
    fetch('billing.php', {method: 'POST', body: fd}).then(r => r.json()).then(res => {
        if (res.status === 'success') {
            saved = true;
            toast(res.msg, 'ok');
            document.getElementById('inv_no').textContent = res.invoice_no;
            if (res.grand !== undefined) {
                document.getElementById('ui_grand').innerText = Number(res.grand).toFixed(2);
                document.getElementById('m_grand').innerText = '₹' + Number(res.grand).toLocaleString('en-IN');
            }
            document.querySelectorAll('#invoiceCanvas input').forEach(i => i.readOnly = true);
            document.querySelectorAll('.c-del,.add-row-btn').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.js-save').forEach(b => b.style.display = 'none');
            document.querySelectorAll('.js-print,.js-new').forEach(b => { b.style.display = 'inline-block'; b.disabled = false; });
            window.scrollTo({top: 0, behavior: 'smooth'});
        } else {
            toast(res.msg || 'Could not save', 'err');
            setSaving(false);
        }
    }).catch(() => { setSaving(false); toast('Network error. Please try again.', 'err'); });
}

function doPrint() { window.print(); }

document.addEventListener('mousedown', e => {
    if (!e.target.classList.contains('p-info') && !e.target.closest('#searchResults'))
        document.getElementById('searchResults').style.display = 'none';
});
window.addEventListener('beforeunload', e => {
    if (saved) return;
    const dirty = document.getElementById('cust_name').value.trim() || [...document.querySelectorAll('.p-info')].some(i => i.value.trim());
    if (dirty) { e.preventDefault(); e.returnValue = ''; }
});
window.onload = () => addNewRow(false);
</script>
</body>
</html>
