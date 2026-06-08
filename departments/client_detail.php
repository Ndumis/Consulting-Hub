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
Security::requireDepartmentAccess('Clients');

$database = new Database();
$db = $database->getConnection();

// Get client ID from URL
$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit_mode = isset($_GET['edit']) && $_GET['edit'] == '1';

if (!$client_id) {
    header("Location: clients.php");
    exit();
}

// Handle client update
if ($_POST && isset($_POST['update_client'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Clients');
    
    $name = Security::sanitizeInput($_POST['name']);
    $email = Security::sanitizeInput($_POST['email']);
    $phone = Security::sanitizeInput($_POST['phone']);
    $company = Security::sanitizeInput($_POST['company']);
    $status = Security::sanitizeInput($_POST['status']);
    
    $query = "UPDATE clients SET name = ?, email = ?, phone = ?, company = ?, status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$name, $email, $phone, $company, $status, $client_id])) {
        // Log activity
        $activity_query = "INSERT INTO client_activities (client_id, activity_type, description, user_id) 
                          VALUES (?, ?, ?, ?)";
        $activity_stmt = $db->prepare($activity_query);
        $activity_stmt->execute([$client_id, 'client_updated', "Client information updated", $_SESSION['user_id']]);
        
        $success_message = "Client updated successfully!";
        $edit_mode = false; // Switch back to view mode after successful update
    } else {
        $error_message = "Error updating client.";
    }
}

// Get client details
$client_query = "SELECT * FROM clients WHERE id = ?";
$client_stmt = $db->prepare($client_query);
$client_stmt->execute([$client_id]);
$client = $client_stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    header("Location: clients.php");
    exit();
}

// Get client communications
$communications_query = "SELECT cc.*, u.username as assigned_to_name 
                        FROM client_contacts cc 
                        LEFT JOIN users u ON cc.assigned_to = u.id 
                        WHERE cc.client_id = ? 
                        ORDER BY cc.created_at DESC";
$communications_stmt = $db->prepare($communications_query);
$communications_stmt->execute([$client_id]);
$communications = $communications_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get client meetings
$meetings_query = "SELECT cm.*, u.username as created_by_name 
                  FROM client_meetings cm 
                  LEFT JOIN users u ON cm.created_by = u.id 
                  WHERE cm.client_id = ? 
                  ORDER BY cm.meeting_date DESC";
$meetings_stmt = $db->prepare($meetings_query);
$meetings_stmt->execute([$client_id]);
$meetings = $meetings_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get client documents (with error handling for missing table)
try {
    $documents_query = "SELECT cd.*, u.username as uploaded_by_name 
                       FROM client_documents cd 
                       LEFT JOIN users u ON cd.uploaded_by = u.id 
                       WHERE cd.client_id = ? 
                       ORDER BY cd.uploaded_at DESC";
    $documents_stmt = $db->prepare($documents_query);
    $documents_stmt->execute([$client_id]);
    $documents = $documents_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table doesn't exist, use empty array
    $documents = [];
}

