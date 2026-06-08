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

// Get user info
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];
$email = $_SESSION['email'];

// Track page visit
try {
    require_once '../includes/ActivityLogger.php';
    $logger = new ActivityLogger($db);
    $logger->logPageVisit('Clients Management', 'Viewed clients management page');
} catch (Exception $e) {
    error_log("Activity logging failed: " . $e->getMessage());
}

// Handle new client creation
if ($_POST && isset($_POST['create_client'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Clients');
    
    $name = Security::sanitizeInput($_POST['name']);
    $email = Security::sanitizeInput($_POST['email']);
    $phone = Security::sanitizeInput($_POST['phone']);
    $company = Security::sanitizeInput($_POST['company']);
    $address = Security::sanitizeInput($_POST['address']);
    $status = Security::sanitizeInput($_POST['status']);
    
    $query = "INSERT INTO clients (name, email, phone, company, address, status) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$name, $email, $phone, $company, $address, $status]);
    $client_id = $db->lastInsertId();
    
    // Log activity
    Utils::logActivity('Clients', 'client_created', "Created new client: {$name} ({$company})", 'client', $client_id);
    
    echo "<script>
        alert('Client created successfully!');
        window.location.href = 'clients.php';
    </script>";
    exit();
}

// Handle client updates
if ($_POST && isset($_POST['update_client'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Clients');
    
    $client_id = (int)$_POST['client_id'];
    $name = Security::sanitizeInput($_POST['name']);
    $email = Security::sanitizeInput($_POST['email']);
    $phone = Security::sanitizeInput($_POST['phone']);
    $company = Security::sanitizeInput($_POST['company']);
    $status = Security::sanitizeInput($_POST['status']);
    
    $query = "UPDATE clients SET name = ?, email = ?, phone = ?, company = ?, status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$name, $email, $phone, $company, $status, $client_id]);
}

// Handle new contact creation
if ($_POST && isset($_POST['create_contact'])) {
    Security::checkCSRFToken();
    
    $client_id = (int)$_POST['client_id'];
    $contact_type = Security::sanitizeInput($_POST['contact_type']);
    $contact_person = Security::sanitizeInput($_POST['contact_person']);
    $email = Security::sanitizeInput($_POST['email']);
    $phone = Security::sanitizeInput($_POST['phone']);
    $subject = Security::sanitizeInput($_POST['subject']);
    $message = Security::sanitizeInput($_POST['message']);
    $priority = Security::sanitizeInput($_POST['priority']);
    $follow_up_date = Security::sanitizeInput($_POST['follow_up_date']);
    
    $query = "INSERT INTO client_contacts (client_id, contact_type, contact_person, email, phone, subject, message, priority, assigned_to, follow_up_date) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$client_id, $contact_type, $contact_person, $email, $phone, $subject, $message, $priority, $_SESSION['user_id'], $follow_up_date]);
    
    // Log activity
    $activity_query = "INSERT INTO client_activities (client_id, activity_type, description, user_id) 
                      VALUES (?, ?, ?, ?)";
    $activity_stmt = $db->prepare($activity_query);
    $activity_stmt->execute([$client_id, 'contact_created', "New {$contact_type} contact: {$subject}", $_SESSION['user_id']]);
}

// Handle contact status updates
if ($_POST && isset($_POST['update_contact_status'])) {
    Security::checkCSRFToken();
    
    $contact_id = (int)$_POST['contact_id'];
    $status = Security::sanitizeInput($_POST['status']);
    
    $query = "UPDATE client_contacts SET status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$status, $contact_id]);
}

// Handle meeting creation
if ($_POST && isset($_POST['create_meeting'])) {
    Security::checkCSRFToken();
    
    $client_id = (int)$_POST['client_id'];
    $meeting_title = Security::sanitizeInput($_POST['meeting_title']);
    $meeting_date = Security::sanitizeInput($_POST['meeting_date']);
    $duration = (int)$_POST['duration'];
    $location = Security::sanitizeInput($_POST['location']);
    $attendees = Security::sanitizeInput($_POST['attendees']);
    $agenda = Security::sanitizeInput($_POST['agenda']);
    
    $query = "INSERT INTO client_meetings (client_id, meeting_title, meeting_date, duration, location, attendees, agenda, created_by, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')";
    $stmt = $db->prepare($query);
    $stmt->execute([$client_id, $meeting_title, $meeting_date, $duration, $location, $attendees, $agenda, $_SESSION['user_id']]);
    
    // Log activity
    $activity_query = "INSERT INTO client_activities (client_id, activity_type, description, user_id) 
                      VALUES (?, ?, ?, ?)";
    $activity_stmt = $db->prepare($activity_query);
    $activity_stmt->execute([$client_id, 'meeting_scheduled', "Meeting scheduled: {$meeting_title}", $_SESSION['user_id']]);
}

// Handle meeting updates
if ($_POST && isset($_POST['update_meeting'])) {
    Security::checkCSRFToken();
    
    $meeting_id = (int)$_POST['meeting_id'];
    $notes = Security::sanitizeInput($_POST['notes']);
    $outcome = Security::sanitizeInput($_POST['outcome']);
    $next_steps = Security::sanitizeInput($_POST['next_steps']);
    $status = Security::sanitizeInput($_POST['status']);
    
    $query = "UPDATE client_meetings SET notes = ?, outcome = ?, next_steps = ?, status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$notes, $outcome, $next_steps, $status, $meeting_id]);
}

// Handle document upload (simulation)
if ($_POST && isset($_POST['upload_document'])) {
    Security::checkCSRFToken();
    
    $client_id = (int)$_POST['client_id'];
    $document_name = Security::sanitizeInput($_POST['document_name']);
    $document_type = Security::sanitizeInput($_POST['document_type']);
    $description = Security::sanitizeInput($_POST['description']);
    
    // Simulate file upload (in real implementation, handle actual file upload)
    $file_path = "/uploads/clients/{$client_id}/" . preg_replace('/[^a-zA-Z0-9._-]/', '', $document_name);
    
    $query = "INSERT INTO client_documents (client_id, document_name, document_type, file_path, description, uploaded_by) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$client_id, $document_name, $document_type, $file_path, $description, $_SESSION['user_id']]);
    
    // Log activity
    $activity_query = "INSERT INTO client_activities (client_id, activity_type, description, user_id) 
                      VALUES (?, ?, ?, ?)";
    $activity_stmt = $db->prepare($activity_query);
    $activity_stmt->execute([$client_id, 'document_uploaded', "Document uploaded: {$document_name}", $_SESSION['user_id']]);
}

// Get data for display
$clients = $db->query("SELECT * FROM clients ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$contacts = $db->query("SELECT cc.*, c.name as client_name, u.username as assigned_to_name 
                       FROM client_contacts cc 
                       JOIN clients c ON cc.client_id = c.id 
                       LEFT JOIN users u ON cc.assigned_to = u.id 
                       ORDER BY cc.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$meetings = $db->query("SELECT cm.*, c.name as client_name, u.username as created_by_name 
                       FROM client_meetings cm 
                       JOIN clients c ON cm.client_id = c.id 
                       LEFT JOIN users u ON cm.created_by = u.id 
                       ORDER BY cm.meeting_date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Check if client_documents table exists before querying
try {
    $documents = $db->query("SELECT cd.*, c.name as client_name, u.username as uploaded_by_name 
                            FROM client_documents cd 
                            JOIN clients c ON cd.client_id = c.id 
                            LEFT JOIN users u ON cd.uploaded_by = u.id 
                            ORDER BY cd.uploaded_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table doesn't exist, use empty array
    $documents = [];
}

// Check if client_activities table exists before querying
try {
    $activities = $db->query("SELECT ca.*, c.name as client_name, u.username as user_name 
                             FROM client_activities ca 
                             JOIN clients c ON ca.client_id = c.id 
                             LEFT JOIN users u ON ca.user_id = u.id 
                             ORDER BY ca.created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Clients Department - Business Management</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        /* Client Statistics */
        .client-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        /* Client Cards Grid */
        .clients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        .client-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .client-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .client-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .client-info {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .client-actions {
            display: flex;
            gap: 0.75rem;
            border-top: 1px solid #ecf0f1;
            padding-top: 1rem;
        }

        /* Form Styles */
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .form-grid-2col {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-purple {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
        }

        .btn-orange {
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            color: white;
        }

        .btn-small {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
        }

        /* Status and Priority Badges */
        .status-badge, .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-prospect { background: #cce7ff; color: #004085; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-lead { background: #fff3cd; color: #856404; }
        .status-open { background: #d4edda; color: #155724; }
        .status-in_progress { background: #fff3cd; color: #856404; }
        .status-resolved { background: #cce7ff; color: #004085; }
        .status-closed { background: #e2e3e5; color: #383d41; }
        .status-scheduled { background: #cce7ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .priority-low { background: #d4edda; color: #155724; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-high { background: #ffeaa7; color: #e17055; }
        .priority-urgent { background: #f8d7da; color: #721c24; }

        /* Activity Timeline */
        .activity-timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }

        .activity-item {
            display: flex;
            margin-bottom: 2rem;
            position: relative;
        }

        .timeline-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #3498db;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
            position: relative;
            z-index: 2;
        }

        .timeline-content {
            flex: 1;
            background: white;
            border: 1px solid #ecf0f1;
            border-radius: 12px;
            padding: 1.5rem;
            margin-left: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .timeline-line {
            position: absolute;
            left: 30px;
            top: 50px;
            bottom: -2rem;
            width: 2px;
            background: #3498db;
            z-index: 1;
        }

        /* Calendar Styles */
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #ddd;
            border: 1px solid #ddd;
        }

        .calendar-day {
            padding: 0.5rem;
            background: white;
            border-bottom: 1px solid #ddd;
            min-height: 100px;
            vertical-align: top;
        }

        .calendar-legend {
            margin-top: 1rem;
            display: flex;
            gap: 2rem;
            justify-content: center;
            font-size: 0.9rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #ecf0f1;
        }

        .close-btn {
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
        }

        .close-btn:hover {
            color: #34495e;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
            background: white;
            border-radius: 12px;
            border: 2px dashed #ecf0f1;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Section Headers */
        .section-header h2 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .section-subtitle {
            color: #7f8c8d;
            margin-top: 0.5rem;
        }

        /* Card Styles */
        .card {
            background: white;
            border: 1px solid #ecf0f1;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    
    <?php include '../includes/header.php'; ?>

    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>🏢 Clients Department</h1>
        
        <!-- Client Statistics -->
        <div class="client-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($clients); ?></div>
                <div class="stat-label">Total Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($clients, function($c) { return $c['status'] === 'active'; })); ?></div>
                <div class="stat-label">Active Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($contacts, function($c) { return $c['status'] === 'open'; })); ?></div>
                <div class="stat-label">Open Contacts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($meetings); ?></div>
                <div class="stat-label">Total Meetings</div>
            </div>
        </div>

        <!-- Create New Client Button -->
        <?php if (Security::canWriteInDepartment($_SESSION['role'], $_SESSION['department'], 'Clients')): ?>
        <div class="text-center" style="margin: 20px 0; text-align: center;">
            <button onclick="openCreateClientModal()" class="btn-primary btn-success">
                ➕ Create New Client
            </button>
        </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="showTab('clients')">Client Management</button>
            <button class="tab-btn" onclick="showTab('communications')">Communications</button>
            <button class="tab-btn" onclick="showTab('meetings')">Meetings</button>
            <button class="tab-btn" onclick="showTab('documents')">Documents</button>
            <button class="tab-btn" onclick="showTab('activity')">Activity Timeline</button>
        </div>
        
        <!-- Clients Tab -->
        <div id="clients" class="tab-content active">
            <div class="section">
                <div class="section-content">
                    <h3>Current Clients</h3>
                    <div class="clients-grid">
                        <?php foreach ($clients as $client): ?>
                            <div class="client-card">
                                <div class="client-header">
                                    <div class="client-title">
                                        <h4><?php echo Security::escapeHTML($client['name']); ?></h4>
                                        <p class="client-company"><?php echo Security::escapeHTML($client['company']); ?></p>
                                    </div>
                                    <span class="status-badge status-<?php echo $client['status']; ?>">
                                        <?php echo ucfirst($client['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="client-info">
                                    <?php if ($client['email']): ?>
                                        <div class="client-detail">
                                            <span class="client-icon">📧</span>
                                            <span class="client-value"><?php echo Security::escapeHTML($client['email']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($client['phone']): ?>
                                        <div class="client-detail">
                                            <span class="client-icon">📞</span>
                                            <span class="client-value"><?php echo Security::escapeHTML($client['phone']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="client-detail">
                                        <span class="client-icon">📅</span>
                                        <span class="client-value">Since <?php echo Utils::formatDate($client['created_at']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="client-actions">
                                    <a href="client_detail.php?id=<?php echo $client['id']; ?>" class="btn btn-view">
                                        View Details
                                    </a>
                                    <?php if (Security::canWriteInDepartment($_SESSION['role'], $_SESSION['department'], 'Clients')): ?>
                                        <a href="client_detail.php?id=<?php echo $client['id']; ?>&edit=1" class="btn btn-edit">
                                            Edit Client
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Communications Tab -->
        <div id="communications" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h2>Client Communications</h2>
                    <p class="section-subtitle">Track all client interactions and communications</p>
                </div>
                <div class="section-content">
                    <div class="form-container">
                        <h3>New Communication</h3>
                        
                        <form method="POST" class="form-grid-2col">
                            <?php echo Security::getCSRFTokenField(); ?>
                            
                            <div class="form-group-full">
                                <label class="form-label">Client *</label>
                                <select name="client_id" id="client-selector" onchange="fillClientDetails()" required class="form-select">
                                    <option value="">Select Client</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['id']; ?>" 
                                                data-name="<?php echo Security::escapeHTML($client['name']); ?>"
                                                data-email="<?php echo Security::escapeHTML($client['email']); ?>"
                                                data-phone="<?php echo Security::escapeHTML($client['phone']); ?>">
                                            <?php echo Security::escapeHTML($client['name'] . ' - ' . $client['company']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Contact Type *</label>
                                <select name="contact_type" required class="form-select">
                                    <option value="">Select Type</option>
                                    <option value="email">📧 Email</option>
                                    <option value="phone">📞 Phone Call</option>
                                    <option value="meeting">🤝 Meeting</option>
                                    <option value="support">🔧 Support Request</option>
                                    <option value="inquiry">❓ General Inquiry</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Priority *</label>
                                <select name="priority" required class="form-select">
                                    <option value="low">🟢 Low</option>
                                    <option value="medium" selected>🟡 Medium</option>
                                    <option value="high">🟠 High</option>
                                    <option value="urgent">🔴 Urgent</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" id="contact-person-field" class="form-input" placeholder="Contact person's name">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="email-field" class="form-input" placeholder="contact@company.com">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" id="phone-field" class="form-input" placeholder="+27-11-123-4567">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Follow-up Date</label>
                                <input type="date" name="follow_up_date" class="form-input">
                            </div>
                            
                            <div class="form-group-full">
                                <label class="form-label">Subject *</label>
                                <input type="text" name="subject" required class="form-input" placeholder="Brief subject of the communication">
                            </div>
                            
                            <div class="form-group-full">
                                <label class="form-label">Message *</label>
                                <textarea name="message" rows="6" required class="form-textarea" placeholder="Detailed message or notes about the communication"></textarea>
                            </div>
                            
                            <div class="form-group-full text-center">
                                <button type="submit" name="create_contact" class="btn-primary">
                                    💬 Log Communication
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <h3>Recent Communications</h3>
                    <?php foreach ($contacts as $contact): ?>
                        <div class="card">
                            <div class="card-header">
                                <h4><?php echo Security::escapeHTML($contact['subject']); ?></h4>
                                <div>
                                    <span class="priority-badge priority-<?php echo $contact['priority']; ?>"><?php echo ucfirst($contact['priority']); ?></span>
                                    <span class="status-badge status-<?php echo $contact['status']; ?>"><?php echo ucfirst($contact['status']); ?></span>
                                </div>
                            </div>
                            <div class="form-grid-2col">
                                <div><strong>Client:</strong> <?php echo Security::escapeHTML($contact['client_name']); ?></div>
                                <div><strong>Type:</strong> <?php echo ucfirst($contact['contact_type']); ?></div>
                                <div><strong>Contact Person:</strong> <?php echo Security::escapeHTML($contact['contact_person']); ?></div>
                                <div><strong>Date:</strong> <?php echo Utils::formatDate($contact['created_at']); ?></div>
                                <?php if ($contact['email']): ?>
                                    <div><strong>Email:</strong> <?php echo Security::escapeHTML($contact['email']); ?></div>
                                <?php endif; ?>
                                <?php if ($contact['phone']): ?>
                                    <div><strong>Phone:</strong> <?php echo Security::escapeHTML($contact['phone']); ?></div>
                                <?php endif; ?>
                                <?php if ($contact['follow_up_date']): ?>
                                    <div><strong>Follow-up:</strong> <?php echo Utils::formatDate($contact['follow_up_date']); ?></div>
                                <?php endif; ?>
                                <div><strong>Assigned to:</strong> <?php echo Security::escapeHTML($contact['assigned_to_name']); ?></div>
                            </div>
                            <div style="margin-top: 1rem;">
                                <strong>Message:</strong><br>
                                <?php echo nl2br(Security::escapeHTML($contact['message'])); ?>
                            </div>
                            
                            <?php if ($contact['status'] === 'open'): ?>
                                <form method="POST" style="margin-top: 1rem;">
                                    <?php echo Security::getCSRFTokenField(); ?>
                                    <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                    <select name="status" class="form-select" style="display: inline; width: auto; margin-right: 1rem;">
                                        <option value="in_progress">In Progress</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                    <button type="submit" name="update_contact_status" class="btn-small" style="background: #3498db; color: white;">Update Status</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Meetings Tab -->
        <div id="meetings" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h2>Client Meetings</h2>
                    <div style="margin-top: 1rem;">
                        <button class="btn-small" onclick="showMeetingView('list')" id="meeting-list-btn" style="background: #007bff; color: white;">📋 List View</button>
                        <button class="btn-small" onclick="showMeetingView('calendar')" id="meeting-calendar-btn" style="background: #6c757d; color: white; margin-left: 0.5rem;">📅 Calendar View</button>
                    </div>
                </div>
                
                <!-- Meeting List View -->
                <div id="meeting-list-view" class="section-content">
                    <div class="form-container">
                        <h3>Schedule New Meeting</h3>
                        <form method="POST" class="form-grid-2col">
                            <?php echo Security::getCSRFTokenField(); ?>
                            
                            <div class="form-group">
                                <label class="form-label">Client *</label>
                                <select name="client_id" required class="form-select">
                                    <option value="">Select Client</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['id']; ?>"><?php echo Security::escapeHTML($client['name'] . ' - ' . $client['company']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Meeting Title *</label>
                                <input type="text" name="meeting_title" required class="form-input" placeholder="Meeting purpose or title">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Meeting Date & Time *</label>
                                <input type="datetime-local" name="meeting_date" required class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Duration (minutes) *</label>
                                <input type="number" name="duration" value="60" required class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-input" placeholder="Meeting location">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Attendees</label>
                                <input type="text" name="attendees" class="form-input" placeholder="Names of attendees">
                            </div>
                            
                            <div class="form-group-full">
                                <label class="form-label">Agenda</label>
                                <textarea name="agenda" rows="4" class="form-textarea" placeholder="Meeting agenda and discussion points"></textarea>
                            </div>
                            
                            <div class="form-group-full text-center">
                                <button type="submit" name="create_meeting" class="btn-primary btn-purple">
                                    📅 Schedule Meeting
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <h3>Scheduled Meetings</h3>
                    <?php foreach ($meetings as $meeting): ?>
                        <div class="card">
                            <div class="card-header">
                                <h4><?php echo Security::escapeHTML($meeting['meeting_title']); ?></h4>
                                <span class="status-badge status-<?php echo $meeting['status']; ?>"><?php echo ucfirst($meeting['status']); ?></span>
                            </div>
                            <div class="form-grid-2col">
                                <div><strong>Client:</strong> <?php echo Security::escapeHTML($meeting['client_name']); ?></div>
                                <div><strong>Date:</strong> <?php echo Utils::formatDate($meeting['meeting_date']); ?></div>
                                <div><strong>Duration:</strong> <?php echo $meeting['duration']; ?> minutes</div>
                                <div><strong>Location:</strong> <?php echo Security::escapeHTML($meeting['location']); ?></div>
                                <div><strong>Created by:</strong> <?php echo Security::escapeHTML($meeting['created_by_name']); ?></div>
                                <div><strong>Attendees:</strong> <?php echo Security::escapeHTML($meeting['attendees']); ?></div>
                            </div>
                            <?php if ($meeting['agenda']): ?>
                                <div style="margin-top: 1rem;">
                                    <strong>Agenda:</strong><br>
                                    <?php echo nl2br(Security::escapeHTML($meeting['agenda'])); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($meeting['notes']): ?>
                                <div style="margin-top: 1rem;">
                                    <strong>Notes:</strong><br>
                                    <?php echo nl2br(Security::escapeHTML($meeting['notes'])); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($meeting['outcome']): ?>
                                <div style="margin-top: 1rem;">
                                    <strong>Outcome:</strong><br>
                                    <?php echo nl2br(Security::escapeHTML($meeting['outcome'])); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($meeting['next_steps']): ?>
                                <div style="margin-top: 1rem;">
                                    <strong>Next Steps:</strong><br>
                                    <?php echo nl2br(Security::escapeHTML($meeting['next_steps'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($meeting['status'] === 'scheduled' || $meeting['status'] === 'in_progress'): ?>
                                <form method="POST" style="margin-top: 1rem;" class="form-grid-2col">
                                    <?php echo Security::getCSRFTokenField(); ?>
                                    <input type="hidden" name="meeting_id" value="<?php echo $meeting['id']; ?>">
                                    <div class="form-group-full">
                                        <label class="form-label">Meeting Notes</label>
                                        <textarea name="notes" rows="3" class="form-textarea"><?php echo Security::escapeHTML($meeting['notes']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Outcome</label>
                                        <textarea name="outcome" rows="2" class="form-textarea"><?php echo Security::escapeHTML($meeting['outcome']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Next Steps</label>
                                        <textarea name="next_steps" rows="2" class="form-textarea"><?php echo Security::escapeHTML($meeting['next_steps']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="scheduled" <?php echo $meeting['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                            <option value="in_progress" <?php echo $meeting['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="completed" <?php echo $meeting['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $meeting['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="form-group-full">
                                        <button type="submit" name="update_meeting" class="btn-small" style="background: #3498db; color: white;">Update Meeting</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Meeting Calendar View -->
                <div id="meeting-calendar-view" class="section-content" style="display: none;">
                    <div class="form-container">
                        <div class="calendar-header">
                            <button onclick="previousMonth()" class="btn-small">← Previous</button>
                            <h3 id="calendar-month-year">Loading...</h3>
                            <button onclick="nextMonth()" class="btn-small">Next →</button>
                        </div>
                        
                        <div id="calendar-grid" class="calendar-grid"></div>
                        
                        <div class="calendar-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background: #007bff;"></div>
                                <span>Scheduled Meeting</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: #28a745;"></div>
                                <span>Completed Meeting</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: #dc3545;"></div>
                                <span>Cancelled Meeting</span>
                            </div>
                        </div>
                        
                        <!-- Meeting Details Modal -->
                        <div id="meeting-modal" class="modal">
                            <div class="modal-content">
                                <div id="modal-meeting-details"></div>
                                <button onclick="closeMeetingModal()" class="btn-primary" style="margin-top: 1rem;">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Tab -->
        <div id="documents" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h2>Client Documents</h2>
                </div>
                <div class="section-content">
                    <div class="form-container">
                        <h3>Add New Document</h3>
                        <form method="POST" class="form-grid-2col">
                            <?php echo Security::getCSRFTokenField(); ?>
                            
                            <div class="form-group">
                                <label class="form-label">Client *</label>
                                <select name="client_id" required class="form-select">
                                    <option value="">Select Client</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['id']; ?>"><?php echo Security::escapeHTML($client['name'] . ' - ' . $client['company']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Document Type *</label>
                                <select name="document_type" required class="form-select">
                                    <option value="">Select Type</option>
                                    <option value="contract">📄 Contract</option>
                                    <option value="proposal">📋 Proposal</option>
                                    <option value="invoice">🧾 Invoice</option>
                                    <option value="report">📊 Report</option>
                                    <option value="presentation">📽️ Presentation</option>
                                    <option value="other">📎 Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group-full">
                                <label class="form-label">Document Name *</label>
                                <input type="text" name="document_name" required class="form-input" placeholder="Enter document name">
                            </div>
                            
                            <div class="form-group-full">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-textarea" placeholder="Document description and purpose"></textarea>
                            </div>
                            
                            <div class="form-group-full text-center">
                                <button type="submit" name="upload_document" class="btn-primary btn-orange">
                                    📤 Add Document
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <h3>Client Documents</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Client</th>
                                <th>Type</th>
                                <th>Uploaded By</th>
                                <th>Upload Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $document): ?>
                                <tr>
                                    <td><?php echo Security::escapeHTML($document['document_name']); ?></td>
                                    <td><?php echo Security::escapeHTML($document['client_name']); ?></td>
                                    <td><?php echo ucfirst($document['document_type']); ?></td>
                                    <td><?php echo Security::escapeHTML($document['uploaded_by_name']); ?></td>
                                    <td><?php echo Utils::formatDate($document['uploaded_at']); ?></td>
                                    <td>
                                        <span class="btn-small" style="background: #ccc; cursor: not-allowed;">Download</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Activity Timeline Tab -->
        <div id="activity" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h2>Activity Timeline</h2>
                    <p class="section-subtitle">Recent client-related activities and interactions</p>
                </div>
                <div class="section-content">
                    <div class="activity-timeline">
                        <?php foreach ($activities as $index => $activity): ?>
                            <div class="activity-item">
                                <?php if ($index < count($activities) - 1): ?>
                                    <div class="timeline-line"></div>
                                <?php endif; ?>
                                
                                <div style="width: 60px; flex-shrink: 0; text-align: center; position: relative; z-index: 2;">
                                    <div class="timeline-dot">
                                        <?php 
                                        $icons = [
                                            'client_created' => '👤',
                                            'contact_created' => '💬',
                                            'meeting_scheduled' => '📅',
                                            'document_uploaded' => '📄'
                                        ];
                                        echo $icons[$activity['activity_type']] ?? '📝';
                                        ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #7f8c8d; margin-top: 0.5rem;">
                                        <?php echo date('H:i', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <div class="timeline-content">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                        <div>
                                            <strong><?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?></strong>
                                            <div style="color: #7f8c8d; font-size: 0.9rem; margin-top: 0.25rem;">
                                                Client: <?php echo Security::escapeHTML($activity['client_name']); ?>
                                            </div>
                                        </div>
                                        <small style="color: #95a5a6; font-size: 0.85rem;">
                                            <?php echo Utils::formatDate($activity['created_at']); ?>
                                        </small>
                                    </div>
                                    
                                    <div style="color: #34495e; line-height: 1.5; margin-bottom: 1rem;">
                                        <?php echo Security::escapeHTML($activity['description']); ?>
                                    </div>
                                    
                                    <div style="font-size: 0.85rem; color: #7f8c8d; border-top: 1px solid #ecf0f1; padding-top: 1rem;">
                                        <strong>Performed by:</strong> <?php echo Security::escapeHTML($activity['user_name']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($activities)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📊</div>
                                <h3>No Activity Yet</h3>
                                <p>Client activities will appear here once you start interacting with clients.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/notification.js"></script>  
    <script>
        // Meeting data for calendar
        const meetingsData = <?php echo json_encode($meetings); ?>;
        let currentDate = new Date();
        
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
            
            // If meetings tab is selected, initialize calendar
            if (tabName === 'meetings') {
                renderCalendar();
            }
        }
        
        function showMeetingView(viewType) {
            const listView = document.getElementById('meeting-list-view');
            const calendarView = document.getElementById('meeting-calendar-view');
            const listBtn = document.getElementById('meeting-list-btn');
            const calendarBtn = document.getElementById('meeting-calendar-btn');
            
            if (viewType === 'list') {
                listView.style.display = 'block';
                calendarView.style.display = 'none';
                listBtn.style.background = '#007bff';
                calendarBtn.style.background = '#6c757d';
            } else {
                listView.style.display = 'none';
                calendarView.style.display = 'block';
                listBtn.style.background = '#6c757d';
                calendarBtn.style.background = '#007bff';
                renderCalendar();
            }
        }
        
        function renderCalendar() {
            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"];
            const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            document.getElementById('calendar-month-year').textContent = `${monthNames[month]} ${year}`;
            
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            let calendarHTML = '';
            
            // Add day headers
            dayNames.forEach(day => {
                calendarHTML += `<div class="calendar-day" style="padding: 0.75rem; background: #f8f9fa; font-weight: bold; text-align: center; border-bottom: 1px solid #ddd;">${day}</div>`;
            });
            
            // Add empty cells for days before month starts
            for (let i = 0; i < firstDay; i++) {
                calendarHTML += `<div class="calendar-day"></div>`;
            }
            
            // Add days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dayMeetings = getDayMeetings(dateStr);
                
                let cellContent = `<div style="font-weight: bold; margin-bottom: 0.5rem;">${day}</div>`;
                
                if (dayMeetings.length > 0) {
                    dayMeetings.forEach(meeting => {
                        const statusColor = getStatusColor(meeting.status);
                        const time = new Date(meeting.meeting_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        cellContent += `<div onclick="showMeetingDetails(${meeting.id})" style="background: ${statusColor}; color: white; padding: 0.25rem; margin: 0.125rem 0; border-radius: 3px; font-size: 0.7rem; cursor: pointer; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${time} - ${meeting.client_name}</div>`;
                    });
                }
                
                calendarHTML += `<div class="calendar-day">${cellContent}</div>`;
            }
            
            document.getElementById('calendar-grid').innerHTML = calendarHTML;
        }
        
        function getDayMeetings(dateStr) {
            return meetingsData.filter(meeting => {
                const meetingDate = new Date(meeting.meeting_date).toISOString().split('T')[0];
                return meetingDate === dateStr;
            });
        }
        
        function getStatusColor(status) {
            switch(status) {
                case 'completed': return '#28a745';
                case 'cancelled': return '#dc3545';
                case 'in_progress': return '#ffc107';
                default: return '#007bff';
            }
        }
        
        function showMeetingDetails(meetingId) {
            const meeting = meetingsData.find(m => m.id == meetingId);
            if (!meeting) return;
            
            const date = new Date(meeting.meeting_date);
            const detailsHTML = `
                <h4>${meeting.meeting_title}</h4>
                <p><strong>Client:</strong> ${meeting.client_name}</p>
                <p><strong>Date:</strong> ${date.toLocaleDateString()} at ${date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                <p><strong>Duration:</strong> ${meeting.duration} minutes</p>
                <p><strong>Location:</strong> ${meeting.location || 'Not specified'}</p>
                <p><strong>Status:</strong> ${meeting.status}</p>
                <p><strong>Attendees:</strong> ${meeting.attendees || 'Not specified'}</p>
                ${meeting.agenda ? `<p><strong>Agenda:</strong><br>${meeting.agenda}</p>` : ''}
                ${meeting.notes ? `<p><strong>Notes:</strong><br>${meeting.notes}</p>` : ''}
            `;
            
            document.getElementById('modal-meeting-details').innerHTML = detailsHTML;
            document.getElementById('meeting-modal').style.display = 'block';
        }
        
        function closeMeetingModal() {
            document.getElementById('meeting-modal').style.display = 'none';
        }
        
        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        }
        
        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        }
        
        // Auto-fill client details in communications form
        function fillClientDetails() {
            const clientSelector = document.getElementById('client-selector');
            const selectedOption = clientSelector.options[clientSelector.selectedIndex];
            
            if (selectedOption && selectedOption.value) {
                const clientName = selectedOption.getAttribute('data-name');
                const clientEmail = selectedOption.getAttribute('data-email');
                const clientPhone = selectedOption.getAttribute('data-phone');
                
                document.getElementById('contact-person-field').value = clientName || '';
                document.getElementById('email-field').value = clientEmail || '';
                document.getElementById('phone-field').value = clientPhone || '';
            } else {
                document.getElementById('contact-person-field').value = '';
                document.getElementById('email-field').value = '';
                document.getElementById('phone-field').value = '';
            }
        }
        
        // Create Client Modal Functions
        function openCreateClientModal() {
            document.getElementById('create-client-modal').style.display = 'block';
        }
        
        function closeCreateClientModal() {
            document.getElementById('create-client-modal').style.display = 'none';
            document.getElementById('create-client-form').reset();
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('meeting-modal');
            const createModal = document.getElementById('create-client-modal');
            
            if (event.target === modal) {
                closeMeetingModal();
            }
            if (event.target === createModal) {
                closeCreateClientModal();
            }
        }
    </script>
    
    <!-- Create Client Modal -->
    <div id="create-client-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Client</h3>
                <span class="close-btn" onclick="closeCreateClientModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="create-client-form" class="form-grid-2col">
                    <?php echo Security::getCSRFTokenField(); ?>
                    <input type="hidden" name="create_client" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">Client Name *</label>
                        <input type="text" name="name" required class="form-input" placeholder="Enter client's full name">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Company *</label>
                        <input type="text" name="company" required class="form-input" placeholder="Enter company name">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" placeholder="client@company.com">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input" placeholder="+27-11-123-4567">
                    </div>
                    
                    <div class="form-group-full">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="3" class="form-textarea" placeholder="Enter company address"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" required class="form-select">
                            <option value="">Select Status</option>
                            <option value="prospect">Prospect</option>
                            <option value="active">Active Client</option>
                            <option value="inactive">Inactive</option>
                            <option value="lead">Lead</option>
                        </select>
                    </div>
                    
                    <div class="form-group-full text-center">
                        <button type="submit" name="create_client" value="1" class="btn-primary btn-success">
                            ✅ Create Client
                        </button>
                        <button type="button" onclick="closeCreateClientModal()" class="btn-primary" style="background: #6c757d; margin-left: 0.5rem;">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>