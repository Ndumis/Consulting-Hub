<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/security.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) { header("Location: auth/login.php"); exit(); }
if (!in_array($_SESSION['role'], ['admin','manager']) && $_SESSION['department'] !== 'HR') {
    header("Location: dashboard.php"); exit();
}

$database = new Database();
$db       = $database->getConnection();
$asset_base = '';
$nav_base   = 'departments/';

$success = ''; $error = '';

// Update candidate status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $id     = (int)($_POST['candidate_id'] ?? 0);
        $status = Security::sanitizeInput($_POST['status'] ?? '');
        $allowed = ['pending','applied','screening','review','interview_scheduled','offer_made','rejected'];
        if ($id && in_array($status, $allowed)) {
            $db->prepare("UPDATE candidates SET status=? WHERE id=?")->execute([$status, $id]);
            $success = 'Candidate status updated.';
            Utils::logActivity($db, 'update', "Candidate #$id status changed to $status");
        }
    }
}

// Filters
$f_status = Security::sanitizeInput($_GET['status'] ?? '');
$f_job    = (int)($_GET['job_id'] ?? 0);
$f_search = Security::sanitizeInput($_GET['search'] ?? '');

$where = []; $params = [];
if ($f_status) { $where[] = 'c.status = ?'; $params[] = $f_status; }
if ($f_job)    { $where[] = 'c.job_posting_id = ?'; $params[] = $f_job; }
if ($f_search) {
    $where[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ?)";
    $params[] = "%$f_search%"; $params[] = "%$f_search%"; $params[] = "%$f_search%";
}
$sql = "SELECT c.*, jp.title AS job_title, jp.department AS job_dept
        FROM candidates c JOIN job_postings jp ON c.job_posting_id = jp.id"
     . ($where ? ' WHERE '.implode(' AND ', $where) : '')
     . " ORDER BY c.created_at DESC";

