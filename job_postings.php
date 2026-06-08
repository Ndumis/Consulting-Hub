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

// Create job posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_job'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $title  = Security::sanitizeInput($_POST['title'] ?? '');
        $dept   = Security::sanitizeInput($_POST['department'] ?? '');
        $desc   = Security::sanitizeInput($_POST['description'] ?? '');
        $req    = Security::sanitizeInput($_POST['requirements'] ?? '');
        $salary = Security::sanitizeInput($_POST['salary_range'] ?? '');
        if (!$title || !$dept) {
            $error = 'Title and department are required.';
        } else {
            $db->prepare("INSERT INTO job_postings (title,department,description,requirements,salary_range,posted_by,status) VALUES (?,?,?,?,?,?,'active')")
               ->execute([$title,$dept,$desc,$req,$salary,$_SESSION['user_id']]);
            $success = "Job posting '$title' created.";
            Utils::logActivity($db, 'create', "Job posting created: $title ($dept)");
        }
    }
}

// Update status (activate/close)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $id = (int)($_POST['job_id'] ?? 0);
        $row = $db->prepare("SELECT status FROM job_postings WHERE id=?");
        $row->execute([$id]);
        $cur = $row->fetchColumn();
        if ($cur) {
            $new = $cur === 'active' ? 'closed' : 'active';
            $db->prepare("UPDATE job_postings SET status=? WHERE id=?")->execute([$new, $id]);
            $success = "Posting set to $new.";
        }
    }
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_job'])) {
    if (Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $id = (int)($_POST['job_id'] ?? 0);
        if ($id && $_SESSION['role'] === 'admin') {
            $db->prepare("DELETE FROM candidates WHERE job_posting_id=?")->execute([$id]);
            $db->prepare("DELETE FROM job_postings WHERE id=?")->execute([$id]);
            $success = 'Job posting deleted.';
            Utils::logActivity($db, 'delete', "Job posting #$id deleted");
        }
    }
}

// Load postings
$f_status = Security::sanitizeInput($_GET['status'] ?? '');
$where = $f_status ? 'WHERE jp.status=?' : '';
$params = $f_status ? [$f_status] : [];

