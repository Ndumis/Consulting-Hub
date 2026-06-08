<?php
require_once '../config/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

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
Security::requireDepartmentAccess('Business Development');

$database = new Database();
$db = $database->getConnection();

// Get user info
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];
$email = $_SESSION['email'];

// Handle new lead creation
if ($_POST && isset($_POST['create_lead'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    
    $company_name = Security::sanitizeInput($_POST['company_name']);
    $contact_person = Security::sanitizeInput($_POST['contact_person']);
    $email = Security::sanitizeInput($_POST['email']);
    $phone = Security::sanitizeInput($_POST['phone']);
    $industry = Security::sanitizeInput($_POST['industry']);
    $notes = Security::sanitizeInput($_POST['notes']);
    
    $query = "INSERT INTO bd_leads (company_name, contact_person, email, phone, industry, notes, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$company_name, $contact_person, $email, $phone, $industry, $notes, $user_id]);
    
    header("Location: bd.php?view=leads&success=lead_created");
    exit();
}

// Handle lead updates
if ($_POST && isset($_POST['update_lead'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    
    $lead_id = (int)$_POST['lead_id'];
    $company_name = Security::sanitizeInput($_POST['company_name']);
    $contact_person = Security::sanitizeInput($_POST['contact_person']);
    $email = Security::sanitizeInput($_POST['email']);
    $phone = Security::sanitizeInput($_POST['phone']);
    $industry = Security::sanitizeInput($_POST['industry']);
    $status = Security::sanitizeInput($_POST['status']);
    $lead_score = (int)$_POST['lead_score'];
    $next_follow_up = Security::sanitizeInput($_POST['next_follow_up']);
    $notes = Security::sanitizeInput($_POST['notes']);
    
    $query = "UPDATE bd_leads SET company_name = ?, contact_person = ?, email = ?, phone = ?, industry = ?, status = ?, lead_score = ?, next_follow_up = ?, notes = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$company_name, $contact_person, $email, $phone, $industry, $status, $lead_score, $next_follow_up, $notes, $lead_id]);
    
    header("Location: bd.php?view=leads&success=lead_updated");
    exit();
}

// Handle activity logging
if ($_POST && isset($_POST['log_activity'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    
    $lead_id = !empty($_POST['lead_id']) ? (int)$_POST['lead_id'] : null;
    $activity_type = Security::sanitizeInput($_POST['activity_type']);
    $activity_date = Security::sanitizeInput($_POST['activity_date']);
    $description = Security::sanitizeInput($_POST['description']);
    $outcome = Security::sanitizeInput($_POST['outcome']);
    $next_action = Security::sanitizeInput($_POST['next_action']);
    $next_action_date = Security::sanitizeInput($_POST['next_action_date']);
    
    $query = "INSERT INTO bd_activities (lead_id, activity_type, activity_date, description, outcome, next_action, next_action_date, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$lead_id, $activity_type, $activity_date, $description, $outcome, $next_action, $next_action_date, $user_id]);
    
    // Update lead's last contact date if activity is linked to a lead
    if ($lead_id) {
        $query = "UPDATE bd_leads SET last_contact_date = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$activity_date, $lead_id]);
    }
    
    header("Location: bd.php?view=activities&success=activity_logged");
    exit();
}

// Handle task creation
if ($_POST && isset($_POST['create_task'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    
    $task_description = Security::sanitizeInput($_POST['task_description']);
    $due_date = Security::sanitizeInput($_POST['due_date']);
    $assigned_to = (int)$_POST['assigned_to'];
    $related_lead_id = !empty($_POST['related_lead_id']) ? (int)$_POST['related_lead_id'] : null;
    $priority = Security::sanitizeInput($_POST['priority']);
    
    $query = "INSERT INTO bd_tasks (task_description, due_date, assigned_to, related_lead_id, priority, created_by) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$task_description, $due_date, $assigned_to, $related_lead_id, $priority, $user_id]);
    
    header("Location: bd.php?view=tasks&success=task_created");
    exit();
}

// Handle task completion
if ($_POST && isset($_POST['complete_task'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    
    $task_id = (int)$_POST['task_id'];
    
    $query = "UPDATE bd_tasks SET status = 'completed' WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$task_id]);
    
    header("Location: bd.php?view=tasks&success=task_completed");
    exit();
}

// Handle target setting
if ($_POST && isset($_POST['set_targets'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    
    $month_year = Security::sanitizeInput($_POST['month_year']) . '-01'; // Add day for DATE format
    $lead_target = (int)$_POST['lead_target'];
    $meeting_target = (int)$_POST['meeting_target'];
    $client_target = (int)$_POST['client_target'];
    
    try {
        // Check if targets already exist for this month
        $query = "SELECT id FROM bd_targets WHERE month_year = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$month_year]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $query = "UPDATE bd_targets SET lead_target = ?, meeting_target = ?, client_target = ? WHERE month_year = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$lead_target, $meeting_target, $client_target, $month_year]);
        } else {
            $query = "INSERT INTO bd_targets (month_year, lead_target, meeting_target, client_target) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$month_year, $lead_target, $meeting_target, $client_target]);
        }
        
        header("Location: bd.php?view=targets&success=targets_updated");
        exit();
    } catch (Exception $e) {
        // Log error and show message
        error_log("Target setting error: " . $e->getMessage());
        header("Location: bd.php?view=targets&error=targets_failed");
        exit();
    }
}

// Get current view parameter
$view = $_GET['view'] ?? 'overview';

// Get current month targets
$current_month = date('Y-m-01');
$targets = [
    'lead_target' => 15,
    'meeting_target' => 5,
    'client_target' => 1
];

try {
    $query = "SELECT lead_target, meeting_target, client_target FROM bd_targets WHERE month_year = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$current_month]);
    $target_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($target_data) {
        $targets = $target_data;
    }
} catch (Exception $e) {
    // Table might not exist yet
}

// Get BD statistics
$stats = [
    'total_leads' => 0,
    'new_leads' => 0,
    'meetings_booked' => 0,
    'clients_converted' => 0,
    'weekly_calls' => 0,
    'weekly_emails' => 0,
    'weekly_meetings' => 0
];

try {
    // Total leads
    $query = "SELECT COUNT(*) as count FROM bd_leads";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_leads'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // New leads this month
    $query = "SELECT COUNT(*) as count FROM bd_leads WHERE created_at >= ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$current_month]);
    $stats['new_leads'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Meetings booked this month (from activities)
    $query = "SELECT COUNT(DISTINCT lead_id) as count FROM bd_activities 
              WHERE activity_type = 'meeting' 
              AND DATE(activity_date) >= ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$current_month]);
    $stats['meetings_booked'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Clients converted (leads with status 'client')
    $query = "SELECT COUNT(*) as count FROM bd_leads WHERE status = 'client'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['clients_converted'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Weekly activities
    $query = "SELECT activity_type, COUNT(*) as count FROM bd_activities 
              WHERE activity_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
              GROUP BY activity_type";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $weekly_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($weekly_activities as $activity) {
        if ($activity['activity_type'] == 'call') {
            $stats['weekly_calls'] = $activity['count'];
        } elseif ($activity['activity_type'] == 'email') {
            $stats['weekly_emails'] = $activity['count'];
        } elseif ($activity['activity_type'] == 'meeting') {
            $stats['weekly_meetings'] = $activity['count'];
        }
    }
} catch (Exception $e) {
    error_log("Statistics error: " . $e->getMessage());
    // Continue with default values
}

// Get all leads
$query = "SELECT * FROM bd_leads ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all activities
$query = "SELECT a.*, l.company_name 
          FROM bd_activities a 
          LEFT JOIN bd_leads l ON a.lead_id = l.id 
          ORDER BY a.activity_date DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$all_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all tasks
$query = "SELECT t.*, l.company_name, u.username as assigned_username 
          FROM bd_tasks t 
          LEFT JOIN bd_leads l ON t.related_lead_id = l.id 
          LEFT JOIN users u ON t.assigned_to = u.id 
          ORDER BY t.due_date ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$all_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all targets
$query = "SELECT * FROM bd_targets ORDER BY month_year DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$all_targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activities for overview
$recent_activities = [];
try {
    $query = "SELECT a.*, l.company_name 
              FROM bd_activities a 
              LEFT JOIN bd_leads l ON a.lead_id = l.id 
              ORDER BY a.activity_date DESC 
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
}

// Get pending tasks for overview
$pending_tasks = [];
try {
    $query = "SELECT t.*, l.company_name 
              FROM bd_tasks t 
              LEFT JOIN bd_leads l ON t.related_lead_id = l.id 
              WHERE t.status = 'pending' 
              ORDER BY t.due_date ASC 
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $pending_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
}

// Get high-potential leads
$high_potential_leads = [];
try {
    $query = "SELECT * FROM bd_leads 
              WHERE lead_score >= 70 AND status NOT IN ('client') 
              ORDER BY lead_score DESC 
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $high_potential_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
}

// Get all users for task assignment
$query = "SELECT id, username FROM users WHERE role = 'employee'";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Development - Business Management</title>
    <link rel="stylesheet" href="../css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-number-large {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label-large {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .weekly-progress {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .progress-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .progress-label {
            width: 120px;
            font-weight: 500;
        }
        
        .progress-bar {
            flex: 1;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #45a049);
            transition: width 0.3s ease;
        }
        
        .progress-count {
            width: 80px;
            text-align: right;
            font-weight: 500;
        }
        
        .lead-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            background: white;
            margin-bottom: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .lead-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-new { background: #fff3cd; color: #856404; }
        .status-contacted { background: #cce5ff; color: #004085; }
        .status-meeting_booked { background: #d4edda; color: #155724; }
        .status-proposal_sent { background: #d1ecf1; color: #0c5460; }
        .status-client { background: #d4edda; color: #155724; }
        
        .priority-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .priority-high { background: #f8d7da; color: #721c24; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-low { background: #d1ecf1; color: #0c5460; }
        
        .activity-timeline {
            border-left: 3px solid #007bff;
            padding-left: 1rem;
            margin-left: 1rem;
        }
        
        .activity-item {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .activity-item::before {
            content: '';
            position: absolute;
            left: -1.5rem;
            top: 0.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #007bff;
        }
        
        .activity-icon {
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }

        /* Navigation Tabs Styles */
        .nav-tabs {
            display: flex;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
            flex-wrap: wrap;
            gap: 4px;
        }

        .nav-tab {
            padding: 12px 20px;
            text-decoration: none;
            color: #495057;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 0;
            justify-content: center;
            text-align: center;
            white-space: nowrap;
        }

        .nav-tab:hover {
            background: #e9ecef;
            color: #495057;
            transform: translateY(-1px);
        }

        .nav-tab.active {
            background: #007bff;
            color: white;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
        }

        .nav-tab.active:hover {
            background: #0056b3;
            color: white;
            transform: translateY(-1px);
        }

        /* Data table styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        /* Responsive design for smaller screens */
        @media (max-width: 768px) {
            .nav-tabs {
                flex-direction: column;
                padding: 8px;
            }
            
            .nav-tab {
                padding: 10px 16px;
                justify-content: flex-start;
            }

            .data-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>🎯 Business Development</h1>
        <div class="nav-tabs">
            <a href="?view=overview" class="nav-tab <?php echo $view === 'overview' ? 'active' : ''; ?>">📊 Overview</a>
            <a href="?view=leads" class="nav-tab <?php echo $view === 'leads' ? 'active' : ''; ?>">📋 Leads Management</a>
            <a href="?view=activities" class="nav-tab <?php echo $view === 'activities' ? 'active' : ''; ?>">📞 Activity Log</a>
            <a href="?view=tasks" class="nav-tab <?php echo $view === 'tasks' ? 'active' : ''; ?>">✅ Tasks & Follow-ups</a>
            <a href="?view=targets" class="nav-tab <?php echo $view === 'targets' ? 'active' : ''; ?>">🎯 Targets & Performance</a>
            <a href="?view=reports" class="nav-tab <?php echo $view === 'reports' ? 'active' : ''; ?>">📊 Reports</a>
        </div>
        
        <div class="section">
            <div class="section-content">
                <?php if ($view === 'overview'): ?>
                    <!-- Overview Dashboard -->
                    <div class="stats-overview">
                        <div class="stat-card">
                            <div class="stat-number-large"><?php echo $stats['new_leads']; ?>/<?php echo $targets['lead_target']; ?></div>
                            <div class="stat-label-large">Monthly Leads</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number-large"><?php echo $stats['meetings_booked']; ?>/<?php echo $targets['meeting_target']; ?></div>
                            <div class="stat-label-large">Meetings Booked</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number-large"><?php echo $stats['clients_converted']; ?>/<?php echo $targets['client_target']; ?></div>
                            <div class="stat-label-large">Clients Converted</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number-large"><?php echo $stats['total_leads']; ?></div>
                            <div class="stat-label-large">Total Leads</div>
                        </div>
                    </div>

                    <div class="dashboard-row">
                        <!-- Left Column -->
                        <div>
                            <!-- Weekly Progress -->
                            <div class="chart-container">
                                <h3>📊 Weekly Activity Progress</h3>
                                <div class="weekly-progress">
                                    <div class="progress-item">
                                        <span class="progress-label">Calls Made</span>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo min(($stats['weekly_calls'] / 30) * 100, 100); ?>%"></div>
                                        </div>
                                        <span class="progress-count"><?php echo $stats['weekly_calls']; ?>/20</span>
                                    </div>
                                    <div class="progress-item">
                                        <span class="progress-label">Emails Sent</span>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo min(($stats['weekly_emails'] / 30) * 100, 100); ?>%"></div>
                                        </div>
                                        <span class="progress-count"><?php echo $stats['weekly_emails']; ?>/30</span>
                                    </div>
                                    <div class="progress-item">
                                        <span class="progress-label">Emails Sent</span>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo min(($stats['weekly_meetings'] / 30) * 100, 100); ?>%"></div>
                                        </div>
                                        <span class="progress-count"><?php echo $stats['weekly_meetings']; ?>/30</span>
                                    </div>
                                </div>
                            </div>

                            <!-- High Potential Leads -->
                            <div class="chart-container">
                                <h3>🔥 High Potential Leads</h3>
                                <?php if (empty($high_potential_leads)): ?>
                                    <div style="text-align: center; padding: 2rem; color: #666;">
                                        <p>No high-potential leads yet.</p>
                                        <p><a href="?view=leads#create" class="btn">Add Your First Lead</a></p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($high_potential_leads as $lead): ?>
                                        <div class="lead-card">
                                            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 0.5rem;">
                                                <h4 style="margin: 0; flex: 1;"><?php echo Security::escapeHTML($lead['company_name']); ?></h4>
                                                <span style="background: #ffeb3b; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;">
                                                    Score: <?php echo $lead['lead_score']; ?>/100
                                                </span>
                                            </div>
                                            <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">
                                                <?php if ($lead['contact_person']): ?>
                                                    <span>👤 <?php echo Security::escapeHTML($lead['contact_person']); ?></span> • 
                                                <?php endif; ?>
                                                <span>📧 <?php echo Security::escapeHTML($lead['email']); ?></span>
                                            </div>
                                            <div style="margin-bottom: 1rem;">
                                                <span class="status-badge status-<?php echo $lead['status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $lead['status'])); ?>
                                                </span>
                                                <?php if ($lead['next_follow_up']): ?>
                                                    <span style="background: #fff3e0; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; margin-left: 0.5rem;">
                                                        📅 Follow-up: <?php echo date('M j', strtotime($lead['next_follow_up'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="?view=leads&action=edit&id=<?php echo $lead['id']; ?>" class="btn btn-small">View</a>
                                                <a href="?view=activities&lead_id=<?php echo $lead['id']; ?>" class="btn btn-small">Log Activity</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div>
                            <!-- Recent Activities -->
                            <div class="chart-container">
                                <h3>📋 Recent Activities</h3>
                                <?php if (empty($recent_activities)): ?>
                                    <div style="text-align: center; padding: 2rem; color: #666;">
                                        <p>No activities logged yet.</p>
                                        <p><a href="?view=activities#create" class="btn">Log Your First Activity</a></p>
                                    </div>
                                <?php else: ?>
                                    <div class="activity-timeline">
                                        <?php foreach ($recent_activities as $activity): ?>
                                            <div class="activity-item">
                                                <div style="display: flex; justify-content: between; align-items: flex-start;">
                                                    <div style="flex: 1;">
                                                        <div style="font-weight: bold; margin-bottom: 0.25rem;">
                                                            <?php 
                                                            $icons = [
                                                                'call' => '📞',
                                                                'email' => '📧', 
                                                                'meeting' => '👥',
                                                                'follow_up' => '🔔',
                                                                'proposal' => '📄'
                                                            ];
                                                            echo $icons[$activity['activity_type']] ?? '📝';
                                                            ?>
                                                            <?php echo Security::escapeHTML($activity['company_name'] ?? 'General Activity'); ?>
                                                        </div>
                                                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.25rem;">
                                                            <?php echo Security::escapeHTML($activity['description']); ?>
                                                        </div>
                                                        <?php if ($activity['outcome']): ?>
                                                            <div style="color: #888; font-size: 0.8rem;">
                                                                Outcome: <?php echo Security::escapeHTML($activity['outcome']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div style="text-align: right; font-size: 0.8rem; color: #999;">
                                                        <?php echo date('M j, g:i A', strtotime($activity['activity_date'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Pending Tasks -->
                            <div class="chart-container">
                                <h3>✅ Pending Tasks</h3>
                                <?php if (empty($pending_tasks)): ?>
                                    <div style="text-align: center; padding: 2rem; color: #666;">
                                        <p>No pending tasks.</p>
                                        <p><a href="?view=tasks#create" class="btn">Create Your First Task</a></p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($pending_tasks as $task): ?>
                                        <div class="lead-card">
                                            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 0.5rem;">
                                                <h4 style="margin: 0; flex: 1;"><?php echo Security::escapeHTML($task['task_description']); ?></h4>
                                                <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                                    <?php echo ucfirst($task['priority']); ?>
                                                </span>
                                            </div>
                                            <?php if ($task['company_name']): ?>
                                                <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">
                                                    Related to: <?php echo Security::escapeHTML($task['company_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div style="display: flex; justify-content: between; align-items: center;">
                                                <span style="font-size: 0.8rem; color: #999;">
                                                    Due: <?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'No deadline'; ?>
                                                </span>
                                                <form method="post" style="display: inline;">
                                                    <?php echo Security::getCSRFTokenField(); ?>
                                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                    <button type="submit" name="complete_task" class="btn btn-small" onclick="return confirm('Mark this task as complete?')">
                                                        Mark Complete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <?php elseif ($view === 'leads'): ?>
                    <!-- Leads Management -->
                    <div class="section no-nav" id="create">
                        <div class="section-header">Add New Lead</div>
                        <div class="section-content">
                            <form method="post">
                                <?php echo Security::getCSRFTokenField(); ?>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="company_name">Company Name:</label>
                                        <input type="text" id="company_name" name="company_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="contact_person">Contact Person:</label>
                                        <input type="text" id="contact_person" name="contact_person" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email:</label>
                                        <input type="email" id="email" name="email" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Phone:</label>
                                        <input type="tel" id="phone" name="phone">
                                    </div>
                                    <div class="form-group">
                                        <label for="industry">Industry:</label>
                                        <select id="industry" name="industry" required>
                                            <option value="insurance">Insurance</option>
                                            <option value="finance">Finance</option>
                                            <option value="technology">Technology</option>
                                            <option value="healthcare">Healthcare</option>
                                            <option value="retail">Retail</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="notes">Notes:</label>
                                    <textarea id="notes" name="notes" rows="4" placeholder="Add any relevant notes about this lead..."></textarea>
                                </div>
                                <button type="submit" name="create_lead" class="btn">Add Lead</button>
                            </form>
                        </div>
                    </div>

                    <div class="section no-nav">
                        <div class="section-header">All Leads (<?php echo count($leads); ?>)</div>
                        <div class="section-content">
                            <?php if (empty($leads)): ?>
                                <div style="text-align: center; padding: 2rem; color: #666;">
                                    <p>No leads added yet.</p>
                                    <p>Add your first lead using the form above to start building your pipeline.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($leads as $lead): ?>
                                    <div class="lead-card">
                                        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1rem;">
                                            <h3 style="margin: 0; flex: 1;"><?php echo Security::escapeHTML($lead['company_name']); ?></h3>
                                            <span class="status-badge status-<?php echo $lead['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $lead['status'])); ?>
                                            </span>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                            <div>
                                                <strong>Contact:</strong> <?php echo Security::escapeHTML($lead['contact_person']); ?><br>
                                                <strong>Email:</strong> <?php echo Security::escapeHTML($lead['email']); ?><br>
                                                <strong>Phone:</strong> <?php echo Security::escapeHTML($lead['phone']); ?>
                                            </div>
                                            <div>
                                                <strong>Industry:</strong> <?php echo Security::escapeHTML(ucfirst($lead['industry'])); ?><br>
                                                <strong>Lead Score:</strong> <?php echo $lead['lead_score']; ?>/100<br>
                                                <?php if ($lead['next_follow_up']): ?>
                                                    <strong>Next Follow-up:</strong> <?php echo date('M j, Y', strtotime($lead['next_follow_up'])); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($lead['notes']): ?>
                                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                                                <strong>Notes:</strong> <?php echo Security::escapeHTML($lead['notes']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button onclick="editLead(<?php echo $lead['id']; ?>)" class="btn btn-small">Edit</button>
                                            <a href="?view=activities&lead_id=<?php echo $lead['id']; ?>" class="btn btn-small">Log Activity</a>
                                            <a href="?view=tasks&lead_id=<?php echo $lead['id']; ?>" class="btn btn-small">Add Task</a>
                                        </div>
                                        
                                        <div id="edit-lead-<?php echo $lead['id']; ?>" class="update-form" style="display: none;">
                                            <form method="post">
                                                <?php echo Security::getCSRFTokenField(); ?>
                                                <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                                <div class="form-grid">
                                                    <div class="form-group">
                                                        <label>Company Name:</label>
                                                        <input type="text" name="company_name" value="<?php echo Security::escapeHTML($lead['company_name']); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Contact Person:</label>
                                                        <input type="text" name="contact_person" value="<?php echo Security::escapeHTML($lead['contact_person']); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Email:</label>
                                                        <input type="email" name="email" value="<?php echo Security::escapeHTML($lead['email']); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Phone:</label>
                                                        <input type="tel" name="phone" value="<?php echo Security::escapeHTML($lead['phone']); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Industry:</label>
                                                        <select name="industry" required>
                                                            <option value="insurance" <?php echo $lead['industry'] == 'insurance' ? 'selected' : ''; ?>>Insurance</option>
                                                            <option value="finance" <?php echo $lead['industry'] == 'finance' ? 'selected' : ''; ?>>Finance</option>
                                                            <option value="technology" <?php echo $lead['industry'] == 'technology' ? 'selected' : ''; ?>>Technology</option>
                                                            <option value="healthcare" <?php echo $lead['industry'] == 'healthcare' ? 'selected' : ''; ?>>Healthcare</option>
                                                            <option value="retail" <?php echo $lead['industry'] == 'retail' ? 'selected' : ''; ?>>Retail</option>
                                                            <option value="other" <?php echo $lead['industry'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Status:</label>
                                                        <select name="status" required>
                                                            <option value="new" <?php echo $lead['status'] == 'new' ? 'selected' : ''; ?>>New</option>
                                                            <option value="contacted" <?php echo $lead['status'] == 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                                            <option value="meeting_booked" <?php echo $lead['status'] == 'meeting_booked' ? 'selected' : ''; ?>>Meeting Booked</option>
                                                            <option value="proposal_sent" <?php echo $lead['status'] == 'proposal_sent' ? 'selected' : ''; ?>>Proposal Sent</option>
                                                            <option value="client" <?php echo $lead['status'] == 'client' ? 'selected' : ''; ?>>Client</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Lead Score (0-100):</label>
                                                        <input type="number" name="lead_score" min="0" max="100" value="<?php echo $lead['lead_score']; ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Next Follow-up:</label>
                                                        <input type="date" name="next_follow_up" value="<?php echo $lead['next_follow_up']; ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Notes:</label>
                                                    <textarea name="notes" rows="4"><?php echo Security::escapeHTML($lead['notes']); ?></textarea>
                                                </div>
                                                <button type="submit" name="update_lead" class="btn btn-small">Update Lead</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($view === 'activities'): ?>
                    <!-- Activity Logging -->
                    <div class="section no-nav" id="create">
                        <div class="section-header">Log New Activity</div>
                        <div class="section-content">
                            <form method="post">
                                <?php echo Security::getCSRFTokenField(); ?>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="activity_lead_id">Related Lead (Optional):</label>
                                        <select id="activity_lead_id" name="lead_id">
                                            <option value="">Select Lead</option>
                                            <?php foreach ($leads as $lead): ?>
                                                <option value="<?php echo $lead['id']; ?>"><?php echo Security::escapeHTML($lead['company_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="activity_type">Activity Type:</label>
                                        <select id="activity_type" name="activity_type" required>
                                            <option value="call">Phone Call</option>
                                            <option value="email">Email</option>
                                            <option value="meeting">Meeting</option>
                                            <option value="follow_up">Follow-up</option>
                                            <option value="proposal">Proposal Sent</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="activity_date">Date & Time:</label>
                                        <input type="datetime-local" id="activity_date" name="activity_date" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="activity_description">Description:</label>
                                    <textarea id="activity_description" name="description" rows="4" required placeholder="Describe the activity..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="outcome">Outcome:</label>
                                    <textarea id="outcome" name="outcome" rows="3" placeholder="What was the result of this activity?"></textarea>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="next_action">Next Action:</label>
                                        <input type="text" id="next_action" name="next_action" placeholder="What needs to happen next?">
                                    </div>
                                    <div class="form-group">
                                        <label for="next_action_date">Next Action Date:</label>
                                        <input type="date" id="next_action_date" name="next_action_date">
                                    </div>
                                </div>
                                <button type="submit" name="log_activity" class="btn">Log Activity</button>
                            </form>
                        </div>
                    </div>

                    <!-- All Activities Display -->
                    <div class="section no-nav">
                        <div class="section-header">All Activities (<?php echo count($all_activities); ?>)</div>
                        <div class="section-content">
                            <?php if (empty($all_activities)): ?>
                                <div style="text-align: center; padding: 2rem; color: #666;">
                                    <p>No activities logged yet.</p>
                                    <p>Log your first activity using the form above.</p>
                                </div>
                            <?php else: ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Lead</th>
                                            <th>Date & Time</th>
                                            <th>Description</th>
                                            <th>Outcome</th>
                                            <th>Next Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_activities as $activity): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $icons = [
                                                        'call' => '📞 Call',
                                                        'email' => '📧 Email', 
                                                        'meeting' => '👥 Meeting',
                                                        'follow_up' => '🔔 Follow-up',
                                                        'proposal' => '📄 Proposal'
                                                    ];
                                                    echo $icons[$activity['activity_type']] ?? '📝 Activity';
                                                    ?>
                                                </td>
                                                <td><?php echo Security::escapeHTML($activity['company_name'] ?? 'General'); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($activity['activity_date'])); ?></td>
                                                <td><?php echo Security::escapeHTML($activity['description']); ?></td>
                                                <td><?php echo Security::escapeHTML($activity['outcome'] ?? '-'); ?></td>
                                                <td>
                                                    <?php if ($activity['next_action']): ?>
                                                        <?php echo Security::escapeHTML($activity['next_action']); ?>
                                                        <?php if ($activity['next_action_date']): ?>
                                                            <br><small>by <?php echo date('M j, Y', strtotime($activity['next_action_date'])); ?></small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                <?php elseif ($view === 'tasks'): ?>
                    <!-- Task Management -->
                    <div class="section no-nav" id="create">
                        <div class="section-header">Create New Task</div>
                        <div class="section-content">
                            <form method="post">
                                <?php echo Security::getCSRFTokenField(); ?>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="task_description">Task Description:</label>
                                        <input type="text" id="task_description" name="task_description" required placeholder="What needs to be done?">
                                    </div>
                                    <div class="form-group">
                                        <label for="due_date">Due Date:</label>
                                        <input type="datetime-local" id="due_date" name="due_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="assigned_to">Assign To:</label>
                                        <select id="assigned_to" name="assigned_to" required>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>" <?php echo $user['id'] == $user_id ? 'selected' : ''; ?>>
                                                    <?php echo Security::escapeHTML($user['username']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="task_lead_id">Related Lead (Optional):</label>
                                        <select id="task_lead_id" name="related_lead_id">
                                            <option value="">Select Lead</option>
                                            <?php foreach ($leads as $lead): ?>
                                                <option value="<?php echo $lead['id']; ?>"><?php echo Security::escapeHTML($lead['company_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="priority">Priority:</label>
                                        <select id="priority" name="priority" required>
                                            <option value="low">Low</option>
                                            <option value="medium" selected>Medium</option>
                                            <option value="high">High</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" name="create_task" class="btn">Create Task</button>
                            </form>
                        </div>
                    </div>

                    <!-- All Tasks Display -->
                    <div class="section no-nav">
                        <div class="section-header">All Tasks (<?php echo count($all_tasks); ?>)</div>
                        <div class="section-content">
                            <?php if (empty($all_tasks)): ?>
                                <div style="text-align: center; padding: 2rem; color: #666;">
                                    <p>No tasks created yet.</p>
                                    <p>Create your first task using the form above.</p>
                                </div>
                            <?php else: ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Task</th>
                                            <th>Related Lead</th>
                                            <th>Assigned To</th>
                                            <th>Due Date</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_tasks as $task): ?>
                                            <tr>
                                                <td><?php echo Security::escapeHTML($task['task_description']); ?></td>
                                                <td><?php echo Security::escapeHTML($task['company_name'] ?? '-'); ?></td>
                                                <td><?php echo Security::escapeHTML($task['assigned_username']); ?></td>
                                                <td>
                                                    <?php if ($task['due_date']): ?>
                                                        <?php echo date('M j, Y g:i A', strtotime($task['due_date'])); ?>
                                                        <?php if (strtotime($task['due_date']) < time() && $task['status'] == 'pending'): ?>
                                                            <br><span style="color: #dc3545; font-size: 0.8rem;">Overdue</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                                        <?php echo ucfirst($task['priority']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $task['status']; ?>">
                                                        <?php echo ucfirst($task['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($task['status'] == 'pending'): ?>
                                                        <form method="post" style="display: inline;">
                                                            <?php echo Security::getCSRFTokenField(); ?>
                                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                            <button type="submit" name="complete_task" class="btn btn-small" onclick="return confirm('Mark this task as complete?')">
                                                                Complete
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span style="color: #28a745;">Completed</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                <?php elseif ($view === 'targets'): ?>
                    <!-- Targets & Performance -->
                    <div class="section no-nav">
                        <div class="section-header">Set Monthly Targets</div>
                        <div class="section-content">
                            <form method="post">
                                <?php echo Security::getCSRFTokenField(); ?>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="month_year">Month:</label>
                                        <input type="month" id="month_year" name="month_year" required value="<?php echo date('Y-m'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="lead_target">Lead Target:</label>
                                        <input type="number" id="lead_target" name="lead_target" min="0" required value="<?php echo $targets['lead_target']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="meeting_target">Meeting Target:</label>
                                        <input type="number" id="meeting_target" name="meeting_target" min="0" required value="<?php echo $targets['meeting_target']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="client_target">Client Target:</label>
                                        <input type="number" id="client_target" name="client_target" min="0" required value="<?php echo $targets['client_target']; ?>">
                                    </div>
                                </div>
                                <button type="submit" name="set_targets" class="btn">Set Targets</button>
                            </form>
                        </div>
                    </div>

                    <!-- All Targets Display -->
                    <div class="section no-nav">
                        <div class="section-header">All Targets (<?php echo count($all_targets); ?>)</div>
                        <div class="section-content">
                            <?php if (empty($all_targets)): ?>
                                <div style="text-align: center; padding: 2rem; color: #666;">
                                    <p>No targets set yet.</p>
                                    <p>Set your first targets using the form above.</p>
                                </div>
                            <?php else: ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Lead Target</th>
                                            <th>Meeting Target</th>
                                            <th>Client Target</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_targets as $target): ?>
                                            <tr>
                                                <td><?php echo date('F Y', strtotime($target['month_year'])); ?></td>
                                                <td><?php echo $target['lead_target']; ?></td>
                                                <td><?php echo $target['meeting_target']; ?></td>
                                                <td><?php echo $target['client_target']; ?></td>
                                                <td><?php echo date('M j, Y', strtotime($target['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Performance Dashboard -->
                    <div class="section no-nav">
                        <div class="section-header">Performance vs Targets</div>
                        <div class="section-content">
                            <div class="stats-overview">
                                <div class="stat-card">
                                    <div class="stat-number-large"><?php echo $stats['new_leads']; ?>/<?php echo $targets['lead_target']; ?></div>
                                    <div class="stat-label-large">Leads This Month</div>
                                    <div style="margin-top: 0.5rem;">
                                        <?php $lead_percentage = $targets['lead_target'] > 0 ? min(($stats['new_leads'] / $targets['lead_target']) * 100, 100) : 0; ?>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $lead_percentage; ?>%"></div>
                                        </div>
                                        <small><?php echo round($lead_percentage, 1); ?>%</small>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number-large"><?php echo $stats['meetings_booked']; ?>/<?php echo $targets['meeting_target']; ?></div>
                                    <div class="stat-label-large">Meetings Booked</div>
                                    <div style="margin-top: 0.5rem;">
                                        <?php $meeting_percentage = $targets['meeting_target'] > 0 ? min(($stats['meetings_booked'] / $targets['meeting_target']) * 100, 100) : 0; ?>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $meeting_percentage; ?>%"></div>
                                        </div>
                                        <small><?php echo round($meeting_percentage, 1); ?>%</small>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number-large"><?php echo $stats['clients_converted']; ?>/<?php echo $targets['client_target']; ?></div>
                                    <div class="stat-label-large">Clients Converted</div>
                                    <div style="margin-top: 0.5rem;">
                                        <?php $client_percentage = $targets['client_target'] > 0 ? min(($stats['clients_converted'] / $targets['client_target']) * 100, 100) : 0; ?>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $client_percentage; ?>%"></div>
                                        </div>
                                        <small><?php echo round($client_percentage, 1); ?>%</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($view === 'reports'): ?>
                    <!-- Reports -->
                    <div class="section no-nav">
                        <div class="section-header">Business Development Reports & Analytics</div>
                        <div class="section-content">
                            
                            <!-- Quick Stats -->
                            <div class="stats-overview">
                                <div class="stat-card">
                                    <div class="stat-number-large"><?php echo $stats['total_leads']; ?></div>
                                    <div class="stat-label-large">Total Leads</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number-large"><?php echo $stats['clients_converted']; ?></div>
                                    <div class="stat-label-large">Clients Converted</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number-large"><?php echo $stats['meetings_booked']; ?></div>
                                    <div class="stat-label-large">Meetings This Month</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number-large">
                                        <?php 
                                        $conversion_rate = $stats['total_leads'] > 0 ? 
                                            round(($stats['clients_converted'] / $stats['total_leads']) * 100, 1) : 0;
                                        echo $conversion_rate; ?>%
                                    </div>
                                    <div class="stat-label-large">Conversion Rate</div>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
                                
                                <!-- Lead Status Report -->
                                <div class="chart-container">
                                    <h3>📊 Lead Status Distribution</h3>
                                    <?php
                                    try {
                                        $query = "SELECT status, COUNT(*) as count FROM bd_leads GROUP BY status";
                                        $stmt = $db->prepare($query);
                                        $stmt->execute();
                                        $lead_statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        if (!empty($lead_statuses)):
                                    ?>
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Status</th>
                                                    <th>Count</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($lead_statuses as $status): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="status-badge status-<?php echo $status['status']; ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $status['status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $status['count']; ?></td>
                                                        <td>
                                                            <?php 
                                                            $percentage = $stats['total_leads'] > 0 ? 
                                                                round(($status['count'] / $stats['total_leads']) * 100, 1) : 0;
                                                            echo $percentage; ?>%
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p style="text-align: center; color: #666; padding: 2rem;">No lead data available.</p>
                                    <?php endif; ?>
                                    <?php } catch (Exception $e) { ?>
                                        <p style="text-align: center; color: #666; padding: 2rem;">Error loading lead status report.</p>
                                    <?php } ?>
                                </div>

                                <!-- Activity Report -->
                                <div class="chart-container">
                                    <h3>📞 Activity Summary (Last 30 Days)</h3>
                                    <?php
                                    try {
                                        $query = "SELECT activity_type, COUNT(*) as count 
                                                FROM bd_activities 
                                                WHERE activity_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                                GROUP BY activity_type 
                                                ORDER BY count DESC";
                                        $stmt = $db->prepare($query);
                                        $stmt->execute();
                                        $activity_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        if (!empty($activity_summary)):
                                    ?>
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Activity Type</th>
                                                    <th>Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($activity_summary as $activity): ?>
                                                    <tr>
                                                        <td>
                                                            <?php 
                                                            $icons = [
                                                                'call' => '📞 Call',
                                                                'email' => '📧 Email', 
                                                                'meeting' => '👥 Meeting',
                                                                'follow_up' => '🔔 Follow-up',
                                                                'proposal' => '📄 Proposal'
                                                            ];
                                                            echo $icons[$activity['activity_type']] ?? '📝 Activity';
                                                            ?>
                                                        </td>
                                                        <td><?php echo $activity['count']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p style="text-align: center; color: #666; padding: 2rem;">No activities in the last 30 days.</p>
                                    <?php endif; ?>
                                    <?php } catch (Exception $e) { ?>
                                        <p style="text-align: center; color: #666; padding: 2rem;">Error loading activity report.</p>
                                    <?php } ?>
                                </div>

                                <!-- Performance vs Targets -->
                                <div class="chart-container">
                                    <h3>🎯 Current Month Performance</h3>
                                    <div class="weekly-progress">
                                        <div class="progress-item">
                                            <span class="progress-label">Leads Target</span>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo min(($stats['new_leads'] / max($targets['lead_target'], 1)) * 100, 100); ?>%"></div>
                                            </div>
                                            <span class="progress-count"><?php echo $stats['new_leads']; ?>/<?php echo $targets['lead_target']; ?></span>
                                        </div>
                                        <div class="progress-item">
                                            <span class="progress-label">Meetings Target</span>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo min(($stats['meetings_booked'] / max($targets['meeting_target'], 1)) * 100, 100); ?>%"></div>
                                            </div>
                                            <span class="progress-count"><?php echo $stats['meetings_booked']; ?>/<?php echo $targets['meeting_target']; ?></span>
                                        </div>
                                        <div class="progress-item">
                                            <span class="progress-label">Clients Target</span>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo min(($stats['clients_converted'] / max($targets['client_target'], 1)) * 100, 100); ?>%"></div>
                                            </div>
                                            <span class="progress-count"><?php echo $stats['clients_converted']; ?>/<?php echo $targets['client_target']; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Industry Breakdown -->
                                <div class="chart-container">
                                    <h3>🏢 Lead Industry Distribution</h3>
                                    <?php
                                    try {
                                        $query = "SELECT industry, COUNT(*) as count FROM bd_leads GROUP BY industry ORDER BY count DESC";
                                        $stmt = $db->prepare($query);
                                        $stmt->execute();
                                        $industry_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        if (!empty($industry_breakdown)):
                                    ?>
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Industry</th>
                                                    <th>Leads</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($industry_breakdown as $industry): ?>
                                                    <tr>
                                                        <td><?php echo ucfirst($industry['industry']); ?></td>
                                                        <td><?php echo $industry['count']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p style="text-align: center; color: #666; padding: 2rem;">No industry data available.</p>
                                    <?php endif; ?>
                                    <?php } catch (Exception $e) { ?>
                                        <p style="text-align: center; color: #666; padding: 2rem;">Error loading industry report.</p>
                                    <?php } ?>
                                </div>

                            </div>

                            <!-- Export Options -->
                            <div class="section no-nav" style="margin-top: 2rem;">
                                <div class="section-header">Export Reports</div>
                                <div class="section-content">
                                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                        <button onclick="exportReport('leads')" class="btn">📥 Export Leads Report</button>
                                        <button onclick="exportReport('activities')" class="btn">📥 Export Activities Report</button>
                                        <button onclick="exportReport('performance')" class="btn">📥 Export Performance Report</button>
                                        <button onclick="exportReport('targets')" class="btn">📥 Export Targets Report</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="../js/notification.js"></script>                                       
    <script>
        function editLead(leadId) {
            const editForm = document.getElementById('edit-lead-' + leadId);
            if (editForm.style.display === 'none') {
                editForm.style.display = 'block';
            } else {
                editForm.style.display = 'none';
            }
        }

        function exportReport(type) {
            alert('Exporting ' + type + ' report...\nThis would generate a CSV/PDF file in a real implementation.');
            // In a real implementation, this would make an AJAX call to generate and download the report
            // window.location.href = 'export.php?type=' + type;
        }

        // Add error/success message handling
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                const successType = urlParams.get('success');
                let message = '';
                
                switch(successType) {
                    case 'targets_updated':
                        message = 'Targets updated successfully!';
                        break;
                    case 'lead_created':
                        message = 'Lead created successfully!';
                        break;
                    case 'activity_logged':
                        message = 'Activity logged successfully!';
                        break;
                    case 'task_created':
                        message = 'Task created successfully!';
                        break;
                }
                
                if (message) {
                    alert(message);
                }
            }
            
            if (urlParams.has('error')) {
                alert('An error occurred. Please try again.');
            }
        });
    </script>
</body>
</html>