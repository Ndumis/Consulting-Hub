<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';
require_once '../includes/page_tracker.php';

// Check department access
Security::requireDepartmentAccess('IT');

$database = new Database();
$db = $database->getConnection();

// Handle new project creation with employee assignments
if ($_POST && isset($_POST['create_project'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('IT');
    
    $name = Security::sanitizeInput($_POST['name']);
    $description = Security::sanitizeInput($_POST['description']);
    $client_id = (int)$_POST['client_id'];
    $category = Security::sanitizeInput($_POST['category']);
    $priority = Security::sanitizeInput($_POST['priority']);
    $start_date = Security::sanitizeInput($_POST['start_date']);
    $end_date = Security::sanitizeInput($_POST['end_date']);
    $assigned_employees = $_POST['assigned_employees'] ?? [];
    
    // Insert project
    $query = "INSERT INTO projects (name, description, client_id, category, priority, start_date, end_date) 
              VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id";
    $stmt = $db->prepare($query);
    $stmt->execute([$name, $description, $client_id, $category, $priority, $start_date, $end_date]);
    $project_id = $stmt->fetchColumn();
    
    // Add project assignments
    if (!empty($assigned_employees)) {
        foreach ($assigned_employees as $user_id) {
            if (!empty($user_id)) {
                $query = "INSERT INTO project_assignments (project_id, user_id) VALUES (?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$project_id, $user_id]);
            }
        }
    }
}

// Handle project updates
if ($_POST && isset($_POST['update_project'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('IT');
    
    $project_id = (int)$_POST['project_id'];
    $progress = (int)$_POST['progress'];
    $status = Security::sanitizeInput($_POST['status']);
    
    $query = "UPDATE projects SET progress = ?, status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$progress, $status, $project_id]);
}

// Handle adding new comment
if ($_POST && isset($_POST['add_comment'])) {
    Security::checkCSRFToken();
    
    $project_id = (int)$_POST['project_id'];
    $comment = Security::sanitizeInput($_POST['comment']);
    $is_blocker = isset($_POST['is_blocker']) ? 1 : 0;
    $parent_comment_id = !empty($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null;
    
    $query = "INSERT INTO project_comments (project_id, user_id, comment, is_blocker, parent_comment_id) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$project_id, $_SESSION['user_id'], $comment, $is_blocker, $parent_comment_id]);
}

// Handle employee assignment updates
if ($_POST && isset($_POST['update_assignments'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('IT');
    
    $project_id = (int)$_POST['project_id'];
    $assigned_employees = $_POST['assigned_employees'] ?? [];
    
    // Remove existing assignments
    $query = "DELETE FROM project_assignments WHERE project_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$project_id]);
    
    // Add new assignments (roles now managed by HR department)
    if (!empty($assigned_employees)) {
        foreach ($assigned_employees as $user_id) {
            if (!empty($user_id)) {
                $query = "INSERT INTO project_assignments (project_id, user_id) VALUES (?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$project_id, $user_id]);
            }
        }
    }
}

// Handle filtering
$filter_status = $_GET['filter_status'] ?? '';
$filter_priority = $_GET['filter_priority'] ?? '';
$filter_category = $_GET['filter_category'] ?? '';
$filter_assigned = $_GET['filter_assigned'] ?? '';

// Build filtered query
$where_conditions = [];
$params = [];

if (!empty($filter_status)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $filter_status;
}
if (!empty($filter_priority)) {
    $where_conditions[] = "p.priority = ?";
    $params[] = $filter_priority;
}
if (!empty($filter_category)) {
    $where_conditions[] = "p.category = ?";
    $params[] = $filter_category;
}
if (!empty($filter_assigned)) {
    $where_conditions[] = "pa.user_id = ?";
    $params[] = $filter_assigned;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get all projects with assignments and comment counts (with filters)
$query = "SELECT p.*, c.name as client_name,
          COUNT(DISTINCT pc.id) as comment_count,
          COUNT(DISTINCT CASE WHEN pc.is_blocker = true THEN pc.id END) as blocker_count,
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

// Get project assignments
$assignments = [];
if (!empty($projects)) {
    $project_ids = array_column($projects, 'id');
    $placeholders = str_repeat('?,', count($project_ids) - 1) . '?';
    $query = "SELECT pa.*, u.username, u.email 
              FROM project_assignments pa 
              JOIN users u ON pa.user_id = u.id 
              WHERE pa.project_id IN ($placeholders)
              ORDER BY pa.project_id, u.username";
    $stmt = $db->prepare($query);
    $stmt->execute($project_ids);
    $assignment_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($assignment_results as $assignment) {
        $assignments[$assignment['project_id']][] = $assignment;
    }
}

// Get project comments with user info
$comments = [];
if (!empty($projects)) {
    $project_ids = array_column($projects, 'id');
    $placeholders = str_repeat('?,', count($project_ids) - 1) . '?';
    $query = "SELECT pc.*, u.username, u.email 
              FROM project_comments pc 
              JOIN users u ON pc.user_id = u.id 
              WHERE pc.project_id IN ($placeholders)
              ORDER BY pc.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($project_ids);
    $comment_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($comment_results as $comment) {
        $comments[$comment['project_id']][] = $comment;
    }
}

// Get clients for dropdown
$query = "SELECT id, name FROM clients ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all IT users for assignment and filters
$query = "SELECT id, username, email FROM users WHERE department = 'IT' OR role = 'admin' ORDER BY username";
$stmt = $db->prepare($query);
$stmt->execute();
$it_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique categories for filter options
$query = "SELECT DISTINCT category FROM projects WHERE category IS NOT NULL ORDER BY category";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$statuses = ['pending', 'in_progress', 'completed', 'on_hold'];
$priorities = ['low', 'medium', 'high'];

// Get current view parameter
$view = $_GET['view'] ?? 'projects';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Department - Business Management</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        /* Tab styles */
        .nav-tabs {
            display: flex;
            background: white;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .nav-tab {
            flex: 1;
            padding: 1rem 2rem;
            text-decoration: none;
            color: #666;
            background: white;
            border: none;
            border-right: 1px solid #eee;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-tab:last-child {
            border-right: none;
        }
        
        .nav-tab.active {
            background: #007bff;
            color: white;
        }
        
        .nav-tab:hover:not(.active) {
            background: #f8f9fa;
        }

        /* Project Grid Styles */
        .project-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .project-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.5rem;
            background: white;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            cursor: pointer;
            height: fit-content;
        }

        .project-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            border-color: #007bff;
        }

        /* Status and Priority Badges */
        .status-badge, .priority-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .status-in_progress { background: #cce5ff; color: #004085; border: 1px solid #b3d7ff; }
        .status-completed { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-on_hold { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .priority-low { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .priority-medium { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .priority-high { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .priority-urgent { background: #dc3545; color: white; border: 1px solid #c82333; }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #45a049);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .project-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .project-card {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .project-grid {
                grid-template-columns: 1fr;
            }
            
            .project-card {
                margin: 0.5rem 0;
            }
        }
    </style>
</head>
<body>   
    <?php include '../includes/header.php'; ?>
    
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php
        $total_projects = count($projects);
        $completed_projects = count(array_filter($projects, function($p) { return $p['status'] == 'completed'; }));
        $in_progress = count(array_filter($projects, function($p) { return $p['status'] == 'in_progress'; }));
        $avg_progress = $total_projects > 0 ? array_sum(array_column($projects, 'progress')) / $total_projects : 0;
        $total_comments = array_sum(array_column($projects, 'comment_count'));
        $total_blockers = array_sum(array_column($projects, 'blocker_count'));
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_projects; ?></div>
                <div class="stat-label">Total Projects</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $in_progress; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $completed_projects; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo round($avg_progress); ?>%</div>
                <div class="stat-label">Avg Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_comments; ?></div>
                <div class="stat-label">Comments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: <?php echo $total_blockers > 0 ? '#dc3545' : '#28a745'; ?>"><?php echo $total_blockers; ?></div>
                <div class="stat-label">Blockers</div>
            </div>
        </div>
        
        <!-- Tab Navigation -->
        <div class="nav-tabs">
            <a href="?view=projects" class="nav-tab <?php echo $view === 'projects' ? 'active' : ''; ?>">📋 View Projects</a>
            <a href="?view=create" class="nav-tab <?php echo $view === 'create' ? 'active' : ''; ?>">➕ Create New Project</a>
        </div>
        
        <?php if ($view === 'projects'): ?>
        <!-- Project Filters -->
        <div class="section">
            <div class="section-header">🔍 Filter Projects</div>
            <div class="section-content">
                <form method="GET" class="filter-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <input type="hidden" name="view" value="projects">
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="filter_status">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo $filter_status == $status ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Priority:</label>
                        <select name="filter_priority">
                            <option value="">All Priorities</option>
                            <?php foreach ($priorities as $priority): ?>
                                <option value="<?php echo $priority; ?>" <?php echo $filter_priority == $priority ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($priority); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Category:</label>
                        <select name="filter_category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo Security::escapeHTML($category); ?>" <?php echo $filter_category == $category ? 'selected' : ''; ?>>
                                    <?php echo Security::escapeHTML($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Assigned To:</label>
                        <select name="filter_assigned">
                            <option value="">All Team Members</option>
                            <?php foreach ($it_users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $filter_assigned == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo Security::escapeHTML($user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="display: flex; align-items: end; gap: 0.5rem;">
                        <button type="submit" class="btn">Apply Filters</button>
                        <a href="it.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Project List -->
        <div class="section">
            <div class="section-header">
                📋 Project List 
                <?php if (!empty($filter_status) || !empty($filter_priority) || !empty($filter_category) || !empty($filter_assigned)): ?>
                    <span style="font-size: 0.8rem; color: #666;">(Filtered - <?php echo count($projects); ?> projects)</span>
                <?php endif; ?>
            </div>
            <div class="section-content">
                <?php if (!empty($projects)): ?>
                    <div class="project-grid">
                        <?php foreach ($projects as $project): ?>
                            <div class="project-card" onclick="window.location.href='project_detail.php?id=<?php echo $project['id']; ?>'">
                                
                                <!-- Header with Title and Badges -->
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <h3 style="margin: 0; color: #333; font-size: 1.1rem; line-height: 1.4; flex: 1;">
                                        <a href="project_detail.php?id=<?php echo $project['id']; ?>" style="text-decoration: none; color: inherit; display: block;">
                                            <?php echo Security::escapeHTML($project['name']); ?>
                                        </a>
                                    </h3>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                                        <span class="status-badge status-<?php echo $project['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                        </span>
                                        <span class="priority-badge priority-<?php echo $project['priority']; ?>">
                                            <?php echo ucfirst($project['priority']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Project Meta Information -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem; font-size: 0.85rem;">
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                        <div>
                                            <strong style="color: #666;">Client:</strong><br>
                                            <span style="color: #333;"><?php echo Security::escapeHTML($project['client_name'] ?? 'No client'); ?></span>
                                        </div>
                                        <div>
                                            <strong style="color: #666;">Category:</strong><br>
                                            <span style="color: #333;"><?php echo Security::escapeHTML($project['category']); ?></span>
                                        </div>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                        <div>
                                            <strong style="color: #666;">Team Size:</strong><br>
                                            <span style="color: #333;"><?php echo (int)$project['team_count']; ?> member<?php echo $project['team_count'] != 1 ? 's' : ''; ?></span>
                                        </div>
                                        <div>
                                            <strong style="color: #666;">Created:</strong><br>
                                            <span style="color: #333;"><?php echo date('M j, Y', strtotime($project['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Progress Section -->
                                <div style="margin-bottom: 1rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                        <strong style="color: #333; font-size: 0.9rem;">Progress: <?php echo (int)$project['progress']; ?>%</strong>
                                        <?php if ($project['end_date']): ?>
                                            <span style="font-size: 0.75rem; color: #666;">
                                                Due: <?php echo date('M j, Y', strtotime($project['end_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo (int)$project['progress']; ?>%"></div>
                                    </div>
                                </div>
                                
                                <!-- Activity Indicators -->
                                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.75rem; border-top: 1px solid #f0f0f0;">
                                    <div style="display: flex; gap: 1rem; font-size: 0.8rem;">
                                        <span style="color: #666;">
                                            <span style="font-weight: 600;">💬</span> <?php echo (int)$project['comment_count']; ?>
                                        </span>
                                        <?php if ($project['blocker_count'] > 0): ?>
                                            <span style="color: #dc3545; font-weight: 600;">
                                                <span>🚨</span> <?php echo (int)$project['blocker_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="project_detail.php?id=<?php echo $project['id']; ?>" class="btn btn-small" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                                        View Details →
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: #666; background: white; border-radius: 8px; border: 2px dashed #ddd;">
                        <h3 style="margin-bottom: 1rem; color: #999;">📭 No Projects Found</h3>
                        <p style="margin-bottom: 1.5rem;">
                            <?php if (!empty($filter_status) || !empty($filter_priority) || !empty($filter_category) || !empty($filter_assigned)): ?>
                                No projects match your current filters. 
                            <?php else: ?>
                                No projects have been created yet.
                            <?php endif; ?>
                        </p>
                        <div style="display: flex; gap: 1rem; justify-content: center;">
                            <?php if (!empty($filter_status) || !empty($filter_priority) || !empty($filter_category) || !empty($filter_assigned)): ?>
                                <a href="it.php" class="btn">Clear Filters</a>
                            <?php endif; ?>
                            <a href="?view=create" class="btn btn-primary">➕ Create First Project</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php elseif ($view === 'create'): ?>
        <div class="section">
            <div class="section-header">➕ Create New Project</div>
            <div class="section-content">
                <form method="post">
                    <?php echo Security::getCSRFTokenField(); ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Project Name:</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="client_id">Client:</label>
                            <select id="client_id" name="client_id" required>
                                <option value="">Select Client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <select id="category" name="category" required>
                                <option value="Web Dev">Web Development</option>
                                <option value="Network Setup">Network Setup</option>
                                <option value="Software Dev">Software Development</option>
                                <option value="Mobile App">Mobile App</option>
                                <option value="Database">Database</option>
                                <option value="Security">Security</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="priority">Priority:</label>
                            <select id="priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="start_date">Start Date:</label>
                            <input type="date" id="start_date" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date:</label>
                            <input type="date" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Assign Team Members:</label>
                        <div id="employee-assignments">
                            <div class="assignment-row" style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center;">
                                <select name="assigned_employees[]" style="flex: 1;">
                                    <option value="">Select Team Member</option>
                                    <?php foreach ($it_users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <span style="font-size: 0.9em; color: #666; flex: 1;">Role assigned by HR department</span>
                                <button type="button" onclick="addAssignmentRow()" class="btn btn-small">+ Add Member</button>
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
        function addAssignmentRow() {
            const container = document.getElementById('employee-assignments');
            const newRow = container.firstElementChild.cloneNode(true);
            
            // Clear employee selection
            const select = newRow.querySelector('select');
            select.selectedIndex = 0;
            
            container.appendChild(newRow);
        }
    </script>
</body>
</html>