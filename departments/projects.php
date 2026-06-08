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

$role = $_SESSION['role'];

// Handle new project creation
if ($_POST && isset($_POST['create_project'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin', 'manager'])) {
        http_response_code(403); die('Access denied.');
    }

    $name        = Security::sanitizeInput($_POST['name']);
    $description = Security::sanitizeInput($_POST['description']);
    $client_id   = (int)$_POST['client_id'];
    $category    = Security::sanitizeInput($_POST['category']);
    $priority    = Security::sanitizeInput($_POST['priority']);
    $start_date  = Security::sanitizeInput($_POST['start_date']);
    $end_date    = Security::sanitizeInput($_POST['end_date']);
    $department  = Security::sanitizeInput($_POST['project_department'] ?? $_SESSION['department'] ?? '');
    $assigned_employees = $_POST['assigned_employees'] ?? [];

    $query = "INSERT INTO projects (name, description, client_id, department, created_by, category, priority, start_date, end_date)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$name, $description, $client_id, $department, $_SESSION['user_id'], $category, $priority, $start_date, $end_date]);
    $project_id = (int)$db->lastInsertId();

    foreach ($assigned_employees as $uid) {
        if (!empty($uid)) {
            $s = $db->prepare("INSERT INTO project_assignments (project_id, user_id) VALUES (?, ?)");
            $s->execute([$project_id, (int)$uid]);
        }
    }

    try {
        require_once '../includes/ActivityLogger.php';
        $logger = new ActivityLogger($db);
        $logger->logCreate('project', $project_id, "Created project: $name");
    } catch (Exception $e) {}

    header("Location: projects.php");
    exit();
}

// Handle project progress/status update
if ($_POST && isset($_POST['update_project'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin', 'manager'])) {
        http_response_code(403); die('Access denied.');
    }
    $project_id = (int)$_POST['project_id'];
    $progress   = (int)$_POST['progress'];
    $status     = Security::sanitizeInput($_POST['status']);
    $s = $db->prepare("UPDATE projects SET progress = ?, status = ? WHERE id = ?");
    $s->execute([$progress, $status, $project_id]);
}

// Handle comment
if ($_POST && isset($_POST['add_comment'])) {
    Security::checkCSRFToken();
    $project_id      = (int)$_POST['project_id'];
    $comment         = Security::sanitizeInput($_POST['comment']);
    $is_blocker      = isset($_POST['is_blocker']) ? 1 : 0;
    $parent_cmt_id   = !empty($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null;
    $s = $db->prepare("INSERT INTO project_comments (project_id, user_id, comment, is_blocker, parent_comment_id) VALUES (?, ?, ?, ?, ?)");
    $s->execute([$project_id, $_SESSION['user_id'], $comment, $is_blocker, $parent_cmt_id]);
}

// Handle assignment update
if ($_POST && isset($_POST['update_assignments'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin', 'manager'])) {
        http_response_code(403); die('Access denied.');
    }
    $project_id = (int)$_POST['project_id'];
    $assigned   = $_POST['assigned_employees'] ?? [];
    $db->prepare("DELETE FROM project_assignments WHERE project_id = ?")->execute([$project_id]);
    foreach ($assigned as $uid) {
        if (!empty($uid)) {
            $s = $db->prepare("INSERT INTO project_assignments (project_id, user_id) VALUES (?, ?)");
            $s->execute([$project_id, (int)$uid]);
        }
    }
}

// Filters
$filter_status   = $_GET['filter_status']   ?? '';
$filter_priority = $_GET['filter_priority'] ?? '';
$filter_category = $_GET['filter_category'] ?? '';
$filter_assigned = $_GET['filter_assigned'] ?? '';

$where_conditions = [];
$params = [];
if (!empty($filter_status))   { $where_conditions[] = "p.status = ?";    $params[] = $filter_status; }
if (!empty($filter_priority)) { $where_conditions[] = "p.priority = ?";  $params[] = $filter_priority; }
if (!empty($filter_category)) { $where_conditions[] = "p.category = ?";  $params[] = $filter_category; }
if (!empty($filter_assigned)) { $where_conditions[] = "pa.user_id = ?";  $params[] = $filter_assigned; }
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$query = "SELECT p.*, c.name as client_name,
          COUNT(DISTINCT pc.id) as comment_count,
          COUNT(DISTINCT CASE WHEN pc.is_blocker = 1 THEN pc.id END) as blocker_count,
          COUNT(DISTINCT pa.user_id) as team_count
          FROM projects p
          LEFT JOIN clients c ON p.client_id = c.id
          LEFT JOIN project_comments pc ON p.id = pc.project_id
          LEFT JOIN project_assignments pa ON p.id = pa.project_id
          $where_clause
          GROUP BY p.id, c.name
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Assignments per project
$assignments = [];
if (!empty($projects)) {
    $ids = array_column($projects, 'id');
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $s = $db->prepare("SELECT pa.*, u.username, u.email FROM project_assignments pa JOIN users u ON pa.user_id = u.id WHERE pa.project_id IN ($ph) ORDER BY pa.project_id, u.username");
    $s->execute($ids);
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $a) {
        $assignments[$a['project_id']][] = $a;
    }
}

// Clients for dropdown
$clients = $db->query("SELECT id, name FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// All users for assignment dropdown
$all_users = $db->query("SELECT id, username, email FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Categories
$categories = $db->query("SELECT DISTINCT category FROM projects WHERE category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN, 0);

$statuses  = ['pending', 'in_progress', 'completed', 'on_hold'];
$priorities = ['low', 'medium', 'high'];
$view = $_GET['view'] ?? 'projects';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management - KConsulting Hub</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .nav-tabs { display:flex; background:#fff; border-radius:8px; margin-bottom:2rem; box-shadow:0 2px 4px rgba(0,0,0,.1); overflow:hidden; }
        .nav-tab  { flex:1; padding:1rem 2rem; text-decoration:none; color:#666; background:#fff; border:none; border-right:1px solid #eee; text-align:center; font-weight:500; transition:all .3s; }
        .nav-tab:last-child { border-right:none; }
        .nav-tab.active { background:#007bff; color:#fff; }
        .nav-tab:hover:not(.active) { background:#f8f9fa; }

        .project-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(400px,1fr)); gap:1.5rem; margin-top:1rem; }
        .project-card { border:1px solid #e0e0e0; border-radius:12px; padding:1.5rem; background:#fff; transition:all .3s; box-shadow:0 2px 8px rgba(0,0,0,.08); cursor:pointer; height:fit-content; }
        .project-card:hover { transform:translateY(-4px); box-shadow:0 4px 16px rgba(0,0,0,.12); border-color:#007bff; }

        .status-badge,.priority-badge { display:inline-block; padding:.35rem .75rem; border-radius:20px; font-size:.8rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
        .status-pending    { background:#fff3cd; color:#856404; border:1px solid #ffeaa7; }
        .status-in_progress{ background:#cce5ff; color:#004085; border:1px solid #b3d7ff; }
        .status-completed  { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .status-on_hold    { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
        .priority-low    { background:#d1ecf1; color:#0c5460; border:1px solid #bee5eb; }
        .priority-medium { background:#fff3cd; color:#856404; border:1px solid #ffeaa7; }
        .priority-high   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
        .priority-urgent { background:#dc3545; color:#fff;    border:1px solid #c82333; }

        .progress-bar  { width:100%; height:8px; background:#f0f0f0; border-radius:10px; overflow:hidden; margin:.5rem 0; }
        .progress-fill { height:100%; background:linear-gradient(90deg,#4CAF50,#45a049); border-radius:10px; transition:width .5s; }

        @media(max-width:768px){ .project-grid{ grid-template-columns:1fr; } }
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
        <?php
        $total_projects    = count($projects);
        $completed_count   = count(array_filter($projects, fn($p) => $p['status'] === 'completed'));
        $in_progress_count = count(array_filter($projects, fn($p) => $p['status'] === 'in_progress'));
        $avg_progress      = $total_projects > 0 ? array_sum(array_column($projects, 'progress')) / $total_projects : 0;
        $total_blockers    = array_sum(array_column($projects, 'blocker_count'));
        ?>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?= $total_projects ?></div><div class="stat-label">Total Projects</div></div>
            <div class="stat-card"><div class="stat-number"><?= $in_progress_count ?></div><div class="stat-label">In Progress</div></div>
            <div class="stat-card"><div class="stat-number"><?= $completed_count ?></div><div class="stat-label">Completed</div></div>
            <div class="stat-card"><div class="stat-number"><?= round($avg_progress) ?>%</div><div class="stat-label">Avg Progress</div></div>
            <div class="stat-card"><div class="stat-number" style="color:<?= $total_blockers > 0 ? '#dc3545' : '#28a745' ?>"><?= $total_blockers ?></div><div class="stat-label">Blockers</div></div>
        </div>

        <div class="nav-tabs">
            <a href="?view=projects" class="nav-tab <?= $view === 'projects' ? 'active' : '' ?>">📋 All Projects</a>
            <?php if (in_array($role, ['admin', 'manager'])): ?>
            <a href="?view=create"   class="nav-tab <?= $view === 'create'   ? 'active' : '' ?>">➕ New Project</a>
            <?php endif; ?>
        </div>

        <?php if ($view === 'projects'): ?>
        <!-- Filters -->
        <div class="section">
            <div class="section-header">🔍 Filter Projects</div>
            <div class="section-content">
                <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
                    <input type="hidden" name="view" value="projects">
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="filter_status">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority:</label>
                        <select name="filter_priority">
                            <option value="">All Priorities</option>
                            <?php foreach ($priorities as $p): ?>
                            <option value="<?= $p ?>" <?= $filter_priority === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category:</label>
                        <select name="filter_category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= Security::escapeHTML($cat) ?>" <?= $filter_category === $cat ? 'selected' : '' ?>><?= Security::escapeHTML($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Assigned To:</label>
                        <select name="filter_assigned">
                            <option value="">All Members</option>
                            <?php foreach ($all_users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $filter_assigned == $u['id'] ? 'selected' : '' ?>><?= Security::escapeHTML($u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex;align-items:end;gap:.5rem;">
                        <button type="submit" class="btn">Apply</button>
                        <a href="projects.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Project List -->
        <div class="section">
            <div class="section-header">
                📋 Projects
                <?php if (!empty($filter_status) || !empty($filter_priority) || !empty($filter_category) || !empty($filter_assigned)): ?>
                <span style="font-size:.8rem;color:#666;">(Filtered — <?= count($projects) ?>)</span>
                <?php endif; ?>
            </div>
            <div class="section-content">
                <?php if (!empty($projects)): ?>
                <div class="project-grid">
                    <?php foreach ($projects as $project): ?>
                    <div class="project-card" onclick="window.location.href='project_detail.php?id=<?= $project['id'] ?>'">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
                            <h3 style="margin:0;color:#333;font-size:1.1rem;line-height:1.4;flex:1;">
                                <a href="project_detail.php?id=<?= $project['id'] ?>" style="text-decoration:none;color:inherit;"><?= Security::escapeHTML($project['name']) ?></a>
                            </h3>
                            <div style="display:flex;flex-direction:column;gap:.5rem;align-items:flex-end;">
                                <span class="status-badge status-<?= $project['status'] ?>"><?= ucfirst(str_replace('_', ' ', $project['status'])) ?></span>
                                <span class="priority-badge priority-<?= $project['priority'] ?>"><?= ucfirst($project['priority']) ?></span>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem;font-size:.85rem;">
                            <div>
                                <strong style="color:#666;">Client:</strong><br>
                                <span><?= Security::escapeHTML($project['client_name'] ?? 'No client') ?></span>
                            </div>
                            <div>
                                <strong style="color:#666;">Category:</strong><br>
                                <span><?= Security::escapeHTML($project['category'] ?? '—') ?></span>
                            </div>
                            <div>
                                <strong style="color:#666;">Team:</strong><br>
                                <span><?= (int)$project['team_count'] ?> member<?= $project['team_count'] != 1 ? 's' : '' ?></span>
                            </div>
                            <div>
                                <strong style="color:#666;">Created:</strong><br>
                                <span><?= date('M j, Y', strtotime($project['created_at'])) ?></span>
                            </div>
                        </div>

                        <div style="margin-bottom:1rem;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;">
                                <strong style="font-size:.9rem;">Progress: <?= (int)$project['progress'] ?>%</strong>
                                <?php if ($project['end_date']): ?>
                                <span style="font-size:.75rem;color:#666;">Due: <?= date('M j, Y', strtotime($project['end_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="progress-bar"><div class="progress-fill" style="width:<?= (int)$project['progress'] ?>%"></div></div>
                        </div>

                        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:.75rem;border-top:1px solid #f0f0f0;">
                            <div style="display:flex;gap:1rem;font-size:.8rem;">
                                <span style="color:#666;">💬 <?= (int)$project['comment_count'] ?></span>
                                <?php if ($project['blocker_count'] > 0): ?>
                                <span style="color:#dc3545;font-weight:600;">🚨 <?= (int)$project['blocker_count'] ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="project_detail.php?id=<?= $project['id'] ?>" class="btn btn-small" style="font-size:.8rem;padding:.4rem .8rem;">View →</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align:center;padding:3rem;color:#666;background:#fff;border-radius:8px;border:2px dashed #ddd;">
                    <h3 style="color:#999;margin-bottom:1rem;">📭 No Projects Found</h3>
                    <p style="margin-bottom:1.5rem;">
                        <?= (!empty($filter_status) || !empty($filter_priority) || !empty($filter_category) || !empty($filter_assigned)) ? 'No projects match your filters.' : 'No projects have been created yet.' ?>
                    </p>
                    <div style="display:flex;gap:1rem;justify-content:center;">
                        <?php if (!empty($filter_status) || !empty($filter_priority) || !empty($filter_category) || !empty($filter_assigned)): ?>
                        <a href="projects.php" class="btn">Clear Filters</a>
                        <?php endif; ?>
                        <?php if (in_array($role, ['admin', 'manager'])): ?>
                        <a href="?view=create" class="btn btn-primary">➕ Create First Project</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($view === 'create' && in_array($role, ['admin', 'manager'])): ?>
        <div class="section">
            <div class="section-header">➕ Create New Project</div>
            <div class="section-content">
                <form method="post">
                    <?= Security::getCSRFTokenField() ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Project Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="client_id">Client *</label>
                            <select id="client_id" name="client_id" required>
                                <option value="">Select Client</option>
                                <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= Security::escapeHTML($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="project_department">Department *</label>
                            <select id="project_department" name="project_department" required>
                                <option value="">Select Department</option>
                                <?php foreach (['IT','Marketing','Business Development','Finance','HR','Clients'] as $d): ?>
                                <option value="<?= $d ?>" <?= ($d === ($_SESSION['department'] ?? '')) ? 'selected' : '' ?>><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="category">Category *</label>
                            <select id="category" name="category" required>
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
                        <div class="form-group">
                            <label for="priority">Priority *</label>
                            <select id="priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="start_date">Start Date *</label>
                            <input type="date" id="start_date" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date *</label>
                            <input type="date" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Assign Team Members</label>
                        <div id="employee-assignments">
                            <div class="assignment-row" style="display:flex;gap:1rem;margin-bottom:1rem;align-items:center;">
                                <select name="assigned_employees[]" style="flex:1;">
                                    <option value="">Select Team Member</option>
                                    <?php foreach ($all_users as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= Security::escapeHTML($u['username']) ?> &lt;<?= Security::escapeHTML($u['email']) ?>&gt;</option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" onclick="addMember()" class="btn btn-small">+ Add</button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="create_project" class="btn">Create Project</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="../js/notification.js"></script>
    <script>
    function addMember() {
        const container = document.getElementById('employee-assignments');
        const row = container.firstElementChild.cloneNode(true);
        row.querySelector('select').selectedIndex = 0;
        container.appendChild(row);
    }
    </script>
</body>
</html>
