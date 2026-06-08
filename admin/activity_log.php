<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$sess_uid  = (int)$_SESSION['user_id'];
$sess_role = $_SESSION['role'] ?? 'staff';
$is_admin  = in_array($sess_role, ['admin', 'manager']);

// ── Filters (admins only get full filter controls) ────────────────────────────
$f_user   = $is_admin && isset($_GET['user_id'])      ? (int)$_GET['user_id']                              : 0;
$f_dept   = $is_admin && isset($_GET['department'])    ? Security::sanitizeInput($_GET['department'])       : '';
$f_type   = isset($_GET['activity_type'])              ? Security::sanitizeInput($_GET['activity_type'])    : '';
$f_from   = isset($_GET['date_from'])                  ? Security::sanitizeInput($_GET['date_from'])        : '';
$f_to     = isset($_GET['date_to'])                    ? Security::sanitizeInput($_GET['date_to'])          : '';
$f_limit  = isset($_GET['limit'])  ? min(500,(int)$_GET['limit'])               : 50;
$f_search = isset($_GET['search']) ? Security::sanitizeInput($_GET['search'])    : '';
$f_page   = isset($_GET['page'])   ? max(1,(int)$_GET['page'])                  : 1;

// Build WHERE conditions
$where = [];
$params = [];

// Non-admins always restricted to their own records
if (!$is_admin) {
    $where[] = "ua.user_id = ?";
    $params[] = $sess_uid;
} else {
    if ($f_user)  { $where[] = "ua.user_id = ?";  $params[] = $f_user; }
    if ($f_dept)  { $where[] = "u.department = ?"; $params[] = $f_dept; }
}
if ($f_type)   { $where[] = "ua.activity_type = ?";                       $params[] = $f_type; }
if ($f_from)   { $where[] = "DATE(ua.created_at) >= ?";                   $params[] = $f_from; }
if ($f_to)     { $where[] = "DATE(ua.created_at) <= ?";                   $params[] = $f_to; }
if ($f_search) { $where[] = "(ua.description LIKE ? OR ua.username LIKE ?)"; $params[] = "%$f_search%"; $params[] = "%$f_search%"; }

$wc = $where ? "WHERE " . implode(" AND ", $where) : "";

// Total count for pagination
$count_stmt = $db->prepare("SELECT COUNT(*) FROM user_activities ua LEFT JOIN users u ON ua.user_id=u.id $wc");
$count_stmt->execute($params);
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_rows / $f_limit));
$f_page      = min($f_page, $total_pages);
$offset      = ($f_page - 1) * $f_limit;

