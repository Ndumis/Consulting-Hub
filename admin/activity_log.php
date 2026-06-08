<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Require admin or manager access for activity log
if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: ../dashboard.php?error=access_denied");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$department_filter = isset($_GET['department']) ? Security::sanitizeInput($_GET['department']) : '';
$activity_type_filter = isset($_GET['activity_type']) ? Security::sanitizeInput($_GET['activity_type']) : '';
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$date_from = isset($_GET['date_from']) ? Security::sanitizeInput($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? Security::sanitizeInput($_GET['date_to']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($department_filter)) {
    $where_conditions[] = "u.department = ?";
    $params[] = $department_filter;
}

if (!empty($activity_type_filter)) {
    $where_conditions[] = "ua.activity_type = ?";
    $params[] = $activity_type_filter;
}

if (!empty($user_filter)) {
    $where_conditions[] = "ua.user_id = ?";
    $params[] = $user_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(ua.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(ua.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get activities from user_activities table (enhanced tracking)
$activity_where = [];
$activity_params = [];

if (!empty($activity_type_filter)) {
    $activity_where[] = "ua.activity_type = ?";
    $activity_params[] = $activity_type_filter;
}

if (!empty($user_filter)) {
    $activity_where[] = "ua.user_id = ?";
    $activity_params[] = $user_filter;
}

if (!empty($department_filter)) {
    $activity_where[] = "u.department = ?";
    $activity_params[] = $department_filter;
}

if (!empty($date_from)) {
    $activity_where[] = "DATE(ua.created_at) >= ?";
    $activity_params[] = $date_from;
}

if (!empty($date_to)) {
    $activity_where[] = "DATE(ua.created_at) <= ?";
    $activity_params[] = $date_to;
}

$activity_where_clause = !empty($activity_where) ? "WHERE " . implode(" AND ", $activity_where) : "";

$query = "SELECT 
    ua.id,
    ua.user_id,
    ua.username,
    ua.activity_type,
    ua.description,
    ua.page_url,
    ua.resource_type,
    ua.resource_id,
    ua.ip_address,
    ua.user_agent,
    ua.session_id,
    ua.additional_data,
    ua.created_at,
    u.email,
    u.department,
    'user_activities' as source_table
FROM user_activities ua
LEFT JOIN users u ON ua.user_id = u.id
$activity_where_clause
ORDER BY ua.created_at DESC
LIMIT $limit";

$stmt = $db->prepare($query);
$stmt->execute($activity_params);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get filter options from both tables
$activity_types = $db->query("
    SELECT DISTINCT activity_type FROM user_activities 
    UNION 
    SELECT DISTINCT activity_type FROM system_activity 
    ORDER BY activity_type")->fetchAll(PDO::FETCH_COLUMN);
$users = $db->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Get departments from users table since user_activities doesn't have department
$departments = $db->query("SELECT DISTINCT department FROM users ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);

// Get statistics from both tables
$stats_query = "SELECT 
                    (SELECT COUNT(*) FROM user_activities) + (SELECT COUNT(*) FROM system_activity) as total_activities,
                    COUNT(DISTINCT u.id) as active_users,
                    COUNT(DISTINCT u.department) as departments_with_activity,
                    DATE(GREATEST(
                        COALESCE((SELECT MAX(created_at) FROM user_activities), '1970-01-01'),
                        COALESCE((SELECT MAX(created_at) FROM system_activity), '1970-01-01')
                    )) as last_activity_date
                FROM users u 
                WHERE u.id IN (
                    SELECT DISTINCT user_id FROM user_activities WHERE user_id IS NOT NULL
                    UNION 
                    SELECT DISTINCT user_id FROM system_activity WHERE user_id IS NOT NULL
                )";
$stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Get activity count by department from users who have activities
$dept_stats = $db->query("SELECT u.department, 
                            (SELECT COUNT(*) FROM user_activities ua WHERE ua.user_id = u.id) +
                            (SELECT COUNT(*) FROM system_activity sa WHERE sa.user_id = u.id) as count
                         FROM users u 
                         WHERE u.id IN (
                            SELECT DISTINCT user_id FROM user_activities WHERE user_id IS NOT NULL
                            UNION 
                            SELECT DISTINCT user_id FROM system_activity WHERE user_id IS NOT NULL
                         )
                         GROUP BY u.department 
                         HAVING count > 0
                         ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Activity Log - Admin</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 5px;
        }
        
        .activity-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .activity-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .activity-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .activity-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }
        
        .activity-table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-IT { background: #e3f2fd; color: #1976d2; }
        .badge-Marketing { background: #f3e5f5; color: #7b1fa2; }
        .badge-Finance { background: #e8f5e8; color: #388e3c; }
        .badge-HR { background: #fff3e0; color: #f57c00; }
        .badge-Clients { background: #fce4ec; color: #c2185b; }
        .badge-Insights { background: #f1f8e9; color: #689f38; }
        
        .activity-description {
            max-width: 300px;
            word-wrap: break-word;
        }
        
        .no-activities {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .export-btn {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .dept-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .dept-stat {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🔍 System Activity Log</h1>
        <p>Administrative monitoring of all user activities across departments</p>
    </div>
    
    <div class="container">
        <!-- Statistics Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_activities']); ?></div>
                <div>Total Activities</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                <div>Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['departments_with_activity']; ?></div>
                <div>Departments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['last_activity_date'] ? Utils::formatDate($stats['last_activity_date']) : 'N/A'; ?></div>
                <div>Last Activity</div>
            </div>
        </div>
        
        <!-- Department Statistics -->
        <?php if (!empty($dept_stats)): ?>
        <div class="stat-card">
            <h3>Activity by Department</h3>
            <div class="dept-stats">
                <?php foreach ($dept_stats as $dept): ?>
                <div class="dept-stat">
                    <div style="font-weight: bold;"><?php echo Security::escapeHTML($dept['department']); ?></div>
                    <div><?php echo number_format($dept['count']); ?> activities</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Filters Section -->
        <div class="filters-section">
            <h3>Filter Activities</h3>
            <form method="GET" action="">
                <div class="filters-grid">
                    <div>
                        <label>Department:</label>
                        <select name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo Security::escapeHTML($dept); ?>" 
                                        <?php echo $department_filter === $dept ? 'selected' : ''; ?>>
                                    <?php echo Security::escapeHTML($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Activity Type:</label>
                        <select name="activity_type">
                            <option value="">All Types</option>
                            <?php foreach ($activity_types as $type): ?>
                                <option value="<?php echo Security::escapeHTML($type); ?>" 
                                        <?php echo $activity_type_filter === $type ? 'selected' : ''; ?>>
                                    <?php echo Security::escapeHTML($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>User:</label>
                        <select name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                        <?php echo $user_filter === (int)$user['id'] ? 'selected' : ''; ?>>
                                    <?php echo Security::escapeHTML($user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>From Date:</label>
                        <input type="date" name="date_from" value="<?php echo Security::escapeHTML($date_from); ?>">
                    </div>
                    <div>
                        <label>To Date:</label>
                        <input type="date" name="date_to" value="<?php echo Security::escapeHTML($date_to); ?>">
                    </div>
                    <div>
                        <label>Limit:</label>
                        <select name="limit">
                            <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                            <option value="250" <?php echo $limit === 250 ? 'selected' : ''; ?>>250</option>
                            <option value="500" <?php echo $limit === 500 ? 'selected' : ''; ?>>500</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="activity_log.php" class="btn btn-secondary">Clear Filters</a>
                <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </form>
        </div>
        
        <!-- Activities Table -->
        <div class="activity-table">
            <?php if (!empty($activities)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Department</th>
                        <th>Activity Type</th>
                        <th>Description</th>
                        <th>Target</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td>
                            <div><?php echo date('Y-m-d', strtotime($activity['created_at'])); ?></div>
                            <div style="font-size: 0.9rem; color: #666;">
                                <?php echo date('H:i:s', strtotime($activity['created_at'])); ?>
                            </div>
                        </td>
                        <td>
                            <div><?php echo Security::escapeHTML($activity['username'] ?? 'Unknown'); ?></div>
                            <div style="font-size: 0.8rem; color: #666;">
                                <?php echo Security::escapeHTML($activity['email'] ?? ''); ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($activity['department'])): ?>
                                <span class="badge badge-<?php echo Security::escapeHTML($activity['department']); ?>">
                                    <?php echo Security::escapeHTML($activity['department']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo Security::escapeHTML($activity['activity_type']); ?></td>
                        <td class="activity-description">
                            <?php echo Security::escapeHTML($activity['description']); ?>
                        </td>
                        <td>
                            <?php if (!empty($activity['resource_type']) && !empty($activity['resource_id'])): ?>
                                <div style="font-size: 0.9rem;">
                                    <?php echo Security::escapeHTML(ucfirst($activity['resource_type'])); ?> #<?php echo $activity['resource_id']; ?>
                                </div>
                            <?php elseif (!empty($activity['page_url'])): ?>
                                <div style="font-size: 0.8rem; color: #666;">
                                    <?php echo Security::escapeHTML($activity['page_url']); ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 0.9rem;"><?php echo Security::escapeHTML($activity['ip_address'] ?? 'Unknown'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-activities">
                <h3>No Activities Found</h3>
                <p>No activities match your current filters or no activities have been logged yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>