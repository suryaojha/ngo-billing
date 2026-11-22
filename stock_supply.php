<?php
session_start();
require 'config.php';
require 'ui.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin','admin'], true)) {
    header('Location: login.php'); exit;
}
$role = $_SESSION['role'];
$me = (int)$_SESSION['user_id'];
$isSuper = $role === 'superadmin';
$admins = $isSuper ? $pdo->query("SELECT id,full_name,company_name FROM users WHERE role='admin' AND IsActive=1 ORDER BY full_name")->fetchAll() : [];
function supply_redirect(): void { header('Location: stock_supply.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isSuper && isset($_POST['request_stock'])) {
        $name = trim((string)($_POST['product_name'] ?? ''));
        $batch = trim((string)($_POST['batch'] ?? ''));
        $qty = filter_var($_POST['requested_qty'] ?? null, FILTER_VALIDATE_INT);
        if ($name === '' || !$qty || $qty < 1) flash_set('error','Enter a product name and a valid quantity.');
        else {
            $st=$pdo->prepare("INSERT INTO stock_requests(admin_id,product_name,batch,requested_qty,note) VALUES(?,?,?,?,?)");
            $st->execute([$me,$name,$batch,$qty,trim((string)($_POST['note'] ?? ''))]);
            flash_set('success','Stock request sent to Super Admin.');
        }
        supply_redirect();
    }

    if ($isSuper && isset($_POST['add_main_stock'])) {
        $name=trim((string)($_POST['name'] ?? '')); $batch=trim((string)($_POST['batch'] ?? ''));
        $qty=filter_var($_POST['stock_qty'] ?? null,FILTER_VALIDATE_INT);
        $rate=$_POST['rate'] ?? '';
        if ($name==='' || $batch==='' || $qty===false || $qty<0 || !is_numeric($rate) || (float)$rate<0) flash_set('error','Enter valid product, batch, quantity and rate.');
        else {
            $q=$pdo->prepare("SELECT id FROM products WHERE admin_id IS NULL AND name=? AND batch=? LIMIT 1"); $q->execute([$name,$batch]);
            if($p=$q->fetch()) $pdo->prepare("UPDATE products SET stock_qty=stock_qty+?,rate=?,expiry=?,capacity=?,gst_percent=? WHERE id=?")->execute([$qty,$rate,$_POST['expiry'] ?? '',$_POST['capacity'] ?? '',$_POST['gst_percent'] ?? 5,$p['id']]);
            else $pdo->prepare("INSERT INTO products(name,batch,expiry,capacity,rate,stock_qty,gst_percent,admin_id) VALUES(?,?,?,?,?,?,?,NULL)")->execute([$name,$batch,$_POST['expiry'] ?? '',$_POST['capacity'] ?? '',$rate,$qty,$_POST['gst_percent'] ?? 5]);
            flash_set('success','Main stock saved.');
        }
        supply_redirect();
    }

    if ($isSuper && isset($_POST['create_supply_bill'])) {
        $adminId=(int)($_POST['admin_id'] ?? 0); $productId=(int)($_POST['product_id'] ?? 0);
        $qty=filter_var($_POST['qty'] ?? null,FILTER_VALIDATE_INT);
        $requestId=(int)($_POST['request_id'] ?? 0);
        try {
            if (!$adminId || !in_array($adminId,array_map('intval',array_column($admins,'id')),true) || !$productId || !$qty || $qty<1) throw new Exception('Choose an Admin, main-stock product and valid quantity.');
            $pdo->beginTransaction();
            if ($requestId) {
                $rq=$pdo->prepare("SELECT requested_qty FROM stock_requests WHERE id=? AND admin_id=? AND status='pending' FOR UPDATE");
                $rq->execute([$requestId,$adminId]); $requested=$rq->fetchColumn();
                if ($requested === false || (int)$requested !== $qty) throw new Exception('Request ID is not pending for this Admin, or the quantity does not match the request.');
            }
            $st=$pdo->prepare("SELECT * FROM products WHERE id=? AND admin_id IS NULL FOR UPDATE"); $st->execute([$productId]); $src=$st->fetch();
            if(!$src) throw new Exception('Main-stock product not found.');
            if((int)$src['stock_qty']<$qty) throw new Exception('Insufficient Super Admin stock. Available: '.(int)$src['stock_qty']);
            $rate=round((float)$src['rate'],2); $line=round($rate*$qty,2);
            $temporaryNo='TMP-'.bin2hex(random_bytes(8));
            $ins=$pdo->prepare("INSERT INTO supply_invoices(invoice_no,admin_id,created_by,stock_request_id,total_amount) VALUES(?,?,?,?,?)");
            $ins->execute([$temporaryNo,$adminId,$me,$requestId ?: null,$line]); $supplyId=(int)$pdo->lastInsertId();
            $invoiceNo='SUP-'.date('Y').'-'.str_pad((string)$supplyId,5,'0',STR_PAD_LEFT);
            $pdo->prepare("UPDATE supply_invoices SET invoice_no=? WHERE id=?")->execute([$invoiceNo,$supplyId]);
            $dest=$pdo->prepare("SELECT id FROM products WHERE admin_id=? AND name=? AND batch=? LIMIT 1"); $dest->execute([$adminId,$src['name'],$src['batch']]);
            $destId=(int)($dest->fetchColumn() ?: 0);
            if($destId) $pdo->prepare("UPDATE products SET stock_qty=stock_qty+?,expiry=?,capacity=? WHERE id=? AND admin_id=?")->execute([$qty,$src['expiry'],$src['capacity'],$destId,$adminId]);
            else { $pdo->prepare("INSERT INTO products(name,batch,expiry,capacity,rate,stock_qty,gst_percent,admin_id) VALUES(?,?,?,?,?,?,?,?)")->execute([$src['name'],$src['batch'],$src['expiry'],$src['capacity'],$rate,$qty,$src['gst_percent'],$adminId]); $destId=(int)$pdo->lastInsertId(); }
            $pdo->prepare("UPDATE products SET stock_qty=stock_qty-? WHERE id=? AND admin_id IS NULL AND stock_qty>=?")->execute([$qty,$productId,$qty]);
            if($pdo->query("SELECT ROW_COUNT()")->fetchColumn()!=1) throw new Exception('Main stock changed while saving. Please retry.');
            $pdo->prepare("INSERT INTO supply_invoice_items(supply_invoice_id,source_product_id,admin_product_id,product_name,batch,expiry,capacity,qty,rate,line_total) VALUES(?,?,?,?,?,?,?,?,?,?)")
                ->execute([$supplyId,$productId,$destId,$src['name'],$src['batch'],$src['expiry'],$src['capacity'],$qty,$rate,$line]);
            if($requestId) $pdo->prepare("UPDATE stock_requests SET status='fulfilled',fulfilled_at=NOW() WHERE id=? AND admin_id=? AND status='pending'")->execute([$requestId,$adminId]);
            $pdo->commit(); flash_set('success',"Supply bill $invoiceNo created. Stock transferred and Admin balance updated.");
        } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); flash_set('error',$e->getMessage()); }
        supply_redirect();
    }

    if ($isSuper && isset($_POST['record_payment'])) {
        $invoiceId=(int)($_POST['supply_invoice_id'] ?? 0); $amount=$_POST['amount'] ?? '';
        try {
            if($invoiceId<1 || !is_numeric($amount) || (float)$amount<=0) throw new Exception('Enter a valid payment amount.');
            $pdo->beginTransaction();
            $q=$pdo->prepare("SELECT si.*,COALESCE((SELECT SUM(sp.amount) FROM supply_payments sp WHERE sp.supply_invoice_id=si.id),0) AS received FROM supply_invoices si WHERE si.id=? FOR UPDATE");
            $q->execute([$invoiceId]); $inv=$q->fetch();
            if(!$inv) throw new Exception('Supply bill not found.');
            $pending=round((float)$inv['total_amount']-(float)$inv['received'],2);
            if((float)$amount>$pending+0.001) throw new Exception('Payment exceeds pending amount ₹'.number_format($pending,2));
            $pdo->prepare("INSERT INTO supply_payments(supply_invoice_id,admin_id,amount,payment_method,reference_note,received_by) VALUES(?,?,?,?,?,?)")
                ->execute([$invoiceId,$inv['admin_id'],round((float)$amount,2),trim((string)($_POST['payment_method'] ?? 'Cash')),trim((string)($_POST['reference_note'] ?? '')),$me]);
            $pdo->commit(); flash_set('success','Payment recorded.');
        } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); flash_set('error',$e->getMessage()); }
        supply_redirect();
    }

    if ($isSuper && isset($_POST['reject_request'])) {
        $rid=(int)($_POST['request_id'] ?? 0);
        $pdo->prepare("UPDATE stock_requests SET status='rejected' WHERE id=? AND status='pending'")->execute([$rid]);
        flash_set('success','Stock request rejected.'); supply_redirect();
    }
}

