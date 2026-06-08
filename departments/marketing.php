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
    Security::requireDepartmentAccess('Marketing');

    $database = new Database();
    $db = $database->getConnection();

    // Auto-migrate blog_posts: add columns introduced in the 2026-06-08 migration
    // if they don't exist yet so the page works before a manual DB migration is run.
    try {
        $col_check = $db->query("SHOW COLUMNS FROM `blog_posts` LIKE 'client_id'");
        if ($col_check->rowCount() === 0) {
            $db->exec("ALTER TABLE `blog_posts`
                ADD COLUMN `client_id`   INT          DEFAULT NULL,
                ADD COLUMN `campaign_id` INT          DEFAULT NULL,
                ADD COLUMN `author_id`   INT          DEFAULT NULL,
                ADD COLUMN `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
    } catch (Exception $e) {
        error_log("blog_posts auto-migration error: " . $e->getMessage());
    }

    // Get user info
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];
    $department = $_SESSION['department'];
    $email = $_SESSION['email'];

    // Handle new campaign creation
    if ($_POST && isset($_POST['create_campaign'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        
        $client_id = (int)$_POST['client_id'];
        $campaign_name = Security::sanitizeInput($_POST['campaign_name']);
        $campaign_type = Security::sanitizeInput($_POST['campaign_type']);
        $budget = floatval($_POST['budget']);
        $start_date = Security::sanitizeInput($_POST['start_date']);
        $end_date = Security::sanitizeInput($_POST['end_date']);
        
        $query = "INSERT INTO marketing_campaigns (client_id, campaign_name, campaign_type, budget, start_date, end_date) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$client_id, $campaign_name, $campaign_type, $budget, $start_date, $end_date]);
    }

    // Handle campaign updates
    if ($_POST && isset($_POST['update_campaign'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        
        $campaign_id = (int)$_POST['campaign_id'];
        $status = Security::sanitizeInput($_POST['status']);
        $metrics = Security::sanitizeInput($_POST['metrics']);
        
        $query = "UPDATE marketing_campaigns SET status = ?, metrics = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$status, $metrics, $campaign_id]);
    }

    // Handle social media post creation
    if ($_POST && isset($_POST['create_social_post'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        
        $client_id = (int)$_POST['client_id'];
        $campaign_id = !empty($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : null;
        $platform = Security::sanitizeInput($_POST['platform']);
        $content = Security::sanitizeInput($_POST['content']);
        $scheduled_for = Security::sanitizeInput($_POST['scheduled_date']);
        
        $query = "INSERT INTO social_media_posts (client_id, campaign_id, platform, content, scheduled_for) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$client_id, $campaign_id, $platform, $content, $scheduled_for]);
    }

    // Handle social media post updates
    if ($_POST && isset($_POST['update_social_post'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        
        $post_id = (int)$_POST['post_id'];
        $platform = Security::sanitizeInput($_POST['platform']);
        $content = Security::sanitizeInput($_POST['content']);
        $scheduled_for = Security::sanitizeInput($_POST['scheduled_date']);
        $status = Security::sanitizeInput($_POST['status']);
        $engagement_metrics = Security::sanitizeInput($_POST['engagement_metrics']);
        
        $query = "UPDATE social_media_posts SET platform = ?, content = ?, scheduled_for = ?, status = ?, engagement_stats = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$platform, $content, $scheduled_for, $status, $engagement_metrics, $post_id]);
    }

    // Handle social media post deletion
    if ($_POST && isset($_POST['delete_social_post'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        
        $post_id = (int)$_POST['post_id'];
        $query = "DELETE FROM social_media_posts WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$post_id]);
    }

    // Handle email campaign creation
    if ($_POST && isset($_POST['create_email_campaign'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        
        $client_id = (int)$_POST['client_id'];
        $campaign_name = Security::sanitizeInput($_POST['campaign_name']);
        $subject = Security::sanitizeInput($_POST['subject']);
        $content = Security::sanitizeInput($_POST['content']);
        $send_date = Security::sanitizeInput($_POST['send_date']);
        
        $query = "INSERT INTO email_campaigns (client_id, campaign_name, subject, content, scheduled_date) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$client_id, $campaign_name, $subject, $content, $send_date]);
    }

    // Handle email campaign updates
    if ($_POST && isset($_POST['update_email_campaign'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        
        $campaign_id = (int)$_POST['campaign_id'];
        $subject = Security::sanitizeInput($_POST['subject']);
        $content = Security::sanitizeInput($_POST['content']);
        $send_date = Security::sanitizeInput($_POST['send_date']);
        $status = Security::sanitizeInput($_POST['status']);
        
        $query = "UPDATE email_campaigns SET subject = ?, content = ?, scheduled_date = ?, status = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$subject, $content, $send_date, $status, $campaign_id]);
    }

    // Handle email campaign sending
    if ($_POST && isset($_POST['send_email_campaign'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        
        $campaign_id = (int)$_POST['campaign_id'];
        $query = "UPDATE email_campaigns SET status = 'sent', sent_date = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$campaign_id]);
        
        // Update recipients status
        $query = "UPDATE email_recipients SET status = 'sent', sent_at = CURRENT_TIMESTAMP WHERE email_campaign_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$campaign_id]);
    }

    // Handle email recipient addition
    if ($_POST && isset($_POST['add_email_recipient'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        
        $campaign_id = (int)$_POST['campaign_id'];
        $email = Security::sanitizeInput($_POST['email']);
        $name = Security::sanitizeInput($_POST['name']);
        
        $query = "INSERT INTO email_recipients (email_campaign_id, email, name) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$campaign_id, $email, $name]);
        
        // Update total recipients count
        $query = "UPDATE email_campaigns SET total_recipients = (SELECT COUNT(*) FROM email_recipients WHERE email_campaign_id = ?) WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$campaign_id, $campaign_id]);
    }

    // Handle blog post creation
    if ($_POST && isset($_POST['create_blog_post'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        
        $client_id = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null;
        $campaign_id = !empty($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : null;
        $title = Security::sanitizeInput($_POST['title']);
        $content = Security::sanitizeInput($_POST['content']);
        $author_id = !empty($_POST['author_id']) ? (int)$_POST['author_id'] : $_SESSION['user_id']; // Default to current user
        $category = Security::sanitizeInput($_POST['category']);
        $tags = Security::sanitizeInput($_POST['tags']);
        $publish_date = Security::sanitizeInput($_POST['publish_date']);
        $status = Security::sanitizeInput($_POST['status']);
        $featured_image = Security::sanitizeInput($_POST['featured_image'] ?? '');
        $excerpt = Security::sanitizeInput($_POST['excerpt'] ?? '');
        
        // Resolve author name from user id
        $author_name = $_SESSION['username'];
        if ($author_id && $author_id !== (int)$_SESSION['user_id']) {
            $a_stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
            $a_stmt->execute([$author_id]);
            $a_row = $a_stmt->fetch(PDO::FETCH_ASSOC);
            if ($a_row) $author_name = $a_row['username'];
        }

        // Generate a unique URL slug from the title
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', trim($title)));
        $slug = trim($slug, '-') . '-' . substr(md5(uniqid()), 0, 6);

        $query = "INSERT INTO blog_posts (client_id, campaign_id, slug, title, content, author, author_id, category, tags, published_at, status, featured_image, excerpt)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$client_id, $campaign_id, $slug, $title, $content, $author_name, $author_id, $category, $tags, $publish_date, $status, $featured_image, $excerpt]);
    }

    // Handle blog post updates
    if ($_POST && isset($_POST['update_blog_post'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        
        $post_id = (int)$_POST['post_id'];
        $title = Security::sanitizeInput($_POST['title']);
        $content = Security::sanitizeInput($_POST['content']);
        $category = Security::sanitizeInput($_POST['category']);
        $tags = Security::sanitizeInput($_POST['tags']);
        $publish_date = Security::sanitizeInput($_POST['publish_date']);
        $status = Security::sanitizeInput($_POST['status']);
        $featured_image = Security::sanitizeInput($_POST['featured_image'] ?? '');
        $excerpt = Security::sanitizeInput($_POST['excerpt'] ?? '');
        
        $query = "UPDATE blog_posts SET title = ?, content = ?, category = ?, tags = ?, published_at = ?, status = ?, featured_image = ?, excerpt = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$title, $content, $category, $tags, $publish_date, $status, $featured_image, $excerpt, $post_id]);
    }

    // Handle blog post deletion (soft delete)
    if ($_POST && isset($_POST['delete_blog_post'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        
        $post_id = (int)$_POST['post_id'];
        
        // Soft delete by changing status to 'deleted'
        $query = "UPDATE blog_posts SET status = 'deleted', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$post_id]);
    }

    // Get current view parameter
    $view = $_GET['view'] ?? 'overview';

    // Get all campaigns with client info
    $query = "SELECT mc.*, c.name as client_name, c.company as client_company 
              FROM marketing_campaigns mc 
              LEFT JOIN clients c ON mc.client_id = c.id 
              ORDER BY mc.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get clients for dropdown
    $query = "SELECT id, name, company FROM clients ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get client overview data
    $query = "SELECT c.*, 
              COUNT(mc.id) as total_campaigns,
              SUM(CASE WHEN mc.status = 'active' THEN 1 ELSE 0 END) as active_campaigns,
              SUM(mc.budget) as total_budget
              FROM clients c 
              LEFT JOIN marketing_campaigns mc ON c.id = mc.client_id 
              WHERE c.status = 'active'
              GROUP BY c.id 
              ORDER BY total_campaigns DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $client_overview = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get social media posts
    $query = "SELECT smp.*, c.name as client_name, mc.campaign_name 
              FROM social_media_posts smp 
              LEFT JOIN clients c ON smp.client_id = c.id 
              LEFT JOIN marketing_campaigns mc ON smp.campaign_id = mc.id 
              ORDER BY smp.scheduled_for ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $social_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get email campaigns
    $query = "SELECT ec.*, c.name as client_name, c.company as client_company, mc.campaign_name
              FROM email_campaigns ec 
              LEFT JOIN marketing_campaigns mc ON ec.marketing_campaign_id = mc.id
              LEFT JOIN clients c ON mc.client_id = c.id 
              ORDER BY ec.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $email_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get email recipients for campaigns
    $email_recipients = [];
    foreach ($email_campaigns as $campaign) {
        $query = "SELECT * FROM email_recipients WHERE email_campaign_id = ? ORDER BY created_at ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([$campaign['id']]);
        $email_recipients[$campaign['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get blog posts with client and campaign info (exclude deleted posts)
    $query = "SELECT bp.*, c.name as client_name, c.company as client_company, mc.campaign_name,
                     DATE(bp.published_at) as publish_date
              FROM blog_posts bp
              LEFT JOIN clients c ON bp.client_id = c.id
              LEFT JOIN marketing_campaigns mc ON bp.campaign_id = mc.id
              WHERE bp.status != 'deleted'
              ORDER BY bp.published_at DESC, bp.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $blog_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calendar helper functions
    function getCalendarData($posts, $campaigns, $year, $month) {
        $calendar_posts = [];
        
        // Add social media posts
        foreach ($posts as $post) {
            $scheduled_date = $post['scheduled_for'] ?? null;
            if (!$scheduled_date) continue;
            
            $post_date = date('Y-m-d', strtotime($scheduled_date));
            $post_year = date('Y', strtotime($scheduled_date));
            $post_month = date('m', strtotime($scheduled_date));
            
            if ($post_year == $year && $post_month == $month) {
                $day = date('j', strtotime($scheduled_date));
                if (!isset($calendar_posts[$day])) {
                    $calendar_posts[$day] = [];
                }
                $post['type'] = 'social_post';
                $calendar_posts[$day][] = $post;
            }
        }
        
        // Add marketing campaigns (start dates)
        foreach ($campaigns as $campaign) {
            $start_date = $campaign['start_date'] ?? null;
            if ($start_date) {
                $campaign_year = date('Y', strtotime($start_date));
                $campaign_month = date('m', strtotime($start_date));
                
                if ($campaign_year == $year && $campaign_month == $month) {
                    $day = date('j', strtotime($start_date));
                    if (!isset($calendar_posts[$day])) {
                        $calendar_posts[$day] = [];
                    }
                    $campaign['type'] = 'campaign_start';
                    $campaign['display_date'] = $start_date;
                    $calendar_posts[$day][] = $campaign;
                }
            }
            
            // Add campaign end dates
            $end_date = $campaign['end_date'] ?? null;
            if ($end_date && $end_date != $start_date) {
                $campaign_year = date('Y', strtotime($end_date));
                $campaign_month = date('m', strtotime($end_date));
                
                if ($campaign_year == $year && $campaign_month == $month) {
                    $day = date('j', strtotime($end_date));
                    if (!isset($calendar_posts[$day])) {
                        $calendar_posts[$day] = [];
                    }
                    $campaign_end = $campaign;
                    $campaign_end['type'] = 'campaign_end';
                    $campaign_end['display_date'] = $end_date;
                    $calendar_posts[$day][] = $campaign_end;
                }
            }
        }
        
        return $calendar_posts;
    }



    $current_year = date('Y');
    $current_month = date('m');
    $calendar_year = $_GET['year'] ?? $current_year;
    $calendar_month = $_GET['month'] ?? $current_month;
    $calendar_posts = getCalendarData($social_posts, $campaigns, $calendar_year, $calendar_month);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Department - Business Management</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        /* Enhanced Marketing Styles */
        /*.marketing-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }*/
        
        .marketing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eaeaea;
        }
        
        .marketing-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .marketing-title i {
            color: #4361ee;
        }
        
        .marketing-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .marketing-stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 4px solid #4361ee;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .marketing-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }
        
        .marketing-stat-card:nth-child(2) {
            border-left-color: #3a0ca3;
        }
        
        .marketing-stat-card:nth-child(3) {
            border-left-color: #f72585;
        }
        
        .marketing-stat-card:nth-child(4) {
            border-left-color: #4cc9f0;
        }
        
        .marketing-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .marketing-stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .marketing-tabs {
            display: flex;
            background: white;
            border-radius: 12px 12px 0 0;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 0;
        }
        
        .marketing-tab {
            flex: 1;
            padding: 1.25rem 1.5rem;
            background: #f8f9fa;
            border: none;
            border-right: 1px solid #eaeaea;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #666;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .marketing-tab:last-child {
            border-right: none;
        }
        
        .marketing-tab.active {
            background: #4361ee;
            color: white;
        }
        
        .marketing-tab:hover:not(.active) {
            background: #e9ecef;
        }
        
        .marketing-section {
            background: white;
            margin-bottom: 2rem;
            border-radius: 0 0 12px 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .marketing-section-header {
            background: #4361ee;
            color: white;
            padding: 1.25rem 1.5rem;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .marketing-section-content {
            padding: 1.5rem;
        }
        
        .marketing-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #f0f0f0;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .marketing-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .marketing-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .marketing-card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        .marketing-card-subtitle {
            color: #666;
            font-size: 0.9rem;
        }
        
        .marketing-card-content {
            margin-bottom: 1.5rem;
        }
        
        .marketing-card-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .marketing-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-platform {
            background: #e9ecef;
            color: #495057;
        }
        
        .badge-instagram {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            color: white;
        }
        
        .badge-facebook {
            background: #1877f2;
            color: white;
        }
        
        .badge-twitter {
            background: #1da1f2;
            color: white;
        }
        
        .badge-linkedin {
            background: #0a66c2;
            color: white;
        }
        
        .badge-status {
            background: #e9ecef;
            color: #495057;
        }
        
        .badge-draft {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-scheduled {
            background: #cce7ff;
            color: #004085;
        }
        
        .badge-published {
            background: #d4edda;
            color: #155724;
        }
        
        .marketing-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .marketing-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }
        
        .marketing-form-group {
            margin-bottom: 1.25rem;
        }
        
        .marketing-form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .marketing-form-input, 
        .marketing-form-select, 
        .marketing-form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .marketing-form-input:focus, 
        .marketing-form-select:focus, 
        .marketing-form-textarea:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
        }
        
        .marketing-form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .marketing-btn {
            background: #4361ee;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .marketing-btn:hover {
            background: #3a0ca3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
        }
        
        .marketing-btn-secondary {
            background: #6c757d;
        }
        
        .marketing-btn-secondary:hover {
            background: #5a6268;
        }
        
        .marketing-btn-success {
            background: #28a745;
        }
        
        .marketing-btn-success:hover {
            background: #218838;
        }
        
        .marketing-btn-danger {
            background: #dc3545;
        }
        
        .marketing-btn-danger:hover {
            background: #c82333;
        }
        
        .marketing-btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .marketing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .marketing-calendar {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .marketing-calendar-header {
            background: #4361ee;
            color: white;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .marketing-calendar-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .marketing-calendar-nav button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .marketing-calendar-nav button:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .marketing-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #eaeaea;
            padding: 1px;
        }
        
        .marketing-calendar-day-header {
            background: #f8f9fa;
            padding: 0.75rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
            color: #666;
        }
        
        .marketing-calendar-day {
            background: white;
            min-height: 120px;
            padding: 0.5rem;
            position: relative;
        }
        
        .marketing-calendar-day.other-month {
            background: #f8f9fa;
            color: #999;
        }
        
        .marketing-calendar-day-number {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .marketing-calendar-post {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 6px;
            padding: 0.25rem 0.5rem;
            margin-bottom: 0.25rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .marketing-calendar-post:hover {
            transform: scale(1.02);
        }
        
        .marketing-calendar-post.instagram { 
            border-color: #e91e63; 
            background: #fce4ec; 
        }
        
        .marketing-calendar-post.facebook { 
            border-color: #3f51b5; 
            background: #e8eaf6; 
        }
        
        .marketing-calendar-post.twitter { 
            border-color: #00bcd4; 
            background: #e0f2f1; 
        }
        
        .marketing-calendar-post.linkedin { 
            border-color: #ff9800; 
            background: #fff3e0; 
        }
        
        .marketing-calendar-post.campaign-start { 
            border-color: #4caf50; 
            background: #e8f5e8; 
        }
        
        .marketing-calendar-post.campaign-end { 
            border-color: #f44336; 
            background: #ffebee; 
        }
        
        .marketing-update-form {
            display: none;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eaeaea;
        }
        
        .marketing-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .marketing-modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .marketing-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .marketing-close:hover {
            color: #333;
        }
        
        .marketing-hidden {
            display: none;
        }
        
        .marketing-quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .marketing-quick-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .marketing-quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
            color: #4361ee;
        }
        
        .marketing-quick-action-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            color: #4361ee;
        }
        
        .marketing-quick-action-text {
            font-weight: 600;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .marketing-tabs {
                flex-direction: column;
            }
            
            .marketing-grid {
                grid-template-columns: 1fr;
            }
            
            .marketing-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .marketing-quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .marketing-stats {
                grid-template-columns: 1fr;
            }
            
            .marketing-quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="marketing-container">
            <div class="marketing-header">
                <h1 class="marketing-title"><i class="fas fa-chart-line"></i> Marketing Department</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
            
            <div class="marketing-tabs">
                <a href="?view=overview" class="marketing-tab <?php echo $view === 'overview' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> Overview
                </a>
                <a href="?view=social-calendar" class="marketing-tab <?php echo $view === 'social-calendar' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> Marketing Calendar
                </a>
                <a href="?view=social-posts" class="marketing-tab <?php echo $view === 'social-posts' ? 'active' : ''; ?>">
                    <i class="fas fa-share-alt"></i> Social Posts
                </a>
                <a href="?view=email-campaigns" class="marketing-tab <?php echo $view === 'email-campaigns' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Email Campaigns
                </a>
                <a href="?view=blog-posts" class="marketing-tab <?php echo $view === 'blog-posts' ? 'active' : ''; ?>">
                    <i class="fas fa-blog"></i> Blog Posts
                </a>
                <a href="?view=campaigns" class="marketing-tab <?php echo $view === 'campaigns' ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i> Campaigns
                </a>
            </div>
            
            <div class="marketing-section">
                <div class="marketing-section-content">
                    <?php if ($view === 'overview'): ?>
                        <?php
                        $total_campaigns = count($campaigns);
                        $active_campaigns = count(array_filter($campaigns, function($c) { return $c['status'] == 'active'; }));
                        $total_budget = array_sum(array_column($campaigns, 'budget'));
                        $total_clients = count($client_overview);
                        $total_social_posts = count($social_posts);
                        $scheduled_posts = count(array_filter($social_posts, function($p) { return $p['status'] == 'scheduled'; }));
                        $total_email_campaigns = count($email_campaigns);
                        $draft_emails = count(array_filter($email_campaigns, function($e) { return $e['status'] == 'draft'; }));
                        ?>
                        
                        <div class="marketing-stats">
                            <div class="marketing-stat-card">
                                <div class="marketing-stat-number"><?php echo $total_campaigns; ?></div>
                                <div class="marketing-stat-label">Total Campaigns</div>
                            </div>
                            <div class="marketing-stat-card">
                                <div class="marketing-stat-number"><?php echo $active_campaigns; ?></div>
                                <div class="marketing-stat-label">Active Campaigns</div>
                            </div>
                            <div class="marketing-stat-card">
                                <div class="marketing-stat-number">R <?php echo number_format($total_budget, 0); ?></div>
                                <div class="marketing-stat-label">Total Budget</div>
                            </div>
                            <div class="marketing-stat-card">
                                <div class="marketing-stat-number"><?php echo $total_clients; ?></div>
                                <div class="marketing-stat-label">Active Clients</div>
                            </div>
                            <div class="marketing-stat-card">
                                <div class="marketing-stat-number"><?php echo $total_social_posts; ?></div>
                                <div class="marketing-stat-label">Social Posts</div>
                            </div>
                            <div class="marketing-stat-card">
                                <div class="marketing-stat-number"><?php echo $scheduled_posts; ?></div>
                                <div class="marketing-stat-label">Scheduled Posts</div>
                            </div>
                            <div class="marketing-stat-card">
                                <div class="marketing-stat-number"><?php echo $total_email_campaigns; ?></div>
                                <div class="marketing-stat-label">Email Campaigns</div>
                            </div>
                            <div class="marketing-stat-card">
                                <div class="marketing-stat-number"><?php echo $draft_emails; ?></div>
                                <div class="marketing-stat-label">Draft Emails</div>
                            </div>
                        </div>
                        
                        <div class="marketing-quick-actions">
                            <a href="?view=social-posts#create" class="marketing-quick-action">
                                <div class="marketing-quick-action-icon"><i class="fas fa-share-alt"></i></div>
                                <div class="marketing-quick-action-text">Create Social Post</div>
                            </a>
                            <a href="?view=email-campaigns#create" class="marketing-quick-action">
                                <div class="marketing-quick-action-icon"><i class="fas fa-envelope"></i></div>
                                <div class="marketing-quick-action-text">Create Email Campaign</div>
                            </a>
                            <a href="?view=campaigns#create" class="marketing-quick-action">
                                <div class="marketing-quick-action-icon"><i class="fas fa-bullhorn"></i></div>
                                <div class="marketing-quick-action-text">Create Campaign</div>
                            </a>
                            <a href="?view=social-calendar" class="marketing-quick-action">
                                <div class="marketing-quick-action-icon"><i class="fas fa-calendar-alt"></i></div>
                                <div class="marketing-quick-action-text">View Marketing Calendar</div>
                            </a>
                        </div>
                        
                        <div class="marketing-section">
                            <div class="marketing-section-header">
                                <i class="fas fa-history"></i> Recent Activity
                            </div>
                            <div class="marketing-section-content">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                    <div>
                                        <h3 style="margin-bottom: 1rem; color: #333;">Latest Social Posts</h3>
                                        <?php foreach (array_slice($social_posts, 0, 3) as $post): ?>
                                        <div class="marketing-card">
                                            <div class="marketing-card-header">
                                                <div>
                                                    <div class="marketing-card-title"><?php echo htmlspecialchars($post['client_name'] ?? 'Unknown Client'); ?></div>
                                                    <div class="marketing-card-subtitle">
                                                        <span class="marketing-badge badge-<?php echo strtolower($post['platform']); ?>"><?php echo $post['platform']; ?></span>
                                                        <span class="marketing-badge badge-<?php echo $post['status']; ?>"><?php echo ucfirst($post['status']); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="marketing-card-content">
                                                <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 100) . (strlen($post['content']) > 100 ? '...' : ''))); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: #999;">
                                                <?php echo date('M j, Y g:i A', strtotime($post['scheduled_for'])); ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div>
                                        <h3 style="margin-bottom: 1rem; color: #333;">Email Campaigns</h3>
                                        <?php foreach (array_slice($email_campaigns, 0, 3) as $campaign): ?>
                                        <div class="marketing-card">
                                            <div class="marketing-card-header">
                                                <div>
                                                    <div class="marketing-card-title"><?php echo htmlspecialchars($campaign['campaign_name']); ?></div>
                                                    <div class="marketing-card-subtitle">
                                                        <span class="marketing-badge badge-<?php echo $campaign['status']; ?>"><?php echo ucfirst($campaign['status']); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="marketing-card-content">
                                                <strong>Subject:</strong> <?php echo htmlspecialchars($campaign['subject']); ?><br>
                                                <strong>Recipients:</strong> <?php echo $campaign['recipients_count'] ?? 0; ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: #999;">
                                                Client: <?php echo htmlspecialchars($campaign['client_name'] ?? 'Unknown Client'); ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    <?php elseif ($view === 'social-calendar'): ?>
                        <div class="marketing-calendar">
                            <div class="marketing-calendar-header">
                                <h2><i class="fas fa-calendar-alt"></i> Marketing Calendar</h2>
                                <div class="marketing-calendar-nav">
                                    <button onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i> Previous</button>
                                    <span id="current-month"><?php echo date('F Y', mktime(0, 0, 0, $calendar_month, 1, $calendar_year)); ?></span>
                                    <button onclick="changeMonth(1)">Next <i class="fas fa-chevron-right"></i></button>
                                </div>
                            </div>
                            
                            <div class="marketing-calendar-grid">
                                <div class="marketing-calendar-day-header">Sun</div>
                                <div class="marketing-calendar-day-header">Mon</div>
                                <div class="marketing-calendar-day-header">Tue</div>
                                <div class="marketing-calendar-day-header">Wed</div>
                                <div class="marketing-calendar-day-header">Thu</div>
                                <div class="marketing-calendar-day-header">Fri</div>
                                <div class="marketing-calendar-day-header">Sat</div>
                                
                                <?php
                                $first_day = mktime(0, 0, 0, $calendar_month, 1, $calendar_year);
                                $last_day = mktime(0, 0, 0, $calendar_month + 1, 0, $calendar_year);
                                $days_in_month = date('t', $first_day);
                                $start_day = date('w', $first_day);
                                
                                // Previous month days
                                $prev_month_days = date('t', mktime(0, 0, 0, $calendar_month - 1, 1, $calendar_year));
                                for ($i = $start_day - 1; $i >= 0; $i--) {
                                    $day = $prev_month_days - $i;
                                    echo "<div class='marketing-calendar-day other-month'><div class='marketing-calendar-day-number'>$day</div></div>";
                                }
                                
                                // Current month days
                                for ($day = 1; $day <= $days_in_month; $day++) {
                                    echo "<div class='marketing-calendar-day'>";
                                    echo "<div class='marketing-calendar-day-number'>$day</div>";
                                    
                                    if (isset($calendar_posts[$day])) {
                                        foreach ($calendar_posts[$day] as $item) {
                                            if ($item['type'] === 'social_post') {
                                                $platform_class = strtolower($item['platform']);
                                                echo "<div class='marketing-calendar-post " . Security::escapeHTML($platform_class) . "' onclick='viewPost(" . (int)$item['id'] . ")'>";
                                                echo "<strong>" . Security::escapeHTML(ucfirst($item['platform'])) . "</strong><br>";
                                                echo Security::escapeHTML(substr($item['content'], 0, 30)) . '...';
                                                echo "</div>";
                                            } elseif ($item['type'] === 'campaign_start') {
                                                echo "<div class='marketing-calendar-post campaign-start' onclick='viewCampaign(" . (int)$item['id'] . ")'>";
                                                echo "<strong>🚀 Campaign Start</strong><br>";
                                                echo Security::escapeHTML(substr($item['campaign_name'], 0, 30)) . '...';
                                                echo "</div>";
                                            } elseif ($item['type'] === 'campaign_end') {
                                                echo "<div class='marketing-calendar-post campaign-end' onclick='viewCampaign(" . (int)$item['id'] . ")'>";
                                                echo "<strong>🏁 Campaign End</strong><br>";
                                                echo Security::escapeHTML(substr($item['campaign_name'], 0, 30)) . '...';
                                                echo "</div>";
                                            }
                                        }
                                    }
                                    
                                    echo "</div>";
                                }
                                
                                // Next month days to fill grid
                                $remaining_cells = 42 - ($start_day + $days_in_month);
                                for ($day = 1; $day <= $remaining_cells; $day++) {
                                    echo "<div class='marketing-calendar-day other-month'><div class='marketing-calendar-day-number'>$day</div></div>";
                                }
                                ?>
                            </div>
                        </div>
                        
                    <?php elseif ($view === 'social-posts'): ?>
                        <div class="marketing-section" id="create">
                            <div class="marketing-section-header">
                                <i class="fas fa-plus-circle"></i> Create New Social Media Post
                            </div>
                            <div class="marketing-section-content">
                                <form method="post" class="marketing-form">
                                    <?php echo Security::getCSRFTokenField(); ?>
                                    <div class="marketing-form-grid">
                                        <div class="marketing-form-group">
                                            <label for="client_id" class="marketing-form-label">Client:</label>
                                            <select id="client_id" name="client_id" class="marketing-form-select" required>
                                                <option value="">Select Client</option>
                                                <?php foreach ($clients as $client): ?>
                                                    <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?> - <?php echo htmlspecialchars($client['company']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label for="campaign_id" class="marketing-form-label">Campaign (Optional):</label>
                                            <select id="campaign_id" name="campaign_id" class="marketing-form-select">
                                                <option value="">Select Campaign</option>
                                                <?php foreach ($campaigns as $campaign): ?>
                                                    <option value="<?php echo $campaign['id']; ?>"><?php echo htmlspecialchars($campaign['campaign_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label for="platform" class="marketing-form-label">Platform:</label>
                                            <select id="platform" name="platform" class="marketing-form-select" required>
                                                <option value="Instagram">Instagram</option>
                                                <option value="Facebook">Facebook</option>
                                                <option value="Twitter">Twitter</option>
                                                <option value="LinkedIn">LinkedIn</option>
                                            </select>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label for="scheduled_date" class="marketing-form-label">Scheduled Date & Time:</label>
                                            <input type="datetime-local" id="scheduled_date" name="scheduled_date" class="marketing-form-input" required>
                                        </div>
                                    </div>
                                    <div class="marketing-form-group">
                                        <label for="content" class="marketing-form-label">Content:</label>
                                        <textarea id="content" name="content" class="marketing-form-textarea" required placeholder="Enter your post content here..."></textarea>
                                    </div>
                                    <button type="submit" name="create_social_post" class="marketing-btn">
                                        <i class="fas fa-plus"></i> Create Social Post
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="marketing-section">
                            <div class="marketing-section-header">
                                <i class="fas fa-share-alt"></i> Social Media Posts
                            </div>
                            <div class="marketing-section-content">
                                <div class="marketing-grid">
                                    <?php foreach ($social_posts as $post): ?>
                                    <div class="marketing-card">
                                        <div class="marketing-card-header">
                                            <div>
                                                <div class="marketing-card-title"><?php echo htmlspecialchars($post['client_name'] ?? 'Unknown Client'); ?></div>
                                                <div class="marketing-card-subtitle">
                                                    <?php if ($post['campaign_name']): ?>
                                                        Campaign: <?php echo htmlspecialchars($post['campaign_name']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="marketing-badge badge-<?php echo strtolower($post['platform']); ?>"><?php echo $post['platform']; ?></span>
                                                <span class="marketing-badge badge-<?php echo $post['status']; ?>"><?php echo ucfirst($post['status']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="marketing-card-content">
                                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem; margin-bottom: 1rem;">
                                            <div><strong>Scheduled:</strong> <?php echo date('M j, Y g:i A', strtotime($post['scheduled_for'])); ?></div>
                                            <div><strong>Created:</strong> <?php echo date('M j, Y', strtotime($post['created_at'])); ?></div>
                                        </div>
                                        
                                        <?php if (isset($post['engagement_metrics']) && $post['engagement_metrics']): ?>
                                        <div style="background: #e8f5e8; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">
                                            <strong><i class="fas fa-chart-bar"></i> Engagement:</strong><br>
                                            <?php 
                                            $metrics = json_decode($post['engagement_metrics'], true);
                                            if ($metrics) {
                                                foreach ($metrics as $key => $value) {
                                                    echo "<span style='font-size: 0.9rem;'>• " . ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "</span><br>";
                                                }
                                            }
                                            ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="marketing-card-actions">
                                            <button onclick="editPost(<?php echo $post['id']; ?>)" class="marketing-btn marketing-btn-small">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="post" style="display: inline;">
                                                <?php echo Security::getCSRFTokenField(); ?>
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <button type="submit" name="delete_social_post" class="marketing-btn marketing-btn-small marketing-btn-danger" onclick="return confirm('Delete this post?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <div id="edit-<?php echo $post['id']; ?>" class="marketing-update-form">
                                            <form method="post">
                                                <?php echo Security::getCSRFTokenField(); ?>
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <div class="marketing-form-grid">
                                                    <div class="marketing-form-group">
                                                        <label class="marketing-form-label">Platform:</label>
                                                        <select name="platform" class="marketing-form-select" required>
                                                            <option value="Instagram" <?php echo $post['platform'] == 'Instagram' ? 'selected' : ''; ?>>Instagram</option>
                                                            <option value="Facebook" <?php echo $post['platform'] == 'Facebook' ? 'selected' : ''; ?>>Facebook</option>
                                                            <option value="Twitter" <?php echo $post['platform'] == 'Twitter' ? 'selected' : ''; ?>>Twitter</option>
                                                            <option value="LinkedIn" <?php echo $post['platform'] == 'LinkedIn' ? 'selected' : ''; ?>>LinkedIn</option>
                                                        </select>
                                                    </div>
                                                    <div class="marketing-form-group">
                                                        <label class="marketing-form-label">Status:</label>
                                                        <select name="status" class="marketing-form-select" required>
                                                            <option value="draft" <?php echo $post['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                            <option value="scheduled" <?php echo $post['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                                            <option value="published" <?php echo $post['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="marketing-form-group">
                                                    <label class="marketing-form-label">Scheduled Date:</label>
                                                    <input type="datetime-local" name="scheduled_date" class="marketing-form-input" value="<?php echo date('Y-m-d\TH:i', strtotime($post['scheduled_for'])); ?>" required>
                                                </div>
                                                <div class="marketing-form-group">
                                                    <label class="marketing-form-label">Content:</label>
                                                    <textarea name="content" class="marketing-form-textarea" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                                                </div>
                                                <div class="marketing-form-group">
                                                    <label class="marketing-form-label">Engagement Metrics (JSON):</label>
                                                    <textarea name="engagement_metrics" class="marketing-form-textarea" placeholder='{"likes": 100, "comments": 10}'><?php echo htmlspecialchars($post['engagement_metrics']); ?></textarea>
                                                </div>
                                                <button type="submit" name="update_social_post" class="marketing-btn marketing-btn-small">
                                                    <i class="fas fa-save"></i> Update Post
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                    <?php elseif ($view === 'email-campaigns'): ?>
                        <div class="marketing-section" id="create">
                            <div class="marketing-section-header">
                                <i class="fas fa-plus-circle"></i> Create New Email Campaign
                            </div>
                            <div class="marketing-section-content">
                                <form method="post" class="marketing-form">
                                    <?php echo Security::getCSRFTokenField(); ?>
                                    <div class="marketing-form-grid">
                                        <div class="marketing-form-group">
                                            <label for="client_id" class="marketing-form-label">Client:</label>
                                            <select id="client_id" name="client_id" class="marketing-form-select" required>
                                                <option value="">Select Client</option>
                                                <?php foreach ($clients as $client): ?>
                                                    <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?> - <?php echo htmlspecialchars($client['company']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label for="campaign_name" class="marketing-form-label">Campaign Name:</label>
                                            <input type="text" id="campaign_name" name="campaign_name" class="marketing-form-input" required>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label for="send_date" class="marketing-form-label">Send Date & Time:</label>
                                            <input type="datetime-local" id="send_date" name="send_date" class="marketing-form-input" required>
                                        </div>
                                    </div>
                                    <div class="marketing-form-group">
                                        <label for="subject" class="marketing-form-label">Subject Line:</label>
                                        <input type="text" id="subject" name="subject" class="marketing-form-input" required placeholder="Enter email subject...">
                                    </div>
                                    <div class="marketing-form-group">
                                        <label for="content" class="marketing-form-label">Email Content:</label>
                                        <textarea id="content" name="content" class="marketing-form-textarea" required placeholder="Enter your email content here..."></textarea>
                                    </div>
                                    <button type="submit" name="create_email_campaign" class="marketing-btn">
                                        <i class="fas fa-plus"></i> Create Email Campaign
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="marketing-section">
                            <div class="marketing-section-header">
                                <i class="fas fa-envelope"></i> Email Campaigns
                            </div>
                            <div class="marketing-section-content">
                                <?php foreach ($email_campaigns as $campaign): ?>
                                <div class="marketing-card">
                                    <div class="marketing-card-header">
                                        <div>
                                            <div class="marketing-card-title"><?php echo htmlspecialchars($campaign['campaign_name']); ?></div>
                                            <div class="marketing-card-subtitle">
                                                Client: <?php echo htmlspecialchars($campaign['client_name'] ?? 'Unknown Client'); ?> - <?php echo htmlspecialchars($campaign['client_company'] ?? ''); ?>
                                            </div>
                                        </div>
                                        <span class="marketing-badge badge-<?php echo $campaign['status']; ?>"><?php echo ucfirst($campaign['status']); ?></span>
                                    </div>
                                    
                                    <div class="marketing-card-content">
                                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                                            <strong>Subject:</strong> <?php echo htmlspecialchars($campaign['subject']); ?><br>
                                            <strong>Content Preview:</strong> <?php echo substr(htmlspecialchars($campaign['content'] ?? 'No content available'), 0, 200) . '...'; ?>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                            <div><strong>Send Date:</strong> <?php echo date('M j, Y g:i A', strtotime($campaign['send_date'])); ?></div>
                                            <div><strong>Recipients:</strong> <?php echo $campaign['recipients_count'] ?? 0; ?></div>
                                            <div><strong>Sent:</strong> <?php echo $campaign['sent_count']; ?></div>
                                        </div>
                                        
                                        <?php if ($campaign['status'] === 'sent' && ($campaign['open_rate'] > 0 || $campaign['click_rate'] > 0)): ?>
                                        <div style="background: #e8f5e8; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">
                                            <strong><i class="fas fa-chart-bar"></i> Campaign Performance:</strong><br>
                                            Open Rate: <?php echo number_format($campaign['open_rate'], 1); ?>% | 
                                            Click Rate: <?php echo number_format($campaign['click_rate'], 1); ?>%
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                                            <strong>Recipients:</strong>
                                            <?php if (isset($email_recipients[$campaign['id']]) && count($email_recipients[$campaign['id']]) > 0): ?>
                                                <?php foreach ($email_recipients[$campaign['id']] as $recipient): ?>
                                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.25rem 0; border-bottom: 1px solid #e0e0e0;">
                                                    <span><?php echo htmlspecialchars($recipient['name']); ?> (<?php echo htmlspecialchars($recipient['email']); ?>)</span>
                                                    <span class="marketing-badge badge-<?php echo $recipient['status']; ?>"><?php echo ucfirst($recipient['status']); ?></span>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div style="color: #666; font-style: italic;">No recipients added yet</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="marketing-card-actions">
                                        <button onclick="editEmailCampaign(<?php echo $campaign['id']; ?>)" class="marketing-btn marketing-btn-small">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="showAddRecipient(<?php echo $campaign['id']; ?>)" class="marketing-btn marketing-btn-small marketing-btn-secondary">
                                            <i class="fas fa-user-plus"></i> Add Recipients
                                        </button>
                                        <?php if ($campaign['status'] === 'draft' && ($campaign['recipients_count'] ?? 0) > 0): ?>
                                        <form method="post" style="display: inline;">
                                            <?php echo Security::getCSRFTokenField(); ?>
                                            <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                            <button type="submit" name="send_email_campaign" class="marketing-btn marketing-btn-small marketing-btn-success" onclick="return confirm('Send this campaign to all recipients?')">
                                                <i class="fas fa-paper-plane"></i> Send Campaign
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div id="edit-email-<?php echo $campaign['id']; ?>" class="marketing-update-form">
                                        <form method="post">
                                            <?php echo Security::getCSRFTokenField(); ?>
                                            <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                            <div class="marketing-form-grid">
                                                <div class="marketing-form-group">
                                                    <label class="marketing-form-label">Send Date:</label>
                                                    <input type="datetime-local" name="send_date" class="marketing-form-input" value="<?php echo date('Y-m-d\TH:i', strtotime($campaign['send_date'])); ?>" required>
                                                </div>
                                                <div class="marketing-form-group">
                                                    <label class="marketing-form-label">Status:</label>
                                                    <select name="status" class="marketing-form-select" required>
                                                        <option value="draft" <?php echo $campaign['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                        <option value="scheduled" <?php echo $campaign['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                                        <option value="sent" <?php echo $campaign['status'] == 'sent' ? 'selected' : ''; ?>>Sent</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="marketing-form-group">
                                                <label class="marketing-form-label">Subject:</label>
                                                <input type="text" name="subject" class="marketing-form-input" value="<?php echo htmlspecialchars($campaign['subject']); ?>" required>
                                            </div>
                                            <div class="marketing-form-group">
                                                <label class="marketing-form-label">Content:</label>
                                                <textarea name="content" class="marketing-form-textarea" required><?php echo htmlspecialchars($campaign['content']); ?></textarea>
                                            </div>
                                            <button type="submit" name="update_email_campaign" class="marketing-btn marketing-btn-small">
                                                <i class="fas fa-save"></i> Update Campaign
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                    <?php elseif ($view === 'campaigns'): ?>
                        <div class="marketing-section" id="create">
                            <div class="marketing-section-header">
                                <i class="fas fa-plus-circle"></i> Create New Campaign
                            </div>
                            <div class="marketing-section-content">
                                <form method="post" class="marketing-form">
                                    <?php echo Security::getCSRFTokenField(); ?>
                                    <div class="marketing-form-grid">
                                        <div class="marketing-form-group">
                                            <label for="campaign_name" class="marketing-form-label">Campaign Name:</label>
                                            <input type="text" id="campaign_name" name="campaign_name" class="marketing-form-input" required>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label for="client_id" class="marketing-form-label">Client:</label>
                                            <select id="client_id" name="client_id" class="marketing-form-select" required>
                                                <option value="">Select Client</option>
                                                <?php foreach ($clients as $client): ?>
                                                    <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?> - <?php echo htmlspecialchars($client['company']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label for="campaign_type" class="marketing-form-label">Campaign Type:</label>
                                            <select id="campaign_type" name="campaign_type" class="marketing-form-select" required>
                                                <option value="social_media">Social Media</option>
                                                <option value="email">Email Marketing</option>
                                                <option value="brand_work">Brand Work</option>
                                                <option value="seo">SEO</option>
                                            </select>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label for="budget" class="marketing-form-label">Budget (R):</label>
                                            <input type="number" id="budget" name="budget" class="marketing-form-input" step="0.01" required>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label for="start_date" class="marketing-form-label">Start Date:</label>
                                            <input type="date" id="start_date" name="start_date" class="marketing-form-input" required>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label for="end_date" class="marketing-form-label">End Date:</label>
                                            <input type="date" id="end_date" name="end_date" class="marketing-form-input" required>
                                        </div>
                                    </div>
                                    <button type="submit" name="create_campaign" class="marketing-btn">
                                        <i class="fas fa-plus"></i> Create Campaign
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="marketing-section">
                            <div class="marketing-section-header">
                                <i class="fas fa-bullhorn"></i> Marketing Campaigns & Statistics
                            </div>
                            <div class="marketing-section-content">
                                <div class="marketing-grid">
                                    <?php foreach ($campaigns as $campaign): ?>
                                    <div class="marketing-card">
                                        <div class="marketing-card-header">
                                            <div>
                                                <div class="marketing-card-title"><?php echo htmlspecialchars($campaign['campaign_name']); ?></div>
                                                <div class="marketing-card-subtitle">
                                                    Client: <?php echo htmlspecialchars($campaign['client_name'] ?? 'Unknown'); ?> - <?php echo htmlspecialchars($campaign['client_company'] ?? ''); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="marketing-badge badge-platform"><?php echo str_replace('_', ' ', ucfirst($campaign['campaign_type'])); ?></span>
                                                <span class="marketing-badge badge-<?php echo $campaign['status']; ?>"><?php echo ucfirst($campaign['status']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="marketing-card-content">
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem;">
                                                <div><strong>Budget:</strong> R <?php echo number_format($campaign['budget'], 2); ?></div>
                                                <div><strong>Duration:</strong> <?php echo $campaign['start_date']; ?> to <?php echo $campaign['end_date']; ?></div>
                                            </div>
                                            
                                            <?php if (isset($campaign['metrics']) && $campaign['metrics']): ?>
                                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                                                <strong><i class="fas fa-chart-bar"></i> Campaign Metrics:</strong><br>
                                                <?php 
                                                $metrics = json_decode($campaign['metrics'], true);
                                                if ($metrics) {
                                                    foreach ($metrics as $key => $value) {
                                                        echo "<span style='font-size: 0.9rem;'>• " . ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "</span><br>";
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="marketing-card-actions">
                                            <button onclick="toggleUpdate(<?php echo $campaign['id']; ?>)" class="marketing-btn marketing-btn-small">
                                                <i class="fas fa-edit"></i> Update Campaign
                                            </button>
                                        </div>
                                        
                                        <div id="update-<?php echo $campaign['id']; ?>" class="marketing-update-form">
                                            <form method="post">
                                                <?php echo Security::getCSRFTokenField(); ?>
                                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                                <div class="marketing-form-grid">
                                                    <div class="marketing-form-group">
                                                        <label class="marketing-form-label">Status:</label>
                                                        <select name="status" class="marketing-form-select" required>
                                                            <option value="planning" <?php echo $campaign['status'] == 'planning' ? 'selected' : ''; ?>>Planning</option>
                                                            <option value="active" <?php echo $campaign['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                            <option value="completed" <?php echo $campaign['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                            <option value="paused" <?php echo $campaign['status'] == 'paused' ? 'selected' : ''; ?>>Paused</option>
                                                        </select>
                                                    </div>
                                                    <div class="marketing-form-group">
                                                        <label class="marketing-form-label">Metrics (JSON):</label>
                                                        <textarea name="metrics" class="marketing-form-textarea" placeholder='{"reach": 1000, "engagement": 50}'><?php echo htmlspecialchars($campaign['metrics']); ?></textarea>
                                                    </div>
                                                </div>
                                                <button type="submit" name="update_campaign" class="marketing-btn marketing-btn-small">
                                                    <i class="fas fa-save"></i> Update
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($view === 'blog-posts'): ?>
                        <div class="marketing-section" id="create">
                            <div class="marketing-section-header">
                                <i class="fas fa-plus-circle"></i> Create New Blog Post
                            </div>
                            <div class="marketing-section-content">
                                <form method="post" class="marketing-form">
                                    <?php echo Security::getCSRFTokenField(); ?>
                                    <div class="marketing-form-grid">
                                        <div class="marketing-form-group">
                                            <label class="marketing-form-label">Title:</label>
                                            <input type="text" name="title" class="marketing-form-input" required>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label class="marketing-form-label">Author:</label>
                                            <input type="text" name="author" class="marketing-form-input" value="<?php echo Security::escapeHTML($_SESSION['username'] ?? ''); ?>" required>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label class="marketing-form-label">Category:</label>
                                            <select name="category" class="marketing-form-select" required>
                                                <option value="">Select Category</option>
                                                <option value="technology">Technology</option>
                                                <option value="business">Business</option>
                                                <option value="marketing">Marketing</option>
                                                <option value="industry-insights">Industry Insights</option>
                                                <option value="case-study">Case Study</option>
                                                <option value="tutorial">Tutorial</option>
                                                <option value="news">News</option>
                                                <option value="company-update">Company Update</option>
                                            </select>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label class="marketing-form-label">Status:</label>
                                            <select name="status" class="marketing-form-select" required>
                                                <option value="draft">Draft</option>
                                                <option value="scheduled">Scheduled</option>
                                                <option value="published">Published</option>
                                            </select>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label class="marketing-form-label">Client (Optional):</label>
                                            <select name="client_id" class="marketing-form-select">
                                                <option value="">No Client</option>
                                                <?php foreach ($clients as $client): ?>
                                                    <option value="<?php echo $client['id']; ?>"><?php echo Security::escapeHTML($client['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label class="marketing-form-label">Campaign (Optional):</label>
                                            <select name="campaign_id" class="marketing-form-select">
                                                <option value="">No Campaign</option>
                                                <?php foreach ($campaigns as $campaign): ?>
                                                    <option value="<?php echo $campaign['id']; ?>"><?php echo Security::escapeHTML($campaign['campaign_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label class="marketing-form-label">Tags (comma-separated):</label>
                                            <input type="text" name="tags" class="marketing-form-input" placeholder="seo, content marketing, business growth">
                                        </div>
                                        <div class="marketing-form-group">
                                            <label class="marketing-form-label">Publish Date:</label>
                                            <input type="datetime-local" name="publish_date" class="marketing-form-input" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                                        </div>
                                        <div class="marketing-form-group">
                                            <label class="marketing-form-label">Featured Image URL (Optional):</label>
                                            <input type="url" name="featured_image" class="marketing-form-input" placeholder="https://example.com/image.jpg">
                                        </div>
                                    </div>
                                    
                                    <div class="marketing-form-group">
                                        <label class="marketing-form-label">Excerpt (Brief Description):</label>
                                        <textarea name="excerpt" class="marketing-form-textarea" rows="3" placeholder="Brief description of the blog post for previews and SEO..."></textarea>
                                    </div>
                                    
                                    <div class="marketing-form-group">
                                        <label class="marketing-form-label">Content:</label>
                                        <textarea name="content" class="marketing-form-textarea" rows="12" required placeholder="Write your blog post content here. You can use HTML for formatting..."></textarea>
                                    </div>
                                    
                                    <button type="submit" name="create_blog_post" class="marketing-btn">
                                        <i class="fas fa-plus"></i> Create Blog Post
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="marketing-section">
                            <div class="marketing-section-header">
                                <i class="fas fa-blog"></i> All Blog Posts (<?php echo count($blog_posts); ?>)
                            </div>
                            <div class="marketing-section-content">
                                <?php if (empty($blog_posts)): ?>
                                    <div style="text-align: center; padding: 2rem; color: #666;">
                                        <i class="fas fa-file-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                        <p>No blog posts created yet.</p>
                                        <p>Create your first blog post using the form above to start building your content library.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="marketing-grid">
                                        <?php foreach ($blog_posts as $post): ?>
                                            <div class="marketing-card">
                                                <div class="marketing-card-header">
                                                    <div>
                                                        <div class="marketing-card-title"><?php echo Security::escapeHTML($post['title']); ?></div>
                                                        <div class="marketing-card-subtitle">
                                                            <span>✍️ <?php echo Security::escapeHTML($post['author']); ?></span> • 
                                                            <span>📅 <?php echo date('M j, Y', strtotime($post['publish_date'])); ?></span>
                                                        </div>
                                                    </div>
                                                    <span class="marketing-badge badge-<?php echo $post['status']; ?>"><?php echo ucfirst($post['status']); ?></span>
                                                </div>
                                                
                                                <?php if ($post['featured_image']): ?>
                                                    <div style="margin-bottom: 1rem;">
                                                        <img src="<?php echo Security::escapeHTML($post['featured_image']); ?>" alt="Featured image" style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px;">
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="marketing-card-content">
                                                    <div style="margin-bottom: 1rem;">
                                                        <span class="marketing-badge badge-platform"><?php echo Security::escapeHTML($post['category']); ?></span>
                                                    </div>
                                                    
                                                    <?php if ($post['excerpt']): ?>
                                                        <p style="margin-bottom: 1rem;"><?php echo Security::escapeHTML(substr($post['excerpt'], 0, 120) . (strlen($post['excerpt']) > 120 ? '...' : '')); ?></p>
                                                    <?php else: ?>
                                                        <p style="margin-bottom: 1rem;"><?php echo Security::escapeHTML(substr(strip_tags($post['content']), 0, 120) . '...'); ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($post['tags']): ?>
                                                        <div style="margin-bottom: 1rem;">
                                                            <?php
                                                            $tags = explode(',', $post['tags']);
                                                            foreach ($tags as $tag):
                                                                $tag = trim($tag);
                                                                if ($tag):
                                                            ?>
                                                                <span class="marketing-badge badge-platform" style="margin-right: 0.5rem; margin-bottom: 0.5rem;">#<?php echo Security::escapeHTML($tag); ?></span>
                                                            <?php
                                                                endif;
                                                            endforeach;
                                                            ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($post['client_name'] || $post['campaign_name']): ?>
                                                        <div style="margin-bottom: 1rem;">
                                                            <?php if ($post['client_name']): ?>
                                                                <span class="marketing-badge badge-platform" style="margin-right: 0.5rem;">🏢 <?php echo Security::escapeHTML($post['client_name']); ?></span>
                                                            <?php endif; ?>
                                                            <?php if ($post['campaign_name']): ?>
                                                                <span class="marketing-badge badge-platform">🎯 <?php echo Security::escapeHTML($post['campaign_name']); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="marketing-card-actions">
                                                    <button onclick="togglePostUpdate(<?php echo $post['id']; ?>)" class="marketing-btn marketing-btn-small">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <?php if ($post['status'] === 'published'): ?>
                                                        <span class="marketing-badge badge-published">Published</span>
                                                    <?php elseif ($post['status'] === 'scheduled'): ?>
                                                        <span class="marketing-badge badge-scheduled">Scheduled</span>
                                                    <?php endif; ?>
                                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this blog post?')">
                                                        <?php echo Security::getCSRFTokenField(); ?>
                                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                        <button type="submit" name="delete_blog_post" class="marketing-btn marketing-btn-small marketing-btn-danger">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                                
                                                <!-- Edit Form -->
                                                <div id="post-update-<?php echo $post['id']; ?>" class="marketing-update-form">
                                                    <form method="post">
                                                        <?php echo Security::getCSRFTokenField(); ?>
                                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                        <div class="marketing-form-grid">
                                                            <div class="marketing-form-group">
                                                                <label class="marketing-form-label">Title:</label>
                                                                <input type="text" name="title" class="marketing-form-input" value="<?php echo Security::escapeHTML($post['title']); ?>" required>
                                                            </div>
                                                            <div class="marketing-form-group">
                                                                <label class="marketing-form-label">Category:</label>
                                                                <select name="category" class="marketing-form-select" required>
                                                                    <option value="technology" <?php echo $post['category'] == 'technology' ? 'selected' : ''; ?>>Technology</option>
                                                                    <option value="business" <?php echo $post['category'] == 'business' ? 'selected' : ''; ?>>Business</option>
                                                                    <option value="marketing" <?php echo $post['category'] == 'marketing' ? 'selected' : ''; ?>>Marketing</option>
                                                                    <option value="industry-insights" <?php echo $post['category'] == 'industry-insights' ? 'selected' : ''; ?>>Industry Insights</option>
                                                                    <option value="case-study" <?php echo $post['category'] == 'case-study' ? 'selected' : ''; ?>>Case Study</option>
                                                                    <option value="tutorial" <?php echo $post['category'] == 'tutorial' ? 'selected' : ''; ?>>Tutorial</option>
                                                                    <option value="news" <?php echo $post['category'] == 'news' ? 'selected' : ''; ?>>News</option>
                                                                    <option value="company-update" <?php echo $post['category'] == 'company-update' ? 'selected' : ''; ?>>Company Update</option>
                                                                </select>
                                                            </div>
                                                            <div class="marketing-form-group">
                                                                <label class="marketing-form-label">Status:</label>
                                                                <select name="status" class="marketing-form-select" required>
                                                                    <option value="draft" <?php echo $post['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                                    <option value="scheduled" <?php echo $post['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                                                    <option value="published" <?php echo $post['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                                                </select>
                                                            </div>
                                                            <div class="marketing-form-group">
                                                                <label class="marketing-form-label">Publish Date:</label>
                                                                <input type="datetime-local" name="publish_date" class="marketing-form-input" value="<?php echo date('Y-m-d\TH:i', strtotime($post['publish_date'])); ?>" required>
                                                            </div>
                                                            <div class="marketing-form-group">
                                                                <label class="marketing-form-label">Tags:</label>
                                                                <input type="text" name="tags" class="marketing-form-input" value="<?php echo Security::escapeHTML($post['tags']); ?>">
                                                            </div>
                                                            <div class="marketing-form-group">
                                                                <label class="marketing-form-label">Featured Image URL:</label>
                                                                <input type="url" name="featured_image" class="marketing-form-input" value="<?php echo Security::escapeHTML($post['featured_image']); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="marketing-form-group">
                                                            <label class="marketing-form-label">Excerpt:</label>
                                                            <textarea name="excerpt" class="marketing-form-textarea" rows="3"><?php echo Security::escapeHTML($post['excerpt']); ?></textarea>
                                                        </div>
                                                        <div class="marketing-form-group">
                                                            <label class="marketing-form-label">Content:</label>
                                                            <textarea name="content" class="marketing-form-textarea" rows="8"><?php echo Security::escapeHTML($post['content']); ?></textarea>
                                                        </div>
                                                        <button type="submit" name="update_blog_post" class="marketing-btn marketing-btn-small">
                                                            <i class="fas fa-save"></i> Update Post
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Recipient Modal -->
    <div id="recipientModal" class="marketing-modal">
        <div class="marketing-modal-content">
            <span class="marketing-close" onclick="closeRecipientModal()">&times;</span>
            <h2><i class="fas fa-user-plus"></i> Add Email Recipients</h2>
            <form method="post">
                <?php echo Security::getCSRFTokenField(); ?>
                <input type="hidden" id="modal-campaign-id" name="campaign_id" value="">
                <div class="marketing-form-group">
                    <label for="recipient-email" class="marketing-form-label">Email:</label>
                    <input type="email" id="recipient-email" name="email" class="marketing-form-input" required>
                </div>
                <div class="marketing-form-group">
                    <label for="recipient-name" class="marketing-form-label">Name:</label>
                    <input type="text" id="recipient-name" name="name" class="marketing-form-input" required>
                </div>
                <button type="submit" name="add_email_recipient" class="marketing-btn">
                    <i class="fas fa-plus"></i> Add Recipient
                </button>
            </form>
        </div>
    </div>
    
    <script src="../js/notification.js"></script>  
    <script>
        function toggleUpdate(campaignId) {
            const form = document.getElementById('update-' + campaignId);
            form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
        }

        function togglePostUpdate(postId) {
            const form = document.getElementById('post-update-' + postId);
            form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
        }
        
        function editPost(postId) {
            const form = document.getElementById('edit-' + postId);
            form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
        }
        
        function editEmailCampaign(campaignId) {
            const form = document.getElementById('edit-email-' + campaignId);
            form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
        }
        
        function showAddRecipient(campaignId) {
            document.getElementById('modal-campaign-id').value = campaignId;
            document.getElementById('recipientModal').style.display = 'block';
        }
        
        function closeRecipientModal() {
            document.getElementById('recipientModal').style.display = 'none';
        }
        
        function viewPost(postId) {
            // Could implement a post detail modal here
            alert('Post details for ID: ' + postId);
        }
        
        function changeMonth(direction) {
            const url = new URL(window.location);
            let month = parseInt(url.searchParams.get('month') || '<?php echo $current_month; ?>');
            let year = parseInt(url.searchParams.get('year') || '<?php echo $current_year; ?>');
            
            month += direction;
            if (month > 12) {
                month = 1;
                year++;
            } else if (month < 1) {
                month = 12;
                year--;
            }
            
            url.searchParams.set('month', month.toString().padStart(2, '0'));
            url.searchParams.set('year', year.toString());
            window.location.href = url.toString();
        }
        
        // Navigation functions
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
        
        function toggleNotifications() {
            // Placeholder for notifications
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !menuToggle.contains(event.target) && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('recipientModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>