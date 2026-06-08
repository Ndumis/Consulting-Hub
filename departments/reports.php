<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';

Security::requireDepartmentAccess('Finance');

$database = new Database();
$db = $database->getConnection();

$sess_role = $_SESSION['role'] ?? 'staff';
$can_write = in_array($sess_role, ['admin','manager']);

// ── Date range ────────────────────────────────────────────────────────────────
$period     = $_GET['period'] ?? 'all';
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

$date_clause_inv = '';
$date_params     = [];

if ($period === 'this_month') {
    $date_clause_inv = ' AND invoice_date >= ? AND invoice_date <= ?';
    $date_params = [date('Y-m-01'), date('Y-m-t')];
} elseif ($period === 'last_month') {
    $date_clause_inv = ' AND invoice_date >= ? AND invoice_date <= ?';
    $date_params = [date('Y-m-01', strtotime('-1 month')), date('Y-m-t', strtotime('-1 month'))];
} elseif ($period === 'this_year') {
    $date_clause_inv = ' AND invoice_date >= ? AND invoice_date <= ?';
    $date_params = [date('Y-01-01'), date('Y-12-31')];
} elseif ($period === 'custom' && $start_date && $end_date) {
    $date_clause_inv = ' AND invoice_date >= ? AND invoice_date <= ?';
    $date_params = [$start_date, $end_date];
}

function qExec($db, $sql, $params=[]) {
    $s = $db->prepare($sql); $s->execute($params); return $s;
}

// ── Financial ─────────────────────────────────────────────────────────────────
$inv_q = qExec($db, "SELECT i.*, c.company FROM invoices i LEFT JOIN clients c ON i.client_id=c.id WHERE 1=1 $date_clause_inv ORDER BY invoice_date DESC", $date_params);
$invoices = $inv_q->fetchAll(PDO::FETCH_ASSOC);

$total_invoiced    = array_sum(array_column($invoices,'total_amount'));
$total_paid        = array_sum(array_column($invoices,'paid_amount'));
$total_outstanding = $total_invoiced - $total_paid;

$quote_date_clause = str_replace('invoice_date','quotation_date',$date_clause_inv);
$quote_q = qExec($db, "SELECT q.*, c.company FROM quotations q LEFT JOIN clients c ON q.client_id=c.id WHERE 1=1 $quote_date_clause ORDER BY quotation_date DESC", $date_params);
$quotations   = $quote_q->fetchAll(PDO::FETCH_ASSOC);
$total_quoted = array_sum(array_column($quotations,'total_amount'));
$quote_accepted = count(array_filter($quotations,fn($q)=>$q['status']==='accepted'));

$exp_q  = qExec($db, "SELECT * FROM expenses ORDER BY expense_date DESC");
$expenses = $exp_q->fetchAll(PDO::FETCH_ASSOC);
$total_expenses    = array_sum(array_column($expenses,'amount'));
$approved_expenses = array_sum(array_map(fn($e)=>$e['amount'], array_filter($expenses,fn($e)=>$e['status']==='approved')));