$stmt = $db->prepare($sql); $stmt->execute($params);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// KPIs
$kpis = $db->query("SELECT
    COUNT(*) AS total,
    SUM(status IN ('pending','applied')) AS new_apps,
    SUM(status='interview_scheduled') AS interviews,
    SUM(status='offer_made') AS offers,
    SUM(status='rejected') AS rejected
    FROM candidates")->fetch(PDO::FETCH_ASSOC);

// Job postings for filter
$jobs_list = $db->query("SELECT id, title FROM job_postings ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

$status_labels = [
    'pending'             => ['Pending',            '#94a3b8', '#f1f5f9'],
    'applied'             => ['Applied',             '#3b82f6', '#eff6ff'],
    'screening'           => ['Screening',           '#f59e0b', '#fffbeb'],
    'review'              => ['In Review',           '#8b5cf6', '#f5f3ff'],
    'interview_scheduled' => ['Interview Scheduled', '#0ea5e9', '#f0f9ff'],
    'offer_made'          => ['Offer Made',          '#10b981', '#ecfdf5'],
    'rejected'            => ['Rejected',            '#ef4444', '#fef2f2'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates — KConsulting Hub</title>
    <link rel="icon" type="image/png" href="img/KConsultingLogo1.png">
    <link rel="stylesheet" href="css/main.css">
    <style>
        .ca-hero { background: linear-gradient(135deg,#0f172a 0%,#1e293b 100%); color:#fff; padding:28px 32px 24px; }
        .ca-hero h1 { font-size:1.5rem; font-weight:800; margin:0 0 4px; }
        .ca-hero p  { font-size:.87rem; color:rgba(255,255,255,.6); margin:0; }

        .ca-wrap { max-width:1300px; margin:0 auto; padding:24px 28px; }

        /* KPIs */
        .ca-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:14px; margin-bottom:24px; }
        .ca-kpi  { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px 18px; }
        .ca-kpi-num { font-size:1.6rem; font-weight:800; color:#0f172a; }
        .ca-kpi-lbl { font-size:.78rem; color:#64748b; margin-top:2px; }

        /* Filter bar */
        .ca-bar { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; }
        .ca-bar input, .ca-bar select { padding:7px 10px; border:1.5px solid #d1d5db; border-radius:7px; font-size:.85rem; color:#374151; }
        .ca-bar input:focus, .ca-bar select:focus { outline:none; border-color:#0f172a; }
        .ca-bar button { padding:7px 16px; border:none; border-radius:7px; cursor:pointer; font-size:.85rem; font-weight:600; }
        .ca-btn-filter { background:#0f172a; color:#fff; }
        .ca-btn-reset  { background:#f1f5f9; color:#374151; }

        /* Alert */
        .ca-alert { border-radius:8px; padding:10px 14px; font-size:.85rem; margin-bottom:16px; }
        .ca-alert-ok  { background:#f0fdf4; border:1px solid #bbf7d0; color:#059669; }
        .ca-alert-err { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }

        /* Table */
        .ca-table-wrap { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
        .ca-table-hdr  { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid #f1f5f9; }
        .ca-table-hdr strong { font-size:.9rem; color:#0f172a; }
        table.ca-tbl { width:100%; border-collapse:collapse; }
        .ca-tbl th { background:#f8fafc; font-size:.78rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; padding:10px 14px; text-align:left; border-bottom:1px solid #e2e8f0; }
        .ca-tbl td { padding:11px 14px; font-size:.86rem; color:#374151; border-bottom:1px solid #f8fafc; vertical-align:middle; }
        .ca-tbl tr:last-child td { border-bottom:none; }
        .ca-tbl tr:hover td { background:#fafafa; }

        .ca-badge { display:inline-block; padding:3px 10px; border-radius:12px; font-size:.76rem; font-weight:700; }

        /* Status dropdown form */
        .ca-status-form select { padding:4px 8px; border:1.5px solid #d1d5db; border-radius:6px; font-size:.8rem; color:#374151; cursor:pointer; }
        .ca-status-form select:focus { outline:none; border-color:#0f172a; }
        .ca-status-form button { display:none; }

        .ca-empty { text-align:center; padding:48px 24px; color:#94a3b8; font-size:.9rem; }
    </style>
</head>
<body>
<?php
$username   = $_SESSION['username'];
$role       = $_SESSION['role'];
$department = $_SESSION['department'];
$user_id    = $_SESSION['user_id'];
include 'includes/header.php';
?>
<div class="main-layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content" style="padding:0;">

        <div class="ca-hero">
            <h1>Candidate Applications</h1>
            <p>HR &rsaquo; Recruitment &rsaquo; Candidates &nbsp;&mdash;&nbsp; <?= count($candidates) ?> result<?= count($candidates) !== 1 ? 's' : '' ?></p>
        </div>

        <div class="ca-wrap">
            <?php if ($success): ?><div class="ca-alert ca-alert-ok">✅ <?= Security::escapeHTML($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="ca-alert ca-alert-err">⚠️ <?= Security::escapeHTML($error) ?></div><?php endif; ?>

            <!-- KPIs -->
            <div class="ca-kpis">
                <div class="ca-kpi"><div class="ca-kpi-num"><?= $kpis['total'] ?></div><div class="ca-kpi-lbl">Total</div></div>
                <div class="ca-kpi"><div class="ca-kpi-num" style="color:#3b82f6"><?= $kpis['new_apps'] ?></div><div class="ca-kpi-lbl">New Applications</div></div>
                <div class="ca-kpi"><div class="ca-kpi-num" style="color:#0ea5e9"><?= $kpis['interviews'] ?></div><div class="ca-kpi-lbl">Interviews</div></div>
                <div class="ca-kpi"><div class="ca-kpi-num" style="color:#10b981"><?= $kpis['offers'] ?></div><div class="ca-kpi-lbl">Offers Made</div></div>
                <div class="ca-kpi"><div class="ca-kpi-num" style="color:#ef4444"><?= $kpis['rejected'] ?></div><div class="ca-kpi-lbl">Rejected</div></div>
            </div>

            <!-- Filter bar -->
            <form method="GET" class="ca-bar">
                <input type="text" name="search" placeholder="Search name / email…" value="<?= Security::escapeHTML($f_search) ?>">
                <select name="job_id">
                    <option value="">All Positions</option>
                    <?php foreach ($jobs_list as $j): ?>
                    <option value="<?= $j['id'] ?>" <?= $f_job === (int)$j['id'] ? 'selected' : '' ?>><?= Security::escapeHTML($j['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">All Statuses</option>
                    <?php foreach ($status_labels as $k => [$lbl]): ?>
                    <option value="<?= $k ?>" <?= $f_status === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="ca-btn-filter">Filter</button>
                <a href="candidates.php" class="ca-btn-reset" style="text-decoration:none;padding:7px 16px;border-radius:7px;font-size:.85rem;font-weight:600;">Reset</a>
            </form>

            <!-- Table -->
            <div class="ca-table-wrap">
                <div class="ca-table-hdr">
                    <strong><?= count($candidates) ?> candidate<?= count($candidates) !== 1 ? 's' : '' ?></strong>
                    <a href="departments/hr.php?tab=recruitment" style="font-size:.82rem;color:#64748b;text-decoration:none;">← HR Recruitment Tab</a>
                </div>
                <?php if (empty($candidates)): ?>
                    <div class="ca-empty">No candidates match your filters.</div>
                <?php else: ?>
                <table class="ca-tbl">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Position</th>
                            <th>Contact</th>
                            <th>Experience</th>
                            <th>Applied</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($candidates as $c): ?>
                        <?php [$lbl, $color, $bg] = $status_labels[$c['status']] ?? ['Unknown','#94a3b8','#f1f5f9']; ?>
                        <tr>
                            <td>
                                <div style="font-weight:700;color:#0f172a;"><?= Security::escapeHTML($c['first_name'].' '.$c['last_name']) ?></div>
                                <?php if ($c['preferred_location']): ?>
                                <div style="font-size:.78rem;color:#94a3b8;"><?= Security::escapeHTML($c['preferred_location']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:600;"><?= Security::escapeHTML($c['job_title']) ?></div>
                                <div style="font-size:.78rem;color:#94a3b8;"><?= Security::escapeHTML($c['job_dept']) ?></div>
                            </td>
                            <td>
                                <div><?= Security::escapeHTML($c['email']) ?></div>
                                <?php if ($c['phone']): ?>
                                <div style="font-size:.78rem;color:#94a3b8;"><?= Security::escapeHTML($c['phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= $c['years_experience'] ? $c['years_experience'].' yrs' : '—' ?></td>
                            <td style="font-size:.8rem;color:#64748b;"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <form method="POST" class="ca-status-form" onchange="this.submit()">
                                    <?= Security::getCSRFTokenField() ?>
                                    <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <select name="status" style="background:<?= $bg ?>;color:<?= $color ?>;border-color:<?= $color ?>30;font-weight:700;">
                                        <?php foreach ($status_labels as $k => [$sl]): ?>
                                        <option value="<?= $k ?>" <?= $c['status'] === $k ? 'selected' : '' ?>><?= $sl ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
</body>
</html>
