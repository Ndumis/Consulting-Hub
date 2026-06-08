<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';
require_once '../includes/file_upload.php';
require_once '../includes/page_tracker.php';

// Check department access
Security::requireDepartmentAccess('HR');

$database = new Database();
$db = $database->getConnection();

// Get user info
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];
$email = $_SESSION['email'];

// Tables exist in MySQL database

// Handle AJAX get department managers request
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_department_managers') {
    $department = Security::sanitizeInput($_GET['dept'] ?? '');
    
    $query = "SELECT id, first_name, last_name FROM hr_employees WHERE department = ? AND status = 'active' AND id != ? ORDER BY first_name, last_name";
    $stmt = $db->prepare($query);
    $stmt->execute([$department, $_GET['exclude_id'] ?? 0]);
    $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'managers' => $managers]);
    exit();
}

// Handle AJAX employee view request
if (isset($_GET['ajax']) && $_GET['ajax'] === 'view_employee') {
    $employee_id = (int)$_GET['id'];
    
    $query = "SELECT e.*, m.first_name as manager_first_name, m.last_name as manager_last_name,
              CONCAT(m.first_name, ' ', m.last_name) as manager_name
              FROM hr_employees e 
              LEFT JOIN hr_employees m ON e.manager_id = m.id 
              WHERE e.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    if ($employee) {
        echo json_encode(['success' => true, 'employee' => $employee]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// Handle employee update
if ($_POST && isset($_POST['update_employee'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    
    $id = (int)$_POST['employee_id'];
    $first_name = Security::sanitizeInput($_POST['first_name']);
    $last_name = Security::sanitizeInput($_POST['last_name']);
    $email = Security::sanitizeInput($_POST['email']);
    $phone = Security::sanitizeInput($_POST['phone']);
    $position = Security::sanitizeInput($_POST['position']);
    $department = Security::sanitizeInput($_POST['department']);
    $role = Security::sanitizeInput($_POST['role']);
    $salary = floatval($_POST['salary']);
    $status = Security::sanitizeInput($_POST['status']);
    $manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
    
    // Validate status
    $valid_statuses = ['active', 'inactive', 'terminated'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'active'; // Default to active if invalid
    }
    
    // Validate manager_id (prevent self-reference)
    if ($manager_id === $id) {
        $manager_id = null;
    }
    
    // Check if manager exists
    if ($manager_id) {
        $manager_check = $db->prepare("SELECT id FROM hr_employees WHERE id = ?");
        $manager_check->execute([$manager_id]);
        if (!$manager_check->fetch()) {
            $manager_id = null; // Manager doesn't exist
        }
    }
    
    $query = "UPDATE hr_employees SET first_name = ?, last_name = ?, email = ?, phone = ?, position = ?, department = ?, role = ?, salary = ?, status = ?, manager_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$first_name, $last_name, $email, $phone, $position, $department, $role, $salary, $status, $manager_id, $id]);
    
    // Log employee update activity
    try {
        require_once '../includes/ActivityLogger.php';
        $logger = new ActivityLogger($db);
        $logger->logEdit('employee', $id, "Updated employee: {$first_name} {$last_name}", [
            'position' => $position,
            'department' => $department,
            'status' => $status
        ]);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

// Handle employee creation
if ($_POST && isset($_POST['create_employee'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    
    $employee_id = 'EMP-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    $first_name = Security::sanitizeInput($_POST['first_name']);
    $last_name = Security::sanitizeInput($_POST['last_name']);
    $email = Security::sanitizeInput($_POST['email']);
    $phone = Security::sanitizeInput($_POST['phone']);
    $position = Security::sanitizeInput($_POST['position']);
    $department = Security::sanitizeInput($_POST['department']);
    $role = Security::sanitizeInput($_POST['role']);
    $hire_date = Security::sanitizeInput($_POST['hire_date']);
    $salary = floatval($_POST['salary']);
    $manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
    
    $query = "INSERT INTO hr_employees (employee_id, first_name, last_name, email, phone, position, department, role, hire_date, salary, manager_id, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$employee_id, $first_name, $last_name, $email, $phone, $position, $department, $role, $hire_date, $salary, $manager_id, 'active']);
    
    $new_employee_db_id = $db->lastInsertId();
    
    // Log employee creation activity
    try {
        require_once '../includes/ActivityLogger.php';
        $logger = new ActivityLogger($db);
        $logger->logCreate('employee', $new_employee_db_id, "Created new employee: {$first_name} {$last_name}", [
            'employee_id' => $employee_id,
            'position' => $position,
            'department' => $department,
            'hire_date' => $hire_date
        ]);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

// Handle leave request
if ($_POST && isset($_POST['create_leave_request'])) {
    Security::checkCSRFToken();
    
    $employee_id = (int)$_POST['employee_id'];
    $leave_type = Security::sanitizeInput($_POST['leave_type']);
    $start_date = Security::sanitizeInput($_POST['start_date']);
    $end_date = Security::sanitizeInput($_POST['end_date']);
    $reason = Security::sanitizeInput($_POST['reason']);
    
    // Calculate days requested
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days_requested = $start->diff($end)->days + 1;
    
    $query = "INSERT INTO hr_leave_requests (employee_id, leave_type, start_date, end_date, days_requested, reason) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$employee_id, $leave_type, $start_date, $end_date, $days_requested, $reason]);
    
    $leave_request_id = $db->lastInsertId();
    
    // Log leave request creation activity
    try {
        require_once '../includes/ActivityLogger.php';
        $logger = new ActivityLogger($db);
        $logger->logCreate('leave_request', $leave_request_id, "Created {$leave_type} leave request for {$days_requested} days", [
            'employee_id' => $employee_id,
            'leave_type' => $leave_type,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'days_requested' => $days_requested
        ]);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

// Handle leave approval/rejection
if ($_POST && isset($_POST['update_leave_status'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    
    $leave_id = (int)$_POST['leave_id'];
    $status = Security::sanitizeInput($_POST['status']);
    
    $query = "UPDATE hr_leave_requests SET status = ?, approved_by = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$status, $_SESSION['user_id'], $leave_id]);
    
    // Log leave request status update activity
    try {
        require_once '../includes/ActivityLogger.php';
        $logger = new ActivityLogger($db);
        $action = ucfirst($status);
        $logger->logEdit('leave_request', $leave_id, "{$action} leave request", [
            'new_status' => $status,
            'approved_by' => $_SESSION['user_id']
        ]);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

// Handle performance review creation
if ($_POST && isset($_POST['create_performance_review'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    
    $employee_id = (int)$_POST['employee_id'];
    $reviewer_id = (int)$_POST['reviewer_id'];
    $review_period = Security::sanitizeInput($_POST['review_period']);
    $overall_rating = (int)$_POST['overall_rating'];
    $goals_achievement = Security::sanitizeInput($_POST['goals_achievement']);
    $strengths = Security::sanitizeInput($_POST['strengths']);
    $areas_for_improvement = Security::sanitizeInput($_POST['areas_for_improvement']);
    $comments = Security::sanitizeInput($_POST['comments']);
    
    $query = "INSERT INTO performance_reviews (employee_id, reviewer_id, review_period, start_date, end_date, overall_rating, goals_achievement, strengths, areas_for_improvement, comments) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $start_date = Security::sanitizeInput($_POST['start_date']);
    $end_date = Security::sanitizeInput($_POST['end_date']);
    $stmt->execute([$employee_id, $reviewer_id, $review_period, $start_date, $end_date, $overall_rating, $goals_achievement, $strengths, $areas_for_improvement, $comments]);
    
    $review_id = $db->lastInsertId();
    
    // Log performance review creation activity
    try {
        require_once '../includes/ActivityLogger.php';
        $logger = new ActivityLogger($db);
        $logger->logCreate('performance_review', $review_id, "Created performance review for employee ID {$employee_id}", [
            'employee_id' => $employee_id,
            'reviewer_id' => $reviewer_id,
            'review_period' => $review_period,
            'overall_rating' => $overall_rating
        ]);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

// Handle job posting update
if ($_POST && isset($_POST['update_job_posting'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    
    $job_id = (int)$_POST['job_id'];
    $title = Security::sanitizeInput($_POST['title']);
    $department = Security::sanitizeInput($_POST['department']);
    $description = Security::sanitizeInput($_POST['description']);
    $requirements = Security::sanitizeInput($_POST['requirements']);
    $salary_range = Security::sanitizeInput($_POST['salary_range']);
    $status = Security::sanitizeInput($_POST['status']);
    
    // Validate department against allowed values
    $allowed_departments = ['IT', 'Marketing', 'Finance', 'HR', 'Sales'];
    if (!in_array($department, $allowed_departments)) {
        $department = 'IT'; // Default to IT if invalid
    }
    
    // Validate status against allowed values
    $allowed_statuses = ['active', 'closed', 'draft'];
    if (!in_array($status, $allowed_statuses)) {
        $status = 'active'; // Default to active if invalid
    }
    
    $query = "UPDATE job_postings SET title = ?, department = ?, description = ?, requirements = ?, salary_range = ?, status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$title, $department, $description, $requirements, $salary_range, $status, $job_id]);
    
    // Log job posting update activity
    try {
        require_once '../includes/ActivityLogger.php';
        $logger = new ActivityLogger($db);
        $logger->logEdit('job_posting', $job_id, "Updated job posting: {$title}", [
            'previous_status' => $_POST['original_status'] ?? 'unknown',
            'new_status' => $status,
            'department' => $department
        ]);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
    
    $success_message = "Job posting updated successfully!";
}

// Handle job posting deletion
if ($_POST && isset($_POST['delete_job_posting'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    
    $job_id = (int)$_POST['job_id'];
    
    // Delete related candidates first
    $db->prepare("DELETE FROM candidates WHERE job_posting_id = ?")->execute([$job_id]);
    
    // Get job posting details before deletion for logging
    $job_stmt = $db->prepare("SELECT title, department FROM job_postings WHERE id = ?");
    $job_stmt->execute([$job_id]);
    $job_details = $job_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete the job posting
    $db->prepare("DELETE FROM job_postings WHERE id = ?")->execute([$job_id]);
    
    // Log job posting deletion activity
    try {
        require_once '../includes/ActivityLogger.php';
        $logger = new ActivityLogger($db);
        $job_title = $job_details['title'] ?? 'Unknown Job';
        $job_dept = $job_details['department'] ?? 'Unknown Department';
        $logger->logDelete('job_posting', $job_id, "Deleted job posting: {$job_title}", [
            'title' => $job_title,
            'department' => $job_dept,
            'candidates_affected' => 'All related candidates were also removed'
        ]);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
    
    $success_message = "Job posting deleted successfully!";
}

// Handle job posting creation
if ($_POST && isset($_POST['create_job_posting'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    
    $title = Security::sanitizeInput($_POST['title']);
    $department = Security::sanitizeInput($_POST['department']);
    $description = Security::sanitizeInput($_POST['description']);
    $requirements = Security::sanitizeInput($_POST['requirements']);
    $salary_range = Security::sanitizeInput($_POST['salary_range']);
    
    // Validate department against allowed values
    $allowed_departments = ['IT', 'Marketing', 'Finance', 'HR', 'Sales'];
    if (!in_array($department, $allowed_departments)) {
        $department = 'IT'; // Default to IT if invalid
    }
    
    $query = "INSERT INTO job_postings (title, department, description, requirements, salary_range, posted_by, status) 
              VALUES (?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $db->prepare($query);
    $stmt->execute([$title, $department, $description, $requirements, $salary_range, $_SESSION['user_id']]);
    
    $job_id = $db->lastInsertId();
    
    // Log job posting creation activity
    try {
        require_once '../includes/ActivityLogger.php';
        $logger = new ActivityLogger($db);
        $logger->logCreate('job_posting', $job_id, "Created new job posting: {$title}", [
            'department' => $department,
            'salary_range' => $salary_range,
            'status' => 'active'
        ]);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

// Handle comprehensive candidate application
if ($_POST && isset($_POST['create_candidate'])) {
    Security::checkCSRFToken();
    
    try {
        $db->beginTransaction();
        
        // Basic candidate information
        $job_posting_id = (int)$_POST['job_posting_id'];
        $first_name = Security::sanitizeInput($_POST['first_name']);
        $last_name = Security::sanitizeInput($_POST['last_name']);
        $email = Security::sanitizeInput($_POST['email']);
        $phone = Security::sanitizeInput($_POST['phone']);
        $cover_letter = Security::sanitizeInput($_POST['cover_letter']);
        
        // Enhanced candidate fields
        $salary_expectation = !empty($_POST['salary_expectation']) ? floatval($_POST['salary_expectation']) : null;
        $availability_date = !empty($_POST['availability_date']) ? Security::sanitizeInput($_POST['availability_date']) : null;
        $preferred_location = Security::sanitizeInput($_POST['preferred_location']);
        $willing_to_relocate = isset($_POST['willing_to_relocate']) ? 't' : 'f';
        $work_authorization = Security::sanitizeInput($_POST['work_authorization']);
        $linkedin_profile = Security::sanitizeInput($_POST['linkedin_profile']);
        $portfolio_website = Security::sanitizeInput($_POST['portfolio_website']);
        $years_experience = !empty($_POST['years_experience']) ? (int)$_POST['years_experience'] : null;
        
        // Insert candidate and get ID
        $query = "INSERT INTO candidates (job_posting_id, first_name, last_name, email, phone, cover_letter, 
                  salary_expectation, availability_date, preferred_location, willing_to_relocate, 
                  work_authorization, linkedin_profile, portfolio_website, years_experience, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending') RETURNING id";
        $stmt = $db->prepare($query);
        $stmt->execute([$job_posting_id, $first_name, $last_name, $email, $phone, $cover_letter,
                       $salary_expectation, $availability_date, $preferred_location, $willing_to_relocate,
                       $work_authorization, $linkedin_profile, $portfolio_website, $years_experience]);
        
        $candidate_id = $stmt->fetchColumn();
        
        // Handle CV file upload
        $resume_filename = null;
        if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
            $upload_result = FileUpload::uploadFile($_FILES['resume_file'], $candidate_id);
            if ($upload_result['success']) {
                $resume_filename = $upload_result['filename'];
                // Update candidate with resume filename
                $stmt = $db->prepare("UPDATE candidates SET resume_file = ? WHERE id = ?");
                $stmt->execute([$resume_filename, $candidate_id]);
            } else {
                throw new Exception('File upload failed: ' . implode(', ', $upload_result['errors']));
            }
        }
        
        // Handle work experience entries
        if (isset($_POST['work_experience']) && is_array($_POST['work_experience'])) {
            foreach ($_POST['work_experience'] as $experience) {
                if (!empty($experience['company_name']) && !empty($experience['position_title'])) {
                    $stmt = $db->prepare("INSERT INTO candidate_work_experience 
                                         (candidate_id, company_name, position_title, start_date, end_date, is_current, responsibilities, achievements) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $candidate_id,
                        Security::sanitizeInput($experience['company_name']),
                        Security::sanitizeInput($experience['position_title']),
                        Security::sanitizeInput($experience['start_date']),
                        isset($experience['is_current']) ? null : (!empty($experience['end_date']) ? Security::sanitizeInput($experience['end_date']) : null),
                        isset($experience['is_current']) ? 't' : 'f',
                        Security::sanitizeInput($experience['responsibilities']),
                        Security::sanitizeInput($experience['achievements'])
                    ]);
                }
            }
        }
        
        // Handle education entries
        if (isset($_POST['education']) && is_array($_POST['education'])) {
            foreach ($_POST['education'] as $education) {
                if (!empty($education['institution_name']) && !empty($education['degree_type'])) {
                    $stmt = $db->prepare("INSERT INTO candidate_education 
                                         (candidate_id, institution_name, degree_type, field_of_study, start_year, end_year, is_current, gpa, honors, description) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $candidate_id,
                        Security::sanitizeInput($education['institution_name']),
                        Security::sanitizeInput($education['degree_type']),
                        Security::sanitizeInput($education['field_of_study']),
                        !empty($education['start_year']) ? (int)$education['start_year'] : null,
                        isset($education['is_current']) ? null : (!empty($education['end_year']) ? (int)$education['end_year'] : null),
                        isset($education['is_current']) ? 't' : 'f',
                        !empty($education['gpa']) ? floatval($education['gpa']) : null,
                        Security::sanitizeInput($education['honors'] ?? ''),
                        Security::sanitizeInput($education['description'] ?? '')
                    ]);
                }
            }
        }
        
        // Log candidate creation activity
        try {
            require_once '../includes/ActivityLogger.php';
            $logger = new ActivityLogger($db);
            $job_posting_query = $db->prepare("SELECT title, department FROM job_postings WHERE id = ?");
            $job_posting_query->execute([$job_posting_id]);
            $job_posting = $job_posting_query->fetch(PDO::FETCH_ASSOC);
            
            $logger->logCreate('candidate', $candidate_id, "New candidate application: {$first_name} {$last_name} for {$job_posting['title']}", [
                'candidate_id' => $candidate_id,
                'job_posting_id' => $job_posting_id,
                'job_title' => $job_posting['title'],
                'department' => $job_posting['department'],
                'email' => $email,
                'phone' => $phone,
                'years_experience' => $years_experience,
                'has_resume' => !empty($resume_filename)
            ]);
        } catch (Exception $e) {
            error_log("Activity logging failed: " . $e->getMessage());
        }
        
        $db->commit();
        $success_message = "Candidate application submitted successfully!";
        
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Error submitting application: " . $e->getMessage();
    }
}

// Get data for display
$employees = $db->query("SELECT e.*, m.first_name as manager_first_name, m.last_name as manager_last_name 
                        FROM hr_employees e 
                        LEFT JOIN hr_employees m ON e.manager_id = m.id 
                        ORDER BY e.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$leave_requests = $db->query("SELECT lr.*, e.first_name, e.last_name, e.employee_id 
                             FROM hr_leave_requests lr 
                             JOIN hr_employees e ON lr.employee_id = e.id 
                             ORDER BY lr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$performance_reviews = $db->query("SELECT pr.*, e.first_name, e.last_name, r.first_name as reviewer_first_name, r.last_name as reviewer_last_name 
                                  FROM performance_reviews pr 
                                  JOIN hr_employees e ON pr.employee_id = e.id 
                                  LEFT JOIN hr_employees r ON pr.reviewer_id = r.id 
                                  ORDER BY pr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch job postings and candidates with debugging
try {
    $job_postings = $db->query("SELECT jp.*, u.username as posted_by_name 
                               FROM job_postings jp 
                               LEFT JOIN users u ON jp.posted_by = u.id 
                               ORDER BY jp.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    $candidates = $db->query("SELECT c.*, jp.title as job_title, jp.department as job_department
                             FROM candidates c 
                             JOIN job_postings jp ON c.job_posting_id = jp.id 
                             ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Initialize empty arrays if there's an error
    $job_postings = [];
    $candidates = [];
    error_log("HR Department - Database query error: " . $e->getMessage());
}

// Fetch work experience and education for each candidate (using prepared statements)
foreach ($candidates as &$candidate) {
    $work_exp_stmt = $db->prepare("SELECT * FROM candidate_work_experience WHERE candidate_id = ? ORDER BY start_date DESC");
    $work_exp_stmt->execute([$candidate['id']]);
    $candidate['work_experience'] = $work_exp_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $education_stmt = $db->prepare("SELECT * FROM candidate_education WHERE candidate_id = ? ORDER BY start_year DESC");
    $education_stmt->execute([$candidate['id']]);
    $candidate['education'] = $education_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Department - Business Management</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        /* HR Statistics */
        .hr-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        /* Employee Cards Grid */
        .employees-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .employee-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .employee-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .employee-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .employee-title h4 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
        }

        .employee-company {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .employee-info {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .employee-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .employee-icon {
            color: #3498db;
        }

        .employee-value {
            font-size: 0.9rem;
        }

        .employee-actions {
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

        .form-title {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 1rem;
        }

        .form-actions {
            text-align: center;
            margin-top: 1rem;
        }

        /* Button Styles */
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
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }

        .btn-small {
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            text-align: center;
        }

        .btn-view {
            background: #3498db;
            color: white;
        }

        .btn-edit {
            background: #27ae60;
            color: white;
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
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-terminated { background: #e2e3e5; color: #383d41; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        .priority-low { background: #d4edda; color: #155724; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-high { background: #ffeaa7; color: #e17055; }
        .priority-urgent { background: #f8d7da; color: #721c24; }

        /* Tab Navigation */
        .tab-nav {
            display: flex;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            gap: 0.5rem;
        }

        .tab-btn {
            flex: 1;
            padding: 1rem 1.5rem;
            background: transparent;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
        }

        .tab-btn:hover {
            background: #e9ecef;
            color: #333;
        }

        .tab-btn.active {
            background: #007bff;
            color: white;
            font-weight: 600;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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

        /* Candidate Form Styles */
        .candidate-form {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
        }

        .dynamic-section {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fafafa;
            position: relative;
        }

        .remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 12px;
        }

        .add-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        /* File Upload */
        .file-upload-area {
            border: 2px dashed #ccc;
            border-radius: 6px;
            padding: 2rem;
            text-align: center;
            background: #fafafa;
            transition: border-color 0.3s;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #007bff;
        }

        .file-info {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }

        /* Candidate Display */
        .candidate-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .candidate-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .candidate-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .experience-item, .education-item {
            background: #f8f9fa;
            border-left: 3px solid #007bff;
            padding: 1rem;
            margin-bottom: 0.5rem;
        }

        /* Job Actions */
        .job-actions {
            display: flex;
            gap: 0.5rem;
        }

        .edit-form {
            animation: slideDown 0.3s ease-out;
            margin-top: 2rem;
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border: 2px solid #007bff;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                max-height: 500px;
                transform: translateY(0);
            }
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
            max-width: 600px;
            width: 90%;
            max-height: 90%;
            overflow-y: auto;
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

        /* Section Headers */
        .section-header h2 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .section-subtitle {
            color: #7f8c8d;
            margin-top: 0.5rem;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>👥 HR Department</h1>
        <!-- HR Statistics -->
        <div class="hr-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($employees); ?></div>
                <div class="stat-label">Total Employees</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($leave_requests, function($lr) { return $lr['status'] === 'pending'; })); ?></div>
                <div class="stat-label">Pending Leave Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($job_postings); ?></div>
                <div class="stat-label">Active Job Postings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($candidates); ?></div>
                <div class="stat-label">Total Candidates</div>
            </div>
        </div>

         <!-- Tab Navigation -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="showTab('employees')">Employees</button>
            <button class="tab-btn" onclick="showTab('leave')">Leave Management</button>
            <button class="tab-btn" onclick="showTab('performance')">Performance Reviews</button>
            <button class="tab-btn" onclick="showTab('recruitment')">Recruitment</button>
        </div>
        
        <!-- Employees Tab -->
        <div id="employees" class="tab-content active">
            <div class="section">
                <div class="section-header">
                    <h2>Employee Management</h2>
                </div>
                <div class="section-content">
                    <div class="form-container">
                        <h3 class="form-title">Add New Employee</h3>
                        <form method="POST" class="form-grid form-grid-2col">
                            <?php echo Security::getCSRFTokenField(); ?>
                            
                            <div class="form-group">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Position *</label>
                                <input type="text" name="position" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Department *</label>
                                <select name="department" class="form-select" required>
                                    <option value="">Select Department</option>
                                    <option value="IT">IT</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Finance">Finance</option>
                                    <option value="HR">HR</option>
                                    <option value="Sales">Sales</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Hire Date *</label>
                                <input type="date" name="hire_date" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Salary</label>
                                <input type="number" name="salary" step="0.01" class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Role *</label>
                                <select name="role" class="form-select" required>
                                    <option value="">Select Role</option>
                                    <optgroup label="IT Department">
                                        <option value="Senior Developer">Senior Developer</option>
                                        <option value="Full Stack Developer">Full Stack Developer</option>
                                        <option value="Frontend Developer">Frontend Developer</option>
                                        <option value="Backend Developer">Backend Developer</option>
                                        <option value="UI/UX Designer">UI/UX Designer</option>
                                        <option value="DevOps Engineer">DevOps Engineer</option>
                                        <option value="QA Tester">QA Tester</option>
                                        <option value="IT Project Manager">IT Project Manager</option>
                                        <option value="System Administrator">System Administrator</option>
                                        <option value="IT Support Specialist">IT Support Specialist</option>
                                    </optgroup>
                                    <!-- Other role groups remain the same -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Manager</label>
                                <select name="manager_id" class="form-select">
                                    <option value="">No Manager</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?php echo $emp['id']; ?>"><?php echo Security::escapeHTML($emp['first_name'] . ' ' . $emp['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group form-group-full form-actions">
                                <button type="submit" name="create_employee" class="btn-primary btn-success">
                                    ➕ Add Employee
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <h3>Current Employees</h3>
                    <div class="employees-grid">
                        <?php foreach ($employees as $employee): ?>
                            <div class="employee-card">
                                <div class="employee-header">
                                    <div class="employee-title">
                                        <h4><?php echo Security::escapeHTML($employee['first_name'] . ' ' . $employee['last_name']); ?></h4>
                                        <p class="employee-company"><?php echo Security::escapeHTML($employee['position']); ?></p>
                                    </div>
                                    <span class="status-badge status-<?php echo $employee['status']; ?>">
                                        <?php echo ucfirst($employee['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="employee-info">
                                    <div class="employee-detail">
                                        <span class="employee-icon">🆔</span>
                                        <span class="employee-value"><?php echo Security::escapeHTML($employee['employee_id']); ?></span>
                                    </div>
                                    
                                    <div class="employee-detail">
                                        <span class="employee-icon">📧</span>
                                        <span class="employee-value"><?php echo Security::escapeHTML($employee['email']); ?></span>
                                    </div>
                                    
                                    <div class="employee-detail">
                                        <span class="employee-icon">🏢</span>
                                        <span class="employee-value"><?php echo Security::escapeHTML($employee['department']); ?></span>
                                    </div>
                                    
                                    <div class="employee-detail">
                                        <span class="employee-icon">📅</span>
                                        <span class="employee-value">Since <?php echo Utils::formatDate($employee['hire_date']); ?></span>
                                    </div>
                                    
                                    <?php if ($employee['salary']): ?>
                                        <div class="employee-detail">
                                            <span class="employee-icon">💰</span>
                                            <span class="employee-value"><?php echo Utils::formatCurrency($employee['salary']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($employee['manager_first_name']): ?>
                                        <div class="employee-detail">
                                            <span class="employee-icon">👤</span>
                                            <span class="employee-value">Manager: <?php echo Security::escapeHTML($employee['manager_first_name'] . ' ' . $employee['manager_last_name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="employee-actions">
                                    <button type="button" class="btn-small btn-view" onclick="viewEmployee(<?php echo $employee['id']; ?>)">
                                        👁️ View
                                    </button>
                                    <button type="button" class="btn-small btn-edit" onclick="editEmployee(<?php echo $employee['id']; ?>)">
                                        ✏️ Edit
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Management Tab -->
        <div id="leave" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h2>Leave Management</h2>
                </div>
                <div class="section-content">
                    <div class="form-container">
                        <h3 class="form-title">Submit Leave Request</h3>
                        <form method="POST" class="form-grid form-grid-2col">
                            <?php echo Security::getCSRFTokenField(); ?>
                            
                            <div class="form-group">
                                <label class="form-label">Employee *</label>
                                <select name="employee_id" class="form-select" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?php echo $emp['id']; ?>"><?php echo Security::escapeHTML($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Leave Type *</label>
                                <select name="leave_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="vacation">Vacation</option>
                                    <option value="sick">Sick Leave</option>
                                    <option value="personal">Personal</option>
                                    <option value="maternity">Maternity/Paternity</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Start Date *</label>
                                <input type="date" name="start_date" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">End Date *</label>
                                <input type="date" name="end_date" class="form-input" required>
                            </div>
                            
                            <div class="form-group form-group-full">
                                <label class="form-label">Reason</label>
                                <textarea name="reason" rows="3" class="form-textarea"></textarea>
                            </div>
                            
                            <div class="form-group form-group-full form-actions">
                                <button type="submit" name="create_leave_request" class="btn-primary">
                                    📅 Submit Leave Request
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <h3>Leave Requests</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leave_requests as $request): ?>
                                <tr>
                                    <td><?php echo Security::escapeHTML($request['first_name'] . ' ' . $request['last_name']); ?></td>
                                    <td><?php echo ucfirst($request['leave_type']); ?></td>
                                    <td><?php echo Utils::formatDate($request['start_date']); ?></td>
                                    <td><?php echo Utils::formatDate($request['end_date']); ?></td>
                                    <td><?php echo $request['days_requested']; ?></td>
                                    <td><span class="status-badge status-<?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                                    <td>
                                        <?php if ($request['status'] === 'pending' && Security::canWriteInDepartment($_SESSION['role'], $_SESSION['department'], 'HR')): ?>
                                            <form method="POST" style="display: inline;">
                                                <?php echo Security::getCSRFTokenField(); ?>
                                                <input type="hidden" name="leave_id" value="<?php echo $request['id']; ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" name="update_leave_status" class="btn-small btn-success">Approve</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <?php echo Security::getCSRFTokenField(); ?>
                                                <input type="hidden" name="leave_id" value="<?php echo $request['id']; ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" name="update_leave_status" class="btn-small btn-danger">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

         <!-- Performance Reviews Tab -->
        <div id="performance" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h2>Performance Reviews</h2>
                </div>
                <div class="section-content">
                    <?php if (Security::canWriteInDepartment($_SESSION['role'], $_SESSION['department'], 'HR')): ?>
                        <div class="form-container">
                            <h3 class="form-title">Create Performance Review</h3>
                            <form method="POST" class="form-grid form-grid-2col">
                                <?php echo Security::getCSRFTokenField(); ?>
                                
                                <div class="form-group">
                                    <label class="form-label">Employee *</label>
                                    <select name="employee_id" class="form-select" required>
                                        <option value="">Select Employee</option>
                                        <?php foreach ($employees as $emp): ?>
                                            <option value="<?php echo $emp['id']; ?>"><?php echo Security::escapeHTML($emp['first_name'] . ' ' . $emp['last_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Reviewer *</label>
                                    <select name="reviewer_id" class="form-select" required>
                                        <option value="">Select Reviewer</option>
                                        <?php foreach ($employees as $emp): ?>
                                            <option value="<?php echo $emp['id']; ?>"><?php echo Security::escapeHTML($emp['first_name'] . ' ' . $emp['last_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Review Period *</label>
                                    <input type="text" name="review_period" class="form-input" placeholder="e.g., Q4 2025" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Period Start Date *</label>
                                    <input type="date" name="start_date" class="form-input" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Period End Date *</label>
                                    <input type="date" name="end_date" class="form-input" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Overall Rating (1-5) *</label>
                                    <select name="overall_rating" class="form-select" required>
                                        <option value="">Select Rating</option>
                                        <option value="1">1 - Needs Improvement</option>
                                        <option value="2">2 - Below Expectations</option>
                                        <option value="3">3 - Meets Expectations</option>
                                        <option value="4">4 - Exceeds Expectations</option>
                                        <option value="5">5 - Outstanding</option>
                                    </select>
                                </div>
                                
                                <div class="form-group form-group-full">
                                    <label class="form-label">Goals Achievement</label>
                                    <textarea name="goals_achievement" rows="3" class="form-textarea"></textarea>
                                </div>
                                
                                <div class="form-group form-group-full">
                                    <label class="form-label">Strengths</label>
                                    <textarea name="strengths" rows="3" class="form-textarea"></textarea>
                                </div>
                                
                                <div class="form-group form-group-full">
                                    <label class="form-label">Areas for Improvement</label>
                                    <textarea name="areas_for_improvement" rows="3" class="form-textarea"></textarea>
                                </div>
                                
                                <div class="form-group form-group-full">
                                    <label class="form-label">Additional Comments</label>
                                    <textarea name="comments" rows="3" class="form-textarea"></textarea>
                                </div>
                                
                                <div class="form-group form-group-full form-actions">
                                    <button type="submit" name="create_performance_review" class="btn-primary">
                                        📊 Create Review
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <h3>Performance Reviews</h3>
                    <?php foreach ($performance_reviews as $review): ?>
                        <div class="card">
                            <div class="card-header">
                                <h4><?php echo Security::escapeHTML($review['first_name'] . ' ' . $review['last_name']); ?> - <?php echo Security::escapeHTML($review['review_period']); ?></h4>
                                <div>
                                    <small>Period: <?php echo $review['start_date'] ? Utils::formatDate($review['start_date']) : ''; ?> - <?php echo $review['end_date'] ? Utils::formatDate($review['end_date']) : ''; ?></small>
                                    <span class="priority-badge priority-<?php echo $review['overall_rating'] >= 4 ? 'high' : ($review['overall_rating'] >= 3 ? 'medium' : 'low'); ?>">
                                        Rating: <?php echo $review['overall_rating']; ?>/5
                                    </span>
                                </div>
                            </div>
                            <div class="form-grid-2col">
                                <div><strong>Reviewer:</strong> <?php echo Security::escapeHTML($review['reviewer_first_name'] . ' ' . $review['reviewer_last_name']); ?></div>
                                <div><strong>Date:</strong> <?php echo Utils::formatDate($review['created_at']); ?></div>
                            </div>
                            <?php if ($review['goals_achievement']): ?>
                                <div style="margin-top: 1rem;"><strong>Goals Achievement:</strong><br><?php echo Security::escapeHTML($review['goals_achievement']); ?></div>
                            <?php endif; ?>
                            <?php if ($review['strengths']): ?>
                                <div style="margin-top: 1rem;"><strong>Strengths:</strong><br><?php echo Security::escapeHTML($review['strengths']); ?></div>
                            <?php endif; ?>
                            <?php if ($review['areas_for_improvement']): ?>
                                <div style="margin-top: 1rem;"><strong>Areas for Improvement:</strong><br><?php echo Security::escapeHTML($review['areas_for_improvement']); ?></div>
                            <?php endif; ?>
                            <?php if ($review['comments']): ?>
                                <div style="margin-top: 1rem;"><strong>Comments:</strong><br><?php echo Security::escapeHTML($review['comments']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recruitment Tab -->
        <div id="recruitment" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h2>Job Postings</h2>
                </div>
                <div class="section-content">
                    <?php if (Security::canWriteInDepartment($_SESSION['role'], $_SESSION['department'], 'HR')): ?>
                        <div class="form-container">
                            <h3 class="form-title">Create Job Posting</h3>
                            <form method="POST" class="form-grid form-grid-2col">
                                <?php echo Security::getCSRFTokenField(); ?>
                                
                                <div class="form-group">
                                    <label class="form-label">Job Title *</label>
                                    <input type="text" name="title" class="form-input" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Department *</label>
                                    <select name="department" class="form-select" required>
                                        <option value="">Select Department</option>
                                        <option value="IT">IT</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Finance">Finance</option>
                                        <option value="HR">HR</option>
                                        <option value="Sales">Sales</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Salary Range</label>
                                    <input type="text" name="salary_range" class="form-input" placeholder="e.g., R 50,000 - R 70,000">
                                </div>
                                
                                <div class="form-group form-group-full">
                                    <label class="form-label">Job Description *</label>
                                    <textarea name="description" rows="4" class="form-textarea" required></textarea>
                                </div>
                                
                                <div class="form-group form-group-full">
                                    <label class="form-label">Requirements *</label>
                                    <textarea name="requirements" rows="4" class="form-textarea" required></textarea>
                                </div>
                                
                                <div class="form-group form-group-full form-actions">
                                    <button type="submit" name="create_job_posting" class="btn-primary">
                                        📋 Post Job
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <h3>Current Job Postings</h3>
                    <?php foreach ($job_postings as $job): ?>
                        <div class="card" id="job-<?php echo $job['id']; ?>">
                            <div class="card-header">
                                <h4><?php echo Security::escapeHTML($job['title']); ?></h4>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <span class="status-badge status-<?php echo $job['status']; ?>"><?php echo ucfirst($job['status']); ?></span>
                                    <?php if (Security::canWriteInDepartment($_SESSION['role'], $_SESSION['department'], 'HR')): ?>
                                        <div class="job-actions">
                                            <button type="button" class="btn-small btn-edit" onclick="editJob(<?php echo $job['id']; ?>)" title="Edit Job">✏️</button>
                                            <button type="button" class="btn-small btn-danger" onclick="deleteJob(<?php echo $job['id']; ?>)" title="Delete Job">🗑️</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-grid-2col">
                                <div><strong>Department:</strong> <?php echo Security::escapeHTML($job['department']); ?></div>
                                <div><strong>Salary:</strong> <?php echo Security::escapeHTML($job['salary_range']); ?></div>
                                <div><strong>Posted by:</strong> <?php echo Security::escapeHTML($job['posted_by_name']); ?></div>
                                <div><strong>Posted:</strong> <?php echo Utils::formatDate($job['created_at']); ?></div>
                            </div>
                            <div style="margin-top: 1rem;">
                                <strong>Description:</strong><br>
                                <span class="job-description"><?php echo nl2br(Security::escapeHTML($job['description'])); ?></span>
                            </div>
                            <div style="margin-top: 1rem;">
                                <strong>Requirements:</strong><br>
                                <span class="job-requirements"><?php echo nl2br(Security::escapeHTML($job['requirements'])); ?></span>
                            </div>
                            
                            <!-- Edit Form -->
                            <div class="edit-form" id="edit-form-<?php echo $job['id']; ?>" style="display: none;">
                                <h5>Edit Job Posting</h5>
                                <form method="POST" class="form-grid form-grid-2col">
                                    <?php echo Security::getCSRFTokenField(); ?>
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Job Title *</label>
                                        <input type="text" name="title" value="<?php echo Security::escapeHTML($job['title']); ?>" class="form-input" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Department *</label>
                                        <select name="department" class="form-select" required>
                                            <option value="IT" <?php echo $job['department'] === 'IT' ? 'selected' : ''; ?>>IT</option>
                                            <option value="Marketing" <?php echo $job['department'] === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                            <option value="Finance" <?php echo $job['department'] === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                            <option value="HR" <?php echo $job['department'] === 'HR' ? 'selected' : ''; ?>>HR</option>
                                            <option value="Sales" <?php echo $job['department'] === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Salary Range</label>
                                        <input type="text" name="salary_range" value="<?php echo Security::escapeHTML($job['salary_range']); ?>" class="form-input">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active" <?php echo $job['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="closed" <?php echo $job['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                            <option value="draft" <?php echo $job['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group form-group-full">
                                        <label class="form-label">Job Description *</label>
                                        <textarea name="description" rows="4" class="form-textarea" required><?php echo Security::escapeHTML($job['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group form-group-full">
                                        <label class="form-label">Requirements *</label>
                                        <textarea name="requirements" rows="4" class="form-textarea" required><?php echo Security::escapeHTML($job['requirements']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group form-group-full form-actions">
                                        <button type="submit" name="update_job_posting" class="btn-primary">Update Job</button>
                                        <button type="button" class="btn-primary btn-warning" onclick="cancelEdit(<?php echo $job['id']; ?>)">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="section">
                <div class="section-header">
                    <h2>External Candidate Application Form</h2>
                </div>
                <div class="section-content">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="candidate-form">
                        <form method="POST" enctype="multipart/form-data" id="candidateForm">
                            <?php echo Security::getCSRFTokenField(); ?>
                            
                            <!-- Basic Information Section -->
                            <div class="form-section">
                                <div class="form-section-title">📋 Basic Information</div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Job Position *:</label>
                                        <select name="job_posting_id" required>
                                            <option value="">Select Position</option>
                                            <?php foreach ($job_postings as $job): ?>
                                                <option value="<?php echo $job['id']; ?>"><?php echo Security::escapeHTML($job['title'] . ' - ' . $job['department']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>First Name *:</label>
                                        <input type="text" name="first_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Last Name *:</label>
                                        <input type="text" name="last_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email *:</label>
                                        <input type="email" name="email" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Phone:</label>
                                        <input type="tel" name="phone">
                                    </div>
                                    <div class="form-group">
                                        <label>Years of Experience:</label>
                                        <input type="number" name="years_experience" min="0" max="50">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Professional Details Section -->
                            <div class="form-section">
                                <div class="form-section-title">💼 Professional Details</div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Salary Expectation (R):</label>
                                        <input type="number" name="salary_expectation" step="0.01" placeholder="e.g., 65000">
                                    </div>
                                    <div class="form-group">
                                        <label>Available Start Date:</label>
                                        <input type="date" name="availability_date">
                                    </div>
                                    <div class="form-group">
                                        <label>Preferred Location:</label>
                                        <input type="text" name="preferred_location" placeholder="e.g., Cape Town, Remote">
                                    </div>
                                    <div class="form-group">
                                        <label>Work Authorization:</label>
                                        <select name="work_authorization">
                                            <option value="">Select Status</option>
                                            <option value="citizen">South African Citizen</option>
                                            <option value="permanent_resident">Permanent Resident</option>
                                            <option value="work_permit">Work Permit</option>
                                            <option value="student_visa">Student Visa</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>LinkedIn Profile:</label>
                                        <input type="url" name="linkedin_profile" placeholder="https://linkedin.com/in/yourprofile">
                                    </div>
                                    <div class="form-group">
                                        <label>Portfolio Website:</label>
                                        <input type="url" name="portfolio_website" placeholder="https://yourportfolio.com">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: flex; align-items: center;">
                                            <input type="checkbox" name="willing_to_relocate" style="margin-right: 0.5rem;">
                                            Willing to relocate if necessary
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- CV Upload Section -->
                            <div class="form-section">
                                <div class="form-section-title">📄 CV Upload</div>
                                <div class="file-upload-area">
                                    <input type="file" name="resume_file" id="resume_file" accept=".pdf,.doc,.docx" style="display: none;">
                                    <label for="resume_file" style="cursor: pointer;">
                                        <div>📁 Click to upload your CV</div>
                                        <div class="file-info">Supported formats: PDF, DOC, DOCX (Max 5MB)</div>
                                    </label>
                                    <div id="file-name" class="file-info" style="display: none;"></div>
                                </div>
                            </div>
                            
                            <!-- Work Experience Section -->
                            <div class="form-section">
                                <div class="form-section-title">💼 Work Experience</div>
                                <div id="work-experience-container">
                                    <div class="dynamic-section work-experience-item">
                                        <button type="button" class="remove-btn" onclick="removeSection(this)" style="display: none;">×</button>
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label>Company Name *:</label>
                                                <input type="text" name="work_experience[0][company_name]" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Position *:</label>
                                                <input type="text" name="work_experience[0][position]" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Start Date *:</label>
                                                <input type="date" name="work_experience[0][start_date]" required>
                                            </div>
                                            <div class="form-group">
                                                <label>End Date:</label>
                                                <input type="date" name="work_experience[0][end_date]">
                                            </div>
                                            <div class="form-group">
                                                <label style="display: flex; align-items: center;">
                                                    <input type="checkbox" name="work_experience[0][is_current]" style="margin-right: 0.5rem;" onchange="toggleEndDate(this)">
                                                    Currently working here
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Job Description:</label>
                                            <textarea name="work_experience[0][description]" rows="3" placeholder="Describe your responsibilities and achievements"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Skills Used:</label>
                                            <input type="text" name="work_experience[0][skills_used]" placeholder="e.g., JavaScript, Project Management, Leadership">
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="add-btn" onclick="addWorkExperience()">+ Add More Experience</button>
                            </div>
                            
                            <!-- Education Section -->
                            <div class="form-section">
                                <div class="form-section-title">🎓 Education</div>
                                <div id="education-container">
                                    <div class="dynamic-section education-item">
                                        <button type="button" class="remove-btn" onclick="removeSection(this)" style="display: none;">×</button>
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label>Institution Name *:</label>
                                                <input type="text" name="education[0][institution_name]" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Degree Type *:</label>
                                                <select name="education[0][degree_type]" required>
                                                    <option value="">Select Degree</option>
                                                    <option value="matric">Matric Certificate</option>
                                                    <option value="diploma">Diploma</option>
                                                    <option value="bachelor">Bachelor's Degree</option>
                                                    <option value="honors">Honours Degree</option>
                                                    <option value="master">Master's Degree</option>
                                                    <option value="doctorate">Doctorate</option>
                                                    <option value="certificate">Certificate</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Field of Study *:</label>
                                                <input type="text" name="education[0][field_of_study]" required placeholder="e.g., Computer Science">
                                            </div>
                                            <div class="form-group">
                                                <label>Start Date *:</label>
                                                <input type="date" name="education[0][start_date]" required>
                                            </div>
                                            <div class="form-group">
                                                <label>End Date:</label>
                                                <input type="date" name="education[0][end_date]">
                                            </div>
                                            <div class="form-group">
                                                <label style="display: flex; align-items: center;">
                                                    <input type="checkbox" name="education[0][is_current]" style="margin-right: 0.5rem;" onchange="toggleEndDate(this)">
                                                    Currently studying here
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label>Grade/GPA:</label>
                                                <input type="text" name="education[0][grade_gpa]" placeholder="e.g., 3.8 GPA, 75%">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Achievements:</label>
                                            <textarea name="education[0][achievements]" rows="2" placeholder="Awards, honors, relevant coursework"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="add-btn" onclick="addEducation()">+ Add More Education</button>
                            </div>
                            
                            <!-- Cover Letter Section -->
                            <div class="form-section">
                                <div class="form-section-title">✍️ Cover Letter</div>
                                <div class="form-group">
                                    <label>Cover Letter:</label>
                                    <textarea name="cover_letter" rows="6" placeholder="Tell us why you're interested in this position and what makes you a great fit..."></textarea>
                                </div>
                            </div>
                            
                            <div style="text-align: center; margin-top: 2rem;">
                                <button type="submit" name="create_candidate" class="btn" style="padding: 1rem 2rem; font-size: 1.1rem;">Submit Application</button>
                            </div>
                        </form>
                    </div>
                    
                    <h3>Candidate Applications</h3>
                    <?php foreach ($candidates as $candidate): ?>
                        <div class="candidate-card">
                            <div class="candidate-header">
                                <h4><?php echo Security::escapeHTML($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h4>
                                <div>
                                    <span class="status-badge status-<?php echo $candidate['status']; ?>"><?php echo ucfirst($candidate['status']); ?></span>
                                    <?php if ($candidate['resume_file']): ?>
                                        <a href="<?php echo FileUpload::getFileUrl($candidate['resume_file']); ?>" target="_blank" class="btn btn-small" style="margin-left: 0.5rem;">📄 View CV</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="candidate-details">
                                <div><strong>Position:</strong> <?php echo Security::escapeHTML($candidate['job_title']); ?></div>
                                <div><strong>Department:</strong> <?php echo Security::escapeHTML($candidate['job_department']); ?></div>
                                <div><strong>Email:</strong> <?php echo Security::escapeHTML($candidate['email']); ?></div>
                                <div><strong>Phone:</strong> <?php echo Security::escapeHTML($candidate['phone']); ?></div>
                                <div><strong>Applied:</strong> <?php echo Utils::formatDate($candidate['created_at']); ?></div>
                                <?php if (isset($candidate['years_experience']) && $candidate['years_experience']): ?>
                                    <div><strong>Experience:</strong> <?php echo $candidate['years_experience']; ?> years</div>
                                <?php endif; ?>
                                <?php if (isset($candidate['salary_expectation']) && $candidate['salary_expectation']): ?>
                                    <div><strong>Salary Expectation:</strong> <?php echo Utils::formatCurrency($candidate['salary_expectation']); ?></div>
                                <?php endif; ?>
                                <?php if (isset($candidate['availability_date']) && $candidate['availability_date']): ?>
                                    <div><strong>Available From:</strong> <?php echo Utils::formatDate($candidate['availability_date']); ?></div>
                                <?php endif; ?>
                                <?php if (isset($candidate['preferred_location']) && $candidate['preferred_location']): ?>
                                    <div><strong>Location:</strong> <?php echo Security::escapeHTML($candidate['preferred_location']); ?></div>
                                <?php endif; ?>
                                <?php if (isset($candidate['work_authorization']) && $candidate['work_authorization']): ?>
                                    <div><strong>Work Authorization:</strong> <?php echo ucfirst(str_replace('_', ' ', $candidate['work_authorization'])); ?></div>
                                <?php endif; ?>
                                <?php if (isset($candidate['willing_to_relocate']) && $candidate['willing_to_relocate']): ?>
                                    <div><strong>Willing to Relocate:</strong> Yes</div>
                                <?php endif; ?>
                                <?php if (isset($candidate['linkedin_profile']) && $candidate['linkedin_profile']): ?>
                                    <div><strong>LinkedIn:</strong> <a href="<?php echo Security::escapeHTML($candidate['linkedin_profile']); ?>" target="_blank">View Profile</a></div>
                                <?php endif; ?>
                                <?php if (isset($candidate['portfolio_website']) && $candidate['portfolio_website']): ?>
                                    <div><strong>Portfolio:</strong> <a href="<?php echo Security::escapeHTML($candidate['portfolio_website']); ?>" target="_blank">View Website</a></div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($candidate['work_experience'])): ?>
                                <div style="margin-top: 1.5rem;">
                                    <h5 style="margin-bottom: 1rem; color: #333;">💼 Work Experience</h5>
                                    <?php foreach ($candidate['work_experience'] as $exp): ?>
                                        <div class="experience-item">
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                                <div>
                                                    <strong><?php echo Security::escapeHTML($exp['position']); ?></strong>
                                                    <div style="color: #007bff; font-weight: 500;"><?php echo Security::escapeHTML($exp['company_name']); ?></div>
                                                </div>
                                                <div style="font-size: 0.9rem; color: #666;">
                                                    <?php echo Utils::formatDate($exp['start_date']); ?> - 
                                                    <?php echo $exp['is_current'] ? 'Present' : ($exp['end_date'] ? Utils::formatDate($exp['end_date']) : 'N/A'); ?>
                                                </div>
                                            </div>
                                            <?php if ($exp['description']): ?>
                                                <div style="margin-bottom: 0.5rem; color: #555;">
                                                    <?php echo nl2br(Security::escapeHTML($exp['description'])); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($exp['skills_used']): ?>
                                                <div style="font-size: 0.9rem;">
                                                    <strong>Skills:</strong> <?php echo Security::escapeHTML($exp['skills_used']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($candidate['education'])): ?>
                                <div style="margin-top: 1.5rem;">
                                    <h5 style="margin-bottom: 1rem; color: #333;">🎓 Education</h5>
                                    <?php foreach ($candidate['education'] as $edu): ?>
                                        <div class="education-item">
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                                <div>
                                                    <strong><?php echo Security::escapeHTML($edu['field_of_study']); ?></strong>
                                                    <div style="color: #007bff; font-weight: 500;"><?php echo Security::escapeHTML($edu['institution_name']); ?></div>
                                                    <div style="font-size: 0.9rem; color: #666;">
                                                        <?php echo ucfirst(str_replace('_', ' ', $edu['degree_type'])); ?>
                                                        <?php if ($edu['grade_gpa']): ?>
                                                            - <?php echo Security::escapeHTML($edu['grade_gpa']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div style="font-size: 0.9rem; color: #666;">
                                                    <?php echo Utils::formatDate($edu['start_date']); ?> - 
                                                    <?php echo $edu['is_current'] ? 'Present' : ($edu['end_date'] ? Utils::formatDate($edu['end_date']) : 'N/A'); ?>
                                                </div>
                                            </div>
                                            <?php if ($edu['achievements']): ?>
                                                <div style="font-size: 0.9rem; color: #555;">
                                                    <strong>Achievements:</strong> <?php echo nl2br(Security::escapeHTML($edu['achievements'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($candidate['cover_letter']): ?>
                                <div style="margin-top: 1.5rem;">
                                    <h5 style="margin-bottom: 0.5rem; color: #333;">✍️ Cover Letter</h5>
                                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; font-style: italic; color: #555;">
                                        <?php echo nl2br(Security::escapeHTML($candidate['cover_letter'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($candidates)): ?>
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <p>No candidate applications yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Job posting management functions
        function editJob(jobId) {
            const editForm = document.getElementById('edit-form-' + jobId);
            const isVisible = editForm.style.display !== 'none';
            
            // Hide all edit forms first
            document.querySelectorAll('.edit-form').forEach(form => {
                form.style.display = 'none';
            });
            
            // Toggle the clicked form
            if (!isVisible) {
                editForm.style.display = 'block';
                editForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
        
        function cancelEdit(jobId) {
            document.getElementById('edit-form-' + jobId).style.display = 'none';
        }
        
        function deleteJob(jobId) {
            if (confirm('Are you sure you want to delete this job posting? This will also delete all related candidate applications.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="delete_job_posting" value="1">
                    <input type="hidden" name="job_id" value="${jobId}">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Tab functionality
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
        
        // File upload functionality
        document.getElementById('resume_file')?.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const fileNameDiv = document.getElementById('file-name');
            if (fileName) {
                fileNameDiv.textContent = `Selected: ${fileName}`;
                fileNameDiv.style.display = 'block';
            } else {
                fileNameDiv.style.display = 'none';
            }
        });
        
        // Dynamic work experience functionality
        let workExperienceCount = 1;
        
        function addWorkExperience() {
            const container = document.getElementById('work-experience-container');
            const newItem = document.createElement('div');
            newItem.className = 'dynamic-section work-experience-item';
            newItem.innerHTML = `
                <button type="button" class="remove-btn" onclick="removeSection(this)">×</button>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Company Name *:</label>
                        <input type="text" name="work_experience[${workExperienceCount}][company_name]" required>
                    </div>
                    <div class="form-group">
                        <label>Position *:</label>
                        <input type="text" name="work_experience[${workExperienceCount}][position]" required>
                    </div>
                    <div class="form-group">
                        <label>Start Date *:</label>
                        <input type="date" name="work_experience[${workExperienceCount}][start_date]" required>
                    </div>
                    <div class="form-group">
                        <label>End Date:</label>
                        <input type="date" name="work_experience[${workExperienceCount}][end_date]">
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" name="work_experience[${workExperienceCount}][is_current]" style="margin-right: 0.5rem;" onchange="toggleEndDate(this)">
                            Currently working here
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Job Description:</label>
                    <textarea name="work_experience[${workExperienceCount}][description]" rows="3" placeholder="Describe your responsibilities and achievements"></textarea>
                </div>
                <div class="form-group">
                    <label>Skills Used:</label>
                    <input type="text" name="work_experience[${workExperienceCount}][skills_used]" placeholder="e.g., JavaScript, Project Management, Leadership">
                </div>
            `;
            container.appendChild(newItem);
            workExperienceCount++;
            updateRemoveButtons('work-experience-item');
        }
        
        // Dynamic education functionality
        let educationCount = 1;
        
        function addEducation() {
            const container = document.getElementById('education-container');
            const newItem = document.createElement('div');
            newItem.className = 'dynamic-section education-item';
            newItem.innerHTML = `
                <button type="button" class="remove-btn" onclick="removeSection(this)">×</button>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Institution Name *:</label>
                        <input type="text" name="education[${educationCount}][institution_name]" required>
                    </div>
                    <div class="form-group">
                        <label>Degree Type *:</label>
                        <select name="education[${educationCount}][degree_type]" required>
                            <option value="">Select Degree</option>
                            <option value="matric">Matric Certificate</option>
                            <option value="diploma">Diploma</option>
                            <option value="bachelor">Bachelor's Degree</option>
                            <option value="honors">Honours Degree</option>
                            <option value="master">Master's Degree</option>
                            <option value="doctorate">Doctorate</option>
                            <option value="certificate">Certificate</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Field of Study *:</label>
                        <input type="text" name="education[${educationCount}][field_of_study]" required placeholder="e.g., Computer Science">
                    </div>
                    <div class="form-group">
                        <label>Start Date *:</label>
                        <input type="date" name="education[${educationCount}][start_date]" required>
                    </div>
                    <div class="form-group">
                        <label>End Date:</label>
                        <input type="date" name="education[${educationCount}][end_date]">
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" name="education[${educationCount}][is_current]" style="margin-right: 0.5rem;" onchange="toggleEndDate(this)">
                            Currently studying here
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Grade/GPA:</label>
                        <input type="text" name="education[${educationCount}][grade_gpa]" placeholder="e.g., 3.8 GPA, 75%">
                    </div>
                </div>
                <div class="form-group">
                    <label>Achievements:</label>
                    <textarea name="education[${educationCount}][achievements]" rows="2" placeholder="Awards, honors, relevant coursework"></textarea>
                </div>
            `;
            container.appendChild(newItem);
            educationCount++;
            updateRemoveButtons('education-item');
        }
        
        // Remove section functionality
        function removeSection(button) {
            const section = button.closest('.dynamic-section');
            const container = section.parentNode;
            section.remove();
            
            // Update remove buttons visibility
            if (container.id === 'work-experience-container') {
                updateRemoveButtons('work-experience-item');
            } else if (container.id === 'education-container') {
                updateRemoveButtons('education-item');
            }
        }
        
        // Update remove buttons visibility
        function updateRemoveButtons(className) {
            const items = document.querySelectorAll(`.${className}`);
            items.forEach((item, index) => {
                const removeBtn = item.querySelector('.remove-btn');
                if (removeBtn) {
                    removeBtn.style.display = items.length > 1 ? 'block' : 'none';
                }
            });
        }
        
        // Toggle end date when current position/education is checked
        function toggleEndDate(checkbox) {
            const container = checkbox.closest('.form-grid') || checkbox.closest('.dynamic-section');
            const endDateInput = container.querySelector('input[type="date"][name*="[end_date]"]');
            if (endDateInput) {
                if (checkbox.checked) {
                    endDateInput.disabled = true;
                    endDateInput.value = '';
                    endDateInput.style.opacity = '0.5';
                } else {
                    endDateInput.disabled = false;
                    endDateInput.style.opacity = '1';
                }
            }
        }
        
        // Form validation
        document.getElementById('candidateForm')?.addEventListener('submit', function(e) {
            // Check if at least one work experience is filled
            const workExpItems = document.querySelectorAll('.work-experience-item');
            let hasWorkExp = false;
            workExpItems.forEach(item => {
                const companyName = item.querySelector('input[name*="[company_name]"]').value.trim();
                const position = item.querySelector('input[name*="[position]"]').value.trim();
                if (companyName && position) {
                    hasWorkExp = true;
                }
            });
            
            // Check if at least one education is filled
            const eduItems = document.querySelectorAll('.education-item');
            let hasEdu = false;
            eduItems.forEach(item => {
                const institutionName = item.querySelector('input[name*="[institution_name]"]').value.trim();
                const degreeType = item.querySelector('select[name*="[degree_type]"]').value.trim();
                if (institutionName && degreeType) {
                    hasEdu = true;
                }
            });
            
            if (!hasWorkExp && !hasEdu) {
                e.preventDefault();
                alert('Please fill in at least one work experience or education entry.');
                return false;
            }
        });
        
        // Employee View/Edit Functions
        function viewEmployee(employeeId) {
            // Fetch employee data securely via AJAX
            fetch(`?ajax=view_employee&id=${employeeId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Employee not found');
                        return;
                    }
                    
                    const employee = data.employee;
                    
                    // Create modal content using DOM API (XSS-safe)
                    const modalDiv = document.createElement('div');
                    modalDiv.style.padding = '2rem';
                    
                    const title = document.createElement('h3');
                    title.textContent = 'Employee Details';
                    modalDiv.appendChild(title);
                    
                    const grid = document.createElement('div');
                    grid.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;';
                    
                    // Safely create each field using textContent (no XSS risk)
                    const fields = [
                        ['ID:', employee.employee_id || 'N/A'],
                        ['Status:', employee.status || 'N/A'],
                        ['Name:', (employee.first_name || '') + ' ' + (employee.last_name || '')],
                        ['Email:', employee.email || 'N/A'],
                        ['Phone:', employee.phone || 'N/A'],
                        ['Position:', employee.position || 'N/A'],
                        ['Department:', employee.department || 'N/A'],
                        ['Role:', employee.role || 'Not Assigned'],
                        ['Hire Date:', employee.hire_date || 'N/A'],
                        ['Salary:', employee.salary ? '$' + parseFloat(employee.salary).toLocaleString() : 'N/A'],
                        ['Manager:', employee.manager_name || 'No Manager']
                    ];
                    
                    fields.forEach(([label, value]) => {
                        const fieldDiv = document.createElement('div');
                        const strong = document.createElement('strong');
                        strong.textContent = label + ' ';
                        fieldDiv.appendChild(strong);
                        fieldDiv.appendChild(document.createTextNode(value));
                        grid.appendChild(fieldDiv);
                    });
                    
                    modalDiv.appendChild(grid);
                    
                    // Add buttons
                    const buttonDiv = document.createElement('div');
                    buttonDiv.style.cssText = 'margin-top: 2rem; text-align: right;';
                    
                    const closeBtn = document.createElement('button');
                    closeBtn.type = 'button';
                    closeBtn.className = 'btn btn-secondary';
                    closeBtn.textContent = 'Close';
                    closeBtn.onclick = closeModal;
                    
                    const editBtn = document.createElement('button');
                    editBtn.type = 'button';
                    editBtn.className = 'btn';
                    editBtn.textContent = 'Edit';
                    editBtn.onclick = () => { closeModal(); editEmployee(employeeId); };
                    
                    buttonDiv.appendChild(closeBtn);
                    buttonDiv.appendChild(document.createTextNode(' '));
                    buttonDiv.appendChild(editBtn);
                    modalDiv.appendChild(buttonDiv);
                    
                    showModal(modalDiv);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load employee data');
                });
        }
        
        function editEmployee(employeeId) {
            // Fetch employee data securely via AJAX (same as view)
            fetch(`?ajax=view_employee&id=${employeeId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Employee not found');
                        return;
                    }
                    
                    const employee = data.employee;
                    
                    // Create edit form using DOM API (XSS-safe)
                    const modalDiv = document.createElement('div');
                    modalDiv.style.padding = '2rem';
                    
                    const title = document.createElement('h3');
                    title.textContent = 'Edit Employee';
                    modalDiv.appendChild(title);
                    
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.className = 'form-grid';
                    form.style.marginTop = '1rem';
                    
                    // Add CSRF token
                    form.innerHTML = '<?php echo Security::getCSRFTokenField(); ?>';
                    
                    // Add hidden employee ID
                    const hiddenId = document.createElement('input');
                    hiddenId.type = 'hidden';
                    hiddenId.name = 'employee_id';
                    hiddenId.value = employee.id;
                    form.appendChild(hiddenId);
                    
                    // Create form fields safely
                    const fields = [
                        {label: 'First Name', name: 'first_name', type: 'text', value: employee.first_name, required: true},
                        {label: 'Last Name', name: 'last_name', type: 'text', value: employee.last_name, required: true},
                        {label: 'Email', name: 'email', type: 'email', value: employee.email, required: true},
                        {label: 'Phone', name: 'phone', type: 'tel', value: employee.phone},
                        {label: 'Position', name: 'position', type: 'text', value: employee.position, required: true},
                        {label: 'Role', name: 'role', type: 'select', value: employee.role, department: employee.department},
                        {label: 'Salary', name: 'salary', type: 'number', value: employee.salary, step: '0.01'}
                    ];
                    
                    fields.forEach(field => {
                        const div = document.createElement('div');
                        div.className = 'form-group';
                        
                        const label = document.createElement('label');
                        label.textContent = field.label + ':';
                        div.appendChild(label);
                        
                        if (field.type === 'select' && field.name === 'role') {
                            // Department-specific role dropdown
                            const select = document.createElement('select');
                            select.name = field.name;
                            
                            // Add default option
                            const defaultOption = document.createElement('option');
                            defaultOption.value = '';
                            defaultOption.textContent = 'Select Role';
                            select.appendChild(defaultOption);
                            
                            // Define department-specific roles
                            const rolesByDept = {
                                'IT': ['Junior Developer', 'Senior Developer', 'Tech Lead', 'DevOps Engineer', 'System Administrator', 'QA Engineer'],
                                'Marketing': ['Marketing Coordinator', 'Marketing Manager', 'Digital Marketing Specialist', 'Content Creator', 'SEO Specialist', 'Campaign Manager'],
                                'Finance': ['Financial Analyst', 'Senior Accountant', 'Finance Manager', 'Budget Analyst', 'Tax Specialist', 'Auditor'],
                                'HR': ['HR Specialist', 'Recruiter', 'HR Manager', 'Training Coordinator', 'Benefits Administrator', 'Employee Relations'],
                                'Sales': ['Sales Representative', 'Senior Sales Rep', 'Sales Manager', 'Account Manager', 'Business Development', 'Customer Success']
                            };
                            
                            const roles = rolesByDept[field.department] || [];
                            roles.forEach(role => {
                                const option = document.createElement('option');
                                option.value = role;
                                option.textContent = role;
                                if (role === field.value) option.selected = true;
                                select.appendChild(option);
                            });
                            
                            div.appendChild(select);
                        } else {
                            // Regular input field
                            const input = document.createElement('input');
                            input.type = field.type;
                            input.name = field.name;
                            input.value = field.value || '';
                            if (field.required) input.required = true;
                            if (field.step) input.step = field.step;
                            div.appendChild(input);
                        }
                        
                        form.appendChild(div);
                    });
                    
                    // Department dropdown
                    const deptDiv = document.createElement('div');
                    deptDiv.className = 'form-group';
                    const deptLabel = document.createElement('label');
                    deptLabel.textContent = 'Department:';
                    deptDiv.appendChild(deptLabel);
                    
                    const deptSelect = document.createElement('select');
                    deptSelect.name = 'department';
                    deptSelect.required = true;
                    
                    ['', 'IT', 'Marketing', 'Finance', 'HR', 'Sales'].forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept;
                        option.textContent = dept || 'Select Department';
                        if (dept === employee.department) option.selected = true;
                        deptSelect.appendChild(option);
                    });
                    
                    deptDiv.appendChild(deptSelect);
                    form.appendChild(deptDiv);
                    
                    // Status dropdown
                    const statusDiv = document.createElement('div');
                    statusDiv.className = 'form-group';
                    const statusLabel = document.createElement('label');
                    statusLabel.textContent = 'Status:';
                    statusDiv.appendChild(statusLabel);
                    
                    const statusSelect = document.createElement('select');
                    statusSelect.name = 'status';
                    statusSelect.required = true;
                    
                    [['active', 'Active'], ['inactive', 'Inactive'], ['terminated', 'Terminated']].forEach(([value, text]) => {
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = text;
                        if (value === employee.status) option.selected = true;
                        statusSelect.appendChild(option);
                    });
                    
                    statusDiv.appendChild(statusSelect);
                    form.appendChild(statusDiv);
                    
                    // Manager dropdown (department-based)
                    const mgmtDiv = document.createElement('div');
                    mgmtDiv.className = 'form-group';
                    const mgmtLabel = document.createElement('label');
                    mgmtLabel.textContent = 'Manager:';
                    mgmtDiv.appendChild(mgmtLabel);
                    
                    const mgmtSelect = document.createElement('select');
                    mgmtSelect.name = 'manager_id';
                    
                    const noMgrOption = document.createElement('option');
                    noMgrOption.value = '';
                    noMgrOption.textContent = 'No Manager';
                    mgmtSelect.appendChild(noMgrOption);
                    
                    // Fetch department managers via AJAX
                    fetch(`?ajax=get_department_managers&dept=${employee.department}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                data.managers.forEach(manager => {
                                    const option = document.createElement('option');
                                    option.value = manager.id;
                                    option.textContent = manager.first_name + ' ' + manager.last_name;
                                    if (manager.id == employee.manager_id) option.selected = true;
                                    mgmtSelect.appendChild(option);
                                });
                            }
                        });
                    
                    mgmtDiv.appendChild(mgmtSelect);
                    form.appendChild(mgmtDiv);
                    
                    // Buttons
                    const buttonDiv = document.createElement('div');
                    buttonDiv.style.cssText = 'grid-column: 1 / -1; margin-top: 1rem; text-align: right;';
                    
                    const cancelBtn = document.createElement('button');
                    cancelBtn.type = 'button';
                    cancelBtn.className = 'btn btn-secondary';
                    cancelBtn.textContent = 'Cancel';
                    cancelBtn.onclick = closeModal;
                    
                    const updateBtn = document.createElement('button');
                    updateBtn.type = 'submit';
                    updateBtn.name = 'update_employee';
                    updateBtn.className = 'btn';
                    updateBtn.textContent = 'Update Employee';
                    
                    buttonDiv.appendChild(cancelBtn);
                    buttonDiv.appendChild(document.createTextNode(' '));
                    buttonDiv.appendChild(updateBtn);
                    form.appendChild(buttonDiv);
                    
                    modalDiv.appendChild(form);
                    showModal(modalDiv);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load employee data');
                });
        }
        
        function showModal(content) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                background: rgba(0,0,0,0.5); z-index: 1000; display: flex; 
                align-items: center; justify-content: center;
            `;
            
            const modalBox = document.createElement('div');
            modalBox.style.cssText = `
                background: white; border-radius: 8px; max-width: 600px; 
                width: 90%; max-height: 90%; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            `;
            
            // Safely append content (can be DOM element or HTML string)
            if (typeof content === 'string') {
                modalBox.innerHTML = content;
            } else {
                modalBox.appendChild(content);
            }
            
            modal.appendChild(modalBox);
            document.body.appendChild(modal);
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }
        
        function closeModal() {
            const modal = document.querySelector('.modal');
            if (modal) {
                modal.remove();
            }
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

        // Initialize remove button visibility on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateRemoveButtons('work-experience-item');
            updateRemoveButtons('education-item');
        });
    </script>

    <script src="../js/notification.js"></script>  
</body>
</html>