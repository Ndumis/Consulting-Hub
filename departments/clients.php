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

$can_write = in_array($_SESSION['role'] ?? '', ['admin', 'manager']);
$csrf = Security::generateCSRFToken();

// ── Create client ────────────────────────────────────────────────────────────
if ($_POST && isset($_POST['create_client'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Clients');
    $name    = Security::sanitizeInput($_POST['name']);
    $email   = Security::sanitizeInput($_POST['email']);
    $phone   = Security::sanitizeInput($_POST['phone']);
    $company = Security::sanitizeInput($_POST['company']);
    $address = Security::sanitizeInput($_POST['address']);
    $status  = Security::sanitizeInput($_POST['status']);
    if (!in_array($status, ['active','prospect','inactive'])) $status = 'prospect';
    $stmt = $db->prepare("INSERT INTO clients (name,email,phone,company,address,status) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$name,$email,$phone,$company,$address,$status]);
    $success_message = "Client <strong>".Security::escapeHTML($name)."</strong> created.";
}

// ── Update client ────────────────────────────────────────────────────────────
if ($_POST && isset($_POST['update_client'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Clients');
    $cid    = (int)$_POST['client_id'];
    $name   = Security::sanitizeInput($_POST['name']);
    $email  = Security::sanitizeInput($_POST['email']);
    $phone  = Security::sanitizeInput($_POST['phone']);
    $company= Security::sanitizeInput($_POST['company']);
    $status = Security::sanitizeInput($_POST['status']);
    if (!in_array($status, ['active','prospect','inactive'])) $status = 'prospect';
    $stmt = $db->prepare("UPDATE clients SET name=?,email=?,phone=?,company=?,status=? WHERE id=?");
    $stmt->execute([$name,$email,$phone,$company,$status,$cid]);
    $success_message = "Client updated.";
}

// ── Create contact ───────────────────────────────────────────────────────────
if ($_POST && isset($_POST['create_contact'])) {
    Security::checkCSRFToken();
    $cid        = (int)$_POST['client_id'];
    $name       = Security::sanitizeInput($_POST['contact_name']);
    $email      = Security::sanitizeInput($_POST['contact_email']);
    $phone      = Security::sanitizeInput($_POST['contact_phone']);
    $position   = Security::sanitizeInput($_POST['contact_position']);
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    $stmt = $db->prepare("INSERT INTO client_contacts (client_id,name,email,phone,position,is_primary,assigned_to) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$cid,$name,$email,$phone,$position,$is_primary,$_SESSION['user_id']]);
    $success_message = "Contact added.";
}

// ── Create meeting ───────────────────────────────────────────────────────────
if ($_POST && isset($_POST['create_meeting'])) {
    Security::checkCSRFToken();
    $cid     = (int)$_POST['client_id'];
    $title   = Security::sanitizeInput($_POST['meeting_title']);
    $date    = Security::sanitizeInput($_POST['meeting_date']);
    $loc     = Security::sanitizeInput($_POST['location']);
    $agenda  = Security::sanitizeInput($_POST['agenda']);
    $stmt = $db->prepare("INSERT INTO client_meetings (client_id,meeting_title,meeting_date,location,agenda,created_by,status) VALUES (?,?,?,?,?,?,'scheduled')");
    $stmt->execute([$cid,$title,$date,$loc,$agenda,$_SESSION['user_id']]);
    $success_message = "Meeting scheduled.";
}

// ── Update meeting ───────────────────────────────────────────────────────────
if ($_POST && isset($_POST['update_meeting'])) {
    Security::checkCSRFToken();
    $mid    = (int)$_POST['meeting_id'];
    $notes  = Security::sanitizeInput($_POST['notes']);
    $status = Security::sanitizeInput($_POST['status']);
    if (!in_array($status, ['scheduled','completed','cancelled'])) $status = 'scheduled';
    $stmt = $db->prepare("UPDATE client_meetings SET notes=?,status=? WHERE id=?");
    $stmt->execute([$notes,$status,$mid]);
    $success_message = "Meeting updated.";
}

// ── Fetch data ───────────────────────────────────────────────────────────────
$clients = $db->query("SELECT c.*,
    (SELECT COUNT(*) FROM projects p WHERE p.client_id=c.id) AS project_count,
    (SELECT COALESCE(SUM(i.total_amount),0) FROM invoices i WHERE i.client_id=c.id) AS total_invoiced,
    (SELECT COALESCE(SUM(i.total_amount),0) FROM invoices i WHERE i.client_id=c.id AND i.status NOT IN ('paid')) AS outstanding,
    (SELECT COUNT(*) FROM client_contacts cc WHERE cc.client_id=c.id) AS contact_count
    FROM clients c ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$contacts = $db->query("SELECT cc.*, c.name AS client_name
    FROM client_contacts cc JOIN clients c ON cc.client_id=c.id
    ORDER BY cc.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$meetings = $db->query("SELECT cm.*, c.name AS client_name, u.username AS created_by_name
    FROM client_meetings cm JOIN clients c ON cm.client_id=c.id
    LEFT JOIN users u ON cm.created_by=u.id
    ORDER BY cm.meeting_date DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_clients  = count($clients);
$active_clients = count(array_filter($clients, fn($c)=>$c['status']==='active'));
$prospects      = count(array_filter($clients, fn($c)=>$c['status']==='prospect'));
$total_revenue  = array_sum(array_column($clients, 'total_invoiced'));
$total_outstanding = array_sum(array_column($clients, 'outstanding'));

$asset_base = '../';
$nav_base   = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - KConsulting Hub</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        :root {
            --cl:      #0ea5e9;
            --cl-dk:   #0284c7;
            --cl-navy: #0c4a6e;
            --cl-grad: linear-gradient(135deg, #0c4a6e 0%, #0ea5e9 100%);
        }

        /* Hero */
        .cl-hero {
            background: var(--cl-grad);
            border-radius: 16px; padding: 28px 32px;
            display: flex; align-items: center; gap: 20px;
            margin-bottom: 20px; flex-wrap: wrap;
        }
        .cl-hero-icon { font-size: 2.8rem; }
        .cl-hero-info { flex: 1; min-width: 180px; }
        .cl-hero-info h1 { color: #fff; font-size: 1.6rem; font-weight: 800; margin: 0 0 4px; }
        .cl-hero-info p  { color: rgba(255,255,255,.75); font-size: .87rem; margin: 0; }
        .cl-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-left: auto; }
        .cl-hero-btn {
            background: rgba(255,255,255,.15); color: #fff;
            border: 1px solid rgba(255,255,255,.3); border-radius: 8px;
            padding: 8px 16px; font-size: .83rem; font-weight: 600; cursor: pointer; transition: background .2s;
        }
        .cl-hero-btn:hover { background: rgba(255,255,255,.28); }

        /* Stats */
        .cl-stats { display: grid; grid-template-columns: repeat(5,1fr); gap: 12px; margin-bottom: 20px; }
        @media(max-width:900px){ .cl-stats{ grid-template-columns: repeat(3,1fr); } }
        @media(max-width:580px){ .cl-stats{ grid-template-columns: 1fr 1fr; } }
        .cl-stat { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 18px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
        .cl-stat .num { font-size: 1.75rem; font-weight: 800; color: #111827; display: block; }
        .cl-stat .lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; font-weight: 600; }
        .cl-stat .num.sky   { color: var(--cl); }
        .cl-stat .num.green { color: #059669; }
        .cl-stat .num.amber { color: #d97706; }
        .cl-stat .num.rose  { color: #e11d48; }
        .cl-stat .num.slate { color: #475569; }

        /* Flash */
        .cl-flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: .87rem; font-weight: 500; }
        .cl-flash.success { background: #e0f2fe; color: #0c4a6e; border-left: 4px solid var(--cl); }
        .cl-flash.error   { background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; }

        /* Tab nav */
        .cl-tabs { display: flex; gap: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 4px; margin-bottom: 20px; overflow-x: auto; }
        .cl-tab  { flex: none; padding: 9px 20px; border: none; background: transparent; border-radius: 7px; cursor: pointer; font-size: .87rem; font-weight: 600; color: #6b7280; transition: all .2s; white-space: nowrap; }
        .cl-tab:hover  { background: #f3f4f6; color: #111827; }
        .cl-tab.active { background: var(--cl); color: #fff; }
        .cl-tab-content { display: none; }
        .cl-tab-content.active { display: block; }

        /* Controls */
        .cl-controls { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
        .cl-search  { flex: 1; min-width: 180px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .87rem; }
        .cl-search:focus { outline: none; border-color: var(--cl); box-shadow: 0 0 0 3px rgba(14,165,233,.1); }
        .cl-filter  { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .85rem; background: #fff; }
        .cl-count   { font-size: .8rem; color: #9ca3af; margin-left: auto; }

        /* Table */
        .cl-table-wrap { overflow-x: auto; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
        .cl-table { width: 100%; border-collapse: collapse; background: #fff; font-size: .86rem; }
        .cl-table th { background: var(--cl); color: #fff; padding: 11px 14px; text-align: left; font-size: .75rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; white-space: nowrap; }
        .cl-table td { padding: 11px 14px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }
        .cl-table tr:last-child td { border-bottom: none; }
        .cl-table tr:hover td { background: #f0f9ff; }
        .cl-no-results { text-align: center; padding: 32px; color: #9ca3af; font-size: .88rem; display: none; }

        /* Client avatar */
        .cl-avatar { width: 36px; height: 36px; border-radius: 10px; background: var(--cl-grad); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: .78rem; font-weight: 700; flex-shrink: 0; }
        .cl-name-cell { display: flex; align-items: center; gap: 10px; }

        /* Badges */
        .cbadge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700; text-transform: capitalize; }
        .cbadge-active   { background: #e0f2fe; color: #0c4a6e; }
        .cbadge-prospect { background: #fef9c3; color: #713f12; }
        .cbadge-inactive { background: #f3f4f6; color: #6b7280; }
        .cbadge-scheduled{ background: #dbeafe; color: #1e40af; }
        .cbadge-completed{ background: #d1fae5; color: #065f46; }
        .cbadge-cancelled{ background: #f3f4f6; color: #6b7280; }
        .cbadge-primary  { background: #e0f2fe; color: #0c4a6e; }

        /* Buttons */
        .cl-btn { padding: 6px 14px; border: none; border-radius: 7px; font-size: .8rem; font-weight: 600; cursor: pointer; transition: all .2s; text-decoration: none; display: inline-block; }
        .cl-btn-sky   { background: var(--cl); color: #fff; }
        .cl-btn-sky:hover   { background: var(--cl-dk); }
        .cl-btn-gray  { background: #f3f4f6; color: #374151; }
        .cl-btn-gray:hover  { background: #e5e7eb; }
        .cl-btn-green { background: #059669; color: #fff; }
        .cl-btn-green:hover { background: #047857; }

        /* Collapsible */
        .cl-collapsible { margin-bottom: 16px; }
        .cl-collapsible-header { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 12px 16px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; font-weight: 600; color: var(--cl-navy); font-size: .9rem; }
        .cl-collapsible-body { display: none; padding: 18px; border: 1px solid #bae6fd; border-top: none; border-radius: 0 0 10px 10px; background: #fff; }
        .cl-collapsible-body.open { display: block; }

        /* Form grid */
        .cl-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .cl-form-grid .full { grid-column: 1/-1; }
        @media(max-width:600px){ .cl-form-grid{ grid-template-columns: 1fr; } }
        .cl-field label { display: block; font-size: .78rem; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .cl-field input, .cl-field select, .cl-field textarea { width: 100%; padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 7px; font-size: .87rem; color: #111827; }
        .cl-field textarea { height: 70px; resize: vertical; }
        .cl-field input:focus, .cl-field select:focus, .cl-field textarea:focus { outline: none; border-color: var(--cl); box-shadow: 0 0 0 3px rgba(14,165,233,.1); }

        /* Modal */
        .cl-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000; align-items: center; justify-content: center; }
        .cl-modal-overlay.open { display: flex; }
        .cl-modal { background: #fff; border-radius: 16px; width: min(560px,95vw); max-height: 90vh; overflow-y: auto; padding: 28px; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,.25); }
        .cl-modal h2 { font-size: 1.1rem; font-weight: 700; color: #111827; margin: 0 0 20px; }
        .cl-modal-close { position: absolute; top: 16px; right: 16px; background: #f3f4f6; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; }
        .cl-modal-close:hover { background: #e5e7eb; }

        /* Revenue pill */
        .rev-pill { font-size: .78rem; font-weight: 700; color: #059669; background: #d1fae5; padding: 2px 8px; border-radius: 20px; }
        .outstanding-pill { font-size: .78rem; font-weight: 700; color: #d97706; background: #fef3c7; padding: 2px 8px; border-radius: 20px; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">

        <?php if (!empty($success_message)): ?>
        <div class="cl-flash success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
        <div class="cl-flash error"><?php echo Security::escapeHTML($error_message); ?></div>
        <?php endif; ?>

        <!-- Hero -->
        <div class="cl-hero">
            <div class="cl-hero-icon">🏢</div>
            <div class="cl-hero-info">
                <h1>Clients</h1>
                <p>Manage client relationships, contacts, and meetings</p>
            </div>
            <?php if ($can_write): ?>
            <div class="cl-hero-actions">
                <button class="cl-hero-btn" onclick="openCollapsible('addClient')">+ Add Client</button>
                <button class="cl-hero-btn" onclick="switchTab('meetings');openCollapsible('addMeeting')">+ Schedule Meeting</button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stats -->
        <div class="cl-stats">
            <div class="cl-stat"><span class="num sky"><?php echo $total_clients; ?></span><span class="lbl">Total Clients</span></div>
            <div class="cl-stat"><span class="num green"><?php echo $active_clients; ?></span><span class="lbl">Active</span></div>
            <div class="cl-stat"><span class="num amber"><?php echo $prospects; ?></span><span class="lbl">Prospects</span></div>
            <div class="cl-stat"><span class="num slate">R<?php echo number_format($total_revenue,0); ?></span><span class="lbl">Total Invoiced</span></div>
            <div class="cl-stat"><span class="num rose">R<?php echo number_format($total_outstanding,0); ?></span><span class="lbl">Outstanding</span></div>
        </div>

        <!-- Tab nav -->
        <div class="cl-tabs">
            <button class="cl-tab active" onclick="switchTab('clients')">🏢 Clients (<?php echo $total_clients; ?>)</button>
            <button class="cl-tab" onclick="switchTab('contacts')">👤 Contacts (<?php echo count($contacts); ?>)</button>
            <button class="cl-tab" onclick="switchTab('meetings')">📅 Meetings (<?php echo count($meetings); ?>)</button>
        </div>

        <!-- ══════════ TAB: CLIENTS ══════════ -->
        <div id="tab-clients" class="cl-tab-content active">
            <?php if ($can_write): ?>
            <div class="cl-collapsible" id="addClient-wrap">
                <div class="cl-collapsible-header" onclick="toggleCollapsible('addClient')">
                    <span>+ Add New Client</span><span id="addClient-arrow">▼</span>
                </div>
                <div class="cl-collapsible-body" id="addClient-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="create_client" value="1">
                        <div class="cl-form-grid">
                            <div class="cl-field"><label>Contact Name *</label><input type="text" name="name" required></div>
                            <div class="cl-field"><label>Company *</label><input type="text" name="company" required></div>
                            <div class="cl-field"><label>Email</label><input type="email" name="email"></div>
                            <div class="cl-field"><label>Phone</label><input type="tel" name="phone"></div>
                            <div class="cl-field"><label>Status</label>
                                <select name="status">
                                    <option value="prospect">Prospect</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="cl-field"><label>Address</label><input type="text" name="address"></div>
                        </div>
                        <div style="margin-top:14px;"><button type="submit" class="cl-btn cl-btn-sky">+ Create Client</button></div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="cl-controls">
                <input class="cl-search" type="text" id="cl-search" placeholder="Search clients…" oninput="filterCL('cl-tbody','cl-search','','cl-status-filter','cl-count')">
                <select class="cl-filter" id="cl-status-filter" onchange="filterCL('cl-tbody','cl-search','','cl-status-filter','cl-count')">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="prospect">Prospect</option>
                    <option value="inactive">Inactive</option>
                </select>
                <span class="cl-count" id="cl-count"><?php echo $total_clients; ?> clients</span>
            </div>

            <div class="cl-table-wrap">
            <table class="cl-table">
                <thead><tr>
                    <th>Client</th><th>Company</th><th>Contact</th>
                    <th>Projects</th><th>Invoiced</th><th>Outstanding</th>
                    <th>Status</th><th>Actions</th>
                </tr></thead>
                <tbody id="cl-tbody">
                <?php foreach ($clients as $cl):
                    $initials = strtoupper(substr($cl['company'],0,1).substr($cl['name'],0,1));
                    $search_str = strtolower($cl['name'].' '.$cl['company'].' '.$cl['email'].' '.$cl['phone']);
                ?>
                <tr data-search="<?php echo htmlspecialchars($search_str,ENT_QUOTES); ?>"
                    data-status="<?php echo $cl['status']; ?>">
                    <td>
                        <div class="cl-name-cell">
                            <div class="cl-avatar"><?php echo $initials; ?></div>
                            <div>
                                <div style="font-weight:600;color:#111827;"><?php echo Security::escapeHTML($cl['name']); ?></div>
                                <div style="font-size:.75rem;color:#9ca3af;"><?php echo Security::escapeHTML($cl['email']??''); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo Security::escapeHTML($cl['company']); ?></td>
                    <td style="font-size:.8rem;"><?php echo Security::escapeHTML($cl['phone']??'—'); ?></td>
                    <td><?php echo (int)$cl['project_count']; ?></td>
                    <td><span class="rev-pill">R<?php echo number_format($cl['total_invoiced'],0); ?></span></td>
                    <td>
                        <?php if ($cl['outstanding'] > 0): ?>
                        <span class="outstanding-pill">R<?php echo number_format($cl['outstanding'],0); ?></span>
                        <?php else: ?>
                        <span style="color:#9ca3af;font-size:.78rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="cbadge cbadge-<?php echo $cl['status']; ?>"><?php echo ucfirst($cl['status']); ?></span></td>
                    <td style="white-space:nowrap;">
                        <a href="client_detail.php?id=<?php echo $cl['id']; ?>" class="cl-btn cl-btn-sky" style="font-size:.75rem;">View</a>
                        <?php if ($can_write): ?>
                        <button class="cl-btn cl-btn-gray" style="font-size:.75rem;" onclick="openEditClient(<?php echo htmlspecialchars(json_encode($cl),ENT_QUOTES); ?>)">✏️</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="cl-no-results" id="cl-tbody-no-results">No clients match your search.</div>
            </div>
        </div>

        <!-- ══════════ TAB: CONTACTS ══════════ -->
        <div id="tab-contacts" class="cl-tab-content">
            <?php if ($can_write): ?>
            <div class="cl-collapsible">
                <div class="cl-collapsible-header" onclick="toggleCollapsible('addContact')">
                    <span>+ Add Contact</span><span id="addContact-arrow">▼</span>
                </div>
                <div class="cl-collapsible-body" id="addContact-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="create_contact" value="1">
                        <div class="cl-form-grid">
                            <div class="cl-field full"><label>Client *</label>
                                <select name="client_id" required>
                                    <option value="">Select client…</option>
                                    <?php foreach ($clients as $cl): ?>
                                    <option value="<?php echo $cl['id']; ?>"><?php echo Security::escapeHTML($cl['company']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="cl-field"><label>Full Name *</label><input type="text" name="contact_name" required></div>
                            <div class="cl-field"><label>Position</label><input type="text" name="contact_position" placeholder="e.g. CEO, Procurement Manager"></div>
                            <div class="cl-field"><label>Email</label><input type="email" name="contact_email"></div>
                            <div class="cl-field"><label>Phone</label><input type="tel" name="contact_phone"></div>
                            <div class="cl-field" style="display:flex;align-items:center;gap:8px;margin-top:20px;">
                                <input type="checkbox" name="is_primary" id="is_primary" style="width:auto;">
                                <label for="is_primary" style="margin:0;font-size:.87rem;">Primary contact</label>
                            </div>
                        </div>
                        <div style="margin-top:14px;"><button type="submit" class="cl-btn cl-btn-sky">Add Contact</button></div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="cl-controls">
                <input class="cl-search" type="text" id="ct-search" placeholder="Search contacts…" oninput="filterCL('ct-tbody','ct-search','','','ct-count')">
                <span class="cl-count" id="ct-count"><?php echo count($contacts); ?> contacts</span>
            </div>

            <div class="cl-table-wrap">
            <table class="cl-table">
                <thead><tr><th>Name</th><th>Client</th><th>Position</th><th>Email</th><th>Phone</th><th>Primary</th></tr></thead>
                <tbody id="ct-tbody">
                <?php foreach ($contacts as $ct):
                    $search_ct = strtolower($ct['name'].' '.$ct['client_name'].' '.$ct['position'].' '.$ct['email']);
                ?>
                <tr data-search="<?php echo htmlspecialchars($search_ct,ENT_QUOTES); ?>" data-status="" data-dept="">
                    <td style="font-weight:500;"><?php echo Security::escapeHTML($ct['name']); ?></td>
                    <td><?php echo Security::escapeHTML($ct['client_name']); ?></td>
                    <td style="font-size:.82rem;"><?php echo Security::escapeHTML($ct['position']??'—'); ?></td>
                    <td style="font-size:.82rem;"><?php echo Security::escapeHTML($ct['email']??'—'); ?></td>
                    <td style="font-size:.82rem;"><?php echo Security::escapeHTML($ct['phone']??'—'); ?></td>
                    <td><?php if ($ct['is_primary']): ?><span class="cbadge cbadge-primary">Primary</span><?php else: ?><span style="color:#d1d5db;">—</span><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($contacts)): ?>
            <div style="text-align:center;padding:32px;color:#9ca3af;font-size:.88rem;">No contacts yet. Add the first one above.</div>
            <?php endif; ?>
            <div class="cl-no-results" id="ct-tbody-no-results">No contacts match your search.</div>
            </div>
        </div>

        <!-- ══════════ TAB: MEETINGS ══════════ -->
        <div id="tab-meetings" class="cl-tab-content">
            <?php if ($can_write): ?>
            <div class="cl-collapsible">
                <div class="cl-collapsible-header" onclick="toggleCollapsible('addMeeting')">
                    <span>+ Schedule Meeting</span><span id="addMeeting-arrow">▼</span>
                </div>
                <div class="cl-collapsible-body" id="addMeeting-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="create_meeting" value="1">
                        <div class="cl-form-grid">
                            <div class="cl-field"><label>Client *</label>
                                <select name="client_id" required>
                                    <option value="">Select client…</option>
                                    <?php foreach ($clients as $cl): ?>
                                    <option value="<?php echo $cl['id']; ?>"><?php echo Security::escapeHTML($cl['company']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="cl-field"><label>Meeting Title *</label><input type="text" name="meeting_title" required></div>
                            <div class="cl-field"><label>Date &amp; Time *</label><input type="datetime-local" name="meeting_date" required></div>
                            <div class="cl-field"><label>Location</label><input type="text" name="location" placeholder="Office, Teams, Google Meet…"></div>
                            <div class="cl-field full"><label>Agenda</label><textarea name="agenda" placeholder="Topics to discuss…"></textarea></div>
                        </div>
                        <div style="margin-top:14px;"><button type="submit" class="cl-btn cl-btn-sky">Schedule Meeting</button></div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="cl-controls">
                <input class="cl-search" type="text" id="mt-search" placeholder="Search meetings…" oninput="filterCL('mt-tbody','mt-search','','mt-status-filter','mt-count')">
                <select class="cl-filter" id="mt-status-filter" onchange="filterCL('mt-tbody','mt-search','','mt-status-filter','mt-count')">
                    <option value="">All Statuses</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <span class="cl-count" id="mt-count"><?php echo count($meetings); ?> meetings</span>
            </div>

            <div class="cl-table-wrap">
            <table class="cl-table">
                <thead><tr><th>Meeting</th><th>Client</th><th>Date</th><th>Location</th><th>Status</th><th>Notes</th><?php if($can_write):?><th>Actions</th><?php endif;?></tr></thead>
                <tbody id="mt-tbody">
                <?php foreach ($meetings as $mt):
                    $search_mt = strtolower($mt['meeting_title'].' '.$mt['client_name'].' '.$mt['location']);
                ?>
                <tr data-search="<?php echo htmlspecialchars($search_mt,ENT_QUOTES); ?>" data-dept="" data-status="<?php echo $mt['status']??'scheduled'; ?>">
                    <td style="font-weight:600;"><?php echo Security::escapeHTML($mt['meeting_title']); ?></td>
                    <td><?php echo Security::escapeHTML($mt['client_name']); ?></td>
                    <td style="font-size:.82rem;"><?php echo date('d M Y H:i',strtotime($mt['meeting_date'])); ?></td>
                    <td style="font-size:.82rem;"><?php echo Security::escapeHTML($mt['location']??'—'); ?></td>
                    <td><span class="cbadge cbadge-<?php echo $mt['status']??'scheduled'; ?>"><?php echo ucfirst($mt['status']??'Scheduled'); ?></span></td>
                    <td style="font-size:.78rem;color:#6b7280;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?php echo Security::escapeHTML(substr($mt['notes']??'',0,60)); ?>
                    </td>
                    <?php if ($can_write): ?>
                    <td>
                        <button class="cl-btn cl-btn-gray" style="font-size:.73rem;" onclick="openUpdateMeeting(<?php echo htmlspecialchars(json_encode($mt),ENT_QUOTES); ?>)">✏️ Update</button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($meetings)): ?>
            <div style="text-align:center;padding:32px;color:#9ca3af;font-size:.88rem;">No meetings scheduled yet.</div>
            <?php endif; ?>
            <div class="cl-no-results" id="mt-tbody-no-results">No meetings match your filter.</div>
            </div>
        </div>

    </div><!-- /.main-content -->

    <!-- Edit Client Modal -->
    <div class="cl-modal-overlay" id="editClientModal">
        <div class="cl-modal">
            <button class="cl-modal-close" onclick="closeModal('editClientModal')">✕</button>
            <h2>Edit Client</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="update_client" value="1">
                <input type="hidden" name="client_id" id="edit_cl_id">
                <div class="cl-form-grid">
                    <div class="cl-field"><label>Contact Name *</label><input type="text" name="name" id="edit_cl_name" required></div>
                    <div class="cl-field"><label>Company *</label><input type="text" name="company" id="edit_cl_company" required></div>
                    <div class="cl-field"><label>Email</label><input type="email" name="email" id="edit_cl_email"></div>
                    <div class="cl-field"><label>Phone</label><input type="tel" name="phone" id="edit_cl_phone"></div>
                    <div class="cl-field full"><label>Status</label>
                        <select name="status" id="edit_cl_status">
                            <option value="active">Active</option>
                            <option value="prospect">Prospect</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:20px;display:flex;gap:10px;">
                    <button type="submit" class="cl-btn cl-btn-sky">Save</button>
                    <button type="button" class="cl-btn cl-btn-gray" onclick="closeModal('editClientModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Meeting Modal -->
    <div class="cl-modal-overlay" id="updateMeetingModal">
        <div class="cl-modal">
            <button class="cl-modal-close" onclick="closeModal('updateMeetingModal')">✕</button>
            <h2>Update Meeting</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="update_meeting" value="1">
                <input type="hidden" name="meeting_id" id="edit_mt_id">
                <div class="cl-form-grid">
                    <div class="cl-field full" id="edit_mt_title_wrap" style="color:#6b7280;font-size:.85rem;"></div>
                    <div class="cl-field"><label>Status</label>
                        <select name="status" id="edit_mt_status">
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="cl-field full"><label>Notes / Outcome</label><textarea name="notes" id="edit_mt_notes" placeholder="Meeting outcomes, action items…"></textarea></div>
                </div>
                <div style="margin-top:20px;display:flex;gap:10px;">
                    <button type="submit" class="cl-btn cl-btn-sky">Save</button>
                    <button type="button" class="cl-btn cl-btn-gray" onclick="closeModal('updateMeetingModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/notification.js"></script>
    <script>
    function switchTab(name) {
        document.querySelectorAll('.cl-tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.cl-tab').forEach(el => el.classList.remove('active'));
        const content = document.getElementById('tab-' + name);
        if (content) content.classList.add('active');
        document.querySelectorAll('.cl-tab').forEach(btn => {
            if (btn.textContent.toLowerCase().includes(name.slice(0,4))) btn.classList.add('active');
        });
    }

    function toggleCollapsible(id) {
        const body = document.getElementById(id + '-body');
        const arrow = document.getElementById(id + '-arrow');
        if (!body) return;
        const open = body.classList.toggle('open');
        if (arrow) arrow.textContent = open ? '▲' : '▼';
    }
    function openCollapsible(id) {
        const body = document.getElementById(id + '-body');
        if (body && !body.classList.contains('open')) toggleCollapsible(id);
        body && body.scrollIntoView({behavior:'smooth',block:'start'});
    }

    function filterCL(tbodyId, searchId, f1Id, f2Id, countId) {
        const q     = (document.getElementById(searchId)?.value||'').toLowerCase().trim();
        const f2    = (document.getElementById(f2Id)?.value||'').toLowerCase();
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        let visible = 0;
        tbody.querySelectorAll('tr').forEach(row => {
            const txt  = (row.dataset.search||'').toLowerCase();
            const stat = (row.dataset.status||'').toLowerCase();
            const show = (!q||txt.includes(q)) && (!f2||stat===f2);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const noRes = document.getElementById(tbodyId + '-no-results');
        if (noRes) noRes.style.display = visible ? 'none' : 'block';
        const cnt = document.getElementById(countId);
        if (cnt) cnt.textContent = visible + (tbodyId.includes('ct')?'contacts':tbodyId.includes('mt')?'meetings':' clients').replace(/^\d+/,'');
        if (cnt) cnt.textContent = visible + ' ' + (tbodyId.includes('ct')?'contacts':tbodyId.includes('mt')?'meetings':'clients');
    }

    function closeModal(id) { document.getElementById(id).classList.remove('open'); }
    document.querySelectorAll('.cl-modal-overlay').forEach(o => {
        o.addEventListener('click', e => { if (e.target===o) o.classList.remove('open'); });
    });

    function openEditClient(cl) {
        document.getElementById('edit_cl_id').value      = cl.id;
        document.getElementById('edit_cl_name').value    = cl.name    || '';
        document.getElementById('edit_cl_company').value = cl.company || '';
        document.getElementById('edit_cl_email').value   = cl.email   || '';
        document.getElementById('edit_cl_phone').value   = cl.phone   || '';
        setSelVal('edit_cl_status', cl.status);
        document.getElementById('editClientModal').classList.add('open');
    }

    function openUpdateMeeting(mt) {
        document.getElementById('edit_mt_id').value    = mt.id;
        document.getElementById('edit_mt_notes').value = mt.notes || '';
        document.getElementById('edit_mt_title_wrap').textContent = mt.meeting_title + ' — ' + mt.client_name;
        setSelVal('edit_mt_status', mt.status || 'scheduled');
        document.getElementById('updateMeetingModal').classList.add('open');
    }

    function setSelVal(id, val) {
        const s = document.getElementById(id);
        if (!s||!val) return;
        for (let i=0;i<s.options.length;i++) if (s.options[i].value===val) { s.selectedIndex=i; return; }
    }
    </script>
</body>
</html>