$mainStock=$isSuper ? $pdo->query("SELECT * FROM products WHERE admin_id IS NULL ORDER BY name,batch")->fetchAll() : [];
$requests=$isSuper
 ? $pdo->query("SELECT r.*,u.full_name AS admin_name FROM stock_requests r JOIN users u ON u.id=r.admin_id WHERE r.status='pending' ORDER BY r.created_at")->fetchAll()
 : $pdo->prepare("SELECT * FROM stock_requests WHERE admin_id=? ORDER BY created_at DESC LIMIT 100");
if(!$isSuper){$requests->execute([$me]);$requests=$requests->fetchAll();}
$invoices=$isSuper
 ? $pdo->query("SELECT si.*,u.full_name AS admin_name,COALESCE((SELECT SUM(sp.amount) FROM supply_payments sp WHERE sp.supply_invoice_id=si.id),0) AS received FROM supply_invoices si JOIN users u ON u.id=si.admin_id ORDER BY si.created_at DESC LIMIT 300")->fetchAll()
 : $pdo->prepare("SELECT si.*,COALESCE((SELECT SUM(sp.amount) FROM supply_payments sp WHERE sp.supply_invoice_id=si.id),0) AS received FROM supply_invoices si WHERE si.admin_id=? ORDER BY si.created_at DESC");
