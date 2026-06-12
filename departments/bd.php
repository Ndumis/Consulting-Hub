<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';
require_once '../includes/page_tracker.php';

Security::requireDepartmentAccess('Business Development');

$database = new Database();
$db = $database->getConnection();

$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'];
$role      = $_SESSION['role'];
$department= $_SESSION['department'];

if ($_POST && isset($_POST['create_lead'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    $db->prepare("INSERT INTO bd_leads (company_name,contact_person,email,phone,industry,notes,created_by) VALUES (?,?,?,?,?,?,?)")
       ->execute([Security::sanitizeInput($_POST['company_name']),Security::sanitizeInput($_POST['contact_person']),Security::sanitizeInput($_POST['email']),Security::sanitizeInput($_POST['phone']),Security::sanitizeInput($_POST['industry']),Security::sanitizeInput($_POST['notes']),$user_id]);
    header("Location: bd.php?view=leads&msg=lead_created"); exit();
}

if ($_POST && isset($_POST['update_lead'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    $db->prepare("UPDATE bd_leads SET company_name=?,contact_person=?,email=?,phone=?,industry=?,status=?,lead_score=?,next_follow_up=?,notes=? WHERE id=?")
       ->execute([Security::sanitizeInput($_POST['company_name']),Security::sanitizeInput($_POST['contact_person']),Security::sanitizeInput($_POST['email']),Security::sanitizeInput($_POST['phone']),Security::sanitizeInput($_POST['industry']),Security::sanitizeInput($_POST['status']),(int)$_POST['lead_score'],Security::sanitizeInput($_POST['next_follow_up']),Security::sanitizeInput($_POST['notes']),(int)$_POST['lead_id']]);
    header("Location: bd.php?view=leads&msg=lead_updated"); exit();
}

if ($_POST && isset($_POST['convert_lead_to_client'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    $lead_id = (int)$_POST['lead_id'];
    $stmt = $db->prepare("SELECT * FROM bd_leads WHERE id=?");
    $stmt->execute([$lead_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lead && $lead['status'] !== 'client') {
        $db->prepare("INSERT INTO clients (name,email,phone,company,status) VALUES (?,?,?,?,'active')")
           ->execute([
               $lead['contact_person'] ?: $lead['company_name'],
               $lead['email'],
               $lead['phone'],
               $lead['company_name'],
           ]);
        $db->prepare("UPDATE bd_leads SET status='client' WHERE id=?")->execute([$lead_id]);
        header("Location: bd.php?view=leads&msg=lead_converted"); exit();
    }
    header("Location: bd.php?view=leads&msg=lead_already_client"); exit();
}

if ($_POST && isset($_POST['log_activity'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    $lead_id = !empty($_POST['lead_id']) ? (int)$_POST['lead_id'] : null;
    $db->prepare("INSERT INTO bd_activities (lead_id,activity_type,activity_date,description,outcome,next_action,next_action_date,created_by) VALUES (?,?,?,?,?,?,?,?)")
       ->execute([$lead_id,Security::sanitizeInput($_POST['activity_type']),Security::sanitizeInput($_POST['activity_date']),Security::sanitizeInput($_POST['description']),Security::sanitizeInput($_POST['outcome']),Security::sanitizeInput($_POST['next_action']),Security::sanitizeInput($_POST['next_action_date']),$user_id]);
    if ($lead_id) {
        $db->prepare("UPDATE bd_leads SET last_contact_date=? WHERE id=?")->execute([Security::sanitizeInput($_POST['activity_date']),$lead_id]);
    }
    header("Location: bd.php?view=activities&msg=activity_logged"); exit();
}

if ($_POST && isset($_POST['create_task'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    $db->prepare("INSERT INTO bd_tasks (task_description,due_date,assigned_to,related_lead_id,priority,created_by) VALUES (?,?,?,?,?,?)")
       ->execute([Security::sanitizeInput($_POST['task_description']),Security::sanitizeInput($_POST['due_date']),(int)$_POST['assigned_to'],!empty($_POST['related_lead_id'])?(int)$_POST['related_lead_id']:null,Security::sanitizeInput($_POST['priority']),$user_id]);
    header("Location: bd.php?view=tasks&msg=task_created"); exit();
}

if ($_POST && isset($_POST['complete_task'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    $db->prepare("UPDATE bd_tasks SET status='completed' WHERE id=?")->execute([(int)$_POST['task_id']]);
    header("Location: bd.php?view=tasks&msg=task_completed"); exit();
}

if ($_POST && isset($_POST['set_targets'])) {
    Security::checkCSRFToken();
    Security::requireWriteAccess('Business Development');
    $my = Security::sanitizeInput($_POST['month_year']).'-01';
    $lt = (int)$_POST['lead_target']; $mt = (int)$_POST['meeting_target']; $ct = (int)$_POST['client_target'];
    try {
        $ex = $db->prepare("SELECT id FROM bd_targets WHERE month_year=?"); $ex->execute([$my]); $exists = $ex->fetch();
        if ($exists) $db->prepare("UPDATE bd_targets SET lead_target=?,meeting_target=?,client_target=? WHERE month_year=?")->execute([$lt,$mt,$ct,$my]);
        else $db->prepare("INSERT INTO bd_targets (month_year,lead_target,meeting_target,client_target) VALUES (?,?,?,?)")->execute([$my,$lt,$mt,$ct]);
        header("Location: bd.php?view=targets&msg=targets_updated"); exit();
    } catch (Exception $e) {
        header("Location: bd.php?view=targets&msg=targets_failed"); exit();
    }
}

$view = $_GET['view'] ?? 'overview';
$msg  = $_GET['msg']  ?? '';

$current_month = date('Y-m-01');
$targets = ['lead_target'=>15,'meeting_target'=>5,'client_target'=>1];
try {
    $tr = $db->prepare("SELECT lead_target,meeting_target,client_target FROM bd_targets WHERE month_year=?"); $tr->execute([$current_month]);
    $td = $tr->fetch(PDO::FETCH_ASSOC); if ($td) $targets = $td;
} catch (Exception $e) {}

$stats = ['total_leads'=>0,'new_leads'=>0,'meetings_booked'=>0,'clients_converted'=>0,'weekly_calls'=>0,'weekly_emails'=>0,'weekly_meetings'=>0,'total_activities'=>0];
try {
    $stats['total_leads']      = $db->query("SELECT COUNT(*) FROM bd_leads")->fetchColumn();
    $s = $db->prepare("SELECT COUNT(*) FROM bd_leads WHERE created_at>=?"); $s->execute([$current_month]); $stats['new_leads']=$s->fetchColumn();
    $s = $db->prepare("SELECT COUNT(DISTINCT lead_id) FROM bd_activities WHERE activity_type='meeting' AND DATE(activity_date)>=?"); $s->execute([$current_month]); $stats['meetings_booked']=$s->fetchColumn();
    $stats['clients_converted']= $db->query("SELECT COUNT(*) FROM bd_leads WHERE status='client'")->fetchColumn();
    $stats['total_activities'] = $db->query("SELECT COUNT(*) FROM bd_activities")->fetchColumn();
    $wa = $db->query("SELECT activity_type,COUNT(*) as c FROM bd_activities WHERE activity_date>=DATE_SUB(NOW(),INTERVAL 7 DAY) GROUP BY activity_type")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($wa as $w) {
        if ($w['activity_type']==='call')    $stats['weekly_calls']    = $w['c'];
        if ($w['activity_type']==='email')   $stats['weekly_emails']   = $w['c'];
        if ($w['activity_type']==='meeting') $stats['weekly_meetings'] = $w['c'];
    }
} catch (Exception $e) {}

$leads         = $db->query("SELECT * FROM bd_leads ORDER BY lead_score DESC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$all_activities= $db->query("SELECT a.*,l.company_name FROM bd_activities a LEFT JOIN bd_leads l ON a.lead_id=l.id ORDER BY a.activity_date DESC")->fetchAll(PDO::FETCH_ASSOC);
$all_tasks     = $db->query("SELECT t.*,l.company_name,u.username as assigned_username FROM bd_tasks t LEFT JOIN bd_leads l ON t.related_lead_id=l.id LEFT JOIN users u ON t.assigned_to=u.id ORDER BY FIELD(t.priority,'high','medium','low'),t.due_date ASC")->fetchAll(PDO::FETCH_ASSOC);
$all_targets   = $db->query("SELECT * FROM bd_targets ORDER BY month_year DESC")->fetchAll(PDO::FETCH_ASSOC);
$recent_activities = $db->query("SELECT a.*,l.company_name FROM bd_activities a LEFT JOIN bd_leads l ON a.lead_id=l.id ORDER BY a.activity_date DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
$pending_tasks     = $db->query("SELECT t.*,l.company_name FROM bd_tasks t LEFT JOIN bd_leads l ON t.related_lead_id=l.id WHERE t.status='pending' ORDER BY t.due_date ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
$high_leads        = $db->query("SELECT * FROM bd_leads WHERE lead_score>=70 AND status NOT IN ('client') ORDER BY lead_score DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$users             = $db->query("SELECT id,username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

$conversion_rate = $stats['total_leads'] > 0 ? round(($stats['clients_converted'] / $stats['total_leads']) * 100, 1) : 0;

$industries    = ['insurance'=>'🛡️ Insurance','finance'=>'💰 Finance','technology'=>'💻 Technology','healthcare'=>'🏥 Healthcare','retail'=>'🛒 Retail','other'=>'🏢 Other'];
$activity_types= ['call'=>'📞 Phone Call','email'=>'📧 Email','meeting'=>'👥 Meeting','follow_up'=>'🔔 Follow-up','proposal'=>'📄 Proposal Sent'];
$lead_statuses = ['new'=>'New','contacted'=>'Contacted','meeting_booked'=>'Meeting Booked','proposal_sent'=>'Proposal Sent','client'=>'Client'];
$act_icons     = ['call'=>'📞','email'=>'📧','meeting'=>'👥','follow_up'=>'🔔','proposal'=>'📄'];
$ind_icons     = ['insurance'=>'🛡️','finance'=>'💰','technology'=>'💻','healthcare'=>'🏥','retail'=>'🛒','other'=>'🏢'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Business Development — KConsulting Hub</title>
<link rel="icon" type="image/png" href="../img/KConsultingLogo1.png">
    <link rel="stylesheet" href="../css/main.css">
<style>
/* BD color tokens: emerald #059669, blue #2563eb */

/* ── HERO ── */
.bd-hero{background:linear-gradient(135deg,#059669 0%,#2563eb 100%);border-radius:14px;padding:1.75rem 2rem;color:#fff;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;}
.bd-hero h2{margin:0 0 .2rem;font-size:1.5rem;font-weight:800;}
.bd-hero p{margin:0;font-size:.875rem;opacity:.85;}
.hero-btn{padding:.55rem 1.1rem;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;border:2px solid rgba(255,255,255,.4);background:rgba(255,255,255,.15);color:#fff;text-decoration:none;transition:all .2s;}
.hero-btn:hover{background:rgba(255,255,255,.3);}
.hero-btn.primary{background:#fff;color:#059669;border-color:#fff;}

/* ── STATS ── */
.bd-stats{display:grid;grid-template-columns:repeat(7,1fr);gap:.85rem;margin-bottom:1.5rem;}
@media(max-width:1100px){.bd-stats{grid-template-columns:repeat(4,1fr);}}
@media(max-width:600px){.bd-stats{grid-template-columns:repeat(2,1fr);}}
.bd-stat{background:#fff;border-radius:10px;padding:.9rem 1rem;box-shadow:0 1px 4px rgba(0,0,0,.07);text-align:center;}
.bd-stat .n{font-size:1.5rem;font-weight:800;color:#111827;line-height:1;}
.bd-stat .l{font-size:.72rem;color:#6b7280;margin-top:.2rem;}

/* ── TABS ── */
.bd-tabs{display:flex;background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.07);overflow:hidden;margin-bottom:1.5rem;flex-wrap:wrap;}
.bd-tab{flex:1;min-width:90px;padding:.85rem .6rem;text-decoration:none;color:#6b7280;text-align:center;font-size:.8rem;font-weight:600;border-right:1px solid #f3f4f6;transition:all .2s;white-space:nowrap;}
.bd-tab:last-child{border-right:none;}
.bd-tab.active{background:#059669;color:#fff;}
.bd-tab:hover:not(.active){background:#f0fdf4;color:#059669;}

/* ── SECTION HEADER ── */
.section-header-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.6rem;}
.section-header-row h3{margin:0;font-size:1rem;font-weight:700;color:#111827;}
.create-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.55rem 1.1rem;background:linear-gradient(135deg,#059669,#2563eb);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;transition:opacity .2s;}
.create-btn:hover{opacity:.88;}
.create-btn.open{background:linear-gradient(135deg,#6b7280,#9ca3af);}

/* ── CONTROLS ROW ── */
.controls-row{display:flex;gap:.6rem;align-items:center;background:#fff;border-radius:10px;padding:.75rem 1rem;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:1.25rem;flex-wrap:wrap;}
.controls-row input[type=text]{flex:1;min-width:180px;padding:.5rem .85rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.875rem;}
.controls-row input[type=text]:focus{outline:none;border-color:#059669;box-shadow:0 0 0 3px rgba(5,150,105,.1);}
.controls-row select{padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.875rem;background:#fafafa;cursor:pointer;}
.controls-row select:focus{outline:none;border-color:#059669;}
.result-count{margin-left:auto;font-size:.8rem;color:#9ca3af;white-space:nowrap;}

/* ── FORM CARD ── */
.form-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden;margin-bottom:1.5rem;}
.form-card-head{background:linear-gradient(135deg,#059669,#2563eb);padding:1.25rem 1.75rem;color:#fff;}
.form-card-head h3{margin:0 0 .1rem;font-size:1.1rem;font-weight:700;}
.form-card-head p{margin:0;font-size:.8rem;opacity:.85;}
.form-card-body{padding:1.75rem;}
.form-section h4{font-size:.88rem;font-weight:700;color:#111827;margin:0 0 .9rem;padding-bottom:.45rem;border-bottom:2px solid #f3f4f6;}
.fg{margin-bottom:.9rem;}
.fg label{display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:.35rem;}
.fg label .req{color:#ef4444;}
.fg label .opt{color:#9ca3af;font-weight:400;}
.fg input,.fg select,.fg textarea{width:100%;padding:.55rem .85rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.875rem;color:#111827;box-sizing:border-box;transition:border .15s;}
.fg input:focus,.fg select:focus,.fg textarea:focus{outline:none;border-color:#059669;box-shadow:0 0 0 3px rgba(5,150,105,.12);}
.form-2col{display:grid;grid-template-columns:1fr 1fr;gap:.9rem;}
@media(max-width:640px){.form-2col{grid-template-columns:1fr;}}
.form-actions{display:flex;gap:.75rem;align-items:center;margin-top:1.25rem;flex-wrap:wrap;}

/* ── LEAD CARDS ── */
.lead-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;}
.lead-card{background:#fff;border-radius:12px;border:1px solid #f3f4f6;box-shadow:0 2px 6px rgba(0,0,0,.06);overflow:hidden;transition:transform .2s,box-shadow .2s;}
.lead-card:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,.1);}
.lead-score-bar{height:4px;}
.lead-card-body{padding:1rem 1.25rem .75rem;}
.lead-company{font-size:.98rem;font-weight:700;color:#111827;margin-bottom:.2rem;}
.lead-industry{font-size:.73rem;color:#9ca3af;margin-bottom:.6rem;}
.lead-contact-row{font-size:.78rem;color:#374151;margin:.2rem 0;display:flex;align-items:center;gap:.4rem;}
.lead-score-info{display:flex;align-items:center;gap:.5rem;margin:.75rem 0 .5rem;}
.score-pill{padding:.15rem .5rem;border-radius:20px;font-size:.7rem;font-weight:700;}
.score-high{background:#dcfce7;color:#166534;}
.score-mid{background:#fef9c3;color:#854d0e;}
.score-low{background:#fee2e2;color:#991b1b;}
.lead-card-foot{padding:.6rem 1.25rem;border-top:1px solid #f9fafb;background:#fafafa;display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap;}

/* Status / priority badges */
.badge{display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap;}
.s-new{background:#fef9c3;color:#854d0e;}
.s-contacted{background:#dbeafe;color:#1e40af;}
.s-meeting_booked{background:#dcfce7;color:#166534;}
.s-proposal_sent{background:#ede9fe;color:#6d28d9;}
.s-client{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
.p-high{background:#fee2e2;color:#991b1b;}
.p-medium{background:#fef9c3;color:#854d0e;}
.p-low{background:#dbeafe;color:#1e40af;}
.t-pending{background:#fef9c3;color:#854d0e;}
.t-completed{background:#dcfce7;color:#166534;}

/* Inline edit */
.inline-edit{display:none;padding:.85rem 1.25rem 1rem;border-top:2px dashed #f3f4f6;}
.inline-edit h5{font-size:.78rem;font-weight:700;color:#6b7280;margin:0 0 .85rem;text-transform:uppercase;letter-spacing:.5px;}

/* ── ACTIVITY TIMELINE ── */
.act-timeline{display:flex;flex-direction:column;gap:0;}
.act-item{display:flex;gap:1rem;padding:1rem 1.25rem;background:#fff;border-radius:0;border-left:3px solid #e5e7eb;border-bottom:1px solid #f3f4f6;transition:background .15s;}
.act-item:first-child{border-radius:12px 12px 0 0;}
.act-item:last-child{border-radius:0 0 12px 12px;border-bottom:none;}
.act-item:hover{background:#f8fffe;}
.act-item.type-call{border-left-color:#059669;}
.act-item.type-email{border-left-color:#2563eb;}
.act-item.type-meeting{border-left-color:#7c3aed;}
.act-item.type-follow_up{border-left-color:#f59e0b;}
.act-item.type-proposal{border-left-color:#ec4899;}
.act-icon{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;background:#f0fdf4;}
.act-body{flex:1;min-width:0;}
.act-company{font-size:.85rem;font-weight:700;color:#111827;margin-bottom:.15rem;}
.act-desc{font-size:.8rem;color:#374151;margin-bottom:.25rem;line-height:1.4;}
.act-outcome{font-size:.75rem;color:#6b7280;}
.act-meta{text-align:right;font-size:.72rem;color:#9ca3af;white-space:nowrap;flex-shrink:0;}
.act-next{font-size:.73rem;color:#2563eb;margin-top:.2rem;}

/* ── TASK CARDS ── */
.task-list{display:flex;flex-direction:column;gap:.75rem;}
.task-card{background:#fff;border-radius:12px;border:1px solid #f3f4f6;box-shadow:0 1px 4px rgba(0,0,0,.05);display:flex;overflow:hidden;transition:transform .15s,box-shadow .15s;}
.task-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08);}
.task-priority-strip{width:4px;flex-shrink:0;}
.task-priority-strip.high{background:#ef4444;}
.task-priority-strip.medium{background:#f59e0b;}
.task-priority-strip.low{background:#3b82f6;}
.task-body{flex:1;padding:.85rem 1.1rem;}
.task-desc{font-size:.88rem;font-weight:700;color:#111827;margin-bottom:.3rem;}
.task-meta{font-size:.73rem;color:#6b7280;display:flex;gap:.75rem;flex-wrap:wrap;}
.task-foot{display:flex;align-items:center;padding:.6rem 1rem;border-left:1px solid #f3f4f6;flex-direction:column;gap:.4rem;justify-content:center;}

/* ── OVERVIEW GRID ── */
.overview-2col{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;}
@media(max-width:900px){.overview-2col{grid-template-columns:1fr;}}
.overview-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden;}
.overview-card-head{background:linear-gradient(135deg,#059669,#2563eb);padding:.85rem 1.25rem;display:flex;justify-content:space-between;align-items:center;}
.overview-card-head h4{margin:0;font-size:.9rem;font-weight:700;color:#fff;}
.overview-card-body{padding:1rem 1.25rem;}

/* Quick actions */
.quick-actions{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;}
.qa-card{background:#fff;border-radius:12px;border:1px solid #f3f4f6;box-shadow:0 2px 6px rgba(0,0,0,.06);padding:1.25rem 1rem;text-decoration:none;color:#111827;text-align:center;transition:all .2s;}
.qa-card:hover{transform:translateY(-3px);box-shadow:0 6px 16px rgba(0,0,0,.1);border-color:#059669;}
.qa-icon{font-size:1.75rem;margin-bottom:.5rem;}
.qa-label{font-size:.82rem;font-weight:600;color:#374151;}

/* Target / progress */
.target-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;}
@media(max-width:640px){.target-grid{grid-template-columns:1fr;}}
.target-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:1.25rem;}
.target-card .target-nums{font-size:1.5rem;font-weight:800;color:#111827;margin-bottom:.25rem;}
.target-card .target-lbl{font-size:.75rem;color:#6b7280;margin-bottom:.75rem;}
.target-prog-track{height:8px;background:#e5e7eb;border-radius:20px;overflow:hidden;margin-bottom:.3rem;}
.target-prog-fill{height:100%;border-radius:20px;background:linear-gradient(90deg,#059669,#2563eb);transition:width .5s;}
.target-pct{font-size:.72rem;color:#9ca3af;}

/* Reports */
.report-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem;}
.report-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:1.25rem;}
.report-card h4{font-size:.9rem;font-weight:700;color:#111827;margin:0 0 1rem;padding-bottom:.5rem;border-bottom:2px solid #f3f4f6;}
.report-row{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;}
.report-row:last-child{margin-bottom:0;}
.report-label{font-size:.78rem;font-weight:600;color:#374151;min-width:120px;}
.report-bar-track{flex:1;height:8px;background:#f3f4f6;border-radius:20px;overflow:hidden;}
.report-bar-fill{height:100%;border-radius:20px;background:linear-gradient(90deg,#059669,#2563eb);}
.report-val{font-size:.78rem;font-weight:700;color:#111827;min-width:36px;text-align:right;}

/* Flash */
.flash{padding:.75rem 1.1rem;border-radius:8px;margin-bottom:1.1rem;font-size:.875rem;font-weight:500;}
.flash-success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
.flash-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}

/* Empty */
.empty-box{text-align:center;padding:3rem 1.5rem;background:#fff;border-radius:12px;border:2px dashed #e5e7eb;}
.empty-box .emoji{font-size:3rem;margin-bottom:.75rem;}
.empty-box h3{color:#374151;margin-bottom:.4rem;}
.empty-box p{color:#9ca3af;font-size:.875rem;}

/* No results */
.no-results{text-align:center;padding:2rem;background:#fff;border-radius:12px;border:2px dashed #f3f4f6;color:#9ca3af;font-size:.875rem;display:none;}

/* Btn xs */
.btn-xs{padding:.28rem .6rem;font-size:.73rem;border-radius:6px;border:1px solid #e5e7eb;background:#fafafa;color:#374151;cursor:pointer;transition:all .15s;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:.25rem;}
.btn-xs:hover{background:#059669;color:#fff;border-color:#059669;}
.btn-xs.danger:hover{background:#ef4444;border-color:#ef4444;}
</style>
</head>
<body>
<?php
$asset_base = '../';
$nav_base   = '';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">

<?php
$flash_map = [
    'lead_created'=>'✅ Lead added successfully.','lead_updated'=>'✅ Lead updated.',
    'activity_logged'=>'✅ Activity logged.','task_created'=>'✅ Task created.',
    'task_completed'=>'✅ Task marked complete.','targets_updated'=>'✅ Targets updated.',
    'targets_failed'=>'❌ Failed to save targets — please try again.',
    'lead_converted'=>'✅ Lead converted to client.','lead_already_client'=>'ℹ️ This lead is already a client.',
];
if ($msg && isset($flash_map[$msg])): ?>
<div class="flash <?= str_starts_with($msg,'targets_failed')?'flash-error':'flash-success' ?>" id="flashMsg"><?= $flash_map[$msg] ?></div>
<?php endif; ?>

<!-- Hero -->
<div class="bd-hero">
    <div>
        <h2>🎯 Business Development</h2>
        <p>Lead pipeline, activities, tasks &amp; targets</p>
    </div>
    <?php if (in_array($role,['admin','manager'])): ?>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
        <a href="?view=leads"      class="hero-btn">➕ New Lead</a>
        <a href="?view=activities" class="hero-btn">📞 Log Activity</a>
        <a href="?view=tasks"      class="hero-btn primary">✅ Add Task</a>
    </div>
    <?php endif; ?>
</div>

<!-- Stats bar -->
<div class="bd-stats">
    <div class="bd-stat"><div class="n"><?= $stats['total_leads'] ?></div><div class="l">Total Leads</div></div>
    <div class="bd-stat"><div class="n" style="color:#2563eb"><?= $stats['new_leads'] ?></div><div class="l">New This Month</div></div>
    <div class="bd-stat"><div class="n" style="color:#059669"><?= $stats['meetings_booked'] ?></div><div class="l">Meetings</div></div>
    <div class="bd-stat"><div class="n" style="color:#7c3aed"><?= $stats['clients_converted'] ?></div><div class="l">Clients Won</div></div>
    <div class="bd-stat"><div class="n" style="color:#f59e0b;font-size:1.2rem;"><?= $conversion_rate ?>%</div><div class="l">Conversion</div></div>
    <div class="bd-stat"><div class="n" style="color:#ec4899"><?= $stats['total_activities'] ?></div><div class="l">Activities</div></div>
    <div class="bd-stat"><div class="n" style="color:#059669"><?= count($pending_tasks) ?></div><div class="l">Open Tasks</div></div>
</div>

<!-- Tabs -->
<div class="bd-tabs">
    <a href="?view=overview"   class="bd-tab <?= $view==='overview'?'active':'' ?>">📊 Overview</a>
    <a href="?view=leads"      class="bd-tab <?= $view==='leads'?'active':'' ?>">📋 Leads (<?= count($leads) ?>)</a>
    <a href="?view=activities" class="bd-tab <?= $view==='activities'?'active':'' ?>">📞 Activities (<?= count($all_activities) ?>)</a>
    <a href="?view=tasks"      class="bd-tab <?= $view==='tasks'?'active':'' ?>">✅ Tasks (<?= count($all_tasks) ?>)</a>
    <a href="?view=targets"    class="bd-tab <?= $view==='targets'?'active':'' ?>">🎯 Targets</a>
    <a href="?view=reports"    class="bd-tab <?= $view==='reports'?'active':'' ?>">📈 Reports</a>
</div>

<!-- ══ OVERVIEW ══ -->
<?php if ($view==='overview'): ?>
<div class="quick-actions">
    <a href="?view=leads"      class="qa-card"><div class="qa-icon">📋</div><div class="qa-label">Add Lead</div></a>
    <a href="?view=activities" class="qa-card"><div class="qa-icon">📞</div><div class="qa-label">Log Activity</div></a>
    <a href="?view=tasks"      class="qa-card"><div class="qa-icon">✅</div><div class="qa-label">Create Task</div></a>
    <a href="?view=targets"    class="qa-card"><div class="qa-icon">🎯</div><div class="qa-label">Set Targets</div></a>
    <a href="?view=reports"    class="qa-card"><div class="qa-icon">📈</div><div class="qa-label">View Reports</div></a>
</div>

<!-- Monthly progress -->
<div class="target-grid" style="margin-bottom:1.5rem;">
    <?php
    $tdata = [
        ['label'=>'Monthly Leads','actual'=>$stats['new_leads'],'target'=>$targets['lead_target'],'icon'=>'📋'],
        ['label'=>'Meetings Booked','actual'=>$stats['meetings_booked'],'target'=>$targets['meeting_target'],'icon'=>'👥'],
        ['label'=>'Clients Won','actual'=>$stats['clients_converted'],'target'=>$targets['client_target'],'icon'=>'🏆'],
    ];
    foreach ($tdata as $td):
        $pct = $td['target']>0 ? min(round($td['actual']/$td['target']*100,1),100) : 0;
        $col = $pct>=100?'#059669':($pct>=50?'#f59e0b':'#ef4444');
    ?>
    <div class="target-card">
        <div style="font-size:1.5rem;margin-bottom:.35rem;"><?= $td['icon'] ?></div>
        <div class="target-nums" style="color:<?= $col ?>"><?= $td['actual'] ?><span style="font-size:1rem;color:#9ca3af;">/<?= $td['target'] ?></span></div>
        <div class="target-lbl"><?= $td['label'] ?></div>
        <div class="target-prog-track"><div class="target-prog-fill" style="width:<?= $pct ?>%;background:<?= $pct>=100?'linear-gradient(90deg,#059669,#16a34a)':($pct>=50?'linear-gradient(90deg,#f59e0b,#d97706)':'linear-gradient(90deg,#ef4444,#dc2626)') ?>;"></div></div>
        <div class="target-pct"><?= $pct ?>% of target</div>
    </div>
    <?php endforeach; ?>
</div>

<div class="overview-2col">
    <!-- High potential leads -->
    <div class="overview-card">
        <div class="overview-card-head"><h4>🔥 High Potential Leads</h4><a href="?view=leads" style="color:rgba(255,255,255,.8);font-size:.75rem;text-decoration:none;">View all →</a></div>
        <div class="overview-card-body">
            <?php if (empty($high_leads)): ?>
            <div style="text-align:center;padding:1.5rem;color:#9ca3af;font-size:.85rem;">No high-scoring leads yet.</div>
            <?php else: foreach ($high_leads as $hl):
                $sc=$hl['lead_score']; $scCol=$sc>=70?'#059669':($sc>=40?'#f59e0b':'#ef4444');
            ?>
            <div style="display:flex;align-items:center;gap:.75rem;padding:.6rem 0;border-bottom:1px solid #f3f4f6;">
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#059669,#2563eb);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff;flex-shrink:0;"><?= $sc ?></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.85rem;font-weight:700;color:#111827;"><?= Security::escapeHTML($hl['company_name']) ?></div>
                    <div style="font-size:.72rem;color:#9ca3af;"><?= $ind_icons[$hl['industry']]??'🏢' ?> <?= ucfirst($hl['industry']) ?> · <span class="badge s-<?= $hl['status'] ?>"><?= ucfirst(str_replace('_',' ',$hl['status'])) ?></span></div>
                </div>
                <?php if ($hl['next_follow_up']): ?><div style="font-size:.7rem;color:#6b7280;white-space:nowrap;">📅 <?= date('M j',strtotime($hl['next_follow_up'])) ?></div><?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Pending tasks -->
    <div class="overview-card">
        <div class="overview-card-head"><h4>✅ Pending Tasks</h4><a href="?view=tasks" style="color:rgba(255,255,255,.8);font-size:.75rem;text-decoration:none;">View all →</a></div>
        <div class="overview-card-body">
            <?php if (empty($pending_tasks)): ?>
            <div style="text-align:center;padding:1.5rem;color:#9ca3af;font-size:.85rem;">No pending tasks.</div>
            <?php else: foreach ($pending_tasks as $pt):
                $overdue = $pt['due_date'] && strtotime($pt['due_date'])<time();
            ?>
            <div style="display:flex;align-items:flex-start;gap:.75rem;padding:.6rem 0;border-bottom:1px solid #f3f4f6;">
                <div class="task-priority-strip <?= $pt['priority'] ?>" style="width:3px;height:36px;border-radius:20px;flex-shrink:0;margin-top:2px;"></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.85rem;font-weight:600;color:#111827;margin-bottom:.1rem;"><?= Security::escapeHTML(mb_strimwidth($pt['task_description'],0,55,'…')) ?></div>
                    <div style="font-size:.72rem;color:<?= $overdue?'#ef4444':'#9ca3af' ?>;">
                        <?= $pt['due_date']?'📅 '.date('M j, Y',strtotime($pt['due_date'])):'No deadline' ?>
                        <?= $overdue?' · ⚠️ Overdue':'' ?>
                    </div>
                </div>
                <span class="badge p-<?= $pt['priority'] ?>"><?= ucfirst($pt['priority']) ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Recent activities -->
    <div class="overview-card" style="grid-column:1/-1;">
        <div class="overview-card-head"><h4>📞 Recent Activities</h4><a href="?view=activities" style="color:rgba(255,255,255,.8);font-size:.75rem;text-decoration:none;">View all →</a></div>
        <div class="overview-card-body" style="padding:0;">
            <?php if (empty($recent_activities)): ?>
            <div style="text-align:center;padding:1.5rem;color:#9ca3af;font-size:.85rem;">No activities yet.</div>
            <?php else: ?>
            <div class="act-timeline" style="border-radius:0 0 12px 12px;overflow:hidden;">
                <?php foreach (array_slice($recent_activities,0,5) as $act): ?>
                <div class="act-item type-<?= $act['activity_type'] ?>">
                    <div class="act-icon"><?= $act_icons[$act['activity_type']]??'📝' ?></div>
                    <div class="act-body">
                        <div class="act-company"><?= Security::escapeHTML($act['company_name']??'General') ?></div>
                        <div class="act-desc"><?= Security::escapeHTML(mb_strimwidth($act['description'],0,90,'…')) ?></div>
                        <?php if ($act['outcome']): ?><div class="act-outcome">Outcome: <?= Security::escapeHTML(mb_strimwidth($act['outcome'],0,60,'…')) ?></div><?php endif; ?>
                    </div>
                    <div class="act-meta"><?= date('M j, g:ia',strtotime($act['activity_date'])) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ LEADS ══ -->
<?php elseif ($view==='leads'): ?>

<?php if (in_array($role,['admin','manager'])): ?>
<div class="section-header-row">
    <h3>📋 Lead Pipeline</h3>
    <button class="create-btn" id="lead-toggle" onclick="toggleForm('lead-form-wrap','lead-toggle','➕ Add Lead','✕ Cancel')">➕ Add Lead</button>
</div>
<div id="lead-form-wrap" style="display:none;">
    <div class="form-card">
        <div class="form-card-head"><h3>📋 Add New Lead</h3><p>Capture a new prospect into the pipeline</p></div>
        <div class="form-card-body">
            <form method="post">
                <?= Security::getCSRFTokenField() ?>
                <div class="form-section"><h4>🏢 Company Information</h4>
                    <div class="form-2col">
                        <div class="fg"><label>Company Name <span class="req">*</span></label><input type="text" name="company_name" required placeholder="e.g. Acme Corp"></div>
                        <div class="fg"><label>Industry <span class="req">*</span></label>
                            <select name="industry" required><option value="">— Select —</option>
                                <?php foreach ($industries as $v=>$l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-section" style="margin-top:1.25rem;"><h4>👤 Contact Details</h4>
                    <div class="form-2col">
                        <div class="fg"><label>Contact Person <span class="req">*</span></label><input type="text" name="contact_person" required placeholder="Full name"></div>
                        <div class="fg"><label>Email <span class="req">*</span></label><input type="email" name="email" required placeholder="contact@company.com"></div>
                        <div class="fg"><label>Phone <span class="opt">(optional)</span></label><input type="tel" name="phone" placeholder="+27 11 000 0000"></div>
                    </div>
                </div>
                <div class="form-section" style="margin-top:1.25rem;"><h4>📝 Notes <span style="color:#9ca3af;font-weight:400;font-size:.8rem;">(optional)</span></h4>
                    <div class="fg" style="margin-bottom:0;"><textarea name="notes" rows="3" placeholder="Initial notes, referral source, talking points…"></textarea></div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="create_lead" class="btn" style="background:#059669;color:#fff;border-color:#059669;padding:.65rem 1.5rem;">📋 Add Lead</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm('lead-form-wrap','lead-toggle','➕ Add Lead','✕ Cancel')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
<div class="section-header-row"><h3>📋 Lead Pipeline</h3></div>
<?php endif; ?>

<!-- Filters -->
<div class="controls-row">
    <input type="text" id="lead-search" placeholder="🔍  Search company, contact, email…" oninput="filterBD('lead-grid','lead-search',{industry:'lead-f-ind',status:'lead-f-status'},'lead-count')">
    <select id="lead-f-ind" onchange="filterBD('lead-grid','lead-search',{industry:'lead-f-ind',status:'lead-f-status'},'lead-count')">
        <option value="">All Industries</option>
        <?php foreach ($industries as $v=>$l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
    </select>
    <select id="lead-f-status" onchange="filterBD('lead-grid','lead-search',{industry:'lead-f-ind',status:'lead-f-status'},'lead-count')">
        <option value="">All Statuses</option>
        <?php foreach ($lead_statuses as $v=>$l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
    </select>
    <div class="lc-date-filter">
        <select id="lead-date-range">
            <option value="all">All Dates</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
            <option value="lastyear">Last Year</option>
            <option value="custom">Custom Range</option>
        </select>
        <span class="lc-custom-range">
            <input type="date" id="lead-date-from">
            <span>to</span>
            <input type="date" id="lead-date-to">
        </span>
    </div>
    <span class="result-count" id="lead-count"><?= count($leads) ?> lead<?= count($leads)!=1?'s':'' ?></span>
</div>

<?php if (empty($leads)): ?>
<div class="empty-box"><div class="emoji">📋</div><h3>No leads yet</h3><p>Add your first prospect using the button above.</p></div>
<?php else: ?>
<div class="no-results" id="lead-no-results">No leads match your filters.</div>
<div class="lead-grid" id="lead-grid">
    <?php foreach ($leads as $lead):
        $sc=$lead['lead_score']; $scCol=$sc>=70?'#059669':($sc>=40?'#f59e0b':'#ef4444');
        $scClass=$sc>=70?'score-high':($sc>=40?'score-mid':'score-low');
        $searchText=strtolower($lead['company_name'].' '.$lead['contact_person'].' '.$lead['email'].' '.$lead['industry']);
    ?>
    <div class="lead-card"
         data-search="<?= htmlspecialchars($searchText,ENT_QUOTES) ?>"
         data-industry="<?= $lead['industry'] ?>"
         data-status="<?= $lead['status'] ?>"
         data-date="<?= !empty($lead['created_at']) ? date('Y-m-d', strtotime($lead['created_at'])) : '' ?>">
        <div class="lead-score-bar" style="background:linear-gradient(90deg,<?= $scCol ?> <?= $sc ?>%,#e5e7eb <?= $sc ?>%);"></div>
        <div class="lead-card-body">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;margin-bottom:.3rem;">
                <div class="lead-company"><?= Security::escapeHTML($lead['company_name']) ?></div>
                <span class="badge s-<?= $lead['status'] ?>"><?= ucfirst(str_replace('_',' ',$lead['status'])) ?></span>
            </div>
            <div class="lead-industry"><?= $ind_icons[$lead['industry']]??'🏢' ?> <?= ucfirst($lead['industry']) ?></div>
            <?php if ($lead['contact_person']): ?><div class="lead-contact-row">👤 <?= Security::escapeHTML($lead['contact_person']) ?></div><?php endif; ?>
            <?php if ($lead['email']): ?><div class="lead-contact-row">📧 <?= Security::escapeHTML($lead['email']) ?></div><?php endif; ?>
            <?php if ($lead['phone']): ?><div class="lead-contact-row">📞 <?= Security::escapeHTML($lead['phone']) ?></div><?php endif; ?>
            <div class="lead-score-info">
                <span class="score-pill <?= $scClass ?>">Score: <?= $sc ?>/100</span>
                <?php if ($lead['next_follow_up']): ?>
                <span style="font-size:.72rem;color:#6b7280;">📅 Follow-up: <?= date('M j, Y',strtotime($lead['next_follow_up'])) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($lead['notes']): ?>
            <div style="font-size:.78rem;color:#6b7280;background:#f8fffe;border-radius:6px;padding:.5rem .7rem;margin-top:.3rem;line-height:1.4;"><?= Security::escapeHTML(mb_strimwidth($lead['notes'],0,100,'…')) ?></div>
            <?php endif; ?>
        </div>
        <div class="lead-card-foot">
            <span style="font-size:.7rem;color:#9ca3af;"><?= date('M j, Y',strtotime($lead['created_at'])) ?></span>
            <?php if (in_array($role,['admin','manager'])): ?>
            <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
                <button class="btn-xs" onclick="toggleInlineEdit('lead-<?= $lead['id'] ?>')">✏️ Edit</button>
                <a href="?view=activities&lead_id=<?= $lead['id'] ?>" class="btn-xs">📞 Log</a>
                <?php if ($lead['status'] !== 'client'): ?>
                <form method="post" style="display:inline;" onsubmit="return confirm('Convert this lead into a client record?');">
                    <?= Security::getCSRFTokenField() ?><input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                    <button type="submit" name="convert_lead_to_client" class="btn-xs" style="background:#059669;color:#fff;border-color:#059669;">🤝 Convert to Client</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if (in_array($role,['admin','manager'])): ?>
        <div id="lead-<?= $lead['id'] ?>" class="inline-edit">
            <h5>✏️ Edit Lead</h5>
            <form method="post">
                <?= Security::getCSRFTokenField() ?><input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                <div class="form-2col">
                    <div class="fg"><label>Company Name</label><input type="text" name="company_name" value="<?= Security::escapeHTML($lead['company_name']) ?>" required></div>
                    <div class="fg"><label>Contact Person</label><input type="text" name="contact_person" value="<?= Security::escapeHTML($lead['contact_person']) ?>" required></div>
                    <div class="fg"><label>Email</label><input type="email" name="email" value="<?= Security::escapeHTML($lead['email']) ?>" required></div>
                    <div class="fg"><label>Phone</label><input type="tel" name="phone" value="<?= Security::escapeHTML($lead['phone']) ?>"></div>
                    <div class="fg"><label>Industry</label>
                        <select name="industry" required>
                            <?php foreach ($industries as $v=>$l): ?><option value="<?= $v ?>" <?= $lead['industry']===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg"><label>Status</label>
                        <select name="status" required>
                            <?php foreach ($lead_statuses as $v=>$l): ?><option value="<?= $v ?>" <?= $lead['status']===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg"><label>Lead Score (0–100)</label><input type="number" name="lead_score" min="0" max="100" value="<?= $lead['lead_score'] ?>"></div>
                    <div class="fg"><label>Next Follow-up</label><input type="date" name="next_follow_up" value="<?= $lead['next_follow_up'] ?>"></div>
                </div>
                <div class="fg"><label>Notes</label><textarea name="notes" rows="3"><?= Security::escapeHTML($lead['notes']) ?></textarea></div>
                <div class="form-actions">
                    <button type="submit" name="update_lead" class="btn" style="background:#059669;color:#fff;border-color:#059669;">💾 Update</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleInlineEdit('lead-<?= $lead['id'] ?>')">Cancel</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<div class="lc-pagination" id="lead-pagination"></div>
<?php endif; ?>

<!-- ══ ACTIVITIES ══ -->
<?php elseif ($view==='activities'): ?>

<?php if (in_array($role,['admin','manager'])): ?>
<div class="section-header-row">
    <h3>📞 Activity Log</h3>
    <button class="create-btn" id="act-toggle" onclick="toggleForm('act-form-wrap','act-toggle','➕ Log Activity','✕ Cancel')">➕ Log Activity</button>
</div>
<div id="act-form-wrap" style="display:none;">
    <div class="form-card">
        <div class="form-card-head"><h3>📞 Log Activity</h3><p>Record a call, meeting, email or follow-up</p></div>
        <div class="form-card-body">
            <form method="post">
                <?= Security::getCSRFTokenField() ?>
                <div class="form-section"><h4>📋 Activity Details</h4>
                    <div class="form-2col">
                        <div class="fg"><label>Activity Type <span class="req">*</span></label>
                            <select name="activity_type" required>
                                <?php foreach ($activity_types as $v=>$l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Related Lead <span class="opt">(optional)</span></label>
                            <select name="lead_id">
                                <option value="">— None —</option>
                                <?php
                                $preselect = $_GET['lead_id'] ?? '';
                                foreach ($leads as $l):
                                ?><option value="<?= $l['id'] ?>" <?= (string)$l['id']===$preselect?'selected':'' ?>><?= Security::escapeHTML($l['company_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg" style="grid-column:1/-1;"><label>Date &amp; Time <span class="req">*</span></label><input type="datetime-local" name="activity_date" required value="<?= date('Y-m-d\TH:i') ?>"></div>
                    </div>
                </div>
                <div class="form-section" style="margin-top:1.25rem;"><h4>📝 Description &amp; Outcome</h4>
                    <div class="fg"><label>Description <span class="req">*</span></label><textarea name="description" rows="3" required placeholder="What happened in this activity?"></textarea></div>
                    <div class="fg"><label>Outcome <span class="opt">(optional)</span></label><textarea name="outcome" rows="2" placeholder="What was the result?"></textarea></div>
                </div>
                <div class="form-section" style="margin-top:1.25rem;"><h4>➡️ Next Steps <span style="color:#9ca3af;font-weight:400;font-size:.8rem;">(optional)</span></h4>
                    <div class="form-2col">
                        <div class="fg"><label>Next Action</label><input type="text" name="next_action" placeholder="What needs to happen next?"></div>
                        <div class="fg"><label>Next Action Date</label><input type="date" name="next_action_date"></div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="log_activity" class="btn" style="background:#059669;color:#fff;border-color:#059669;padding:.65rem 1.5rem;">📞 Log Activity</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm('act-form-wrap','act-toggle','➕ Log Activity','✕ Cancel')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
<div class="section-header-row"><h3>📞 Activity Log</h3></div>
<?php endif; ?>

<!-- Filters -->
<div class="controls-row">
    <input type="text" id="act-search" placeholder="🔍  Search company, description…" oninput="filterBD('act-timeline','act-search',{type:'act-f-type'},'act-count')">
    <select id="act-f-type" onchange="filterBD('act-timeline','act-search',{type:'act-f-type'},'act-count')">
        <option value="">All Types</option>
        <?php foreach ($activity_types as $v=>$l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
    </select>
    <div class="lc-date-filter">
        <select id="act-date-range">
            <option value="all">All Dates</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
            <option value="lastyear">Last Year</option>
            <option value="custom">Custom Range</option>
        </select>
        <span class="lc-custom-range">
            <input type="date" id="act-date-from">
            <span>to</span>
            <input type="date" id="act-date-to">
        </span>
    </div>
    <span class="result-count" id="act-count"><?= count($all_activities) ?> activit<?= count($all_activities)!=1?'ies':'y' ?></span>
</div>

<?php if (empty($all_activities)): ?>
<div class="empty-box"><div class="emoji">📞</div><h3>No activities logged yet</h3><p>Use the button above to log your first activity.</p></div>
<?php else: ?>
<div class="no-results" id="act-no-results">No activities match your filters.</div>
<div class="act-timeline" id="act-timeline" style="border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);">
    <?php foreach ($all_activities as $act):
        $searchText = strtolower(($act['company_name']??'general').' '.$act['description'].' '.($act['outcome']??'').' '.$act['activity_type']);
    ?>
    <div class="act-item type-<?= $act['activity_type'] ?>"
         data-search="<?= htmlspecialchars($searchText,ENT_QUOTES) ?>"
         data-type="<?= $act['activity_type'] ?>"
         data-date="<?= !empty($act['activity_date']) ? date('Y-m-d', strtotime($act['activity_date'])) : '' ?>">
        <div class="act-icon"><?= $act_icons[$act['activity_type']]??'📝' ?></div>
        <div class="act-body">
            <div class="act-company"><?= Security::escapeHTML($act['company_name']??'General Activity') ?> <span style="font-size:.72rem;color:#9ca3af;font-weight:400;">· <?= ucfirst(str_replace('_',' ',$act['activity_type'])) ?></span></div>
            <div class="act-desc"><?= Security::escapeHTML($act['description']) ?></div>
            <?php if ($act['outcome']): ?><div class="act-outcome">Outcome: <?= Security::escapeHTML($act['outcome']) ?></div><?php endif; ?>
            <?php if ($act['next_action']): ?>
            <div class="act-next">➡️ <?= Security::escapeHTML($act['next_action']) ?><?= $act['next_action_date']?' — by '.date('M j, Y',strtotime($act['next_action_date'])):'' ?></div>
            <?php endif; ?>
        </div>
        <div class="act-meta"><?= date('M j, Y',strtotime($act['activity_date'])) ?><br><?= date('g:i a',strtotime($act['activity_date'])) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<div class="lc-pagination" id="act-pagination"></div>
<?php endif; ?>

<!-- ══ TASKS ══ -->
<?php elseif ($view==='tasks'): ?>

<?php if (in_array($role,['admin','manager'])): ?>
<div class="section-header-row">
    <h3>✅ Tasks &amp; Follow-ups</h3>
    <button class="create-btn" id="task-toggle" onclick="toggleForm('task-form-wrap','task-toggle','➕ Create Task','✕ Cancel')">➕ Create Task</button>
</div>
<div id="task-form-wrap" style="display:none;">
    <div class="form-card">
        <div class="form-card-head"><h3>✅ Create Task</h3><p>Assign a follow-up or action item</p></div>
        <div class="form-card-body">
            <form method="post">
                <?= Security::getCSRFTokenField() ?>
                <div class="form-section"><h4>📋 Task Details</h4>
                    <div class="form-2col">
                        <div class="fg" style="grid-column:1/-1;"><label>Task Description <span class="req">*</span></label><input type="text" name="task_description" required placeholder="What needs to be done?"></div>
                        <div class="fg"><label>Due Date &amp; Time <span class="req">*</span></label><input type="datetime-local" name="due_date" required></div>
                        <div class="fg"><label>Priority <span class="req">*</span></label>
                            <select name="priority" required>
                                <option value="high">🔴 High</option>
                                <option value="medium" selected>🟡 Medium</option>
                                <option value="low">🔵 Low</option>
                            </select>
                        </div>
                        <div class="fg"><label>Assign To <span class="req">*</span></label>
                            <select name="assigned_to" required>
                                <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>" <?= $u['id']==$user_id?'selected':'' ?>><?= Security::escapeHTML($u['username']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Related Lead <span class="opt">(optional)</span></label>
                            <select name="related_lead_id">
                                <option value="">— None —</option>
                                <?php
                                $preselect = $_GET['lead_id'] ?? '';
                                foreach ($leads as $l):
                                ?><option value="<?= $l['id'] ?>" <?= (string)$l['id']===$preselect?'selected':'' ?>><?= Security::escapeHTML($l['company_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="create_task" class="btn" style="background:#059669;color:#fff;border-color:#059669;padding:.65rem 1.5rem;">✅ Create Task</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm('task-form-wrap','task-toggle','➕ Create Task','✕ Cancel')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
<div class="section-header-row"><h3>✅ Tasks &amp; Follow-ups</h3></div>
<?php endif; ?>

<!-- Filters -->
<div class="controls-row">
    <input type="text" id="task-search" placeholder="🔍  Search task, lead, assignee…" oninput="filterBD('task-list','task-search',{priority:'task-f-pri',status:'task-f-status'},'task-count')">
    <select id="task-f-pri" onchange="filterBD('task-list','task-search',{priority:'task-f-pri',status:'task-f-status'},'task-count')">
        <option value="">All Priorities</option>
        <option value="high">🔴 High</option>
        <option value="medium">🟡 Medium</option>
        <option value="low">🔵 Low</option>
    </select>
    <select id="task-f-status" onchange="filterBD('task-list','task-search',{priority:'task-f-pri',status:'task-f-status'},'task-count')">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="completed">Completed</option>
    </select>
    <div class="lc-date-filter">
        <select id="task-date-range">
            <option value="all">All Dates</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
            <option value="lastyear">Last Year</option>
            <option value="custom">Custom Range</option>
        </select>
        <span class="lc-custom-range">
            <input type="date" id="task-date-from">
            <span>to</span>
            <input type="date" id="task-date-to">
        </span>
    </div>
    <span class="result-count" id="task-count"><?= count($all_tasks) ?> task<?= count($all_tasks)!=1?'s':'' ?></span>
</div>

<?php if (empty($all_tasks)): ?>
<div class="empty-box"><div class="emoji">✅</div><h3>No tasks yet</h3><p>Create your first task using the button above.</p></div>
<?php else: ?>
<div class="no-results" id="task-no-results">No tasks match your filters.</div>
<div class="task-list" id="task-list">
    <?php foreach ($all_tasks as $task):
        $overdue = $task['status']==='pending' && $task['due_date'] && strtotime($task['due_date'])<time();
        $searchText = strtolower($task['task_description'].' '.($task['company_name']??'').' '.($task['assigned_username']??''));
    ?>
    <div class="task-card"
         data-search="<?= htmlspecialchars($searchText,ENT_QUOTES) ?>"
         data-priority="<?= $task['priority'] ?>"
         data-status="<?= $task['status'] ?>"
         data-date="<?= !empty($task['due_date']) ? date('Y-m-d', strtotime($task['due_date'])) : '' ?>">
        <div class="task-priority-strip <?= $task['priority'] ?>"></div>
        <div class="task-body">
            <div class="task-desc"><?= Security::escapeHTML($task['task_description']) ?></div>
            <div class="task-meta">
                <?php if ($task['company_name']): ?><span>🏢 <?= Security::escapeHTML($task['company_name']) ?></span><?php endif; ?>
                <span>👤 <?= Security::escapeHTML($task['assigned_username']??'Unassigned') ?></span>
                <span style="color:<?= $overdue?'#ef4444':'#6b7280' ?>;">📅 <?= $task['due_date']?date('M j, Y',strtotime($task['due_date'])):'No deadline' ?><?= $overdue?' · ⚠️ Overdue':'' ?></span>
                <span class="badge p-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span>
                <span class="badge t-<?= $task['status'] ?>"><?= ucfirst($task['status']) ?></span>
            </div>
        </div>
        <div class="task-foot">
            <?php if ($task['status']==='pending' && in_array($role,['admin','manager'])): ?>
            <form method="post" onsubmit="return confirm('Mark complete?')">
                <?= Security::getCSRFTokenField() ?><input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                <button type="submit" name="complete_task" class="btn-xs" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534;">✔ Done</button>
            </form>
            <?php else: ?>
            <span style="font-size:.75rem;color:#9ca3af;">✔</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<div class="lc-pagination" id="task-pagination"></div>
<?php endif; ?>

<!-- ══ TARGETS ══ -->
<?php elseif ($view==='targets'): ?>

<?php if (in_array($role,['admin','manager'])): ?>
<div class="section-header-row">
    <h3>🎯 Monthly Targets</h3>
</div>
<div class="form-card" style="margin-bottom:1.5rem;">
    <div class="form-card-head"><h3>🎯 Set Monthly Targets</h3><p>Define goals for leads, meetings and client conversions</p></div>
    <div class="form-card-body">
        <form method="post">
            <?= Security::getCSRFTokenField() ?>
            <div class="form-2col">
                <div class="fg"><label>Month <span class="req">*</span></label><input type="month" name="month_year" required value="<?= date('Y-m') ?>"></div>
                <div class="fg"><label>Lead Target <span class="req">*</span></label><input type="number" name="lead_target" min="0" required value="<?= $targets['lead_target'] ?>" placeholder="15"></div>
                <div class="fg"><label>Meeting Target <span class="req">*</span></label><input type="number" name="meeting_target" min="0" required value="<?= $targets['meeting_target'] ?>" placeholder="5"></div>
                <div class="fg"><label>Client Target <span class="req">*</span></label><input type="number" name="client_target" min="0" required value="<?= $targets['client_target'] ?>" placeholder="1"></div>
            </div>
            <div class="form-actions">
                <button type="submit" name="set_targets" class="btn" style="background:#059669;color:#fff;border-color:#059669;padding:.65rem 1.5rem;">🎯 Save Targets</button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<div class="section-header-row"><h3>🎯 Monthly Targets</h3></div>
<?php endif; ?>

<!-- Current month performance -->
<div class="section-header-row" style="margin-top:.5rem;"><h3>📊 Current Month Performance — <?= date('F Y') ?></h3></div>
<div class="target-grid">
    <?php
    $tperf=[
        ['label'=>'Leads','actual'=>$stats['new_leads'],'target'=>$targets['lead_target'],'icon'=>'📋'],
        ['label'=>'Meetings','actual'=>$stats['meetings_booked'],'target'=>$targets['meeting_target'],'icon'=>'👥'],
        ['label'=>'Clients Won','actual'=>$stats['clients_converted'],'target'=>$targets['client_target'],'icon'=>'🏆'],
    ];
    foreach ($tperf as $tp):
        $pct=$tp['target']>0?min(round($tp['actual']/$tp['target']*100,1),100):0;
        $col=$pct>=100?'#059669':($pct>=50?'#f59e0b':'#ef4444');
    ?>
    <div class="target-card">
        <div style="font-size:1.75rem;margin-bottom:.5rem;"><?= $tp['icon'] ?></div>
        <div class="target-nums" style="color:<?= $col ?>"><?= $tp['actual'] ?><span style="font-size:1rem;font-weight:400;color:#9ca3af;"> / <?= $tp['target'] ?></span></div>
        <div class="target-lbl"><?= $tp['label'] ?></div>
        <div class="target-prog-track"><div class="target-prog-fill" style="width:<?= $pct ?>%;background:<?= $pct>=100?'linear-gradient(90deg,#059669,#16a34a)':($pct>=50?'linear-gradient(90deg,#f59e0b,#d97706)':'linear-gradient(90deg,#ef4444,#dc2626)') ?>;"></div></div>
        <div class="target-pct"><?= $pct ?>% of target<?= $pct>=100?' ✅':'' ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Historical targets -->
<?php if (!empty($all_targets)): ?>
<div class="section-header-row" style="margin-top:1rem;"><h3>📅 Target History</h3></div>
<div style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;">
        <thead style="background:#f8fffe;">
            <tr><?php foreach(['Month','Lead Target','Meeting Target','Client Target','Set On'] as $h): ?><th style="padding:.75rem 1rem;text-align:left;font-size:.78rem;font-weight:700;color:#374151;border-bottom:2px solid #f3f4f6;"><?= $h ?></th><?php endforeach; ?></tr>
        </thead>
        <tbody>
            <?php foreach ($all_targets as $t): ?>
            <tr style="border-bottom:1px solid #f9fafb;">
                <td style="padding:.7rem 1rem;font-size:.85rem;font-weight:600;"><?= date('F Y',strtotime($t['month_year'])) ?></td>
                <td style="padding:.7rem 1rem;font-size:.85rem;"><?= $t['lead_target'] ?></td>
                <td style="padding:.7rem 1rem;font-size:.85rem;"><?= $t['meeting_target'] ?></td>
                <td style="padding:.7rem 1rem;font-size:.85rem;"><?= $t['client_target'] ?></td>
                <td style="padding:.7rem 1rem;font-size:.78rem;color:#9ca3af;"><?= date('M j, Y',strtotime($t['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ══ REPORTS ══ -->
<?php elseif ($view==='reports'): ?>

<div class="bd-stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.5rem;">
    <div class="bd-stat"><div class="n"><?= $stats['total_leads'] ?></div><div class="l">Total Leads</div></div>
    <div class="bd-stat"><div class="n" style="color:#059669;"><?= $stats['clients_converted'] ?></div><div class="l">Clients Won</div></div>
    <div class="bd-stat"><div class="n" style="color:#2563eb;"><?= $stats['meetings_booked'] ?></div><div class="l">Meetings This Month</div></div>
    <div class="bd-stat"><div class="n" style="color:#f59e0b;"><?= $conversion_rate ?>%</div><div class="l">Conversion Rate</div></div>
</div>

<div class="report-grid">
    <!-- Lead status distribution -->
    <div class="report-card">
        <h4>📊 Lead Status Distribution</h4>
        <?php
        $ls_data = $db->query("SELECT status,COUNT(*) as c FROM bd_leads GROUP BY status ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($ls_data)): ?><p style="color:#9ca3af;font-size:.85rem;text-align:center;padding:1rem 0;">No lead data yet.</p>
        <?php else: foreach ($ls_data as $ls):
            $pct = $stats['total_leads']>0?round($ls['c']/$stats['total_leads']*100,1):0;
        ?>
        <div class="report-row">
            <span class="report-label"><span class="badge s-<?= $ls['status'] ?>"><?= ucfirst(str_replace('_',' ',$ls['status'])) ?></span></span>
            <div class="report-bar-track"><div class="report-bar-fill" style="width:<?= $pct ?>%;"></div></div>
            <span class="report-val"><?= $ls['c'] ?></span>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Activity summary (30 days) -->
    <div class="report-card">
        <h4>📞 Activity Summary (30 Days)</h4>
        <?php
        $as_data = $db->query("SELECT activity_type,COUNT(*) as c FROM bd_activities WHERE activity_date>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY activity_type ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
        $max_acts = !empty($as_data) ? max(array_column($as_data,'c')) : 1;
        if (empty($as_data)): ?><p style="color:#9ca3af;font-size:.85rem;text-align:center;padding:1rem 0;">No activities in the last 30 days.</p>
        <?php else: foreach ($as_data as $as): $pct=round($as['c']/$max_acts*100,1); ?>
        <div class="report-row">
            <span class="report-label"><?= $act_icons[$as['activity_type']]??'📝' ?> <?= ucfirst(str_replace('_',' ',$as['activity_type'])) ?></span>
            <div class="report-bar-track"><div class="report-bar-fill" style="width:<?= $pct ?>%;"></div></div>
            <span class="report-val"><?= $as['c'] ?></span>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Industry breakdown -->
    <div class="report-card">
        <h4>🏢 Leads by Industry</h4>
        <?php
        $ind_data = $db->query("SELECT industry,COUNT(*) as c FROM bd_leads GROUP BY industry ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
        $max_ind = !empty($ind_data) ? max(array_column($ind_data,'c')) : 1;
        if (empty($ind_data)): ?><p style="color:#9ca3af;font-size:.85rem;text-align:center;padding:1rem 0;">No lead data yet.</p>
        <?php else: foreach ($ind_data as $id): $pct=round($id['c']/$max_ind*100,1); ?>
        <div class="report-row">
            <span class="report-label"><?= $ind_icons[$id['industry']]??'🏢' ?> <?= ucfirst($id['industry']) ?></span>
            <div class="report-bar-track"><div class="report-bar-fill" style="width:<?= $pct ?>%;"></div></div>
            <span class="report-val"><?= $id['c'] ?></span>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Performance vs targets -->
    <div class="report-card">
        <h4>🎯 Performance vs Targets — <?= date('F Y') ?></h4>
        <?php
        $perf=[
            ['label'=>'📋 Leads','actual'=>$stats['new_leads'],'target'=>$targets['lead_target']],
            ['label'=>'👥 Meetings','actual'=>$stats['meetings_booked'],'target'=>$targets['meeting_target']],
            ['label'=>'🏆 Clients','actual'=>$stats['clients_converted'],'target'=>$targets['client_target']],
        ];
        foreach ($perf as $p): $pct=$p['target']>0?min(round($p['actual']/$p['target']*100,1),100):0; ?>
        <div class="report-row">
            <span class="report-label"><?= $p['label'] ?></span>
            <div class="report-bar-track"><div class="report-bar-fill" style="width:<?= $pct ?>%;background:<?= $pct>=100?'linear-gradient(90deg,#059669,#16a34a)':($pct>=50?'linear-gradient(90deg,#f59e0b,#d97706)':'linear-gradient(90deg,#ef4444,#dc2626)') ?>;"></div></div>
            <span class="report-val"><?= $p['actual'] ?>/<?= $p['target'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>

</div><!-- /.main-content -->

<script src="../js/notification.js"></script>
<script src="../js/list-controls.js"></script>
<script>
function toggleForm(wrapId, btnId, labelOpen, labelClose) {
    const wrap = document.getElementById(wrapId);
    const btn  = document.getElementById(btnId);
    const isOpen = wrap.style.display === 'block';
    wrap.style.display = isOpen ? 'none' : 'block';
    if (btn) { btn.textContent = isOpen ? labelOpen : labelClose; btn.classList.toggle('open', !isOpen); }
    if (!isOpen) wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function toggleInlineEdit(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'block' ? 'none' : 'block';
}

function filterBD(containerId, searchInputId, filterMap, countId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const searchVal = (document.getElementById(searchInputId)?.value || '').toLowerCase().trim();
    const filterVals = {};
    for (const [key, sid] of Object.entries(filterMap)) filterVals[key] = document.getElementById(sid)?.value || '';
    const cards = container.querySelectorAll('[data-search]');
    let visible = 0;
    cards.forEach(card => {
        let show = true;
        if (searchVal && !(card.dataset.search || '').includes(searchVal)) show = false;
        for (const [key, val] of Object.entries(filterVals)) if (val && card.dataset[key] !== val) show = false;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const countEl = document.getElementById(countId);
    if (countEl) {
        const raw = countEl.textContent.replace(/^\d+\s*/,'').split(' ')[0];
        countEl.textContent = visible + ' ' + raw + (visible !== 1 ? (raw.endsWith('y') ? 'ies' : 's') : (raw.endsWith('ies') ? 'y' : raw));
    }
    const noRes = document.getElementById(containerId.split('-')[0] + '-no-results') ||
                  document.getElementById(containerId + '-no-results');
    if (noRes) noRes.style.display = visible === 0 ? 'block' : 'none';

    const prefix = containerId.split('-')[0];
    ListControls.applyDateFilterAndPaginate(containerId, '[data-search]', prefix+'-date-range', prefix+'-date-from', prefix+'-date-to', prefix+'-pagination');
}

// Init pagination + date-range filters for BD lists (only the active view's elements exist)
document.addEventListener('DOMContentLoaded', function() {
    ListControls.initDateRangeControl('lead-date-range', 'lead-date-from', 'lead-date-to', function() {
        filterBD('lead-grid','lead-search',{industry:'lead-f-ind',status:'lead-f-status'},'lead-count');
    });
    ListControls.initDateRangeControl('act-date-range', 'act-date-from', 'act-date-to', function() {
        filterBD('act-timeline','act-search',{type:'act-f-type'},'act-count');
    });
    ListControls.initDateRangeControl('task-date-range', 'task-date-from', 'task-date-to', function() {
        filterBD('task-list','task-search',{priority:'task-f-pri',status:'task-f-status'},'task-count');
    });
    filterBD('lead-grid','lead-search',{industry:'lead-f-ind',status:'lead-f-status'},'lead-count');
    filterBD('act-timeline','act-search',{type:'act-f-type'},'act-count');
    filterBD('task-list','task-search',{priority:'task-f-pri',status:'task-f-status'},'task-count');
});

const flash = document.getElementById('flashMsg');
if (flash) setTimeout(() => { flash.style.transition='opacity .5s'; flash.style.opacity=0; setTimeout(()=>flash.remove(),500); }, 3500);
</script>
</body>
</html>

