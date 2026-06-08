<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';

Security::requireDepartmentAccess('Clients');

$database = new Database();
$db = $database->getConnection();

$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$client_id) { header("Location: clients.php"); exit(); }

$can_write = in_array($_SESSION['role'] ?? '', ['admin', 'manager']);
$csrf = Security::generateCSRFToken();

// ── Update client ────────────────────────────────────────────────────────────
if ($_POST && isset($_POST['update_client'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Clients');
    $name   = Security::sanitizeInput($_POST['name']);
    $email  = Security::sanitizeInput($_POST['email']);
    $phone  = Security::sanitizeInput($_POST['phone']);
    $company= Security::sanitizeInput($_POST['company']);
    $address= Security::sanitizeInput($_POST['address']);
    $status = Security::sanitizeInput($_POST['status']);
    if (!in_array($status, ['active','prospect','inactive'])) $status = 'prospect';
    $stmt = $db->prepare("UPDATE clients SET name=?,email=?,phone=?,company=?,address=?,status=? WHERE id=?");
    $stmt->execute([$name,$email,$phone,$company,$address,$status,$client_id]);
    $success_message = "Client updated.";
}

// ── Add contact ──────────────────────────────────────────────────────────────
if ($_POST && isset($_POST['create_contact'])) {
    Security::checkCSRFToken();
    $name       = Security::sanitizeInput($_POST['contact_name']);
    $email      = Security::sanitizeInput($_POST['contact_email']);
    $phone      = Security::sanitizeInput($_POST['contact_phone']);
    $position   = Security::sanitizeInput($_POST['contact_position']);
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    // Only one primary per client
    if ($is_primary) $db->prepare("UPDATE client_contacts SET is_primary=0 WHERE client_id=?")->execute([$client_id]);
    $stmt = $db->prepare("INSERT INTO client_contacts (client_id,name,email,phone,position,is_primary,assigned_to) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$client_id,$name,$email,$phone,$position,$is_primary,$_SESSION['user_id']]);
    $success_message = "Contact added.";
}

// ── Schedule meeting ─────────────────────────────────────────────────────────
if ($_POST && isset($_POST['create_meeting'])) {
    Security::checkCSRFToken();
    $title  = Security::sanitizeInput($_POST['meeting_title']);
    $date   = Security::sanitizeInput($_POST['meeting_date']);
    $loc    = Security::sanitizeInput($_POST['location']);
    $agenda = Security::sanitizeInput($_POST['agenda']);
    $stmt = $db->prepare("INSERT INTO client_meetings (client_id,meeting_title,meeting_date,location,agenda,created_by,status) VALUES (?,?,?,?,?,?,'scheduled')");
    $stmt->execute([$client_id,$title,$date,$loc,$agenda,$_SESSION['user_id']]);
    $success_message = "Meeting scheduled.";
}

// ── Update meeting ───────────────────────────────────────────────────────────
if ($_POST && isset($_POST['update_meeting'])) {
    Security::checkCSRFToken();
    $mid    = (int)$_POST['meeting_id'];
    $notes  = Security::sanitizeInput($_POST['notes']);
    $status = Security::sanitizeInput($_POST['status']);
    if (!in_array($status, ['scheduled','completed','cancelled'])) $status = 'scheduled';
    $db->prepare("UPDATE client_meetings SET notes=?,status=? WHERE id=? AND client_id=?")->execute([$notes,$status,$mid,$client_id]);
    $success_message = "Meeting updated.";
}

// ── Fetch client ─────────────────────────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM clients WHERE id=?");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$client) { header("Location: clients.php"); exit(); }

// ── Fetch related data ───────────────────────────────────────────────────────
$contacts = $db->prepare("SELECT cc.*, u.username AS assigned_to_name FROM client_contacts cc LEFT JOIN users u ON cc.assigned_to=u.id WHERE cc.client_id=? ORDER BY cc.is_primary DESC, cc.created_at ASC");
$contacts->execute([$client_id]);
$contacts = $contacts->fetchAll(PDO::FETCH_ASSOC);

$meetings_stmt = $db->prepare("SELECT cm.*, u.username AS created_by_name FROM client_meetings cm LEFT JOIN users u ON cm.created_by=u.id WHERE cm.client_id=? ORDER BY cm.meeting_date DESC");
$meetings_stmt->execute([$client_id]);
$meetings = $meetings_stmt->fetchAll(PDO::FETCH_ASSOC);

$projects_stmt = $db->prepare("SELECT p.*, u.username AS created_by_name FROM projects p LEFT JOIN users u ON p.created_by=u.id WHERE p.client_id=? ORDER BY p.created_at DESC");
$projects_stmt->execute([$client_id]);
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);

$invoices_stmt = $db->prepare("SELECT i.*, u.username AS created_by_name FROM invoices i LEFT JOIN users u ON i.created_by=u.id WHERE i.client_id=? ORDER BY i.created_at DESC");
$invoices_stmt->execute([$client_id]);
$invoices = $invoices_stmt->fetchAll(PDO::FETCH_ASSOC);

$quotes_stmt = $db->prepare("SELECT q.*, u.username AS created_by_name FROM quotations q LEFT JOIN users u ON q.created_by=u.id WHERE q.client_id=? ORDER BY q.created_at DESC");
$quotes_stmt->execute([$client_id]);
$quotes = $quotes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Financial totals
$total_invoiced   = array_sum(array_column($invoices, 'total_amount'));
$total_paid       = array_sum(array_column(array_filter($invoices, fn($i)=>$i['status']==='paid'), 'total_amount'));
$total_outstanding= $total_invoiced - $total_paid;
$total_quoted     = array_sum(array_column($quotes, 'total_amount'));

$initials = strtoupper(substr($client['company'],0,1).substr($client['name'],0,1));

$asset_base = '../';
$nav_base   = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Security::escapeHTML($client['company']); ?> - KConsulting Hub</title>
    <link rel="icon" type="image/png" href="../img/KConsultingLogo1.png">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        :root {
            --cl:      #0ea5e9;
            --cl-dk:   #0284c7;
            --cl-navy: #0c4a6e;
            --cl-grad: linear-gradient(135deg, #0c4a6e 0%, #0ea5e9 100%);
        }

        /* Hero */
        .cd-hero {
            background: var(--cl-grad);
            border-radius: 16px; padding: 28px 32px;
            display: flex; align-items: center; gap: 20px;
            margin-bottom: 20px; flex-wrap: wrap;
        }
        .cd-logo {
            width: 64px; height: 64px; border-radius: 14px;
            background: rgba(255,255,255,.2); border: 2px solid rgba(255,255,255,.35);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; font-weight: 800; color: #fff; flex-shrink: 0;
        }
        .cd-hero-info { flex: 1; min-width: 180px; }
        .cd-hero-info h1 { color: #fff; font-size: 1.6rem; font-weight: 800; margin: 0 0 3px; }
        .cd-hero-info .sub { color: rgba(255,255,255,.75); font-size: .87rem; }
        .cd-hero-chips { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
        .cd-chip { background: rgba(255,255,255,.15); color: #fff; padding: 3px 12px; border-radius: 20px; font-size: .75rem; font-weight: 600; border: 1px solid rgba(255,255,255,.2); }
        .cd-chip.green { background: rgba(16,185,129,.4); }
        .cd-chip.amber { background: rgba(245,158,11,.4); }
        .cd-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-left: auto; }
        .cd-hero-btn { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); border-radius: 8px; padding: 8px 16px; font-size: .83rem; font-weight: 600; cursor: pointer; transition: background .2s; text-decoration: none; display: inline-block; }
        .cd-hero-btn:hover { background: rgba(255,255,255,.28); }
        .cd-hero-btn.back { background: transparent; border-color: rgba(255,255,255,.3); }

        /* Stats */
        .cd-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 20px; }
        @media(max-width:700px){ .cd-stats{ grid-template-columns: 1fr 1fr; } }
        .cd-stat { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 18px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
        .cd-stat .num { font-size: 1.5rem; font-weight: 800; color: #111827; display: block; }
        .cd-stat .lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; font-weight: 600; }
        .cd-stat .num.sky   { color: var(--cl); }
        .cd-stat .num.green { color: #059669; }
        .cd-stat .num.amber { color: #d97706; }
        .cd-stat .num.rose  { color: #e11d48; }

        /* Flash */
        .cd-flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: .87rem; font-weight: 500; }
        .cd-flash.success { background: #e0f2fe; color: #0c4a6e; border-left: 4px solid var(--cl); }
        .cd-flash.error   { background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; }

        /* Tab nav */
        .cd-tabs { display: flex; gap: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 4px; margin-bottom: 20px; overflow-x: auto; }
        .cd-tab  { flex: none; padding: 9px 20px; border: none; background: transparent; border-radius: 7px; cursor: pointer; font-size: .87rem; font-weight: 600; color: #6b7280; transition: all .2s; white-space: nowrap; }
        .cd-tab:hover  { background: #f3f4f6; color: #111827; }
        .cd-tab.active { background: var(--cl); color: #fff; }
        .cd-tab-content { display: none; }
        .cd-tab-content.active { display: block; }

        /* Card */
        .cd-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.05); margin-bottom: 16px; }
        .cd-card h3 { font-size: .95rem; font-weight: 700; color: #111827; margin: 0 0 14px; padding-bottom: 10px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between; }

        /* Info grid */
        .cd-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .cd-info-item .lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .4px; color: #9ca3af; font-weight: 600; margin-bottom: 3px; }
        .cd-info-item .val { font-size: .9rem; color: #111827; font-weight: 500; }

        /* Table */
        .cd-table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid #e5e7eb; }
        .cd-table { width: 100%; border-collapse: collapse; font-size: .85rem; background: #fff; }
        .cd-table th { background: var(--cl); color: #fff; padding: 10px 13px; text-align: left; font-size: .73rem; text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; }
        .cd-table td { padding: 10px 13px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }
        .cd-table tr:last-child td { border-bottom: none; }
        .cd-table tr:hover td { background: #f0f9ff; }

        /* Badges */
        .cbadge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700; text-transform: capitalize; }
        .cbadge-active, .cbadge-completed, .cbadge-paid { background: #d1fae5; color: #065f46; }
        .cbadge-prospect,.cbadge-pending,.cbadge-in_progress { background: #fef3c7; color: #92400e; }
        .cbadge-inactive,.cbadge-cancelled { background: #f3f4f6; color: #6b7280; }
        .cbadge-scheduled { background: #dbeafe; color: #1e40af; }
        .cbadge-overdue   { background: #fee2e2; color: #991b1b; }
        .cbadge-sent      { background: #ede9fe; color: #5b21b6; }
        .cbadge-accepted  { background: #d1fae5; color: #065f46; }
        .cbadge-draft     { background: #f9fafb; color: #6b7280; border: 1px solid #e5e7eb; }
        .cbadge-primary   { background: #e0f2fe; color: #0c4a6e; }

        /* Buttons */
        .cd-btn { padding: 6px 14px; border: none; border-radius: 7px; font-size: .8rem; font-weight: 600; cursor: pointer; transition: all .2s; text-decoration: none; display: inline-block; }
        .cd-btn-sky  { background: var(--cl); color: #fff; }
        .cd-btn-sky:hover  { background: var(--cl-dk); }
        .cd-btn-gray { background: #f3f4f6; color: #374151; }
        .cd-btn-gray:hover { background: #e5e7eb; }

        /* Collapsible */
        .cd-collapsible-header { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 11px 16px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; font-weight: 600; color: var(--cl-navy); font-size: .88rem; margin-bottom: 0; }
        .cd-collapsible-body { display: none; padding: 18px; border: 1px solid #bae6fd; border-top: none; border-radius: 0 0 10px 10px; background: #fff; margin-bottom: 16px; }
        .cd-collapsible-body.open { display: block; }

        /* Form grid */
        .cd-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .cd-form-grid .full { grid-column: 1/-1; }
        @media(max-width:600px){ .cd-form-grid{grid-template-columns:1fr;} }
        .cd-field label { display: block; font-size: .77rem; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .cd-field input,.cd-field select,.cd-field textarea { width: 100%; padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 7px; font-size: .87rem; color: #111827; }
        .cd-field textarea { height: 70px; resize: vertical; }
        .cd-field input:focus,.cd-field select:focus,.cd-field textarea:focus { outline: none; border-color: var(--cl); box-shadow: 0 0 0 3px rgba(14,165,233,.1); }

        /* Progress bar */
        .prog-wrap { width: 100px; height: 6px; background: #e5e7eb; border-radius: 6px; overflow: hidden; display: inline-block; vertical-align: middle; margin-right: 6px; }
        .prog-fill { height: 100%; border-radius: 6px; background: var(--cl); }

        /* Modal */
        .cd-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000; align-items: center; justify-content: center; }
        .cd-modal-overlay.open { display: flex; }
        .cd-modal { background: #fff; border-radius: 16px; width: min(560px,95vw); max-height: 90vh; overflow-y: auto; padding: 28px; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,.25); }
        .cd-modal h2 { font-size: 1.1rem; font-weight: 700; color: #111827; margin: 0 0 20px; }
        .cd-modal-close { position: absolute; top: 16px; right: 16px; background: #f3f4f6; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; }
        .cd-modal-close:hover { background: #e5e7eb; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">

        <?php if (!empty($success_message)): ?>
        <div class="cd-flash success"><?php echo Security::escapeHTML($success_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
        <div class="cd-flash error"><?php echo Security::escapeHTML($error_message); ?></div>
        <?php endif; ?>

        <!-- Hero -->
        <div class="cd-hero">
            <div class="cd-logo"><?php echo $initials; ?></div>
            <div class="cd-hero-info">
                <h1><?php echo Security::escapeHTML($client['company']); ?></h1>
                <div class="sub"><?php echo Security::escapeHTML($client['name']); ?> &middot; <?php echo Security::escapeHTML($client['email']??''); ?></div>
                <div class="cd-hero-chips">
                    <span class="cd-chip <?php echo $client['status']==='active'?'green':($client['status']==='prospect'?'amber':''); ?>">
                        <?php echo ucfirst($client['status']); ?>
                    </span>
                    <span class="cd-chip"><?php echo count($projects); ?> projects</span>
                    <span class="cd-chip"><?php echo count($contacts); ?> contacts</span>
                    <?php if ($total_outstanding > 0): ?>
                    <span class="cd-chip amber">R<?php echo number_format($total_outstanding,0); ?> outstanding</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="cd-hero-actions">
                <a href="clients.php" class="cd-hero-btn back">← Back</a>
                <?php if ($can_write): ?>
                <button class="cd-hero-btn" onclick="document.getElementById('editClientModal').classList.add('open')">✏️ Edit</button>
                <?php endif; ?>
                <a href="finance.php" class="cd-hero-btn">💰 Finance</a>
            </div>
        </div>

        <!-- Stats -->
        <div class="cd-stats">
            <div class="cd-stat">
                <span class="num sky"><?php echo count($projects); ?></span>
                <span class="lbl">Projects</span>
            </div>
            <div class="cd-stat">
                <span class="num green">R<?php echo number_format($total_paid,0); ?></span>
                <span class="lbl">Paid</span>
            </div>
            <div class="cd-stat">
                <span class="num amber">R<?php echo number_format($total_outstanding,0); ?></span>
                <span class="lbl">Outstanding</span>
            </div>
            <div class="cd-stat">
                <span class="num rose"><?php echo count(array_filter($meetings,fn($m)=>$m['status']==='scheduled')); ?></span>
                <span class="lbl">Upcoming Meetings</span>
            </div>
        </div>

        <!-- Tabs -->
        <div class="cd-tabs">
            <button class="cd-tab active" onclick="switchTab('overview')">📋 Overview</button>
            <button class="cd-tab" onclick="switchTab('contacts')">👤 Contacts (<?php echo count($contacts); ?>)</button>
            <button class="cd-tab" onclick="switchTab('meetings')">📅 Meetings (<?php echo count($meetings); ?>)</button>
            <button class="cd-tab" onclick="switchTab('projects')">🗂 Projects (<?php echo count($projects); ?>)</button>
            <button class="cd-tab" onclick="switchTab('financials')">💰 Financials</button>
        </div>

        <!-- ══════════ TAB: OVERVIEW ══════════ -->
        <div id="tab-overview" class="cd-tab-content active">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;flex-wrap:wrap;">
                <!-- Client info -->
                <div class="cd-card">
                    <h3>Client Information</h3>
                    <div class="cd-info-grid">
                        <div class="cd-info-item"><div class="lbl">Contact Name</div><div class="val"><?php echo Security::escapeHTML($client['name']); ?></div></div>
                        <div class="cd-info-item"><div class="lbl">Company</div><div class="val"><?php echo Security::escapeHTML($client['company']); ?></div></div>
                        <div class="cd-info-item"><div class="lbl">Email</div><div class="val"><?php echo Security::escapeHTML($client['email']??'—'); ?></div></div>
                        <div class="cd-info-item"><div class="lbl">Phone</div><div class="val"><?php echo Security::escapeHTML($client['phone']??'—'); ?></div></div>
                        <div class="cd-info-item" style="grid-column:1/-1;"><div class="lbl">Address</div><div class="val"><?php echo Security::escapeHTML($client['address']??'—'); ?></div></div>
                        <div class="cd-info-item"><div class="lbl">Status</div><div class="val"><span class="cbadge cbadge-<?php echo $client['status']; ?>"><?php echo ucfirst($client['status']); ?></span></div></div>
                        <div class="cd-info-item"><div class="lbl">Client Since</div><div class="val"><?php echo date('d M Y',strtotime($client['created_at'])); ?></div></div>
                    </div>
                </div>
                <!-- Financial summary -->
                <div class="cd-card">
                    <h3>Financial Summary</h3>
                    <div class="cd-info-grid">
                        <div class="cd-info-item"><div class="lbl">Total Quoted</div><div class="val" style="color:var(--cl);font-weight:700;">R<?php echo number_format($total_quoted,2); ?></div></div>
                        <div class="cd-info-item"><div class="lbl">Total Invoiced</div><div class="val" style="font-weight:700;">R<?php echo number_format($total_invoiced,2); ?></div></div>
                        <div class="cd-info-item"><div class="lbl">Paid</div><div class="val" style="color:#059669;font-weight:700;">R<?php echo number_format($total_paid,2); ?></div></div>
                        <div class="cd-info-item"><div class="lbl">Outstanding</div><div class="val" style="color:#d97706;font-weight:700;">R<?php echo number_format($total_outstanding,2); ?></div></div>
                        <div class="cd-info-item"><div class="lbl">Quotations</div><div class="val"><?php echo count($quotes); ?></div></div>
                        <div class="cd-info-item"><div class="lbl">Invoices</div><div class="val"><?php echo count($invoices); ?></div></div>
                    </div>
                </div>
            </div>

            <!-- Recent projects -->
            <?php if (!empty($projects)): ?>
            <div class="cd-card">
                <h3>Active Projects <a href="?id=<?php echo $client_id; ?>&tab=projects" onclick="switchTab('projects');return false;" style="font-size:.78rem;font-weight:400;color:var(--cl);">View all →</a></h3>
                <div class="cd-table-wrap">
                <table class="cd-table">
                    <thead><tr><th>Project</th><th>Status</th><th>Progress</th><th>End Date</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($projects,0,4) as $pr): ?>
                    <tr>
                        <td style="font-weight:500;"><?php echo Security::escapeHTML($pr['name']); ?></td>
                        <td><span class="cbadge cbadge-<?php echo str_replace(' ','_',$pr['status']); ?>"><?php echo ucfirst($pr['status']); ?></span></td>
                        <td>
                            <div class="prog-wrap"><div class="prog-fill" style="width:<?php echo min(100,(int)$pr['progress']); ?>%"></div></div>
                            <span style="font-size:.78rem;color:#6b7280;"><?php echo (int)$pr['progress']; ?>%</span>
                        </td>
                        <td style="font-size:.8rem;"><?php echo $pr['end_date'] ? date('d M Y',strtotime($pr['end_date'])) : '—'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Primary contact -->
            <?php $primary = array_values(array_filter($contacts, fn($c)=>$c['is_primary']))[0] ?? ($contacts[0] ?? null); ?>
            <?php if ($primary): ?>
            <div class="cd-card">
                <h3>Primary Contact</h3>
                <div class="cd-info-grid">
                    <div class="cd-info-item"><div class="lbl">Name</div><div class="val"><?php echo Security::escapeHTML($primary['name']); ?></div></div>
                    <div class="cd-info-item"><div class="lbl">Position</div><div class="val"><?php echo Security::escapeHTML($primary['position']??'—'); ?></div></div>
                    <div class="cd-info-item"><div class="lbl">Email</div><div class="val"><?php echo Security::escapeHTML($primary['email']??'—'); ?></div></div>
                    <div class="cd-info-item"><div class="lbl">Phone</div><div class="val"><?php echo Security::escapeHTML($primary['phone']??'—'); ?></div></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══════════ TAB: CONTACTS ══════════ -->
        <div id="tab-contacts" class="cd-tab-content">
            <?php if ($can_write): ?>
            <div style="margin-bottom:4px;">
            <div class="cd-collapsible-header" onclick="toggleCD('ctForm')">
                <span>+ Add Contact</span><span id="ctForm-arrow">▼</span>
            </div>
            <div class="cd-collapsible-body" id="ctForm-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="create_contact" value="1">
                    <div class="cd-form-grid">
                        <div class="cd-field"><label>Full Name *</label><input type="text" name="contact_name" required></div>
                        <div class="cd-field"><label>Position</label><input type="text" name="contact_position"></div>
                        <div class="cd-field"><label>Email</label><input type="email" name="contact_email"></div>
                        <div class="cd-field"><label>Phone</label><input type="tel" name="contact_phone"></div>
                        <div class="cd-field" style="display:flex;align-items:center;gap:8px;margin-top:20px;">
                            <input type="checkbox" name="is_primary" id="cd_is_primary" style="width:auto;">
                            <label for="cd_is_primary" style="margin:0;font-size:.87rem;">Set as primary contact</label>
                        </div>
                    </div>
                    <div style="margin-top:14px;"><button type="submit" class="cd-btn cd-btn-sky">Add Contact</button></div>
                </form>
            </div>
            </div>
            <?php endif; ?>

            <?php if (empty($contacts)): ?>
            <div class="cd-card" style="text-align:center;color:#9ca3af;">No contacts yet.</div>
            <?php else: ?>
            <div class="cd-table-wrap">
            <table class="cd-table">
                <thead><tr><th>Name</th><th>Position</th><th>Email</th><th>Phone</th><th>Primary</th></tr></thead>
                <tbody>
                <?php foreach ($contacts as $ct): ?>
                <tr>
                    <td style="font-weight:600;"><?php echo Security::escapeHTML($ct['name']); ?></td>
                    <td><?php echo Security::escapeHTML($ct['position']??'—'); ?></td>
                    <td style="font-size:.82rem;"><?php echo Security::escapeHTML($ct['email']??'—'); ?></td>
                    <td style="font-size:.82rem;"><?php echo Security::escapeHTML($ct['phone']??'—'); ?></td>
                    <td><?php echo $ct['is_primary']?'<span class="cbadge cbadge-primary">Primary</span>':'<span style="color:#d1d5db;">—</span>'; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══════════ TAB: MEETINGS ══════════ -->
        <div id="tab-meetings" class="cd-tab-content">
            <?php if ($can_write): ?>
            <div style="margin-bottom:4px;">
            <div class="cd-collapsible-header" onclick="toggleCD('mtForm')">
                <span>+ Schedule Meeting</span><span id="mtForm-arrow">▼</span>
            </div>
            <div class="cd-collapsible-body" id="mtForm-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="create_meeting" value="1">
                    <div class="cd-form-grid">
                        <div class="cd-field"><label>Title *</label><input type="text" name="meeting_title" required></div>
                        <div class="cd-field"><label>Date &amp; Time *</label><input type="datetime-local" name="meeting_date" required></div>
                        <div class="cd-field"><label>Location</label><input type="text" name="location" placeholder="Office / Teams / Google Meet…"></div>
                        <div class="cd-field full"><label>Agenda</label><textarea name="agenda"></textarea></div>
                    </div>
                    <div style="margin-top:14px;"><button type="submit" class="cd-btn cd-btn-sky">Schedule</button></div>
                </form>
            </div>
            </div>
            <?php endif; ?>

            <?php if (empty($meetings)): ?>
            <div class="cd-card" style="text-align:center;color:#9ca3af;">No meetings scheduled yet.</div>
            <?php else: ?>
            <div class="cd-table-wrap">
            <table class="cd-table">
                <thead><tr><th>Title</th><th>Date</th><th>Location</th><th>Status</th><th>Notes</th><?php if($can_write):?><th>Actions</th><?php endif;?></tr></thead>
                <tbody>
                <?php foreach ($meetings as $mt): ?>
                <tr>
                    <td style="font-weight:600;"><?php echo Security::escapeHTML($mt['meeting_title']); ?></td>
                    <td style="font-size:.82rem;"><?php echo date('d M Y H:i',strtotime($mt['meeting_date'])); ?></td>
                    <td style="font-size:.82rem;"><?php echo Security::escapeHTML($mt['location']??'—'); ?></td>
                    <td><span class="cbadge cbadge-<?php echo $mt['status']??'scheduled'; ?>"><?php echo ucfirst($mt['status']??'Scheduled'); ?></span></td>
                    <td style="font-size:.78rem;color:#6b7280;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo Security::escapeHTML(substr($mt['notes']??'',0,80)); ?></td>
                    <?php if ($can_write): ?>
                    <td><button class="cd-btn cd-btn-gray" style="font-size:.73rem;" onclick="openMtUpdate(<?php echo htmlspecialchars(json_encode($mt),ENT_QUOTES); ?>)">✏️ Update</button></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══════════ TAB: PROJECTS ══════════ -->
        <div id="tab-projects" class="cd-tab-content">
            <?php if (empty($projects)): ?>
            <div class="cd-card" style="text-align:center;color:#9ca3af;">No projects linked to this client yet.</div>
            <?php else: ?>
            <div class="cd-table-wrap">
            <table class="cd-table">
                <thead><tr><th>Project</th><th>Department</th><th>Priority</th><th>Status</th><th>Progress</th><th>End Date</th></tr></thead>
                <tbody>
                <?php foreach ($projects as $pr): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?php echo Security::escapeHTML($pr['name']); ?></div>
                        <div style="font-size:.75rem;color:#9ca3af;"><?php echo Security::escapeHTML(substr($pr['description']??'',0,60)); ?></div>
                    </td>
                    <td><?php echo Security::escapeHTML($pr['department']??'—'); ?></td>
                    <td><?php
                        $pclr = ['high'=>'#dc2626','medium'=>'#d97706','low'=>'#059669'];
                        $clr  = $pclr[$pr['priority']] ?? '#9ca3af';
                    ?><span style="font-size:.75rem;font-weight:700;color:<?php echo $clr; ?>;text-transform:uppercase;"><?php echo $pr['priority']??'—'; ?></span></td>
                    <td><span class="cbadge cbadge-<?php echo str_replace(' ','_',$pr['status']); ?>"><?php echo ucfirst($pr['status']); ?></span></td>
                    <td style="white-space:nowrap;">
                        <div class="prog-wrap"><div class="prog-fill" style="width:<?php echo min(100,(int)$pr['progress']); ?>%"></div></div>
                        <span style="font-size:.78rem;color:#6b7280;"><?php echo (int)$pr['progress']; ?>%</span>
                    </td>
                    <td style="font-size:.8rem;"><?php echo $pr['end_date'] ? date('d M Y',strtotime($pr['end_date'])) : '—'; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══════════ TAB: FINANCIALS ══════════ -->
        <div id="tab-financials" class="cd-tab-content">
            <!-- Invoices -->
            <div class="cd-card">
                <h3>Invoices (<?php echo count($invoices); ?>)</h3>
                <?php if (empty($invoices)): ?>
                <p style="color:#9ca3af;font-size:.87rem;">No invoices yet.</p>
                <?php else: ?>
                <div class="cd-table-wrap">
                <table class="cd-table">
                    <thead><tr><th>#</th><th>Date</th><th>Due Date</th><th>Amount</th><th>Status</th><th>PDF</th></tr></thead>
                    <tbody>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td style="font-family:monospace;font-size:.8rem;"><?php echo Security::escapeHTML($inv['invoice_number']??'—'); ?></td>
                        <td style="font-size:.82rem;"><?php echo $inv['invoice_date'] ? date('d M Y',strtotime($inv['invoice_date'])) : '—'; ?></td>
                        <td style="font-size:.82rem;"><?php echo $inv['due_date'] ? date('d M Y',strtotime($inv['due_date'])) : '—'; ?></td>
                        <td style="font-weight:700;">R<?php echo number_format($inv['total_amount'],2); ?></td>
                        <td><span class="cbadge cbadge-<?php echo $inv['status']; ?>"><?php echo ucfirst($inv['status']); ?></span></td>
                        <td><a href="finance_pdf.php?type=invoice&id=<?php echo $inv['id']; ?>" target="_blank" class="cd-btn cd-btn-gray" style="font-size:.73rem;">📄 PDF</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quotations -->
            <div class="cd-card">
                <h3>Quotations (<?php echo count($quotes); ?>)</h3>
                <?php if (empty($quotes)): ?>
                <p style="color:#9ca3af;font-size:.87rem;">No quotations yet.</p>
                <?php else: ?>
                <div class="cd-table-wrap">
                <table class="cd-table">
                    <thead><tr><th>#</th><th>Date</th><th>Valid Until</th><th>Amount</th><th>Status</th><th>PDF</th></tr></thead>
                    <tbody>
                    <?php foreach ($quotes as $q): ?>
                    <tr>
                        <td style="font-family:monospace;font-size:.8rem;"><?php echo Security::escapeHTML($q['quotation_number']??'—'); ?></td>
                        <td style="font-size:.82rem;"><?php echo $q['quotation_date'] ? date('d M Y',strtotime($q['quotation_date'])) : '—'; ?></td>
                        <td style="font-size:.82rem;"><?php echo $q['valid_until'] ? date('d M Y',strtotime($q['valid_until'])) : '—'; ?></td>
                        <td style="font-weight:700;">R<?php echo number_format($q['total_amount'],2); ?></td>
                        <td><span class="cbadge cbadge-<?php echo $q['status']; ?>"><?php echo ucfirst($q['status']); ?></span></td>
                        <td><a href="finance_pdf.php?type=quotation&id=<?php echo $q['id']; ?>" target="_blank" class="cd-btn cd-btn-gray" style="font-size:.73rem;">📄 PDF</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.main-content -->

    <!-- Edit Client Modal -->
    <div class="cd-modal-overlay" id="editClientModal">
        <div class="cd-modal">
            <button class="cd-modal-close" onclick="document.getElementById('editClientModal').classList.remove('open')">✕</button>
            <h2>Edit Client</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="update_client" value="1">
                <div class="cd-form-grid">
                    <div class="cd-field"><label>Contact Name *</label><input type="text" name="name" value="<?php echo Security::escapeHTML($client['name']); ?>" required></div>
                    <div class="cd-field"><label>Company *</label><input type="text" name="company" value="<?php echo Security::escapeHTML($client['company']); ?>" required></div>
                    <div class="cd-field"><label>Email</label><input type="email" name="email" value="<?php echo Security::escapeHTML($client['email']??''); ?>"></div>
                    <div class="cd-field"><label>Phone</label><input type="tel" name="phone" value="<?php echo Security::escapeHTML($client['phone']??''); ?>"></div>
                    <div class="cd-field full"><label>Address</label><input type="text" name="address" value="<?php echo Security::escapeHTML($client['address']??''); ?>"></div>
                    <div class="cd-field"><label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo $client['status']==='active'?'selected':''; ?>>Active</option>
                            <option value="prospect" <?php echo $client['status']==='prospect'?'selected':''; ?>>Prospect</option>
                            <option value="inactive" <?php echo $client['status']==='inactive'?'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:20px;display:flex;gap:10px;">
                    <button type="submit" class="cd-btn cd-btn-sky">Save Changes</button>
                    <button type="button" class="cd-btn cd-btn-gray" onclick="document.getElementById('editClientModal').classList.remove('open')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Meeting Modal -->
    <div class="cd-modal-overlay" id="mtUpdateModal">
        <div class="cd-modal">
            <button class="cd-modal-close" onclick="document.getElementById('mtUpdateModal').classList.remove('open')">✕</button>
            <h2>Update Meeting</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="update_meeting" value="1">
                <input type="hidden" name="meeting_id" id="mt_update_id">
                <div class="cd-form-grid">
                    <div class="cd-field full" id="mt_update_title" style="font-size:.85rem;color:#6b7280;"></div>
                    <div class="cd-field"><label>Status</label>
                        <select name="status" id="mt_update_status">
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="cd-field full"><label>Notes / Outcome</label><textarea name="notes" id="mt_update_notes"></textarea></div>
                </div>
                <div style="margin-top:20px;display:flex;gap:10px;">
                    <button type="submit" class="cd-btn cd-btn-sky">Save</button>
                    <button type="button" class="cd-btn cd-btn-gray" onclick="document.getElementById('mtUpdateModal').classList.remove('open')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/notification.js"></script>
    <script>
    function switchTab(name) {
        document.querySelectorAll('.cd-tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.cd-tab').forEach(el => el.classList.remove('active'));
        const c = document.getElementById('tab-'+name);
        if (c) c.classList.add('active');
        document.querySelectorAll('.cd-tab').forEach(b => {
            if (b.textContent.toLowerCase().includes(name.slice(0,4))) b.classList.add('active');
        });
    }
    function toggleCD(id) {
        const body = document.getElementById(id+'-body');
        const arr  = document.getElementById(id+'-arrow');
        if (!body) return;
        const open = body.classList.toggle('open');
        if (arr) arr.textContent = open ? '▲' : '▼';
    }
    function openMtUpdate(mt) {
        document.getElementById('mt_update_id').value    = mt.id;
        document.getElementById('mt_update_notes').value = mt.notes || '';
        document.getElementById('mt_update_title').textContent = mt.meeting_title;
        const s = document.getElementById('mt_update_status');
        for (let i=0;i<s.options.length;i++) if (s.options[i].value===(mt.status||'scheduled')) { s.selectedIndex=i; break; }
        document.getElementById('mtUpdateModal').classList.add('open');
    }
    document.querySelectorAll('.cd-modal-overlay').forEach(o => {
        o.addEventListener('click', e => { if (e.target===o) o.classList.remove('open'); });
    });
    </script>
</body>
</html>