if(!$isSuper){$invoices->execute([$me]);$invoices=$invoices->fetchAll();}
$summary=$isSuper ? $pdo->query("SELECT u.id,u.full_name,COALESCE(SUM(si.total_amount),0) AS billed,COALESCE(SUM(COALESCE(pay.received,0)),0) AS received FROM users u LEFT JOIN supply_invoices si ON si.admin_id=u.id LEFT JOIN (SELECT supply_invoice_id,SUM(amount) AS received FROM supply_payments GROUP BY supply_invoice_id) pay ON pay.supply_invoice_id=si.id WHERE u.role='admin' GROUP BY u.id,u.full_name ORDER BY u.full_name")->fetchAll() : [];
$allBills=$isSuper ? $pdo->query("SELECT p.id,p.name,p.batch,p.stock_qty,p.rate FROM products p WHERE p.admin_id IS NULL AND p.stock_qty>0 ORDER BY p.name,p.batch")->fetchAll() : [];
$viewSupplyId=(int)($_GET['view_supply'] ?? 0); $viewSupply=null; $viewItems=[]; $viewPayments=[];
if ($viewSupplyId) {
    $sqlView="SELECT si.*,u.full_name AS admin_name,u.company_name FROM supply_invoices si JOIN users u ON u.id=si.admin_id WHERE si.id=?".($isSuper?"":" AND si.admin_id=?");
    $qv=$pdo->prepare($sqlView); $qv->execute($isSuper?[$viewSupplyId]:[$viewSupplyId,$me]); $viewSupply=$qv->fetch();
    if($viewSupply){
        $qi=$pdo->prepare("SELECT * FROM supply_invoice_items WHERE supply_invoice_id=? ORDER BY id"); $qi->execute([$viewSupplyId]); $viewItems=$qi->fetchAll();
        $qp=$pdo->prepare("SELECT amount,payment_method,reference_note,paid_at FROM supply_payments WHERE supply_invoice_id=? ORDER BY paid_at"); $qp->execute([$viewSupplyId]); $viewPayments=$qp->fetchAll();
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Stock Supply & Payments</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet"><style><?php echo ui_css(); ?>.wrap{max-width:1240px;margin:24px auto;padding:0 16px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}.muted{color:var(--muted);font-size:12px}.form-grid{grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}h1{font-size:24px;margin:0 0 6px}.table-wrap{overflow:auto}.section{margin-top:22px}@media(max-width:700px){.wrap{padding:0 10px}}</style></head><body>
<?php echo ui_nav('supply'); ?><main class="wrap"><?php echo flash_show(); ?>
<?php if($viewSupply): ?>
<section class="card"><div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap"><div><h1>Supply Bill <?php echo e($viewSupply['invoice_no']); ?></h1><p class="muted">Admin: <?php echo e($viewSupply['admin_name']); ?> · <?php echo date('d-M-Y',strtotime($viewSupply['created_at'])); ?></p></div><div class="no-print"><a class="btn btn-light" href="stock_supply.php">← Back</a> <button class="btn btn-primary" onclick="window.print()">🖨 Print Bill</button></div></div>
<div class="table-wrap"><table class="data"><thead><tr><th>Product</th><th>Batch</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody><?php foreach($viewItems as $it): ?><tr><td><?php echo e($it['product_name']); ?></td><td><?php echo e($it['batch']); ?></td><td><?php echo (int)$it['qty']; ?></td><td>₹<?php echo number_format($it['rate'],2); ?></td><td>₹<?php echo number_format($it['line_total'],2); ?></td></tr><?php endforeach; ?></tbody></table></div>
<p style="text-align:right;font-size:18px;font-weight:800;margin-top:16px">Bill Total: ₹<?php echo number_format($viewSupply['total_amount'],2); ?></p>
<h3 style="margin:20px 0 8px">Payment history</h3><div class="table-wrap"><table class="data"><thead><tr><th>Date</th><th>Method</th><th>Reference</th><th>Amount</th></tr></thead><tbody><?php foreach($viewPayments as $p): ?><tr><td><?php echo date('d-M-Y H:i',strtotime($p['paid_at'])); ?></td><td><?php echo e($p['payment_method']); ?></td><td><?php echo e($p['reference_note']); ?></td><td>₹<?php echo number_format($p['amount'],2); ?></td></tr><?php endforeach; ?><?php if(!$viewPayments): ?><tr><td colspan="4">No payments recorded yet.</td></tr><?php endif; ?></tbody></table></div></section>
<?php else: ?>
<h1><?php echo $isSuper?'Admin Supply Bills & Collections':'Request Stock from Super Admin'; ?></h1>
<p class="muted"><?php echo $isSuper?'Create supply bills, transfer main stock to Admins and record payments against each bill.':'Request products from Super Admin and track your requests and payable supply bills.'; ?></p>
<?php if($isSuper): ?>
<div class="grid section">
 <section class="card"><h2 class="card-title">Add to Super Admin Main Stock</h2><form method="post" class="form-grid">
 <div><label>Product</label><input name="name" required></div><div><label>Batch</label><input name="batch" required></div><div><label>Quantity</label><input type="number" min="0" step="1" name="stock_qty" required></div><div><label>Supply rate / unit (₹)</label><input type="number" min="0" step=".01" name="rate" required></div><div><label>Expiry</label><input name="expiry"></div><div><label>Capacity</label><input name="capacity"></div><div><label>GST %</label><input type="number" min="0" max="100" step=".01" name="gst_percent" value="5"></div><div style="align-self:end"><button class="btn btn-primary" name="add_main_stock">Save Main Stock</button></div></form></section>
 <section class="card"><h2 class="card-title">Create Supply Bill for Admin</h2><form method="post" class="form-grid">
 <div><label>Admin</label><select name="admin_id" required><option value="">Choose Admin</option><?php foreach($admins as $a):?><option value="<?php echo (int)$a['id'];?>"><?php echo e($a['full_name']);?></option><?php endforeach;?></select></div>
 <div><label>Main stock product</label><select name="product_id" required><option value="">Choose product</option><?php foreach($allBills as $p):?><option value="<?php echo (int)$p['id'];?>"><?php echo e($p['name'].' / '.$p['batch'].' · '.$p['stock_qty'].' available · ₹'.$p['rate']);?></option><?php endforeach;?></select></div>
 <div><label>Quantity</label><input type="number" name="qty" min="1" step="1" required></div><div><label>Request ID (optional)</label><input type="number" name="request_id" min="1" placeholder="Link to request"></div>
 <div style="align-self:end"><button class="btn btn-success" name="create_supply_bill">Create Bill & Transfer Stock</button></div></form><p class="muted">The bill total uses the main-stock product's supply rate. Stock transfer and bill creation happen in one database transaction.</p></section>
</div>
<section class="card section"><h2 class="card-title">Admin balances</h2><div class="table-wrap"><table class="data rt"><thead><tr><th>Admin</th><th>Total supply bills</th><th>Received</th><th>Pending</th></tr></thead><tbody><?php foreach($summary as $a):$pending=(float)$a['billed']-(float)$a['received'];?><tr><td data-label="Admin"><?php echo e($a['full_name']);?></td><td data-label="Billed">₹<?php echo number_format($a['billed'],2);?></td><td data-label="Received">₹<?php echo number_format($a['received'],2);?></td><td data-label="Pending"><strong>₹<?php echo number_format($pending,2);?></strong></td></tr><?php endforeach;?></tbody></table></div></section>
<section class="card section"><h2 class="card-title">Pending stock requests</h2><div class="table-wrap"><table class="data rt"><thead><tr><th>Request ID</th><th>Admin</th><th>Product</th><th>Batch</th><th>Qty</th><th>Note</th><th>Action</th></tr></thead><tbody><?php foreach($requests as $r):?><tr><td data-label="Request ID">#<?php echo (int)$r['id'];?></td><td data-label="Admin"><?php echo e($r['admin_name']);?></td><td data-label="Product"><?php echo e($r['product_name']);?></td><td data-label="Batch"><?php echo e($r['batch']);?></td><td data-label="Qty"><?php echo (int)$r['requested_qty'];?></td><td data-label="Note"><?php echo e($r['note']);?></td><td data-label="Action"><form method="post" style="display:inline"><input type="hidden" name="request_id" value="<?php echo (int)$r['id'];?>"><button class="btn btn-light btn-sm" name="reject_request">Reject</button></form><small class="muted">To fulfil: use Create Supply Bill and enter this Request ID.</small></td></tr><?php endforeach;?><?php if(!$requests):?><tr><td colspan="7">No pending requests.</td></tr><?php endif;?></tbody></table></div></section>
<?php else: ?>
<section class="card section"><h2 class="card-title">Request stock</h2><form method="post" class="form-grid"><div><label>Product name</label><input name="product_name" required></div><div><label>Batch (if known)</label><input name="batch"></div><div><label>Quantity</label><input type="number" name="requested_qty" min="1" step="1" required></div><div><label>Note</label><input name="note"></div><div style="align-self:end"><button class="btn btn-primary" name="request_stock">Send Request</button></div></form></section>
<section class="card section"><h2 class="card-title">My requests</h2><div class="table-wrap"><table class="data rt"><thead><tr><th>Product</th><th>Batch</th><th>Qty</th><th>Status</th><th>Date</th></tr></thead><tbody><?php foreach($requests as $r):?><tr><td data-label="Product"><?php echo e($r['product_name']);?></td><td data-label="Batch"><?php echo e($r['batch']);?></td><td data-label="Qty"><?php echo (int)$r['requested_qty'];?></td><td data-label="Status"><?php echo e(ucfirst($r['status']));?></td><td data-label="Date"><?php echo date('d-M-Y',strtotime($r['created_at']));?></td></tr><?php endforeach;?></tbody></table></div></section>
<?php endif;?>
<section class="card section"><h2 class="card-title"><?php echo $isSuper?'Supply bills & payment collection':'Supply bills / amount payable';?></h2><div class="table-wrap"><table class="data rt"><thead><tr><th>Bill</th><?php if($isSuper):?><th>Admin</th><?php endif;?><th>Total</th><th>Received</th><th>Pending</th><th>Date</th><?php if($isSuper):?><th>Record payment</th><?php endif;?></tr></thead><tbody><?php foreach($invoices as $v):$pending=max(0,(float)$v['total_amount']-(float)$v['received']);?><tr><td data-label="Bill"><a href="stock_supply.php?view_supply=<?php echo (int)$v['id'];?>"><?php echo e($v['invoice_no']);?></a></td><?php if($isSuper):?><td data-label="Admin"><?php echo e($v['admin_name']);?></td><?php endif;?><td data-label="Total">₹<?php echo number_format($v['total_amount'],2);?></td><td data-label="Received">₹<?php echo number_format($v['received'],2);?></td><td data-label="Pending">₹<?php echo number_format($pending,2);?></td><td data-label="Date"><?php echo date('d-M-Y',strtotime($v['created_at']));?></td><?php if($isSuper):?><td data-label="Record payment"><?php if($pending>0):?><form method="post" class="form-grid"><input type="hidden" name="supply_invoice_id" value="<?php echo (int)$v['id'];?>"><input type="number" name="amount" min=".01" max="<?php echo e(number_format($pending,2,'.',''));?>" step=".01" placeholder="Amount" required><select name="payment_method"><option>Cash</option><option>UPI</option><option>Bank Transfer</option><option>Cheque</option></select><input name="reference_note" placeholder="Reference / note"><button class="btn btn-success btn-sm" name="record_payment">Save payment</button></form><?php else:?><span class="badge b-green">Paid</span><?php endif;?></td><?php endif;?></tr><?php endforeach;?></tbody></table></div></section>
<?php endif; ?></main></body></html>