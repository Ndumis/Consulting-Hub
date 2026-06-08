<?php
require_once 'config/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

require_once 'config/database.php';
require_once 'config/security.php';
require_once 'includes/functions.php';

// Track page visit
if (isset($_SESSION['user_id'])) {
    try {
        require_once 'includes/ActivityLogger.php';
        $database = new Database();
        $db = $database->getConnection();
        $logger = new ActivityLogger($db);
        $logger->logPageVisit('Dashboard', 'User accessed main dashboard');
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

$database = new Database();
$db = $database->getConnection();

// Get user info
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];
$email = $_SESSION['email'];

// Initialize statistics array with default values
$stats = [
    // IT Department
    'total_projects' => 0,
    'in_progress_projects' => 0,
    'completed_projects' => 0,
    'project_blockers' => 0,
    
    // Marketing Department
    'active_campaigns' => 0,
    'total_campaigns' => 0,
    'social_posts' => 0,
    'email_campaigns' => 0,
    
    // Business Development
    'total_leads' => 0,
    'new_leads' => 0,
    'meetings_booked' => 0,
    'clients_converted' => 0,
    'pending_tasks' => 0,
    
    // HR Department
    'total_employees' => 0,
    'pending_leave_requests' => 0,
    
    // Client Department
    'active_clients' => 0,
    'total_clients' => 0,
    
    // Finance
    'total_invoices' => 0,
    'total_quotations' => 0
];

// Get comprehensive statistics with error handling
try {
    // IT Department Statistics
    $query = "SELECT COUNT(*) as total_projects FROM projects";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_projects'] ?? 0;

    $query = "SELECT COUNT(*) as in_progress_projects FROM projects WHERE status = 'in_progress'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['in_progress_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['in_progress_projects'] ?? 0;

    $query = "SELECT COUNT(*) as completed_projects FROM projects WHERE status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['completed_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['completed_projects'] ?? 0;

    // Marketing Department Statistics
    $query = "SELECT COUNT(*) as active_campaigns FROM marketing_campaigns WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['active_campaigns'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_campaigns'] ?? 0;

    $query = "SELECT COUNT(*) as total_campaigns FROM marketing_campaigns";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_campaigns'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_campaigns'] ?? 0;

    // Business Development Statistics
    $query = "SELECT COUNT(*) as total_leads FROM bd_leads";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_leads'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_leads'] ?? 0;

    $query = "SELECT COUNT(*) as new_leads FROM bd_leads WHERE status = 'new'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['new_leads'] = $stmt->fetch(PDO::FETCH_ASSOC)['new_leads'] ?? 0;

    $query = "SELECT COUNT(*) as meetings_booked FROM bd_leads WHERE status = 'meeting_booked'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['meetings_booked'] = $stmt->fetch(PDO::FETCH_ASSOC)['meetings_booked'] ?? 0;

    $query = "SELECT COUNT(*) as clients_converted FROM bd_leads WHERE status = 'client'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['clients_converted'] = $stmt->fetch(PDO::FETCH_ASSOC)['clients_converted'] ?? 0;

    $query = "SELECT COUNT(*) as pending_tasks FROM bd_tasks WHERE status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_tasks'] ?? 0;

    // HR Department Statistics
    $query = "SELECT COUNT(*) as total_employees FROM hr_employees WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_employees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_employees'] ?? 0;

    // Client Department Statistics
    $query = "SELECT COUNT(*) as active_clients FROM clients WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['active_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_clients'] ?? 0;

    $query = "SELECT COUNT(*) as total_clients FROM clients";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_clients'] ?? 0;

    // Finance Statistics
    $query = "SELECT COUNT(*) as total_invoices FROM invoices";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_invoices'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_invoices'] ?? 0;

} catch (Exception $e) {
    error_log("Dashboard statistics error: " . $e->getMessage());
    // Continue with default values
}

