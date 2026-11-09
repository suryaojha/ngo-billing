<?php
/**
 * ui.php — shared look, navigation and helpers for the billing system pages
 * (billing.php, inventory.php, reports.php). Not used by the NGO website.
 */

const LOW_STOCK_LIMIT = 10;

function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

/* ---------- flash messages (survive one redirect) ---------- */
function flash_set(string $type, string $text): void { $_SESSION['flash'] = ['type' => $type, 'text' => $text]; }
function flash_show(): string {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash']; unset($_SESSION['flash']);
    return '<div class="alert alert-' . e($f['type']) . '">' . e($f['text']) . '</div>';
}

/* ---------- expiry helper: products store expiry as text, usually YYYY-MM ---------- */
function expiry_state($exp): string {
    $exp = trim((string)$exp);
    if ($exp === '') return '';
    if (preg_match('/^(\d{4})-(\d{1,2})/', $exp, $m)) { $y = (int)$m[1]; $mo = (int)$m[2]; }
    elseif (preg_match('/^(\d{1,2})[\/-](\d{4})$/', $exp, $m)) { $mo = (int)$m[1]; $y = (int)$m[2]; }
    else return '';
    if ($mo < 1 || $mo > 12) return '';
    $end = strtotime(sprintf('%04d-%02d-01 +1 month -1 day', $y, $mo));
    if ($end === false) return '';
    $today = strtotime('today');
    if ($end < $today) return 'expired';
    if ($end <= strtotime('+90 days', $today)) return 'soon';
    return 'ok';
}

/* ---------- stock list filter (shared by inventory.php and export.php) ---------- */
function filter_products(array $products, string $q, string $status): array {
    $q = strtolower(trim($q));
    return array_values(array_filter($products, function ($p) use ($q, $status) {
        if ($q !== '' && strpos(strtolower(($p['admin_name'] ?? '') . ' ' . $p['name'] . ' ' . $p['batch'] . ' ' . ($p['capacity'] ?? '')), $q) === false) return false;
        $qty = (int)$p['stock_qty'];
        if ($status === 'out' && $qty > 0) return false;
        if ($status === 'low' && !($qty > 0 && $qty <= LOW_STOCK_LIMIT)) return false;
        if ($status === 'exp' && !in_array(expiry_state($p['expiry']), ['expired', 'soon'], true)) return false;
        return true;
    }));
}

/* ---------- pagination ---------- */
function page_params(int $defPer = 25): array {
    $per = (int)($_GET['per'] ?? $defPer);
    if (!in_array($per, [10, 25, 50, 100], true)) $per = $defPer;
    $page = max(1, (int)($_GET['page'] ?? 1));
    return [$page, $per];
}

// Page links that keep every other filter in the URL.
function pager(int $total, int $page, int $per): string {
    $pages = max(1, (int)ceil($total / $per));
    $page  = min($page, $pages);
    $url   = function (array $over) {
        $q = array_merge($_GET, $over);
        foreach ($q as $k => $v) if ($v === '' || $v === null) unset($q[$k]);
        return '?' . http_build_query($q);
    };
    $from = $total ? ($page - 1) * $per + 1 : 0;
    $to   = min($total, $page * $per);

    $h  = '<div class="pager no-print"><div class="pager-info">Showing <b>' . $from . '–' . $to . '</b> of <b>' . number_format($total) . '</b></div>';
    if ($pages > 1) {
        $h .= '<div class="pager-links">';
        $h .= $page > 1 ? '<a href="' . e($url(['page' => $page - 1])) . '">‹ Prev</a>' : '<span class="off">‹ Prev</span>';
        $shown = [];
        for ($p = 1; $p <= $pages; $p++) if ($p === 1 || $p === $pages || abs($p - $page) <= 2) $shown[] = $p;
        $prev = 0;
        foreach ($shown as $p) {
            if ($p - $prev > 1) $h .= '<span class="dots">…</span>';
            $h .= $p === $page ? '<span class="cur">' . $p . '</span>' : '<a href="' . e($url(['page' => $p])) . '">' . $p . '</a>';
            $prev = $p;
        }
        $h .= $page < $pages ? '<a href="' . e($url(['page' => $page + 1])) . '">Next ›</a>' : '<span class="off">Next ›</span>';
        $h .= '</div>';
    }
    $h .= '<label class="pager-per">Rows <select onchange="location.href=this.value">';
    foreach ([10, 25, 50, 100] as $n) {
        $h .= '<option value="' . e($url(['per' => $n, 'page' => 1])) . '"' . ($n === $per ? ' selected' : '') . '>' . $n . '</option>';
    }
    return $h . '</select></label></div>';
}

