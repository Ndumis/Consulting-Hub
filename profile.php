<?php
require_once 'config/session.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

require_once 'config/database.php';
require_once 'config/security.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$user_id    = $_SESSION['user_id'];
$user_role  = $_SESSION['role']  ?? 'employee';
$user_dept  = $_SESSION['department'] ?? '';
$csrf_token = Security::generateCSRFToken();

$msg = '';
$msg_type = 'success';

// ── Fetch linked employee record ─────────────────────────────────────────────
$emp_stmt = $db->prepare("
    SELECT e.*, m.first_name AS mgr_first, m.last_name AS mgr_last
    FROM hr_employees e
    LEFT JOIN hr_employees m ON e.manager_id = m.id
    WHERE e.user_id = ?
");
$emp_stmt->execute([$user_id]);
$employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);

// ── Handle profile update ────────────────────────────────────────────────────
if ($_POST && isset($_POST['update_profile'])) {
    Security::checkCSRFToken();
    if (!$employee) { $msg = 'No employee record linked to your account.'; $msg_type = 'error'; }
    else {
        $phone             = Security::sanitizeInput($_POST['phone'] ?? '');
        $emergency_contact = Security::sanitizeInput($_POST['emergency_contact'] ?? '');
        $emergency_phone   = Security::sanitizeInput($_POST['emergency_phone'] ?? '');
        $bio               = Security::sanitizeInput($_POST['bio'] ?? '');
        $address           = Security::sanitizeInput($_POST['address'] ?? '');
        $date_of_birth     = Security::sanitizeInput($_POST['date_of_birth'] ?? '');
        $national_id       = Security::sanitizeInput($_POST['national_id'] ?? '');

        $stmt = $db->prepare("
            UPDATE hr_employees
            SET phone=?, emergency_contact=?, emergency_phone=?, bio=?, address=?, date_of_birth=?, national_id=?
            WHERE user_id=?
        ");
        $stmt->execute([$phone, $emergency_contact, $emergency_phone, $bio, $address,
                        $date_of_birth ?: null, $national_id, $user_id]);
        $msg = 'Profile updated successfully.';
        // Refresh employee record
        $emp_stmt->execute([$user_id]);
        $employee = $emp_stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ── Handle leave request submission ─────────────────────────────────────────
if ($_POST && isset($_POST['book_leave'])) {
    Security::checkCSRFToken();
    if (!$employee) { $msg = 'No employee record linked to your account.'; $msg_type = 'error'; }
    else {
        $leave_type  = Security::sanitizeInput($_POST['leave_type'] ?? '');
        $start_date  = Security::sanitizeInput($_POST['start_date'] ?? '');
        $end_date    = Security::sanitizeInput($_POST['end_date'] ?? '');
        $reason      = Security::sanitizeInput($_POST['reason'] ?? '');

        $valid_types = ['annual', 'sick', 'personal', 'maternity', 'paternity', 'study', 'unpaid'];
        if (!in_array($leave_type, $valid_types)) { $msg = 'Invalid leave type.'; $msg_type = 'error'; }
        elseif ($start_date > $end_date) { $msg = 'End date must be on or after start date.'; $msg_type = 'error'; }
        else {
            $days = (new DateTime($start_date))->diff(new DateTime($end_date))->days + 1;
            $stmt = $db->prepare("
                INSERT INTO hr_leave_requests (employee_id, leave_type, start_date, end_date, days_requested, reason)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$employee['id'], $leave_type, $start_date, $end_date, $days, $reason]);
            $msg = "Leave request submitted for $days day(s). Awaiting HR approval.";
        }
    }
}

// ── Fetch data ───────────────────────────────────────────────────────────────
$my_leave = [];
$my_reviews = [];
if ($employee) {
    $ls = $db->prepare("
        SELECT *, DATEDIFF(end_date, start_date)+1 AS days_count
        FROM hr_leave_requests WHERE employee_id=? ORDER BY created_at DESC
    ");
    $ls->execute([$employee['id']]);
    $my_leave = $ls->fetchAll(PDO::FETCH_ASSOC);

    $rs = $db->prepare("
        SELECT pr.*, u.username AS reviewer_name
        FROM performance_reviews pr
        LEFT JOIN users u ON pr.reviewer_id = u.id
        WHERE pr.employee_id=?
        ORDER BY pr.review_period_end DESC
    ");
    $rs->execute([$employee['id']]);
    $my_reviews = $rs->fetchAll(PDO::FETCH_ASSOC);
}

// ── Leave balance summary (simple annual 15-day allowance) ──────────────────
$leave_taken = 0;
foreach ($my_leave as $lr) {
    if ($lr['leave_type'] === 'annual' && $lr['status'] === 'approved' &&
        date('Y', strtotime($lr['start_date'])) === date('Y')) {
        $leave_taken += (int)$lr['days_requested'];
    }
}
$leave_balance = max(0, 15 - $leave_taken);

// Active tab
$active_tab = $_GET['tab'] ?? 'profile';
$valid_tabs = ['profile', 'leave', 'reviews'];
if (!in_array($active_tab, $valid_tabs)) $active_tab = 'profile';

// Initials for avatar
$initials = 'U';
if ($employee) {
    $initials = strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1));
} elseif (!empty($_SESSION['username'])) {
    $initials = strtoupper(substr($_SESSION['username'], 0, 2));
}

$asset_base = '';
$nav_base   = 'departments/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - KConsulting Hub</title>
    <link rel="icon" type="image/png" href="img/KConsultingLogo1.png">
    <link rel="stylesheet" href="css/main.css">
    <style>
        :root {
            --pr: #4f46e5;
            --pr-dk: #4338ca;
            --pr-vi: #7c3aed;
            --pr-grad: linear-gradient(135deg, #312e81 0%, #4f46e5 100%);
        }

        /* ── Profile hero ── */
        .pr-hero {
            background: var(--pr-grad);
            border-radius: 16px;
            padding: 32px 36px;
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }
        .pr-avatar {
            width: 80px; height: 80px;
            background: rgba(255,255,255,.2);
            border: 3px solid rgba(255,255,255,.4);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; font-weight: 700; color: #fff;
            flex-shrink: 0;
        }
        .pr-hero-info { flex: 1; min-width: 200px; }
        .pr-hero-info h1 { color: #fff; font-size: 1.5rem; font-weight: 700; margin: 0 0 5px; }
        .pr-hero-info p  { color: rgba(255,255,255,.75); font-size: 0.87rem; margin: 0; }
        .pr-hero-chips { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
        .pr-chip {
            background: rgba(255,255,255,.15); color: #fff;
            padding: 3px 12px; border-radius: 20px; font-size: 0.76rem; font-weight: 600;
        }
        .pr-hero-stats { display: flex; gap: 20px; margin-left: auto; flex-wrap: wrap; }
        .pr-hero-stat { text-align: center; }
        .pr-hero-stat .num { color: #fff; font-size: 1.5rem; font-weight: 700; display: block; }
        .pr-hero-stat .lbl { color: rgba(255,255,255,.7); font-size: 0.72rem; text-transform: uppercase; letter-spacing: .4px; }

        /* ── Not-linked card ── */
        .pr-unlinked {
            background: #fff; border: 2px dashed #d1d5db; border-radius: 14px;
            padding: 48px; text-align: center; color: #6b7280;
        }
        .pr-unlinked .icon { font-size: 3rem; margin-bottom: 12px; }
        .pr-unlinked h3 { color: #111827; font-size: 1.1rem; margin-bottom: 8px; }
        .pr-unlinked p  { font-size: 0.87rem; line-height: 1.6; }

        /* ── Flash ── */
        .pr-flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 0.88rem; font-weight: 500; }
        .pr-flash.success { background: #d1fae5; color: #065f46; border-left: 4px solid #059669; }
        .pr-flash.error   { background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; }

        /* ── Tab nav ── */
        .pr-tabs {
            display: flex; gap: 0; background: #fff; border: 1px solid #e5e7eb;
            border-radius: 10px; padding: 4px; margin-bottom: 22px; overflow-x: auto;
        }
        .pr-tab {
            flex: none; padding: 9px 22px; border: none; background: transparent;
            border-radius: 7px; cursor: pointer; font-size: 0.88rem; font-weight: 600;
            color: #6b7280; transition: all .2s; white-space: nowrap;
            text-decoration: none; display: inline-block;
        }
        .pr-tab:hover { background: #f3f4f6; color: #111827; }
        .pr-tab.active { background: var(--pr); color: #fff; }

        /* ── Grid form layout ── */
        .pr-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .pr-grid .full { grid-column: 1 / -1; }
        @media (max-width: 640px) { .pr-grid { grid-template-columns: 1fr; } }

        .pr-field label { display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .pr-field input, .pr-field select, .pr-field textarea {
            width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px;
            font-size: 0.9rem; color: #111827; transition: border .2s;
        }
        .pr-field textarea { height: 80px; resize: vertical; }
        .pr-field input:focus, .pr-field select:focus, .pr-field textarea:focus {
            outline: none; border-color: var(--pr); box-shadow: 0 0 0 3px rgba(79,70,229,.1);
        }
        .pr-field input[readonly] { background: #f9fafb; color: #6b7280; cursor: not-allowed; }

        /* ── Section card ── */
        .pr-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
            padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.05);
        }
        .pr-card h2 { font-size: 1rem; font-weight: 700; color: #111827; margin: 0 0 18px; padding-bottom: 12px; border-bottom: 1px solid #f3f4f6; }
        .pr-card-readonly { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .pr-ro-field { }
        .pr-ro-field .lbl { font-size: 0.72rem; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; margin-bottom: 3px; }
        .pr-ro-field .val { font-size: 0.9rem; font-weight: 500; color: #111827; }

        .pr-btn {
            background: var(--pr); color: #fff; padding: 9px 20px; border: none;
            border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer;
            transition: background .2s;
        }
        .pr-btn:hover { background: var(--pr-dk); }

        /* ── Leave cards ── */
        .leave-grid { display: grid; gap: 12px; }
        .leave-card {
            border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 18px;
            display: flex; align-items: center; gap: 14px;
            background: #fff;
        }
        .leave-type-icon { font-size: 1.5rem; width: 40px; text-align: center; flex-shrink: 0; }
        .leave-card-info { flex: 1; }
        .leave-card-info .leave-type-lbl { font-weight: 700; color: #111827; font-size: 0.9rem; text-transform: capitalize; }
        .leave-card-info .leave-dates { font-size: 0.8rem; color: #6b7280; margin-top: 2px; }
        .leave-status { flex-shrink: 0; }
        .lbadge { padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: capitalize; }
        .lbadge-pending  { background: #fef3c7; color: #92400e; }
        .lbadge-approved { background: #d1fae5; color: #065f46; }
        .lbadge-rejected { background: #fee2e2; color: #991b1b; }
        .lbadge-cancelled{ background: #f3f4f6; color: #6b7280; }

        /* ── Leave balance bar ── */
        .leave-balance-card {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 12px; padding: 20px 24px; color: #fff; margin-bottom: 20px;
            display: flex; align-items: center; gap: 20px; flex-wrap: wrap;
        }
        .lb-num { font-size: 2.5rem; font-weight: 700; }
        .lb-info .title { font-size: 0.9rem; font-weight: 600; opacity: .9; }
        .lb-info .sub   { font-size: 0.78rem; opacity: .7; }
        .lb-bar-wrap { flex: 1; min-width: 140px; }
        .lb-bar-bg { background: rgba(255,255,255,.25); border-radius: 10px; height: 8px; overflow: hidden; }
        .lb-bar-fill { background: #fff; height: 100%; border-radius: 10px; transition: width .4s; }

        /* ── Review cards ── */
        .review-card {
            border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;
            background: #fff; margin-bottom: 14px;
        }
        .review-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 8px; }
        .review-card-head .period { font-weight: 700; color: #111827; font-size: 0.95rem; }
        .review-card-head .reviewer { font-size: 0.8rem; color: #6b7280; }
        .stars { display: flex; gap: 3px; }
        .star { font-size: 1.1rem; }
        .star.filled { color: #f59e0b; }
        .star.empty  { color: #e5e7eb; }
        .review-section { margin-bottom: 12px; }
        .review-section .rlbl { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; font-weight: 700; margin-bottom: 4px; }
        .review-section .rval { font-size: 0.88rem; color: #374151; line-height: 1.6; }
        .rbadge { padding: 2px 10px; border-radius: 20px; font-size: 0.73rem; font-weight: 700; }
        .rbadge-draft     { background: #f3f4f6; color: #374151; }
        .rbadge-published { background: #d1fae5; color: #065f46; }
        .rbadge-completed { background: #e0e7ff; color: #3730a3; }

        /* ── Leave type icons ── */
        .lti-annual    { content: '🌴'; }
        .lti-sick      { content: '🤒'; }
        .lti-personal  { content: '🏠'; }
        .lti-maternity { content: '👶'; }
        .lti-paternity { content: '👶'; }
        .lti-study     { content: '📚'; }
        .lti-unpaid    { content: '📋'; }

        .pr-empty { text-align: center; padding: 40px 20px; color: #9ca3af; font-size: 0.9rem; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <?php if ($msg): ?>
        <div class="pr-flash <?php echo $msg_type; ?>"><?php echo Security::escapeHTML($msg); ?></div>
        <?php endif; ?>

        <?php if (!$employee): ?>
        <!-- ── Not linked state ── -->
        <div class="pr-unlinked">
            <div class="icon">🔗</div>
            <h3>Employee Record Not Linked</h3>
            <p>Your portal account (<?php echo Security::escapeHTML($_SESSION['username']); ?>) is not yet linked to an employee record.<br>
            Please contact <strong>HR</strong> to have your account linked so you can manage leave and view performance reviews.</p>
        </div>

        <?php else: ?>

        <!-- ── Hero ── -->
        <div class="pr-hero">
            <div class="pr-avatar"><?php echo $initials; ?></div>
            <div class="pr-hero-info">
                <h1><?php echo Security::escapeHTML($employee['first_name'] . ' ' . $employee['last_name']); ?></h1>
                <p><?php echo Security::escapeHTML($employee['position'] ?? 'No position set'); ?> &mdash; <?php echo Security::escapeHTML($employee['department'] ?? ''); ?></p>
                <div class="pr-hero-chips">
                    <span class="pr-chip"><?php echo Security::escapeHTML($employee['employee_id']); ?></span>
                    <span class="pr-chip"><?php echo ucfirst($user_role); ?></span>
                    <?php if (!empty($employee['status'])): ?>
                    <span class="pr-chip" style="background:<?php echo $employee['status']==='active'?'rgba(16,185,129,.3)':'rgba(239,68,68,.3)'; ?>">
                        <?php echo ucfirst($employee['status']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pr-hero-stats">
                <div class="pr-hero-stat">
                    <span class="num"><?php echo $leave_balance; ?></span>
                    <span class="lbl">Leave Days Left</span>
                </div>
                <div class="pr-hero-stat">
                    <span class="num"><?php echo count(array_filter($my_leave, fn($l) => $l['status'] === 'approved')); ?></span>
                    <span class="lbl">Leaves Taken</span>
                </div>
                <div class="pr-hero-stat">
                    <span class="num"><?php echo count($my_reviews); ?></span>
                    <span class="lbl">Reviews</span>
                </div>
            </div>
        </div>

        <!-- ── Tab nav ── -->
        <div class="pr-tabs">
            <a href="?tab=profile" class="pr-tab <?php echo $active_tab==='profile'?'active':''; ?>">👤 My Profile</a>
            <a href="?tab=leave"   class="pr-tab <?php echo $active_tab==='leave'  ?'active':''; ?>">📅 Leave (<?php echo count($my_leave); ?>)</a>
            <a href="?tab=reviews" class="pr-tab <?php echo $active_tab==='reviews'?'active':''; ?>">⭐ Reviews (<?php echo count($my_reviews); ?>)</a>
        </div>

        <!-- ══════════════════ TAB: PROFILE ══════════════════ -->
        <?php if ($active_tab === 'profile'): ?>

        <!-- Read-only HR-managed info -->
        <div class="pr-card">
            <h2>Employment Information</h2>
            <div class="pr-card-readonly">
                <div class="pr-ro-field">
                    <div class="lbl">Employee ID</div>
                    <div class="val"><?php echo Security::escapeHTML($employee['employee_id']); ?></div>
                </div>
                <div class="pr-ro-field">
                    <div class="lbl">Department</div>
                    <div class="val"><?php echo Security::escapeHTML($employee['department'] ?? '—'); ?></div>
                </div>
                <div class="pr-ro-field">
                    <div class="lbl">Position</div>
                    <div class="val"><?php echo Security::escapeHTML($employee['position'] ?? '—'); ?></div>
                </div>
                <div class="pr-ro-field">
                    <div class="lbl">Hire Date</div>
                    <div class="val"><?php echo $employee['hire_date'] ? date('d M Y', strtotime($employee['hire_date'])) : '—'; ?></div>
                </div>
                <div class="pr-ro-field">
                    <div class="lbl">Status</div>
                    <div class="val"><?php echo ucfirst($employee['status'] ?? '—'); ?></div>
                </div>
                <div class="pr-ro-field">
                    <div class="lbl">Manager</div>
                    <div class="val"><?php echo $employee['mgr_first'] ? Security::escapeHTML($employee['mgr_first'].' '.$employee['mgr_last']) : '—'; ?></div>
                </div>
            </div>
            <p style="font-size:.78rem;color:#9ca3af;margin-top:16px;">These fields are managed by HR. Contact your HR manager to request changes.</p>
        </div>

        <!-- Editable personal info -->
        <div class="pr-card">
            <h2>Personal Information <span style="font-size:.75rem;font-weight:400;color:#6b7280;">— you can edit these</span></h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="update_profile" value="1">
                <div class="pr-grid">
                    <div class="pr-field">
                        <label>First Name</label>
                        <input type="text" value="<?php echo Security::escapeHTML($employee['first_name']); ?>" readonly>
                    </div>
                    <div class="pr-field">
                        <label>Last Name</label>
                        <input type="text" value="<?php echo Security::escapeHTML($employee['last_name']); ?>" readonly>
                    </div>
                    <div class="pr-field">
                        <label>Work Email</label>
                        <input type="email" value="<?php echo Security::escapeHTML($employee['email']); ?>" readonly>
                    </div>
                    <div class="pr-field">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo Security::escapeHTML($employee['phone'] ?? ''); ?>" placeholder="+27 XX XXX XXXX">
                    </div>
                    <div class="pr-field">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" value="<?php echo Security::escapeHTML($employee['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="pr-field">
                        <label>National ID / Passport</label>
                        <input type="text" name="national_id" value="<?php echo Security::escapeHTML($employee['national_id'] ?? ''); ?>" placeholder="ID number">
                    </div>
                    <div class="pr-field full">
                        <label>Home Address</label>
                        <textarea name="address" placeholder="Street, City, Province, Postal Code"><?php echo Security::escapeHTML($employee['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="pr-field">
                        <label>Emergency Contact Name</label>
                        <input type="text" name="emergency_contact" value="<?php echo Security::escapeHTML($employee['emergency_contact'] ?? ''); ?>" placeholder="Full name">
                    </div>
                    <div class="pr-field">
                        <label>Emergency Contact Phone</label>
                        <input type="tel" name="emergency_phone" value="<?php echo Security::escapeHTML($employee['emergency_phone'] ?? ''); ?>" placeholder="+27 XX XXX XXXX">
                    </div>
                    <div class="pr-field full">
                        <label>Bio / About Me</label>
                        <textarea name="bio" rows="3" placeholder="A short professional bio..."><?php echo Security::escapeHTML($employee['bio'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div style="margin-top:20px;">
                    <button type="submit" class="pr-btn">💾 Save Changes</button>
                </div>
            </form>
        </div>

        <!-- ══════════════════ TAB: LEAVE ══════════════════ -->
        <?php elseif ($active_tab === 'leave'): ?>

        <!-- Balance card -->
        <div class="leave-balance-card">
            <div class="lb-num"><?php echo $leave_balance; ?></div>
            <div class="lb-info">
                <div class="title">Annual Leave Days Remaining</div>
                <div class="sub"><?php echo $leave_taken; ?> of 15 days used this year</div>
            </div>
            <div class="lb-bar-wrap">
                <div class="lb-bar-bg">
                    <div class="lb-bar-fill" style="width:<?php echo round(min(100, ($leave_taken/15)*100)); ?>%"></div>
                </div>
                <div style="font-size:.75rem;opacity:.7;margin-top:5px;text-align:right;"><?php echo round(($leave_taken/15)*100); ?>% used</div>
            </div>
        </div>

        <!-- Book leave form -->
        <div class="pr-card">
            <h2>Book Leave</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="book_leave" value="1">
                <div class="pr-grid">
                    <div class="pr-field">
                        <label>Leave Type *</label>
                        <select name="leave_type" required>
                            <option value="">Select type...</option>
                            <option value="annual">🌴 Annual Leave</option>
                            <option value="sick">🤒 Sick Leave</option>
                            <option value="personal">🏠 Personal Leave</option>
                            <option value="maternity">👶 Maternity Leave</option>
                            <option value="paternity">👶 Paternity Leave</option>
                            <option value="study">📚 Study Leave</option>
                            <option value="unpaid">📋 Unpaid Leave</option>
                        </select>
                    </div>
                    <div class="pr-field" style="align-self:end;">
                        <!-- spacer -->
                    </div>
                    <div class="pr-field">
                        <label>Start Date *</label>
                        <input type="date" name="start_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="pr-field">
                        <label>End Date *</label>
                        <input type="date" name="end_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="pr-field full">
                        <label>Reason / Notes</label>
                        <textarea name="reason" placeholder="Brief explanation (optional for annual leave)..."></textarea>
                    </div>
                </div>
                <div style="margin-top:18px;">
                    <button type="submit" class="pr-btn">📤 Submit Leave Request</button>
                </div>
            </form>
        </div>

        <!-- Leave history -->
        <div class="pr-card">
            <h2>My Leave History</h2>
            <?php if (empty($my_leave)): ?>
                <div class="pr-empty">No leave requests yet.</div>
            <?php else: ?>
            <div class="leave-grid">
                <?php
                $leave_icons = [
                    'annual'=>'🌴','sick'=>'🤒','personal'=>'🏠',
                    'maternity'=>'👶','paternity'=>'👶','study'=>'📚','unpaid'=>'📋'
                ];
                foreach ($my_leave as $lr):
                    $icon = $leave_icons[$lr['leave_type']] ?? '📋';
                ?>
                <div class="leave-card">
                    <div class="leave-type-icon"><?php echo $icon; ?></div>
                    <div class="leave-card-info">
                        <div class="leave-type-lbl"><?php echo ucfirst($lr['leave_type']); ?> Leave &mdash; <?php echo (int)$lr['days_requested']; ?> day<?php echo $lr['days_requested']>1?'s':''; ?></div>
                        <div class="leave-dates">
                            <?php echo date('d M Y', strtotime($lr['start_date'])); ?>
                            <?php if ($lr['start_date'] !== $lr['end_date']): ?> &mdash; <?php echo date('d M Y', strtotime($lr['end_date'])); ?><?php endif; ?>
                            <?php if (!empty($lr['reason'])): ?> &middot; <?php echo Security::escapeHTML(substr($lr['reason'], 0, 60)); ?><?php endif; ?>
                        </div>
                    </div>
                    <div class="leave-status">
                        <span class="lbadge lbadge-<?php echo $lr['status']; ?>"><?php echo ucfirst($lr['status']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══════════════════ TAB: REVIEWS ══════════════════ -->
        <?php elseif ($active_tab === 'reviews'): ?>

        <?php if (empty($my_reviews)): ?>
            <div class="pr-card">
                <div class="pr-empty">No performance reviews yet.<br><span style="font-size:.8rem;">Your manager will create reviews periodically.</span></div>
            </div>
        <?php else: ?>
            <?php foreach ($my_reviews as $review): ?>
            <div class="review-card">
                <div class="review-card-head">
                    <div>
                        <div class="period">
                            <?php echo date('M Y', strtotime($review['review_period_start'])); ?>
                            &mdash;
                            <?php echo date('M Y', strtotime($review['review_period_end'])); ?>
                        </div>
                        <div class="reviewer">Reviewed by: <?php echo Security::escapeHTML($review['reviewer_name'] ?? 'N/A'); ?></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div class="stars">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <span class="star <?php echo $s <= $review['overall_rating'] ? 'filled' : 'empty'; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="rbadge rbadge-<?php echo $review['status']; ?>"><?php echo ucfirst($review['status']); ?></span>
                    </div>
                </div>

                <?php if (!empty($review['goals_achievement'])): ?>
                <div class="review-section">
                    <div class="rlbl">Goals & Achievements</div>
                    <div class="rval"><?php echo nl2br(Security::escapeHTML($review['goals_achievement'])); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($review['strengths'])): ?>
                <div class="review-section">
                    <div class="rlbl">Strengths</div>
                    <div class="rval"><?php echo nl2br(Security::escapeHTML($review['strengths'])); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($review['areas_for_improvement'])): ?>
                <div class="review-section">
                    <div class="rlbl">Areas for Improvement</div>
                    <div class="rval"><?php echo nl2br(Security::escapeHTML($review['areas_for_improvement'])); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($review['comments'])): ?>
                <div class="review-section">
                    <div class="rlbl">Manager Comments</div>
                    <div class="rval"><?php echo nl2br(Security::escapeHTML($review['comments'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php endif; /* end tab */ ?>

        <?php endif; /* end employee check */ ?>

    </div><!-- /.main-content -->

    <script src="js/notification.js"></script>
</body>
</html>