$stmt = $db->prepare("SELECT ua.id, ua.user_id, ua.username, ua.activity_type, ua.description,
    ua.page_url, ua.resource_type, ua.resource_id, ua.ip_address, ua.created_at,
    u.email, u.department
    FROM user_activities ua
    LEFT JOIN users u ON ua.user_id = u.id
    $wc
    ORDER BY ua.created_at DESC
    LIMIT $f_limit OFFSET $offset");
$stmt->execute($params);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Stats ─────────────────────────────────────────────────────────────────────
$total_logged = (int)$db->query("SELECT COUNT(*) FROM user_activities")->fetchColumn();
$today_count  = (int)$db->query("SELECT COUNT(*) FROM user_activities WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$users_today  = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM user_activities WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$last_7_days  = (int)$db->query("SELECT COUNT(*) FROM user_activities WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// Filter select options (admin only)
if ($is_admin) {
    $f_activity_types = $db->query("SELECT DISTINCT activity_type FROM user_activities WHERE activity_type IS NOT NULL ORDER BY activity_type")->fetchAll(PDO::FETCH_COLUMN);
    $f_users          = $db->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
    $f_depts          = $db->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
} else {
    $s = $db->prepare("SELECT DISTINCT activity_type FROM user_activities WHERE user_id=? AND activity_type IS NOT NULL ORDER BY activity_type");
    $s->execute([$sess_uid]);
    $f_activity_types = $s->fetchAll(PDO::FETCH_COLUMN);
    $f_users = [];
    $f_depts = [];
}

// Activity type → colour map
$type_colours = [
    'login'       => ['#dbeafe','#1e40af'],
    'logout'      => ['#f3f4f6','#374151'],
    'create'      => ['#d1fae5','#065f46'],
    'update'      => ['#fef3c7','#92400e'],
    'delete'      => ['#fee2e2','#991b1b'],
    'view'        => ['#ede9fe','#5b21b6'],
    'upload'      => ['#fce7f3','#9d174d'],
    'download'    => ['#e0f2fe','#075985'],
    'page_view'   => ['#f1f5f9','#475569'],
];
function type_badge($type, $map) {
    $t = strtolower($type ?? 'other');
    $key = $map[$t] ?? ['#f3f4f6','#374151'];
    return "<span style=\"padding:2px 9px;border-radius:20px;font-size:.72rem;font-weight:700;background:{$key[0]};color:{$key[1]};\">" . htmlspecialchars(ucfirst($type)) . "</span>";
}

$asset_base = '../'; $nav_base = '../departments/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - KConsulting Hub</title>
    <link rel="icon" type="image/png" href="../img/KConsultingLogo1.png">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        :root {
            --al:      #0f172a;
            --al-red:  #dc2626;
            --al-grad: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        .al-hero { background: var(--al-grad); border-radius: 16px; padding: 26px 32px; display: flex; align-items: center; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .al-hero-icon { font-size: 2.6rem; }
        .al-hero-info h1 { color: #fff; font-size: 1.5rem; font-weight: 800; margin: 0 0 3px; }
        .al-hero-info p  { color: rgba(255,255,255,.6); font-size: .86rem; margin: 0; }
        .al-hero-badge { margin-left: auto; background: rgba(255,255,255,.1); color: rgba(255,255,255,.85); border: 1px solid rgba(255,255,255,.2); border-radius: 20px; padding: 5px 14px; font-size: .78rem; font-weight: 600; }

        .al-kpi { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 20px; }
        @media(max-width:800px){ .al-kpi { grid-template-columns: 1fr 1fr; } }
        .al-kpi-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 20px; border-left: 4px solid transparent; }
        .al-kpi-card.slate { border-left-color: #475569; }
        .al-kpi-card.blue  { border-left-color: #2563eb; }
        .al-kpi-card.green { border-left-color: #059669; }
        .al-kpi-card.amber { border-left-color: #d97706; }
        .al-kpi-card .num { font-size: 1.7rem; font-weight: 800; color: #111827; }
        .al-kpi-card .lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; font-weight: 600; margin-top: 3px; }

        .al-filter { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; }
        .al-filter h4 { font-size: .86rem; font-weight: 700; color: #374151; margin: 0 0 12px; }
        .al-filter-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(180px,1fr)); gap: 10px; margin-bottom: 12px; }
        .al-filter-grid label { display: block; font-size: .75rem; color: #6b7280; font-weight: 600; margin-bottom: 4px; }
        .al-filter-grid select, .al-filter-grid input { width: 100%; padding: 7px 10px; border: 1px solid #d1d5db; border-radius: 7px; font-size: .84rem; color: #374151; box-sizing: border-box; }
        .al-search { border: 1px solid #d1d5db; border-radius: 7px; padding: 7px 12px; font-size: .84rem; width: 260px; }
        .al-btn { padding: 7px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: .84rem; font-weight: 600; }
        .al-btn-primary { background: var(--al); color: #fff; }
        .al-btn-ghost   { background: transparent; border: 1px solid #d1d5db; color: #374151; text-decoration: none; display: inline-block; }

        .al-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
        .al-card-head { padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f3f4f6; }
        .al-card-head h3 { font-size: .95rem; font-weight: 700; color: #111827; margin: 0; }
        .al-card-sub { font-size: .78rem; color: #9ca3af; }
        .al-tbl { width: 100%; border-collapse: collapse; font-size: .84rem; }
        .al-tbl thead tr { background: var(--al); color: #fff; }
        .al-tbl th { padding: 9px 14px; text-align: left; font-size: .71rem; text-transform: uppercase; letter-spacing: .4px; font-weight: 600; }
        .al-tbl td { padding: 9px 14px; color: #374151; border-bottom: 1px solid #f9fafb; vertical-align: top; }
        .al-tbl tbody tr:hover { background: #f8fafc; }
        .al-tbl tbody tr:last-child td { border-bottom: none; }

        .al-user-chip { display: flex; align-items: center; gap: 7px; }
        .al-avatar { width: 26px; height: 26px; border-radius: 50%; background: #4f46e5; color: #fff; font-size: .68rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .al-dept-badge { padding: 2px 8px; border-radius: 20px; font-size: .7rem; font-weight: 700; }
        .al-empty { text-align: center; padding: 48px; color: #9ca3af; }
        .al-empty h3 { font-size: 1rem; font-weight: 700; color: #374151; margin: 0 0 6px; }
        .al-empty p { font-size: .85rem; margin: 0; }

        .al-tag-row { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
        .al-tag { padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600; background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
        .al-tag.active { background: #0f172a; color: #fff; border-color: #0f172a; cursor: pointer; }

        /* Dept colour map */
        .dept-IT             { background: #dbeafe; color: #1e40af; }
        .dept-Marketing      { background: #f3e5f5; color: #7b1fa2; }
        .dept-Finance        { background: #d1fae5; color: #065f46; }
        .dept-HR             { background: #fef3c7; color: #92400e; }
        .dept-Clients        { background: #fce7f3; color: #9d174d; }
        .dept-Insights       { background: #f0fdf4; color: #166534; }
        .dept-Business\ Development { background: #e0f2fe; color: #075985; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">

        <!-- Hero -->
        <div class="al-hero">
            <div class="al-hero-icon">📋</div>
            <div class="al-hero-info">
                <h1>Activity Log</h1>
                <p><?= $is_admin ? 'System-wide audit trail of all user actions' : 'Your personal activity history' ?></p>
            </div>
            <?php if ($is_admin): ?>
            <div class="al-hero-badge">Admin View</div>
            <?php endif; ?>
        </div>

        <!-- KPI -->
        <div class="al-kpi">
            <div class="al-kpi-card slate">
                <div class="num"><?= number_format($total_logged) ?></div>
                <div class="lbl">Total Events</div>
            </div>
            <div class="al-kpi-card blue">
                <div class="num"><?= number_format($last_7_days) ?></div>
                <div class="lbl">Last 7 Days</div>
            </div>
            <div class="al-kpi-card green">
                <div class="num"><?= number_format($today_count) ?></div>
                <div class="lbl">Today</div>
            </div>
            <div class="al-kpi-card amber">
                <div class="num"><?= number_format($users_today) ?></div>
                <div class="lbl">Active Users Today</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="al-filter">
            <h4>🔍 Filter</h4>
            <form method="GET">
                <div class="al-filter-grid">
                    <div>
                        <label>Search</label>
                        <input type="text" name="search" value="<?= Security::escapeHTML($f_search) ?>" placeholder="Description or user…">
                    </div>
                    <?php if ($is_admin): ?>
                    <div>
                        <label>User</label>
                        <select name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($f_users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $f_user===$u['id']?'selected':'' ?>><?= Security::escapeHTML($u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Department</label>
                        <select name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($f_depts as $d): ?>
                            <option value="<?= Security::escapeHTML($d) ?>" <?= $f_dept===$d?'selected':'' ?>><?= Security::escapeHTML($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label>Activity Type</label>
                        <select name="activity_type">
                            <option value="">All Types</option>
                            <?php foreach ($f_activity_types as $t): ?>
                            <option value="<?= Security::escapeHTML($t) ?>" <?= $f_type===$t?'selected':'' ?>><?= Security::escapeHTML(ucfirst($t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>From Date</label>
                        <div style="display:flex;gap:5px;align-items:center;">
                            <input type="date" name="date_from" id="dateFrom" value="<?= Security::escapeHTML($f_from) ?>" style="flex:1;">
                            <?php if ($f_from): ?><button type="button" onclick="clearDate('dateFrom')" title="Clear" style="border:none;background:none;cursor:pointer;color:#9ca3af;font-size:1rem;padding:0 2px;">×</button><?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label>To Date</label>
                        <div style="display:flex;gap:5px;align-items:center;">
                            <input type="date" name="date_to" id="dateTo" value="<?= Security::escapeHTML($f_to) ?>" style="flex:1;">
                            <?php if ($f_to): ?><button type="button" onclick="clearDate('dateTo')" title="Clear" style="border:none;background:none;cursor:pointer;color:#9ca3af;font-size:1rem;padding:0 2px;">×</button><?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label>Rows</label>
                        <select name="limit">
                            <?php foreach ([25,50,100,250,500] as $n): ?>
                            <option value="<?= $n ?>" <?= $f_limit===$n?'selected':'' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;">
                    <button type="submit" class="al-btn al-btn-primary">Apply</button>
                    <a href="activity_log.php" class="al-btn al-btn-ghost">Reset</a>
                    <span style="font-size:.78rem;color:#9ca3af;margin-left:6px;">
                        Showing <?= number_format(($f_page-1)*$f_limit+1) ?>–<?= number_format(min($f_page*$f_limit,$total_rows)) ?> of <?= number_format($total_rows) ?> records
                    </span>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="al-card">
            <div class="al-card-head">
                <h3>📋 Activity Records</h3>
                <span class="al-card-sub">Page <?= $f_page ?> of <?= $total_pages ?> &bull; <?= number_format($total_rows) ?> total</span>
            </div>
            <?php if (empty($activities)): ?>
            <div class="al-empty">
                <h3>No activities found</h3>
                <p>No records match your current filters<?= !$is_admin ? ' — your activity will appear here as you use the portal' : '' ?>.</p>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="al-tbl">
                    <thead><tr>
                        <th>Date &amp; Time</th>
                        <?= $is_admin ? '<th>User</th><th>Dept</th>' : '' ?>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Target</th>
                        <th>IP</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($activities as $a):
                        $initials = strtoupper(mb_substr($a['username'] ?? 'U', 0, 2));
                    ?>
                    <tr>
                        <td style="white-space:nowrap;">
                            <div style="font-weight:600;font-size:.83rem;color:#111827;"><?= date('d M Y', strtotime($a['created_at'])) ?></div>
                            <div style="font-size:.76rem;color:#9ca3af;"><?= date('H:i:s', strtotime($a['created_at'])) ?></div>
                        </td>
                        <?php if ($is_admin): ?>
                        <td>
                            <div class="al-user-chip">
                                <div class="al-avatar"><?= Security::escapeHTML($initials) ?></div>
                                <div>
                                    <div style="font-weight:600;font-size:.83rem;color:#111827;"><?= Security::escapeHTML($a['username'] ?? '—') ?></div>
                                    <?php if ($a['email']): ?><div style="font-size:.73rem;color:#9ca3af;"><?= Security::escapeHTML($a['email']) ?></div><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($a['department']): ?>
                            <span class="al-dept-badge dept-<?= Security::escapeHTML($a['department']) ?>"><?= Security::escapeHTML($a['department']) ?></span>
                            <?php else: ?>
                            <span style="color:#9ca3af;">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td><?= type_badge($a['activity_type'], $type_colours) ?></td>
                        <td style="max-width:320px;word-break:break-word;color:#374151;"><?= Security::escapeHTML($a['description'] ?? '') ?></td>
                        <td style="font-size:.78rem;color:#6b7280;">
                            <?php if ($a['resource_type'] && $a['resource_id']): ?>
                                <div><?= Security::escapeHTML(ucfirst($a['resource_type'])) ?> #<?= (int)$a['resource_id'] ?></div>
                            <?php elseif ($a['page_url']): ?>
                                <div style="color:#9ca3af;font-size:.74rem;"><?= Security::escapeHTML($a['page_url']) ?></div>
                            <?php else: ?>
                                <span style="color:#d1d5db;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap;"><?= Security::escapeHTML($a['ip_address'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1):
            // Build query string without page so we can append it
            $qp = $_GET;
            unset($qp['page']);
            $qs = http_build_query($qp);
            $qs = $qs ? $qs . '&' : '';

            // Window: show up to 7 page buttons
            $win   = 3;
            $start = max(1, $f_page - $win);
            $end   = min($total_pages, $f_page + $win);
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:16px;">
            <span style="font-size:.82rem;color:#6b7280;">
                Showing rows <?= number_format(($f_page-1)*$f_limit+1) ?>–<?= number_format(min($f_page*$f_limit,$total_rows)) ?> of <?= number_format($total_rows) ?>
            </span>
            <div style="display:flex;align-items:center;gap:4px;">
                <?php if ($f_page > 1): ?>
                <a href="?<?= $qs ?>page=1" class="pg-btn">«</a>
                <a href="?<?= $qs ?>page=<?= $f_page-1 ?>" class="pg-btn">‹</a>
                <?php endif; ?>

                <?php if ($start > 1): ?>
                <a href="?<?= $qs ?>page=1" class="pg-btn">1</a>
                <?php if ($start > 2): ?><span class="pg-ellipsis">…</span><?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $start; $p <= $end; $p++): ?>
                <a href="?<?= $qs ?>page=<?= $p ?>" class="pg-btn <?= $p===$f_page?'pg-active':'' ?>"><?= $p ?></a>
                <?php endfor; ?>

                <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?><span class="pg-ellipsis">…</span><?php endif; ?>
                <a href="?<?= $qs ?>page=<?= $total_pages ?>" class="pg-btn"><?= $total_pages ?></a>
                <?php endif; ?>

                <?php if ($f_page < $total_pages): ?>
                <a href="?<?= $qs ?>page=<?= $f_page+1 ?>" class="pg-btn">›</a>
                <a href="?<?= $qs ?>page=<?= $total_pages ?>" class="pg-btn">»</a>
                <?php endif; ?>
            </div>
        </div>

        <style>
        .pg-btn { display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 8px;border:1px solid #e5e7eb;border-radius:7px;background:#fff;color:#374151;font-size:.84rem;font-weight:600;text-decoration:none;transition:all .15s; }
        .pg-btn:hover { background:#f3f4f6;border-color:#d1d5db; }
        .pg-active { background:var(--al);color:#fff;border-color:var(--al); }
        .pg-active:hover { background:#1e293b; }
        .pg-ellipsis { color:#9ca3af;font-size:.84rem;padding:0 4px; }
        </style>
        <?php endif; ?>

    </div><!-- /.main-content -->

<script>
function clearDate(id) {
    const el = document.getElementById(id);
    if (el) { el.value = ''; el.closest('form').submit(); }
}
</script>
</body>
</html>

