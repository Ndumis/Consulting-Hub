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

    $query = "SELECT COUNT(*) as total_quotations FROM quotations";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_quotations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_quotations'] ?? 0;

    // HR pending leave
    $query = "SELECT COUNT(*) as pending_leave_requests FROM hr_leave_requests WHERE status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending_leave_requests'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_leave_requests'] ?? 0;

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

// Monthly trends — real DB data, last 6 months with data (all-time, newest first capped at 6)
$mt_months = []; $mt_proj = []; $mt_camp = []; $mt_clients = []; $mt_leads = [];
try {
    // Build a month spine: all distinct months that have any activity
    $spine_rows = $db->query("
        SELECT ym FROM (
            SELECT DATE_FORMAT(updated_at,'%Y-%m') AS ym FROM projects WHERE status='completed'
            UNION SELECT DATE_FORMAT(created_at,'%Y-%m') FROM marketing_campaigns
            UNION SELECT DATE_FORMAT(created_at,'%Y-%m') FROM clients
            UNION SELECT DATE_FORMAT(created_at,'%Y-%m') FROM bd_leads
        ) t GROUP BY ym ORDER BY ym DESC LIMIT 6")->fetchAll(PDO::FETCH_COLUMN);
    $spine = array_reverse($spine_rows);

    foreach ($spine as $ym) {
        $mt_months[] = date('M Y', strtotime($ym.'-01'));
    }
    foreach ($spine as $ym) {
        $s = $db->prepare("SELECT COUNT(*) FROM projects WHERE status='completed' AND DATE_FORMAT(updated_at,'%Y-%m')=?");
        $s->execute([$ym]); $mt_proj[] = (int)$s->fetchColumn();
        $s = $db->prepare("SELECT COUNT(*) FROM marketing_campaigns WHERE DATE_FORMAT(created_at,'%Y-%m')=?");
        $s->execute([$ym]); $mt_camp[] = (int)$s->fetchColumn();
        $s = $db->prepare("SELECT COUNT(*) FROM clients WHERE DATE_FORMAT(created_at,'%Y-%m')=?");
        $s->execute([$ym]); $mt_clients[] = (int)$s->fetchColumn();
        $s = $db->prepare("SELECT COUNT(*) FROM bd_leads WHERE DATE_FORMAT(created_at,'%Y-%m')=?");
        $s->execute([$ym]); $mt_leads[] = (int)$s->fetchColumn();
    }
} catch (Exception $e) { /* silent */ }
$monthly_trends = [
    'labels'             => $mt_months,
    'projects_completed' => $mt_proj,
    'campaigns_launched' => $mt_camp,
    'clients_acquired'   => $mt_clients,
    'leads_generated'    => $mt_leads,
];

// ── DEPT CONFIG ─────────────────────────────────────────────
$dept_config = [
    'IT'                   => ['slug' => 'it',        'icon' => '💻', 'color' => '#0ea5e9'],
    'Marketing'            => ['slug' => 'marketing', 'icon' => '📈', 'color' => '#f97316'],
    'Business Development' => ['slug' => 'bd',        'icon' => '🎯', 'color' => '#8b5cf6'],
    'Finance'              => ['slug' => 'finance',   'icon' => '💰', 'color' => '#22c55e'],
    'HR'                   => ['slug' => 'hr',        'icon' => '👥', 'color' => '#ec4899'],
    'Clients'              => ['slug' => 'clients',   'icon' => '🏢', 'color' => '#f59e0b'],
];
$dept_slug  = $dept_config[$department]['slug']  ?? '';
$dept_icon  = $dept_config[$department]['icon']  ?? '🏢';
$dept_color = $dept_config[$department]['color'] ?? '#6b7280';

// ── PERSONAL "MY WORK" DATA (employee + manager) ──────────────
$my_projects   = [];
$my_tasks      = [];
$my_docs       = [];   // invoices/quotations/campaigns/posts/leaves depending on dept
$my_team       = [];
$dept_stats    = ['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0,
                  'a_label' => '', 'b_label' => '', 'c_label' => '', 'd_label' => ''];

try {
    // Team members (manager only)
    if ($role === 'manager') {
        $s = $db->prepare("SELECT id, username, email, role FROM users WHERE department = ? AND id != ? ORDER BY username");
        $s->execute([$department, $user_id]);
        $my_team = $s->fetchAll(PDO::FETCH_ASSOC);
    }

    switch ($department) {
        case 'IT':
            // My assigned projects
            $s = $db->prepare("SELECT p.id, p.name, p.status, p.progress, p.end_date, pa.role as my_role
                FROM project_assignments pa JOIN projects p ON pa.project_id = p.id
                WHERE pa.user_id = ? ORDER BY p.updated_at DESC LIMIT 6");
            $s->execute([$user_id]);
            $my_projects = $s->fetchAll(PDO::FETCH_ASSOC);
            // Dept stats
            $dept_stats = [
                'a' => $stats['total_projects'],       'a_label' => 'Total Projects',
                'b' => $stats['in_progress_projects'], 'b_label' => 'In Progress',
                'c' => $stats['completed_projects'],   'c_label' => 'Completed',
                'd' => $stats['project_blockers'] ?? 0, 'd_label' => 'Blockers',
            ];
            break;

        case 'Marketing':
            $s = $db->prepare("SELECT id, campaign_name, status, start_date, end_date FROM marketing_campaigns WHERE created_by = ? ORDER BY created_at DESC LIMIT 4");
            $s->execute([$user_id]);
            $my_projects = $s->fetchAll(PDO::FETCH_ASSOC);
            $s = $db->prepare("SELECT id, title, status, published_at FROM blog_posts WHERE author_id = ? ORDER BY created_at DESC LIMIT 4");
            $s->execute([$user_id]);
            $my_docs = $s->fetchAll(PDO::FETCH_ASSOC);
            $dept_stats = [
                'a' => $stats['active_campaigns'],  'a_label' => 'Active Campaigns',
                'b' => $stats['total_campaigns'],   'b_label' => 'Total Campaigns',
                'c' => 0,                           'c_label' => 'Blog Posts',
                'd' => 0,                           'd_label' => 'Social Posts',
            ];
            try {
                $s = $db->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'");
                $dept_stats['c'] = (int)$s->fetchColumn();
                $s = $db->query("SELECT COUNT(*) FROM social_posts WHERE MONTH(created_at)=MONTH(CURDATE())");
                $dept_stats['d'] = (int)$s->fetchColumn();
            } catch(Exception $e) {}
            break;

        case 'Business Development':
            $s = $db->prepare("SELECT id, company_name, contact_person, status, next_follow_up FROM bd_leads WHERE created_by = ? ORDER BY updated_at DESC LIMIT 5");
            $s->execute([$user_id]);
            $my_projects = $s->fetchAll(PDO::FETCH_ASSOC);
            $s = $db->prepare("SELECT bt.id, bt.task_description, bt.due_date, bt.status, bl.company_name
                FROM bd_tasks bt LEFT JOIN bd_leads bl ON bt.related_lead_id = bl.id
                WHERE bt.created_by = ? AND bt.status = 'pending' ORDER BY bt.due_date ASC LIMIT 5");
            $s->execute([$user_id]);
            $my_tasks = $s->fetchAll(PDO::FETCH_ASSOC);
            $dept_stats = [
                'a' => $stats['total_leads'],       'a_label' => 'Total Leads',
                'b' => $stats['new_leads'],         'b_label' => 'New Leads',
                'c' => $stats['meetings_booked'],   'c_label' => 'Meetings Booked',
                'd' => $stats['clients_converted'], 'd_label' => 'Converted',
            ];
            break;

        case 'Finance':
            $s = $db->prepare("SELECT q.id, q.quotation_number, c.name as client_name, q.total_amount, q.status, q.created_at
                FROM quotations q LEFT JOIN clients c ON q.client_id = c.id
                WHERE q.created_by = ? ORDER BY q.created_at DESC LIMIT 4");
            $s->execute([$user_id]);
            $my_projects = $s->fetchAll(PDO::FETCH_ASSOC);
            $s = $db->prepare("SELECT i.id, i.invoice_number, c.name as client_name, i.total_amount, i.status, i.due_date
                FROM invoices i LEFT JOIN clients c ON i.client_id = c.id
                WHERE i.created_by = ? ORDER BY i.created_at DESC LIMIT 4");
            $s->execute([$user_id]);
            $my_docs = $s->fetchAll(PDO::FETCH_ASSOC);
            $pending_inv = 0; $accepted_q = 0;
            try {
                $s2 = $db->query("SELECT COUNT(*) FROM invoices WHERE status='pending'");
                $pending_inv = (int)$s2->fetchColumn();
                $s2 = $db->query("SELECT COUNT(*) FROM quotations WHERE status='accepted'");
                $accepted_q = (int)$s2->fetchColumn();
            } catch(Exception $e) {}
            $dept_stats = [
                'a' => $stats['total_invoices'],   'a_label' => 'Total Invoices',
                'b' => $pending_inv,               'b_label' => 'Pending Invoices',
                'c' => $stats['total_quotations'] ?? 0, 'c_label' => 'Quotations',
                'd' => $accepted_q,                'd_label' => 'Accepted Quotes',
            ];
            break;

        case 'HR':
            $s = $db->prepare("SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.status, lr.days_requested
                FROM hr_leave_requests lr JOIN hr_employees e ON lr.employee_id = e.id
                WHERE e.user_id = ? ORDER BY lr.created_at DESC LIMIT 5");
            $s->execute([$user_id]);
            $my_projects = $s->fetchAll(PDO::FETCH_ASSOC);
            $pending_leave = 0; $new_hires = 0;
            try {
                $s2 = $db->query("SELECT COUNT(*) FROM hr_leave_requests WHERE status='pending'");
                $pending_leave = (int)$s2->fetchColumn();
                $s2 = $db->query("SELECT COUNT(*) FROM hr_employees WHERE hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
                $new_hires = (int)$s2->fetchColumn();
            } catch(Exception $e) {}
            $dept_stats = [
                'a' => $stats['total_employees'],  'a_label' => 'Active Employees',
                'b' => $pending_leave,             'b_label' => 'Leave Pending',
                'c' => $new_hires,                 'c_label' => 'New Hires (30d)',
                'd' => 0,                          'd_label' => 'Departments',
            ];
            try {
                $s2 = $db->query("SELECT COUNT(DISTINCT department) FROM users WHERE department IS NOT NULL");
                $dept_stats['d'] = (int)$s2->fetchColumn();
            } catch(Exception $e) {}
            break;

        case 'Clients':
            $s = $db->prepare("SELECT id, name, company, status, email FROM clients ORDER BY created_at DESC LIMIT 6");
            $s->execute();
            $my_projects = $s->fetchAll(PDO::FETCH_ASSOC);
            $dept_stats = [
                'a' => $stats['active_clients'], 'a_label' => 'Active Clients',
                'b' => $stats['total_clients'],  'b_label' => 'Total Clients',
                'c' => 0,                        'c_label' => 'New This Month',
                'd' => 0,                        'd_label' => 'Interactions',
            ];
            try {
                $s2 = $db->query("SELECT COUNT(*) FROM clients WHERE MONTH(created_at)=MONTH(CURDATE())");
                $dept_stats['c'] = (int)$s2->fetchColumn();
                $s2 = $db->query("SELECT COUNT(*) FROM client_activities WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $dept_stats['d'] = (int)$s2->fetchColumn();
            } catch(Exception $e) {}
            break;
    }
} catch (Exception $e) {
    error_log("Personal stats error: " . $e->getMessage());
}

// ── QUICK ACTIONS per role/dept ───────────────────────────────
$quick_actions = [];
if ($role === 'admin') {
    $quick_actions = [
        ['href' => 'departments/projects.php?view=create',      'icon' => '⚙️', 'label' => 'New Project'],
        ['href' => 'departments/marketing.php?action=new_campaign', 'icon' => '📢', 'label' => 'New Campaign'],
        ['href' => 'departments/bd.php?action=new_lead',        'icon' => '🎯', 'label' => 'New Lead'],
        ['href' => 'departments/finance.php?action=new_invoice','icon' => '💰', 'label' => 'Create Invoice'],
        ['href' => 'departments/clients.php?action=new_client', 'icon' => '👤', 'label' => 'Add Client'],
        ['href' => 'departments/hr.php?action=new_employee',    'icon' => '👥', 'label' => 'New Employee'],
    ];
} elseif ($role === 'manager') {
    $map = [
        'IT'                   => [
            ['href' => 'departments/projects.php?view=create',  'icon' => '⚙️', 'label' => 'New Project'],
            ['href' => 'departments/projects.php',              'icon' => '📋', 'label' => 'All Projects'],
            ['href' => 'departments/it.php',                    'icon' => '💻', 'label' => 'IT Assets'],
            ['href' => 'departments/clients.php',               'icon' => '🏢', 'label' => 'Clients'],
        ],
        'Marketing' => [
            ['href' => 'departments/marketing.php?action=new_campaign', 'icon' => '📢', 'label' => 'New Campaign'],
            ['href' => 'departments/marketing.php?view=blog-posts',     'icon' => '✍️', 'label' => 'New Blog Post'],
            ['href' => 'departments/marketing.php',                     'icon' => '📈', 'label' => 'Marketing Hub'],
            ['href' => 'departments/insights.php',                      'icon' => '📊', 'label' => 'Analytics'],
        ],
        'Business Development' => [
            ['href' => 'departments/bd.php?action=new_lead',  'icon' => '🎯', 'label' => 'New Lead'],
            ['href' => 'departments/bd.php',                  'icon' => '📋', 'label' => 'Pipeline'],
            ['href' => 'departments/clients.php',             'icon' => '🏢', 'label' => 'Clients'],
            ['href' => 'departments/insights.php',            'icon' => '📊', 'label' => 'Reports'],
        ],
        'Finance' => [
            ['href' => 'departments/finance.php?action=new_invoice',    'icon' => '💰', 'label' => 'New Invoice'],
            ['href' => 'departments/finance.php?action=new_quotation',  'icon' => '📄', 'label' => 'New Quote'],
            ['href' => 'departments/finance.php',                       'icon' => '💳', 'label' => 'Finance Hub'],
            ['href' => 'departments/reports.php',                       'icon' => '📑', 'label' => 'Reports'],
        ],
        'HR' => [
            ['href' => 'departments/hr.php?action=new_employee', 'icon' => '👤', 'label' => 'Add Employee'],
            ['href' => 'departments/hr.php',                     'icon' => '👥', 'label' => 'HR Hub'],
            ['href' => 'departments/insights.php',               'icon' => '📊', 'label' => 'Analytics'],
            ['href' => 'departments/reports.php',                'icon' => '📑', 'label' => 'Reports'],
        ],
        'Clients' => [
            ['href' => 'departments/clients.php?action=new_client', 'icon' => '👤', 'label' => 'Add Client'],
            ['href' => 'departments/clients.php',                   'icon' => '🏢', 'label' => 'Clients'],
            ['href' => 'departments/bd.php',                        'icon' => '🎯', 'label' => 'BD Pipeline'],
            ['href' => 'departments/insights.php',                  'icon' => '📊', 'label' => 'Reports'],
        ],
    ];
    $quick_actions = $map[$department] ?? $map['IT'];
} else { // employee
    $map = [
        'IT'                   => [
            ['href' => 'departments/projects.php', 'icon' => '📋', 'label' => 'Projects'],
            ['href' => 'departments/it.php',       'icon' => '💻', 'label' => 'IT Assets'],
            ['href' => 'departments/clients.php',  'icon' => '🏢', 'label' => 'Clients'],
        ],
        'Marketing' => [
            ['href' => 'departments/marketing.php?view=blog-posts', 'icon' => '✍️', 'label' => 'New Blog Post'],
            ['href' => 'departments/marketing.php',                 'icon' => '📈', 'label' => 'Campaigns'],
        ],
        'Business Development' => [
            ['href' => 'departments/bd.php?action=new_lead', 'icon' => '🎯', 'label' => 'Add Lead'],
            ['href' => 'departments/bd.php',                 'icon' => '📋', 'label' => 'My Tasks'],
        ],
        'Finance' => [
            ['href' => 'departments/finance.php?action=new_invoice',   'icon' => '💰', 'label' => 'New Invoice'],
            ['href' => 'departments/finance.php?action=new_quotation', 'icon' => '📄', 'label' => 'New Quote'],
        ],
        'HR' => [
            ['href' => 'departments/hr.php', 'icon' => '📅', 'label' => 'Request Leave'],
            ['href' => 'departments/hr.php', 'icon' => '👥', 'label' => 'HR Hub'],
        ],
        'Clients' => [
            ['href' => 'departments/clients.php', 'icon' => '🏢', 'label' => 'View Clients'],
        ],
    ];
    $quick_actions = $map[$department] ?? [];
}
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
        /* Welcome banner */
        .welcome-banner {
            display: flex; align-items: center; justify-content: space-between;
            background: #fff; border-radius: 10px; padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            flex-wrap: wrap; gap: 0.75rem;
        }
        .welcome-banner h1 { font-size: 1.35rem; margin: 0 0 0.2rem; color: #111827; }
        .welcome-banner p  { margin: 0; color: #9ca3af; font-size: 0.85rem; }
        .welcome-badges { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .role-badge, .dept-badge {
            display: inline-flex; align-items: center; gap: 0.25rem;
            padding: 0.3rem 0.75rem; border-radius: 20px;
            font-size: 0.8rem; font-weight: 600;
        }
        /* Blog rows */
        .blog-row { padding: 0.65rem 0; border-bottom: 1px solid #f3f4f6; }
        .blog-row:last-child { border-bottom: none; }
        .blog-row strong { font-size: 0.875rem; color: #111827; }
        .blog-meta { font-size: 0.75rem; color: #9ca3af; margin: 0.15rem 0; }
        .blog-excerpt { font-size: 0.82rem; color: #6b7280; margin: 0.2rem 0 0; }
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
    <?php
    $asset_base = '';
    $nav_base   = 'departments/';
    include 'includes/header.php';
    include 'includes/sidebar.php';
    ?>
    
    <div class="main-content">

        <!-- ═══ WELCOME BANNER ═══ -->
        <?php
        $hour = (int)date('H');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $role_colors = ['admin' => '#e8a020', 'manager' => '#3b82f6', 'employee' => '#22c55e'];
        $role_color  = $role_colors[$role] ?? '#6b7280';
        ?>
        <div class="welcome-banner">
            <div class="welcome-text">
                <h1><?= $greeting ?>, <?= Security::escapeHTML($username) ?>!</h1>
                <p><?= date('l, F j, Y') ?></p>
            </div>
            <div class="welcome-badges">
                <span class="role-badge" style="background:<?= $role_color ?>20;color:<?= $role_color ?>;border:1px solid <?= $role_color ?>40;">
                    <?= ucfirst($role) ?>
                </span>
                <?php if ($department): ?>
                <span class="dept-badge" style="background:<?= $dept_color ?>20;color:<?= $dept_color ?>;border:1px solid <?= $dept_color ?>40;">
                    <?= $dept_icon ?> <?= Security::escapeHTML($department) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- ═══ QUICK ACTIONS ═══ -->
        <?php if (!empty($quick_actions)): ?>
        <div class="quick-actions">
            <h3>🚀 Quick Actions</h3>
            <div class="action-buttons">
                <?php foreach ($quick_actions as $a): ?>
                <a href="<?= Security::escapeHTML($a['href']) ?>" class="action-btn">
                    <span class="icon"><?= $a['icon'] ?></span>
                    <?= Security::escapeHTML($a['label']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
        <!-- ═══════════════════════════════════════════════
             ADMIN VIEW — full org-wide dashboard
        ═══════════════════════════════════════════════ -->

        <!-- Global Stats Grid -->
        <div class="dashboard-grid">
            <div class="stats-widget">
                <div class="stat-number"><?= $stats['total_projects'] ?></div>
                <div class="stat-label">Total Projects</div>
                <div class="stat-change stat-increase">↗ <?= $stats['in_progress_projects'] ?> active</div>
            </div>
            <div class="stats-widget">
                <div class="stat-number"><?= $stats['active_campaigns'] ?></div>
                <div class="stat-label">Active Campaigns</div>
                <div class="stat-change stat-increase">📈 Running</div>
            </div>
            <div class="stats-widget">
                <div class="stat-number"><?= $stats['total_leads'] ?></div>
                <div class="stat-label">Total Leads</div>
                <div class="stat-change stat-increase">🎯 <?= $stats['new_leads'] ?> new</div>
            </div>
            <div class="stats-widget">
                <div class="stat-number"><?= $stats['total_employees'] ?></div>
                <div class="stat-label">Team Members</div>
                <div class="stat-change stat-increase">👥 Active</div>
            </div>
            <div class="stats-widget">
                <div class="stat-number"><?= $stats['active_clients'] ?></div>
                <div class="stat-label">Active Clients</div>
                <div class="stat-change stat-increase">🏢 Engaged</div>
            </div>
            <div class="stats-widget">
                <div class="stat-number"><?= $stats['total_invoices'] ?></div>
                <div class="stat-label">Total Invoices</div>
                <div class="stat-change stat-increase">💰 Finance</div>
            </div>
        </div>

        <div class="dashboard-row">
            <div>
                <div class="chart-container">
                    <h3>💻 Project Status</h3>
                    <div style="height:220px;"><canvas id="projectStatusChart"></canvas></div>
                </div>
                <div class="chart-container">
                    <h3>📊 Monthly Trends</h3>
                    <div style="height:220px;"><canvas id="monthlyTrendsChart"></canvas></div>
                </div>
            </div>
            <div>
                <div class="calendar-widget"><?php include_once 'includes/_calendar_widget.php'; ?></div>
                <div class="chart-container">
                    <h3>🏢 Team Distribution</h3>
                    <div style="height:200px;"><canvas id="departmentChart"></canvas></div>
                </div>
                <div class="chart-container">
                    <h3>📰 Recent Blog Posts</h3>
                    <?php if (empty($recent_blog_posts)): ?>
                        <p style="color:#999;text-align:center;padding:1.5rem 0;">No published posts yet. <a href="departments/marketing.php?view=blog-posts">Create one</a></p>
                    <?php else: foreach ($recent_blog_posts as $post): ?>
                        <div class="blog-row">
                            <strong><?= Security::escapeHTML($post['title']) ?></strong>
                            <div class="blog-meta">✍️ <?= Security::escapeHTML($post['author'] ?? '—') ?> &bull; <?= date('M j, Y', strtotime($post['publish_date'])) ?></div>
                            <?php if ($post['excerpt']): ?><p class="blog-excerpt"><?= Security::escapeHTML(mb_strimwidth($post['excerpt'], 0, 100, '…')) ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; endif; ?>
                    <div style="text-align:center;margin-top:0.75rem;"><a href="departments/marketing.php?view=blog-posts" class="btn">View All</a></div>
                </div>
            </div>
        </div>

        <!-- All Department Cards -->
        <h2 style="margin-bottom:1rem;">🏢 Department Modules</h2>
        <div class="department-grid">
            <?php
            $dept_cards = [
                ['href'=>'departments/projects.php',  'icon'=>'📋', 'title'=>'Project Management',       'desc'=>'All org-wide projects — track progress, team assignments and blockers.', 'stat1'=>$stats['in_progress_projects'].' active', 'stat2'=>$stats['completed_projects'].' completed'],
                ['href'=>'departments/it.php',        'icon'=>'💻', 'title'=>'IT Department',            'desc'=>'Asset inventory, software licenses, and IT infrastructure.',             'stat1'=>'Assets & Licenses',                     'stat2'=>'IT team only'],
                ['href'=>'departments/marketing.php', 'icon'=>'📈', 'title'=>'Marketing',              'desc'=>'Campaigns, blog posts, social media and analytics.',     'stat1'=>$stats['active_campaigns'].' campaigns',  'stat2'=>$stats['total_campaigns'].' total'],
                ['href'=>'departments/bd.php',        'icon'=>'🎯', 'title'=>'Business Development',   'desc'=>'Leads, pipeline, meetings and growth tracking.',         'stat1'=>$stats['total_leads'].' leads',           'stat2'=>$stats['meetings_booked'].' meetings'],
                ['href'=>'departments/finance.php',   'icon'=>'💰', 'title'=>'Finance',                'desc'=>'Invoices, quotations, expenses and PDF documents.',      'stat1'=>$stats['total_invoices'].' invoices',     'stat2'=>($stats['total_quotations'] ?? 0).' quotes'],
                ['href'=>'departments/hr.php',        'icon'=>'👥', 'title'=>'HR',                     'desc'=>'Employees, leave requests and performance reviews.',     'stat1'=>$stats['total_employees'].' active',      'stat2'=>($stats['pending_leave_requests'] ?? 0).' pending leave'],
                ['href'=>'departments/clients.php',   'icon'=>'🏢', 'title'=>'Clients',                'desc'=>'Client profiles, relationships and communications.',     'stat1'=>$stats['active_clients'].' active',       'stat2'=>$stats['total_clients'].' total'],
            ];
            foreach ($dept_cards as $card): ?>
            <div class="department-card" onclick="window.location.href='<?= $card['href'] ?>'">
                <h3><?= $card['icon'] ?> <?= $card['title'] ?></h3>
                <p><?= $card['desc'] ?></p>
                <div style="display:flex;justify-content:space-between;align-items:center;margin:1rem 0;">
                    <span class="department-status status-active">Active</span>
                    <div style="text-align:right;">
                        <small><?= $card['stat1'] ?></small><br>
                        <small><?= $card['stat2'] ?></small>
                    </div>
                </div>
                <a href="<?= $card['href'] ?>" class="btn">Open</a>
            </div>
            <?php endforeach; ?>
        </div>

        <?php elseif ($role === 'manager'): ?>
        <!-- ═══════════════════════════════════════════════
             MANAGER VIEW — dept-deep + team overview
        ═══════════════════════════════════════════════ -->

        <!-- Dept Stats -->
        <div class="dashboard-grid">
            <?php foreach (['a','b','c','d'] as $k): ?>
            <div class="stats-widget" style="border-top:3px solid <?= $dept_color ?>;">
                <div class="stat-number"><?= $dept_stats[$k] ?></div>
                <div class="stat-label"><?= $dept_stats[$k.'_label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-row">
            <div>
                <div class="chart-container">
                    <h3>💻 Project Status</h3>
                    <div style="height:220px;"><canvas id="projectStatusChart"></canvas></div>
                </div>
                <div class="chart-container">
                    <h3>📊 Monthly Trends</h3>
                    <div style="height:220px;"><canvas id="monthlyTrendsChart"></canvas></div>
                </div>
                <!-- My work items -->
                <?php if (!empty($my_projects)): ?>
                <div class="chart-container">
                    <h3><?= $dept_icon ?> <?= Security::escapeHTML($department) ?> — My Items</h3>
                    <?php include_once 'includes/_my_work_list.php'; ?>
                </div>
                <?php endif; ?>
            </div>
            <div>
                <div class="calendar-widget"><?php include_once 'includes/_calendar_widget.php'; ?></div>
                <!-- Team Members -->
                <?php if (!empty($my_team)): ?>
                <div class="chart-container">
                    <h3>👥 Team — <?= Security::escapeHTML($department) ?> (<?= count($my_team) ?>)</h3>
                    <div style="display:flex;flex-direction:column;gap:0.5rem;margin-top:0.5rem;">
                        <?php foreach ($my_team as $member): ?>
                        <div style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid #f3f4f6;">
                            <div style="width:32px;height:32px;border-radius:50%;background:<?= $dept_color ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;flex-shrink:0;">
                                <?= strtoupper(mb_substr($member['username'], 0, 2)) ?>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:600;font-size:0.85rem;"><?= Security::escapeHTML($member['username']) ?></div>
                                <div style="font-size:0.75rem;color:#6b7280;"><?= Security::escapeHTML($member['email']) ?></div>
                            </div>
                            <span style="font-size:0.72rem;padding:2px 8px;border-radius:10px;background:#f3f4f6;color:#374151;"><?= ucfirst($member['role']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <!-- All dept links -->
                <div class="chart-container">
                    <h3>🔗 All Departments</h3>
                    <div style="display:flex;flex-direction:column;gap:0.35rem;margin-top:0.5rem;">
                        <?php foreach ($dept_config as $dname => $dcfg): ?>
                        <a href="departments/<?= $dcfg['slug'] ?>.php" style="display:flex;align-items:center;gap:0.6rem;padding:0.45rem 0.75rem;border-radius:6px;text-decoration:none;color:<?= $dname === $department ? '#fff' : '#374151' ?>;background:<?= $dname === $department ? $dept_color : '#f9fafb' ?>;font-size:0.85rem;transition:background 0.15s;">
                            <span><?= $dcfg['icon'] ?></span><span><?= $dname ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- ═══════════════════════════════════════════════
             EMPLOYEE VIEW — personal my-work dashboard
        ═══════════════════════════════════════════════ -->

        <!-- Personal Stats -->
        <div class="dashboard-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
            <div class="stats-widget" style="border-top:3px solid <?= $dept_color ?>;">
                <div class="stat-number"><?= count($my_projects) ?></div>
                <div class="stat-label">My <?= $department === 'IT' ? 'Projects' : ($department === 'Finance' ? 'Quotes' : ($department === 'HR' ? 'Leave Requests' : 'Items')) ?></div>
            </div>
            <?php if (!empty($my_tasks)): ?>
            <div class="stats-widget" style="border-top:3px solid <?= $dept_color ?>;">
                <div class="stat-number"><?= count($my_tasks) ?></div>
                <div class="stat-label">Pending Tasks</div>
            </div>
            <?php endif; ?>
            <?php if (!empty($my_docs)): ?>
            <div class="stats-widget" style="border-top:3px solid <?= $dept_color ?>;">
                <div class="stat-number"><?= count($my_docs) ?></div>
                <div class="stat-label"><?= $department === 'Marketing' ? 'My Posts' : 'My Invoices' ?></div>
            </div>
            <?php endif; ?>
            <div class="stats-widget">
                <div class="stat-number"><?= $dept_stats['a'] ?></div>
                <div class="stat-label"><?= $dept_stats['a_label'] ?> (Org)</div>
            </div>
        </div>

        <div class="dashboard-row">
            <div>
                <?php if (!empty($my_projects)): ?>
                <div class="chart-container">
                    <h3><?= $dept_icon ?> My Work — <?= Security::escapeHTML($department) ?></h3>
                    <?php include_once 'includes/_my_work_list.php'; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($my_tasks)): ?>
                <div class="chart-container">
                    <h3>✅ My Pending Tasks</h3>
                    <div style="display:flex;flex-direction:column;gap:0.5rem;margin-top:0.5rem;">
                        <?php foreach ($my_tasks as $t): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0;border-bottom:1px solid #f3f4f6;">
                            <div>
                                <div style="font-weight:500;font-size:0.875rem;"><?= Security::escapeHTML(mb_strimwidth($t['task_description'], 0, 55, '…')) ?></div>
                                <?php if ($t['company_name'] ?? null): ?><div style="font-size:0.75rem;color:#6b7280;"><?= Security::escapeHTML($t['company_name']) ?></div><?php endif; ?>
                            </div>
                            <?php if ($t['due_date']): ?>
                            <span style="font-size:0.72rem;color:#ef4444;white-space:nowrap;margin-left:0.5rem;">Due <?= date('M j', strtotime($t['due_date'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($my_docs)): ?>
                <div class="chart-container">
                    <h3><?= $department === 'Marketing' ? '✍️ My Blog Posts' : '📄 My Invoices' ?></h3>
                    <?php foreach ($my_docs as $d): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0;border-bottom:1px solid #f3f4f6;">
                        <div style="font-size:0.875rem;font-weight:500;"><?= Security::escapeHTML($d['title'] ?? $d['invoice_number'] ?? '—') ?></div>
                        <?php $ds = $d['status'] ?? ''; $dc = $ds === 'published' || $ds === 'paid' ? '#22c55e' : ($ds === 'pending' ? '#f59e0b' : '#9ca3af'); ?>
                        <span style="font-size:0.72rem;padding:2px 8px;border-radius:10px;background:<?= $dc ?>20;color:<?= $dc ?>;"><?= ucfirst($ds) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div>
                <div class="calendar-widget"><?php include_once 'includes/_calendar_widget.php'; ?></div>
                <div class="chart-container" style="text-align:center;padding:2rem 1.5rem;">
                    <div style="font-size:3rem;margin-bottom:0.75rem;"><?= $dept_icon ?></div>
                    <h3 style="margin-bottom:0.5rem;"><?= Security::escapeHTML($department) ?></h3>
                    <p style="color:#6b7280;font-size:0.875rem;margin-bottom:1rem;">Go to your department workspace</p>
                    <a href="departments/<?= $dept_slug ?>.php" class="btn" style="background:<?= $dept_color ?>;border-color:<?= $dept_color ?>;">Open <?= Security::escapeHTML($department) ?></a>
                </div>
            </div>
        </div>

        <?php endif; /* end role check */ ?>
    </div>
     <script src="js/notification.js"></script>                            
    <script>
        
        // Charts Configuration
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        // Project Status Chart
        const _psc = document.getElementById('projectStatusChart');
        if (_psc) new Chart(_psc.getContext('2d'), {
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
        const _mtc = document.getElementById('monthlyTrendsChart');
        if (_mtc) new Chart(_mtc.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($monthly_trends['labels']); ?>,
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
        const _dcc = document.getElementById('departmentChart');
        if (_dcc) new Chart(_dcc.getContext('2d'), {
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