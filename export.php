<?php
/**
 * export.php — download data as Excel.
 *   ?type=stock     current stock            (respects ?admin= ?q= ?status=)
 *   ?type=invoices  invoices + their items   (respects ?admin= ?q= ?d1= ?d2=)
 *   ?type=all       FULL BACKUP: stock, every invoice (incl. cancelled), every invoice item
 *                   (+ the user list for the superadmin, never passwords)
 * Admin: own business only.  Superadmin: everything (optionally one admin via ?admin=).
 */
session_start();
require 'config.php';
require 'ui.php';
require 'xlsx.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$role = $_SESSION['role'];
if (!in_array($role, ['admin', 'superadmin'], true)) { http_response_code(403); die('Exporting is only available to admins.'); }
$is_super = ($role === 'superadmin');
$me = (int)$_SESSION['user_id'];

$list_admin = $me;
if ($is_super) {
    $list_admin = null;
    if (!empty($_GET['admin'])) {
        $c = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='admin'");
        $c->execute([(int)$_GET['admin']]);
        if ($c->fetch()) $list_admin = (int)$_GET['admin'];
    }
}
$type = in_array($_GET['type'] ?? '', ['stock', 'invoices', 'all'], true) ? $_GET['type'] : 'all';
$showAdmin = $is_super && !$list_admin;   // "Admin" column only when several businesses can appear

/* ---------------- sheet builders ---------------- */
function sheet_stock(PDO $pdo, ?int $adminId, bool $showAdmin, string $q, string $status): array {
    $sql = "SELECT p.*, u.full_name AS admin_name FROM products p LEFT JOIN users u ON u.id=p.admin_id"
         . ($adminId ? " WHERE p.admin_id=?" : "") . " ORDER BY u.full_name, p.name, p.expiry";
    $st = $pdo->prepare($sql);
    $st->execute($adminId ? [$adminId] : []);
    $rows = [];
    foreach (filter_products($st->fetchAll(), $q, $status) as $p) {
        $qty = (int)$p['stock_qty'];
        $r = $showAdmin ? [$p['admin_name'] ?? 'Unassigned'] : [];
        array_push($r, $p['name'], $p['batch'], $p['expiry'], $p['capacity'], (float)$p['rate'], (float)$p['gst_percent'], $qty,
            round(max(0, $qty) * (float)$p['rate'], 2), $qty <= 0 ? 'Out of stock' : ($qty <= LOW_STOCK_LIMIT ? 'Low stock' : 'In stock'),
            ['expired' => 'Expired', 'soon' => 'Expiring ≤90 days', 'ok' => 'OK', '' => ''][expiry_state($p['expiry'])]);
        $rows[] = $r;
    }
    $o = $showAdmin ? 1 : 0;
    $headers = array_merge($showAdmin ? ['Admin'] : [], ['Product', 'Batch', 'Expiry', 'Capacity', 'Rate (₹)', 'GST %', 'Stock Qty', 'Stock Value (₹)', 'Status', 'Expiry Status']);
    return ['headers' => $headers, 'rows' => $rows, 'money' => [4 + $o, 5 + $o, 7 + $o]];
}

