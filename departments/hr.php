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

Security::requireDepartmentAccess('HR');

$database = new Database();
$db = $database->getConnection();

$sess_user_id = $_SESSION['user_id'];
$sess_role    = $_SESSION['role'];

// ── AJAX: department managers ────────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_department_managers') {
    $dept = Security::sanitizeInput($_GET['dept'] ?? '');
    $stmt = $db->prepare("SELECT id, first_name, last_name FROM hr_employees WHERE department=? AND status='active' AND id!=? ORDER BY first_name, last_name");
    $stmt->execute([$dept, $_GET['exclude_id'] ?? 0]);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'managers' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit();
}

// ── AJAX: view employee ──────────────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'view_employee') {
    $eid = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT e.*, CONCAT(m.first_name,' ',m.last_name) AS manager_name
                          FROM hr_employees e LEFT JOIN hr_employees m ON e.manager_id=m.id WHERE e.id=?");
    $stmt->execute([$eid]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($emp ? ['success' => true, 'employee' => $emp] : ['success' => false]);
    exit();
}

// ── Update employee ──────────────────────────────────────────────────────────
if ($_POST && isset($_POST['update_employee'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    $id          = (int)$_POST['employee_id'];
    $first_name  = Security::sanitizeInput($_POST['first_name']);
    $last_name   = Security::sanitizeInput($_POST['last_name']);
    $email       = Security::sanitizeInput($_POST['email']);
    $phone       = Security::sanitizeInput($_POST['phone']);
    $position    = Security::sanitizeInput($_POST['position']);
    $department  = Security::sanitizeInput($_POST['department']);
    $role        = Security::sanitizeInput($_POST['role']);
    $salary      = floatval($_POST['salary']);
    $status      = Security::sanitizeInput($_POST['status']);
    $manager_id  = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
    $user_id_lnk = !empty($_POST['user_id_link']) ? (int)$_POST['user_id_link'] : null;

    if (!in_array($status, ['active','inactive','terminated'])) $status = 'active';
    if ($manager_id === $id) $manager_id = null;
    if ($manager_id) {
        $chk = $db->prepare("SELECT id FROM hr_employees WHERE id=?");
        $chk->execute([$manager_id]);
        if (!$chk->fetch()) $manager_id = null;
    }
    // Validate user_id_lnk — must not already be used by another employee
    if ($user_id_lnk) {
        $chk = $db->prepare("SELECT id FROM hr_employees WHERE user_id=? AND id!=?");
        $chk->execute([$user_id_lnk, $id]);
        if ($chk->fetch()) $user_id_lnk = null;
    }

    $stmt = $db->prepare("UPDATE hr_employees SET first_name=?,last_name=?,email=?,phone=?,position=?,department=?,role=?,salary=?,status=?,manager_id=?,user_id=?,updated_at=CURRENT_TIMESTAMP WHERE id=?");
    $stmt->execute([$first_name,$last_name,$email,$phone,$position,$department,$role,$salary,$status,$manager_id,$user_id_lnk,$id]);
    try {
        require_once '../includes/ActivityLogger.php';
        (new ActivityLogger($db))->logEdit('employee',$id,"Updated employee: {$first_name} {$last_name}",['status'=>$status]);
    } catch(Exception $e) { error_log($e->getMessage()); }
    $success_message = "Employee updated.";
}

// ── Create employee (auto-creates linked user account) ───────────────────────
if ($_POST && isset($_POST['create_employee'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');

    $employee_id = 'EMP-'.str_pad(rand(1000,9999),4,'0',STR_PAD_LEFT);
    $first_name  = Security::sanitizeInput($_POST['first_name']);
    $last_name   = Security::sanitizeInput($_POST['last_name']);
    $email       = Security::sanitizeInput($_POST['email']);
    $phone       = Security::sanitizeInput($_POST['phone']);
    $position    = Security::sanitizeInput($_POST['position']);
    $department  = Security::sanitizeInput($_POST['department']);
    $role        = Security::sanitizeInput($_POST['role']);
    $hire_date   = Security::sanitizeInput($_POST['hire_date']);
    $salary      = floatval($_POST['salary']);
    $manager_id  = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;

    try {
        $db->beginTransaction();

        // Generate unique username: firstname_lastname, append number if taken
        $base_username = strtolower(preg_replace('/[^a-z0-9]/i', '', $first_name).'_'.preg_replace('/[^a-z0-9]/i', '', $last_name));
        $username = $base_username;
        $suffix   = 2;
        while (true) {
            $chk = $db->prepare("SELECT id FROM users WHERE username=?");
            $chk->execute([$username]);
            if (!$chk->fetch()) break;
            $username = $base_username.$suffix++;
        }

        // Temporary password: Welcome + last 4 of employee_id (e.g. Welcome1234!)
        $tmp_suffix   = substr($employee_id, -4);
        $tmp_password = 'Welcome'.$tmp_suffix.'!';
        $hashed       = password_hash($tmp_password, PASSWORD_BCRYPT);

        // Map HR department name to users.department enum
        $dept_map = ['IT'=>'IT','Marketing'=>'Marketing','Finance'=>'Finance','HR'=>'HR','Business Development'=>'IT'];
        $user_dept = $dept_map[$department] ?? 'IT';
        $user_role = in_array($role, ['admin','manager','employee']) ? $role : 'employee';

        // Create user account
        $stmt = $db->prepare("INSERT INTO users (username,email,password,role,department) VALUES (?,?,?,?,?)");
        $stmt->execute([$username, $email, $hashed, $user_role, $user_dept]);
        $new_user_id = $db->lastInsertId();

        // Create employee record, linked to the new user
        $stmt = $db->prepare("INSERT INTO hr_employees (employee_id,first_name,last_name,email,phone,position,department,role,hire_date,salary,manager_id,user_id,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'active')");
        $stmt->execute([$employee_id,$first_name,$last_name,$email,$phone,$position,$department,$role,$hire_date,$salary,$manager_id,$new_user_id]);
        $new_id = $db->lastInsertId();

        $db->commit();

        try {
            require_once '../includes/ActivityLogger.php';
            (new ActivityLogger($db))->logCreate('employee',$new_id,"Created employee: {$first_name} {$last_name}",['employee_id'=>$employee_id,'username'=>$username]);
        } catch(Exception $e) { error_log($e->getMessage()); }

        $success_message = "Employee <strong>{$first_name} {$last_name}</strong> created. Portal account: <code>{$username}</code> / Temp password: <code>{$tmp_password}</code> — share with employee and ask them to update via My Profile.";

    } catch(Exception $e) {
        $db->rollBack();
        $error_message = "Failed to create employee: ".$e->getMessage();
    }
}

// ── Create leave request (HR-initiated) ─────────────────────────────────────
if ($_POST && isset($_POST['create_leave_request'])) {
    Security::checkCSRFToken();
    $emp_id     = (int)$_POST['employee_id'];
    $leave_type = Security::sanitizeInput($_POST['leave_type']);
    $start_date = Security::sanitizeInput($_POST['start_date']);
    $end_date   = Security::sanitizeInput($_POST['end_date']);
    $reason     = Security::sanitizeInput($_POST['reason']);
    $days       = (new DateTime($start_date))->diff(new DateTime($end_date))->days + 1;
    $stmt = $db->prepare("INSERT INTO hr_leave_requests (employee_id,leave_type,start_date,end_date,days_requested,reason) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$emp_id,$leave_type,$start_date,$end_date,$days,$reason]);
    // Notify all HR managers
    $emp_name_r = $db->prepare("SELECT CONCAT(first_name,' ',last_name) as name FROM hr_employees WHERE id=?");
    $emp_name_r->execute([$emp_id]);
    $emp_name = $emp_name_r->fetchColumn() ?: 'An employee';
    Notifications::sendToDept($db, 'HR', 'leave', "New leave request",
        "$emp_name requested $days day(s) of $leave_type leave starting $start_date.",
        'hr.php?tab=leave');
    $success_message = "Leave request created.";
}

// ── Update leave status ──────────────────────────────────────────────────────
if ($_POST && isset($_POST['update_leave_status'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    $leave_id = (int)$_POST['leave_id'];
    $status   = Security::sanitizeInput($_POST['status']);
    if (!in_array($status, ['approved','rejected','cancelled'])) $status = 'pending';
    $stmt = $db->prepare("UPDATE hr_leave_requests SET status=?,approved_by=? WHERE id=?");
    $stmt->execute([$status,$sess_user_id,$leave_id]);
    // Notify the employee's linked user account
    $lr_r = $db->prepare("SELECT lr.leave_type, lr.start_date, e.user_id
        FROM hr_leave_requests lr JOIN hr_employees e ON lr.employee_id=e.id WHERE lr.id=?");
    $lr_r->execute([$leave_id]);
    $lr_row = $lr_r->fetch(PDO::FETCH_ASSOC);
    if ($lr_row && $lr_row['user_id']) {
        $icon  = $status === 'approved' ? 'success' : 'warning';
        $msg   = ucfirst($status)." — {$lr_row['leave_type']} from {$lr_row['start_date']}";
        Notifications::send($db, $lr_row['user_id'], $icon, "Leave request $status", $msg, '../profile.php?tab=leave');
    }
    $success_message = "Leave request ".ucfirst($status).".";
}

// ── Create performance review ────────────────────────────────────────────────
if ($_POST && isset($_POST['create_performance_review'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    $emp_id      = (int)$_POST['employee_id'];
    $reviewer_id = (int)$_POST['reviewer_id'];
    $start_date  = Security::sanitizeInput($_POST['start_date']);
    $end_date    = Security::sanitizeInput($_POST['end_date']);
    $rating      = max(1, min(5, (int)$_POST['overall_rating']));
    $goals       = Security::sanitizeInput($_POST['goals_achievement']);
    $strengths   = Security::sanitizeInput($_POST['strengths']);
    $improvements= Security::sanitizeInput($_POST['areas_for_improvement']);
    $comments    = Security::sanitizeInput($_POST['comments']);
    $stmt = $db->prepare("INSERT INTO performance_reviews (employee_id,reviewer_id,review_period_start,review_period_end,overall_rating,goals_achievement,strengths,areas_for_improvement,comments,status) VALUES (?,?,?,?,?,?,?,?,?,'published')");
    $stmt->execute([$emp_id,$reviewer_id,$start_date,$end_date,$rating,$goals,$strengths,$improvements,$comments]);
    try {
        require_once '../includes/ActivityLogger.php';
        (new ActivityLogger($db))->logCreate('performance_review',$db->lastInsertId(),"Created review for employee #{$emp_id}",['rating'=>$rating]);
    } catch(Exception $e) { error_log($e->getMessage()); }
    $success_message = "Performance review saved.";
}

// ── Update job posting ───────────────────────────────────────────────────────
if ($_POST && isset($_POST['update_job_posting'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    $job_id      = (int)$_POST['job_id'];
    $title       = Security::sanitizeInput($_POST['title']);
    $department  = Security::sanitizeInput($_POST['department']);
    $description = Security::sanitizeInput($_POST['description']);
    $requirements= Security::sanitizeInput($_POST['requirements']);
    $salary_range= Security::sanitizeInput($_POST['salary_range']);
    $status      = Security::sanitizeInput($_POST['status']);
    $allowed_depts = ['IT','Marketing','Finance','HR','Business Development'];
    if (!in_array($department, $allowed_depts)) $department = 'IT';
    if (!in_array($status, ['active','closed','draft'])) $status = 'active';
    $stmt = $db->prepare("UPDATE job_postings SET title=?,department=?,description=?,requirements=?,salary_range=?,status=? WHERE id=?");
    $stmt->execute([$title,$department,$description,$requirements,$salary_range,$status,$job_id]);
    $success_message = "Job posting updated.";
}

// ── Delete job posting ───────────────────────────────────────────────────────
if ($_POST && isset($_POST['delete_job_posting'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    $job_id = (int)$_POST['job_id'];
    $db->prepare("DELETE FROM candidates WHERE job_posting_id=?")->execute([$job_id]);
    $db->prepare("DELETE FROM job_postings WHERE id=?")->execute([$job_id]);
    $success_message = "Job posting deleted.";
}

// ── Create job posting ───────────────────────────────────────────────────────
if ($_POST && isset($_POST['create_job_posting'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('HR');
    $title       = Security::sanitizeInput($_POST['title']);
    $department  = Security::sanitizeInput($_POST['department']);
    $description = Security::sanitizeInput($_POST['description']);
    $requirements= Security::sanitizeInput($_POST['requirements']);
    $salary_range= Security::sanitizeInput($_POST['salary_range']);
    $allowed_depts = ['IT','Marketing','Finance','HR','Business Development'];
    if (!in_array($department, $allowed_depts)) $department = 'IT';
    $stmt = $db->prepare("INSERT INTO job_postings (title,department,description,requirements,salary_range,posted_by,status) VALUES (?,?,?,?,?,?,'active')");
    $stmt->execute([$title,$department,$description,$requirements,$salary_range,$sess_user_id]);
    $success_message = "Job posting created.";
}

// ── Add candidate ────────────────────────────────────────────────────────────
if ($_POST && isset($_POST['create_candidate'])) {
    Security::checkCSRFToken();
    try {
        $db->beginTransaction();
        $job_posting_id = (int)$_POST['job_posting_id'];
        $first_name     = Security::sanitizeInput($_POST['first_name']);
        $last_name      = Security::sanitizeInput($_POST['last_name']);
        $email          = Security::sanitizeInput($_POST['email']);
        $phone          = Security::sanitizeInput($_POST['phone']);
        $cover_letter   = Security::sanitizeInput($_POST['cover_letter']);

        $stmt = $db->prepare("INSERT INTO candidates (job_posting_id,first_name,last_name,email,phone,cover_letter,status) VALUES (?,?,?,?,?,?,'pending')");
        $stmt->execute([$job_posting_id,$first_name,$last_name,$email,$phone,$cover_letter]);
        $candidate_id = $db->lastInsertId();

        if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
            $upload = FileUpload::uploadFile($_FILES['resume_file'], $candidate_id);
            if ($upload['success']) {
                $db->prepare("UPDATE candidates SET resume_file=? WHERE id=?")->execute([$upload['filename'],$candidate_id]);
            } else {
                throw new Exception('Upload failed: '.implode(', ',$upload['errors']));
            }
        }

        if (isset($_POST['work_experience']) && is_array($_POST['work_experience'])) {
            foreach ($_POST['work_experience'] as $we) {
                if (!empty($we['company_name']) && !empty($we['position_title'])) {
                    $stmt = $db->prepare("INSERT INTO candidate_work_experience (candidate_id,company_name,position_title,start_date,end_date,is_current,responsibilities,achievements) VALUES (?,?,?,?,?,?,?,?)");
                    $is_cur = isset($we['is_current']) ? 't' : 'f';
                    $stmt->execute([$candidate_id,Security::sanitizeInput($we['company_name']),Security::sanitizeInput($we['position_title']),
                        Security::sanitizeInput($we['start_date']),($is_cur==='t'?null:Security::sanitizeInput($we['end_date']??'')),
                        $is_cur,Security::sanitizeInput($we['responsibilities']??''),Security::sanitizeInput($we['achievements']??'')]);
                }
            }
        }

        if (isset($_POST['education']) && is_array($_POST['education'])) {
            foreach ($_POST['education'] as $ed) {
                if (!empty($ed['institution_name']) && !empty($ed['degree_type'])) {
                    $stmt = $db->prepare("INSERT INTO candidate_education (candidate_id,institution_name,degree_type,field_of_study,start_year,end_year,is_current,gpa,honors,description) VALUES (?,?,?,?,?,?,?,?,?,?)");
                    $is_cur = isset($ed['is_current']) ? 't' : 'f';
                    $stmt->execute([$candidate_id,Security::sanitizeInput($ed['institution_name']),Security::sanitizeInput($ed['degree_type']),
                        Security::sanitizeInput($ed['field_of_study']??''),
                        !empty($ed['start_year'])?(int)$ed['start_year']:null,
                        ($is_cur==='t'?null:(!empty($ed['end_year'])?(int)$ed['end_year']:null)),
                        $is_cur,!empty($ed['gpa'])?floatval($ed['gpa']):null,
                        Security::sanitizeInput($ed['honors']??''),Security::sanitizeInput($ed['description']??'')]);
                }
            }
        }

        $db->commit();
        $success_message = "Candidate application submitted.";
    } catch(Exception $e) {
        $db->rollBack();
        $error_message = "Error: ".$e->getMessage();
    }
}

// ── Fetch data ───────────────────────────────────────────────────────────────
$employees = $db->query("SELECT e.*, CONCAT(m.first_name,' ',m.last_name) AS manager_name
                         FROM hr_employees e LEFT JOIN hr_employees m ON e.manager_id=m.id
                         ORDER BY e.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$leave_requests = $db->query("SELECT lr.*, e.first_name, e.last_name, e.employee_id AS emp_code
                              FROM hr_leave_requests lr
                              JOIN hr_employees e ON lr.employee_id=e.id
                              ORDER BY lr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// FIXED: join users table for reviewer, not hr_employees
$performance_reviews = $db->query("SELECT pr.*, e.first_name, e.last_name, u.username AS reviewer_name
                                   FROM performance_reviews pr
                                   JOIN hr_employees e ON pr.employee_id=e.id
                                   LEFT JOIN users u ON pr.reviewer_id=u.id
                                   ORDER BY pr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

try {
    $job_postings = $db->query("SELECT jp.*, u.username AS posted_by_name
                                FROM job_postings jp LEFT JOIN users u ON jp.posted_by=u.id
                                ORDER BY jp.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $candidates   = $db->query("SELECT c.*, jp.title AS job_title, jp.department AS job_department
                                FROM candidates c JOIN job_postings jp ON c.job_posting_id=jp.id
                                ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $job_postings = []; $candidates = [];
    error_log($e->getMessage());
}

// Unlinked users (for employee ↔ user linking dropdown)
$linked_user_ids = array_filter(array_column($employees,'user_id'));
$unlinked_users  = $db->query("SELECT id, username, email, role FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Write access
$can_write = in_array($sess_role, ['admin','manager']);

// Stats
$total_emp    = count($employees);
$active_emp   = count(array_filter($employees, fn($e)=>$e['status']==='active'));
$pending_lv   = count(array_filter($leave_requests, fn($l)=>$l['status']==='pending'));
$open_jobs    = count(array_filter($job_postings, fn($j)=>$j['status']==='active'));
$new_candidates = count(array_filter($candidates, fn($c)=>$c['status']==='pending'));

// Dept breakdown
$dept_counts = [];
foreach ($employees as $e) {
    $d = $e['department'] ?: 'Other';
    $dept_counts[$d] = ($dept_counts[$d] ?? 0) + 1;
}
arsort($dept_counts);

$csrf = Security::generateCSRFToken();
$asset_base = '../';
$nav_base   = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Department - KConsulting Hub</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        :root {
            --hr:      #7c3aed;
            --hr-dk:   #6d28d9;
            --hr-vi:   #4c1d95;
            --hr-rose: #e11d48;
            --hr-grad: linear-gradient(135deg, #4c1d95 0%, #7c3aed 100%);
        }

        /* ── Hero ── */
        .hr-hero {
            background: var(--hr-grad);
            border-radius: 16px; padding: 28px 32px;
            display: flex; align-items: center; gap: 20px;
            margin-bottom: 20px; flex-wrap: wrap;
        }
        .hr-hero-icon { font-size: 2.8rem; }
        .hr-hero-info { flex: 1; min-width: 180px; }
        .hr-hero-info h1 { color: #fff; font-size: 1.6rem; font-weight: 800; margin: 0 0 4px; }
        .hr-hero-info p  { color: rgba(255,255,255,.75); font-size: .87rem; margin: 0; }
        .hr-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-left: auto; }
        .hr-hero-btn {
            background: rgba(255,255,255,.15); color: #fff;
            border: 1px solid rgba(255,255,255,.3); border-radius: 8px;
            padding: 8px 16px; font-size: .83rem; font-weight: 600; cursor: pointer;
            transition: background .2s; text-decoration: none;
        }
        .hr-hero-btn:hover { background: rgba(255,255,255,.28); }
        .hr-hero-btn.rose  { background: rgba(225,29,72,.7); border-color: transparent; }
        .hr-hero-btn.rose:hover { background: #be123c; }

        /* ── Stats bar ── */
        .hr-stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px; margin-bottom: 20px;
        }
        @media(max-width:900px){ .hr-stats{ grid-template-columns: repeat(3,1fr); } }
        @media(max-width:600px){ .hr-stats{ grid-template-columns: 1fr 1fr; } }
        .hr-stat {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
            padding: 16px 18px; box-shadow: 0 1px 4px rgba(0,0,0,.05);
        }
        .hr-stat .num { font-size: 1.75rem; font-weight: 800; color: #111827; display: block; }
        .hr-stat .lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; font-weight: 600; }
        .hr-stat .num.violet  { color: var(--hr); }
        .hr-stat .num.green   { color: #059669; }
        .hr-stat .num.amber   { color: #d97706; }
        .hr-stat .num.rose    { color: var(--hr-rose); }
        .hr-stat .num.blue    { color: #2563eb; }

        /* ── Flash ── */
        .hr-flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: .87rem; font-weight: 500; }
        .hr-flash.success { background: #d1fae5; color: #065f46; border-left: 4px solid #059669; }
        .hr-flash.error   { background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; }

        /* ── Tab nav ── */
        .hr-tabs {
            display: flex; gap: 0; background: #fff;
            border: 1px solid #e5e7eb; border-radius: 10px;
            padding: 4px; margin-bottom: 20px; overflow-x: auto;
        }
        .hr-tab {
            flex: none; padding: 9px 20px; border: none; background: transparent;
            border-radius: 7px; cursor: pointer; font-size: .87rem; font-weight: 600;
            color: #6b7280; transition: all .2s; white-space: nowrap;
        }
        .hr-tab:hover { background: #f3f4f6; color: #111827; }
        .hr-tab.active { background: var(--hr); color: #fff; }

        /* ── Controls row ── */
        .hr-controls {
            display: flex; align-items: center; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;
        }
        .hr-search {
            flex: 1; min-width: 180px; padding: 8px 12px;
            border: 1px solid #d1d5db; border-radius: 8px; font-size: .87rem;
        }
        .hr-search:focus { outline: none; border-color: var(--hr); box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
        .hr-filter {
            padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px;
            font-size: .85rem; background: #fff; color: #374151;
        }
        .hr-count { font-size: .8rem; color: #9ca3af; margin-left: auto; }

        /* ── Table ── */
        .hr-table-wrap { overflow-x: auto; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
        .hr-table { width: 100%; border-collapse: collapse; background: #fff; font-size: .86rem; }
        .hr-table th { background: var(--hr); color: #fff; padding: 11px 14px; text-align: left; font-size: .75rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; white-space: nowrap; }
        .hr-table td { padding: 11px 14px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }
        .hr-table tr:last-child td { border-bottom: none; }
        .hr-table tr:hover td { background: #faf5ff; }
        .hr-no-results { text-align: center; padding: 32px; color: #9ca3af; font-size: .88rem; display: none; }

        /* ── Badges ── */
        .hbadge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700; text-transform: capitalize; }
        .hbadge-active     { background: #d1fae5; color: #065f46; }
        .hbadge-inactive   { background: #fef3c7; color: #92400e; }
        .hbadge-terminated { background: #fee2e2; color: #991b1b; }
        .hbadge-pending    { background: #fef3c7; color: #92400e; }
        .hbadge-approved   { background: #d1fae5; color: #065f46; }
        .hbadge-rejected   { background: #fee2e2; color: #991b1b; }
        .hbadge-cancelled  { background: #f3f4f6; color: #6b7280; }
        .hbadge-open, .hbadge-active-job { background: #ede9fe; color: #5b21b6; }
        .hbadge-closed     { background: #f3f4f6; color: #6b7280; }
        .hbadge-draft      { background: #fef9c3; color: #713f12; }
        .hbadge-hired      { background: #d1fae5; color: #065f46; }
        .hbadge-interview  { background: #dbeafe; color: #1e40af; }
        .hbadge-rejected-c { background: #fee2e2; color: #991b1b; }

        /* ── Buttons ── */
        .hr-btn {
            padding: 6px 14px; border: none; border-radius: 7px;
            font-size: .8rem; font-weight: 600; cursor: pointer; transition: all .2s;
            text-decoration: none; display: inline-block;
        }
        .hr-btn-violet { background: var(--hr); color: #fff; }
        .hr-btn-violet:hover { background: var(--hr-dk); }
        .hr-btn-rose   { background: var(--hr-rose); color: #fff; }
        .hr-btn-rose:hover { background: #be123c; }
        .hr-btn-green  { background: #059669; color: #fff; }
        .hr-btn-green:hover { background: #047857; }
        .hr-btn-amber  { background: #d97706; color: #fff; }
        .hr-btn-amber:hover { background: #b45309; }
        .hr-btn-gray   { background: #f3f4f6; color: #374151; }
        .hr-btn-gray:hover { background: #e5e7eb; }

        /* ── Avatar initials ── */
        .hr-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--hr-grad); color: #fff;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 700; flex-shrink: 0;
        }
        .emp-name-cell { display: flex; align-items: center; gap: 10px; }

        /* ── Stars ── */
        .hr-stars { color: #f59e0b; font-size: .9rem; letter-spacing: 1px; }

        /* ── Modal ── */
        .hr-modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.55); z-index: 1000;
            align-items: center; justify-content: center;
        }
        .hr-modal-overlay.open { display: flex; }
        .hr-modal {
            background: #fff; border-radius: 16px;
            width: min(680px,95vw); max-height: 90vh;
            overflow-y: auto; padding: 28px; position: relative;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .hr-modal h2 { font-size: 1.1rem; font-weight: 700; color: #111827; margin: 0 0 20px; }
        .hr-modal-close {
            position: absolute; top: 16px; right: 16px;
            background: #f3f4f6; border: none; border-radius: 50%;
            width: 30px; height: 30px; cursor: pointer; font-size: 1rem;
            display: flex; align-items: center; justify-content: center;
        }
        .hr-modal-close:hover { background: #e5e7eb; }

        /* ── Inline form grid ── */
        .hr-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .hr-form-grid .full { grid-column: 1/-1; }
        @media(max-width:600px){ .hr-form-grid{ grid-template-columns: 1fr; } }
        .hr-field label { display: block; font-size: .78rem; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .hr-field input, .hr-field select, .hr-field textarea {
            width: 100%; padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 7px;
            font-size: .87rem; color: #111827;
        }
        .hr-field textarea { height: 70px; resize: vertical; }
        .hr-field input:focus, .hr-field select:focus, .hr-field textarea:focus {
            outline: none; border-color: var(--hr); box-shadow: 0 0 0 3px rgba(124,58,237,.1);
        }

        /* ── Card wrapper (Overview) ── */
        .hr-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.05); margin-bottom: 16px;
        }
        .hr-card h3 { font-size: .95rem; font-weight: 700; color: #111827; margin: 0 0 14px; }

        /* ── Dept grid ── */
        .dept-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(140px,1fr)); gap: 10px; }
        .dept-chip {
            background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 10px;
            padding: 12px; text-align: center;
        }
        .dept-chip .dc-num { font-size: 1.5rem; font-weight: 800; color: var(--hr); }
        .dept-chip .dc-lbl { font-size: .75rem; color: #6b7280; margin-top: 3px; }

        /* ── Linked badge ── */
        .linked-badge { display: inline-flex; align-items: center; gap: 4px; font-size: .72rem; font-weight: 600; }
        .linked-badge.yes { color: #059669; }
        .linked-badge.no  { color: #9ca3af; }

        /* ── Tab content visibility ── */
        .hr-tab-content { display: none; }
        .hr-tab-content.active { display: block; }

        /* ── Collapsible form section ── */
        .hr-collapsible { margin-bottom: 16px; }
        .hr-collapsible-header {
            background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 10px;
            padding: 12px 16px; cursor: pointer; display: flex; align-items: center;
            justify-content: space-between; font-weight: 600; color: var(--hr-dk); font-size: .9rem;
        }
        .hr-collapsible-body { display: none; padding: 18px; border: 1px solid #e9d5ff; border-top: none; border-radius: 0 0 10px 10px; background: #fff; }
        .hr-collapsible-body.open { display: block; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">

        <?php if (!empty($success_message)): ?>
        <div class="hr-flash success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
        <div class="hr-flash error"><?php echo Security::escapeHTML($error_message); ?></div>
        <?php endif; ?>

        <!-- Hero -->
        <div class="hr-hero">
            <div class="hr-hero-icon">👥</div>
            <div class="hr-hero-info">
                <h1>HR Department</h1>
                <p>People management, leave, performance &amp; recruitment</p>
            </div>
            <?php if ($can_write): ?>
            <div class="hr-hero-actions">
                <button class="hr-hero-btn" onclick="openHeroForm('addEmp')">+ Add Employee</button>
                <button class="hr-hero-btn" onclick="switchTab('leave');toggleCollapsible('leaveForm')">+ Leave Request</button>
                <button class="hr-hero-btn" onclick="switchTab('recruitment');toggleCollapsible('jobForm')">+ Job Posting</button>
                <button class="hr-hero-btn rose" onclick="switchTab('performance');toggleCollapsible('reviewForm')">+ Review</button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stats bar -->
        <div class="hr-stats">
            <div class="hr-stat">
                <span class="num violet"><?php echo $total_emp; ?></span>
                <span class="lbl">Total Employees</span>
            </div>
            <div class="hr-stat">
                <span class="num green"><?php echo $active_emp; ?></span>
                <span class="lbl">Active</span>
            </div>
            <div class="hr-stat">
                <span class="num amber"><?php echo $pending_lv; ?></span>
                <span class="lbl">Pending Leave</span>
            </div>
            <div class="hr-stat">
                <span class="num blue"><?php echo $open_jobs; ?></span>
                <span class="lbl">Open Positions</span>
            </div>
            <div class="hr-stat">
                <span class="num rose"><?php echo $new_candidates; ?></span>
                <span class="lbl">New Candidates</span>
            </div>
        </div>

        <!-- Tab nav -->
        <div class="hr-tabs">
            <button class="hr-tab active" onclick="switchTab('overview')">📊 Overview</button>
            <button class="hr-tab" onclick="switchTab('employees')">👤 Employees (<?php echo $total_emp; ?>)</button>
            <button class="hr-tab" onclick="switchTab('leave')">📅 Leave (<?php echo count($leave_requests); ?>)</button>
            <button class="hr-tab" onclick="switchTab('performance')">⭐ Reviews (<?php echo count($performance_reviews); ?>)</button>
            <button class="hr-tab" onclick="switchTab('recruitment')">📋 Recruitment (<?php echo count($job_postings); ?>)</button>
        </div>

        <!-- ══════════ TAB: OVERVIEW ══════════ -->
        <div id="tab-overview" class="hr-tab-content active">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;flex-wrap:wrap;">
                <!-- Dept breakdown -->
                <div class="hr-card">
                    <h3>Employees by Department</h3>
                    <div class="dept-grid">
                        <?php foreach ($dept_counts as $dept => $cnt): ?>
                        <div class="dept-chip">
                            <div class="dc-num"><?php echo $cnt; ?></div>
                            <div class="dc-lbl"><?php echo Security::escapeHTML($dept); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Status breakdown -->
                <div class="hr-card">
                    <h3>Workforce Status</h3>
                    <?php
                    $status_counts = [];
                    foreach ($employees as $e) $status_counts[$e['status']] = ($status_counts[$e['status']] ?? 0) + 1;
                    $status_colors = ['active'=>'#059669','inactive'=>'#d97706','terminated'=>'#dc2626'];
                    ?>
                    <?php foreach ($status_counts as $st => $cnt): ?>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <div style="flex:1;font-size:.87rem;color:#374151;text-transform:capitalize;"><?php echo $st; ?></div>
                        <div style="width:120px;height:8px;background:#f3f4f6;border-radius:8px;overflow:hidden;">
                            <div style="width:<?php echo round($cnt/$total_emp*100); ?>%;height:100%;background:<?php echo $status_colors[$st]??'#9ca3af'; ?>;border-radius:8px;"></div>
                        </div>
                        <div style="font-size:.85rem;font-weight:700;color:<?php echo $status_colors[$st]??'#374151'; ?>;width:24px;text-align:right;"><?php echo $cnt; ?></div>
                    </div>
                    <?php endforeach; ?>
                    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f3f4f6;">
                        <div style="font-size:.8rem;color:#9ca3af;">Portal Account Linked</div>
                        <?php $linked = count(array_filter($employees, fn($e)=>!empty($e['user_id']))); ?>
                        <div style="font-size:1.2rem;font-weight:700;color:var(--hr);margin-top:4px;">
                            <?php echo $linked; ?> / <?php echo $total_emp; ?>
                            <span style="font-size:.75rem;color:#9ca3af;font-weight:400;">&nbsp;employees have portal access</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Recent leave -->
            <div class="hr-card">
                <h3>Recent Leave Requests</h3>
                <?php $recent_leave = array_slice($leave_requests, 0, 6); ?>
                <?php if (empty($recent_leave)): ?>
                <p style="color:#9ca3af;font-size:.87rem;">No leave requests yet.</p>
                <?php else: ?>
                <div class="hr-table-wrap">
                <table class="hr-table">
                    <thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent_leave as $lr): ?>
                    <tr>
                        <td><?php echo Security::escapeHTML($lr['first_name'].' '.$lr['last_name']); ?></td>
                        <td style="text-transform:capitalize;"><?php echo Security::escapeHTML($lr['leave_type']); ?></td>
                        <td><?php echo date('d M',strtotime($lr['start_date'])); ?> – <?php echo date('d M',strtotime($lr['end_date'])); ?></td>
                        <td><?php echo $lr['days_requested']; ?></td>
                        <td><span class="hbadge hbadge-<?php echo $lr['status']; ?>"><?php echo ucfirst($lr['status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ══════════ TAB: EMPLOYEES ══════════ -->
        <div id="tab-employees" class="hr-tab-content">
            <?php if ($can_write): ?>
            <div class="hr-collapsible" id="addEmp-wrap">
                <div class="hr-collapsible-header" onclick="toggleCollapsible('addEmp')">
                    <span>+ Add New Employee</span><span id="addEmp-arrow">▼</span>
                </div>
                <div class="hr-collapsible-body" id="addEmp-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="create_employee" value="1">
                        <div class="hr-form-grid">
                            <div class="hr-field"><label>First Name *</label><input type="text" name="first_name" required></div>
                            <div class="hr-field"><label>Last Name *</label><input type="text" name="last_name" required></div>
                            <div class="hr-field"><label>Email *</label><input type="email" name="email" required></div>
                            <div class="hr-field"><label>Phone</label><input type="tel" name="phone"></div>
                            <div class="hr-field"><label>Position *</label><input type="text" name="position" required></div>
                            <div class="hr-field"><label>Department *</label>
                                <select name="department" required>
                                    <?php foreach(['IT','Marketing','Finance','HR','Business Development'] as $d): ?>
                                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="hr-field"><label>Role</label>
                                <select name="role">
                                    <option value="employee">Employee</option>
                                    <option value="manager">Manager</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="hr-field"><label>Hire Date *</label><input type="date" name="hire_date" required value="<?php echo date('Y-m-d'); ?>"></div>
                            <div class="hr-field"><label>Salary (ZAR)</label><input type="number" name="salary" step="0.01" min="0"></div>
                            <div class="hr-field"><label>Manager</label>
                                <select name="manager_id">
                                    <option value="">None</option>
                                    <?php foreach ($employees as $mgr): ?>
                                    <option value="<?php echo $mgr['id']; ?>"><?php echo Security::escapeHTML($mgr['first_name'].' '.$mgr['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <p style="font-size:.78rem;color:#9ca3af;margin-top:10px;">A portal account will be created automatically. Temporary credentials will appear in the confirmation message.</p>
                        <div style="margin-top:12px;"><button type="submit" class="hr-btn hr-btn-violet">+ Create Employee</button></div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Search/filter -->
            <div class="hr-controls">
                <input class="hr-search" type="text" id="emp-search" placeholder="Search employees…" oninput="filterHR('emp-tbody','emp-search','emp-dept-filter','emp-status-filter','emp-count')">
                <select class="hr-filter" id="emp-dept-filter" onchange="filterHR('emp-tbody','emp-search','emp-dept-filter','emp-status-filter','emp-count')">
                    <option value="">All Departments</option>
                    <?php foreach (array_keys($dept_counts) as $d): ?>
                    <option value="<?php echo strtolower($d); ?>"><?php echo Security::escapeHTML($d); ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="hr-filter" id="emp-status-filter" onchange="filterHR('emp-tbody','emp-search','emp-dept-filter','emp-status-filter','emp-count')">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="terminated">Terminated</option>
                </select>
                <span class="hr-count" id="emp-count"><?php echo $total_emp; ?> employees</span>
            </div>

            <div class="hr-table-wrap">
            <table class="hr-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>ID</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Hire Date</th>
                        <th>Status</th>
                        <th>Portal</th>
                        <?php if ($can_write): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="emp-tbody">
                <?php foreach ($employees as $emp):
                    $initials = strtoupper(substr($emp['first_name'],0,1).substr($emp['last_name'],0,1));
                    $search_str = strtolower($emp['first_name'].' '.$emp['last_name'].' '.$emp['email'].' '.$emp['position'].' '.$emp['employee_id']);
                ?>
                <tr data-search="<?php echo htmlspecialchars($search_str,ENT_QUOTES); ?>"
                    data-dept="<?php echo strtolower(htmlspecialchars($emp['department']??'')); ?>"
                    data-status="<?php echo $emp['status']; ?>">
                    <td>
                        <div class="emp-name-cell">
                            <div class="hr-avatar"><?php echo $initials; ?></div>
                            <div>
                                <div style="font-weight:600;color:#111827;"><?php echo Security::escapeHTML($emp['first_name'].' '.$emp['last_name']); ?></div>
                                <div style="font-size:.75rem;color:#9ca3af;"><?php echo Security::escapeHTML($emp['email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-family:monospace;font-size:.8rem;"><?php echo Security::escapeHTML($emp['employee_id']); ?></td>
                    <td><?php echo Security::escapeHTML($emp['position'] ?? '—'); ?></td>
                    <td><?php echo Security::escapeHTML($emp['department'] ?? '—'); ?></td>
                    <td><?php echo $emp['hire_date'] ? date('d M Y',strtotime($emp['hire_date'])) : '—'; ?></td>
                    <td><span class="hbadge hbadge-<?php echo $emp['status']; ?>"><?php echo ucfirst($emp['status']); ?></span></td>
                    <td>
                        <?php if (!empty($emp['user_id'])): ?>
                        <span class="linked-badge yes">🔗 Linked</span>
                        <?php else: ?>
                        <span class="linked-badge no">— None</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($can_write): ?>
                    <td>
                        <button class="hr-btn hr-btn-gray" style="font-size:.75rem;" onclick="openEditEmp(<?php echo htmlspecialchars(json_encode($emp),ENT_QUOTES); ?>)">✏️ Edit</button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="hr-no-results" id="emp-tbody-no-results">No employees match your search.</div>
            </div>
        </div>

        <!-- ══════════ TAB: LEAVE ══════════ -->
        <div id="tab-leave" class="hr-tab-content">
            <?php if ($can_write): ?>
            <div class="hr-collapsible">
                <div class="hr-collapsible-header" onclick="toggleCollapsible('leaveForm')">
                    <span>+ Create Leave Request</span><span id="leaveForm-arrow">▼</span>
                </div>
                <div class="hr-collapsible-body" id="leaveForm-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="create_leave_request" value="1">
                        <div class="hr-form-grid">
                            <div class="hr-field"><label>Employee *</label>
                                <select name="employee_id" required>
                                    <option value="">Select…</option>
                                    <?php foreach ($employees as $e): ?>
                                    <option value="<?php echo $e['id']; ?>"><?php echo Security::escapeHTML($e['first_name'].' '.$e['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="hr-field"><label>Leave Type *</label>
                                <select name="leave_type" required>
                                    <option value="annual">Annual</option>
                                    <option value="sick">Sick</option>
                                    <option value="personal">Personal</option>
                                    <option value="maternity">Maternity</option>
                                    <option value="paternity">Paternity</option>
                                    <option value="study">Study</option>
                                    <option value="unpaid">Unpaid</option>
                                </select>
                            </div>
                            <div class="hr-field"><label>Start Date *</label><input type="date" name="start_date" required></div>
                            <div class="hr-field"><label>End Date *</label><input type="date" name="end_date" required></div>
                            <div class="hr-field full"><label>Reason</label><textarea name="reason" placeholder="Optional reason…"></textarea></div>
                        </div>
                        <div style="margin-top:14px;"><button type="submit" class="hr-btn hr-btn-violet">Submit Request</button></div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="hr-controls">
                <input class="hr-search" type="text" id="lv-search" placeholder="Search by employee or type…" oninput="filterHR('lv-tbody','lv-search','lv-type-filter','lv-status-filter','lv-count')">
                <select class="hr-filter" id="lv-type-filter" onchange="filterHR('lv-tbody','lv-search','lv-type-filter','lv-status-filter','lv-count')">
                    <option value="">All Types</option>
                    <?php foreach(['annual','sick','personal','maternity','paternity','study','unpaid'] as $lt): ?>
                    <option value="<?php echo $lt; ?>"><?php echo ucfirst($lt); ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="hr-filter" id="lv-status-filter" onchange="filterHR('lv-tbody','lv-search','lv-type-filter','lv-status-filter','lv-count')">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <span class="hr-count" id="lv-count"><?php echo count($leave_requests); ?> requests</span>
            </div>

            <div class="hr-table-wrap">
            <table class="hr-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <?php if ($can_write): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="lv-tbody">
                <?php foreach ($leave_requests as $lr):
                    $search_lv = strtolower($lr['first_name'].' '.$lr['last_name'].' '.$lr['leave_type'].' '.$lr['emp_code']);
                ?>
                <tr data-search="<?php echo htmlspecialchars($search_lv,ENT_QUOTES); ?>"
                    data-dept="<?php echo $lr['leave_type']; ?>"
                    data-status="<?php echo $lr['status']; ?>">
                    <td style="font-weight:500;"><?php echo Security::escapeHTML($lr['first_name'].' '.$lr['last_name']); ?></td>
                    <td style="text-transform:capitalize;"><?php echo Security::escapeHTML($lr['leave_type']); ?></td>
                    <td><?php echo date('d M Y',strtotime($lr['start_date'])); ?></td>
                    <td><?php echo date('d M Y',strtotime($lr['end_date'])); ?></td>
                    <td><?php echo $lr['days_requested']; ?></td>
                    <td><span class="hbadge hbadge-<?php echo $lr['status']; ?>"><?php echo ucfirst($lr['status']); ?></span></td>
                    <td style="font-size:.78rem;color:#9ca3af;"><?php echo date('d M Y',strtotime($lr['created_at'])); ?></td>
                    <?php if ($can_write): ?>
                    <td style="white-space:nowrap;">
                        <?php if ($lr['status'] === 'pending'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                            <input type="hidden" name="update_leave_status" value="1">
                            <input type="hidden" name="leave_id" value="<?php echo $lr['id']; ?>">
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="hr-btn hr-btn-green" style="font-size:.72rem;padding:4px 10px;">✓ Approve</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                            <input type="hidden" name="update_leave_status" value="1">
                            <input type="hidden" name="leave_id" value="<?php echo $lr['id']; ?>">
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" class="hr-btn hr-btn-rose" style="font-size:.72rem;padding:4px 10px;">✗ Reject</button>
                        </form>
                        <?php else: ?>
                        <span style="font-size:.78rem;color:#9ca3af;">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="hr-no-results" id="lv-tbody-no-results">No leave requests match your filter.</div>
            </div>
        </div>

        <!-- ══════════ TAB: PERFORMANCE ══════════ -->
        <div id="tab-performance" class="hr-tab-content">
            <?php if ($can_write): ?>
            <div class="hr-collapsible">
                <div class="hr-collapsible-header" onclick="toggleCollapsible('reviewForm')">
                    <span>+ Create Performance Review</span><span id="reviewForm-arrow">▼</span>
                </div>
                <div class="hr-collapsible-body" id="reviewForm-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="create_performance_review" value="1">
                        <div class="hr-form-grid">
                            <div class="hr-field"><label>Employee *</label>
                                <select name="employee_id" required>
                                    <option value="">Select…</option>
                                    <?php foreach ($employees as $e): ?>
                                    <option value="<?php echo $e['id']; ?>"><?php echo Security::escapeHTML($e['first_name'].' '.$e['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="hr-field"><label>Reviewer (Portal User) *</label>
                                <select name="reviewer_id" required>
                                    <option value="">Select…</option>
                                    <?php foreach ($unlinked_users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo Security::escapeHTML($u['username']); ?> (<?php echo $u['role']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="hr-field"><label>Period Start *</label><input type="date" name="start_date" required></div>
                            <div class="hr-field"><label>Period End *</label><input type="date" name="end_date" required></div>
                            <div class="hr-field"><label>Overall Rating (1–5) *</label>
                                <select name="overall_rating" required>
                                    <option value="5">5 – Outstanding</option>
                                    <option value="4">4 – Exceeds Expectations</option>
                                    <option value="3" selected>3 – Meets Expectations</option>
                                    <option value="2">2 – Needs Improvement</option>
                                    <option value="1">1 – Unsatisfactory</option>
                                </select>
                            </div>
                            <div class="hr-field full"><label>Goals &amp; Achievements</label><textarea name="goals_achievement"></textarea></div>
                            <div class="hr-field full"><label>Strengths</label><textarea name="strengths"></textarea></div>
                            <div class="hr-field full"><label>Areas for Improvement</label><textarea name="areas_for_improvement"></textarea></div>
                            <div class="hr-field full"><label>Manager Comments</label><textarea name="comments"></textarea></div>
                        </div>
                        <div style="margin-top:14px;"><button type="submit" class="hr-btn hr-btn-rose">Save Review</button></div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="hr-controls">
                <input class="hr-search" type="text" id="rv-search" placeholder="Search reviews…" oninput="filterHR('rv-tbody','rv-search','rv-dept-filter','rv-status-filter','rv-count')">
                <select class="hr-filter" id="rv-dept-filter" onchange="filterHR('rv-tbody','rv-search','rv-dept-filter','rv-status-filter','rv-count')">
                    <option value="">All Ratings</option>
                    <option value="5">5 Stars</option><option value="4">4 Stars</option>
                    <option value="3">3 Stars</option><option value="2">2 Stars</option><option value="1">1 Star</option>
                </select>
                <select class="hr-filter" id="rv-status-filter" onchange="filterHR('rv-tbody','rv-search','rv-dept-filter','rv-status-filter','rv-count')">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="completed">Completed</option>
                </select>
                <span class="hr-count" id="rv-count"><?php echo count($performance_reviews); ?> reviews</span>
            </div>

            <div class="hr-table-wrap">
            <table class="hr-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Period</th>
                        <th>Rating</th>
                        <th>Reviewer</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="rv-tbody">
                <?php foreach ($performance_reviews as $rv):
                    $search_rv = strtolower($rv['first_name'].' '.$rv['last_name'].' '.($rv['reviewer_name']??''));
                    $stars = str_repeat('★', (int)$rv['overall_rating']).str_repeat('☆', 5-(int)$rv['overall_rating']);
                ?>
                <tr data-search="<?php echo htmlspecialchars($search_rv,ENT_QUOTES); ?>"
                    data-dept="<?php echo $rv['overall_rating']; ?>"
                    data-status="<?php echo $rv['status']; ?>">
                    <td style="font-weight:500;"><?php echo Security::escapeHTML($rv['first_name'].' '.$rv['last_name']); ?></td>
                    <td style="font-size:.8rem;">
                        <?php echo date('M Y',strtotime($rv['review_period_start'])); ?>
                        – <?php echo date('M Y',strtotime($rv['review_period_end'])); ?>
                    </td>
                    <td><span class="hr-stars"><?php echo $stars; ?></span></td>
                    <td style="font-size:.82rem;"><?php echo Security::escapeHTML($rv['reviewer_name'] ?? '—'); ?></td>
                    <td><span class="hbadge hbadge-<?php echo $rv['status']; ?>"><?php echo ucfirst($rv['status']); ?></span></td>
                    <td style="font-size:.78rem;color:#9ca3af;"><?php echo date('d M Y',strtotime($rv['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="hr-no-results" id="rv-tbody-no-results">No reviews match your filter.</div>
            </div>
        </div>

        <!-- ══════════ TAB: RECRUITMENT ══════════ -->
        <div id="tab-recruitment" class="hr-tab-content">
            <?php if ($can_write): ?>
            <!-- New job posting form -->
            <div class="hr-collapsible">
                <div class="hr-collapsible-header" onclick="toggleCollapsible('jobForm')">
                    <span>+ Post New Job</span><span id="jobForm-arrow">▼</span>
                </div>
                <div class="hr-collapsible-body" id="jobForm-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="create_job_posting" value="1">
                        <div class="hr-form-grid">
                            <div class="hr-field"><label>Job Title *</label><input type="text" name="title" required></div>
                            <div class="hr-field"><label>Department *</label>
                                <select name="department" required>
                                    <?php foreach(['IT','Marketing','Finance','HR','Business Development'] as $d): ?>
                                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="hr-field"><label>Salary Range</label><input type="text" name="salary_range" placeholder="e.g. R25,000 – R35,000"></div>
                            <div class="hr-field full"><label>Description *</label><textarea name="description" required></textarea></div>
                            <div class="hr-field full"><label>Requirements</label><textarea name="requirements"></textarea></div>
                        </div>
                        <div style="margin-top:14px;"><button type="submit" class="hr-btn hr-btn-violet">Post Job</button></div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Job postings table -->
            <div class="hr-controls">
                <input class="hr-search" type="text" id="jp-search" placeholder="Search postings…" oninput="filterHR('jp-tbody','jp-search','jp-dept-filter','jp-status-filter','jp-count')">
                <select class="hr-filter" id="jp-dept-filter" onchange="filterHR('jp-tbody','jp-search','jp-dept-filter','jp-status-filter','jp-count')">
                    <option value="">All Departments</option>
                    <?php foreach(['IT','Marketing','Finance','HR','Business Development'] as $d): ?>
                    <option value="<?php echo strtolower($d); ?>"><?php echo $d; ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="hr-filter" id="jp-status-filter" onchange="filterHR('jp-tbody','jp-search','jp-dept-filter','jp-status-filter','jp-count')">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="draft">Draft</option>
                    <option value="closed">Closed</option>
                </select>
                <span class="hr-count" id="jp-count"><?php echo count($job_postings); ?> postings</span>
            </div>

            <div class="hr-table-wrap" style="margin-bottom:24px;">
            <table class="hr-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Salary Range</th>
                        <th>Status</th>
                        <th>Posted</th>
                        <th>Candidates</th>
                        <?php if ($can_write): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="jp-tbody">
                <?php foreach ($job_postings as $jp):
                    $jp_candidates = array_filter($candidates, fn($c)=>$c['job_posting_id']===$jp['id']);
                    $search_jp = strtolower($jp['title'].' '.$jp['department'].' '.($jp['posted_by_name']??''));
                ?>
                <tr data-search="<?php echo htmlspecialchars($search_jp,ENT_QUOTES); ?>"
                    data-dept="<?php echo strtolower(htmlspecialchars($jp['department']??'')); ?>"
                    data-status="<?php echo $jp['status']; ?>">
                    <td style="font-weight:600;"><?php echo Security::escapeHTML($jp['title']); ?></td>
                    <td><?php echo Security::escapeHTML($jp['department']); ?></td>
                    <td style="font-size:.82rem;"><?php echo Security::escapeHTML($jp['salary_range'] ?? '—'); ?></td>
                    <td><span class="hbadge hbadge-<?php echo $jp['status']; ?>"><?php echo ucfirst($jp['status']); ?></span></td>
                    <td style="font-size:.78rem;color:#9ca3af;"><?php echo date('d M Y',strtotime($jp['created_at'])); ?></td>
                    <td><?php echo count($jp_candidates); ?></td>
                    <?php if ($can_write): ?>
                    <td style="white-space:nowrap;">
                        <button class="hr-btn hr-btn-gray" style="font-size:.73rem;" onclick="openEditJob(<?php echo htmlspecialchars(json_encode($jp),ENT_QUOTES); ?>)">✏️ Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this posting and all candidates?')">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                            <input type="hidden" name="delete_job_posting" value="1">
                            <input type="hidden" name="job_id" value="<?php echo $jp['id']; ?>">
                            <button type="submit" class="hr-btn hr-btn-rose" style="font-size:.73rem;">🗑 Delete</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="hr-no-results" id="jp-tbody-no-results">No job postings match your filter.</div>
            </div>

            <!-- Candidates section -->
            <div class="hr-collapsible">
                <div class="hr-collapsible-header" onclick="toggleCollapsible('candForm')">
                    <span>+ Add Candidate Application</span><span id="candForm-arrow">▼</span>
                </div>
                <div class="hr-collapsible-body" id="candForm-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="create_candidate" value="1">
                        <div class="hr-form-grid">
                            <div class="hr-field full"><label>Job Posting *</label>
                                <select name="job_posting_id" required>
                                    <option value="">Select…</option>
                                    <?php foreach ($job_postings as $jp): ?>
                                    <option value="<?php echo $jp['id']; ?>"><?php echo Security::escapeHTML($jp['title'].' — '.$jp['department']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="hr-field"><label>First Name *</label><input type="text" name="first_name" required></div>
                            <div class="hr-field"><label>Last Name *</label><input type="text" name="last_name" required></div>
                            <div class="hr-field"><label>Email *</label><input type="email" name="email" required></div>
                            <div class="hr-field"><label>Phone</label><input type="tel" name="phone"></div>
                            <div class="hr-field full"><label>Cover Letter</label><textarea name="cover_letter" placeholder="Candidate's cover letter…"></textarea></div>
                            <div class="hr-field full"><label>CV / Resume (PDF)</label><input type="file" name="resume_file" accept=".pdf,.doc,.docx"></div>
                        </div>
                        <div style="margin-top:14px;"><button type="submit" class="hr-btn hr-btn-violet">Submit Application</button></div>
                    </form>
                </div>
            </div>

            <!-- Candidates table -->
            <div class="hr-controls" style="margin-top:16px;">
                <input class="hr-search" type="text" id="cand-search" placeholder="Search candidates…" oninput="filterHR('cand-tbody','cand-search','cand-job-filter','cand-status-filter','cand-count')">
                <select class="hr-filter" id="cand-job-filter" onchange="filterHR('cand-tbody','cand-search','cand-job-filter','cand-status-filter','cand-count')">
                    <option value="">All Positions</option>
                    <?php foreach ($job_postings as $jp): ?>
                    <option value="<?php echo strtolower(htmlspecialchars($jp['title'])); ?>"><?php echo Security::escapeHTML($jp['title']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="hr-filter" id="cand-status-filter" onchange="filterHR('cand-tbody','cand-search','cand-job-filter','cand-status-filter','cand-count')">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="interview">Interview</option>
                    <option value="hired">Hired</option>
                    <option value="rejected">Rejected</option>
                </select>
                <span class="hr-count" id="cand-count"><?php echo count($candidates); ?> candidates</span>
            </div>

            <div class="hr-table-wrap">
            <table class="hr-table">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Applied</th>
                        <th>CV</th>
                    </tr>
                </thead>
                <tbody id="cand-tbody">
                <?php foreach ($candidates as $cand):
                    $search_cand = strtolower($cand['first_name'].' '.$cand['last_name'].' '.$cand['email'].' '.$cand['job_title']);
                ?>
                <tr data-search="<?php echo htmlspecialchars($search_cand,ENT_QUOTES); ?>"
                    data-dept="<?php echo strtolower(htmlspecialchars($cand['job_title']??'')); ?>"
                    data-status="<?php echo $cand['status']; ?>">
                    <td>
                        <div style="font-weight:600;"><?php echo Security::escapeHTML($cand['first_name'].' '.$cand['last_name']); ?></div>
                        <div style="font-size:.75rem;color:#9ca3af;"><?php echo Security::escapeHTML($cand['email']); ?></div>
                    </td>
                    <td><?php echo Security::escapeHTML($cand['job_title']); ?></td>
                    <td><?php echo Security::escapeHTML($cand['job_department']); ?></td>
                    <td><span class="hbadge hbadge-<?php echo $cand['status']; ?>"><?php echo ucfirst($cand['status']); ?></span></td>
                    <td style="font-size:.78rem;color:#9ca3af;"><?php echo date('d M Y',strtotime($cand['created_at'])); ?></td>
                    <td>
                        <?php if (!empty($cand['resume_file'])): ?>
                        <a href="../uploads/<?php echo Security::escapeHTML($cand['resume_file']); ?>" target="_blank" class="hr-btn hr-btn-gray" style="font-size:.73rem;">📄 CV</a>
                        <?php else: ?>
                        <span style="font-size:.75rem;color:#9ca3af;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="hr-no-results" id="cand-tbody-no-results">No candidates match your filter.</div>
            </div>
        </div>

    </div><!-- /.main-content -->

    <!-- ══ Edit Employee Modal ══ -->
    <div class="hr-modal-overlay" id="editEmpModal">
        <div class="hr-modal">
            <button class="hr-modal-close" onclick="closeModal('editEmpModal')">✕</button>
            <h2>Edit Employee</h2>
            <form method="POST" id="editEmpForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="update_employee" value="1">
                <input type="hidden" name="employee_id" id="edit_emp_id">
                <div class="hr-form-grid">
                    <div class="hr-field"><label>First Name *</label><input type="text" name="first_name" id="edit_first_name" required></div>
                    <div class="hr-field"><label>Last Name *</label><input type="text" name="last_name" id="edit_last_name" required></div>
                    <div class="hr-field"><label>Email *</label><input type="email" name="email" id="edit_email" required></div>
                    <div class="hr-field"><label>Phone</label><input type="tel" name="phone" id="edit_phone"></div>
                    <div class="hr-field"><label>Position</label><input type="text" name="position" id="edit_position"></div>
                    <div class="hr-field"><label>Department</label>
                        <select name="department" id="edit_department">
                            <?php foreach(['IT','Marketing','Finance','HR','Business Development'] as $d): ?>
                            <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="hr-field"><label>Role</label>
                        <select name="role" id="edit_role">
                            <option value="employee">Employee</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="hr-field"><label>Status</label>
                        <select name="status" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                    <div class="hr-field"><label>Salary (ZAR)</label><input type="number" name="salary" id="edit_salary" step="0.01" min="0"></div>
                    <div class="hr-field"><label>Manager</label>
                        <select name="manager_id" id="edit_manager_id">
                            <option value="">None</option>
                            <?php foreach ($employees as $mgr): ?>
                            <option value="<?php echo $mgr['id']; ?>"><?php echo Security::escapeHTML($mgr['first_name'].' '.$mgr['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="hr-field"><label>Link Portal Account</label>
                        <select name="user_id_link" id="edit_user_id_link">
                            <option value="">Not linked</option>
                            <?php foreach ($unlinked_users as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo Security::escapeHTML($u['username']); ?> (<?php echo $u['role']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="margin-top:20px;display:flex;gap:10px;">
                    <button type="submit" class="hr-btn hr-btn-violet">Save Changes</button>
                    <button type="button" class="hr-btn hr-btn-gray" onclick="closeModal('editEmpModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══ Edit Job Modal ══ -->
    <div class="hr-modal-overlay" id="editJobModal">
        <div class="hr-modal">
            <button class="hr-modal-close" onclick="closeModal('editJobModal')">✕</button>
            <h2>Edit Job Posting</h2>
            <form method="POST" id="editJobForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="update_job_posting" value="1">
                <input type="hidden" name="job_id" id="edit_job_id">
                <div class="hr-form-grid">
                    <div class="hr-field"><label>Title *</label><input type="text" name="title" id="edit_job_title" required></div>
                    <div class="hr-field"><label>Department</label>
                        <select name="department" id="edit_job_dept">
                            <?php foreach(['IT','Marketing','Finance','HR','Business Development'] as $d): ?>
                            <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="hr-field"><label>Salary Range</label><input type="text" name="salary_range" id="edit_job_salary"></div>
                    <div class="hr-field"><label>Status</label>
                        <select name="status" id="edit_job_status">
                            <option value="active">Active</option>
                            <option value="draft">Draft</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="hr-field full"><label>Description</label><textarea name="description" id="edit_job_desc"></textarea></div>
                    <div class="hr-field full"><label>Requirements</label><textarea name="requirements" id="edit_job_req"></textarea></div>
                </div>
                <div style="margin-top:20px;display:flex;gap:10px;">
                    <button type="submit" class="hr-btn hr-btn-violet">Save Changes</button>
                    <button type="button" class="hr-btn hr-btn-gray" onclick="closeModal('editJobModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/notification.js"></script>
    <script>
    // ── Tab switching ────────────────────────────────────────────────────────
    function switchTab(name) {
        document.querySelectorAll('.hr-tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.hr-tab').forEach(el => el.classList.remove('active'));
        const content = document.getElementById('tab-' + name);
        if (content) content.classList.add('active');
        const btns = document.querySelectorAll('.hr-tab');
        btns.forEach(btn => {
            if (btn.textContent.toLowerCase().includes(name.slice(0,4))) btn.classList.add('active');
        });
    }

    // ── Collapsible form sections ────────────────────────────────────────────
    function toggleCollapsible(id) {
        const body  = document.getElementById(id + '-body');
        const arrow = document.getElementById(id + '-arrow');
        if (!body) return;
        const open = body.classList.toggle('open');
        if (arrow) arrow.textContent = open ? '▲' : '▼';
    }
    function openHeroForm(id) {
        switchTab('employees');
        const body = document.getElementById(id + '-body');
        if (body && !body.classList.contains('open')) toggleCollapsible(id);
        body && body.scrollIntoView({behavior:'smooth',block:'start'});
    }

    // ── Filter function ──────────────────────────────────────────────────────
    function filterHR(tbodyId, searchId, filter1Id, filter2Id, countId) {
        const q      = (document.getElementById(searchId)?.value||'').toLowerCase().trim();
        const f1     = (document.getElementById(filter1Id)?.value||'').toLowerCase();
        const f2     = (document.getElementById(filter2Id)?.value||'').toLowerCase();
        const tbody  = document.getElementById(tbodyId);
        if (!tbody) return;
        const rows   = tbody.querySelectorAll('tr');
        let visible  = 0;
        rows.forEach(row => {
            const txt  = (row.dataset.search||'').toLowerCase();
            const dept = (row.dataset.dept||'').toLowerCase();
            const stat = (row.dataset.status||'').toLowerCase();
            const show = (!q||txt.includes(q)) && (!f1||dept.includes(f1)) && (!f2||stat===f2);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const noRes = document.getElementById(tbodyId + '-no-results');
        if (noRes) noRes.style.display = visible ? 'none' : 'block';
        const cnt = document.getElementById(countId);
        if (cnt) cnt.textContent = visible + ' ' + (tbodyId.includes('emp')?'employees':tbodyId.includes('lv')?'requests':tbodyId.includes('rv')?'reviews':tbodyId.includes('jp')?'postings':'candidates');
    }

    // ── Modals ───────────────────────────────────────────────────────────────
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }
    document.querySelectorAll('.hr-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });
    });

    function openEditEmp(emp) {
        document.getElementById('edit_emp_id').value       = emp.id;
        document.getElementById('edit_first_name').value   = emp.first_name || '';
        document.getElementById('edit_last_name').value    = emp.last_name  || '';
        document.getElementById('edit_email').value        = emp.email      || '';
        document.getElementById('edit_phone').value        = emp.phone      || '';
        document.getElementById('edit_position').value     = emp.position   || '';
        document.getElementById('edit_salary').value       = emp.salary     || '';
        setSelectVal('edit_department', emp.department);
        setSelectVal('edit_role',       emp.role);
        setSelectVal('edit_status',     emp.status);
        setSelectVal('edit_manager_id', emp.manager_id);
        setSelectVal('edit_user_id_link', emp.user_id);
        document.getElementById('editEmpModal').classList.add('open');
    }

    function openEditJob(jp) {
        document.getElementById('edit_job_id').value      = jp.id;
        document.getElementById('edit_job_title').value   = jp.title        || '';
        document.getElementById('edit_job_salary').value  = jp.salary_range || '';
        document.getElementById('edit_job_desc').value    = jp.description  || '';
        document.getElementById('edit_job_req').value     = jp.requirements || '';
        setSelectVal('edit_job_dept',   jp.department);
        setSelectVal('edit_job_status', jp.status);
        document.getElementById('editJobModal').classList.add('open');
    }

    function setSelectVal(id, val) {
        const sel = document.getElementById(id);
        if (!sel || val === null || val === undefined) return;
        for (let i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value == val) { sel.selectedIndex = i; return; }
        }
    }
    </script>
</body>
</html>
