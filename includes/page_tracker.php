<?php
// Page visit tracking - include this at the top of pages you want to track

function trackPageVisit($page_name, $additional_data = null) {
    // Only track if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    try {
        require_once __DIR__ . '/ActivityLogger.php';
        require_once __DIR__ . '/../config/database.php';
        
        $database = new Database();
        $db = $database->getConnection();
        $logger = new ActivityLogger($db);
        
        $description = "Visited {$page_name} page";
        if ($additional_data) {
            $logger->logActivity('page_visit', $description, ['additional_data' => $additional_data]);
        } else {
            $logger->logActivity('page_visit', $description);
        }
        
    } catch (Exception $e) {
        // Don't break the page if logging fails
        error_log("Page tracking failed: " . $e->getMessage());
    }
}

// Auto-detect page name from current script
function autoTrackPageVisit() {
    $script_name = basename($_SERVER['SCRIPT_NAME'], '.php');
    $page_map = [
        'dashboard' => 'Dashboard',
        'hr' => 'HR Department',
        'marketing' => 'Marketing Department',
        'finance' => 'Finance Department',
        'clients' => 'Client Management',
        'projects' => 'Project Management',
        'insights' => 'Business Insights'
    ];
    
    $page_name = $page_map[$script_name] ?? ucfirst($script_name);
    trackPageVisit($page_name);
}

// Call this function to automatically track the current page
autoTrackPageVisit();
?>