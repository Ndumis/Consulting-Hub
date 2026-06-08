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
Security::requireDepartmentAccess('Insights');

$database = new Database();
$db = $database->getConnection();

// Tables exist in MySQL database

// Handle custom report creation
if ($_POST && isset($_POST['create_report'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Insights');
    
    $report_name = Security::sanitizeInput($_POST['report_name']);
    $report_type = Security::sanitizeInput($_POST['report_type']);
    $chart_type = Security::sanitizeInput($_POST['chart_type']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    $query = "INSERT INTO custom_reports (report_name, report_type, chart_type, created_by, is_public) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$report_name, $report_type, $chart_type, $_SESSION['user_id'], $is_public]);
}

// Calculate comprehensive analytics
$analytics = [];

// User and Department Analytics
$analytics['users'] = $db->query("SELECT COUNT(*) as total FROM users")->fetch(PDO::FETCH_ASSOC)['total'];
$analytics['departments'] = $db->query("SELECT department, COUNT(*) as count FROM users GROUP BY department")->fetchAll(PDO::FETCH_ASSOC);

// Client Analytics
$analytics['clients'] = [
    'total' => $db->query("SELECT COUNT(*) as total FROM clients")->fetch(PDO::FETCH_ASSOC)['total'],
    'active' => $db->query("SELECT COUNT(*) as total FROM clients WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC)['total'],
    'prospects' => $db->query("SELECT COUNT(*) as total FROM clients WHERE status = 'prospect'")->fetch(PDO::FETCH_ASSOC)['total'],
    'by_status' => $db->query("SELECT status, COUNT(*) as count FROM clients GROUP BY status")->fetchAll(PDO::FETCH_ASSOC)
];

// Project Analytics
$analytics['projects'] = [
    'total' => $db->query("SELECT COUNT(*) as total FROM projects")->fetch(PDO::FETCH_ASSOC)['total'],
    'in_progress' => $db->query("SELECT COUNT(*) as total FROM projects WHERE status = 'in_progress'")->fetch(PDO::FETCH_ASSOC)['total'],
    'completed' => $db->query("SELECT COUNT(*) as total FROM projects WHERE status = 'completed'")->fetch(PDO::FETCH_ASSOC)['total'],
    'by_category' => $db->query("SELECT category, COUNT(*) as count FROM projects GROUP BY category")->fetchAll(PDO::FETCH_ASSOC),
    'by_priority' => $db->query("SELECT priority, COUNT(*) as count FROM projects GROUP BY priority")->fetchAll(PDO::FETCH_ASSOC),
    'avg_progress' => $db->query("SELECT AVG(progress) as avg_progress FROM projects")->fetch(PDO::FETCH_ASSOC)['avg_progress']
];

// Finance Analytics (if tables exist)
try {
    $analytics['finance'] = [
        'quotations' => $db->query("SELECT COUNT(*) as total FROM quotations")->fetch(PDO::FETCH_ASSOC)['total'],
        'invoices' => $db->query("SELECT COUNT(*) as total FROM invoices")->fetch(PDO::FETCH_ASSOC)['total'],
        'total_revenue' => $db->query("SELECT SUM(total_amount) as total FROM invoices WHERE status = 'paid'")->fetch(PDO::FETCH_ASSOC)['total'] ?: 0,
        'pending_revenue' => $db->query("SELECT SUM(total_amount) as total FROM invoices WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['total'] ?: 0
    ];
} catch (PDOException $e) {
    $analytics['finance'] = ['quotations' => 0, 'invoices' => 0, 'total_revenue' => 0, 'pending_revenue' => 0];
}

// Marketing Analytics (if tables exist)
try {
    $analytics['marketing'] = [
        'campaigns' => $db->query("SELECT COUNT(*) as total FROM marketing_campaigns")->fetch(PDO::FETCH_ASSOC)['total'],
        'social_posts' => $db->query("SELECT COUNT(*) as total FROM social_media_posts")->fetch(PDO::FETCH_ASSOC)['total'],
        'email_campaigns' => $db->query("SELECT COUNT(*) as total FROM email_campaigns")->fetch(PDO::FETCH_ASSOC)['total'],
        'campaigns_by_type' => $db->query("SELECT campaign_type, COUNT(*) as count FROM marketing_campaigns GROUP BY campaign_type")->fetchAll(PDO::FETCH_ASSOC)
    ];
} catch (PDOException $e) {
    $analytics['marketing'] = ['campaigns' => 0, 'social_posts' => 0, 'email_campaigns' => 0, 'campaigns_by_type' => []];
}

// HR Analytics (if tables exist)
try {
    $analytics['hr'] = [
        'employees' => $db->query("SELECT COUNT(*) as total FROM hr_employees")->fetch(PDO::FETCH_ASSOC)['total'],
        'active_employees' => $db->query("SELECT COUNT(*) as total FROM hr_employees WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC)['total'],
        'leave_requests' => $db->query("SELECT COUNT(*) as total FROM hr_leave_requests")->fetch(PDO::FETCH_ASSOC)['total'],
        'pending_leaves' => $db->query("SELECT COUNT(*) as total FROM hr_leave_requests WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['total'],
        'performance_reviews' => $db->query("SELECT COUNT(*) as total FROM performance_reviews")->fetch(PDO::FETCH_ASSOC)['total'],
        'job_postings' => $db->query("SELECT COUNT(*) as total FROM job_postings WHERE status = 'open'")->fetch(PDO::FETCH_ASSOC)['total']
    ];
} catch (PDOException $e) {
    $analytics['hr'] = ['employees' => 0, 'active_employees' => 0, 'leave_requests' => 0, 'pending_leaves' => 0, 'performance_reviews' => 0, 'job_postings' => 0];
}

// Client Communications Analytics (if tables exist)
try {
    $analytics['communications'] = [
        'total_contacts' => $db->query("SELECT COUNT(*) as total FROM client_contacts")->fetch(PDO::FETCH_ASSOC)['total'],
        'primary_contacts' => $db->query("SELECT COUNT(*) as total FROM client_contacts WHERE is_primary = 1")->fetch(PDO::FETCH_ASSOC)['total'],
        'open_contacts' => $db->query("SELECT COUNT(*) as total FROM client_contacts WHERE status = 'open'")->fetch(PDO::FETCH_ASSOC)['total'],
        'meetings' => $db->query("SELECT COUNT(*) as total FROM client_meetings")->fetch(PDO::FETCH_ASSOC)['total'],
        'completed_meetings' => $db->query("SELECT COUNT(*) as total FROM client_meetings WHERE status = 'completed'")->fetch(PDO::FETCH_ASSOC)['total']
    ];
} catch (PDOException $e) {
    $analytics['communications'] = ['total_contacts' => 0, 'primary_contacts' => 0, 'open_contacts' => 0, 'meetings' => 0, 'completed_meetings' => 0];
}

// Time-based analytics (last 30 days)
$thirty_days_ago = date('Y-m-d', strtotime('-30 days'));

try {
    // Use proper prepared statements for recent analytics
    $new_clients_stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE created_at >= ?");
    $new_clients_stmt->execute([$thirty_days_ago]);
    $new_clients = $new_clients_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $new_projects_stmt = $db->prepare("SELECT COUNT(*) as total FROM projects WHERE created_at >= ?");
    $new_projects_stmt->execute([$thirty_days_ago]);
    $new_projects = $new_projects_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $completed_projects_stmt = $db->prepare("SELECT COUNT(*) as total FROM projects WHERE status = 'completed' AND end_date >= ?");
    $completed_projects_stmt->execute([$thirty_days_ago]);
    $completed_projects = $completed_projects_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $analytics['recent'] = [
        'new_clients' => $new_clients,
        'new_projects' => $new_projects,
        'completed_projects' => $completed_projects
    ];
} catch (PDOException $e) {
    $analytics['recent'] = ['new_clients' => 0, 'new_projects' => 0, 'completed_projects' => 0];
}

// Custom reports - using proper prepared statement  
try {
    $reports_stmt = $db->prepare("SELECT cr.*, u.username as created_by_name 
                                  FROM custom_reports cr 
                                  LEFT JOIN users u ON cr.created_by = u.id 
                                  WHERE cr.created_by = ?
                                  ORDER BY cr.created_at DESC");
    $reports_stmt->execute([$_SESSION['user_id']]);
    $custom_reports = $reports_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $custom_reports = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insights Department - Business Management</title>
    <link rel="stylesheet" href="../css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chart-canvas {
            width: 100% !important;
            height: 300px !important;
        }
        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        .metric-value {
            font-weight: bold;
            color: #333;
        }
        .trend-up {
            color: #28a745;
        }
        .trend-down {
            color: #dc3545;
        }
        .dashboard-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .kpi-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }
        .kpi-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .kpi-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- KPI Overview -->
        <div class="dashboard-overview">
            <div class="kpi-card">
                <div class="kpi-number"><?php echo $analytics['clients']['total']; ?></div>
                <div class="kpi-label">Total Clients</div>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="kpi-number"><?php echo $analytics['projects']['total']; ?></div>
                <div class="kpi-label">Total Projects</div>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="kpi-number"><?php echo Utils::formatCurrency($analytics['finance']['total_revenue']); ?></div>
                <div class="kpi-label">Total Revenue</div>
            </div>
            <div class="kpi-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="kpi-number"><?php echo $analytics['hr']['employees']; ?></div>
                <div class="kpi-label">Total Employees</div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="showTab('overview')">Business Overview</button>
            <button class="tab-btn" onclick="showTab('clients')">Client Analytics</button>
            <button class="tab-btn" onclick="showTab('projects')">Project Analytics</button>
            <button class="tab-btn" onclick="showTab('finance')">Financial Analytics</button>
            <button class="tab-btn" onclick="showTab('reports')">Custom Reports</button>
        </div>
        
        <!-- Business Overview Tab -->
        <div id="overview" class="tab-content active">
            <div class="insights-grid">
                <!-- Department Distribution -->
                <div class="chart-container">
                    <h3>Team Distribution by Department</h3>
                    <canvas id="departmentChart" class="chart-canvas"></canvas>
                </div>
                
                <!-- Project Status -->
                <div class="chart-container">
                    <h3>Project Status Overview</h3>
                    <canvas id="projectStatusChart" class="chart-canvas"></canvas>
                </div>
                
                <!-- Monthly Trends -->
                <div class="chart-container">
                    <h3>Monthly Business Metrics</h3>
                    <canvas id="monthlyTrendsChart" class="chart-canvas"></canvas>
                </div>
                
                <!-- Key Metrics -->
                <div class="chart-container">
                    <h3>Key Performance Indicators</h3>
                    <div class="metric-row">
                        <span>Active Clients</span>
                        <span class="metric-value"><?php echo $analytics['clients']['active']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Projects in Progress</span>
                        <span class="metric-value"><?php echo $analytics['projects']['in_progress']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Average Project Progress</span>
                        <span class="metric-value"><?php echo round($analytics['projects']['avg_progress'], 1); ?>%</span>
                    </div>
                    <div class="metric-row">
                        <span>Open Client Contacts</span>
                        <span class="metric-value"><?php echo $analytics['communications']['open_contacts']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Pending Leave Requests</span>
                        <span class="metric-value"><?php echo $analytics['hr']['pending_leaves']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Marketing Campaigns</span>
                        <span class="metric-value"><?php echo $analytics['marketing']['campaigns']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Analytics Tab -->
        <div id="clients" class="tab-content">
            <div class="insights-grid">
                <!-- Client Status Distribution -->
                <div class="chart-container">
                    <h3>Client Status Distribution</h3>
                    <canvas id="clientStatusChart" class="chart-canvas"></canvas>
                </div>
                
                <!-- Communication Analytics -->
                <div class="chart-container">
                    <h3>Client Communications</h3>
                    <div class="metric-row">
                        <span>Total Contacts</span>
                        <span class="metric-value"><?php echo $analytics['communications']['total_contacts']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Open Contacts</span>
                        <span class="metric-value"><?php echo $analytics['communications']['open_contacts']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Total Meetings</span>
                        <span class="metric-value"><?php echo $analytics['communications']['meetings']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Completed Meetings</span>
                        <span class="metric-value"><?php echo $analytics['communications']['completed_meetings']; ?></span>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="chart-container">
                    <h3>Recent Activity (Last 30 Days)</h3>
                    <div class="metric-row">
                        <span>New Clients</span>
                        <span class="metric-value trend-up"><?php echo $analytics['recent']['new_clients']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>New Projects</span>
                        <span class="metric-value trend-up"><?php echo $analytics['recent']['new_projects']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Completed Projects</span>
                        <span class="metric-value trend-up"><?php echo $analytics['recent']['completed_projects']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Analytics Tab -->
        <div id="projects" class="tab-content">
            <div class="insights-grid">
                <!-- Project Categories -->
                <div class="chart-container">
                    <h3>Projects by Category</h3>
                    <canvas id="projectCategoryChart" class="chart-canvas"></canvas>
                </div>
                
                <!-- Project Priority -->
                <div class="chart-container">
                    <h3>Projects by Priority</h3>
                    <canvas id="projectPriorityChart" class="chart-canvas"></canvas>
                </div>
                
                <!-- Project Metrics -->
                <div class="chart-container">
                    <h3>Project Performance</h3>
                    <div class="metric-row">
                        <span>Total Projects</span>
                        <span class="metric-value"><?php echo $analytics['projects']['total']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>In Progress</span>
                        <span class="metric-value"><?php echo $analytics['projects']['in_progress']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Completed</span>
                        <span class="metric-value"><?php echo $analytics['projects']['completed']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Average Progress</span>
                        <span class="metric-value"><?php echo round($analytics['projects']['avg_progress'], 1); ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Analytics Tab -->
        <div id="finance" class="tab-content">
            <div class="insights-grid">
                <!-- Revenue Overview -->
                <div class="chart-container">
                    <h3>Revenue Overview</h3>
                    <canvas id="revenueChart" class="chart-canvas"></canvas>
                </div>
                
                <!-- Financial Metrics -->
                <div class="chart-container">
                    <h3>Financial Performance</h3>
                    <div class="metric-row">
                        <span>Total Quotations</span>
                        <span class="metric-value"><?php echo $analytics['finance']['quotations']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Total Invoices</span>
                        <span class="metric-value"><?php echo $analytics['finance']['invoices']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Total Revenue</span>
                        <span class="metric-value trend-up"><?php echo Utils::formatCurrency($analytics['finance']['total_revenue']); ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Pending Revenue</span>
                        <span class="metric-value"><?php echo Utils::formatCurrency($analytics['finance']['pending_revenue']); ?></span>
                    </div>
                </div>
                
                <!-- Marketing Analytics -->
                <div class="chart-container">
                    <h3>Marketing Performance</h3>
                    <div class="metric-row">
                        <span>Marketing Campaigns</span>
                        <span class="metric-value"><?php echo $analytics['marketing']['campaigns']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Social Media Posts</span>
                        <span class="metric-value"><?php echo $analytics['marketing']['social_posts']; ?></span>
                    </div>
                    <div class="metric-row">
                        <span>Email Campaigns</span>
                        <span class="metric-value"><?php echo $analytics['marketing']['email_campaigns']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Reports Tab -->
        <div id="reports" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h2>Custom Reports</h2>
                </div>
                <div class="section-content">
                    <?php if (Security::canWriteInDepartment($_SESSION['role'], $_SESSION['department'], 'Insights')): ?>
                        <form method="POST" class="form-grid">
                            <?php echo Security::getCSRFTokenField(); ?>
                            <div class="form-group">
                                <label>Report Name:</label>
                                <input type="text" name="report_name" required>
                            </div>
                            <div class="form-group">
                                <label>Report Type:</label>
                                <select name="report_type" required>
                                    <option value="">Select Type</option>
                                    <option value="client_analysis">Client Analysis</option>
                                    <option value="project_summary">Project Summary</option>
                                    <option value="financial_report">Financial Report</option>
                                    <option value="team_performance">Team Performance</option>
                                    <option value="custom_query">Custom Query</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Chart Type:</label>
                                <select name="chart_type" required>
                                    <option value="">Select Chart</option>
                                    <option value="bar">Bar Chart</option>
                                    <option value="line">Line Chart</option>
                                    <option value="pie">Pie Chart</option>
                                    <option value="table">Data Table</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_public" value="1">
                                    Make report public (visible to all users)
                                </label>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="create_report" class="btn">Create Report</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <h3>Available Reports</h3>
                    <?php foreach ($custom_reports as $report): ?>
                        <div class="card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <h4><?php echo Security::escapeHTML($report['report_name']); ?></h4>
                                <div>
                                    <span class="status-badge status-active"><?php echo ucfirst(str_replace('_', ' ', $report['report_type'])); ?></span>
                                    <?php if ($report['is_public']): ?>
                                        <span class="priority-badge priority-medium">Public</span>
                                    <?php else: ?>
                                        <span class="priority-badge priority-low">Private</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-grid">
                                <div><strong>Chart Type:</strong> <?php echo ucfirst($report['chart_type']); ?></div>
                                <div><strong>Created by:</strong> <?php echo Security::escapeHTML($report['created_by_name']); ?></div>
                                <div><strong>Created:</strong> <?php echo Utils::formatDate($report['created_at']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/notification.js"></script>  
    <script>
        // Chart configuration
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        // Department Distribution Chart
        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        new Chart(departmentCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($analytics['departments'], 'department')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($analytics['departments'], 'count')); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Project Status Chart
        const projectStatusCtx = document.getElementById('projectStatusChart').getContext('2d');
        new Chart(projectStatusCtx, {
            type: 'bar',
            data: {
                labels: ['Total', 'In Progress', 'Completed'],
                datasets: [{
                    label: 'Projects',
                    data: [<?php echo $analytics['projects']['total']; ?>, <?php echo $analytics['projects']['in_progress']; ?>, <?php echo $analytics['projects']['completed']; ?>],
                    backgroundColor: ['#36A2EB', '#FF9F40', '#4BC0C0']
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
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
                datasets: [{
                    label: 'New Clients',
                    data: [12, 19, 15, 25, 22, 30],
                    borderColor: '#FF6384',
                    tension: 0.1
                }, {
                    label: 'Completed Projects',
                    data: [8, 12, 18, 15, 20, 25],
                    borderColor: '#36A2EB',
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Client Status Chart
        const clientStatusCtx = document.getElementById('clientStatusChart').getContext('2d');
        new Chart(clientStatusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($analytics['clients']['by_status'], 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($analytics['clients']['by_status'], 'count')); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
                }]
            }
        });

        // Project Category Chart
        const projectCategoryCtx = document.getElementById('projectCategoryChart').getContext('2d');
        new Chart(projectCategoryCtx, {
            type: 'horizontalBar',
            data: {
                labels: <?php echo json_encode(array_column($analytics['projects']['by_category'], 'category')); ?>,
                datasets: [{
                    label: 'Projects',
                    data: <?php echo json_encode(array_column($analytics['projects']['by_category'], 'count')); ?>,
                    backgroundColor: '#36A2EB'
                }]
            },
            options: {
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Project Priority Chart
        const projectPriorityCtx = document.getElementById('projectPriorityChart').getContext('2d');
        new Chart(projectPriorityCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($analytics['projects']['by_priority'], 'priority')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($analytics['projects']['by_priority'], 'count')); ?>,
                    backgroundColor: ['#4BC0C0', '#FFCE56', '#FF6384', '#FF9F40']
                }]
            }
        });

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: ['Total Revenue', 'Pending Revenue'],
                datasets: [{
                    label: 'Revenue ($)',
                    data: [<?php echo $analytics['finance']['total_revenue']; ?>, <?php echo $analytics['finance']['pending_revenue']; ?>],
                    backgroundColor: ['#4BC0C0', '#FF9F40']
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>