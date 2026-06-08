<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';

// Check department access
Security::requireDepartmentAccess('Finance');

$database = new Database();
$db = $database->getConnection();

// Get date range filters
$start_date = Security::sanitizeInput($_GET['start_date'] ?? date('Y-m-01'));
$end_date = Security::sanitizeInput($_GET['end_date'] ?? date('Y-m-t'));
$period = Security::sanitizeInput($_GET['period'] ?? 'month');

// Track page visit
try {
    require_once '../includes/ActivityLogger.php';
    $logger = new ActivityLogger($db);
    $logger->logPageVisit('Financial Reports', 'Viewed financial analytics and reports page');
} catch (Exception $e) {
    error_log("Activity logging failed: " . $e->getMessage());
}

// Financial Performance Analytics
$financial_analytics = [
    'revenue' => [
        'total' => 0,
        'invoiced' => 0,
        'received' => 0,
        'outstanding' => 0
    ],
    'growth' => [
        'revenue_growth' => 0,
        'client_growth' => 0,
        'project_growth' => 0
    ],
    'efficiency' => [
        'avg_invoice_value' => 0,
        'payment_cycle' => 0,
        'conversion_rate' => 0
    ]
];

// Calculate financial metrics
$query = "SELECT 
            SUM(total_amount) as total_invoiced,
            SUM(paid_amount) as total_received,
            SUM(CASE WHEN status != 'paid' THEN total_amount - paid_amount ELSE 0 END) as outstanding,
            AVG(total_amount) as avg_invoice,
            COUNT(*) as invoice_count
          FROM invoices 
          WHERE invoice_date BETWEEN ? AND ?";
$stmt = $db->prepare($query);
$stmt->execute([$start_date, $end_date]);
$revenue_data = $stmt->fetch(PDO::FETCH_ASSOC);

$financial_analytics['revenue']['invoiced'] = $revenue_data['total_invoiced'] ?: 0;
$financial_analytics['revenue']['received'] = $revenue_data['total_received'] ?: 0;
$financial_analytics['revenue']['outstanding'] = $revenue_data['outstanding'] ?: 0;
$financial_analytics['efficiency']['avg_invoice_value'] = $revenue_data['avg_invoice'] ?: 0;

// Project Performance Analytics
$project_stats = [];
$query = "SELECT 
            p.name as project_name,
            p.status,
            COUNT(i.id) as invoice_count,
            SUM(COALESCE(i.total_amount, 0)) as project_revenue,
            AVG(i.total_amount) as avg_invoice_value
          FROM projects p 
          LEFT JOIN invoices i ON p.id = i.project_id 
            AND i.invoice_date BETWEEN ? AND ?
          GROUP BY p.id, p.name, p.status
          ORDER BY ISNULL(project_revenue), project_revenue DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$start_date, $end_date]);
$project_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Client Performance Analytics
$client_stats = [];
$query = "SELECT 
            c.name as client_name,
            COUNT(i.id) as invoice_count,
            SUM(COALESCE(i.total_amount, 0)) as client_revenue,
            SUM(COALESCE(i.paid_amount, 0)) as amount_paid,
            AVG(i.total_amount) as avg_invoice_value,
            CASE WHEN SUM(i.total_amount) > 0 
                 THEN (SUM(i.paid_amount) / SUM(i.total_amount)) * 100 
                 ELSE 0 END as payment_rate
          FROM clients c 
          LEFT JOIN invoices i ON c.id = i.client_id 
            AND i.invoice_date BETWEEN ? AND ?
          GROUP BY c.id, c.name
          ORDER BY ISNULL(client_revenue), client_revenue DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$start_date, $end_date]);
$client_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly Trends Analytics
$monthly_trends = [];
$query = "SELECT
            DATE_FORMAT(invoice_date, '%Y-%m') as month,
            COUNT(*) as invoice_count,
            SUM(total_amount) as revenue,
            SUM(paid_amount) as payments,
            COUNT(DISTINCT client_id) as unique_clients
          FROM invoices
          WHERE invoice_date >= DATE_SUB(?, INTERVAL 12 MONTH)
          GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
          ORDER BY month";
