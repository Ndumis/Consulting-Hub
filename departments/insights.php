<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';

Security::requireDepartmentAccess('Insights');

$database = new Database();
$db = $database->getConnection();

// ── KPI metrics ──────────────────────────────────────────────────────────────
$kpi = [];

// Finance
$r = $db->query("SELECT COALESCE(SUM(total_amount),0) as inv, COALESCE(SUM(paid_amount),0) as paid, COUNT(*) as cnt FROM invoices")->fetch(PDO::FETCH_ASSOC);
$kpi['total_invoiced']   = (float)$r['inv'];
$kpi['total_paid']       = (float)$r['paid'];
$kpi['outstanding']      = $kpi['total_invoiced'] - $kpi['total_paid'];
$kpi['invoice_count']    = (int)$r['cnt'];

$r2 = $db->query("SELECT COALESCE(SUM(total_amount),0) as tot, COUNT(*) as cnt FROM quotations")->fetch(PDO::FETCH_ASSOC);
$kpi['total_quoted']    = (float)$r2['tot'];
$kpi['quote_count']     = (int)$r2['cnt'];

// Clients
$kpi['total_clients']   = (int)$db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$kpi['active_clients']  = (int)$db->query("SELECT COUNT(*) FROM clients WHERE status='active'")->fetchColumn();
$kpi['prospects']       = (int)$db->query("SELECT COUNT(*) FROM clients WHERE status='prospect'")->fetchColumn();

// Projects
$kpi['total_projects']  = (int)$db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$kpi['completed']       = (int)$db->query("SELECT COUNT(*) FROM projects WHERE status='completed'")->fetchColumn();
$kpi['in_progress']     = (int)$db->query("SELECT COUNT(*) FROM projects WHERE status='in_progress'")->fetchColumn();
$kpi['avg_progress']    = round((float)$db->query("SELECT AVG(progress) FROM projects")->fetchColumn());

// HR
$kpi['employees']       = (int)$db->query("SELECT COUNT(*) FROM hr_employees WHERE status='active'")->fetchColumn();
$kpi['pending_leave']   = (int)$db->query("SELECT COUNT(*) FROM hr_leave_requests WHERE status='pending'")->fetchColumn();
$kpi['open_jobs']       = (int)$db->query("SELECT COUNT(*) FROM job_postings WHERE status='active'")->fetchColumn();

// BD
$kpi['leads']           = (int)$db->query("SELECT COUNT(*) FROM bd_leads")->fetchColumn();
$kpi['hot_leads']       = (int)$db->query("SELECT COUNT(*) FROM bd_leads WHERE lead_score >= 70")->fetchColumn();

// Marketing
$kpi['campaigns']       = (int)$db->query("SELECT COUNT(*) FROM marketing_campaigns")->fetchColumn();
$kpi['active_campaigns']= (int)$db->query("SELECT COUNT(*) FROM marketing_campaigns WHERE status='active'")->fetchColumn();

// ── Chart data ───────────────────────────────────────────────────────────────