function sheet_invoices(PDO $pdo, string $role, ?int $adminId, array $filters, bool $activeOnly, bool $showAdmin): array {
    [$where, $params] = invoice_where($role, $adminId, $filters, $activeOnly);
    $st = $pdo->prepare("SELECT i.*, a.full_name AS admin_name, c.full_name AS made_by
                         FROM invoices i LEFT JOIN users a ON a.id=i.admin_id LEFT JOIN users c ON c.id=i.created_by
                         $where ORDER BY i.created_at DESC, i.id DESC");
    $st->execute($params);
    $rows = [];
    foreach ($st->fetchAll() as $i) {
        $r = [$i['invoice_no'], date('Y-m-d H:i', strtotime($i['created_at']))];
        if ($showAdmin) $r[] = $i['admin_name'] ?? '';
        array_push($r, $i['customer_name'], $i['customer_phone'], $i['customer_address'], (float)($i['gst_percent'] ?? 5),
            (float)$i['total_amount'], $i['made_by'] ?? '', (int)$i['status'] === 1 ? 'Active' : 'Cancelled');
        $rows[] = $r;
    }
    $o = $showAdmin ? 1 : 0;
    $headers = array_merge(['Invoice No', 'Date'], $showAdmin ? ['Admin'] : [], ['Customer', 'Phone', 'Address', 'GST %', 'Total (₹)', 'Made by', 'Status']);
    return ['headers' => $headers, 'rows' => $rows, 'money' => [6 + $o]];
}

function sheet_items(PDO $pdo, string $role, ?int $adminId, array $filters, bool $activeOnly, bool $showAdmin): array {
    [$where, $params] = invoice_where($role, $adminId, $filters, $activeOnly);
    $st = $pdo->prepare("SELECT ii.*, i.invoice_no, i.created_at AS inv_date, i.customer_name, i.status, a.full_name AS admin_name
                         FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id LEFT JOIN users a ON a.id=i.admin_id
                         $where ORDER BY i.created_at DESC, i.id DESC, ii.id");
    $st->execute($params);
    $rows = [];
    foreach ($st->fetchAll() as $t) {
        $gross = (float)$t['rate'] * (int)$t['qty'];
        $net = round($gross * (1 - (float)$t['discount'] / 100), 2);
        $r = [$t['invoice_no'], date('Y-m-d', strtotime($t['inv_date']))];
        if ($showAdmin) $r[] = $t['admin_name'] ?? '';
        array_push($r, $t['customer_name'], $t['product_name'], $t['batch'], $t['expiry'], $t['capacity'], (int)$t['qty'],
            (float)$t['rate'], (float)$t['discount'], round($gross, 2), $net, (int)$t['status'] === 1 ? 'Active' : 'Cancelled');
        $rows[] = $r;
    }
    $o = $showAdmin ? 1 : 0;
    $headers = array_merge(['Invoice No', 'Date'], $showAdmin ? ['Admin'] : [], ['Customer', 'Product', 'Batch', 'Expiry', 'Capacity', 'Qty', 'Rate (₹)', 'Discount %', 'Amount (₹)', 'After discount (₹)', 'Status']);
    return ['headers' => $headers, 'rows' => $rows, 'money' => [8 + $o, 9 + $o, 10 + $o, 11 + $o]];
}

function sheet_users(PDO $pdo): array {
    $rows = [];
    foreach ($pdo->query("SELECT u.*, a.full_name AS admin_name FROM users u LEFT JOIN users a ON a.id=u.admin_id ORDER BY FIELD(u.role,'superadmin','admin','staff'), u.full_name")->fetchAll() as $u) {
        $rows[] = [$u['username'], $u['full_name'], $u['role'], $u['admin_name'] ?? '', (int)$u['IsActive'] ? 'Active' : 'Disabled',
                   $u['company_name'] ?? '', $u['gstin'] ?? '', $u['company_phone'] ?? '', $u['created_at'] ?? ''];
    }
    return ['headers' => ['Username', 'Name', 'Role', 'Belongs to admin', 'Status', 'Company', 'GSTIN', 'Phone', 'Created'], 'rows' => $rows];
}

/* ---------------- build + send ---------------- */
try {
    $stamp = date('Y-m-d_Hi');
    $q = (string)($_GET['q'] ?? ''); $status = (string)($_GET['status'] ?? '');
    $sheets = [];

    if ($type === 'stock') {
        $sheets['Stock'] = sheet_stock($pdo, $list_admin, $showAdmin, $q, $status);
        $file = "stock_$stamp";
    } elseif ($type === 'invoices') {
        $sheets['Invoices']      = sheet_invoices($pdo, $role, $list_admin, $_GET, true, $showAdmin);
        $sheets['Invoice Items'] = sheet_items($pdo, $role, $list_admin, $_GET, true, $showAdmin);
        $file = "invoices_$stamp";
    } else {
        $sheets['Stock']         = sheet_stock($pdo, $list_admin, $showAdmin, '', '');
        $sheets['Invoices']      = sheet_invoices($pdo, $role, $list_admin, [], false, $showAdmin);
        $sheets['Invoice Items'] = sheet_items($pdo, $role, $list_admin, [], false, $showAdmin);
        if ($is_super) $sheets['Users'] = sheet_users($pdo);
        $file = "backup_all_$stamp";
    }
    send_workbook($file, $sheets);
} catch (Throwable $e) {
    error_log('Export error: ' . $e->getMessage());
    http_response_code(500);
    die('Could not create the Excel file. Please try again, or contact support if it keeps happening.');
}