// Get upcoming events from all departments (next 7 days)
$upcoming_events = [];
try {
    $query = "
        SELECT 
            ce.title,
            ce.event_date,
            ce.event_time,
            ce.description as location,
            ce.event_type,
            'calendar' as department,
            '📅' as icon,
            COALESCE(c.name, 'N/A') as related_name,
            'Client' as related_type
        FROM calendar_events ce 
        LEFT JOIN clients c ON ce.client_id = c.id 
        WHERE ce.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        
        UNION ALL
        
        SELECT 
            CONCAT('Project Deadline: ', p.name) as title,
            p.end_date as event_date,
            '23:59:00' as event_time,
            NULL as location,
            'Project Deadline' as event_type,
            'IT' as department,
            '⏰' as icon,
            COALESCE(c.name, 'N/A') as related_name,
            'Client' as related_type
        FROM projects p
        LEFT JOIN clients c ON p.client_id = c.id
        WHERE p.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND p.status != 'completed'
        
        UNION ALL
        
        SELECT 
            CONCAT('Campaign Launch: ', mc.campaign_name) as title,
            mc.start_date as event_date,
            '09:00:00' as event_time,
            NULL as location,
            'Campaign Launch' as event_type,
            'Marketing' as department,
            '🚀' as icon,
            mc.campaign_type as related_name,
            'Campaign' as related_type
        FROM marketing_campaigns mc
        WHERE mc.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND mc.status = 'active'
        
        UNION ALL
        
        SELECT 
            CONCAT('Lead Follow-up: ', bl.company_name) as title,
            bl.next_follow_up as event_date,
            '09:00:00' as event_time,
            CONCAT('Contact: ', bl.contact_person) as location,
            'Lead Follow-up' as event_type,
            'Business Development' as department,
            '🎯' as icon,
            bl.industry as related_name,
            'Industry' as related_type
        FROM bd_leads bl
        WHERE bl.next_follow_up BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND bl.status NOT IN ('client')
        
        UNION ALL
        
        SELECT 
            CONCAT('Task Due: ', bt.task_description) as title,
            bt.due_date as event_date,
            '17:00:00' as event_time,
            NULL as location,
            'Task Deadline' as event_type,
            'Business Development' as department,
            '✅' as icon,
            COALESCE(bl.company_name, 'General') as related_name,
            'Lead' as related_type
        FROM bd_tasks bt
        LEFT JOIN bd_leads bl ON bt.related_lead_id = bl.id
        WHERE bt.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND bt.status = 'pending'
        
        ORDER BY event_date, event_time
        LIMIT 8";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Upcoming events error: " . $e->getMessage());
    $upcoming_events = [];
}

// Get project progress data for charts
$project_progress = ['in_progress' => 0, 'completed' => 0, 'pending' => 0];
try {
    $query = "SELECT status, COUNT(*) as count FROM projects GROUP BY status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $project_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($project_data as $row) {
        $project_progress[$row['status']] = $row['count'];
    }
} catch (Exception $e) {
    error_log("Project progress error: " . $e->getMessage());
}

// Get department distribution
$department_data = [];
try {
    $query = "SELECT department, COUNT(*) as count FROM users GROUP BY department";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $dept_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dept_data as $row) {
        $department_data[$row['department']] = $row['count'];
    }
} catch (Exception $e) {
    error_log("Department data error: " . $e->getMessage());
    $department_data = ['IT' => 1, 'Marketing' => 1, 'Business Development' => 1, 'Finance' => 1, 'HR' => 1, 'Clients' => 1];
}

// Get recent blog posts for dashboard
$recent_blog_posts = [];
try {
    $query = "SELECT bp.*, c.name as client_name, mc.campaign_name,
                     DATE(bp.published_at) as publish_date
              FROM blog_posts bp
              LEFT JOIN clients c ON bp.client_id = c.id
              LEFT JOIN marketing_campaigns mc ON bp.campaign_id = mc.id
              WHERE bp.status = 'published' AND bp.published_at <= NOW()
              ORDER BY bp.published_at DESC
              LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_blog_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Blog posts error: " . $e->getMessage());
    $recent_blog_posts = [];
}