/* ---------- invoice list filters (shared by reports.php and export.php) ---------- */
// Returns [' WHERE ...', [params]] for the invoices table aliased as "i".
function invoice_where(string $role, ?int $listAdmin, array $g, bool $activeOnly = true): array {
    $w = []; $p = [];
    if ($activeOnly) $w[] = 'i.status=1';
    if ($listAdmin) { $w[] = 'i.admin_id=?'; $p[] = $listAdmin; }
    if ($role === 'staff') { $w[] = 'i.created_by=?'; $p[] = (int)($_SESSION['user_id'] ?? 0); }
    // names/phones are stored in a latin1 column; plain ASCII search text avoids collation errors
    $q = trim(preg_replace('/[^\x20-\x7E]/', '', (string)($g['q'] ?? '')));
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $w[] = '(i.invoice_no LIKE ? OR i.customer_name LIKE ? OR i.customer_phone LIKE ?)';
        array_push($p, $like, $like, $like);
    }
    foreach (['d1' => '>=', 'd2' => '<='] as $k => $op) {
        $d = (string)($g[$k] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) { $w[] = "DATE(i.created_at) $op ?"; $p[] = $d; }
    }
    return [$w ? ' WHERE ' . implode(' AND ', $w) : '', $p];
}

/* ---------- navigation bar ---------- */
function ui_nav(string $active): string {
    $role = $_SESSION['role'] ?? '';
    $links = [];
    if ($role === 'superadmin') $links[] = ['superadmin.php', '🏠 Dashboard', 'dashboard'];
    $links[] = ['inventory.php', '📦 Stock', 'stock'];          // superadmin: all admins · admin: own · staff: their admin's (read-only)
    $links[] = ['billing.php', '🧾 Billing', 'billing'];
    $links[] = ['reports.php', '📊 Invoices', 'reports'];
    if ($role === 'admin') $links[] = ['team.php', '👥 My Team', 'team'];

    $roleLabel = ['superadmin' => 'Super Admin', 'admin' => 'Admin', 'staff' => 'Staff'][$role] ?? '';
    $h  = '<nav class="topnav no-print"><div class="topnav-in">';
    $h .= '<div class="brand">💊 <span>' . e($_SESSION['full_name'] ?? 'Billing') . '</span><small>' . e($roleLabel) . '</small></div>';
    $h .= '<button class="nav-toggle" type="button" aria-label="Menu" onclick="document.getElementById(\'navLinks\').classList.toggle(\'open\')">☰</button>';
    $h .= '<div class="nav-links" id="navLinks">';
    foreach ($links as [$href, $label, $key]) {
        $h .= '<a href="' . $href . '"' . ($key === $active ? ' class="active"' : '') . '>' . $label . '</a>';
    }
    $h .= '<a href="logout.php" class="logout">Logout</a></div></div></nav>';
    return $h;
}

