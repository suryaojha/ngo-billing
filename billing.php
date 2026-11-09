<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];

// Determine which admin's stock to show
// staff -> their admin_id; admin -> their own id; superadmin -> all
if ($role === 'staff') {
    $stock_admin_id = $_SESSION['admin_id'];
    if (!$stock_admin_id) {
        die('Your account is not linked to a business yet. Please ask your admin/super admin to fix your account before billing.');
    }
} elseif ($role === 'admin') {
    $stock_admin_id = $user_id;
} else {
    $stock_admin_id = null; // superadmin sees all
}

// Company letterhead — per admin (tenant), not a single hardcoded company.
// Superadmin has no business of their own, so falls back to generic values.
$company = [
    'name' => 'RUSHIKESH E. H. PHARMA', 'address' => '', 'gstin' => '', 'phone' => '',
    'bank_name' => '', 'bank_account' => '', 'bank_ifsc' => '', 'bank_branch' => '',
];
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
$logo_path    = 'uploads/bill.png';
$company_name = $company['name'];

// Auto invoice number — scoped to this admin's own sequence
$stmt_inv = $pdo->prepare("SELECT MAX(CAST(invoice_no AS UNSIGNED)) as max_inv FROM invoices WHERE status=1 AND admin_id <=> ?");
$stmt_inv->execute([$stock_admin_id]);
$row_inv  = $stmt_inv->fetch();
$next_inv_no = str_pad(($row_inv['max_inv'] ? $row_inv['max_inv'] + 1 : 1), 3, '0', STR_PAD_LEFT);