// Monthly revenue (last 12 months)
$monthly = $db->query("SELECT DATE_FORMAT(invoice_date,'%b %Y') as lbl, DATE_FORMAT(invoice_date,'%Y-%m') as ym,
    COALESCE(SUM(total_amount),0) as invoiced, COALESCE(SUM(paid_amount),0) as paid, COUNT(*) as cnt
    FROM invoices GROUP BY ym ORDER BY ym ASC")->fetchAll(PDO::FETCH_ASSOC);

// Client revenue breakdown (top 6)
$top_clients = $db->query("SELECT c.company, COALESCE(SUM(i.total_amount),0) as rev
    FROM clients c JOIN invoices i ON c.id=i.client_id
    GROUP BY c.id ORDER BY rev DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

// Project status breakdown
$proj_status = $db->query("SELECT status, COUNT(*) as cnt FROM projects GROUP BY status ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);

// Invoice status breakdown
$inv_status = $db->query("SELECT status, COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as val FROM invoices GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);

// BD leads by industry
$leads_by_industry = $db->query("SELECT industry, COUNT(*) as cnt FROM bd_leads WHERE industry IS NOT NULL AND industry!='' GROUP BY industry ORDER BY cnt DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

// Quotation conversion
$quote_conv = $db->query("SELECT status, COUNT(*) as cnt FROM quotations GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);

// Expenses by category
$expenses = $db->query("SELECT category, COALESCE(SUM(amount),0) as total FROM expenses GROUP BY category ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

// Recent activity (last 30 days)
$thirty = date('Y-m-d', strtotime('-30 days'));
$new_clients  = (int)$db->prepare("SELECT COUNT(*) FROM clients WHERE created_at>=?")->execute([$thirty]) ? $db->query("SELECT COUNT(*) FROM clients WHERE created_at>='$thirty'")->fetchColumn() : 0;
$new_invoices = (int)$db->query("SELECT COUNT(*) FROM invoices WHERE created_at>='$thirty'")->fetchColumn();

// JS-ready arrays
$month_labels   = json_encode(array_column($monthly, 'lbl'));
$month_invoiced = json_encode(array_map(fn($r)=>round($r['invoiced'],2), $monthly));
$month_paid     = json_encode(array_map(fn($r)=>round($r['paid'],2), $monthly));

$client_labels  = json_encode(array_column($top_clients, 'company'));
$client_rev     = json_encode(array_map(fn($r)=>round($r['rev'],2), $top_clients));

$proj_labels    = json_encode(array_column($proj_status, 'status'));
$proj_data      = json_encode(array_column($proj_status, 'cnt'));

$inv_labels     = json_encode(array_column($inv_status, 'status'));
$inv_data       = json_encode(array_map(fn($r)=>round($r['val'],2), $inv_status));

$lead_labels    = json_encode(array_column($leads_by_industry, 'industry'));
$lead_data      = json_encode(array_column($leads_by_industry, 'cnt'));

$asset_base = '../'; $nav_base = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insights - KConsulting Hub</title>
    <link rel="icon" type="image/png" href="../img/KConsultingLogo1.png">
    <link rel="stylesheet" href="../css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --in:      #4f46e5;
            --in-dk:   #4338ca;
            --in-nav:  #1e1b4b;
            --in-grad: linear-gradient(135deg, #1e1b4b 0%, #4f46e5 100%);
        }

        /* Hero */
        .in-hero { background: var(--in-grad); border-radius: 16px; padding: 26px 32px; display: flex; align-items: center; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .in-hero-icon { font-size: 2.6rem; }
        .in-hero-info { flex: 1; }
        .in-hero-info h1 { color: #fff; font-size: 1.5rem; font-weight: 800; margin: 0 0 3px; }
        .in-hero-info p  { color: rgba(255,255,255,.72); font-size: .86rem; margin: 0; }

        /* KPI grid */
        .in-kpi { display: grid; grid-template-columns: repeat(6, 1fr); gap: 11px; margin-bottom: 20px; }
        @media(max-width:1100px){ .in-kpi{ grid-template-columns: repeat(3,1fr); } }
        @media(max-width:600px) { .in-kpi{ grid-template-columns: 1fr 1fr; } }
        .in-kpi-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,.05); border-top: 3px solid transparent; }
        .in-kpi-card.indigo { border-top-color: var(--in); }
        .in-kpi-card.green  { border-top-color: #059669; }
        .in-kpi-card.amber  { border-top-color: #d97706; }
        .in-kpi-card.rose   { border-top-color: #e11d48; }
        .in-kpi-card.sky    { border-top-color: #0ea5e9; }
        .in-kpi-card.violet { border-top-color: #7c3aed; }
        .in-kpi-card .num { font-size: 1.6rem; font-weight: 800; color: #111827; display: block; line-height: 1.1; }
        .in-kpi-card .lbl { font-size: .7rem; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; font-weight: 600; margin-top: 4px; }
        .in-kpi-card .sub { font-size: .75rem; color: #6b7280; margin-top: 2px; }

        /* Tabs */
        .in-tabs { display: flex; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 4px; margin-bottom: 20px; overflow-x: auto; gap: 0; }
        .in-tab  { flex: none; padding: 8px 18px; border: none; background: transparent; border-radius: 7px; cursor: pointer; font-size: .86rem; font-weight: 600; color: #6b7280; transition: all .2s; white-space: nowrap; }
        .in-tab:hover  { background: #f3f4f6; color: #111827; }
        .in-tab.active { background: var(--in); color: #fff; }
        .in-tab-content { display: none; }
        .in-tab-content.active { display: block; }

        /* Chart cards */
        .in-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .in-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        @media(max-width:900px){ .in-grid-2,.in-grid-3 { grid-template-columns: 1fr; } }
        .in-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
        .in-card h3 { font-size: .92rem; font-weight: 700; color: #111827; margin: 0 0 16px; display: flex; align-items: center; gap: 8px; }
        .in-card h3 .badge { font-size: .7rem; font-weight: 600; background: #ede9fe; color: #5b21b6; padding: 2px 8px; border-radius: 20px; }
        .chart-wrap { position: relative; height: 220px; }
        .chart-wrap-tall { position: relative; height: 280px; }

        /* Section header */
        .in-section-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .in-section-head h2 { font-size: 1rem; font-weight: 700; color: #111827; margin: 0; }
        .in-section-divider { flex: 1; height: 1px; background: #f3f4f6; }

        /* Metric rows */
        .in-metric-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f9fafb; font-size: .85rem; }
        .in-metric-row:last-child { border-bottom: none; }
        .in-metric-lbl { color: #374151; }
        .in-metric-val { font-weight: 700; color: #111827; }

        /* Mini stat row */
        .in-mini-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
        .in-mini { background: #f8fafc; border-radius: 10px; padding: 12px; text-align: center; }
        .in-mini .n { font-size: 1.2rem; font-weight: 800; color: #111827; }
        .in-mini .l { font-size: .7rem; color: #9ca3af; text-transform: uppercase; letter-spacing: .4px; margin-top: 2px; }

        /* Progress bars */
        .in-prog-wrap { background: #f3f4f6; border-radius: 6px; height: 6px; overflow: hidden; flex: 1; margin: 0 10px; }
        .in-prog-fill { height: 100%; border-radius: 6px; transition: width .4s; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">

        <!-- Hero -->
        <div class="in-hero">
            <div class="in-hero-icon">📉</div>
            <div class="in-hero-info">
                <h1>Business Insights</h1>
                <p>Real-time analytics across Finance, Projects, HR, BD &amp; Marketing</p>
            </div>
        </div>

        <!-- KPI bar -->
        <div class="in-kpi">
            <div class="in-kpi-card indigo">
                <span class="num">R<?php echo number_format($kpi['total_invoiced'],0); ?></span>
                <div class="lbl">Total Invoiced</div>
                <div class="sub">R<?php echo number_format($kpi['total_paid'],0); ?> collected</div>
            </div>
            <div class="in-kpi-card green">
                <span class="num"><?php echo $kpi['active_clients']; ?></span>
                <div class="lbl">Active Clients</div>
                <div class="sub"><?php echo $kpi['prospects']; ?> prospects</div>
            </div>
            <div class="in-kpi-card sky">
                <span class="num"><?php echo $kpi['total_projects']; ?></span>
                <div class="lbl">Projects</div>
                <div class="sub"><?php echo $kpi['completed']; ?> completed</div>
            </div>
            <div class="in-kpi-card violet">
                <span class="num"><?php echo $kpi['employees']; ?></span>
                <div class="lbl">Active Employees</div>
                <div class="sub"><?php echo $kpi['pending_leave']; ?> pending leave</div>
            </div>
            <div class="in-kpi-card amber">
                <span class="num"><?php echo $kpi['leads']; ?></span>
                <div class="lbl">BD Leads</div>
                <div class="sub"><?php echo $kpi['hot_leads']; ?> high-score</div>
            </div>
            <div class="in-kpi-card rose">
                <span class="num">R<?php echo number_format($kpi['outstanding'],0); ?></span>
                <div class="lbl">Outstanding</div>
                <div class="sub"><?php echo $kpi['invoice_count']; ?> invoices total</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="in-tabs">
            <button class="in-tab active" onclick="switchIn('overview')">📊 Overview</button>
            <button class="in-tab" onclick="switchIn('finance')">💰 Finance</button>
            <button class="in-tab" onclick="switchIn('projects')">🗂 Projects</button>
            <button class="in-tab" onclick="switchIn('hr')">👥 HR</button>
            <button class="in-tab" onclick="switchIn('bd')">🎯 BD</button>
            <button class="in-tab" onclick="switchIn('marketing')">📈 Marketing</button>
        </div>

        <!-- ══════════ OVERVIEW ══════════ -->
        <div id="tab-overview" class="in-tab-content active">
            <!-- Revenue trend -->
            <div class="in-card" style="margin-bottom:16px;">
                <h3>💰 Revenue Trend <span class="badge">All time</span></h3>
                <div class="chart-wrap-tall"><canvas id="revenueChart"></canvas></div>
            </div>

            <div class="in-grid-2">
                <!-- Top clients -->
                <div class="in-card">
                    <h3>🏢 Top Clients by Revenue</h3>
                    <div class="chart-wrap"><canvas id="clientChart"></canvas></div>
                </div>
                <!-- Project status -->
                <div class="in-card">
                    <h3>🗂 Project Status</h3>
                    <div class="chart-wrap"><canvas id="projChart"></canvas></div>
                </div>
            </div>

            <div class="in-grid-2">
                <!-- Invoice breakdown -->
                <div class="in-card">
                    <h3>📄 Invoice Values by Status</h3>
                    <div class="chart-wrap"><canvas id="invChart"></canvas></div>
                </div>
                <!-- Quick metrics -->
                <div class="in-card">
                    <h3>⚡ Quick Metrics</h3>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl">Quotation pipeline</span>
                        <span class="in-metric-val">R<?php echo number_format($kpi['total_quoted'],0); ?></span>
                    </div>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl">Collection rate</span>
                        <span class="in-metric-val"><?php echo $kpi['total_invoiced']>0?round($kpi['total_paid']/$kpi['total_invoiced']*100).'%':'—'; ?></span>
                    </div>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl">Project completion rate</span>
                        <span class="in-metric-val"><?php echo $kpi['total_projects']>0?round($kpi['completed']/$kpi['total_projects']*100).'%':'—'; ?></span>
                    </div>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl">Average project progress</span>
                        <span class="in-metric-val"><?php echo $kpi['avg_progress']; ?>%</span>
                    </div>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl">Open job positions</span>
                        <span class="in-metric-val"><?php echo $kpi['open_jobs']; ?></span>
                    </div>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl">Active marketing campaigns</span>
                        <span class="in-metric-val"><?php echo $kpi['active_campaigns']; ?> / <?php echo $kpi['campaigns']; ?></span>
                    </div>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl">Client total (active + prospects)</span>
                        <span class="in-metric-val"><?php echo $kpi['total_clients']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════ FINANCE ══════════ -->
        <div id="tab-finance" class="in-tab-content">
            <div class="in-grid-3" style="margin-bottom:16px;">
                <div class="in-card">
                    <div class="in-mini-stats">
                        <div class="in-mini"><div class="n">R<?php echo number_format($kpi['total_invoiced']/1000,1); ?>k</div><div class="l">Invoiced</div></div>
                        <div class="in-mini"><div class="n" style="color:#059669;">R<?php echo number_format($kpi['total_paid']/1000,1); ?>k</div><div class="l">Paid</div></div>
                        <div class="in-mini"><div class="n" style="color:#d97706;">R<?php echo number_format($kpi['outstanding']/1000,1); ?>k</div><div class="l">Outstanding</div></div>
                    </div>
                    <div style="margin-top:14px;">
                        <div style="font-size:.75rem;color:#9ca3af;margin-bottom:5px;">Collection rate</div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="in-prog-wrap"><div class="in-prog-fill" style="width:<?php echo $kpi['total_invoiced']>0?round($kpi['total_paid']/$kpi['total_invoiced']*100):0; ?>%;background:#059669;"></div></div>
                            <span style="font-size:.8rem;font-weight:700;"><?php echo $kpi['total_invoiced']>0?round($kpi['total_paid']/$kpi['total_invoiced']*100).'%':'0%'; ?></span>
                        </div>
                    </div>
                </div>
                <div class="in-card">
                    <h3>📋 Invoice Status</h3>
                    <?php foreach ($inv_status as $is):
                        $clrs = ['paid'=>'#059669','pending'=>'#d97706','overdue'=>'#dc2626','cancelled'=>'#9ca3af','draft'=>'#6b7280'];
                        $clr = $clrs[$is['status']] ?? '#4f46e5';
                    ?>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl" style="text-transform:capitalize;"><?php echo $is['status']; ?> (<?php echo $is['cnt']; ?>)</span>
                        <span class="in-metric-val" style="color:<?php echo $clr; ?>;">R<?php echo number_format($is['val'],0); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="in-card">
                    <h3>📝 Quotation Pipeline</h3>
                    <?php foreach ($quote_conv as $qc): $pct = $kpi['quote_count']>0?round($qc['cnt']/$kpi['quote_count']*100):0; ?>
                    <div style="margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;font-size:.8rem;margin-bottom:4px;">
                            <span style="text-transform:capitalize;color:#374151;"><?php echo $qc['status']; ?></span>
                            <span style="font-weight:700;"><?php echo $qc['cnt']; ?> (<?php echo $pct; ?>%)</span>
                        </div>
                        <div class="in-prog-wrap" style="margin:0;"><div class="in-prog-fill" style="width:<?php echo $pct; ?>%;background:var(--in);"></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="in-grid-2">
                <div class="in-card">
                    <h3>📈 Revenue Over Time</h3>
                    <div class="chart-wrap-tall"><canvas id="finRevChart"></canvas></div>
                </div>
                <div class="in-card">
                    <h3>🏢 Top Clients — Revenue</h3>
                    <?php foreach ($top_clients as $i => $tc): $pct = $top_clients[0]['rev']>0?round($tc['rev']/$top_clients[0]['rev']*100):0; ?>
                    <div style="margin-bottom:12px;">
                        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:4px;">
                            <span style="color:#374151;font-weight:500;"><?php echo Security::escapeHTML($tc['company']); ?></span>
                            <span style="font-weight:700;">R<?php echo number_format($tc['rev'],0); ?></span>
                        </div>
                        <div class="in-prog-wrap" style="margin:0;"><div class="in-prog-fill" style="width:<?php echo $pct; ?>%;background:var(--in);"></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($expenses)): ?>
            <div class="in-card">
                <h3>🧾 Expenses by Category</h3>
                <?php $exp_total = array_sum(array_column($expenses,'total')); ?>
                <div style="display:flex;flex-wrap:wrap;gap:10px;">
                <?php foreach ($expenses as $ex): $pct = $exp_total>0?round($ex['total']/$exp_total*100):0; ?>
                <div style="flex:1;min-width:140px;background:#f8fafc;border-radius:10px;padding:12px;">
                    <div style="font-size:.75rem;color:#9ca3af;text-transform:capitalize;"><?php echo Security::escapeHTML($ex['category']); ?></div>
                    <div style="font-size:1.1rem;font-weight:800;color:#111827;margin-top:3px;">R<?php echo number_format($ex['total'],0); ?></div>
                    <div style="font-size:.72rem;color:#6b7280;"><?php echo $pct; ?>% of expenses</div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══════════ PROJECTS ══════════ -->
        <div id="tab-projects" class="in-tab-content">
            <div class="in-grid-2">
                <div class="in-card">
                    <h3>🗂 Status Breakdown</h3>
                    <div class="chart-wrap"><canvas id="projStatusChart"></canvas></div>
                </div>
                <div class="in-card">
                    <h3>📊 Key Metrics</h3>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl">Total projects</span>
                        <span class="in-metric-val"><?php echo $kpi['total_projects']; ?></span>
                    </div>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl">Completed</span>
                        <span class="in-metric-val" style="color:#059669;"><?php echo $kpi['completed']; ?></span>
                    </div>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl">In progress</span>
                        <span class="in-metric-val" style="color:#d97706;"><?php echo $kpi['in_progress']; ?></span>
                    </div>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl">Avg. progress</span>
                        <span class="in-metric-val"><?php echo $kpi['avg_progress']; ?>%</span>
                    </div>
                    <?php
                    $by_dept = $db->query("SELECT department, COUNT(*) as cnt FROM projects GROUP BY department ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($by_dept as $d):
                    ?>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl"><?php echo Security::escapeHTML($d['department']??'Unassigned'); ?></span>
                        <span class="in-metric-val"><?php echo $d['cnt']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent projects table -->
            <div class="in-card">
                <h3>🗂 All Projects</h3>
                <div style="overflow-x:auto;border-radius:8px;border:1px solid #e5e7eb;">
                <table style="width:100%;border-collapse:collapse;font-size:.84rem;">
                    <thead><tr style="background:var(--in);color:#fff;">
                        <th style="padding:9px 13px;text-align:left;font-size:.73rem;text-transform:uppercase;letter-spacing:.4px;">Project</th>
                        <th style="padding:9px 13px;text-align:left;">Client</th>
                        <th style="padding:9px 13px;text-align:left;">Status</th>
                        <th style="padding:9px 13px;text-align:left;">Progress</th>
                        <th style="padding:9px 13px;text-align:left;">End Date</th>
                    </tr></thead>
                    <tbody>
                    <?php
                    $projs = $db->query("SELECT p.*, c.company FROM projects p LEFT JOIN clients c ON p.client_id=c.id ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($projs as $p): ?>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:9px 13px;font-weight:600;color:#111827;"><?php echo Security::escapeHTML($p['name']); ?></td>
                        <td style="padding:9px 13px;color:#6b7280;"><?php echo Security::escapeHTML($p['company']??'—'); ?></td>
                        <td style="padding:9px 13px;"><span style="padding:2px 9px;border-radius:20px;font-size:.72rem;font-weight:700;background:<?php echo $p['status']==='completed'?'#d1fae5':($p['status']==='in_progress'?'#fef3c7':'#f3f4f6'); ?>;color:<?php echo $p['status']==='completed'?'#065f46':($p['status']==='in_progress'?'#92400e':'#374151'); ?>;"><?php echo ucfirst($p['status']); ?></span></td>
                        <td style="padding:9px 13px;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="width:80px;height:5px;background:#e5e7eb;border-radius:5px;overflow:hidden;">
                                    <div style="width:<?php echo min(100,(int)$p['progress']); ?>%;height:100%;background:var(--in);border-radius:5px;"></div>
                                </div>
                                <span style="font-size:.75rem;color:#6b7280;"><?php echo (int)$p['progress']; ?>%</span>
                            </div>
                        </td>
                        <td style="padding:9px 13px;font-size:.78rem;color:#9ca3af;"><?php echo $p['end_date']?date('d M Y',strtotime($p['end_date'])):'—'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- ══════════ HR ══════════ -->
        <div id="tab-hr" class="in-tab-content">
            <div class="in-grid-3">
                <div class="in-card">
                    <h3>👥 Headcount</h3>
                    <?php
                    $by_dept = $db->query("SELECT department, COUNT(*) as cnt FROM hr_employees WHERE status='active' GROUP BY department ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
                    $max_cnt = $by_dept[0]['cnt'] ?? 1;
                    foreach ($by_dept as $d):
                        $pct = round($d['cnt']/$max_cnt*100);
                    ?>
                    <div style="margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px;">
                            <span style="color:#374151;"><?php echo Security::escapeHTML($d['department']); ?></span>
                            <span style="font-weight:700;"><?php echo $d['cnt']; ?></span>
                        </div>
                        <div class="in-prog-wrap" style="margin:0;"><div class="in-prog-fill" style="width:<?php echo $pct; ?>%;background:#7c3aed;"></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="in-card">
                    <h3>📅 Leave Summary</h3>
                    <?php $leave_by_type = $db->query("SELECT leave_type, status, COUNT(*) as cnt FROM hr_leave_requests GROUP BY leave_type, status")->fetchAll(PDO::FETCH_ASSOC); ?>
                    <?php
                    $lv_agg = [];
                    foreach ($leave_by_type as $l) { $lv_agg[$l['status']] = ($lv_agg[$l['status']]??0) + $l['cnt']; }
                    ?>
                    <div class="in-metric-row"><span class="in-metric-lbl">Pending approval</span><span class="in-metric-val" style="color:#d97706;"><?php echo $lv_agg['pending']??0; ?></span></div>
                    <div class="in-metric-row"><span class="in-metric-lbl">Approved</span><span class="in-metric-val" style="color:#059669;"><?php echo $lv_agg['approved']??0; ?></span></div>
                    <div class="in-metric-row"><span class="in-metric-lbl">Rejected</span><span class="in-metric-val" style="color:#dc2626;"><?php echo $lv_agg['rejected']??0; ?></span></div>
                    <div class="in-metric-row"><span class="in-metric-lbl">Total requests</span><span class="in-metric-val"><?php echo array_sum($lv_agg); ?></span></div>
                    <div class="in-metric-row"><span class="in-metric-lbl">Open job postings</span><span class="in-metric-val" style="color:#7c3aed;"><?php echo $kpi['open_jobs']; ?></span></div>
                    <div class="in-metric-row"><span class="in-metric-lbl">Performance reviews</span><span class="in-metric-val"><?php echo (int)$db->query("SELECT COUNT(*) FROM performance_reviews")->fetchColumn(); ?></span></div>
                </div>
                <div class="in-card">
                    <h3>🔗 Portal Linkage</h3>
                    <?php
                    $linked   = (int)$db->query("SELECT COUNT(*) FROM hr_employees WHERE user_id IS NOT NULL")->fetchColumn();
                    $unlinked = $kpi['employees'] - $linked;
                    $pct_link = $kpi['employees']>0?round($linked/$kpi['employees']*100):0;
                    ?>
                    <div style="text-align:center;margin-bottom:16px;">
                        <div style="font-size:2rem;font-weight:800;color:var(--in);"><?php echo $pct_link; ?>%</div>
                        <div style="font-size:.78rem;color:#9ca3af;">employees with portal accounts</div>
                    </div>
                    <div class="in-prog-wrap" style="margin:0 0 12px;height:10px;border-radius:10px;">
                        <div class="in-prog-fill" style="width:<?php echo $pct_link; ?>%;background:linear-gradient(90deg,#7c3aed,#4f46e5);border-radius:10px;"></div>
                    </div>
                    <div class="in-metric-row"><span class="in-metric-lbl">Linked</span><span class="in-metric-val" style="color:#059669;"><?php echo $linked; ?></span></div>
                    <div class="in-metric-row"><span class="in-metric-lbl">Not linked</span><span class="in-metric-val" style="color:#9ca3af;"><?php echo $unlinked; ?></span></div>
                </div>
            </div>
        </div>

        <!-- ══════════ BD ══════════ -->
        <div id="tab-bd" class="in-tab-content">
            <div class="in-grid-2">
                <div class="in-card">
                    <h3>🎯 Leads by Industry</h3>
                    <?php if (!empty($leads_by_industry)): ?>
                    <div class="chart-wrap"><canvas id="bdIndustryChart"></canvas></div>
                    <?php else: ?>
                    <p style="color:#9ca3af;font-size:.87rem;">No industry data yet.</p>
                    <?php endif; ?>
                </div>
                <div class="in-card">
                    <h3>📊 Lead Metrics</h3>
                    <?php
                    $leads_by_status = $db->query("SELECT status, COUNT(*) as cnt FROM bd_leads GROUP BY status ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
                    $leads_total = array_sum(array_column($leads_by_status,'cnt'));
                    foreach ($leads_by_status as $l): $pct = $leads_total>0?round($l['cnt']/$leads_total*100):0; ?>
                    <div style="margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px;">
                            <span style="text-transform:capitalize;color:#374151;"><?php echo $l['status']; ?></span>
                            <span style="font-weight:700;"><?php echo $l['cnt']; ?> (<?php echo $pct; ?>%)</span>
                        </div>
                        <div class="in-prog-wrap" style="margin:0;"><div class="in-prog-fill" style="width:<?php echo $pct; ?>%;background:#059669;"></div></div>
                    </div>
                    <?php endforeach; ?>
                    <div style="margin-top:14px;padding-top:14px;border-top:1px solid #f3f4f6;">
                        <div class="in-metric-row"><span class="in-metric-lbl">High-score leads (≥70)</span><span class="in-metric-val" style="color:#059669;"><?php echo $kpi['hot_leads']; ?></span></div>
                        <div class="in-metric-row"><span class="in-metric-lbl">Total leads</span><span class="in-metric-val"><?php echo $kpi['leads']; ?></span></div>
                        <?php $act_cnt = (int)$db->query("SELECT COUNT(*) FROM bd_activities")->fetchColumn(); ?>
                        <div class="in-metric-row"><span class="in-metric-lbl">Total activities logged</span><span class="in-metric-val"><?php echo $act_cnt; ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════ MARKETING ══════════ -->
        <div id="tab-marketing" class="in-tab-content">
            <div class="in-grid-2">
                <div class="in-card">
                    <h3>📈 Campaign Overview</h3>
                    <?php
                    $camp_by_status = $db->query("SELECT status, COUNT(*) as cnt FROM marketing_campaigns GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
                    $camp_total = array_sum(array_column($camp_by_status,'cnt'));
                    foreach ($camp_by_status as $c): $pct = $camp_total>0?round($c['cnt']/$camp_total*100):0; ?>
                    <div style="margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:3px;">
                            <span style="text-transform:capitalize;color:#374151;"><?php echo $c['status']; ?></span>
                            <span style="font-weight:700;"><?php echo $c['cnt']; ?></span>
                        </div>
                        <div class="in-prog-wrap" style="margin:0;"><div class="in-prog-fill" style="width:<?php echo $pct; ?>%;background:#0ea5e9;"></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="in-card">
                    <h3>📊 Channel Breakdown</h3>
                    <?php
                    $camp_by_type = $db->query("SELECT COALESCE(campaign_type,'Other') as t, COUNT(*) as cnt FROM marketing_campaigns GROUP BY t ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($camp_by_type as $c):
                    ?>
                    <div class="in-metric-row">
                        <span class="in-metric-lbl" style="text-transform:capitalize;"><?php echo Security::escapeHTML($c['t']); ?></span>
                        <span class="in-metric-val"><?php echo $c['cnt']; ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div style="margin-top:14px;padding-top:14px;border-top:1px solid #f3f4f6;">
                        <?php $email_cnt = (int)$db->query("SELECT COUNT(*) FROM email_campaigns")->fetchColumn(); ?>
                        <?php $social_cnt = (int)$db->query("SELECT COUNT(*) FROM social_media_posts")->fetchColumn(); ?>
                        <div class="in-metric-row"><span class="in-metric-lbl">Email campaigns</span><span class="in-metric-val"><?php echo $email_cnt; ?></span></div>
                        <div class="in-metric-row"><span class="in-metric-lbl">Social media posts</span><span class="in-metric-val"><?php echo $social_cnt; ?></span></div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.main-content -->

    <script>
    // ── Tab switch ────────────────────────────────────────────────────────────
    const chartsInit = {};
    function switchIn(name) {
        document.querySelectorAll('.in-tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.in-tab').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-'+name)?.classList.add('active');
        document.querySelectorAll('.in-tab').forEach(b => {
            if (b.textContent.toLowerCase().includes(name.slice(0,3))) b.classList.add('active');
        });
        initCharts(name);
    }

    // ── Chart colours ─────────────────────────────────────────────────────────
    const PALETTE = ['#4f46e5','#0ea5e9','#059669','#d97706','#e11d48','#7c3aed','#0284c7','#047857'];
    const SOFT    = PALETTE.map(c => c + '99');

    function initCharts(tab) {
        if (tab === 'overview' || tab === undefined) {
            if (!chartsInit.revenue) {
                chartsInit.revenue = new Chart(document.getElementById('revenueChart'), {
                    type: 'bar',
                    data: {
                        labels: <?php echo $month_labels; ?>,
                        datasets: [
                            { label: 'Invoiced', data: <?php echo $month_invoiced; ?>, backgroundColor: '#4f46e599', borderColor: '#4f46e5', borderWidth: 1.5, borderRadius: 4 },
                            { label: 'Paid', data: <?php echo $month_paid; ?>, backgroundColor: '#05996999', borderColor: '#059669', borderWidth: 1.5, borderRadius: 4 }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { callback: v => 'R'+v.toLocaleString() } } } }
                });
            }
            if (!chartsInit.client) {
                chartsInit.client = new Chart(document.getElementById('clientChart'), {
                    type: 'bar',
                    data: { labels: <?php echo $client_labels; ?>, datasets: [{ label: 'Revenue', data: <?php echo $client_rev; ?>, backgroundColor: SOFT, borderColor: PALETTE, borderWidth: 1.5, borderRadius: 4 }] },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => 'R'+v.toLocaleString() } } } }
                });
            }
            if (!chartsInit.proj) {
                chartsInit.proj = new Chart(document.getElementById('projChart'), {
                    type: 'doughnut',
                    data: { labels: <?php echo $proj_labels; ?>, datasets: [{ data: <?php echo $proj_data; ?>, backgroundColor: PALETTE, borderWidth: 2, borderColor: '#fff' }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '60%' }
                });
            }
            if (!chartsInit.inv) {
                chartsInit.inv = new Chart(document.getElementById('invChart'), {
                    type: 'doughnut',
                    data: { labels: <?php echo $inv_labels; ?>, datasets: [{ data: <?php echo $inv_data; ?>, backgroundColor: PALETTE.slice(2), borderWidth: 2, borderColor: '#fff' }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '60%' }
                });
            }
        }
        if (tab === 'finance' && !chartsInit.finRev) {
            chartsInit.finRev = new Chart(document.getElementById('finRevChart'), {
                type: 'line',
                data: {
                    labels: <?php echo $month_labels; ?>,
                    datasets: [
                        { label: 'Invoiced', data: <?php echo $month_invoiced; ?>, borderColor: '#4f46e5', backgroundColor: '#4f46e520', fill: true, tension: 0.4, pointRadius: 4 },
                        { label: 'Paid', data: <?php echo $month_paid; ?>, borderColor: '#059669', backgroundColor: '#05996920', fill: true, tension: 0.4, pointRadius: 4 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { callback: v => 'R'+v.toLocaleString() } } } }
            });
        }
        if (tab === 'projects' && !chartsInit.projStatus) {
            chartsInit.projStatus = new Chart(document.getElementById('projStatusChart'), {
                type: 'doughnut',
                data: { labels: <?php echo $proj_labels; ?>, datasets: [{ data: <?php echo $proj_data; ?>, backgroundColor: PALETTE, borderWidth: 2, borderColor: '#fff' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '55%' }
            });
        }
        if (tab === 'bd' && !chartsInit.bdIndustry && <?php echo count($leads_by_industry)>0?'true':'false'; ?>) {
            chartsInit.bdIndustry = new Chart(document.getElementById('bdIndustryChart'), {
                type: 'doughnut',
                data: { labels: <?php echo $lead_labels; ?>, datasets: [{ data: <?php echo $lead_data; ?>, backgroundColor: PALETTE, borderWidth: 2, borderColor: '#fff' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '55%' }
            });
        }
    }

    // Init overview charts on load
    window.addEventListener('DOMContentLoaded', () => initCharts('overview'));
    </script>
</body>
</html>