/* ---------- base CSS (all billing pages) ---------- */
function ui_css(): string {
    return <<<'CSS'
:root{--primary:#2563eb;--success:#16a34a;--danger:#ef4444;--warn:#d97706;--bg:#f1f5f9;--dark:#0f172a;--line:#e2e8f0;--muted:#64748b}
*{box-sizing:border-box}
body{font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;margin:0;background:var(--bg);color:#1e293b;-webkit-text-size-adjust:100%}
a{color:var(--primary)}
.topnav{background:var(--dark);position:sticky;top:0;z-index:1000;color:#fff}
.topnav-in{max-width:1240px;margin:0 auto;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.brand{font-weight:800;font-size:17px;display:flex;align-items:center;gap:8px}
.brand small{font-weight:600;font-size:10px;background:#1e293b;color:#93c5fd;padding:2px 8px;border-radius:20px;text-transform:uppercase}
.nav-links{display:flex;align-items:center;gap:4px;flex-wrap:wrap}
.nav-links a{color:#cbd5e1;text-decoration:none;font-size:14px;font-weight:600;padding:8px 14px;border-radius:8px}
.nav-links a:hover{background:#1e293b;color:#fff}
.nav-links a.active{background:var(--primary);color:#fff}
.nav-links a.logout{color:#fca5a5}
.nav-toggle{display:none;background:#1e293b;color:#fff;border:none;border-radius:8px;font-size:20px;padding:6px 12px;cursor:pointer}
.container{max-width:1240px;margin:24px auto;padding:0 20px}
.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:22px;margin-bottom:22px;box-shadow:0 4px 10px -4px rgba(0,0,0,.06)}
.card-title{font-size:18px;font-weight:700;margin:0 0 16px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:22px}
.stat-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px 18px}
.stat-card h3{margin:0;font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
.stat-card p{margin:6px 0 0;font-size:26px;font-weight:800}
input,select,textarea{font-family:inherit;font-size:14px;padding:11px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:inherit;max-width:100%}
input:focus,select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
.form-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.form-grid label{display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:4px}
.form-grid input,.form-grid select{width:100%}
.btn{display:inline-block;padding:11px 18px;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none;white-space:nowrap;font-family:inherit}
.btn-primary{background:var(--primary);color:#fff}.btn-success{background:var(--success);color:#fff}
.btn-danger{background:var(--danger);color:#fff}.btn-warn{background:var(--warn);color:#fff}
.btn-light{background:#f1f5f9;color:#0f172a}.btn-sm{padding:6px 12px;font-size:12px}
.btn[disabled]{opacity:.55;cursor:not-allowed}
table.data{width:100%;border-collapse:collapse}
table.data th{background:#f1f5f9;padding:12px 14px;text-align:left;font-size:11px;text-transform:uppercase;color:#475569}
table.data td{padding:12px 14px;border-bottom:1px solid #f1f5f9;font-size:14px;vertical-align:middle}
table.data tr:hover td{background:#f8fafc}
.badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.b-green{background:#dcfce7;color:#166534}.b-red{background:#fee2e2;color:#991b1b}
.b-amber{background:#fef3c7;color:#92400e}.b-blue{background:#dbeafe;color:#1e40af}.b-gray{background:#e2e8f0;color:#334155}
.alert{padding:12px 16px;border-radius:10px;margin-bottom:18px;font-size:14px}
.alert-success{background:#dcfce7;color:#166534;border:1px solid #86efac}
.alert-error,.alert-danger{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.alert-info{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd}
.search-bar{width:100%;max-width:320px}
.pager{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:16px;font-size:13px;color:var(--muted)}
.pager-links{display:flex;gap:4px;flex-wrap:wrap;align-items:center}
.pager-links a,.pager-links .cur,.pager-links .off,.pager-links .dots{min-width:36px;height:36px;padding:0 10px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;text-decoration:none;font-weight:600;font-size:13px}
.pager-links a{background:#fff;border:1px solid var(--line);color:#0f172a}.pager-links a:hover{border-color:var(--primary);color:var(--primary)}
.pager-links .cur{background:var(--primary);color:#fff}.pager-links .off{color:#cbd5e1;border:1px solid #f1f5f9}.pager-links .dots{color:var(--muted)}
.pager-per select{padding:6px 10px;font-size:13px}
.btn-excel{background:#107c41;color:#fff}.btn-excel:hover{background:#0b5e31}
.modal-bg{position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:5000;display:none;align-items:center;justify-content:center;padding:14px}
.modal-bg.open{display:flex}
.modal{background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:92vh;overflow:auto;padding:22px}
.modal h2{margin:0 0 14px;font-size:18px}
.toast{position:fixed;left:50%;transform:translateX(-50%);bottom:84px;background:#0f172a;color:#fff;padding:12px 18px;border-radius:10px;font-size:14px;z-index:9000;max-width:90vw;display:none;box-shadow:0 10px 30px rgba(0,0,0,.3)}
.toast.err{background:#b91c1c}.toast.ok{background:#15803d}

@media(max-width:900px){.form-grid{grid-template-columns:1fr 1fr}}
@media(max-width:768px){
 .topnav-in{padding:10px 14px}
 .nav-toggle{display:block}
 .nav-links{display:none;width:100%;flex-direction:column;align-items:stretch;padding-top:6px}
 .nav-links.open{display:flex}
 .nav-links a{padding:12px 14px;font-size:15px}
 .container{padding:0 12px;margin:14px auto}
 .card{padding:16px;border-radius:14px}
 .form-grid{grid-template-columns:1fr}
 input,select,textarea{font-size:16px} /* stops iOS zoom-on-focus */
 .search-bar{max-width:none}
 .stat-card p{font-size:22px}
 /* tables → cards */
 table.rt,table.rt tbody,table.rt tr,table.rt td{display:block;width:100%}
 table.rt thead{display:none}
 table.rt tr{border:1px solid var(--line);border-radius:12px;margin-bottom:10px;padding:8px 12px;background:#fff}
 table.rt td{border:none!important;padding:6px 0!important;display:flex;justify-content:space-between;align-items:center;gap:12px;text-align:right}
 table.rt td::before{content:attr(data-label);font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;text-align:left;flex-shrink:0}
 table.rt td.no-label::before{display:none}
 table.rt td.no-label{justify-content:flex-start;flex-wrap:wrap}
}
CSS;
}

/* ---------- invoice sheet CSS (billing.php + reports.php) ---------- */
function ui_invoice_css(): string {
    return <<<'CSS'
.invoice-canvas{width:210mm;min-height:297mm;margin:0 auto 90px;background:#fff;padding:12mm;box-shadow:0 10px 40px rgba(0,0,0,.1);color:#000}
.inv-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px;gap:16px}
.inv-header h1{margin:0;font-size:30px;font-weight:800}
.inv-sub{font-size:12px;line-height:1.45;margin-top:5px}
.logo-box img{max-width:150px;max-height:80px;object-fit:contain}
.invoice-frame{border:2px solid #000}
.row-split{display:grid;grid-template-columns:1.2fr .8fr;border-bottom:2px solid #000}
.col-box{padding:12px;border-right:2px solid #000;font-size:13px}.col-box:last-child{border-right:none}
.col-box h3{margin:0 0 8px;font-size:13px}
.kv{display:flex;margin-bottom:3px;align-items:center}.kv>span:first-child{width:70px;font-weight:700;flex-shrink:0}
table.inv{width:100%;border-collapse:collapse}
table.inv th{border-bottom:2px solid #000;border-right:1px solid #000;padding:10px 5px;font-size:11px;background:#f8fafc}
table.inv td{border-bottom:1px solid #000;border-right:1px solid #000;padding:7px 5px;font-size:12px;text-align:center}
table.inv td.c-prod{text-align:left;padding-left:8px}
.footer-summary{display:grid;grid-template-columns:1.3fr .7fr}
.summary-left{padding:14px;border-right:2px solid #000}
.sum-row{display:flex;justify-content:space-between;margin-bottom:5px;font-size:13px}
.grand-total-line{border-top:2px solid #000;padding:12px 15px;font-size:18px;display:flex;justify-content:space-between;font-weight:800}
.sign-row{display:flex;justify-content:space-between;margin-top:70px}
.sign-row div{text-align:center;border-top:1px solid #000;font-size:12px;font-weight:700;padding-top:5px}

@media screen and (max-width:768px){
 .invoice-canvas{width:100%;min-height:0;padding:10px;box-shadow:none;border-radius:12px;margin-bottom:96px}
 .inv-header{flex-direction:column;align-items:center;text-align:center}
 .inv-header h1{font-size:22px}
 .row-split,.footer-summary{grid-template-columns:1fr}
 .row-split{border-bottom:0}
 .col-box{border-right:none;border-bottom:2px solid #000}
 .summary-left{border-right:none;border-bottom:2px solid #000}
 table.inv,table.inv tbody,table.inv tr,table.inv td{display:block;width:100%}
 table.inv thead{display:none}
 table.inv tbody tr{display:grid;grid-template-columns:1fr 1fr;gap:8px 10px;border:1px solid #cbd5e1;border-radius:10px;margin:8px;padding:10px;position:relative;background:#fff;width:auto}
 table.inv td{border:none!important;padding:0!important;text-align:left!important;font-size:14px!important}
 table.inv td::before{content:attr(data-label);display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:2px}
 table.inv td.c-sr{display:none}
 table.inv td.c-prod{grid-column:1/-1;padding-right:26px!important}
 table.inv td.c-del{position:absolute;top:6px;right:10px;width:auto}
 table.inv td.c-del::before{display:none}
 .sign-row{margin-top:40px}.sign-row div{width:44%!important}
}
@media print{
 @page{size:A4;margin:8mm}
 body{background:#fff}
 .no-print,.topnav,.toast,.mbar{display:none!important}
 .invoice-canvas{box-shadow:none;margin:0;width:100%;min-height:0;padding:3mm}
 table.inv th:nth-child(5),table.inv td:nth-child(5){display:none}
 tr.blank-row{display:none}
}
CSS;
}

/* ---------- number to words (Indian system) for invoices rendered in PHP ---------- */
function num_to_words(int $num): string {
    $a = ['', 'One ', 'Two ', 'Three ', 'Four ', 'Five ', 'Six ', 'Seven ', 'Eight ', 'Nine ', 'Ten ', 'Eleven ', 'Twelve ', 'Thirteen ', 'Fourteen ', 'Fifteen ', 'Sixteen ', 'Seventeen ', 'Eighteen ', 'Nineteen '];
    $b = ['', '', 'Twenty ', 'Thirty ', 'Forty ', 'Fifty ', 'Sixty ', 'Seventy ', 'Eighty ', 'Ninety '];
    if ($num <= 0) return 'Zero Rupees Only';
    $words = function (int $n) use (&$words, $a, $b): string {
        $s = '';
        if ($n >= 10000000) { $s .= $words(intdiv($n, 10000000)) . 'Crore '; $n %= 10000000; }
        if ($n >= 100000)   { $s .= $words(intdiv($n, 100000)) . 'Lakh '; $n %= 100000; }
        if ($n >= 1000)     { $s .= $words(intdiv($n, 1000)) . 'Thousand '; $n %= 1000; }
        if ($n >= 100)      { $s .= $a[intdiv($n, 100)] . 'Hundred '; $n %= 100; }
        if ($n >= 20)       { $s .= $b[intdiv($n, 10)]; $n %= 10; }
        if ($n > 0)         { $s .= $a[$n]; }
        return $s;
    };
    return trim($words($num)) . ' Rupees Only';
}

/* ---------- company letterhead for an admin (tenant) ---------- */
function load_company(PDO $pdo, $adminId): array {
    $c = ['name' => 'Company Name Not Set', 'address' => '', 'gstin' => '', 'phone' => '',
          'bank_name' => '', 'bank_account' => '', 'bank_ifsc' => '', 'bank_branch' => ''];
    if (!$adminId) return $c;
    $st = $pdo->prepare("SELECT company_name,company_address,gstin,company_phone,bank_name,bank_account,bank_ifsc,bank_branch FROM users WHERE id=?");
    $st->execute([$adminId]);
    if ($r = $st->fetch()) {
        $c = [
            'name' => $r['company_name'] ?: 'Company Name Not Set',
            'address' => $r['company_address'] ?? '', 'gstin' => $r['gstin'] ?? '', 'phone' => $r['company_phone'] ?? '',
            'bank_name' => $r['bank_name'] ?? '', 'bank_account' => $r['bank_account'] ?? '',
            'bank_ifsc' => $r['bank_ifsc'] ?? '', 'bank_branch' => $r['bank_branch'] ?? '',
        ];
    }
    return $c;
}