// Get client activities (with error handling for missing table)
try {
    $activities_query = "SELECT ca.*, u.username as user_name 
                        FROM client_activities ca 
                        LEFT JOIN users u ON ca.user_id = u.id 
                        WHERE ca.client_id = ? 
                        ORDER BY ca.created_at DESC 
                        LIMIT 20";
    $activities_stmt = $db->prepare($activities_query);
    $activities_stmt->execute([$client_id]);
    $activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table doesn't exist, use empty array
    $activities = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Details - <?php echo Security::escapeHTML($client['name']); ?></title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .client-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .client-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .client-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .client-header-content {
            position: relative;
            z-index: 1;
        }
        
        .client-header-actions {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .client-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .client-header .company-name {
            margin: 0.75rem 0 0 0;
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .client-status-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .client-edit-btn {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .client-edit-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            color: white;
        }
        
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column !important;
                gap: 1rem;
                padding: 1rem !important;
            }
            
            .main-nav {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.5rem !important;
            }
            
            .main-nav a {
                padding: 0.25rem 0.5rem !important;
                font-size: 0.9rem;
            }
            
            .client-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
                padding: 2rem;
                margin-top: 1rem;
            }
            
            .client-header h1 {
                font-size: 2rem;
            }
            
            .client-header-actions {
                align-self: stretch;
                justify-content: space-between;
            }
        }
        .client-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .info-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
        }
        .info-card h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        .detail-value {
            color: #333;
        }
        .tab-content-detail {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
            display: none;
        }
        
        .tab-content-detail.active {
            display: block;
        }
        
        .tab-nav {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 0.5rem;
        }
        
        .tab-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 0.75rem 1.5rem;
            border-radius: 6px 6px 0 0;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
            border-bottom: none;
        }
        
        .tab-btn:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .tab-btn.active {
            background: white;
            color: #333;
            border-color: #dee2e6;
            border-bottom: 2px solid white;
            margin-bottom: -2px;
            font-weight: 600;
        }
        .edit-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .communication-item, .meeting-item, .document-item, .activity-item {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fafafa;
        }
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .item-title {
            font-weight: bold;
            color: #333;
        }
        .item-date {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div style="margin-bottom: 1rem;">
            <a href="clients.php" class="btn btn-small">← Back to Clients</a>
        </div>

        <!-- Client Header -->
        <div class="client-header">
            <div class="client-header-content">
                <h1><?php echo Security::escapeHTML($client['name']); ?></h1>
                <p class="company-name">
                    🏢 <?php echo Security::escapeHTML($client['company']); ?>
                </p>
            </div>
            <div class="client-header-actions">
                <span class="client-status-badge">
                    <?php echo ucfirst($client['status']); ?>
                </span>
                <?php if (!$edit_mode && Security::canWriteInDepartment($_SESSION['role'], $_SESSION['department'], 'Clients')): ?>
                    <a href="?id=<?php echo $client_id; ?>&edit=1" class="client-edit-btn">✏️ Edit Client</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($edit_mode && Security::canWriteInDepartment($_SESSION['role'], $_SESSION['department'], 'Clients')): ?>
            <!-- Edit Form -->
            <div class="edit-form">
                <h3>Edit Client Information</h3>
                <form method="POST" class="form-grid">
                    <?php echo Security::getCSRFTokenField(); ?>
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="name" value="<?php echo Security::escapeHTML($client['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" value="<?php echo Security::escapeHTML($client['email']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone:</label>
                        <input type="tel" name="phone" value="<?php echo Security::escapeHTML($client['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Company:</label>
                        <input type="text" name="company" value="<?php echo Security::escapeHTML($client['company']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status">
                            <option value="prospect" <?php echo $client['status'] === 'prospect' ? 'selected' : ''; ?>>Prospect</option>
                            <option value="active" <?php echo $client['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $client['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="closed" <?php echo $client['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" name="update_client" class="btn">Update Client</button>
                        <a href="?id=<?php echo $client_id; ?>" class="btn btn-small" style="background: #6c757d; color: white; margin-left: 1rem;">View Details</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- View Mode -->
            <div class="client-info-grid">
                <!-- Basic Information -->
                <div class="info-card">
                    <h3>📋 Basic Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value"><?php echo Security::escapeHTML($client['name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Company:</span>
                        <span class="detail-value"><?php echo Security::escapeHTML($client['company']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">
                            <?php if ($client['email']): ?>
                                <a href="mailto:<?php echo Security::escapeHTML($client['email']); ?>">
                                    <?php echo Security::escapeHTML($client['email']); ?>
                                </a>
                            <?php else: ?>
                                Not provided
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value">
                            <?php if ($client['phone']): ?>
                                <a href="tel:<?php echo Security::escapeHTML($client['phone']); ?>">
                                    <?php echo Security::escapeHTML($client['phone']); ?>
                                </a>
                            <?php else: ?>
                                Not provided
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="status-badge status-<?php echo $client['status']; ?>">
                                <?php echo ucfirst($client['status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Client Since:</span>
                        <span class="detail-value"><?php echo Utils::formatDate($client['created_at']); ?></span>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="info-card">
                    <h3>📊 Quick Stats</h3>
                    <div class="detail-row">
                        <span class="detail-label">Total Communications:</span>
                        <span class="detail-value"><?php echo count($communications); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Open Communications:</span>
                        <span class="detail-value">
                            <?php echo count(array_filter($communications, function($c) { return $c['status'] === 'open'; })); ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Meetings:</span>
                        <span class="detail-value"><?php echo count($meetings); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Upcoming Meetings:</span>
                        <span class="detail-value">
                            <?php 
                            $upcoming = array_filter($meetings, function($m) { 
                                return $m['status'] === 'scheduled' && strtotime($m['meeting_date']) > time(); 
                            });
                            echo count($upcoming);
                            ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Documents:</span>
                        <span class="detail-value"><?php echo count($documents); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Activity:</span>
                        <span class="detail-value">
                            <?php if (!empty($activities)): ?>
                                <?php echo Utils::formatDate($activities[0]['created_at']); ?>
                            <?php else: ?>
                                No activity
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-nav">
                <button class="tab-btn active" onclick="showDetailTab('communications-detail')">💬 Communications</button>
                <button class="tab-btn" onclick="showDetailTab('meetings-detail')">📅 Meetings</button>
                <button class="tab-btn" onclick="showDetailTab('documents-detail')">📄 Documents</button>
                <button class="tab-btn" onclick="showDetailTab('activity-detail')">📈 Activity Timeline</button>
            </div>

            <!-- Communications Tab -->
            <div id="communications-detail" class="tab-content-detail active">
                <h3>Recent Communications</h3>
                <?php if (!empty($communications)): ?>
                    <?php foreach ($communications as $comm): ?>
                        <div class="communication-item">
                            <div class="item-header">
                                <span class="item-title"><?php echo Security::escapeHTML($comm['subject']); ?></span>
                                <span class="item-date"><?php echo Utils::formatDate($comm['created_at']); ?></span>
                            </div>
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Type:</strong> <?php echo ucfirst($comm['contact_type']); ?> |
                                <strong>Priority:</strong> <span class="priority-badge priority-<?php echo $comm['priority']; ?>"><?php echo ucfirst($comm['priority']); ?></span> |
                                <strong>Status:</strong> <span class="status-badge status-<?php echo $comm['status']; ?>"><?php echo ucfirst($comm['status']); ?></span>
                            </div>
                            <?php if ($comm['contact_person']): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>Contact Person:</strong> <?php echo Security::escapeHTML($comm['contact_person']); ?>
                                </div>
                            <?php endif; ?>
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Message:</strong> <?php echo nl2br(Security::escapeHTML($comm['message'])); ?>
                            </div>
                            <?php if ($comm['assigned_to_name']): ?>
                                <div style="font-size: 0.9rem; color: #666;">
                                    Assigned to: <?php echo Security::escapeHTML($comm['assigned_to_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; font-style: italic; padding: 2rem;">
                        No communications recorded for this client.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Meetings Tab -->
            <div id="meetings-detail" class="tab-content-detail">
                <h3>Client Meetings</h3>
                <?php if (!empty($meetings)): ?>
                    <?php foreach ($meetings as $meeting): ?>
                        <div class="meeting-item">
                            <div class="item-header">
                                <span class="item-title"><?php echo Security::escapeHTML($meeting['meeting_title']); ?></span>
                                <span class="item-date"><?php echo Utils::formatDate($meeting['meeting_date']); ?></span>
                            </div>
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Status:</strong> <span class="status-badge status-<?php echo $meeting['status']; ?>"><?php echo ucfirst($meeting['status']); ?></span> |
                                <strong>Duration:</strong> <?php echo $meeting['duration']; ?> minutes
                            </div>
                            <?php if ($meeting['location']): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>Location:</strong> <?php echo Security::escapeHTML($meeting['location']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($meeting['attendees']): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>Attendees:</strong> <?php echo Security::escapeHTML($meeting['attendees']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($meeting['agenda']): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>Agenda:</strong> <?php echo nl2br(Security::escapeHTML($meeting['agenda'])); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($meeting['notes']): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>Notes:</strong> <?php echo nl2br(Security::escapeHTML($meeting['notes'])); ?>
                                </div>
                            <?php endif; ?>
                            <div style="font-size: 0.9rem; color: #666;">
                                Created by: <?php echo Security::escapeHTML($meeting['created_by_name']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; font-style: italic; padding: 2rem;">
                        No meetings scheduled for this client.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Documents Tab -->
            <div id="documents-detail" class="tab-content-detail">
                <h3>Client Documents</h3>
                <?php if (!empty($documents)): ?>
                    <?php foreach ($documents as $doc): ?>
                        <div class="document-item">
                            <div class="item-header">
                                <span class="item-title"><?php echo Security::escapeHTML($doc['document_name']); ?></span>
                                <span class="item-date"><?php echo Utils::formatDate($doc['uploaded_at']); ?></span>
                            </div>
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Type:</strong> <?php echo ucfirst($doc['document_type']); ?>
                            </div>
                            <?php if ($doc['description']): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>Description:</strong> <?php echo Security::escapeHTML($doc['description']); ?>
                                </div>
                            <?php endif; ?>
                            <div style="font-size: 0.9rem; color: #666;">
                                Uploaded by: <?php echo Security::escapeHTML($doc['uploaded_by_name']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; font-style: italic; padding: 2rem;">
                        No documents uploaded for this client.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Activity Timeline Tab -->
            <div id="activity-detail" class="tab-content-detail">
                <h3>Activity Timeline</h3>
                <?php if (!empty($activities)): ?>
                    <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="item-header">
                                <span class="item-title"><?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?></span>
                                <span class="item-date"><?php echo Utils::formatDate($activity['created_at']); ?></span>
                            </div>
                            <div style="margin-bottom: 0.5rem;">
                                <?php echo Security::escapeHTML($activity['description']); ?>
                            </div>
                            <div style="font-size: 0.9rem; color: #666;">
                                by <?php echo Security::escapeHTML($activity['user_name']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; font-style: italic; padding: 2rem;">
                        No activity recorded for this client.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="../js/notification.js"></script>  
    <script>
        function showDetailTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content-detail');
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