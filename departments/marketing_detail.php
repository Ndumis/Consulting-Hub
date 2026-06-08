<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';

// Check department access
Security::requireDepartmentAccess('Marketing');

$database = new Database();
$db = $database->getConnection();

// Get item type and ID from URL
$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id || !in_array($type, ['campaign', 'social_post', 'email_campaign'])) {
    header("Location: marketing.php");
    exit();
}

// Handle employee assignment updates
if ($_POST && isset($_POST['update_assignments'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Marketing');
    
    $assigned_employees = $_POST['assigned_employees'] ?? [];
    
    // Map table names based on type
    $assignment_table = '';
    $foreign_key = '';
    switch ($type) {
        case 'campaign':
            $assignment_table = 'marketing_campaign_assignments';
            $foreign_key = 'campaign_id';
            break;
        case 'social_post':
            $assignment_table = 'social_post_assignments';
            $foreign_key = 'post_id';
            break;
        case 'email_campaign':
            $assignment_table = 'email_campaign_assignments';
            $foreign_key = 'email_campaign_id';
            break;
    }
    
    // Remove existing assignments
    $query = "DELETE FROM $assignment_table WHERE $foreign_key = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    
    // Add new assignments (roles now managed by HR department)
    if (!empty($assigned_employees)) {
        foreach ($assigned_employees as $user_id) {
            if (!empty($user_id)) {
                $query = "INSERT INTO $assignment_table ($foreign_key, user_id) VALUES (?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$id, $user_id]);
            }
        }
    }
    
    header("Location: marketing_detail.php?type=$type&id=$id");
    exit();
}

// Handle item updates
if ($_POST && isset($_POST['update_item'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Marketing');
    
    switch ($type) {
        case 'campaign':
            $campaign_name = Security::sanitizeInput($_POST['campaign_name']);
            $campaign_type = Security::sanitizeInput($_POST['campaign_type']);
            $budget = floatval($_POST['budget']);
            $start_date = Security::sanitizeInput($_POST['start_date']);
            $end_date = Security::sanitizeInput($_POST['end_date']);
            $status = Security::sanitizeInput($_POST['status']);
            $description = Security::sanitizeInput($_POST['description']);
            
            $query = "UPDATE marketing_campaigns SET campaign_name = ?, campaign_type = ?, budget = ?, start_date = ?, end_date = ?, status = ?, description = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$campaign_name, $campaign_type, $budget, $start_date, $end_date, $status, $description, $id]);
            break;
            
        case 'social_post':
            $platform = Security::sanitizeInput($_POST['platform']);
            $content = Security::sanitizeInput($_POST['content']);
            $scheduled_date = Security::sanitizeInput($_POST['scheduled_date']);
            $status = Security::sanitizeInput($_POST['status']);
            $hashtags = Security::sanitizeInput($_POST['hashtags']);
            
            $query = "UPDATE social_media_posts SET platform = ?, content = ?, scheduled_date = ?, status = ?, hashtags = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$platform, $content, $scheduled_date, $status, $hashtags, $id]);
            break;
            
        case 'email_campaign':
            $campaign_name = Security::sanitizeInput($_POST['campaign_name']);
            $subject = Security::sanitizeInput($_POST['subject']);
            $content = Security::sanitizeInput($_POST['content']);
            $send_date = Security::sanitizeInput($_POST['send_date']);
            $status = Security::sanitizeInput($_POST['status']);
            
            $query = "UPDATE email_campaigns SET campaign_name = ?, subject = ?, content = ?, send_date = ?, status = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$campaign_name, $subject, $content, $send_date, $status, $id]);
            break;
    }
    
    header("Location: marketing_detail.php?type=$type&id=$id");
    exit();
}

// Get item details based on type
$item = null;
$assignments = [];

switch ($type) {
    case 'campaign':
        $query = "SELECT mc.*, c.name as client_name, c.email as client_email 
                  FROM marketing_campaigns mc 
                  LEFT JOIN clients c ON mc.client_id = c.id 
                  WHERE mc.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get assignments
        $query = "SELECT mca.*, u.username, u.email 
                  FROM marketing_campaign_assignments mca 
                  JOIN users u ON mca.user_id = u.id 
                  WHERE mca.campaign_id = ? 
                  ORDER BY mca.role, u.username";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'social_post':
        $query = "SELECT smp.*, c.name as client_name, mc.campaign_name 
                  FROM social_media_posts smp 
                  LEFT JOIN clients c ON smp.client_id = c.id 
                  LEFT JOIN marketing_campaigns mc ON smp.campaign_id = mc.id 
                  WHERE smp.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get assignments
        $query = "SELECT spa.*, u.username, u.email 
                  FROM social_post_assignments spa 
                  JOIN users u ON spa.user_id = u.id 
                  WHERE spa.post_id = ? 
                  ORDER BY spa.role, u.username";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'email_campaign':
        $query = "SELECT ec.*, c.name as client_name 
                  FROM email_campaigns ec 
                  LEFT JOIN clients c ON ec.client_id = c.id 
                  WHERE ec.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get assignments
        $query = "SELECT eca.*, u.username, u.email 
                  FROM email_campaign_assignments eca 
                  JOIN users u ON eca.user_id = u.id 
                  WHERE eca.email_campaign_id = ? 
                  ORDER BY eca.role, u.username";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
}

if (!$item) {
    header("Location: marketing.php");
    exit();
}

// Get all marketing users for assignment dropdown
$query = "SELECT id, username, email FROM users WHERE department = 'Marketing' OR role = 'admin' ORDER BY username";
$stmt = $db->prepare($query);
$stmt->execute();
$marketing_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get type-specific title and icon
$type_info = [
    'campaign' => ['title' => 'Marketing Campaign', 'icon' => '📢'],
    'social_post' => ['title' => 'Social Media Post', 'icon' => '📱'],
    'email_campaign' => ['title' => 'Email Campaign', 'icon' => '📧']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $type_info[$type]['title']; ?> Details - <?php echo Security::escapeHTML($item[$type === 'social_post' ? 'platform' : 'campaign_name'] ?? 'Item'); ?></title>
    <link rel="icon" type="image/png" href="../img/KConsultingLogo1.png">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .detail-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .detail-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        .main-content-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sidebar-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
        }
        .assignment-member {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            margin: 0.5rem 0;
            border-radius: 6px;
            border-left: 4px solid #007bff;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-draft { background: #f8d7da; color: #721c24; }
        .status-scheduled { background: #e2e3e5; color: #383d41; }
        .status-sent { background: #d1ecf1; color: #0c5460; }
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            margin-bottom: 1rem;
            display: inline-block;
        }
        .assignment-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: center;
        }
        @media (max-width: 768px) {
            .detail-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <a href="marketing.php" class="back-btn">← Back to Marketing Department</a>
        
        <div class="detail-header">
            <h1><?php echo $type_info[$type]['icon']; ?> <?php echo $type_info[$type]['title']; ?> Details</h1>
            <h2><?php echo Security::escapeHTML($item[$type === 'social_post' ? 'platform' : 'campaign_name'] ?? 'Unnamed Item'); ?></h2>
            <?php if ($item['status'] ?? null): ?>
                <span class="status-badge status-<?php echo Security::escapeHTML($item['status']); ?>">
                    <?php echo Security::escapeHTML(ucfirst($item['status'])); ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="detail-content">
            <!-- Main Content -->
            <div class="main-content-section">
                <h3>📝 Item Information</h3>
                
                <?php if ($type === 'campaign'): ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                        <div>
                            <strong>Client:</strong><br>
                            <?php echo Security::escapeHTML($item['client_name'] ?? 'No client assigned'); ?>
                        </div>
                        <div>
                            <strong>Campaign Type:</strong><br>
                            <?php echo Security::escapeHTML($item['campaign_type'] ?? 'Not specified'); ?>
                        </div>
                        <div>
                            <strong>Budget:</strong><br>
                            R <?php echo number_format($item['budget'] ?? 0, 2); ?>
                        </div>
                        <div>
                            <strong>Duration:</strong><br>
                            <?php echo Security::escapeHTML($item['start_date'] ?? 'TBD'); ?> to <?php echo Security::escapeHTML($item['end_date'] ?? 'TBD'); ?>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 2rem;">
                        <strong>Description:</strong>
                        <div style="background: #f5f5f5; padding: 1rem; border-radius: 4px; margin-top: 0.5rem;">
                            <?php echo Security::escapeHTML($item['description'] ?? 'No description provided'); ?>
                        </div>
                    </div>

                    <?php if ($_SESSION['role'] === 'admin' || ($_SESSION['role'] === 'manager' && $_SESSION['department'] === 'Marketing')): ?>
                    <!-- Update Campaign Form -->
                    <form method="POST" style="border-top: 1px solid #eee; padding-top: 2rem;">
                        <?php echo Security::getCSRFTokenField(); ?>
                        <h4>Update Campaign</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label>Campaign Name:</label>
                                <input type="text" name="campaign_name" value="<?php echo Security::escapeHTML($item['campaign_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Campaign Type:</label>
                                <select name="campaign_type">
                                    <option value="Social Media" <?php echo $item['campaign_type'] == 'Social Media' ? 'selected' : ''; ?>>Social Media</option>
                                    <option value="Email Marketing" <?php echo $item['campaign_type'] == 'Email Marketing' ? 'selected' : ''; ?>>Email Marketing</option>
                                    <option value="Content Marketing" <?php echo $item['campaign_type'] == 'Content Marketing' ? 'selected' : ''; ?>>Content Marketing</option>
                                    <option value="PPC" <?php echo $item['campaign_type'] == 'PPC' ? 'selected' : ''; ?>>PPC</option>
                                    <option value="SEO" <?php echo $item['campaign_type'] == 'SEO' ? 'selected' : ''; ?>>SEO</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label>Budget:</label>
                                <input type="number" name="budget" step="0.01" value="<?php echo $item['budget']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Start Date:</label>
                                <input type="date" name="start_date" value="<?php echo $item['start_date']; ?>">
                            </div>
                            <div class="form-group">
                                <label>End Date:</label>
                                <input type="date" name="end_date" value="<?php echo $item['end_date']; ?>">
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label>Status:</label>
                                <select name="status">
                                    <option value="pending" <?php echo $item['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="active" <?php echo $item['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="completed" <?php echo $item['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="paused" <?php echo $item['status'] == 'paused' ? 'selected' : ''; ?>>Paused</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description:</label>
                            <textarea name="description" rows="4"><?php echo Security::escapeHTML($item['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_item" class="btn">Update Campaign</button>
                    </form>
                    <?php endif; ?>

                <?php elseif ($type === 'social_post'): ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                        <div>
                            <strong>Platform:</strong><br>
                            <?php echo Security::escapeHTML($item['platform'] ?? 'Not specified'); ?>
                        </div>
                        <div>
                            <strong>Scheduled Date:</strong><br>
                            <?php echo date('M j, Y \a\t g:i A', strtotime($item['scheduled_date'])); ?>
                        </div>
                        <div>
                            <strong>Campaign:</strong><br>
                            <?php echo Security::escapeHTML($item['campaign_name'] ?? 'No campaign'); ?>
                        </div>
                        <div>
                            <strong>Client:</strong><br>
                            <?php echo Security::escapeHTML($item['client_name'] ?? 'No client'); ?>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 2rem;">
                        <strong>Content:</strong>
                        <div style="background: #f5f5f5; padding: 1rem; border-radius: 4px; margin-top: 0.5rem; white-space: pre-wrap;">
                            <?php echo Security::escapeHTML($item['content'] ?? 'No content'); ?>
                        </div>
                    </div>

                    <?php if ($_SESSION['role'] === 'admin' || ($_SESSION['role'] === 'manager' && $_SESSION['department'] === 'Marketing')): ?>
                    <!-- Update Social Post Form -->
                    <form method="POST" style="border-top: 1px solid #eee; padding-top: 2rem;">
                        <?php echo Security::getCSRFTokenField(); ?>
                        <h4>Update Social Media Post</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label>Platform:</label>
                                <select name="platform">
                                    <option value="Facebook" <?php echo $item['platform'] == 'Facebook' ? 'selected' : ''; ?>>Facebook</option>
                                    <option value="Instagram" <?php echo $item['platform'] == 'Instagram' ? 'selected' : ''; ?>>Instagram</option>
                                    <option value="Twitter" <?php echo $item['platform'] == 'Twitter' ? 'selected' : ''; ?>>Twitter</option>
                                    <option value="LinkedIn" <?php echo $item['platform'] == 'LinkedIn' ? 'selected' : ''; ?>>LinkedIn</option>
                                    <option value="TikTok" <?php echo $item['platform'] == 'TikTok' ? 'selected' : ''; ?>>TikTok</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Scheduled Date:</label>
                                <input type="datetime-local" name="scheduled_date" value="<?php echo date('Y-m-d\TH:i', strtotime($item['scheduled_date'])); ?>">
                            </div>
                            <div class="form-group">
                                <label>Status:</label>
                                <select name="status">
                                    <option value="draft" <?php echo $item['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="scheduled" <?php echo $item['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="published" <?php echo $item['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Content:</label>
                            <textarea name="content" rows="4" required><?php echo Security::escapeHTML($item['content']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Hashtags:</label>
                            <input type="text" name="hashtags" value="<?php echo Security::escapeHTML($item['hashtags'] ?? ''); ?>" placeholder="#marketing #social #business">
                        </div>
                        
                        <button type="submit" name="update_item" class="btn">Update Post</button>
                    </form>
                    <?php endif; ?>

                <?php elseif ($type === 'email_campaign'): ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                        <div>
                            <strong>Subject:</strong><br>
                            <?php echo Security::escapeHTML($item['subject'] ?? 'No subject'); ?>
                        </div>
                        <div>
                            <strong>Send Date:</strong><br>
                            <?php echo $item['send_date'] ? date('M j, Y \a\t g:i A', strtotime($item['send_date'])) : 'Not scheduled'; ?>
                        </div>
                        <div>
                            <strong>Recipients:</strong><br>
                            <?php echo (int)($item['total_recipients'] ?? 0); ?> recipients
                        </div>
                        <div>
                            <strong>Client:</strong><br>
                            <?php echo Security::escapeHTML($item['client_name'] ?? 'No client'); ?>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 2rem;">
                        <strong>Email Content:</strong>
                        <div style="background: #f5f5f5; padding: 1rem; border-radius: 4px; margin-top: 0.5rem; white-space: pre-wrap;">
                            <?php echo Security::escapeHTML($item['content'] ?? 'No content'); ?>
                        </div>
                    </div>

                    <?php if ($_SESSION['role'] === 'admin' || ($_SESSION['role'] === 'manager' && $_SESSION['department'] === 'Marketing')): ?>
                    <!-- Update Email Campaign Form -->
                    <form method="POST" style="border-top: 1px solid #eee; padding-top: 2rem;">
                        <?php echo Security::getCSRFTokenField(); ?>
                        <h4>Update Email Campaign</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label>Campaign Name:</label>
                                <input type="text" name="campaign_name" value="<?php echo Security::escapeHTML($item['campaign_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Status:</label>
                                <select name="status">
                                    <option value="draft" <?php echo $item['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="scheduled" <?php echo $item['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="sent" <?php echo $item['status'] == 'sent' ? 'selected' : ''; ?>>Sent</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label>Subject:</label>
                                <input type="text" name="subject" value="<?php echo Security::escapeHTML($item['subject']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Send Date:</label>
                                <input type="datetime-local" name="send_date" value="<?php echo $item['send_date'] ? date('Y-m-d\TH:i', strtotime($item['send_date'])) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Content:</label>
                            <textarea name="content" rows="6" required><?php echo Security::escapeHTML($item['content']); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_item" class="btn">Update Email Campaign</button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Team Assignment Sidebar -->
            <div class="sidebar-section">
                <h3>👥 Team Assignments</h3>
                
                <div class="team-members" style="margin-bottom: 2rem;">
                    <?php if (!empty($assignments)): ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="assignment-member">
                                <div>
                                    <strong><?php echo Security::escapeHTML($assignment['username']); ?></strong>
                                    <small style="display: block; color: #666;">
                                        <?php echo Security::escapeHTML($assignment['role']); ?>
                                    </small>
                                </div>
                                <small><?php echo Security::escapeHTML($assignment['email']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #666; font-style: italic; text-align: center; padding: 2rem;">
                            No team members assigned yet.
                        </p>
                    <?php endif; ?>
                </div>

                <?php if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Manager'): ?>
                <!-- Update Team Form -->
                <form method="POST" style="border-top: 1px solid #eee; padding-top: 2rem;">
                    <?php echo Security::getCSRFTokenField(); ?>
                    <h4>Manage Team Assignments</h4>
                    
                    <div id="assignmentContainer">
                        <?php if (!empty($assignments)): ?>
                            <?php foreach ($assignments as $index => $assignment): ?>
                                <div class="assignment-row">
                                    <select name="assigned_employees[]" style="flex: 2;">
                                        <option value="">Select Employee...</option>
                                        <?php foreach ($marketing_users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>" 
                                                    <?php echo $user['id'] == $assignment['user_id'] ? 'selected' : ''; ?>>
                                                <?php echo Security::escapeHTML($user['username'] . ' (' . $user['email'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="employee_roles[]" style="flex: 1;">
                                        <option value="Team Member" <?php echo $assignment['role'] == 'Team Member' ? 'selected' : ''; ?>>Team Member</option>
                                        <option value="Content Creator" <?php echo $assignment['role'] == 'Content Creator' ? 'selected' : ''; ?>>Content Creator</option>
                                        <option value="Designer" <?php echo $assignment['role'] == 'Designer' ? 'selected' : ''; ?>>Designer</option>
                                        <option value="Manager" <?php echo $assignment['role'] == 'Manager' ? 'selected' : ''; ?>>Manager</option>
                                        <option value="Analyst" <?php echo $assignment['role'] == 'Analyst' ? 'selected' : ''; ?>>Analyst</option>
                                        <option value="Coordinator" <?php echo $assignment['role'] == 'Coordinator' ? 'selected' : ''; ?>>Coordinator</option>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="assignment-row">
                                <select name="assigned_employees[]" style="flex: 2;">
                                    <option value="">Select Employee...</option>
                                    <?php foreach ($marketing_users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo Security::escapeHTML($user['username'] . ' (' . $user['email'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="employee_roles[]" style="flex: 1;">
                                    <option value="Team Member">Team Member</option>
                                    <option value="Content Creator">Content Creator</option>
                                    <option value="Designer">Designer</option>
                                    <option value="Manager">Manager</option>
                                    <option value="Analyst">Analyst</option>
                                    <option value="Coordinator">Coordinator</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <button type="button" onclick="addAssignmentRow()" class="btn btn-small">+ Add Team Member</button>
                        <button type="submit" name="update_assignments" class="btn">Update Team</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../js/notification.js"></script>  
    <script>
        function addAssignmentRow() {
            const container = document.getElementById('assignmentContainer');
            const newRow = document.createElement('div');
            newRow.className = 'assignment-row';
            newRow.innerHTML = `
                <select name="assigned_employees[]" style="flex: 2;">
                    <option value="">Select Employee...</option>
                    <?php foreach ($marketing_users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo Security::escapeHTML($user['username'] . ' (' . $user['email'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="employee_roles[]" style="flex: 1;">
                    <option value="Team Member">Team Member</option>
                    <option value="Content Creator">Content Creator</option>
                    <option value="Designer">Designer</option>
                    <option value="Manager">Manager</option>
                    <option value="Analyst">Analyst</option>
                    <option value="Coordinator">Coordinator</option>
                </select>
            `;
            container.appendChild(newRow);
        }
    </script>
</body>
</html>
