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
$db       = $database->getConnection();
$role     = $_SESSION['role'];
$uid      = $_SESSION['user_id'];

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$project_id) { header("Location: projects.php"); exit(); }

try {
    require_once '../includes/ActivityLogger.php';
    (new ActivityLogger($db))->logPageVisit('Project Detail', "Viewed project #$project_id");
} catch (Exception $e) {}

// ── POST HANDLERS ─────────────────────────────────────────────────────────

if ($_POST && isset($_POST['add_comment'])) {
    Security::checkCSRFToken();
    $db->prepare("INSERT INTO project_comments (project_id, user_id, comment, is_blocker, parent_comment_id) VALUES (?,?,?,?,?)")
       ->execute([$project_id, $uid, Security::sanitizeInput($_POST['comment']),
                  ($_POST['is_blocker'] ?? 0) ? 1 : 0,
                  !empty($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null]);
    header("Location: project_detail.php?id=$project_id#comments"); exit();
}

if ($_POST && isset($_POST['update_progress'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin','manager'])) { http_response_code(403); die(); }
    $prog   = min(100, max(0, (int)$_POST['progress']));
    $status = Security::sanitizeInput($_POST['status']);
    $desc   = Security::sanitizeInput($_POST['description'] ?? '');
    $db->prepare("UPDATE projects SET progress=?, status=?, description=? WHERE id=?")->execute([$prog, $status, $desc, $project_id]);
    header("Location: project_detail.php?id=$project_id&updated=1"); exit();
}

if ($_POST && isset($_POST['update_assignments'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin','manager'])) { http_response_code(403); die(); }
    $db->prepare("DELETE FROM project_assignments WHERE project_id=?")->execute([$project_id]);
    foreach ($_POST['assigned_employees'] ?? [] as $euid) {
        if (!empty($euid)) {
            $db->prepare("INSERT INTO project_assignments (project_id, user_id) VALUES (?,?)")->execute([$project_id, (int)$euid]);
        }
    }
    header("Location: project_detail.php?id=$project_id&team_updated=1"); exit();
}

// ── FETCH DATA ────────────────────────────────────────────────────────────

$project = $db->prepare("SELECT p.*, c.name as client_name, c.email as client_email, u.username as created_by_name
    FROM projects p LEFT JOIN clients c ON p.client_id=c.id LEFT JOIN users u ON p.created_by=u.id WHERE p.id=?");
$project->execute([$project_id]);
$project = $project->fetch(PDO::FETCH_ASSOC);
if (!$project) { header("Location: projects.php"); exit(); }

$assignments = $db->prepare("SELECT pa.*, u.username, u.email, u.department FROM project_assignments pa JOIN users u ON pa.user_id=u.id WHERE pa.project_id=? ORDER BY u.username");
$assignments->execute([$project_id]);
$assignments = $assignments->fetchAll(PDO::FETCH_ASSOC);

$all_users = $db->query("SELECT id, username, email, department FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
$assigned_ids = array_column($assignments, 'user_id');

$all_comments = $db->prepare("SELECT pc.*, u.username FROM project_comments pc JOIN users u ON pc.user_id=u.id WHERE pc.project_id=? ORDER BY pc.created_at ASC");
$all_comments->execute([$project_id]);
$all_comments = $all_comments->fetchAll(PDO::FETCH_ASSOC);

$top_comments = []; $replies = [];
foreach ($all_comments as $c) {
    if ($c['parent_comment_id'] === null) $top_comments[] = $c;
    else $replies[$c['parent_comment_id']][] = $c;
}

// Derived values
$prog       = (int)$project['progress'];
$progColor  = $prog >= 75 ? '#22c55e' : ($prog >= 50 ? '#3b82f6' : ($prog >= 25 ? '#f59e0b' : '#ef4444'));
$now        = time();
$end        = $project['end_date'] ? strtotime($project['end_date']) : null;
$start      = $project['start_date'] ? strtotime($project['start_date']) : null;
$days_left  = $end ? (int)(($end - $now) / 86400) : null;
$is_done    = $project['status'] === 'completed';
$blockers   = count(array_filter($all_comments, fn($c) => $c['is_blocker'] && !$c['parent_comment_id']));

if ($is_done)            { $due_class = 'due-done';    $due_text = '✓ Completed'; }
elseif ($days_left === null) { $due_class = 'due-ok';  $due_text = 'No due date'; }
elseif ($days_left < 0)  { $due_class = 'due-overdue'; $due_text = abs($days_left).' days overdue'; }
elseif ($days_left === 0){ $due_class = 'due-soon';    $due_text = 'Due today'; }
elseif ($days_left <= 7) { $due_class = 'due-soon';    $due_text = "Due in $days_left days"; }
else                     { $due_class = 'due-ok';      $due_text = date('M j, Y', $end); }

$priority_colors = ['urgent'=>'#dc2626','high'=>'#ef4444','medium'=>'#f59e0b','low'=>'#22c55e'];
$pri_color = $priority_colors[$project['priority']] ?? '#9ca3af';
$avatar_colors = ['#6366f1','#0ea5e9','#8b5cf6','#f59e0b','#22c55e','#ec4899','#ef4444','#14b8a6'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Security::escapeHTML($project['name']) ?> — KConsulting Hub</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        /* ── HERO ── */
        .pd-hero {
            background:#fff; border-radius:12px; padding:1.5rem 2rem;
            box-shadow:0 2px 8px rgba(0,0,0,.07); margin-bottom:1.5rem;
            border-top:4px solid <?= $pri_color ?>;
        }
        .pd-breadcrumb { font-size:.8rem; color:#9ca3af; margin-bottom:.75rem; }
        .pd-breadcrumb a { color:#6366f1; text-decoration:none; }
        .pd-breadcrumb a:hover { text-decoration:underline; }
        .pd-title-row { display:flex; align-items:flex-start; gap:1rem; flex-wrap:wrap; margin-bottom:1rem; }
        .pd-title { font-size:1.6rem; font-weight:800; color:#111827; line-height:1.25; flex:1; margin:0; }
        .pd-badge-row { display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; }
        .badge { display:inline-flex; align-items:center; padding:.25rem .7rem; border-radius:20px; font-size:.75rem; font-weight:700; letter-spacing:.3px; white-space:nowrap; }
        .b-pending    { background:#fef9c3; color:#854d0e; }
        .b-in_progress{ background:#dbeafe; color:#1e40af; }
        .b-completed  { background:#dcfce7; color:#14532d; }
        .b-on_hold    { background:#fee2e2; color:#991b1b; }
        .b-low        { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
        .b-medium     { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }
        .b-high       { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .b-urgent     { background:#dc2626; color:#fff; }
        .b-dept       { background:#f3f4f6; color:#374151; }
        .due-done    { background:#dcfce7; color:#166534; }
        .due-overdue { background:#fee2e2; color:#dc2626; font-weight:700; }
        .due-soon    { background:#fef3c7; color:#b45309; font-weight:700; }
        .due-ok      { background:#f3f4f6; color:#6b7280; }

        /* Hero progress */
        .pd-progress-section { margin-top:1rem; }
        .pd-prog-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:.5rem; }
        .pd-prog-header span { font-size:.85rem; color:#6b7280; font-weight:500; }
        .pd-prog-header strong { font-size:1.1rem; font-weight:800; color:<?= $progColor ?>; }
        .pd-prog-bar  { height:10px; background:#f3f4f6; border-radius:10px; overflow:hidden; }
        .pd-prog-fill { height:100%; border-radius:10px; background:<?= $progColor ?>; transition:width .6s ease; }

        /* Stat chips */
        .pd-chips { display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1rem; }
        .chip { display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .75rem; border-radius:8px; font-size:.8rem; font-weight:500; background:#f3f4f6; color:#374151; }
        .chip.red { background:#fee2e2; color:#dc2626; }

        /* ── LAYOUT ── */
        .pd-layout { display:grid; grid-template-columns:1fr 340px; gap:1.5rem; align-items:start; }
        @media(max-width:900px){ .pd-layout { grid-template-columns:1fr; } }

        /* ── CARDS ── */
        .pd-card { background:#fff; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,.07); overflow:hidden; margin-bottom:1.25rem; }
        .pd-card-head { padding:.85rem 1.25rem; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; }
        .pd-card-head h3 { margin:0; font-size:.95rem; font-weight:700; color:#111827; }
        .pd-card-body { padding:1.25rem; }

        /* Description */
        .pd-description { font-size:.9rem; line-height:1.7; color:#374151; white-space:pre-line; background:#f9fafb; border-radius:8px; padding:1rem; }
        .pd-no-desc { color:#9ca3af; font-style:italic; text-align:center; padding:1.5rem; }

        /* Info table */
        .info-rows { display:flex; flex-direction:column; gap:0; }
        .info-row  { display:flex; align-items:flex-start; gap:.75rem; padding:.65rem 0; border-bottom:1px solid #f9fafb; font-size:.85rem; }
        .info-row:last-child { border-bottom:none; }
        .info-icon { font-size:1rem; flex-shrink:0; width:22px; text-align:center; margin-top:.05rem; }
        .info-label { color:#9ca3af; min-width:80px; flex-shrink:0; }
        .info-value { color:#111827; font-weight:500; flex:1; }

        /* Update form */
        .uf-group { margin-bottom:1rem; }
        .uf-group label { display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
        .uf-group input, .uf-group select, .uf-group textarea {
            width:100%; padding:.55rem .85rem; border:1px solid #e5e7eb; border-radius:8px;
            font-size:.875rem; color:#111827; box-sizing:border-box; transition:border .15s;
        }
        .uf-group input:focus, .uf-group select:focus, .uf-group textarea:focus {
            outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1);
        }
        .prog-row { display:flex; align-items:center; gap:.75rem; }
        .prog-slider { flex:1; -webkit-appearance:none; height:6px; border-radius:10px; background:#e5e7eb; cursor:pointer; outline:none; }
        .prog-slider::-webkit-slider-thumb { -webkit-appearance:none; width:18px; height:18px; border-radius:50%; background:#6366f1; cursor:pointer; box-shadow:0 2px 6px rgba(99,102,241,.4); }
        .prog-pct { font-size:1rem; font-weight:700; color:#6366f1; min-width:38px; text-align:right; }

        /* Team */
        .team-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:.65rem; }
        .team-member { display:flex; flex-direction:column; align-items:center; gap:.4rem; padding:.75rem .5rem; border-radius:10px; border:1px solid #f3f4f6; background:#fafafe; text-align:center; }
        .team-av { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.85rem; font-weight:700; color:#fff; }
        .team-name { font-size:.78rem; font-weight:600; color:#111827; line-height:1.2; word-break:break-word; }
        .team-dept { font-size:.7rem; color:#9ca3af; }
        .no-team { text-align:center; padding:1.5rem; color:#9ca3af; font-style:italic; font-size:.875rem; }

        /* Team picker in form */
        .team-picker { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:.5rem; max-height:220px; overflow-y:auto; padding:.2rem; }
        .team-pick-item { display:flex; align-items:center; gap:.55rem; padding:.45rem .65rem; border:1px solid #e5e7eb; border-radius:8px; cursor:pointer; transition:all .15s; font-size:.82rem; }
        .team-pick-item:hover { border-color:#6366f1; background:#f5f3ff; }
        .team-pick-item input { accent-color:#6366f1; }
        .pick-av { width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.6rem; font-weight:700; color:#fff; flex-shrink:0; }

        /* ── COMMENTS ── */
        .comment-thread { display:flex; flex-direction:column; gap:.85rem; }
        .comment-item { background:#fff; border:1px solid #f3f4f6; border-radius:10px; overflow:hidden; }
        .comment-item.is-blocker { border-color:#fca5a5; background:#fff5f5; }
        .comment-header { display:flex; align-items:center; justify-content:space-between; padding:.6rem .9rem; background:#f9fafb; border-bottom:1px solid #f3f4f6; }
        .comment-item.is-blocker .comment-header { background:#fef2f2; border-bottom-color:#fca5a5; }
        .comment-author { display:flex; align-items:center; gap:.5rem; }
        .cmt-av { width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.6rem; font-weight:700; color:#fff; flex-shrink:0; }
        .cmt-name { font-size:.82rem; font-weight:700; color:#111827; }
        .cmt-time { font-size:.73rem; color:#9ca3af; }
        .blocker-tag { background:#dc2626; color:#fff; font-size:.68rem; font-weight:700; padding:.15rem .45rem; border-radius:4px; letter-spacing:.3px; }
        .comment-body { padding:.75rem .9rem; font-size:.875rem; color:#374151; line-height:1.6; white-space:pre-line; }
        .comment-actions { padding:.3rem .9rem .6rem; display:flex; gap:.5rem; }
        .reply-btn { background:none; border:none; cursor:pointer; font-size:.78rem; color:#6366f1; font-weight:600; padding:0; }
        .reply-btn:hover { text-decoration:underline; }

        /* Replies */
        .replies-wrap { border-top:1px solid #f3f4f6; background:#fafafa; padding:.75rem .9rem; display:flex; flex-direction:column; gap:.6rem; }
        .reply-item { background:#fff; border:1px solid #f3f4f6; border-radius:8px; overflow:hidden; }
        .reply-item .comment-header { padding:.45rem .75rem; }
        .reply-item .comment-body   { padding:.5rem .75rem; font-size:.84rem; }

        /* Reply form */
        .reply-form-wrap { border-top:1px solid #f3f4f6; background:#fafafa; padding:.75rem .9rem; display:none; }
        .reply-form-wrap textarea { width:100%; padding:.5rem .75rem; border:1px solid #e5e7eb; border-radius:8px; font-size:.84rem; resize:vertical; box-sizing:border-box; }
        .reply-form-wrap textarea:focus { outline:none; border-color:#6366f1; }

        /* Add comment */
        .add-comment-form { background:#fff; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,.07); padding:1.25rem; margin-top:1.25rem; }
        .add-comment-form h3 { margin:0 0 1rem; font-size:.95rem; font-weight:700; color:#111827; }
        .add-comment-form textarea { width:100%; padding:.65rem .9rem; border:1px solid #e5e7eb; border-radius:8px; font-size:.875rem; resize:vertical; box-sizing:border-box; transition:border .15s; }
        .add-comment-form textarea:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
        .cmt-type-row { display:flex; gap:.5rem; margin:.75rem 0; flex-wrap:wrap; }
        .cmt-type-btn { display:flex; align-items:center; gap:.4rem; padding:.4rem .85rem; border:2px solid #e5e7eb; border-radius:8px; cursor:pointer; font-size:.8rem; font-weight:600; transition:all .15s; background:#fafafa; }
        .cmt-type-btn.selected { border-color:#6366f1; background:#f5f3ff; color:#6366f1; }
        .cmt-type-btn.selected-blocker { border-color:#ef4444; background:#fff5f5; color:#ef4444; }
        .cmt-type-input { display:none; }

        /* Flash alert */
        .page-alert { padding:.75rem 1.25rem; border-radius:8px; margin-bottom:1.25rem; font-weight:500; font-size:.875rem; }
        .alert-success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
        .empty-comments { text-align:center; padding:2.5rem; color:#9ca3af; }
        .empty-comments .emoji { font-size:2.5rem; margin-bottom:.5rem; }
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

    <?php if ($_GET['updated'] ?? ''): ?><div class="page-alert alert-success">✅ Project updated successfully.</div><?php endif; ?>
    <?php if ($_GET['team_updated'] ?? ''): ?><div class="page-alert alert-success">✅ Team assignments updated.</div><?php endif; ?>

    <!-- ── HERO ── -->
    <div class="pd-hero">
        <div class="pd-breadcrumb">
            <a href="projects.php">← Projects</a> / <?= Security::escapeHTML($project['name']) ?>
        </div>

        <div class="pd-title-row">
            <h1 class="pd-title"><?= Security::escapeHTML($project['name']) ?></h1>
            <div class="pd-badge-row">
                <span class="badge b-<?= $project['status'] ?>"><?= ucfirst(str_replace('_',' ',$project['status'])) ?></span>
                <span class="badge b-<?= $project['priority'] ?>"><?= ucfirst($project['priority']) ?></span>
                <?php if ($project['department']): ?>
                <span class="badge b-dept">📁 <?= Security::escapeHTML($project['department']) ?></span>
                <?php endif; ?>
                <span class="badge due-<?= str_replace('due-','',$due_class) ?> badge"><?= $due_text ?></span>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="pd-progress-section">
            <div class="pd-prog-header">
                <span>Overall Progress</span>
                <strong><?= $prog ?>%</strong>
            </div>
            <div class="pd-prog-bar"><div class="pd-prog-fill" style="width:<?= $prog ?>%"></div></div>
        </div>

        <!-- Quick stat chips -->
        <div class="pd-chips">
            <span class="chip">👥 <?= count($assignments) ?> team member<?= count($assignments) != 1 ? 's' : '' ?></span>
            <span class="chip">💬 <?= count($top_comments) ?> comment<?= count($top_comments) != 1 ? 's' : '' ?></span>
            <?php if ($blockers > 0): ?>
            <span class="chip red">🚨 <?= $blockers ?> blocker<?= $blockers != 1 ? 's' : '' ?></span>
            <?php endif; ?>
            <?php if ($project['start_date']): ?>
            <span class="chip">🗓 Started <?= date('M j, Y', strtotime($project['start_date'])) ?></span>
            <?php endif; ?>
            <?php if ($project['created_by_name']): ?>
            <span class="chip">✍️ by <?= Security::escapeHTML($project['created_by_name']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── TWO-COLUMN LAYOUT ── -->
    <div class="pd-layout">

        <!-- LEFT: Description + Comments -->
        <div>
            <!-- Description -->
            <div class="pd-card">
                <div class="pd-card-head"><h3>📝 Description</h3></div>
                <div class="pd-card-body">
                    <?php if (!empty(trim($project['description'] ?? ''))): ?>
                    <div class="pd-description"><?= Security::escapeHTML($project['description']) ?></div>
                    <?php else: ?>
                    <div class="pd-no-desc">No description provided.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comments -->
            <div class="pd-card" id="comments">
                <div class="pd-card-head">
                    <h3>💬 Activity &amp; Comments</h3>
                    <span style="font-size:.78rem;color:#9ca3af;"><?= count($top_comments) ?> thread<?= count($top_comments) != 1 ? 's' : '' ?></span>
                </div>
                <div class="pd-card-body">
                    <?php if (empty($top_comments)): ?>
                    <div class="empty-comments">
                        <div class="emoji">💬</div>
                        <p>No comments yet. Share an update below!</p>
                    </div>
                    <?php else: ?>
                    <div class="comment-thread">
                        <?php foreach ($top_comments as $cmt):
                            $cavCol = $avatar_colors[crc32($cmt['username']) % count($avatar_colors)];
                            $cavIni = strtoupper(mb_substr($cmt['username'], 0, 2));
                            $cReplies = $replies[$cmt['id']] ?? [];
                        ?>
                        <div class="comment-item <?= $cmt['is_blocker'] ? 'is-blocker' : '' ?>">
                            <div class="comment-header">
                                <div class="comment-author">
                                    <div class="cmt-av" style="background:<?= $cavCol ?>"><?= $cavIni ?></div>
                                    <span class="cmt-name"><?= Security::escapeHTML($cmt['username']) ?></span>
                                    <?php if ($cmt['is_blocker']): ?>
                                    <span class="blocker-tag">🚨 BLOCKER</span>
                                    <?php endif; ?>
                                </div>
                                <span class="cmt-time"><?= date('M j, Y · g:i A', strtotime($cmt['created_at'])) ?></span>
                            </div>
                            <div class="comment-body"><?= Security::escapeHTML($cmt['comment']) ?></div>
                            <div class="comment-actions">
                                <button type="button" class="reply-btn" onclick="toggleReply(<?= $cmt['id'] ?>)">↩ Reply</button>
                                <?php if (!empty($cReplies)): ?>
                                <button type="button" class="reply-btn" onclick="toggleReplies(<?= $cmt['id'] ?>)" id="show-replies-<?= $cmt['id'] ?>">
                                    ▾ <?= count($cReplies) ?> repl<?= count($cReplies) != 1 ? 'ies' : 'y' ?>
                                </button>
                                <?php endif; ?>
                            </div>

                            <!-- Replies -->
                            <?php if (!empty($cReplies)): ?>
                            <div class="replies-wrap" id="replies-<?= $cmt['id'] ?>" style="display:none;">
                                <?php foreach ($cReplies as $rep):
                                    $rCol = $avatar_colors[crc32($rep['username']) % count($avatar_colors)];
                                    $rIni = strtoupper(mb_substr($rep['username'], 0, 2));
                                ?>
                                <div class="reply-item">
                                    <div class="comment-header">
                                        <div class="comment-author">
                                            <div class="cmt-av" style="background:<?= $rCol ?>"><?= $rIni ?></div>
                                            <span class="cmt-name"><?= Security::escapeHTML($rep['username']) ?></span>
                                        </div>
                                        <span class="cmt-time"><?= date('M j, Y · g:i A', strtotime($rep['created_at'])) ?></span>
                                    </div>
                                    <div class="comment-body"><?= Security::escapeHTML($rep['comment']) ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Reply form -->
                            <div class="reply-form-wrap" id="reply-form-<?= $cmt['id'] ?>">
                                <form method="post">
                                    <?= Security::getCSRFTokenField() ?>
                                    <input type="hidden" name="parent_comment_id" value="<?= $cmt['id'] ?>">
                                    <textarea name="comment" rows="2" placeholder="Write a reply…" required></textarea>
                                    <div style="display:flex;gap:.5rem;margin-top:.5rem;">
                                        <button type="submit" name="add_comment" class="btn btn-small" style="background:#6366f1;color:#fff;border-color:#6366f1;">Post Reply</button>
                                        <button type="button" class="btn btn-small btn-secondary" onclick="toggleReply(<?= $cmt['id'] ?>)">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Add Comment Form -->
                    <div class="add-comment-form">
                        <h3>Add Comment</h3>
                        <form method="post">
                            <?= Security::getCSRFTokenField() ?>
                            <textarea name="comment" rows="3" placeholder="Share an update, ask a question, or flag a blocker…" required id="newCommentText"></textarea>

                            <!-- Type selector -->
                            <div class="cmt-type-row" id="cmtTypeRow">
                                <label class="cmt-type-btn selected" id="typeNormal">
                                    <input type="radio" name="is_blocker" value="0" class="cmt-type-input" checked> 📝 Update
                                </label>
                                <label class="cmt-type-btn" id="typeBlocker">
                                    <input type="radio" name="is_blocker" value="1" class="cmt-type-input"> 🚨 Blocker
                                </label>
                            </div>

                            <button type="submit" name="add_comment" class="btn" style="background:#6366f1;color:#fff;border-color:#6366f1;">
                                Post Comment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Sidebar cards -->
        <div>

            <!-- Project Info -->
            <div class="pd-card">
                <div class="pd-card-head"><h3>📋 Project Info</h3></div>
                <div class="pd-card-body" style="padding:.5rem 1.25rem;">
                    <div class="info-rows">
                        <div class="info-row">
                            <span class="info-icon">🏢</span>
                            <span class="info-label">Client</span>
                            <span class="info-value"><?= Security::escapeHTML($project['client_name'] ?? '—') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon">📁</span>
                            <span class="info-label">Dept</span>
                            <span class="info-value"><?= Security::escapeHTML($project['department'] ?? '—') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon">🏷</span>
                            <span class="info-label">Category</span>
                            <span class="info-value"><?= Security::escapeHTML($project['category'] ?? '—') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon">📅</span>
                            <span class="info-label">Start</span>
                            <span class="info-value"><?= $project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : '—' ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon">⏰</span>
                            <span class="info-label">Due</span>
                            <span class="info-value">
                                <?php if ($project['end_date']): ?>
                                <span class="badge <?= $due_class ?>"><?= $due_text ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon">✍️</span>
                            <span class="info-label">Created by</span>
                            <span class="info-value"><?= Security::escapeHTML($project['created_by_name'] ?? '—') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-icon">🕐</span>
                            <span class="info-label">Added</span>
                            <span class="info-value"><?= date('M j, Y', strtotime($project['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Members -->
            <div class="pd-card">
                <div class="pd-card-head">
                    <h3>👥 Team (<?= count($assignments) ?>)</h3>
                    <?php if (in_array($role, ['admin','manager'])): ?>
                    <button type="button" class="btn btn-small btn-secondary" style="font-size:.75rem;" onclick="toggleManageTeam()">Manage</button>
                    <?php endif; ?>
                </div>
                <div class="pd-card-body">
                    <?php if (empty($assignments)): ?>
                    <div class="no-team">No team members assigned yet.</div>
                    <?php else: ?>
                    <div class="team-grid">
                        <?php foreach ($assignments as $m):
                            $col = $avatar_colors[crc32($m['username']) % count($avatar_colors)];
                            $ini = strtoupper(mb_substr($m['username'], 0, 2));
                        ?>
                        <div class="team-member">
                            <div class="team-av" style="background:<?= $col ?>"><?= $ini ?></div>
                            <div class="team-name"><?= Security::escapeHTML($m['username']) ?></div>
                            <div class="team-dept"><?= Security::escapeHTML($m['department'] ?? '') ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Manage Team Form (collapsed) -->
                    <?php if (in_array($role, ['admin','manager'])): ?>
                    <div id="manageTeamForm" style="display:none;margin-top:1.25rem;border-top:1px solid #f3f4f6;padding-top:1rem;">
                        <p style="font-size:.8rem;color:#6b7280;margin:0 0 .75rem;">Select team members for this project:</p>
                        <input type="text" id="teamSearch" placeholder="🔍 Filter…" oninput="filterTeamPicker(this.value)" style="width:100%;padding:.45rem .75rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.82rem;margin-bottom:.6rem;box-sizing:border-box;">
                        <form method="post">
                            <?= Security::getCSRFTokenField() ?>
                            <div class="team-picker" id="teamPickerGrid">
                                <?php foreach ($all_users as $u):
                                    $col = $avatar_colors[crc32($u['username']) % count($avatar_colors)];
                                    $ini = strtoupper(mb_substr($u['username'], 0, 2));
                                    $checked = in_array($u['id'], $assigned_ids) ? 'checked' : '';
                                ?>
                                <label class="team-pick-item" data-name="<?= strtolower(Security::escapeHTML($u['username'])) ?>">
                                    <input type="checkbox" name="assigned_employees[]" value="<?= $u['id'] ?>" <?= $checked ?>>
                                    <div class="pick-av" style="background:<?= $col ?>"><?= $ini ?></div>
                                    <div>
                                        <strong style="font-size:.8rem;"><?= Security::escapeHTML($u['username']) ?></strong>
                                        <small style="font-size:.7rem;color:#9ca3af;display:block;"><?= Security::escapeHTML($u['department'] ?? '') ?></small>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" name="update_assignments" class="btn" style="background:#6366f1;color:#fff;border-color:#6366f1;width:100%;margin-top:.75rem;">
                                Save Team
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Update Project (admin/manager only) -->
            <?php if (in_array($role, ['admin','manager'])): ?>
            <div class="pd-card">
                <div class="pd-card-head"><h3>✏️ Update Project</h3></div>
                <div class="pd-card-body">
                    <form method="post">
                        <?= Security::getCSRFTokenField() ?>
                        <div class="uf-group">
                            <label>Status</label>
                            <select name="status">
                                <?php foreach (['pending'=>'⏸ Pending','in_progress'=>'▶ In Progress','completed'=>'✓ Completed','on_hold'=>'⏹ On Hold'] as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= $project['status']===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="uf-group">
                            <label>Progress — <span id="progDisplay" style="color:#6366f1;font-weight:700;"><?= $prog ?>%</span></label>
                            <div class="prog-row">
                                <input type="range" class="prog-slider" name="progress" id="progSlider"
                                       min="0" max="100" step="5" value="<?= $prog ?>"
                                       oninput="document.getElementById('progDisplay').textContent=this.value+'%'">
                                <span class="prog-pct" id="progPct"><?= $prog ?>%</span>
                            </div>
                        </div>
                        <div class="uf-group">
                            <label>Description</label>
                            <textarea name="description" rows="4" placeholder="Update the project description…"><?= Security::escapeHTML($project['description'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" name="update_progress" class="btn" style="background:#6366f1;color:#fff;border-color:#6366f1;width:100%;">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.right column -->
    </div><!-- /.pd-layout -->

</div><!-- /.main-content -->

<script src="../js/notification.js"></script>
<script>
// Reply form toggle
function toggleReply(id) {
    const f = document.getElementById('reply-form-' + id);
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
    if (f.style.display === 'block') f.querySelector('textarea').focus();
}

// Replies visibility toggle
function toggleReplies(id) {
    const r   = document.getElementById('replies-' + id);
    const btn = document.getElementById('show-replies-' + id);
    if (!r) return;
    const open = r.style.display !== 'none';
    r.style.display   = open ? 'none' : 'flex';
    r.style.flexDirection = 'column';
    r.style.gap = '.6rem';
    btn.textContent = open
        ? btn.textContent.replace('▴','▾')
        : btn.textContent.replace('▾','▴');
}

// Show replies by default if there are any
document.querySelectorAll('[id^="replies-"]').forEach(el => {
    el.style.display = 'flex';
    el.style.flexDirection = 'column';
    el.style.gap = '.6rem';
    const id  = el.id.replace('replies-','');
    const btn = document.getElementById('show-replies-' + id);
    if (btn) btn.textContent = btn.textContent.replace('▾','▴');
});

// Manage team toggle
function toggleManageTeam() {
    const f = document.getElementById('manageTeamForm');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}

// Team picker filter
function filterTeamPicker(q) {
    document.querySelectorAll('#teamPickerGrid .team-pick-item').forEach(item => {
        item.style.display = item.dataset.name.includes(q.toLowerCase()) ? '' : 'none';
    });
}

// Comment type selector
document.querySelectorAll('.cmt-type-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.cmt-type-btn').forEach(b => b.classList.remove('selected','selected-blocker'));
        const isBlocker = btn.id === 'typeBlocker';
        btn.classList.add(isBlocker ? 'selected-blocker' : 'selected');
        btn.querySelector('input').checked = true;
    });
});

// Progress slider sync
const slider = document.getElementById('progSlider');
const pct    = document.getElementById('progPct');
if (slider) slider.addEventListener('input', () => pct.textContent = slider.value + '%');

// Auto-dismiss flash
setTimeout(() => document.querySelectorAll('.page-alert').forEach(el => {
    el.style.transition = 'opacity .5s'; el.style.opacity = 0;
    setTimeout(() => el.remove(), 500);
}), 3000);
</script>
</body>
</html>
