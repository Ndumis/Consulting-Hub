<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$role       = $_SESSION['role'];
$user_id    = $_SESSION['user_id'];
$department = $_SESSION['department'];

// ── POST HANDLERS ────────────────────────────────────────────────────────────

// Create project
if ($_POST && isset($_POST['create_project'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin', 'manager'])) { http_response_code(403); die(); }

    $name        = Security::sanitizeInput($_POST['name']);
    $description = Security::sanitizeInput($_POST['description'] ?? '');
    $client_id   = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null;
    $dept        = Security::sanitizeInput($_POST['project_department'] ?? $department ?? '');
    $category    = Security::sanitizeInput($_POST['category']);
    $priority    = Security::sanitizeInput($_POST['priority']);
    $start_date  = $_POST['start_date'] ?: null;
    $end_date    = $_POST['end_date']   ?: null;
    $assigned    = $_POST['assigned_employees'] ?? [];

    $s = $db->prepare("INSERT INTO projects (name, description, client_id, department, created_by, category, priority, start_date, end_date) VALUES (?,?,?,?,?,?,?,?,?)");
    $s->execute([$name, $description, $client_id, $dept, $user_id, $category, $priority, $start_date, $end_date]);
    $pid = (int)$db->lastInsertId();

    foreach ($assigned as $uid) {
        if (!empty($uid)) {
            $db->prepare("INSERT INTO project_assignments (project_id, user_id) VALUES (?,?)")->execute([$pid, (int)$uid]);
        }
    }

    try { require_once '../includes/ActivityLogger.php'; (new ActivityLogger($db))->logCreate('project', $pid, "Created project: $name"); } catch (Exception $e) {}
    header("Location: projects.php?created=1"); exit();
}

// Update progress/status (quick update)
if ($_POST && isset($_POST['update_project'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin', 'manager'])) { http_response_code(403); die(); }
    $pid      = (int)$_POST['project_id'];
    $progress = min(100, max(0, (int)$_POST['progress']));
    $status   = Security::sanitizeInput($_POST['status']);
    $db->prepare("UPDATE projects SET progress=?, status=? WHERE id=?")->execute([$progress, $status, $pid]);
    header("Location: projects.php?updated=1"); exit();
}

// Delete project (admin only)
if ($_POST && isset($_POST['delete_project'])) {
    Security::checkCSRFToken();
    if ($role !== 'admin') { http_response_code(403); die(); }
    $pid = (int)$_POST['project_id'];
    $db->prepare("DELETE FROM project_assignments WHERE project_id=?")->execute([$pid]);
    $db->prepare("DELETE FROM project_comments WHERE project_id=?")->execute([$pid]);
    $db->prepare("DELETE FROM projects WHERE id=?")->execute([$pid]);
    header("Location: projects.php?deleted=1"); exit();
}

// ── FILTERS & SORT ───────────────────────────────────────────────────────────

$filter_status   = $_GET['filter_status']   ?? '';
$filter_priority = $_GET['filter_priority'] ?? '';
$filter_dept     = $_GET['filter_dept']     ?? '';
$filter_assigned = $_GET['filter_assigned'] ?? '';
$search          = trim($_GET['search']     ?? '');
$sort_by         = $_GET['sort_by']         ?? 'created_at';
$view_mode       = $_GET['view_mode']       ?? 'grid';

$allowed_sort = ['name', 'created_at', 'end_date', 'priority', 'progress', 'status'];
if (!in_array($sort_by, $allowed_sort)) $sort_by = 'created_at';

$where = ['1=1'];
$params = [];

if ($filter_status)   { $where[] = "p.status = ?";     $params[] = $filter_status; }
if ($filter_priority) { $where[] = "p.priority = ?";   $params[] = $filter_priority; }
if ($filter_dept)     { $where[] = "p.department = ?"; $params[] = $filter_dept; }
if ($filter_assigned) { $where[] = "EXISTS (SELECT 1 FROM project_assignments pa2 WHERE pa2.project_id=p.id AND pa2.user_id=?)"; $params[] = (int)$filter_assigned; }
if ($search)          { $where[] = "(p.name LIKE ? OR c.name LIKE ? OR p.category LIKE ?)"; $like = "%$search%"; $params = array_merge($params, [$like, $like, $like]); }

$sort_map = ['priority' => "FIELD(p.priority,'urgent','high','medium','low')", 'name' => 'p.name', 'end_date' => 'p.end_date IS NULL, p.end_date', 'progress' => 'p.progress DESC', 'status' => 'p.status', 'created_at' => 'p.created_at DESC'];
$order_sql = $sort_map[$sort_by];

$where_sql = implode(' AND ', $where);
$query = "SELECT p.*, c.name as client_name,
          u.username as created_by_name,
          COUNT(DISTINCT pc.id) as comment_count,
          COUNT(DISTINCT CASE WHEN pc.is_blocker=1 THEN pc.id END) as blocker_count,
          COUNT(DISTINCT pa.user_id) as team_count
          FROM projects p
          LEFT JOIN clients c ON p.client_id = c.id
          LEFT JOIN users u ON p.created_by = u.id
          LEFT JOIN project_comments pc ON p.id = pc.project_id
          LEFT JOIN project_assignments pa ON p.id = pa.project_id
          WHERE $where_sql
          GROUP BY p.id
          ORDER BY $order_sql";

$stmt = $db->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Team members per project
$assignments = [];
if (!empty($projects)) {
    $ids = array_column($projects, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $s   = $db->prepare("SELECT pa.project_id, u.id as user_id, u.username FROM project_assignments pa JOIN users u ON pa.user_id=u.id WHERE pa.project_id IN ($ph) ORDER BY u.username");
    $s->execute($ids);
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $a) {
        $assignments[$a['project_id']][] = $a;
    }
}

// Dropdowns
$clients   = $db->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$all_users = $db->query("SELECT id, username, department FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
$all_depts = ['IT','Marketing','Business Development','Finance','HR','Clients'];

// Stats
$total     = count($projects);
$active    = count(array_filter($projects, fn($p) => $p['status'] === 'in_progress'));
$done      = count(array_filter($projects, fn($p) => $p['status'] === 'completed'));
$overdue   = count(array_filter($projects, fn($p) => $p['end_date'] && strtotime($p['end_date']) < time() && $p['status'] !== 'completed'));
$blockers  = array_sum(array_column($projects, 'blocker_count'));
$avg_prog  = $total > 0 ? round(array_sum(array_column($projects, 'progress')) / $total) : 0;

$view = $_GET['view'] ?? 'list';
$msg  = $_GET['created'] ?? ($_GET['updated'] ?? ($_GET['deleted'] ?? ''));

// Avatar color palette
$avatar_colors = ['#6366f1','#0ea5e9','#8b5cf6','#f59e0b','#22c55e','#ec4899','#ef4444','#14b8a6'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - KConsulting Hub</title>
    <link rel="icon" type="image/png" href="../img/KConsultingLogo1.png">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        /* ── PAGE CHROME ── */
        .page-hero { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem; }
        .page-hero-left h2 { font-size:1.5rem; font-weight:700; color:#111827; margin:0 0 .2rem; }
        .page-hero-left p  { margin:0; color:#6b7280; font-size:.875rem; }

        /* ── STATS ── */
        .pstats { display:grid; grid-template-columns:repeat(6,1fr); gap:1rem; margin-bottom:1.5rem; }
        @media(max-width:900px){ .pstats{ grid-template-columns:repeat(3,1fr); } }
        @media(max-width:500px){ .pstats{ grid-template-columns:repeat(2,1fr); } }
        .pstat  { background:#fff; border-radius:10px; padding:1rem 1.25rem; box-shadow:0 1px 4px rgba(0,0,0,.06); text-align:center; }
        .pstat .n { font-size:1.6rem; font-weight:700; color:#111827; line-height:1; }
        .pstat .l { font-size:.75rem; color:#6b7280; margin-top:.25rem; }

        /* ── CONTROLS ── */
        .controls-bar { display:flex; gap:.75rem; align-items:center; background:#fff; border-radius:10px; padding:.75rem 1rem; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:1.25rem; flex-wrap:wrap; }
        .controls-bar input[type=text] { flex:1; min-width:200px; padding:.5rem .85rem; border:1px solid #e5e7eb; border-radius:8px; font-size:.875rem; outline:none; }
        .controls-bar input[type=text]:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
        .controls-bar select { padding:.5rem .75rem; border:1px solid #e5e7eb; border-radius:8px; font-size:.875rem; color:#374151; background:#fafafa; cursor:pointer; }
        .controls-bar select:focus { outline:none; border-color:#6366f1; }
        .view-btns { display:flex; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; }
        .view-btn  { padding:.45rem .75rem; background:#fff; border:none; cursor:pointer; font-size:1rem; color:#9ca3af; transition:all .2s; }
        .view-btn.active { background:#6366f1; color:#fff; }
        .controls-divider { width:1px; height:28px; background:#e5e7eb; flex-shrink:0; }
        .filter-tag { display:inline-flex; align-items:center; gap:.35rem; background:#ede9fe; color:#5b21b6; border-radius:20px; padding:.25rem .7rem; font-size:.78rem; font-weight:600; }
        .filter-tag button { background:none; border:none; cursor:pointer; color:#7c3aed; line-height:1; padding:0; font-size:.9rem; }

        /* ── GRID CARDS ── */
        .project-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:1.25rem; }
        .project-card {
            background:#fff; border-radius:12px; padding:1.25rem 1.25rem 1rem;
            box-shadow:0 2px 6px rgba(0,0,0,.07); border:1px solid #f3f4f6;
            border-left:4px solid #e5e7eb; transition:transform .2s, box-shadow .2s;
            display:flex; flex-direction:column; gap:.85rem;
        }
        .project-card:hover { transform:translateY(-3px); box-shadow:0 6px 18px rgba(0,0,0,.1); }
        .project-card[data-priority="urgent"] { border-left-color:#dc2626; }
        .project-card[data-priority="high"]   { border-left-color:#ef4444; }
        .project-card[data-priority="medium"] { border-left-color:#f59e0b; }
        .project-card[data-priority="low"]    { border-left-color:#22c55e; }

        .card-top    { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; }
        .card-title  { font-size:1rem; font-weight:700; color:#111827; line-height:1.35; text-decoration:none; flex:1; }
        .card-title:hover { color:#6366f1; }
        .card-badges { display:flex; flex-direction:column; gap:.35rem; align-items:flex-end; flex-shrink:0; }

        .badge { display:inline-flex; align-items:center; padding:.2rem .6rem; border-radius:20px; font-size:.72rem; font-weight:700; letter-spacing:.3px; white-space:nowrap; }
        .b-pending    { background:#fef9c3; color:#854d0e; }
        .b-in_progress{ background:#dbeafe; color:#1e40af; }
        .b-completed  { background:#dcfce7; color:#14532d; }
        .b-on_hold    { background:#fee2e2; color:#991b1b; }
        .b-low        { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
        .b-medium     { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }
        .b-high       { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .b-urgent     { background:#dc2626; color:#fff; }
        .b-dept       { background:#f3f4f6; color:#6b7280; }

        .card-meta    { display:grid; grid-template-columns:1fr 1fr; gap:.4rem .75rem; font-size:.8rem; }
        .meta-item    { color:#374151; }
        .meta-label   { color:#9ca3af; font-size:.72rem; display:block; margin-bottom:.1rem; }

        /* Progress bar */
        .prog-wrap { }
        .prog-info { display:flex; justify-content:space-between; font-size:.8rem; margin-bottom:.35rem; }
        .prog-bar  { height:7px; background:#f3f4f6; border-radius:10px; overflow:hidden; }
        .prog-fill { height:100%; border-radius:10px; transition:width .5s; }

        /* Due date chip */
        .due-chip { font-size:.75rem; font-weight:600; padding:.15rem .5rem; border-radius:6px; }
        .due-overdue { background:#fee2e2; color:#dc2626; }
        .due-soon    { background:#fef3c7; color:#b45309; }
        .due-ok      { background:#f3f4f6; color:#6b7280; }
        .due-done    { background:#dcfce7; color:#166534; }

        /* Team avatars */
        .team-row  { display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; }
        .team-av   { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.65rem; font-weight:700; color:#fff; flex-shrink:0; border:2px solid #fff; cursor:default; position:relative; }
        .team-more { background:#f3f4f6; color:#6b7280; font-size:.7rem; font-weight:700; }
        .no-team   { color:#9ca3af; font-size:.8rem; font-style:italic; }

        /* Card footer */
        .card-footer { display:flex; align-items:center; justify-content:space-between; padding-top:.75rem; border-top:1px solid #f9fafb; margin-top:auto; }
        .card-meta-foot { display:flex; gap:.85rem; font-size:.78rem; color:#9ca3af; }
        .card-actions   { display:flex; gap:.4rem; }
        .btn-xs { padding:.3rem .65rem; font-size:.75rem; border-radius:6px; border:1px solid #e5e7eb; background:#fafafa; color:#374151; cursor:pointer; transition:all .15s; text-decoration:none; display:inline-block; white-space:nowrap; }
        .btn-xs:hover { background:#6366f1; color:#fff; border-color:#6366f1; }
        .btn-xs-danger:hover { background:#ef4444; border-color:#ef4444; color:#fff; }

        /* ── LIST TABLE ── */
        .proj-table { width:100%; border-collapse:collapse; }
        .proj-table th { background:#f8f9fa; padding:.65rem 1rem; text-align:left; font-size:.78rem; font-weight:700; color:#374151; border-bottom:2px solid #e5e7eb; white-space:nowrap; }
        .proj-table td { padding:.7rem 1rem; border-bottom:1px solid #f3f4f6; font-size:.85rem; }
        .proj-table tr:hover td { background:#fafafe; }
        .proj-table .pri-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:.4rem; }
        .tbl-prog { width:80px; height:6px; background:#f3f4f6; border-radius:10px; overflow:hidden; display:inline-block; vertical-align:middle; }
        .tbl-prog-fill { height:100%; border-radius:10px; }

        /* ── QUICK UPDATE PANEL ── */
        .qpanel-overlay { position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:900; display:none; }
        .qpanel-overlay.open { display:block; }
        .qpanel { position:fixed; right:0; top:0; bottom:0; width:min(420px,100vw); background:#fff; box-shadow:-4px 0 24px rgba(0,0,0,.15); z-index:901; transform:translateX(100%); transition:transform .3s cubic-bezier(.4,0,.2,1); overflow-y:auto; display:flex; flex-direction:column; }
        .qpanel.open { transform:translateX(0); }
        .qpanel-head { background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; padding:1.5rem; }
        .qpanel-head h3 { margin:0 0 .25rem; font-size:1.1rem; }
        .qpanel-head p  { margin:0; font-size:.8rem; opacity:.8; }
        .qpanel-body { padding:1.5rem; flex:1; }
        .qpanel-group { margin-bottom:1.25rem; }
        .qpanel-group label { display:block; font-weight:600; font-size:.83rem; color:#374151; margin-bottom:.4rem; }
        .qpanel-group select,
        .qpanel-group input { width:100%; padding:.6rem .85rem; border:1px solid #e5e7eb; border-radius:8px; font-size:.875rem; }
        .prog-slider { -webkit-appearance:none; height:6px; border-radius:10px; background:#e5e7eb; outline:none; cursor:pointer; }
        .prog-slider::-webkit-slider-thumb { -webkit-appearance:none; width:18px; height:18px; border-radius:50%; background:#6366f1; cursor:pointer; box-shadow:0 2px 6px rgba(99,102,241,.4); }
        .prog-display { text-align:center; font-size:1.5rem; font-weight:700; color:#6366f1; margin:.5rem 0; }
        .qpanel-foot { padding:1rem 1.5rem; border-top:1px solid #f3f4f6; display:flex; gap:.75rem; }
        .qpanel-foot .btn { flex:1; justify-content:center; }

        /* ── CREATE FORM ── */
        .create-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); overflow:hidden; }
        .create-header { background:linear-gradient(135deg,#6366f1,#8b5cf6); padding:1.5rem 2rem; color:#fff; }
        .create-header h2 { margin:0 0 .2rem; font-size:1.25rem; }
        .create-header p  { margin:0; font-size:.875rem; opacity:.85; }
        .create-body { padding:2rem; display:grid; gap:1.5rem; }
        .create-section h4 { font-size:.9rem; font-weight:700; color:#111827; margin:0 0 1rem; padding-bottom:.5rem; border-bottom:2px solid #f3f4f6; }
        .create-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        @media(max-width:600px){ .create-grid { grid-template-columns:1fr; } }
        .cf-group label  { display:block; font-size:.83rem; font-weight:600; color:#374151; margin-bottom:.4rem; }
        .cf-group label span { color:#ef4444; }
        .cf-group input,
        .cf-group select,
        .cf-group textarea { width:100%; padding:.6rem .85rem; border:1px solid #e5e7eb; border-radius:8px; font-size:.875rem; color:#111827; transition:border-color .15s; box-sizing:border-box; }
        .cf-group input:focus,
        .cf-group select:focus,
        .cf-group textarea:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
        .cf-full { grid-column:1/-1; }

        /* Team picker */
        .team-picker { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:.6rem; max-height:240px; overflow-y:auto; padding:.25rem; }
        .team-pick-item { display:flex; align-items:center; gap:.6rem; padding:.5rem .75rem; border:1px solid #e5e7eb; border-radius:8px; cursor:pointer; transition:all .15s; }
        .team-pick-item:hover { border-color:#6366f1; background:#f5f3ff; }
        .team-pick-item input[type=checkbox] { accent-color:#6366f1; }
        .team-pick-item .tav { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:700; color:#fff; flex-shrink:0; }
        .team-pick-info strong { display:block; font-size:.82rem; color:#111827; }
        .team-pick-info small  { font-size:.72rem; color:#9ca3af; }

        /* Alert */
        .page-alert { padding:.85rem 1.25rem; border-radius:8px; margin-bottom:1.25rem; font-weight:500; font-size:.875rem; }
        .alert-success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
        .alert-info    { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }

        /* Empty state */
        .empty-state { text-align:center; padding:4rem 2rem; background:#fff; border-radius:12px; border:2px dashed #e5e7eb; }
        .empty-state .emoji { font-size:3.5rem; margin-bottom:1rem; }
        .empty-state h3 { color:#374151; font-size:1.25rem; margin-bottom:.5rem; }
        .empty-state p  { color:#9ca3af; margin-bottom:1.5rem; }

        @media(max-width:600px) {
            .project-grid { grid-template-columns:1fr; }
            .pstats { grid-template-columns:repeat(3,1fr); }
        }
    </style>
</head>
<body>
<?php
$asset_base = '../';
$nav_base   = '';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">

    <!-- Page hero -->
    <div class="page-hero">
        <div class="page-hero-left">
            <h2>📋 Projects</h2>
            <p>Track, manage and collaborate on all company projects</p>
        </div>
        <?php if (in_array($role, ['admin','manager'])): ?>
        <a href="?view=create" class="btn" style="background:#6366f1;border-color:#6366f1;color:#fff;display:flex;align-items:center;gap:.4rem;">
            ➕ New Project
        </a>
        <?php endif; ?>
    </div>

    <!-- Alerts -->
    <?php if ($_GET['created'] ?? ''): ?>
    <div class="page-alert alert-success">✅ Project created successfully.</div>
    <?php elseif ($_GET['updated'] ?? ''): ?>
    <div class="page-alert alert-success">✅ Project updated.</div>
    <?php elseif ($_GET['deleted'] ?? ''): ?>
    <div class="page-alert alert-info">🗑️ Project deleted.</div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="pstats">
        <div class="pstat"><div class="n"><?= $total ?></div><div class="l">Total</div></div>
        <div class="pstat"><div class="n" style="color:#3b82f6"><?= $active ?></div><div class="l">In Progress</div></div>
        <div class="pstat"><div class="n" style="color:#22c55e"><?= $done ?></div><div class="l">Completed</div></div>
        <div class="pstat"><div class="n" style="color:<?= $overdue > 0 ? '#ef4444' : '#22c55e' ?>"><?= $overdue ?></div><div class="l">Overdue</div></div>
        <div class="pstat"><div class="n" style="color:<?= $blockers > 0 ? '#ef4444' : '#22c55e' ?>"><?= $blockers ?></div><div class="l">Blockers</div></div>
        <div class="pstat"><div class="n"><?= $avg_prog ?>%</div><div class="l">Avg Progress</div></div>
    </div>

    <?php if ($view !== 'create'): ?>
    <!-- Controls bar -->
    <form method="GET" id="filterForm">
        <input type="hidden" name="view" value="list">
        <div class="controls-bar">
            <!-- Search -->
            <input type="text" name="search" value="<?= Security::escapeHTML($search) ?>" placeholder="🔍  Search projects, clients…" oninput="document.getElementById('filterForm').submit()">

            <!-- Filters -->
            <select name="filter_status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach (['pending'=>'Pending','in_progress'=>'In Progress','completed'=>'Completed','on_hold'=>'On Hold'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $filter_status===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>

            <select name="filter_priority" onchange="this.form.submit()">
                <option value="">All Priorities</option>
                <?php foreach (['urgent'=>'🔴 Urgent','high'=>'🟠 High','medium'=>'🟡 Medium','low'=>'🟢 Low'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $filter_priority===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>

            <select name="filter_dept" onchange="this.form.submit()">
                <option value="">All Departments</option>
                <?php foreach ($all_depts as $d): ?>
                <option value="<?= $d ?>" <?= $filter_dept===$d?'selected':'' ?>><?= $d ?></option>
                <?php endforeach; ?>
            </select>

            <select name="sort_by" onchange="this.form.submit()">
                <option value="created_at" <?= $sort_by==='created_at'?'selected':'' ?>>Sort: Newest</option>
                <option value="end_date"   <?= $sort_by==='end_date'  ?'selected':'' ?>>Sort: Due Date</option>
                <option value="priority"   <?= $sort_by==='priority'  ?'selected':'' ?>>Sort: Priority</option>
                <option value="progress"   <?= $sort_by==='progress'  ?'selected':'' ?>>Sort: Progress</option>
                <option value="name"       <?= $sort_by==='name'      ?'selected':'' ?>>Sort: Name A–Z</option>
            </select>

            <div class="controls-divider"></div>

            <!-- View toggle -->
            <div class="view-btns">
                <button type="button" class="view-btn <?= $view_mode==='grid'?'active':'' ?>" onclick="setView('grid')" title="Grid view">⊞</button>
                <button type="button" class="view-btn <?= $view_mode==='list'?'active':'' ?>" onclick="setView('list')" title="List view">☰</button>
            </div>
            <input type="hidden" name="view_mode" id="view_mode_input" value="<?= Security::escapeHTML($view_mode) ?>">

            <?php if ($filter_status || $filter_priority || $filter_dept || $search || $filter_assigned): ?>
            <a href="projects.php" class="btn btn-secondary btn-small" style="white-space:nowrap;">✕ Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Active filter tags -->
    <?php if ($filter_status || $filter_priority || $filter_dept): ?>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
        <?php if ($filter_status): ?><span class="filter-tag"><?= ucfirst(str_replace('_',' ',$filter_status)) ?></span><?php endif; ?>
        <?php if ($filter_priority): ?><span class="filter-tag"><?= ucfirst($filter_priority) ?></span><?php endif; ?>
        <?php if ($filter_dept): ?><span class="filter-tag"><?= Security::escapeHTML($filter_dept) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── PROJECT DISPLAY ── -->
    <?php if (empty($projects)): ?>
    <div class="empty-state">
        <div class="emoji">📭</div>
        <h3><?= ($search || $filter_status || $filter_priority || $filter_dept) ? 'No projects match your filters' : 'No projects yet' ?></h3>
        <p><?= ($search || $filter_status || $filter_priority || $filter_dept) ? 'Try adjusting or clearing your filters.' : 'Create your first project to get started.' ?></p>
        <div style="display:flex;gap:.75rem;justify-content:center;">
            <?php if ($search || $filter_status || $filter_priority || $filter_dept): ?>
            <a href="projects.php" class="btn btn-secondary">Clear Filters</a>
            <?php endif; ?>
            <?php if (in_array($role, ['admin','manager'])): ?>
            <a href="?view=create" class="btn" style="background:#6366f1;color:#fff;">➕ Create Project</a>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($view_mode === 'grid'): ?>
    <!-- ── GRID VIEW ── -->
    <div class="project-grid">
        <?php foreach ($projects as $p):
            $prog   = (int)$p['progress'];
            $progColor = $prog >= 75 ? '#22c55e' : ($prog >= 50 ? '#3b82f6' : ($prog >= 25 ? '#f59e0b' : '#ef4444'));
            $team   = $assignments[$p['id']] ?? [];
            $now    = time();
            $end    = $p['end_date'] ? strtotime($p['end_date']) : null;
            $days   = $end ? (int)(($end - $now) / 86400) : null;
            $isDone = $p['status'] === 'completed';

            if ($isDone)         { $dueClass = 'due-done'; $dueText = '✓ Done'; }
            elseif (!$end)       { $dueClass = 'due-ok';   $dueText = 'No due date'; }
            elseif ($days < 0)   { $dueClass = 'due-overdue'; $dueText = abs($days).'d overdue'; }
            elseif ($days <= 7)  { $dueClass = 'due-soon';    $dueText = $days === 0 ? 'Due today' : "Due in {$days}d"; }
            else                 { $dueClass = 'due-ok'; $dueText = date('M j', $end); }

            $statusClass = 'b-'.str_replace(' ','_',$p['status']);
        ?>
        <div class="project-card" data-priority="<?= $p['priority'] ?>">
            <!-- Top row: title + badges -->
            <div class="card-top">
                <a href="project_detail.php?id=<?= $p['id'] ?>" class="card-title"><?= Security::escapeHTML($p['name']) ?></a>
                <div class="card-badges">
                    <span class="badge <?= $statusClass ?>"><?= ucfirst(str_replace('_',' ',$p['status'])) ?></span>
                    <span class="badge b-<?= $p['priority'] ?>"><?= ucfirst($p['priority']) ?></span>
                </div>
            </div>

            <!-- Meta info grid -->
            <div class="card-meta">
                <div class="meta-item">
                    <span class="meta-label">Client</span>
                    <?= Security::escapeHTML($p['client_name'] ?? '—') ?>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Department</span>
                    <span class="badge b-dept" style="font-size:.7rem;"><?= Security::escapeHTML($p['department'] ?? '—') ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Category</span>
                    <?= Security::escapeHTML($p['category'] ?? '—') ?>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Due</span>
                    <span class="due-chip <?= $dueClass ?>"><?= $dueText ?></span>
                </div>
            </div>

            <!-- Progress -->
            <div class="prog-wrap">
                <div class="prog-info">
                    <span style="font-size:.8rem;font-weight:600;color:#374151;">Progress</span>
                    <span style="font-size:.8rem;font-weight:700;color:<?= $progColor ?>"><?= $prog ?>%</span>
                </div>
                <div class="prog-bar"><div class="prog-fill" style="width:<?= $prog ?>%;background:<?= $progColor ?>;"></div></div>
            </div>

            <!-- Team avatars -->
            <div class="team-row">
                <?php if (empty($team)): ?>
                <span class="no-team">No team assigned</span>
                <?php else:
                    $show = array_slice($team, 0, 4);
                    $more = count($team) - 4;
                    foreach ($show as $m):
                        $col = $avatar_colors[crc32($m['username']) % count($avatar_colors)];
                        $ini = strtoupper(mb_substr($m['username'], 0, 2));
                ?>
                <div class="team-av" style="background:<?= $col ?>;" title="<?= Security::escapeHTML($m['username']) ?>"><?= $ini ?></div>
                <?php endforeach;
                if ($more > 0): ?>
                <div class="team-av team-more">+<?= $more ?></div>
                <?php endif; endif; ?>
            </div>

            <!-- Footer -->
            <div class="card-footer">
                <div class="card-meta-foot">
                    💬 <?= (int)$p['comment_count'] ?>
                    <?php if ($p['blocker_count'] > 0): ?>
                    &nbsp; 🚨 <span style="color:#ef4444;font-weight:700;"><?= (int)$p['blocker_count'] ?></span>
                    <?php endif; ?>
                    &nbsp; 👥 <?= (int)$p['team_count'] ?>
                </div>
                <div class="card-actions">
                    <a href="project_detail.php?id=<?= $p['id'] ?>" class="btn-xs">View</a>
                    <?php if (in_array($role, ['admin','manager'])): ?>
                    <button type="button" class="btn-xs" onclick="openQuickUpdate(<?= htmlspecialchars(json_encode(['id'=>$p['id'],'name'=>$p['name'],'progress'=>$prog,'status'=>$p['status'],'priority'=>$p['priority']]), ENT_QUOTES) ?>)">Update</button>
                    <?php endif; ?>
                    <?php if ($role === 'admin'): ?>
                    <button type="button" class="btn-xs btn-xs-danger" onclick="confirmDelete(<?= $p['id'] ?>, '<?= addslashes(Security::escapeHTML($p['name'])) ?>')" title="Delete">🗑</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <!-- ── LIST VIEW ── -->
    <div class="section">
        <div class="section-content" style="overflow-x:auto;">
            <table class="proj-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Client</th>
                        <th>Dept</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Team</th>
                        <th>Due</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($projects as $p):
                    $prog = (int)$p['progress'];
                    $progColor = $prog >= 75 ? '#22c55e' : ($prog >= 50 ? '#3b82f6' : ($prog >= 25 ? '#f59e0b' : '#ef4444'));
                    $team = $assignments[$p['id']] ?? [];
                    $now  = time();
                    $end  = $p['end_date'] ? strtotime($p['end_date']) : null;
                    $days = $end ? (int)(($end - $now) / 86400) : null;
                    $isDone = $p['status'] === 'completed';
                    $priColors = ['urgent'=>'#dc2626','high'=>'#ef4444','medium'=>'#f59e0b','low'=>'#22c55e'];
                    $priColor  = $priColors[$p['priority']] ?? '#9ca3af';
                    if ($isDone)         { $dueClass = 'due-done'; $dueText = '✓ Done'; }
                    elseif (!$end)       { $dueClass = 'due-ok';   $dueText = '—'; }
                    elseif ($days < 0)   { $dueClass = 'due-overdue'; $dueText = abs($days).'d overdue'; }
                    elseif ($days <= 7)  { $dueClass = 'due-soon';    $dueText = $days === 0 ? 'Today' : "{$days}d"; }
                    else                 { $dueClass = 'due-ok'; $dueText = date('M j', $end); }
                ?>
                <tr>
                    <td>
                        <a href="project_detail.php?id=<?= $p['id'] ?>" style="font-weight:600;color:#111827;text-decoration:none;"><?= Security::escapeHTML($p['name']) ?></a>
                        <?php if ($p['blocker_count'] > 0): ?><span style="color:#ef4444;font-size:.75rem;"> 🚨<?= (int)$p['blocker_count'] ?></span><?php endif; ?>
                    </td>
                    <td style="color:#6b7280;"><?= Security::escapeHTML($p['client_name'] ?? '—') ?></td>
                    <td><span class="badge b-dept" style="font-size:.7rem;"><?= Security::escapeHTML($p['department'] ?? '—') ?></span></td>
                    <td><span class="pri-dot" style="background:<?= $priColor ?>"></span><?= ucfirst($p['priority']) ?></td>
                    <td><span class="badge b-<?= $p['status'] ?>"><?= ucfirst(str_replace('_',' ',$p['status'])) ?></span></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <div class="tbl-prog"><div class="tbl-prog-fill" style="width:<?= $prog ?>%;background:<?= $progColor ?>"></div></div>
                            <span style="font-size:.78rem;color:#374151;"><?= $prog ?>%</span>
                        </div>
                    </td>
                    <td>
                        <div class="team-row">
                            <?php
                            $show = array_slice($team, 0, 3);
                            $more = count($team) - 3;
                            foreach ($show as $m):
                                $col = $avatar_colors[crc32($m['username']) % count($avatar_colors)];
                                $ini = strtoupper(mb_substr($m['username'], 0, 2));
                            ?>
                            <div class="team-av" style="background:<?= $col ?>;width:22px;height:22px;font-size:.6rem;" title="<?= Security::escapeHTML($m['username']) ?>"><?= $ini ?></div>
                            <?php endforeach;
                            if ($more > 0): ?><div class="team-av team-more" style="width:22px;height:22px;font-size:.6rem;">+<?= $more ?></div><?php endif;
                            if (empty($team)): ?><span class="no-team">—</span><?php endif; ?>
                        </div>
                    </td>
                    <td><span class="due-chip <?= $dueClass ?>"><?= $dueText ?></span></td>
                    <td>
                        <div style="display:flex;gap:.3rem;">
                            <a href="project_detail.php?id=<?= $p['id'] ?>" class="btn-xs">View</a>
                            <?php if (in_array($role, ['admin','manager'])): ?>
                            <button type="button" class="btn-xs" onclick="openQuickUpdate(<?= htmlspecialchars(json_encode(['id'=>$p['id'],'name'=>$p['name'],'progress'=>$prog,'status'=>$p['status'],'priority'=>$p['priority']]), ENT_QUOTES) ?>)">Update</button>
                            <?php endif; ?>
                            <?php if ($role === 'admin'): ?>
                            <button type="button" class="btn-xs btn-xs-danger" onclick="confirmDelete(<?= $p['id'] ?>, '<?= addslashes(Security::escapeHTML($p['name'])) ?>')">🗑</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; /* view mode */ ?>

    <?php else: /* view === create */ ?>
    <!-- ── CREATE PROJECT FORM ── -->
    <div class="create-card">
        <div class="create-header">
            <h2>➕ Create New Project</h2>
            <p>Fill in the details below to create a new project</p>
        </div>
        <div class="create-body">
            <form method="post">
                <?= Security::getCSRFTokenField() ?>

                <!-- Section 1: Project Info -->
                <div class="create-section">
                    <h4>📋 Project Info</h4>
                    <div class="create-grid">
                        <div class="cf-group cf-full">
                            <label>Project Name <span>*</span></label>
                            <input type="text" name="name" required placeholder="e.g. Website Redesign for Acme Ltd">
                        </div>
                        <div class="cf-group">
                            <label>Client</label>
                            <select name="client_id">
                                <option value="">— No client —</option>
                                <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= Security::escapeHTML($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="cf-group">
                            <label>Department <span>*</span></label>
                            <select name="project_department" required>
                                <option value="">Select Department</option>
                                <?php foreach ($all_depts as $d): ?>
                                <option value="<?= $d ?>" <?= $d === $department ? 'selected' : '' ?>><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="cf-group">
                            <label>Category <span>*</span></label>
                            <select name="category" required>
                                <option value="Web Dev">Web Development</option>
                                <option value="Software Dev">Software Development</option>
                                <option value="Mobile App">Mobile App</option>
                                <option value="Consulting">Consulting</option>
                                <option value="Design">Design</option>
                                <option value="Research">Research</option>
                                <option value="Marketing Project">Marketing Project</option>
                                <option value="Infrastructure">Infrastructure</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="cf-group">
                            <label>Priority <span>*</span></label>
                            <select name="priority" required>
                                <option value="low">🟢 Low</option>
                                <option value="medium" selected>🟡 Medium</option>
                                <option value="high">🟠 High</option>
                                <option value="urgent">🔴 Urgent</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Dates -->
                <div class="create-section">
                    <h4>📅 Schedule</h4>
                    <div class="create-grid">
                        <div class="cf-group">
                            <label>Start Date <span>*</span></label>
                            <input type="date" name="start_date" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="cf-group">
                            <label>End Date <span>*</span></label>
                            <input type="date" name="end_date" required>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Description -->
                <div class="create-section">
                    <h4>📝 Description</h4>
                    <div class="cf-group">
                        <label>Project Description</label>
                        <textarea name="description" rows="4" placeholder="Describe the project goals, deliverables, and any important context…" maxlength="2000" id="desc_ta"></textarea>
                        <div style="text-align:right;font-size:.75rem;color:#9ca3af;margin-top:.25rem;"><span id="desc_count">0</span>/2000</div>
                    </div>
                </div>

                <!-- Section 4: Team -->
                <div class="create-section">
                    <h4>👥 Assign Team Members</h4>
                    <input type="text" id="teamSearch" placeholder="🔍 Filter team members…" style="width:100%;padding:.5rem .85rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.875rem;margin-bottom:.75rem;box-sizing:border-box;" oninput="filterTeam(this.value)">
                    <div class="team-picker" id="teamPicker">
                        <?php foreach ($all_users as $u):
                            $col = $avatar_colors[crc32($u['username']) % count($avatar_colors)];
                            $ini = strtoupper(mb_substr($u['username'], 0, 2));
                        ?>
                        <label class="team-pick-item" data-name="<?= strtolower(Security::escapeHTML($u['username'])) ?> <?= strtolower(Security::escapeHTML($u['department'] ?? '')) ?>">
                            <input type="checkbox" name="assigned_employees[]" value="<?= $u['id'] ?>">
                            <div class="tav" style="background:<?= $col ?>"><?= $ini ?></div>
                            <div class="team-pick-info">
                                <strong><?= Security::escapeHTML($u['username']) ?></strong>
                                <small><?= Security::escapeHTML($u['department'] ?? '') ?></small>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Submit -->
                <div style="display:flex;gap:.75rem;align-items:center;">
                    <button type="submit" name="create_project" class="btn" style="background:#6366f1;border-color:#6366f1;color:#fff;padding:.7rem 2rem;">
                        Create Project
                    </button>
                    <a href="projects.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.main-content -->

<!-- ── QUICK UPDATE PANEL ── -->
<?php if (in_array($role, ['admin','manager'])): ?>
<div id="qpOverlay" class="qpanel-overlay" onclick="closeQuickUpdate()"></div>
<div id="qPanel" class="qpanel">
    <div class="qpanel-head">
        <h3 id="qp_title">Update Project</h3>
        <p id="qp_sub">Adjust progress and status</p>
    </div>
    <div class="qpanel-body">
        <form method="post" id="qpForm">
            <?= Security::getCSRFTokenField() ?>
            <input type="hidden" name="project_id" id="qp_id">
            <div class="qpanel-group">
                <label>Status</label>
                <select name="status" id="qp_status">
                    <option value="pending">⏸ Pending</option>
                    <option value="in_progress">▶ In Progress</option>
                    <option value="completed">✓ Completed</option>
                    <option value="on_hold">⏹ On Hold</option>
                </select>
            </div>
            <div class="qpanel-group">
                <label>Progress — <span id="qp_pct_label" style="color:#6366f1;font-size:1rem;">0%</span></label>
                <input type="range" class="prog-slider" name="progress" id="qp_progress" min="0" max="100" step="5" value="0" oninput="document.getElementById('qp_pct_label').textContent=this.value+'%'">
                <div style="display:flex;justify-content:space-between;font-size:.72rem;color:#9ca3af;margin-top:.25rem;"><span>0%</span><span>50%</span><span>100%</span></div>
            </div>
        </form>
    </div>
    <div class="qpanel-foot">
        <button type="button" class="btn btn-secondary" onclick="closeQuickUpdate()">Cancel</button>
        <button type="button" class="btn" style="background:#6366f1;color:#fff;" onclick="document.getElementById('qpForm').submit()">
            <input type="hidden" name="update_project" form="qpForm" value="1">
            Save Changes
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Delete confirm form (hidden) -->
<?php if ($role === 'admin'): ?>
<form method="post" id="deleteForm" style="display:none;">
    <?= Security::getCSRFTokenField() ?>
    <input type="hidden" name="project_id" id="del_pid">
    <input type="hidden" name="delete_project" value="1">
</form>
<?php endif; ?>

<script src="../js/notification.js"></script>
<script>
// View toggle
function setView(mode) {
    document.getElementById('view_mode_input').value = mode;
    document.getElementById('filterForm').submit();
}

// Quick update panel
function openQuickUpdate(data) {
    document.getElementById('qp_id').value = data.id;
    document.getElementById('qp_title').textContent = data.name;
    document.getElementById('qp_sub').textContent   = 'Priority: ' + data.priority.charAt(0).toUpperCase() + data.priority.slice(1);
    document.getElementById('qp_status').value   = data.status;
    document.getElementById('qp_progress').value = data.progress;
    document.getElementById('qp_pct_label').textContent = data.progress + '%';
    document.getElementById('qpOverlay').classList.add('open');
    document.getElementById('qPanel').classList.add('open');
}
function closeQuickUpdate() {
    document.getElementById('qpOverlay').classList.remove('open');
    document.getElementById('qPanel').classList.remove('open');
}

// Auto-sync status when progress reaches 100
document.getElementById('qp_progress')?.addEventListener('input', function() {
    if (parseInt(this.value) === 100) {
        document.getElementById('qp_status').value = 'completed';
    } else if (parseInt(this.value) > 0 && document.getElementById('qp_status').value === 'pending') {
        document.getElementById('qp_status').value = 'in_progress';
    }
});

// Delete confirmation
function confirmDelete(pid, name) {
    if (!confirm('Delete project "' + name + '"?\n\nThis will also remove all comments and assignments. This cannot be undone.')) return;
    document.getElementById('del_pid').value = pid;
    document.getElementById('deleteForm').submit();
}

// Description character count
const descTa = document.getElementById('desc_ta');
if (descTa) {
    descTa.addEventListener('input', function() {
        document.getElementById('desc_count').textContent = this.value.length;
    });
}

// Team member search filter
function filterTeam(query) {
    const q = query.toLowerCase();
    document.querySelectorAll('#teamPicker .team-pick-item').forEach(item => {
        item.style.display = item.dataset.name.includes(q) ? '' : 'none';
    });
}

// Auto-dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.page-alert').forEach(el => {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    });
}, 3000);
</script>
</body>
</html>