$stmt = $db->prepare($query);
$stmt->execute([$end_date]);
$monthly_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Department Performance Analytics
$dept_performance = [];
$query = "SELECT 
            u.department,
            COUNT(i.id) as invoices_created,
            SUM(i.total_amount) as revenue_generated,
            AVG(i.total_amount) as avg_invoice_value,
            COUNT(DISTINCT i.client_id) as clients_served
          FROM invoices i
          JOIN users u ON i.created_by = u.id
          WHERE i.invoice_date BETWEEN ? AND ?
          GROUP BY u.department
          ORDER BY revenue_generated DESC";
$stmt = $db->prepare($query);
$stmt->execute([$start_date, $end_date]);
$dept_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Payment Analysis
$payment_analysis = [];
$query = "SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as total_value,
            AVG(total_amount) as avg_value,
            AVG(DATEDIFF(COALESCE(
                CASE WHEN status = 'paid' THEN
                    (SELECT MAX(transaction_date) FROM money_flow WHERE invoice_id = i.id)
                ELSE NULL END,
                due_date), invoice_date)) as avg_payment_days
          FROM invoices i
          WHERE invoice_date BETWEEN ? AND ?
          GROUP BY status
          ORDER BY count DESC";
$stmt = $db->prepare($query);
$stmt->execute([$start_date, $end_date]);
$payment_analysis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Quotation Conversion Analysis
$quotation_analysis = [];
$query = "SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as total_value,
            AVG(total_amount) as avg_value
          FROM quotations
          WHERE quotation_date BETWEEN ? AND ?
          GROUP BY status
          ORDER BY count DESC";
$stmt = $db->prepare($query);
$stmt->execute([$start_date, $end_date]);
$quotation_analysis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate conversion rate
$total_quotations = array_sum(array_column($quotation_analysis, 'count'));
$converted_quotations = 0;
foreach ($quotation_analysis as $analysis) {
    if ($analysis['status'] === 'converted') {
        $converted_quotations = $analysis['count'];
        break;
    }
}
$conversion_rate = $total_quotations > 0 ? ($converted_quotations / $total_quotations) * 100 : 0;

