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
Security::requireDepartmentAccess('IT');

$database = new Database();
$db = $database->getConnection();

// Get project ID from URL
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$project_id) {
    header("Location: it.php");
    exit();
}

// Track page visit
try {
    require_once '../includes/ActivityLogger.php';
    $logger = new ActivityLogger($db);
    $logger->logPageVisit('Project Detail', "Viewed project detail page (ID: {$project_id})");
} catch (Exception $e) {
    error_log("Activity logging failed: " . $e->getMessage());
}

// Handle adding new comment
if ($_POST && isset($_POST['add_comment'])) {
    Security::checkCSRFToken();
    
    $comment = Security::sanitizeInput($_POST['comment']);
    $is_blocker = isset($_POST['is_blocker']) && $_POST['is_blocker'] === '1' ? 1 : 0;
    $parent_comment_id = !empty($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null;
    
    $query = "INSERT INTO project_comments (project_id, user_id, comment, is_blocker, parent_comment_id) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$project_id, $_SESSION['user_id'], $comment, $is_blocker, $parent_comment_id]);
    
    header("Location: project_detail.php?id=$project_id");
    exit();
}

// Handle progress update
if ($_POST && isset($_POST['update_progress'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('IT');
    
    $progress = (int)$_POST['progress'];
    $status = Security::sanitizeInput($_POST['status']);
    $description = Security::sanitizeInput($_POST['description']);
    
    $query = "UPDATE projects SET progress = ?, status = ?, description = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$progress, $status, $description, $project_id]);
    
    header("Location: project_detail.php?id=$project_id");
    exit();
}

// Handle team assignment updates
if ($_POST && isset($_POST['update_assignments'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('IT');
    
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
    
    header("Location: project_detail.php?id=$project_id");
    exit();
}

// Get project details
$query = "SELECT p.*, c.name as client_name, c.email as client_email 
          FROM projects p 
          LEFT JOIN clients c ON p.client_id = c.id 
          WHERE p.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: it.php");
    exit();
}

// Get project assignments
$query = "SELECT pa.*, u.username, u.email 
          FROM project_assignments pa 
          JOIN users u ON pa.user_id = u.id 
          WHERE pa.project_id = ? 
          ORDER BY u.username";
$stmt = $db->prepare($query);
$stmt->execute([$project_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all users for assignment dropdown
$query = "SELECT id, username, email, department FROM users WHERE department = 'IT' OR role = 'admin' ORDER BY username";
$stmt = $db->prepare($query);
$stmt->execute();
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get project comments (threaded)
$query = "SELECT pc.*, u.username 
          FROM project_comments pc 
          JOIN users u ON pc.user_id = u.id 
          WHERE pc.project_id = ? 
          ORDER BY pc.created_at ASC";
$stmt = $db->prepare($query);
$stmt->execute([$project_id]);
$all_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize comments into threads
$comments = [];
$replies = [];
foreach ($all_comments as $comment) {
    if ($comment['parent_comment_id'] == null) {
        $comments[] = $comment;
    } else {
        $replies[$comment['parent_comment_id']][] = $comment;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Security::escapeHTML($project['name']); ?> - Project Details</title>
    <link rel="stylesheet" href="../css/main.css">
</head>
<body>
    <div class="header">
        <div class="logo-section">
            <img src="../img/KConsultingLogo.png" alt="KConsulting" class="logo-img">
            <h1>Business Management System</h1>
        </div>
        <div class="user-info">
            <span>Welcome, <?php echo Security::escapeHTML($_SESSION['username']); ?></span>
            <span class="user-role">(<?php echo Security::escapeHTML($_SESSION['role']); ?>)</span>
            <a href="../auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="main-content" style="padding: 2rem; max-width: 1400px; margin: 0 auto;">
        <div class="project-header" style="margin-bottom: 2rem;">
            <a href="it.php" class="back-btn">← Back to IT Department</a>
            <h1 style="margin: 1rem 0; display: flex; align-items: center; gap: 1rem;">
                <?php echo Security::escapeHTML($project['name']); ?>
                <span class="status-badge status-<?php echo Security::escapeHTML($project['status']); ?>">
                    <?php echo Security::escapeHTML(ucfirst($project['status'])); ?>
                </span>
                <span class="priority-badge priority-<?php echo Security::escapeHTML($project['priority']); ?>">
                    <?php echo Security::escapeHTML(ucfirst($project['priority'])); ?>
                </span>
            </h1>
        </div>

        <div class="project-content" style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
            <!-- Project Details -->
            <div class="section">
                <div class="section-header">
                    📋 Project Information
                </div>
                <div class="section-content">
                    <div class="project-info">
                        <p><strong>Client:</strong> <?php echo Security::escapeHTML($project['client_name'] ?? 'No client assigned'); ?></p>
                        <p><strong>Category:</strong> <?php echo Security::escapeHTML($project['category']); ?></p>
                        <p><strong>Start Date:</strong> <?php echo Security::escapeHTML($project['start_date'] ?? 'Not set'); ?></p>
                        <p><strong>End Date:</strong> <?php echo Security::escapeHTML($project['end_date'] ?? 'Not set'); ?></p>
                        <p><strong>Progress:</strong> <?php echo (int)$project['progress']; ?>%</p>
                        
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo (int)$project['progress']; ?>%"></div>
                        </div>
                        
                        <p><strong>Description:</strong></p>
                        <div style="background: #f5f5f5; padding: 1rem; border-radius: 4px; margin-top: 0.5rem;">
                            <?php echo Security::escapeHTML($project['description'] ?? 'No description provided'); ?>
                        </div>
                        
                        <!-- Show Assigned Employees -->
                        <p><strong>Assigned Team:</strong></p>
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 0.5rem;">
                            <?php if (!empty($assignments)): ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                    <?php foreach ($assignments as $assignment): ?>
                                        <span style="background: #007bff; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.9rem;">
                                            <?php echo Security::escapeHTML($assignment['username']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #666; font-style: italic;">No team members assigned yet</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (Security::canWriteInDepartment($_SESSION['role'], $_SESSION['department'], 'IT')): ?>
                    <!-- Update Progress Form -->
                    <form method="POST" style="margin-top: 2rem; border-top: 1px solid #eee; padding-top: 2rem;">
                        <?php echo Security::getCSRFTokenField(); ?>
                        <h4>Update Project</h4>
                        
                        <div class="form-group">
                            <label>Description:</label>
                            <textarea name="description" rows="3"><?php echo Security::escapeHTML($project['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Progress (%):</label>
                            <input type="range" name="progress" min="0" max="100" value="<?php echo (int)$project['progress']; ?>" 
                                   oninput="this.nextElementSibling.value = this.value + '%'">
                            <output><?php echo (int)$project['progress']; ?>%</output>
                        </div>
                        
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status">
                                <option value="pending" <?php echo $project['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo $project['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="on_hold" <?php echo $project['status'] == 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="update_progress" class="btn">Update Project</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        
        <!-- Team Assignments Section - Full Width -->
        <div class="section" style="margin-top: 2rem;">
            <div class="section-header">
                👥 Team Assignments
            </div>
            <div class="section-content">
                <div class="team-members" style="margin-bottom: 2rem;">
                    <?php if (!empty($assignments)): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                            <?php foreach ($assignments as $assignment): ?>
                                <div class="team-member" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007bff;">
                                    <div>
                                        <strong style="font-size: 1.1rem;"><?php echo Security::escapeHTML($assignment['username']); ?></strong>
                                        <div style="color: #666; font-size: 0.9rem;">Role managed by HR department</div>
                                        <div style="color: #999; font-size: 0.8rem;"><?php echo Security::escapeHTML($assignment['email']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 3rem; color: #666; font-style: italic; background: #f8f9fa; border-radius: 8px;">
                            <h3>No team members assigned yet</h3>
                            <p>Use the form below to assign team members to this project.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (Security::canWriteInDepartment($_SESSION['role'], $_SESSION['department'], 'IT')): ?>
                <!-- Update Team Form -->
                <div style="background: white; padding: 2rem; border-radius: 8px; border: 1px solid #ddd;">
                    <form method="POST">
                        <?php echo Security::getCSRFTokenField(); ?>
                        <h4 style="margin-bottom: 1.5rem; color: #333;">Manage Team Assignments</h4>
                        
                        <div id="assignmentContainer">
                            <?php if (!empty($assignments)): ?>
                                <?php foreach ($assignments as $index => $assignment): ?>
                                    <div class="assignment-row" style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                        <select name="assigned_employees[]" style="flex: 2; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                                            <option value="">Select Employee...</option>
                                            <?php foreach ($all_users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>" 
                                                        <?php echo $user['id'] == $assignment['user_id'] ? 'selected' : ''; ?>>
                                                    <?php echo Security::escapeHTML($user['username'] . ' (' . $user['email'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span style="flex: 1; padding: 0.75rem; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; color: #666;">
                                            Role managed by HR department
                                        </span>
                                        <button type="button" onclick="removeAssignmentRow(this)" class="btn btn-small" style="background: #dc3545; color: white; padding: 0.5rem;">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="assignment-row" style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                    <select name="assigned_employees[]" style="flex: 2; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                                        <option value="">Select Employee...</option>
                                        <?php foreach ($all_users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>">
                                                <?php echo Security::escapeHTML($user['username'] . ' (' . $user['email'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="employee_roles[]" style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                                        <option value="Developer">Developer</option>
                                        <option value="Lead Developer">Lead Developer</option>
                                        <option value="UI/UX Designer">UI/UX Designer</option>
                                        <option value="Project Manager">Project Manager</option>
                                        <option value="QA Tester">QA Tester</option>
                                        <option value="DevOps">DevOps</option>
                                    </select>
                                    <button type="button" onclick="removeAssignmentRow(this)" class="btn btn-small" style="background: #dc3545; color: white; padding: 0.5rem;">Remove</button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                            <button type="button" onclick="addAssignmentRow()" class="btn btn-small" style="background: #28a745; color: white;">+ Add Team Member</button>
                            <button type="submit" name="update_assignments" class="btn">Update Team Assignments</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="section" style="margin-top: 2rem;">
            <div class="section-header">
                💬 Project Comments & Discussion
            </div>
            <div class="section-content">

                <!-- Comments List -->
                <div class="comments-list">
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment <?php echo $comment['is_blocker'] ? 'blocker-comment' : ''; ?>" 
                                 style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; 
                                        <?php echo $comment['is_blocker'] ? 'border-color: #dc3545; background-color: #fff5f5;' : 'background-color: white;'; ?>">
                                
                                <div class="comment-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <strong><?php echo Security::escapeHTML($comment['username']); ?></strong>
                                        <?php if ($comment['is_blocker']): ?>
                                            <span style="color: #dc3545; font-weight: bold;">🚨 BLOCKER</span>
                                        <?php endif; ?>
                                    </div>
                                    <small style="color: #666;">
                                        <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                    </small>
                                </div>
                                
                                <div class="comment-content" style="margin-bottom: 1rem;">
                                    <?php echo Security::escapeHTML($comment['comment']); ?>
                                </div>
                                
                                <!-- Reply Button -->
                                <button type="button" onclick="toggleReplyForm(<?php echo $comment['id']; ?>)" 
                                        class="btn btn-small" style="font-size: 0.8rem;">Reply</button>
                                
                                <!-- Reply Form (Hidden by default) -->
                                <div id="reply-form-<?php echo $comment['id']; ?>" style="display: none; margin-top: 1rem; padding: 1rem; background: #f0f0f0; border-radius: 4px;">
                                    <form method="POST">
                                        <?php echo Security::getCSRFTokenField(); ?>
                                        <input type="hidden" name="parent_comment_id" value="<?php echo $comment['id']; ?>">
                                        <div class="form-group">
                                            <textarea name="comment" required rows="2" placeholder="Reply to this comment..."></textarea>
                                        </div>
                                        <button type="submit" name="add_comment" class="btn btn-small">Post Reply</button>
                                        <button type="button" onclick="toggleReplyForm(<?php echo $comment['id']; ?>)" class="btn btn-small">Cancel</button>
                                    </form>
                                </div>
                                
                                <!-- Replies -->
                                <?php if (isset($replies[$comment['id']])): ?>
                                    <div class="replies" style="margin-top: 1rem; margin-left: 2rem; border-left: 3px solid #e0e0e0; padding-left: 1rem;">
                                        <?php foreach ($replies[$comment['id']] as $reply): ?>
                                            <div class="reply" style="background: #f8f9fa; padding: 0.75rem; margin-bottom: 0.5rem; border-radius: 4px;">
                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                                    <strong style="font-size: 0.9rem;"><?php echo Security::escapeHTML($reply['username']); ?></strong>
                                                    <small style="color: #666; font-size: 0.8rem;">
                                                        <?php echo date('M j, Y \a\t g:i A', strtotime($reply['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <div style="font-size: 0.9rem;">
                                                    <?php echo Security::escapeHTML($reply['comment']); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #666; font-style: italic; text-align: center; padding: 2rem;">
                            No comments yet. Be the first to share an update!
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Add Comment Form - Moved to Bottom -->
                <form method="POST" class="comment-form" style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-top: 2px solid #007bff;">
                    <?php echo Security::getCSRFTokenField(); ?>
                    <h4 style="margin-bottom: 1rem; color: #333;">💬 Add New Comment</h4>
                    <div class="form-group">
                        <label>Comment:</label>
                        <textarea name="comment" required rows="3" placeholder="Share updates, ask questions, or report blockers..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Priority Level:</label>
                        <select name="is_blocker" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="0">📝 Normal Comment</option>
                            <option value="1">🚨 Blocker (Requires Immediate Attention)</option>
                        </select>
                    </div>
                    <button type="submit" name="add_comment" class="btn" style="background: #007bff; color: white;">Post Comment</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/notification.js"></script>  
    <script>
        // User data for dynamic dropdown creation
        const allUsers = <?php echo json_encode($all_users); ?>;
        
        function addAssignmentRow() {
            const container = document.getElementById('assignmentContainer');
            const newRow = document.createElement('div');
            newRow.className = 'assignment-row';
            newRow.style.cssText = 'display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;';
            
            // Create employee select options
            let employeeOptions = '<option value="">Select Employee...</option>';
            allUsers.forEach(user => {
                employeeOptions += `<option value="${user.id}">${user.username} (${user.email})</option>`;
            });
            
            newRow.innerHTML = `
                <select name="assigned_employees[]" style="flex: 2; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                    ${employeeOptions}
                </select>
                <select name="employee_roles[]" style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="Developer">Developer</option>
                    <option value="Lead Developer">Lead Developer</option>
                    <option value="UI/UX Designer">UI/UX Designer</option>
                    <option value="Project Manager">Project Manager</option>
                    <option value="QA Tester">QA Tester</option>
                    <option value="DevOps">DevOps</option>
                </select>
                <button type="button" onclick="removeAssignmentRow(this)" class="btn btn-small" style="background: #dc3545; color: white; padding: 0.5rem;">Remove</button>
            `;
            container.appendChild(newRow);
        }
        
        function removeAssignmentRow(button) {
            const row = button.parentElement;
            row.remove();
        }

        function toggleReplyForm(commentId) {
            const form = document.getElementById('reply-form-' + commentId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>