// Monthly trends data
$monthly_trends = [
    'projects_completed' => [8, 12, 15, 18, 22, 25],
    'campaigns_launched' => [3, 5, 4, 7, 6, 8],
    'clients_acquired' => [2, 4, 3, 5, 4, 6],
    'leads_generated' => [10, 15, 12, 18, 20, 22]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Management Dashboard</title>
    <link rel="stylesheet" href="css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .header {
            position: relative;
        }
        .menu-toggle {
            z-index: 1001;
        }
        .stats-widget {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }
        .stat-change {
            font-size: 0.8rem;
            font-weight: 500;
        }
        .stat-increase {
            color: #28a745;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .quick-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            background: #007bff;
            color: white;
            transform: translateY(-2px);
        }
        .action-btn .icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .dashboard-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 768px) {
            .dashboard-row {
                grid-template-columns: 1fr;
            }
        }
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .calendar-widget {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .calendar-event {
            display: flex;
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            align-items: flex-start;
        }
        .calendar-event:last-child {
            border-bottom: none;
        }
        .event-time {
            min-width: 60px;
            text-align: center;
            margin-right: 1rem;
        }
        .event-details {
            flex: 1;
        }
        .event-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .department-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .department-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .department-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .department-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-section">
            <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">☰</button>
            <img src="img/KConsultingLogo.png" alt="KConsulting" class="logo-img">
            <h2>Business Management System</h2>
        </div>
        <div class="notifications-section">
            <?php
            $notification_count = 0;
            try {
                $query = "SELECT COUNT(DISTINCT pa.id) as count FROM project_assignments pa 
                         JOIN projects p ON pa.project_id = p.id 
                         WHERE pa.user_id = ? AND pa.assigned_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $stmt = $db->prepare($query);
                $stmt->execute([$user_id]);
                $assignments = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                
                $query = "SELECT COUNT(DISTINCT pc.id) as count FROM project_comments pc 
                         JOIN project_assignments pa ON pc.project_id = pa.project_id 
                         WHERE pa.user_id = ? AND pc.user_id != ? AND pc.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $stmt = $db->prepare($query);
                $stmt->execute([$user_id, $user_id]);
                $comments = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                
                $notification_count = $assignments + $comments;
            } catch (Exception $e) {
                $notification_count = 0;
            }
            ?>
            <div class="notification-icon" onclick="toggleNotifications()">
                <span class="notification-bell">🔔</span>
                <?php if ($notification_count > 0): ?>
                <span class="notification-badge"><?php echo $notification_count; ?></span>
                <?php endif; ?>
            </div>
            
            <div id="notificationsDropdown" class="notifications-dropdown" style="display: none;">
                <div class="notifications-header">
                    <h4>Notifications</h4>
                    <span class="close-notifications" onclick="toggleNotifications()">&times;</span>
                </div>
                <div class="notifications-body">
                    <?php
                    try {
                        $notifications = [];
                        
                        $query = "SELECT DISTINCT p.name as title, p.description, pa.assigned_at, pa.role, 'assignment' as type
                                 FROM project_assignments pa 
                                 JOIN projects p ON pa.project_id = p.id 
                                 WHERE pa.user_id = ? AND pa.assigned_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                                 ORDER BY pa.assigned_at DESC LIMIT 5";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$user_id]);
                        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $query = "SELECT DISTINCT p.name as title, pc.comment, pc.created_at, u.username, 'comment' as type
                                 FROM project_comments pc 
                                 JOIN project_assignments pa ON pc.project_id = pa.project_id 
                                 JOIN projects p ON pc.project_id = p.id
                                 JOIN users u ON pc.user_id = u.id
                                 WHERE pa.user_id = ? AND pc.user_id != ? AND pc.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                                 ORDER BY pc.created_at DESC LIMIT 5";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$user_id, $user_id]);
                        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $notifications = array_merge($assignments, $comments);
                        
                        usort($notifications, function($a, $b) {
                            $time_a = $a['type'] == 'assignment' ? $a['assigned_at'] : $a['created_at'];
                            $time_b = $b['type'] == 'assignment' ? $b['assigned_at'] : $b['created_at'];
                            return strtotime($time_b) - strtotime($time_a);
                        });
                        
                        if (empty($notifications)): ?>
                            <div class="notification-item no-notifications">
                                <p>No recent notifications</p>
                            </div>
                        <?php else:
                            foreach (array_slice($notifications, 0, 5) as $notification): ?>
                                <div class="notification-item">
                                    <?php if ($notification['type'] == 'assignment'): ?>
                                        <div class="notification-icon-type">📋</div>
                                        <div class="notification-content">
                                            <p class="notification-title">New Project Assignment</p>
                                            <p class="notification-text">Assigned to: <strong><?php echo Security::escapeHTML($notification['title']); ?></strong></p>
                                            <p class="notification-meta">Role: <?php echo Security::escapeHTML($notification['role']); ?> • <?php echo date('M j, g:i A', strtotime($notification['assigned_at'])); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <div class="notification-icon-type">💬</div>
                                        <div class="notification-content">
                                            <p class="notification-title">New Comment on <?php echo Security::escapeHTML($notification['title']); ?></p>
                                            <p class="notification-text"><?php echo Security::escapeHTML(substr($notification['comment'], 0, 50) . (strlen($notification['comment']) > 50 ? '...' : '')); ?></p>
                                            <p class="notification-meta">By <?php echo Security::escapeHTML($notification['username']); ?> • <?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; 
                        endif;
                    } catch (Exception $e) {
                        echo '<div class="notification-item no-notifications"><p>Unable to load notifications</p></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="user-info">
            <span>Welcome, <?php echo Security::escapeHTML($username); ?> (<?php echo Security::escapeHTML(ucfirst($role)); ?>)</span>
            <a href="auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="sidebar" id="sidebar">
        <a href="dashboard.php" class="nav-item active">📊 Dashboard</a>
        <a href="departments/it.php" class="nav-item">💻 IT Department</a>
        <a href="departments/marketing.php" class="nav-item">📈 Marketing</a>
        <a href="departments/bd.php" class="nav-item">🎯 Business Development</a>
        <a href="departments/finance.php" class="nav-item">💰 Finance</a>
        <a href="departments/hr.php" class="nav-item">👥 HR</a>
        <a href="departments/clients.php" class="nav-item">🏢 Clients</a>
        <a href="departments/insights.php" class="nav-item">📊 Insights</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="admin/activity_log.php" class="nav-item" style="border-top: 1px solid #404040; margin-top: 0.5rem; padding-top: 1rem;">🔧 Activity Log</a>
        <?php endif; ?>
    </div>
    
    <div class="main-content">
        <h1>📊 Dashboard Overview</h1>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>🚀 Quick Actions</h3>
            <div class="action-buttons">
                <a href="departments/it.php?action=new_project" class="action-btn">
                    <span class="icon">⚙️</span>New Project
                </a>
                <a href="departments/marketing.php?action=new_campaign" class="action-btn">
                    <span class="icon">📢</span>New Campaign
                </a>
                <a href="departments/bd.php?action=new_lead" class="action-btn">
                    <span class="icon">🎯</span>New Lead
                </a>
                <a href="departments/finance.php?action=new_invoice" class="action-btn">
                    <span class="icon">💰</span>Create Invoice
                </a>
                <a href="departments/clients.php?action=new_client" class="action-btn">
                    <span class="icon">👤</span>Add Client
                </a>
                <a href="departments/hr.php?action=new_employee" class="action-btn">
                    <span class="icon">👥</span>New Employee
                </a>
            </div>
        </div>

        <!-- Main Dashboard Row -->
        <div class="dashboard-row">
            <!-- Left Column - Statistics and Charts -->
            <div>
                <!-- Department Statistics -->
                <div class="dashboard-grid">
                    <div class="stats-widget">
                        <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
                        <div class="stat-label">Total Projects</div>
                        <div class="stat-change stat-increase">↗ <?php echo $stats['in_progress_projects']; ?> active</div>
                    </div>
                    <div class="stats-widget">
                        <div class="stat-number"><?php echo $stats['active_campaigns']; ?></div>
                        <div class="stat-label">Active Campaigns</div>
                        <div class="stat-change stat-increase">📈 Running</div>
                    </div>
                    <div class="stats-widget">
                        <div class="stat-number"><?php echo $stats['total_leads']; ?></div>
                        <div class="stat-label">Total Leads</div>
                        <div class="stat-change stat-increase">🎯 <?php echo $stats['new_leads']; ?> new</div>
                    </div>
                    <div class="stats-widget">
                        <div class="stat-number"><?php echo $stats['total_employees']; ?></div>
                        <div class="stat-label">Team Members</div>
                        <div class="stat-change stat-increase">👥 Active</div>
                    </div>
                    <div class="stats-widget">
                        <div class="stat-number"><?php echo $stats['active_clients']; ?></div>
                        <div class="stat-label">Active Clients</div>
                        <div class="stat-change stat-increase">🏢 Engaged</div>
                    </div>
                    <div class="stats-widget">
                        <div class="stat-number"><?php echo $stats['clients_converted']; ?></div>
                        <div class="stat-label">Clients Converted</div>
                        <div class="stat-change stat-increase">💰 From BD</div>
                    </div>
                </div>

                <!-- IT Department Detailed Stats -->
                <div class="chart-container">
                    <h3>💻 IT Department Overview</h3>
                    <canvas id="projectStatusChart"></canvas>
                </div>

                <!-- Marketing & Performance Chart -->
                <div class="chart-container">
                    <h3>📊 Monthly Trends</h3>
                    <canvas id="monthlyTrendsChart"></canvas>
                </div>
            </div>

            <!-- Right Column - Calendar and Notifications -->
            <div>
                <!-- Calendar Widget -->
                <div class="calendar-widget">
                    <div class="calendar-header">
                        <span>📅 Upcoming Events - All Departments</span>
                        <small>Next 7 Days</small>
                    </div>
                    <div class="calendar-events">
                        <?php if (empty($upcoming_events)): ?>
                            <div style="text-align: center; color: #666; padding: 2rem;">
                                <p>No upcoming events</p>
                                <small>Your calendar is clear!</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcoming_events as $event): ?>
                                <div class="calendar-event" style="border-left: 4px solid <?php 
                                    echo $event['department'] == 'IT' ? '#28a745' : 
                                        ($event['department'] == 'Marketing' ? '#007bff' : 
                                        ($event['department'] == 'Business Development' ? '#ff6b35' : 
                                        ($event['department'] == 'Clients' ? '#ffc107' : 
                                        ($event['department'] == 'HR' ? '#6f42c1' : '#6c757d')))); ?>;">
                                    <div class="event-time">
                                        <?php echo date('M j', strtotime($event['event_date'])); ?><br>
                                        <small><?php echo $event['event_time'] && $event['event_time'] != '00:00:00' ? date('H:i', strtotime($event['event_time'])) : 'All Day'; ?></small>
                                    </div>
                                    <div class="event-details">
                                        <div class="event-title">
                                            <?php echo $event['icon']; ?> <?php echo Security::escapeHTML($event['title']); ?>
                                        </div>
                                        <div class="event-type" style="font-size: 0.8rem; color: #666; margin: 0.25rem 0;">
                                            <?php echo Security::escapeHTML($event['event_type']); ?> • <?php echo Security::escapeHTML($event['department']); ?>
                                        </div>
                                        <?php if ($event['location']): ?>
                                            <div class="event-location">📍 <?php echo Security::escapeHTML($event['location']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($event['related_name'] && $event['related_name'] != 'N/A'): ?>
                                            <div class="event-related" style="font-size: 0.8rem; color: #888;">
                                                <?php echo Security::escapeHTML($event['related_type']); ?>: <?php echo Security::escapeHTML($event['related_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Department Performance Overview -->
                <div class="chart-container">
                    <h3>🏢 Department Distribution</h3>
                    <canvas id="departmentChart"></canvas>
                </div>

                <!-- Recent Blog Posts Widget -->
                <div class="chart-container">
                    <h3>📰 Recent Blog Posts</h3>
                    <?php if (empty($recent_blog_posts)): ?>
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <p>No published blog posts yet.</p>
                            <p><a href="departments/marketing.php?view=blog-posts" class="btn">Create Your First Blog Post</a></p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($recent_blog_posts as $post): ?>
                                <div style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem; background: white;">
                                    <h4 style="margin: 0 0 0.5rem 0; color: #333;"><?php echo Security::escapeHTML($post['title']); ?></h4>
                                    <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">
                                        <span>✍️ <?php echo Security::escapeHTML($post['author'] ?? 'Unknown'); ?></span> • 
                                        <span>📅 <?php echo date('M j, Y', strtotime($post['publish_date'])); ?></span>
                                    </div>
                                    <?php if ($post['excerpt']): ?>
                                        <p style="margin: 0.5rem 0; color: #555; font-size: 0.9rem;"><?php echo Security::escapeHTML(substr($post['excerpt'], 0, 120) . (strlen($post['excerpt']) > 120 ? '...' : '')); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="text-align: center; margin-top: 1rem;">
                            <a href="departments/marketing.php?view=blog-posts" class="btn">View All Blog Posts</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Department Modules -->
        <h2>🏢 Department Modules</h2>
        <div class="department-grid">
            <div class="department-card" onclick="window.location.href='departments/it.php'">
                <h3>💻 IT Department</h3>
                <p>Manage projects, track progress, handle web development, network setup, and software development tasks.</p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0;">
                    <span class="department-status status-active">Fully Functional</span>
                    <div>
                        <small>Active: <?php echo $stats['in_progress_projects']; ?> projects</small><br>
                        <small>Completed: <?php echo $stats['completed_projects']; ?></small>
                    </div>
                </div>
                <a href="departments/it.php" class="btn">Access IT Department</a>
            </div>
            
            <div class="department-card" onclick="window.location.href='departments/marketing.php'">
                <h3>📈 Marketing Department</h3>
                <p>Oversee client accounts, social media campaigns, email marketing, and brand development projects.</p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0;">
                    <span class="department-status status-active">Fully Functional</span>
                    <div>
                        <small>Campaigns: <?php echo $stats['active_campaigns']; ?> active</small><br>
                        <small>Total: <?php echo $stats['total_campaigns']; ?></small>
                    </div>
                </div>
                <a href="departments/marketing.php" class="btn">Access Marketing</a>
            </div>
            
            <div class="department-card" onclick="window.location.href='departments/bd.php'">
                <h3>🎯 Business Development</h3>
                <p>Manage leads, track sales pipeline, log activities, set targets, and monitor business growth performance.</p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0;">
                    <span class="department-status status-active">Fully Functional</span>
                    <div>
                        <small>Leads: <?php echo $stats['total_leads']; ?> total</small><br>
                        <small>Meetings: <?php echo $stats['meetings_booked']; ?> booked</small>
                    </div>
                </div>
                <a href="departments/bd.php" class="btn">Access Business Development</a>
            </div>
            
            <div class="department-card" onclick="window.location.href='departments/finance.php'">
                <h3>💰 Finance Department</h3>
                <p>Create quotations, manage invoices, handle VAT calculations, and generate PDF documents for financial management.</p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0;">
                    <span class="department-status status-active">Fully Functional</span>
                    <div>
                        <small>Invoices: <?php echo $stats['total_invoices']; ?></small><br>
                        <small>Quotations: <?php echo $stats['total_quotations']; ?></small>
                    </div>
                </div>
                <a href="departments/finance.php" class="btn">Access Finance</a>
            </div>
            
            <div class="department-card" onclick="window.location.href='departments/hr.php'">
                <h3>👥 HR Department</h3>
                <p>Manage employee records, recruitment, performance reviews, and team coordination.</p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0;">
                    <span class="department-status status-active">Fully Functional</span>
                    <div>
                        <small>Employees: <?php echo $stats['total_employees']; ?> active</small><br>
                        <small>Leave Requests: <?php echo $stats['pending_leave_requests']; ?> pending</small>
                    </div>
                </div>
                <a href="departments/hr.php" class="btn">Access HR</a>
            </div>
            
            <div class="department-card" onclick="window.location.href='departments/clients.php'">
                <h3>🏢 Clients Department</h3>
                <p>Centralized client management, relationship tracking, and communication history.</p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0;">
                    <span class="department-status status-active">Fully Functional</span>
                    <div>
                        <small>Active: <?php echo $stats['active_clients']; ?> clients</small><br>
                        <small>Total: <?php echo $stats['total_clients']; ?></small>
                    </div>
                </div>
                <a href="departments/clients.php" class="btn">Access Clients</a>
            </div>
        </div>
    </div>
     <script src="js/notification.js"></script>                            
    <script>
        
        // Charts Configuration
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        // Project Status Chart
        const projectStatusCtx = document.getElementById('projectStatusChart').getContext('2d');
        new Chart(projectStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['In Progress', 'Completed', 'Pending'],
                datasets: [{
                    data: [
                        <?php echo $stats['in_progress_projects']; ?>,
                        <?php echo $stats['completed_projects']; ?>,
                        <?php echo max($stats['total_projects'] - $stats['in_progress_projects'] - $stats['completed_projects'], 0); ?>
                    ],
                    backgroundColor: ['#36A2EB', '#4BC0C0', '#FFCE56'],
                    borderWidth: 0
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Monthly Trends Chart
        const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
        new Chart(monthlyTrendsCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'Projects Completed',
                        data: <?php echo json_encode($monthly_trends['projects_completed']); ?>,
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Campaigns Launched', 
                        data: <?php echo json_encode($monthly_trends['campaigns_launched']); ?>,
                        borderColor: '#4BC0C0',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Leads Generated',
                        data: <?php echo json_encode($monthly_trends['leads_generated']); ?>,
                        borderColor: '#FF6B35',
                        backgroundColor: 'rgba(255, 107, 53, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        // Department Distribution Chart
        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        new Chart(departmentCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($department_data)); ?>,
                datasets: [{
                    label: 'Team Members',
                    data: <?php echo json_encode(array_values($department_data)); ?>,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB', 
                        '#FF6B35',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>