// AJAX: product search — filtered by admin's stock
if (isset($_GET['action']) && $_GET['action'] == 'search') {
    $term = $_GET['term'] . '%';
    if ($stock_admin_id) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE (name LIKE ? OR batch LIKE ?) AND admin_id=? LIMIT 20");
        $stmt->execute([$term, $term, $stock_admin_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR batch LIKE ? LIMIT 20");
        $stmt->execute([$term, $term]);
    }
    echo json_encode($stmt->fetchAll());
    exit;
}

// AJAX: save invoice
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_invoice'])) {
    $data = json_decode($_POST['invoice_data'], true);

    if (empty($data['items'])) {
        echo json_encode(['status'=>'error','msg'=>'No items in invoice.']);
        exit;
    }

    // Save with a few retries: if the auto-picked invoice number collides
    // with one another tab/user just saved a split-second ago, the unique
    // (admin_id, invoice_no) constraint rejects it and we simply pick the
    // next number and try again — this is what actually prevents duplicate
    // invoice numbers, not just the on-screen suggestion.
    $maxAttempts = 5;
    $lastError = null;
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        try {
            $pdo->beginTransaction();

            $stmt_inv2 = $pdo->prepare("SELECT MAX(CAST(invoice_no AS UNSIGNED)) as max_inv FROM invoices WHERE status=1 AND admin_id <=> ? FOR UPDATE");
            $stmt_inv2->execute([$stock_admin_id]);
            $row_inv2 = $stmt_inv2->fetch();
            $invoice_no = str_pad(($row_inv2['max_inv'] ? $row_inv2['max_inv'] + 1 : 1), 3, '0', STR_PAD_LEFT);

            $gstPercent = isset($data['gst_percent']) && is_numeric($data['gst_percent']) ? (float)$data['gst_percent'] : 5.00;
            $stmt = $pdo->prepare("INSERT INTO invoices (invoice_no,customer_name,customer_address,customer_phone,total_amount,gst_percent,admin_id,created_by) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$invoice_no,$data['customer_name'],$data['customer_address'],$data['customer_phone'],$data['grand_total'],$gstPercent,$stock_admin_id,$user_id]);
            $invoice_db_id = $pdo->lastInsertId();

            $itemStmt    = $pdo->prepare("INSERT INTO invoice_items (invoice_id,product_id,product_name,batch,expiry,capacity,qty,rate,discount,gross_amount) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $updateStock = $pdo->prepare("UPDATE products SET stock_qty=stock_qty-? WHERE id=? AND admin_id <=> ? AND stock_qty>=?");

            foreach ($data['items'] as $item) {
                $productId = isset($item['id']) && $item['id'] !== '' ? (int)$item['id'] : null;
                $itemStmt->execute([$invoice_db_id,$productId,$item['name'],$item['batch'],$item['expiry']??'',$item['capacity']??'',$item['qty'],$item['rate'],$item['dis'],$item['gross']]);

                if ($productId) {
                    $updateStock->execute([$item['qty'],$productId,$stock_admin_id,$item['qty']]);
                    if ($updateStock->rowCount() == 0) throw new Exception("Insufficient stock for {$item['name']} (Batch: {$item['batch']})");
                } else {
                    throw new Exception("Please pick \"{$item['name']}\" from the search list so stock can be tracked correctly.");
                }
            }
            $pdo->commit();
            echo json_encode(['status'=>'success','msg'=>"Invoice #$invoice_no Saved & Stock Updated!",'invoice_no'=>$invoice_no]);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            // 23000 = integrity constraint violation (our unique invoice_no per admin) -> retry
            if ($e->getCode() == 23000 && $attempt < $maxAttempts - 1) {
                $lastError = $e;
                continue;
            }
            error_log("Invoice save error: " . $e->getMessage());
            echo json_encode(['status'=>'error','msg'=>'Could not save invoice. Please try again.']);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['status'=>'error','msg'=>'Could not save invoice after several attempts. Please try again.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Console - <?php echo $company_name; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#2563eb; --border-thick:2px solid #000; --border-thin:1px solid #000; }
        body { font-family:'Inter',sans-serif; background:#f1f5f9; margin:0; padding:20px; color:#1e293b; }
        .no-print-toolbar { max-width:950px; margin:0 auto 20px; background:#fff; padding:15px 25px; border-radius:12px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 4px 12px rgba(0,0,0,0.1); flex-wrap:wrap; gap:15px; }
        .invoice-canvas { width:210mm; min-height:297mm; margin:0 auto; background:#fff; padding:12mm; box-sizing:border-box; box-shadow:0 10px 40px rgba(0,0,0,0.1); position:relative; }
        input.edit-field { border:1px solid transparent; background:transparent; font-family:inherit; font-size:inherit; color:inherit; width:100%; outline:none; transition:0.2s; }
        input.edit-field:hover { background:#f8fafc; border-bottom:1px dashed #cbd5e1; }
        input.edit-field:focus { background:#fff; border:1px solid var(--primary); border-radius:4px; }
        .header-section { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px; }
        .logo-box img { max-width:150px; max-height:80px; object-fit:contain; }
        .invoice-frame { border:var(--border-thick); }
        .row-split { display:grid; grid-template-columns:1.2fr 0.8fr; border-bottom:var(--border-thick); }
        .col-box { padding:12px; border-right:var(--border-thick); }
        .col-box:last-child { border-right:none; }
        table { width:100%; border-collapse:collapse; }
        th { border-bottom:var(--border-thick); border-right:var(--border-thin); padding:10px 5px; font-size:11px; background:#f8fafc; }
        td { border-bottom:var(--border-thin); border-right:var(--border-thin); padding:8px 5px; font-size:12px; text-align:center; }
        .footer-summary { display:grid; grid-template-columns:1.3fr 0.7fr; }
        .summary-left { padding:15px; border-right:var(--border-thick); }
        .grand-total-line { border-top:var(--border-thick); padding:12px 15px; font-size:18px; background:#fdfdfd; display:flex; justify-content:space-between; font-weight:800; }
        .btn { padding:12px 25px; border:none; border-radius:8px; font-weight:700; cursor:pointer; white-space:nowrap; }
        .add-row-btn { padding:10px; text-align:center; background:#f0fdf4; color:#16a34a; font-weight:bold; cursor:pointer; border-bottom:var(--border-thick); }
        .search-container { position:relative; }
        .search-results { position:absolute; background:white; border:1px solid #ddd; width:100%; z-index:999; box-shadow:0 10px 20px rgba(0,0,0,0.1); border-radius:8px; display:none; text-align:left; }
        .search-item { padding:10px; cursor:pointer; border-bottom:1px solid #f1f5f9; font-size:13px; }
        .search-item:hover { background:#eff6ff; color:var(--primary); }
        @media screen and (max-width:768px) {
            body{padding:10px;}
            .no-print-toolbar{flex-direction:column;text-align:center;padding:15px;}
            .invoice-canvas{width:100%;min-height:auto;padding:10px;box-shadow:none;}
            .row-split{grid-template-columns:1fr;border-bottom:none;}
            .col-box{border-right:none;border-bottom:var(--border-thick);}
            .table-responsive{width:100%;overflow-x:auto;}
            table{min-width:800px;}
            .footer-summary{grid-template-columns:1fr;}
            .summary-left{border-right:none;border-bottom:var(--border-thick);}
        }
        @media print {
            .no-print-toolbar,.add-row-btn,.del-btn,.search-results{display:none!important;}
            body{background:white;padding:0;}
            .invoice-canvas{box-shadow:none;border:none;margin:0;width:100%;padding:5mm;}
            .table-responsive{overflow:visible;}
            table{min-width:100%;}
            th:nth-child(5),td:nth-child(5){display:none;}
        }
    </style>
</head>
<body>
<div class="no-print-toolbar">
    <div>
        <h2 style="margin:0;font-size:18px;color:var(--primary);">Billing Console</h2>
        <span style="font-size:11px;color:#64748b;">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
        <?php if($role === 'staff'): ?>
            <span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:6px;font-size:10px;margin-left:6px;">Staff</span>
        <?php endif; ?>
        </span>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <?php if($role === 'admin'): ?>
            <button class="btn" style="background:#f1f5f9;" onclick="location.href='inventory.php'">📦 Inventory</button>
            <button class="btn" style="background:#f1f5f9;" onclick="location.href='reports.php'">📊 Reports</button>
        <?php endif; ?>
        <button class="btn" style="background:#16a34a;color:white;" id="saveBtn" onclick="saveInvoiceToDB()">Save & Sync Stock</button>
        <button class="btn" style="background:var(--primary);color:white;display:none;" id="printBtn" onclick="window.print()">Print</button>
        <button class="btn" style="background:#ef4444;color:white;" onclick="location.href='logout.php'">Logout</button>
    </div>
</div>

<div class="invoice-canvas">
    <div style="text-align:center;font-weight:800;font-size:14px;margin-bottom:10px;text-decoration:underline;">GST INVOICE</div>
    <div class="header-section">
        <div class="company-header">
            <h1 style="margin:0;font-size:32px;font-weight:800;"><?php echo htmlspecialchars($company['name']); ?></h1>
            <div style="font-size:12px;line-height:1.4;margin-top:5px;">
                <?php echo nl2br(htmlspecialchars($company['address'])); ?><?php if($company['address']): ?><br><?php endif; ?>
                <?php if($company['gstin']): ?><strong>GSTIN: <?php echo htmlspecialchars($company['gstin']); ?></strong><?php endif; ?>
                <?php if($company['phone']): ?> | <strong>Phone:</strong> <?php echo htmlspecialchars($company['phone']); ?><?php endif; ?>
            </div>
        </div>
        <div class="logo-box">
            <?php if(file_exists($logo_path)): ?>
                <img src="<?php echo $logo_path; ?>?v=<?php echo time(); ?>">
            <?php else: ?>
                <div style="border:1px dashed #ccc;font-size:10px;padding:10px;">LOGO</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="invoice-frame">
        <div class="row-split">
            <div class="col-box">
                <h3>Billing Details (Bill To)</h3>
                <div style="display:flex;margin-bottom:2px;"><span style="width:70px;font-weight:bold;">Name:</span><input type="text" id="cust_name" class="edit-field" style="font-weight:800;" value=""></div>
                <div style="display:flex;margin-bottom:2px;"><span style="width:70px;font-weight:bold;">Address:</span><input type="text" id="cust_addr" class="edit-field" value=""></div>
                <div style="display:flex;margin-bottom:2px;"><span style="width:70px;font-weight:bold;">Phone:</span><input type="text" id="cust_phone" class="edit-field" value=""></div>
            </div>
            <div class="col-box">
                <div style="margin-bottom:8px;"><strong>Invoice No:</strong> <input type="text" id="inv_no" class="edit-field" style="width:100px;font-weight:bold;" value="<?php echo $next_inv_no; ?> (auto)" readonly title="Assigned automatically when you save, to prevent duplicate numbers"></div>
                <div style="margin-bottom:8px;"><strong>Date:</strong> <input type="text" class="edit-field" style="width:100px;" value="<?php echo date('d-m-Y'); ?>"></div>
                <div><strong>Payment:</strong> <input type="text" class="edit-field" style="width:100px;" value="Cash"></div>
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
                        <th class="del-btn"></th>
                    </tr>
                </thead>
                <tbody id="invoice_body"></tbody>
            </table>
        </div>

        <div class="add-row-btn" onclick="addNewRow()">+ CLICK HERE TO ADD NEW PRODUCT</div>

        <div class="footer-summary">
            <div class="summary-left">
                <div style="display:flex;justify-content:space-between;font-weight:bold;font-size:11px;"><span>E & O.E</span><span>Total Items: <span id="ui_item_count">0</span></span></div>
                <div style="margin-top:15px;">
                    <span style="font-size:10px;font-weight:bold;color:#64748b;">AMOUNT IN WORDS:</span>
                    <div id="ui_amount_words" style="font-weight:800;text-transform:uppercase;font-size:13px;">Zero Rupees Only</div>
                </div>
                <?php if($company['bank_name'] || $company['bank_account']): ?>
                <div style="margin-top:30px;font-size:10px;border-top:1px solid #eee;padding-top:8px;">
                    <strong>BANK:</strong> <?php echo htmlspecialchars($company['bank_name']); ?> | <strong>A/C:</strong> <?php echo htmlspecialchars($company['bank_account']); ?><br>
                    <strong>IFSC:</strong> <?php echo htmlspecialchars($company['bank_ifsc']); ?> | <strong>BRANCH:</strong> <?php echo htmlspecialchars($company['bank_branch']); ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="summary-right">
                <div style="padding:10px 15px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px;"><span>Total Gross:</span><span id="ui_total_gross">0.00</span></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px;"><span>Total Discount:</span><span id="ui_total_discount">0.00</span></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px;"><span>Taxable Value:</span><span id="ui_taxable">0.00</span></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                        <span>GST (<input type="number" id="ui_gst_per" value="5" oninput="calc()" style="width:30px;border:none;background:transparent;font-weight:bold;">%)</span>
                        <span id="ui_gst_amt">0.00</span>
                    </div>
                </div>
                <div class="grand-total-line"><span>GRAND TOTAL</span><span id="ui_grand">0.00</span></div>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;margin-top:80px;">
        <div style="text-align:center;border-top:1px solid #000;width:180px;font-size:12px;font-weight:bold;padding-top:5px;">RECEIVER'S SIGNATURE</div>
        <div style="text-align:center;border-top:1px solid #000;width:220px;font-size:12px;font-weight:bold;padding-top:5px;">FOR <?php echo $company_name; ?></div>
    </div>
</div>

<div id="searchResults" class="search-results"></div>

<script>
function numToWords(n) {
    const a=['','One ','Two ','Three ','Four ','Five ','Six ','Seven ','Eight ','Nine ','Ten ','Eleven ','Twelve ','Thirteen ','Fourteen ','Fifteen ','Sixteen ','Seventeen ','Eighteen ','Nineteen '];
    const b=['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    let num=Math.floor(n).toString(),n_length=num.length;
    if(n_length>9)return'Too Large';
    let n_array=new Array(0,0,0,0,0,0,0,0,0),received_n_array=[];
    for(let i=0;i<n_length;i++)received_n_array[i]=num.substr(i,1);
    for(let i=9-n_length,j=0;i<9;i++,j++)n_array[i]=received_n_array[j];
    for(let i=0,j=1;i<9;i++,j++){if(i==0||i==2||i==4||i==7){if(n_array[i]==1){n_array[j]=10+parseInt(n_array[j]);n_array[i]=0;}}}
    let str="";
    for(let i=0;i<9;i++){let v=(i==0||i==2||i==4||i==7)?n_array[i]*10:n_array[i];if(v!=0)str+=a[v]||(b[n_array[i]]+' '+a[n_array[i+1]]);if((i==1&&v!=0)||(i==0&&v!=0&&n_array[i+1]==0))str+="Crore ";if((i==3&&v!=0)||(i==2&&v!=0&&n_array[i+1]==0))str+="Lakh ";if((i==5&&v!=0)||(i==4&&v!=0&&n_array[i+1]==0))str+="Thousand ";if(i==6&&v!=0)str+="Hundred ";}
    return(str||"Zero")+" Rupees Only";
}

let activeRow=null;

function addNewRow(data={}) {
    const tbody=document.getElementById('invoice_body');
    const row=document.createElement('tr');
    row.innerHTML=`
        <td class="sr-cell"></td>
        <td class="search-container"><input type="text" class="edit-field p-info" placeholder="Search product..." data-pid="${data.pid||''}" value="${data.info||''}" onfocus="searchProduct(this)" onkeyup="this.dataset.pid='';searchProduct(this)"></td>
        <td><input type="text" class="edit-field p-batch" value="${data.batch||''}" readonly></td>
        <td><input type="text" class="edit-field p-exp" value="${data.exp||''}" readonly></td>
        <td><input type="text" class="edit-field p-cap" value="${data.cap||''}" readonly></td>
        <td><input type="number" class="edit-field p-rate" oninput="calc()" value="${data.rate||0}" readonly></td>
        <td><input type="number" class="edit-field p-qty" oninput="checkStock(this);calc()" value="${data.qty||0}" data-stock="${data.stock||0}"></td>
        <td><input type="number" class="edit-field p-dis" oninput="calc()" value="${data.dis||0}"></td>
        <td class="p-gross" style="font-weight:bold;">0.00</td>
        <td class="del-btn" onclick="this.parentElement.remove();calc();">✕</td>
    `;
    tbody.appendChild(row);
    calc();
}

function searchProduct(input) {
    activeRow=input.parentElement.parentElement;
    fetch(`billing.php?action=search&term=${input.value||''}`)
        .then(res=>res.json())
        .then(data=>{
            let container=document.getElementById('searchResults');
            container.innerHTML='';
            if(data.length===0){container.innerHTML='<div class="search-item" style="color:#94a3b8;">No products found</div>';}
            data.forEach(p=>{
                const stock=Math.max(0,parseInt(p.stock_qty)||0);
                if(stock<=0)return;
                let div=document.createElement('div');
                div.className='search-item';
                div.innerHTML=`<strong>${p.name}</strong> - Batch: ${p.batch} | Stock: ${stock}`;
                div.onclick=()=>{
                    activeRow.querySelector('.p-info').value=p.name;
                    activeRow.querySelector('.p-info').dataset.pid=p.id;
                    activeRow.querySelector('.p-batch').value=p.batch;
                    activeRow.querySelector('.p-exp').value=p.expiry;
                    activeRow.querySelector('.p-cap').value=p.capacity;
                    activeRow.querySelector('.p-rate').value=p.rate;
                    activeRow.querySelector('.p-qty').value=0;
                    activeRow.querySelector('.p-qty').dataset.stock=stock;
                    activeRow.querySelector('.p-qty').focus();
                    container.style.display='none';
                    calc();
                };
                container.appendChild(div);
            });
            let rect=input.getBoundingClientRect();
            container.style.left=rect.left+'px';
            container.style.top=(rect.bottom+window.scrollY)+'px';
            container.style.width=rect.width+'px';
            container.style.display='block';
        });
}

function calc() {
    let totalGross=0,totalDiscount=0;
    const rows=document.querySelectorAll('#invoice_body tr');
    const gstP=parseFloat(document.getElementById('ui_gst_per').value)||0;
    rows.forEach((r,idx)=>{
        r.querySelector('.sr-cell').innerText=idx+1;
        const rate=parseFloat(r.querySelector('.p-rate').value)||0;
        const qty=parseFloat(r.querySelector('.p-qty').value)||0;
        const dis=parseFloat(r.querySelector('.p-dis').value)||0;
        const gross=rate*qty;
        r.querySelector('.p-gross').innerText=gross.toFixed(2);
        totalGross+=gross;
        totalDiscount+=gross*(dis/100);
    });
    const taxable=totalGross-totalDiscount;
    const gstAmt=taxable*(gstP/100);
    const grand=Math.round(taxable+gstAmt);
    document.getElementById('ui_item_count').innerText=rows.length;
    document.getElementById('ui_total_gross').innerText=totalGross.toFixed(2);
    document.getElementById('ui_total_discount').innerText=totalDiscount.toFixed(2);
    document.getElementById('ui_taxable').innerText=taxable.toFixed(2);
    document.getElementById('ui_gst_amt').innerText=gstAmt.toFixed(2);
    document.getElementById('ui_grand').innerText=grand.toFixed(2);
    document.getElementById('ui_amount_words').innerText=numToWords(grand);
}

function checkStock(input) {
    const stock=parseInt(input.dataset.stock)||0;
    const qty=parseInt(input.value)||0;
    if(qty>stock){alert(`⚠️ Insufficient Stock! Available: ${stock}`);input.value=stock;calc();}
}

function saveInvoiceToDB() {
    let items=[],missingPid=false;
    document.querySelectorAll('#invoice_body tr').forEach(r=>{
        const infoInput=r.querySelector('.p-info');
        const name=infoInput.value.trim();
        if(!name)return;
        if(!infoInput.dataset.pid)missingPid=true;
        items.push({id:infoInput.dataset.pid||null,name,batch:r.querySelector('.p-batch').value,expiry:r.querySelector('.p-exp').value,capacity:r.querySelector('.p-cap').value,qty:r.querySelector('.p-qty').value,rate:r.querySelector('.p-rate').value,dis:r.querySelector('.p-dis').value,gross:r.querySelector('.p-gross').innerText});
    });
    if(items.length===0){alert('⚠️ Please add at least one product!');return;}
    if(missingPid){alert('⚠️ Please pick every product from the search suggestions (so stock is tracked correctly) before saving.');return;}
    const saveBtn=document.getElementById('saveBtn');
    saveBtn.disabled=true;saveBtn.textContent='Saving...';
    let data={customer_name:document.getElementById('cust_name').value,customer_address:document.getElementById('cust_addr').value,customer_phone:document.getElementById('cust_phone').value,grand_total:document.getElementById('ui_grand').innerText,gst_percent:document.getElementById('ui_gst_per').value,items};
    let fd=new FormData();
    fd.append('save_invoice','1');
    fd.append('invoice_data',JSON.stringify(data));
    fetch('billing.php',{method:'POST',body:fd}).then(res=>res.json()).then(res=>{
        alert(res.msg);
        if(res.status==='success'){
            if(res.invoice_no)document.getElementById('inv_no').value=res.invoice_no;
            saveBtn.style.display='none';
            document.getElementById('printBtn').style.display='inline-block';
        } else {
            saveBtn.disabled=false;saveBtn.textContent='Save & Sync Stock';
        }
    }).catch(()=>{saveBtn.disabled=false;saveBtn.textContent='Save & Sync Stock';alert('Network error. Please try again.');});
}

document.addEventListener('click',e=>{if(!e.target.classList.contains('p-info'))document.getElementById('searchResults').style.display='none';});
window.onload=()=>addNewRow();
</script>
</body>
</html>