$stmt = $db->prepare("SELECT jp.*, u.username AS posted_by_name,
    (SELECT COUNT(*) FROM candidates c WHERE c.job_posting_id=jp.id) AS applicant_count
    FROM job_postings jp LEFT JOIN users u ON jp.posted_by=u.id
    $where ORDER BY jp.created_at DESC");
$stmt->execute($params);
$postings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$kpis = $db->query("SELECT
    COUNT(*) AS total,
    SUM(status='active') AS active,
    SUM(status='closed') AS closed,
    (SELECT COUNT(*) FROM candidates) AS candidates
    FROM job_postings")->fetch(PDO::FETCH_ASSOC);

$departments = ['Finance','HR','IT','Marketing','BD','Operations','Projects','Management'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings — KConsulting Hub</title>
    <link rel="icon" type="image/png" href="img/KConsultingLogo1.png">
    <link rel="stylesheet" href="css/main.css">
    <style>
        .jp-hero { background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%); color:#fff; padding:28px 32px 24px; }
        .jp-hero h1 { font-size:1.5rem; font-weight:800; margin:0 0 4px; }
        .jp-hero p  { font-size:.87rem; color:rgba(255,255,255,.6); margin:0; }

        .jp-wrap { max-width:1200px; margin:0 auto; padding:24px 28px; }

        .jp-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:14px; margin-bottom:24px; }
        .jp-kpi  { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px 18px; }
        .jp-kpi-num { font-size:1.6rem; font-weight:800; color:#0f172a; }
        .jp-kpi-lbl { font-size:.78rem; color:#64748b; margin-top:2px; }

        .jp-alert { border-radius:8px; padding:10px 14px; font-size:.85rem; margin-bottom:16px; }
        .jp-alert-ok  { background:#f0fdf4; border:1px solid #bbf7d0; color:#059669; }
        .jp-alert-err { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }

        /* Top bar */
        .jp-topbar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:20px; }
        .jp-filter-row { display:flex; gap:8px; flex-wrap:wrap; }
        .jp-filter-row a { padding:6px 14px; border-radius:20px; font-size:.82rem; font-weight:600; text-decoration:none; border:1.5px solid #e2e8f0; color:#374151; }
        .jp-filter-row a.active, .jp-filter-row a:hover { background:#0f172a; color:#fff; border-color:#0f172a; }
        .jp-new-btn { background:#0f172a; color:#fff; border:none; padding:8px 18px; border-radius:8px; font-size:.85rem; font-weight:700; cursor:pointer; }

        /* Cards */
        .jp-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:18px; }
        .jp-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; position:relative; }
        .jp-card-hdr { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; }
        .jp-card-title { font-size:1rem; font-weight:800; color:#0f172a; margin:0 0 3px; }
        .jp-card-dept  { font-size:.8rem; color:#64748b; }
        .jp-badge { display:inline-block; padding:3px 10px; border-radius:12px; font-size:.74rem; font-weight:700; }
        .jp-badge-active { background:#dcfce7; color:#16a34a; }
        .jp-badge-closed { background:#f1f5f9; color:#64748b; }

        .jp-card-meta { display:flex; flex-wrap:wrap; gap:14px; font-size:.8rem; color:#64748b; margin-bottom:12px; }
        .jp-card-meta span strong { color:#374151; }

        .jp-card-desc { font-size:.83rem; color:#374151; line-height:1.5; max-height:72px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; margin-bottom:14px; }

        .jp-card-actions { display:flex; gap:8px; flex-wrap:wrap; }
        .jp-act-btn { padding:6px 14px; border-radius:7px; font-size:.8rem; font-weight:600; cursor:pointer; border:1.5px solid; }
        .jp-act-close  { background:#fff;    color:#0f172a; border-color:#0f172a; }
        .jp-act-open   { background:#0f172a; color:#fff;    border-color:#0f172a; }
        .jp-act-view   { background:#f0f9ff; color:#0ea5e9; border-color:#bae6fd; text-decoration:none; display:inline-flex; align-items:center; }
        .jp-act-delete { background:#fef2f2; color:#dc2626; border-color:#fecaca; }

        .jp-empty { text-align:center; padding:48px 24px; color:#94a3b8; font-size:.9rem; }

        /* Modal */
        .jp-modal-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:500; align-items:center; justify-content:center; }
        .jp-modal-backdrop.open { display:flex; }
        .jp-modal { background:#fff; border-radius:14px; padding:28px; width:100%; max-width:560px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.2); }
        .jp-modal h2 { font-size:1.1rem; font-weight:800; color:#0f172a; margin:0 0 20px; }
        .jp-modal label { display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:5px; margin-top:14px; }
        .jp-modal input, .jp-modal select, .jp-modal textarea {
            width:100%; padding:9px 12px; border:1.5px solid #d1d5db; border-radius:8px;
            font-size:.9rem; color:#111827; box-sizing:border-box;
        }
        .jp-modal input:focus, .jp-modal select:focus, .jp-modal textarea:focus { outline:none; border-color:#0f172a; }
        .jp-modal textarea { resize:vertical; min-height:80px; }
        .jp-modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; }
        .jp-modal-save   { background:#0f172a; color:#fff; border:none; padding:9px 22px; border-radius:8px; font-weight:700; font-size:.9rem; cursor:pointer; }
        .jp-modal-cancel { background:#f1f5f9; color:#374151; border:none; padding:9px 18px; border-radius:8px; font-weight:600; font-size:.9rem; cursor:pointer; }
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

        <div class="jp-hero">
            <h1>Job Postings</h1>
            <p>HR &rsaquo; Recruitment &rsaquo; Job Postings &nbsp;&mdash;&nbsp; <?= count($postings) ?> posting<?= count($postings) !== 1 ? 's' : '' ?></p>
        </div>

        <div class="jp-wrap">
            <?php if ($success): ?><div class="jp-alert jp-alert-ok">✅ <?= Security::escapeHTML($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="jp-alert jp-alert-err">⚠️ <?= Security::escapeHTML($error) ?></div><?php endif; ?>

            <!-- KPIs -->
            <div class="jp-kpis">
                <div class="jp-kpi"><div class="jp-kpi-num"><?= $kpis['total'] ?></div><div class="jp-kpi-lbl">Total Postings</div></div>
                <div class="jp-kpi"><div class="jp-kpi-num" style="color:#16a34a"><?= $kpis['active'] ?></div><div class="jp-kpi-lbl">Active</div></div>
                <div class="jp-kpi"><div class="jp-kpi-num" style="color:#64748b"><?= $kpis['closed'] ?></div><div class="jp-kpi-lbl">Closed</div></div>
                <div class="jp-kpi"><div class="jp-kpi-num" style="color:#3b82f6"><?= $kpis['candidates'] ?></div><div class="jp-kpi-lbl">Total Applicants</div></div>
            </div>

            <!-- Top bar -->
            <div class="jp-topbar">
                <div class="jp-filter-row">
                    <a href="job_postings.php" class="<?= !$f_status ? 'active' : '' ?>">All</a>
                    <a href="job_postings.php?status=active" class="<?= $f_status==='active' ? 'active' : '' ?>">Active</a>
                    <a href="job_postings.php?status=closed" class="<?= $f_status==='closed' ? 'active' : '' ?>">Closed</a>
                </div>
                <button class="jp-new-btn" onclick="openModal()">+ New Posting</button>
            </div>

            <!-- Cards -->
            <?php if (empty($postings)): ?>
                <div class="jp-empty">No job postings found. Create one to get started.</div>
            <?php else: ?>
            <div class="jp-grid">
                <?php foreach ($postings as $jp): ?>
                <div class="jp-card">
                    <div class="jp-card-hdr">
                        <div>
                            <div class="jp-card-title"><?= Security::escapeHTML($jp['title']) ?></div>
                            <div class="jp-card-dept"><?= Security::escapeHTML($jp['department']) ?></div>
                        </div>
                        <span class="jp-badge <?= $jp['status']==='active' ? 'jp-badge-active' : 'jp-badge-closed' ?>">
                            <?= ucfirst($jp['status']) ?>
                        </span>
                    </div>
                    <div class="jp-card-meta">
                        <span>Applicants: <strong><?= $jp['applicant_count'] ?></strong></span>
                        <?php if ($jp['salary_range']): ?>
                        <span>Salary: <strong><?= Security::escapeHTML($jp['salary_range']) ?></strong></span>
                        <?php endif; ?>
                        <span>Posted: <strong><?= date('d M Y', strtotime($jp['created_at'])) ?></strong></span>
                        <span>By: <strong><?= Security::escapeHTML($jp['posted_by_name'] ?? 'System') ?></strong></span>
                    </div>
                    <?php if ($jp['description']): ?>
                    <div class="jp-card-desc"><?= Security::escapeHTML($jp['description']) ?></div>
                    <?php endif; ?>
                    <div class="jp-card-actions">
                        <a href="candidates.php?job_id=<?= $jp['id'] ?>" class="jp-act-btn jp-act-view">View <?= $jp['applicant_count'] ?> applicant<?= $jp['applicant_count']!=1?'s':'' ?></a>
                        <form method="POST" style="display:inline;">
                            <?= Security::getCSRFTokenField() ?>
                            <input type="hidden" name="job_id" value="<?= $jp['id'] ?>">
                            <button type="submit" name="toggle_status" class="jp-act-btn <?= $jp['status']==='active' ? 'jp-act-close' : 'jp-act-open' ?>">
                                <?= $jp['status']==='active' ? 'Close' : 'Reopen' ?>
                            </button>
                        </form>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this posting and all its candidates?')">
                            <?= Security::getCSRFTokenField() ?>
                            <input type="hidden" name="job_id" value="<?= $jp['id'] ?>">
                            <button type="submit" name="delete_job" class="jp-act-btn jp-act-delete">Delete</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- New Posting Modal -->
<div class="jp-modal-backdrop" id="jpModal">
    <div class="jp-modal">
        <h2>New Job Posting</h2>
        <form method="POST">
            <?= Security::getCSRFTokenField() ?>
            <label>Job Title *</label>
            <input type="text" name="title" required placeholder="e.g. Senior Developer">
            <label>Department *</label>
            <select name="department" required>
                <option value="">Select department</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d ?>"><?= $d ?></option>
                <?php endforeach; ?>
            </select>
            <label>Salary Range</label>
            <input type="text" name="salary_range" placeholder="e.g. R25,000 – R35,000 per month">
            <label>Job Description</label>
            <textarea name="description" rows="4" placeholder="Describe the role and responsibilities…"></textarea>
            <label>Requirements</label>
            <textarea name="requirements" rows="3" placeholder="List required skills and qualifications…"></textarea>
            <div class="jp-modal-actions">
                <button type="button" class="jp-modal-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="create_job" class="jp-modal-save">Create Posting</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal()  { document.getElementById('jpModal').classList.add('open'); }
function closeModal() { document.getElementById('jpModal').classList.remove('open'); }
document.getElementById('jpModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