// Monthly trend
$monthly_trend = qExec($db, "SELECT DATE_FORMAT(invoice_date,'%Y-%m') as ym, DATE_FORMAT(invoice_date,'%b %Y') as lbl,
    COALESCE(SUM(total_amount),0) as invoiced, COALESCE(SUM(paid_amount),0) as paid, COUNT(*) as cnt
    FROM invoices GROUP BY ym ORDER BY ym ASC")->fetchAll(PDO::FETCH_ASSOC);

// Top clients
$top_clients = qExec($db, "SELECT c.company, c.status as cstatus,
    COALESCE(SUM(i.total_amount),0) as invoiced, COALESCE(SUM(i.paid_amount),0) as paid,
    COUNT(i.id) as invoice_cnt
    FROM clients c LEFT JOIN invoices i ON c.id=i.client_id
    GROUP BY c.id ORDER BY invoiced DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// ── Projects ──────────────────────────────────────────────────────────────────
$projects = qExec($db, "SELECT p.*, c.company FROM projects p LEFT JOIN clients c ON p.client_id=c.id ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$proj_by_status = [];
foreach ($projects as $p) { $proj_by_status[$p['status']] = ($proj_by_status[$p['status']] ?? 0) + 1; }

// ── HR ────────────────────────────────────────────────────────────────────────
$employees     = qExec($db, "SELECT department, COUNT(*) as cnt, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active FROM hr_employees GROUP BY department ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
$leave_summary = qExec($db, "SELECT leave_type, status, COUNT(*) as cnt FROM hr_leave_requests GROUP BY leave_type, status ORDER BY leave_type")->fetchAll(PDO::FETCH_ASSOC);

// ── Custom reports ────────────────────────────────────────────────────────────
$custom_reports = qExec($db, "SELECT cr.*, u.username as created_by_name FROM custom_reports cr LEFT JOIN users u ON cr.created_by=u.id ORDER BY cr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_write) {
    if (!Security::checkCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_msg = 'CSRF validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_report') {
            $rname   = Security::sanitizeInput($_POST['report_name'] ?? '');
            $rdesc   = Security::sanitizeInput($_POST['report_description'] ?? '');
            $rtype   = Security::sanitizeInput($_POST['report_type'] ?? 'financial');
            $rsql    = trim($_POST['sql_query'] ?? '');
            $rparams = trim($_POST['parameters'] ?? '');
            if (!$rname || !$rsql) {
                $error_msg = 'Report name and SQL query are required.';
            } else {
                $s = $db->prepare("INSERT INTO custom_reports (name,description,report_type,sql_query,parameters,created_by,created_at) VALUES (?,?,?,?,?,?,NOW())");
                $s->execute([$rname,$rdesc,$rtype,$rsql,$rparams,$_SESSION['user_id']]);
                header("Location: reports.php?msg=" . urlencode('Report saved successfully.'));
                exit();
            }
        } elseif ($action === 'delete_report') {
            $rid = (int)($_POST['report_id'] ?? 0);
            if ($rid > 0) {
                $db->prepare("DELETE FROM custom_reports WHERE id=?")->execute([$rid]);
                header("Location: reports.php?msg=" . urlencode('Report deleted.'));
                exit();
            }
        }
    }
}

if (isset($_GET['msg'])) { $success_msg = Security::escapeHTML($_GET['msg']); }

$asset_base = '../'; $nav_base = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - KConsulting Hub</title>
    <link rel="icon" type="image/png" href="../img/KConsultingLogo1.png">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        :root {
            --rp:      #0f172a;
            --rp-mid:  #334155;
            --rp-blue: #2563eb;
            --rp-grad: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
        }

        .rp-hero { background: var(--rp-grad); border-radius: 16px; padding: 26px 32px; display: flex; align-items: center; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .rp-hero-icon { font-size: 2.6rem; }
        .rp-hero-info h1 { color: #fff; font-size: 1.5rem; font-weight: 800; margin: 0 0 3px; }
        .rp-hero-info p  { color: rgba(255,255,255,.65); font-size: .86rem; margin: 0; }
        .rp-hero-actions { margin-left: auto; }

        .rp-filter { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        .rp-filter select, .rp-filter input[type=date] { border: 1px solid #d1d5db; border-radius: 7px; padding: 6px 10px; font-size: .84rem; color: #374151; }
        .rp-filter-label { font-size: .8rem; font-weight: 600; color: #6b7280; white-space: nowrap; }
        .rp-btn { padding: 7px 18px; border-radius: 8px; border: none; cursor: pointer; font-size: .84rem; font-weight: 600; text-decoration: none; display: inline-block; }
        .rp-btn-primary { background: var(--rp-blue); color: #fff; }
        .rp-btn-ghost   { background: transparent; border: 1px solid #d1d5db; color: #374151; }
        .rp-btn-ghost:hover { background: #f9fafb; }
        .rp-btn-danger  { background: transparent; border: 1px solid #fecaca; color: #dc2626; }
        .rp-btn-danger:hover { background: #fef2f2; }

        .rp-tabs { display: flex; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 4px; margin-bottom: 20px; overflow-x: auto; gap: 0; }
        .rp-tab  { flex: none; padding: 8px 18px; border: none; background: transparent; border-radius: 7px; cursor: pointer; font-size: .86rem; font-weight: 600; color: #6b7280; }
        .rp-tab:hover  { background: #f3f4f6; color: #111827; }
        .rp-tab.active { background: var(--rp); color: #fff; }
        .rp-tab-content { display: none; }
        .rp-tab-content.active { display: block; }

        .rp-grid-4 { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 18px; }
        .rp-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        @media(max-width:1000px){ .rp-grid-4 { grid-template-columns: 1fr 1fr; } }
        @media(max-width:700px) { .rp-grid-4,.rp-grid-2 { grid-template-columns: 1fr; } }

        .rp-stat { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(0,0,0,.05); border-left: 4px solid transparent; }
        .rp-stat.blue  { border-left-color: var(--rp-blue); }
        .rp-stat.green { border-left-color: #059669; }
        .rp-stat.amber { border-left-color: #d97706; }
        .rp-stat.red   { border-left-color: #dc2626; }
        .rp-stat .num { font-size: 1.7rem; font-weight: 800; color: #111827; }
        .rp-stat .lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; font-weight: 600; margin-top: 3px; }
        .rp-stat .sub { font-size: .78rem; color: #6b7280; margin-top: 2px; }

        .rp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; margin-bottom: 16px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
        .rp-card-head { padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f3f4f6; }
        .rp-card-head h3 { font-size: .95rem; font-weight: 700; color: #111827; margin: 0; }
        .rp-card-sub { font-size: .78rem; color: #9ca3af; }
        .rp-tbl { width: 100%; border-collapse: collapse; font-size: .84rem; }
        .rp-tbl thead tr { background: var(--rp); color: #fff; }
        .rp-tbl th { padding: 9px 14px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .4px; font-weight: 600; }
        .rp-tbl td { padding: 9px 14px; color: #374151; border-bottom: 1px solid #f9fafb; }
        .rp-tbl tbody tr:hover { background: #f8fafc; }
        .rp-tbl tbody tr:last-child td { border-bottom: none; }

        .badge-paid      { background: #d1fae5; color: #065f46; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .badge-pending   { background: #fef3c7; color: #92400e; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .badge-overdue   { background: #fce7f3; color: #9d174d; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .badge-draft     { background: #f3f4f6; color: #374151; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .badge-accepted  { background: #dbeafe; color: #1e40af; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .badge-cancelled { background: #f3f4f6; color: #9ca3af; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .badge-sent      { background: #ede9fe; color: #5b21b6; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .badge-rejected  { background: #fce7f3; color: #9d174d; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .badge-active    { background: #d1fae5; color: #065f46; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .badge-prospect  { background: #fef3c7; color: #92400e; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 700; }

        .rp-form-group { margin-bottom: 14px; }
        .rp-form-group label { display: block; font-size: .82rem; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .rp-form-group input, .rp-form-group select, .rp-form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .85rem; box-sizing: border-box; }
        .rp-form-group textarea { min-height: 80px; font-family: monospace; }

        @media print {
            .sidebar, header, .rp-hero-actions, .rp-filter, .rp-tabs, form, button { display: none !important; }
            .main-content { margin: 0; padding: 0; }
            .rp-tab-content { display: block !important; }
            .rp-card { box-shadow: none; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">

        <?php if ($error_msg): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 18px;margin-bottom:16px;color:#dc2626;font-size:.87rem;">⚠️ <?php echo $error_msg; ?></div>
        <?php endif; ?>
        <?php if ($success_msg): ?>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 18px;margin-bottom:16px;color:#059669;font-size:.87rem;">✅ <?php echo $success_msg; ?></div>
        <?php endif; ?>

        <!-- Hero -->
        <div class="rp-hero">
            <div class="rp-hero-icon">📋</div>
            <div class="rp-hero-info">
                <h1>Reports</h1>
                <p>Financial, client, project &amp; HR data — filterable by period</p>
            </div>
            <div class="rp-hero-actions">
                <button class="rp-btn rp-btn-ghost" onclick="window.print()" style="color:#fff;border-color:rgba(255,255,255,.3);">🖨 Print</button>
            </div>
        </div>

        <!-- Date filter -->
        <form method="get" action="">
            <div class="rp-filter">
                <span class="rp-filter-label">Period:</span>
                <select name="period" onchange="toggleCustomDates(this.value)">
                    <option value="all" <?php echo $period==='all'?'selected':''; ?>>All time</option>
                    <option value="this_month" <?php echo $period==='this_month'?'selected':''; ?>>This month</option>
                    <option value="last_month" <?php echo $period==='last_month'?'selected':''; ?>>Last month</option>
                    <option value="this_year" <?php echo $period==='this_year'?'selected':''; ?>>This year</option>
                    <option value="custom" <?php echo $period==='custom'?'selected':''; ?>>Custom range</option>
                </select>
                <span id="custom-date-wrap" style="display:<?php echo $period==='custom'?'flex':'none'; ?>;gap:6px;align-items:center;">
                    <div style="display:flex;align-items:center;gap:3px;">
                        <input type="date" id="rpDateFrom" name="start_date" value="<?php echo Security::escapeHTML($start_date); ?>">
                        <?php if ($start_date): ?><button type="button" onclick="clearRpDate('rpDateFrom')" style="border:none;background:none;cursor:pointer;color:#9ca3af;font-size:1.1rem;line-height:1;padding:0 2px;" title="Clear">×</button><?php endif; ?>
                    </div>
                    <span style="font-size:.8rem;color:#9ca3af;">to</span>
                    <div style="display:flex;align-items:center;gap:3px;">
                        <input type="date" id="rpDateTo" name="end_date" value="<?php echo Security::escapeHTML($end_date); ?>">
                        <?php if ($end_date): ?><button type="button" onclick="clearRpDate('rpDateTo')" style="border:none;background:none;cursor:pointer;color:#9ca3af;font-size:1.1rem;line-height:1;padding:0 2px;" title="Clear">×</button><?php endif; ?>
                    </div>
                </span>
                <button type="submit" class="rp-btn rp-btn-primary">Apply</button>
                <a href="reports.php" class="rp-btn rp-btn-ghost">Reset</a>
            </div>
        </form>

        <!-- KPI row -->
        <div class="rp-grid-4">
            <div class="rp-stat blue">
                <div class="num">R<?php echo number_format($total_invoiced,0); ?></div>
                <div class="lbl">Total Invoiced</div>
                <div class="sub"><?php echo count($invoices); ?> invoice<?php echo count($invoices)!=1?'s':''; ?></div>
            </div>
            <div class="rp-stat green">
                <div class="num">R<?php echo number_format($total_paid,0); ?></div>
                <div class="lbl">Collected</div>
                <div class="sub"><?php echo $total_invoiced>0?round($total_paid/$total_invoiced*100).'% rate':'—'; ?></div>
            </div>
            <div class="rp-stat amber">
                <div class="num">R<?php echo number_format($total_outstanding,0); ?></div>
                <div class="lbl">Outstanding</div>
                <div class="sub">
                    <?php $overdue_cnt = count(array_filter($invoices,fn($i)=>$i['status']==='overdue')); ?>
                    <?php echo $overdue_cnt; ?> overdue
                </div>
            </div>
            <div class="rp-stat red">
                <div class="num">R<?php echo number_format($total_quoted,0); ?></div>
                <div class="lbl">Quoted Pipeline</div>
                <div class="sub"><?php echo $quote_accepted; ?> / <?php echo count($quotations); ?> accepted</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="rp-tabs">
            <button class="rp-tab active" onclick="switchRP(this,'financial')">💰 Financial</button>
            <button class="rp-tab" onclick="switchRP(this,'clients')">🏢 Clients</button>
            <button class="rp-tab" onclick="switchRP(this,'projects')">🗂 Projects</button>
            <button class="rp-tab" onclick="switchRP(this,'hr')">👥 HR</button>
            <button class="rp-tab" onclick="switchRP(this,'custom')">📌 Saved Reports</button>
        </div>

        <!-- ══════════ FINANCIAL ══════════ -->
        <div id="tab-financial" class="rp-tab-content active">
            <div class="rp-card">
                <div class="rp-card-head">
                    <h3>📄 Invoices</h3>
                    <span class="rp-card-sub">R<?php echo number_format($total_invoiced,2); ?> total · R<?php echo number_format($total_paid,2); ?> paid</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="rp-tbl">
                        <thead><tr>
                            <th>Invoice #</th><th>Client</th><th>Date</th><th>Due</th>
                            <th>Amount</th><th>Paid</th><th>Outstanding</th><th>Status</th><th>PDF</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($invoices as $inv):
                            $ost = $inv['total_amount'] - $inv['paid_amount'];
                        ?>
                        <tr>
                            <td style="font-weight:600;color:#111827;"><?php echo Security::escapeHTML($inv['invoice_number']); ?></td>
                            <td><?php echo Security::escapeHTML($inv['company'] ?? '—'); ?></td>
                            <td><?php echo $inv['invoice_date'] ? date('d M Y', strtotime($inv['invoice_date'])) : '—'; ?></td>
                            <td><?php echo $inv['due_date'] ? date('d M Y', strtotime($inv['due_date'])) : '—'; ?></td>
                            <td style="font-weight:600;">R<?php echo number_format($inv['total_amount'],2); ?></td>
                            <td style="color:#059669;">R<?php echo number_format($inv['paid_amount'],2); ?></td>
                            <td style="color:<?php echo $ost>0?'#d97706':'#059669'; ?>;">R<?php echo number_format($ost,2); ?></td>
                            <td><span class="badge-<?php echo $inv['status']; ?>"><?php echo ucfirst($inv['status']); ?></span></td>
                            <td><a href="../finance_pdf.php?type=invoice&id=<?php echo $inv['id']; ?>" target="_blank" style="color:var(--rp-blue);font-size:.8rem;text-decoration:none;font-weight:600;">PDF ↗</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($invoices)): ?>
                        <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:24px;">No invoices for this period.</td></tr>
                        <?php endif; ?>
                        </tbody>
                        <?php if (count($invoices) > 1): ?>
                        <tfoot><tr style="background:#f8fafc;font-weight:700;">
                            <td colspan="4" style="padding:9px 14px;color:#374151;">Totals</td>
                            <td style="padding:9px 14px;">R<?php echo number_format($total_invoiced,2); ?></td>
                            <td style="padding:9px 14px;color:#059669;">R<?php echo number_format($total_paid,2); ?></td>
                            <td style="padding:9px 14px;color:#d97706;">R<?php echo number_format($total_outstanding,2); ?></td>
                            <td colspan="2"></td>
                        </tr></tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="rp-card">
                <div class="rp-card-head">
                    <h3>📝 Quotations</h3>
                    <span class="rp-card-sub">R<?php echo number_format($total_quoted,2); ?> pipeline · <?php echo $quote_accepted; ?> accepted</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="rp-tbl">
                        <thead><tr>
                            <th>Quote #</th><th>Client</th><th>Date</th><th>Valid Until</th><th>Amount</th><th>Status</th><th>PDF</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($quotations as $q): ?>
                        <tr>
                            <td style="font-weight:600;color:#111827;"><?php echo Security::escapeHTML($q['quotation_number']); ?></td>
                            <td><?php echo Security::escapeHTML($q['company'] ?? '—'); ?></td>
                            <td><?php echo $q['quotation_date'] ? date('d M Y', strtotime($q['quotation_date'])) : '—'; ?></td>
                            <td><?php echo $q['valid_until'] ? date('d M Y', strtotime($q['valid_until'])) : '—'; ?></td>
                            <td style="font-weight:600;">R<?php echo number_format($q['total_amount'],2); ?></td>
                            <td><span class="badge-<?php echo $q['status']; ?>"><?php echo ucfirst($q['status']); ?></span></td>
                            <td><a href="../finance_pdf.php?type=quotation&id=<?php echo $q['id']; ?>" target="_blank" style="color:var(--rp-blue);font-size:.8rem;text-decoration:none;font-weight:600;">PDF ↗</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($quotations)): ?>
                        <tr><td colspan="7" style="text-align:center;color:#9ca3af;padding:24px;">No quotations for this period.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!empty($monthly_trend)): ?>
            <div class="rp-card">
                <div class="rp-card-head">
                    <h3>📈 Monthly Revenue Breakdown</h3>
                    <span class="rp-card-sub">All-time</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="rp-tbl">
                        <thead><tr><th>Period</th><th>Invoices</th><th>Total Invoiced</th><th>Collected</th><th>Collection Rate</th></tr></thead>
                        <tbody>
                        <?php foreach ($monthly_trend as $m):
                            $rate = $m['invoiced']>0?round($m['paid']/$m['invoiced']*100):0;
                        ?>
                        <tr>
                            <td style="font-weight:600;"><?php echo $m['lbl']; ?></td>
                            <td><?php echo $m['cnt']; ?></td>
                            <td>R<?php echo number_format($m['invoiced'],2); ?></td>
                            <td style="color:#059669;">R<?php echo number_format($m['paid'],2); ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:80px;height:5px;background:#e5e7eb;border-radius:5px;overflow:hidden;"><div style="width:<?php echo $rate; ?>%;height:100%;background:#059669;border-radius:5px;"></div></div>
                                    <span style="font-size:.78rem;"><?php echo $rate; ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="rp-card">
                <div class="rp-card-head">
                    <h3>🧾 Expenses</h3>
                    <span class="rp-card-sub">R<?php echo number_format($total_expenses,2); ?> total · R<?php echo number_format($approved_expenses,2); ?> approved</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="rp-tbl">
                        <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($expenses as $ex): ?>
                        <tr>
                            <td><?php echo $ex['expense_date'] ? date('d M Y', strtotime($ex['expense_date'])) : '—'; ?></td>
                            <td style="text-transform:capitalize;"><?php echo Security::escapeHTML($ex['category']); ?></td>
                            <td style="color:#6b7280;"><?php echo Security::escapeHTML($ex['description'] ?? ''); ?></td>
                            <td style="font-weight:600;">R<?php echo number_format($ex['amount'],2); ?></td>
                            <td>
                                <?php $eb = $ex['status']==='approved'?'paid':($ex['status']==='rejected'?'overdue':'pending'); ?>
                                <span class="badge-<?php echo $eb; ?>"><?php echo ucfirst($ex['status']); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($expenses)): ?>
                        <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:24px;">No expense records.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══════════ CLIENTS ══════════ -->
        <div id="tab-clients" class="rp-tab-content">
            <div class="rp-card">
                <div class="rp-card-head">
                    <h3>🏢 Client Revenue Summary</h3>
                    <span class="rp-card-sub">Ranked by total invoiced (all time)</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="rp-tbl">
                        <thead><tr>
                            <th>#</th><th>Company</th><th>Status</th><th>Invoices</th>
                            <th>Total Invoiced</th><th>Collected</th><th>Outstanding</th><th>Rate</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($top_clients as $i => $cl):
                            $cl_ost  = $cl['invoiced'] - $cl['paid'];
                            $cl_rate = $cl['invoiced'] > 0 ? round($cl['paid']/$cl['invoiced']*100) : 0;
                        ?>
                        <tr>
                            <td style="color:#9ca3af;font-size:.8rem;"><?php echo $i+1; ?></td>
                            <td style="font-weight:600;color:#111827;"><?php echo Security::escapeHTML($cl['company']); ?></td>
                            <td><span class="badge-<?php echo $cl['cstatus']; ?>"><?php echo ucfirst($cl['cstatus']); ?></span></td>
                            <td><?php echo $cl['invoice_cnt']; ?></td>
                            <td style="font-weight:600;">R<?php echo number_format($cl['invoiced'],2); ?></td>
                            <td style="color:#059669;">R<?php echo number_format($cl['paid'],2); ?></td>
                            <td style="color:<?php echo $cl_ost>0?'#d97706':'#059669'; ?>;">R<?php echo number_format($cl_ost,2); ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div style="width:60px;height:5px;background:#e5e7eb;border-radius:5px;overflow:hidden;"><div style="width:<?php echo $cl_rate; ?>%;height:100%;background:var(--rp-blue);border-radius:5px;"></div></div>
                                    <span style="font-size:.78rem;"><?php echo $cl_rate; ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($top_clients)): ?>
                        <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:24px;">No client data.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══════════ PROJECTS ══════════ -->
        <div id="tab-projects" class="rp-tab-content">
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
                <?php foreach ($proj_by_status as $st => $cnt): ?>
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:12px 20px;text-align:center;">
                    <div style="font-size:1.4rem;font-weight:800;color:#111827;"><?php echo $cnt; ?></div>
                    <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;color:#9ca3af;margin-top:2px;"><?php echo str_replace('_',' ',$st); ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="rp-card">
                <div class="rp-card-head">
                    <h3>🗂 All Projects</h3>
                    <span class="rp-card-sub"><?php echo count($projects); ?> total</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="rp-tbl">
                        <thead><tr>
                            <th>Project</th><th>Client</th><th>Dept</th><th>Status</th>
                            <th>Progress</th><th>Start</th><th>End</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($projects as $p):
                            $st_map = ['completed'=>'paid','in_progress'=>'pending','on_hold'=>'overdue'];
                            $st_b   = $st_map[$p['status']] ?? 'draft';
                        ?>
                        <tr>
                            <td style="font-weight:600;color:#111827;"><?php echo Security::escapeHTML($p['name']); ?></td>
                            <td style="color:#6b7280;"><?php echo Security::escapeHTML($p['company'] ?? '—'); ?></td>
                            <td style="color:#6b7280;"><?php echo Security::escapeHTML($p['department'] ?? '—'); ?></td>
                            <td><span class="badge-<?php echo $st_b; ?>"><?php echo ucfirst(str_replace('_',' ',$p['status'])); ?></span></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="width:80px;height:5px;background:#e5e7eb;border-radius:5px;overflow:hidden;"><div style="width:<?php echo min(100,(int)$p['progress']); ?>%;height:100%;background:var(--rp-blue);border-radius:5px;"></div></div>
                                    <span style="font-size:.78rem;"><?php echo (int)$p['progress']; ?>%</span>
                                </div>
                            </td>
                            <td style="font-size:.8rem;color:#9ca3af;"><?php echo $p['start_date'] ? date('d M Y', strtotime($p['start_date'])) : '—'; ?></td>
                            <td style="font-size:.8rem;color:#9ca3af;"><?php echo $p['end_date'] ? date('d M Y', strtotime($p['end_date'])) : '—'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projects)): ?>
                        <tr><td colspan="7" style="text-align:center;color:#9ca3af;padding:24px;">No projects found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══════════ HR ══════════ -->
        <div id="tab-hr" class="rp-tab-content">
            <div class="rp-grid-2">
                <div class="rp-card">
                    <div class="rp-card-head"><h3>👥 Headcount by Department</h3></div>
                    <div style="overflow-x:auto;">
                        <table class="rp-tbl">
                            <thead><tr><th>Department</th><th>Total</th><th>Active</th></tr></thead>
                            <tbody>
                            <?php foreach ($employees as $e): ?>
                            <tr>
                                <td style="font-weight:600;"><?php echo Security::escapeHTML($e['department']); ?></td>
                                <td><?php echo $e['cnt']; ?></td>
                                <td style="color:#059669;"><?php echo $e['active']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($employees)): ?>
                            <tr><td colspan="3" style="text-align:center;color:#9ca3af;padding:24px;">No employee data.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="rp-card">
                    <div class="rp-card-head"><h3>📅 Leave Requests by Type</h3></div>
                    <div style="overflow-x:auto;">
                        <table class="rp-tbl">
                            <thead><tr><th>Type</th><th>Status</th><th>Count</th></tr></thead>
                            <tbody>
                            <?php foreach ($leave_summary as $l):
                                $sb = ['approved'=>'paid','rejected'=>'overdue','pending'=>'pending'][$l['status']] ?? 'draft';
                            ?>
                            <tr>
                                <td style="text-transform:capitalize;"><?php echo Security::escapeHTML(str_replace('_',' ',$l['leave_type'])); ?></td>
                                <td><span class="badge-<?php echo $sb; ?>"><?php echo ucfirst($l['status']); ?></span></td>
                                <td style="font-weight:600;"><?php echo $l['cnt']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($leave_summary)): ?>
                            <tr><td colspan="3" style="text-align:center;color:#9ca3af;padding:24px;">No leave data.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════ CUSTOM REPORTS ══════════ -->
        <div id="tab-custom" class="rp-tab-content">
            <?php if ($can_write): ?>
            <div class="rp-card" style="margin-bottom:16px;">
                <div class="rp-card-head"><h3>➕ Save New Report Template</h3></div>
                <div style="padding:20px;">
                    <form method="post">
                        <?php echo Security::getCSRFTokenField(); ?>
                        <input type="hidden" name="action" value="save_report">
                        <div class="rp-grid-2">
                            <div>
                                <div class="rp-form-group">
                                    <label>Report Name *</label>
                                    <input type="text" name="report_name" placeholder="e.g. Monthly Revenue Summary" required>
                                </div>
                                <div class="rp-form-group">
                                    <label>Description</label>
                                    <input type="text" name="report_description" placeholder="Optional description">
                                </div>
                                <div class="rp-form-group">
                                    <label>Report Type</label>
                                    <select name="report_type">
                                        <option value="financial">Financial</option>
                                        <option value="clients">Clients</option>
                                        <option value="projects">Projects</option>
                                        <option value="hr">HR</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                                <div class="rp-form-group">
                                    <label>Parameters (JSON, optional)</label>
                                    <input type="text" name="parameters" placeholder='{"period":"this_month"}'>
                                </div>
                            </div>
                            <div>
                                <div class="rp-form-group">
                                    <label>SQL Query * <span style="font-size:.75rem;color:#9ca3af;">(SELECT queries only)</span></label>
                                    <textarea name="sql_query" style="min-height:180px;" placeholder="SELECT ..."></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="rp-btn rp-btn-primary">Save Report</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="rp-card">
                <div class="rp-card-head">
                    <h3>📌 Saved Report Templates</h3>
                    <span class="rp-card-sub"><?php echo count($custom_reports); ?> saved</span>
                </div>
                <?php if (empty($custom_reports)): ?>
                <div style="padding:32px;text-align:center;color:#9ca3af;font-size:.87rem;">No saved reports yet.</div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="rp-tbl">
                        <thead><tr>
                            <th>Name</th><th>Type</th><th>Description</th>
                            <th>Created by</th><th>Date</th><?php if($can_write): ?><th></th><?php endif; ?>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($custom_reports as $cr): ?>
                        <tr>
                            <td style="font-weight:600;color:#111827;"><?php echo Security::escapeHTML($cr['name']); ?></td>
                            <td><span style="padding:2px 9px;border-radius:20px;font-size:.72rem;font-weight:700;background:#ede9fe;color:#5b21b6;"><?php echo Security::escapeHTML($cr['report_type']); ?></span></td>
                            <td style="color:#6b7280;"><?php echo Security::escapeHTML($cr['description'] ?? ''); ?></td>
                            <td style="color:#6b7280;"><?php echo Security::escapeHTML($cr['created_by_name'] ?? '—'); ?></td>
                            <td style="font-size:.78rem;color:#9ca3af;"><?php echo $cr['created_at'] ? date('d M Y', strtotime($cr['created_at'])) : '—'; ?></td>
                            <?php if ($can_write): ?>
                            <td>
                                <form method="post" onsubmit="return confirm('Delete this saved report?');" style="display:inline;">
                                    <?php echo Security::getCSRFTokenField(); ?>
                                    <input type="hidden" name="action" value="delete_report">
                                    <input type="hidden" name="report_id" value="<?php echo $cr['id']; ?>">
                                    <button type="submit" class="rp-btn rp-btn-danger" style="padding:4px 10px;font-size:.75rem;">Delete</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.main-content -->

    <script>
    function switchRP(btn, name) {
        document.querySelectorAll('.rp-tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.rp-tab').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-'+name)?.classList.add('active');
        btn.classList.add('active');
    }

    function toggleCustomDates(val) {
        document.getElementById('custom-date-wrap').style.display = val === 'custom' ? 'flex' : 'none';
    }

    function clearRpDate(id) {
        const el = document.getElementById(id);
        if (el) { el.value = ''; el.closest('form').submit(); }
    }
    </script>
</body>
</html>