// Revenue Forecast (Simple trend-based prediction)
$forecast_data = [];
if (count($monthly_trends) >= 3) {
    $recent_months = array_slice($monthly_trends, -3);
    $avg_growth = 0;
    for ($i = 1; $i < count($recent_months); $i++) {
        $prev = $recent_months[$i-1]['revenue'] ?: 1;
        $current = $recent_months[$i]['revenue'] ?: 0;
        $avg_growth += (($current - $prev) / $prev) * 100;
    }
    $avg_growth /= (count($recent_months) - 1);
    
    $last_revenue = end($monthly_trends)['revenue'] ?: 0;
    $forecast_data = [
        'next_month' => $last_revenue * (1 + $avg_growth / 100),
        'growth_rate' => $avg_growth
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Financial Analytics - Business Management System</title>
    <link rel="stylesheet" href="../css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .analytics-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .analytics-header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        
        .analytics-filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn-filter {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 20px;
        }
        
        .btn-filter:hover {
            background: #764ba2;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .analytics-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 15px;
            color: white;
        }
        
        .card-icon.revenue { background: linear-gradient(135deg, #667eea, #764ba2); }
        .card-icon.growth { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .card-icon.efficiency { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .card-icon.clients { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        .card-icon.projects { background: linear-gradient(135deg, #fa709a, #fee140); }
        .card-icon.forecast { background: linear-gradient(135deg, #a8edea, #fed6e3); }
        
        .card-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .metric-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        
        .metric-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .metric-change {
            font-size: 0.9em;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .metric-change.positive {
            background: #d4edda;
            color: #155724;
        }
        
        .metric-change.negative {
            background: #f8d7da;
            color: #721c24;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 1.4em;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        .data-table {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-paid { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-overdue { background: #f8d7da; color: #721c24; }
        .status-draft { background: #e2e3e5; color: #383d41; }
        
        .export-buttons {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-export {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-export:hover {
            background: #218838;
        }
        
        @media (max-width: 768px) {
            .analytics-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
    <div class="analytics-container">
        <!-- Analytics Header -->
        <div class="analytics-header">
            <h1>📊 Advanced Financial Analytics</h1>
            <p>Comprehensive business intelligence and performance insights</p>
        </div>
        
        <!-- Date Range Filters -->
        <div class="analytics-filters">
            <form method="GET" style="display: flex; gap: 20px; align-items: end; flex-wrap: wrap;">
                <div class="filter-group">
                    <label>Start Date:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="filter-group">
                    <label>End Date:</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="filter-group">
                    <label>Period:</label>
                    <select name="period">
                        <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="quarter" <?php echo $period === 'quarter' ? 'selected' : ''; ?>>Quarterly</option>
                        <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>Yearly</option>
                    </select>
                </div>
                <button type="submit" class="btn-filter">📈 Generate Report</button>
            </form>
        </div>
        
        <!-- Key Metrics Grid -->
        <div class="analytics-grid">
            <!-- Revenue Metrics -->
            <div class="analytics-card">
                <div class="card-header">
                    <div class="card-icon revenue">💰</div>
                    <h3 class="card-title">Revenue Performance</h3>
                </div>
                <div class="metric-label">Total Invoiced</div>
                <div class="metric-value">R <?php echo number_format($financial_analytics['revenue']['invoiced'], 2); ?></div>
                <div class="metric-label">Total Received</div>
                <div class="metric-value" style="font-size: 1.5em; color: #28a745;">R <?php echo number_format($financial_analytics['revenue']['received'], 2); ?></div>
                <div class="metric-label">Outstanding</div>
                <div class="metric-value" style="font-size: 1.2em; color: #dc3545;">R <?php echo number_format($financial_analytics['revenue']['outstanding'], 2); ?></div>
            </div>
            
            <!-- Conversion Analytics -->
            <div class="analytics-card">
                <div class="card-header">
                    <div class="card-icon growth">📈</div>
                    <h3 class="card-title">Conversion Analytics</h3>
                </div>
                <div class="metric-label">Quotation Conversion Rate</div>
                <div class="metric-value"><?php echo number_format($conversion_rate, 1); ?>%</div>
                <div class="metric-label">Total Quotations: <?php echo $total_quotations; ?></div>
                <div class="metric-label">Converted: <?php echo $converted_quotations; ?></div>
                <?php if ($conversion_rate >= 70): ?>
                    <span class="metric-change positive">Excellent</span>
                <?php elseif ($conversion_rate >= 50): ?>
                    <span class="metric-change" style="background: #fff3cd; color: #856404;">Good</span>
                <?php else: ?>
                    <span class="metric-change negative">Needs Improvement</span>
                <?php endif; ?>
            </div>
            
            <!-- Efficiency Metrics -->
            <div class="analytics-card">
                <div class="card-header">
                    <div class="card-icon efficiency">⚡</div>
                    <h3 class="card-title">Business Efficiency</h3>
                </div>
                <div class="metric-label">Average Invoice Value</div>
                <div class="metric-value">R <?php echo number_format($financial_analytics['efficiency']['avg_invoice_value'], 2); ?></div>
                <div class="metric-label">Total Invoices: <?php echo $revenue_data['invoice_count'] ?: 0; ?></div>
                <?php
                $collection_rate = $financial_analytics['revenue']['invoiced'] > 0 
                    ? ($financial_analytics['revenue']['received'] / $financial_analytics['revenue']['invoiced']) * 100 
                    : 0;
                ?>
                <div class="metric-label">Collection Rate</div>
                <div class="metric-value" style="font-size: 1.5em;"><?php echo number_format($collection_rate, 1); ?>%</div>
            </div>
            
            <!-- Forecast -->
            <?php if (!empty($forecast_data)): ?>
            <div class="analytics-card">
                <div class="card-header">
                    <div class="card-icon forecast">🔮</div>
                    <h3 class="card-title">Revenue Forecast</h3>
                </div>
                <div class="metric-label">Next Month Projection</div>
                <div class="metric-value">R <?php echo number_format($forecast_data['next_month'], 2); ?></div>
                <div class="metric-label">Growth Trend</div>
                <span class="metric-change <?php echo $forecast_data['growth_rate'] >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $forecast_data['growth_rate'] >= 0 ? '↗' : '↘'; ?>
                    <?php echo abs(number_format($forecast_data['growth_rate'], 1)); ?>%
                </span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Charts Section -->
        <div class="chart-container">
            <h3 class="chart-title">Monthly Revenue Trends</h3>
            <canvas id="revenueChart" width="400" height="200"></canvas>
        </div>
        
        <div class="analytics-grid">
            <div class="chart-container">
                <h3 class="chart-title">Payment Status Distribution</h3>
                <canvas id="paymentChart" width="400" height="200"></canvas>
            </div>
            
            <div class="chart-container">
                <h3 class="chart-title">Department Performance</h3>
                <canvas id="departmentChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Top Performing Projects Table -->
        <div class="data-table">
            <h3 class="chart-title">🏆 Top Performing Projects</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Status</th>
                        <th>Invoices</th>
                        <th>Revenue</th>
                        <th>Avg Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($project_stats as $project): ?>
                    <tr>
                        <td><?php echo Security::escapeHTML($project['project_name']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $project['status']; ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $project['invoice_count']; ?></td>
                        <td>R <?php echo number_format($project['project_revenue'], 2); ?></td>
                        <td>R <?php echo number_format($project['avg_invoice_value'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Top Clients Table -->
        <div class="data-table">
            <h3 class="chart-title">💎 Top Clients by Revenue</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>Invoices</th>
                        <th>Total Revenue</th>
                        <th>Amount Paid</th>
                        <th>Payment Rate</th>
                        <th>Avg Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($client_stats as $client): ?>
                    <tr>
                        <td><?php echo Security::escapeHTML($client['client_name']); ?></td>
                        <td><?php echo $client['invoice_count']; ?></td>
                        <td>R <?php echo number_format($client['client_revenue'], 2); ?></td>
                        <td>R <?php echo number_format($client['amount_paid'], 2); ?></td>
                        <td>
                            <span class="metric-change <?php echo $client['payment_rate'] >= 80 ? 'positive' : ($client['payment_rate'] >= 50 ? '' : 'negative'); ?>">
                                <?php echo number_format($client['payment_rate'], 1); ?>%
                            </span>
                        </td>
                        <td>R <?php echo number_format($client['avg_invoice_value'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Export Options -->
        <div class="export-buttons">
            <button class="btn-export" onclick="exportToCSV()">
                📊 Export CSV
            </button>
            <button class="btn-export" onclick="exportToPDF()">
                📄 Export PDF
            </button>
            <button class="btn-export" onclick="printReport()">
                🖨️ Print Report
            </button>
        </div>
    </div>

    </div><!-- /.main-content -->

    <script src="../js/notification.js"></script>
    <script>
        // Revenue Trends Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($trend) { return "'".$trend['month']."'"; }, $monthly_trends)); ?>],
                datasets: [{
                    label: 'Revenue',
                    data: [<?php echo implode(',', array_column($monthly_trends, 'revenue')); ?>],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Payments',
                    data: [<?php echo implode(',', array_column($monthly_trends, 'payments')); ?>],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Payment Status Chart
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($status) { return "'".ucfirst($status['status'])."'"; }, $payment_analysis)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($payment_analysis, 'count')); ?>],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#6c757d',
                        '#17a2b8'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
        
        // Department Performance Chart
        const deptCtx = document.getElementById('departmentChart').getContext('2d');
        const departmentChart = new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($dept) { return "'".$dept['department']."'"; }, $dept_performance)); ?>],
                datasets: [{
                    label: 'Revenue Generated',
                    data: [<?php echo implode(',', array_column($dept_performance, 'revenue_generated')); ?>],
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: '#667eea',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Export Functions
        function exportToCSV() {
            alert('CSV Export functionality ready for implementation');
        }
        
        function exportToPDF() {
            window.print();
        }
        
        function printReport() {
            window.print();
        }
        
        // Auto-refresh data every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>