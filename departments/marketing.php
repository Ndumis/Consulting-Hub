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

    Security::requireDepartmentAccess('Marketing');

    $database = new Database();
    $db = $database->getConnection();

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

    $user_id   = $_SESSION['user_id'];
    $username  = $_SESSION['username'];
    $role      = $_SESSION['role'];
    $department= $_SESSION['department'];
    $email     = $_SESSION['email'];

    if ($_POST && isset($_POST['create_campaign'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        $db->prepare("INSERT INTO marketing_campaigns (client_id,campaign_name,campaign_type,budget,start_date,end_date) VALUES (?,?,?,?,?,?)")
           ->execute([(int)$_POST['client_id'],Security::sanitizeInput($_POST['campaign_name']),Security::sanitizeInput($_POST['campaign_type']),floatval($_POST['budget']),Security::sanitizeInput($_POST['start_date']),Security::sanitizeInput($_POST['end_date'])]);
        header("Location: marketing.php?view=campaigns&msg=campaign_added"); exit();
    }

    if ($_POST && isset($_POST['update_campaign'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        $db->prepare("UPDATE marketing_campaigns SET status=?,metrics=? WHERE id=?")
           ->execute([Security::sanitizeInput($_POST['status']),Security::sanitizeInput($_POST['metrics']),(int)$_POST['campaign_id']]);
        header("Location: marketing.php?view=campaigns&msg=campaign_updated"); exit();
    }

    if ($_POST && isset($_POST['create_social_post'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        $db->prepare("INSERT INTO social_media_posts (client_id,campaign_id,platform,content,scheduled_for) VALUES (?,?,?,?,?)")
           ->execute([(int)$_POST['client_id'],!empty($_POST['campaign_id'])?(int)$_POST['campaign_id']:null,Security::sanitizeInput($_POST['platform']),Security::sanitizeInput($_POST['content']),Security::sanitizeInput($_POST['scheduled_date'])]);
        header("Location: marketing.php?view=social-posts&msg=post_added"); exit();
    }

    if ($_POST && isset($_POST['update_social_post'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        $db->prepare("UPDATE social_media_posts SET platform=?,content=?,scheduled_for=?,status=?,engagement_stats=? WHERE id=?")
           ->execute([Security::sanitizeInput($_POST['platform']),Security::sanitizeInput($_POST['content']),Security::sanitizeInput($_POST['scheduled_date']),Security::sanitizeInput($_POST['status']),Security::sanitizeInput($_POST['engagement_metrics']),(int)$_POST['post_id']]);
        header("Location: marketing.php?view=social-posts&msg=post_updated"); exit();
    }

    if ($_POST && isset($_POST['delete_social_post'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        $db->prepare("DELETE FROM social_media_posts WHERE id=?")->execute([(int)$_POST['post_id']]);
        header("Location: marketing.php?view=social-posts&msg=post_deleted"); exit();
    }

    if ($_POST && isset($_POST['create_email_campaign'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        $db->prepare("INSERT INTO email_campaigns (client_id,campaign_name,subject,content,scheduled_date) VALUES (?,?,?,?,?)")
           ->execute([(int)$_POST['client_id'],Security::sanitizeInput($_POST['campaign_name']),Security::sanitizeInput($_POST['subject']),Security::sanitizeInput($_POST['content']),Security::sanitizeInput($_POST['send_date'])]);
        header("Location: marketing.php?view=email-campaigns&msg=email_added"); exit();
    }

    if ($_POST && isset($_POST['update_email_campaign'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        $db->prepare("UPDATE email_campaigns SET subject=?,content=?,scheduled_date=?,status=? WHERE id=?")
           ->execute([Security::sanitizeInput($_POST['subject']),Security::sanitizeInput($_POST['content']),Security::sanitizeInput($_POST['send_date']),Security::sanitizeInput($_POST['status']),(int)$_POST['campaign_id']]);
        header("Location: marketing.php?view=email-campaigns&msg=email_updated"); exit();
    }

    if ($_POST && isset($_POST['send_email_campaign'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        $db->prepare("UPDATE email_campaigns SET status='sent',sent_date=NOW() WHERE id=?")->execute([(int)$_POST['campaign_id']]);
        $db->prepare("UPDATE email_recipients SET status='sent',sent_at=CURRENT_TIMESTAMP WHERE email_campaign_id=?")->execute([(int)$_POST['campaign_id']]);
        header("Location: marketing.php?view=email-campaigns&msg=email_sent"); exit();
    }

    if ($_POST && isset($_POST['add_email_recipient'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        $cid = (int)$_POST['campaign_id'];
        $db->prepare("INSERT INTO email_recipients (email_campaign_id,email,name) VALUES (?,?,?)")
           ->execute([$cid,Security::sanitizeInput($_POST['email']),Security::sanitizeInput($_POST['name'])]);
        $db->prepare("UPDATE email_campaigns SET total_recipients=(SELECT COUNT(*) FROM email_recipients WHERE email_campaign_id=?) WHERE id=?")->execute([$cid,$cid]);
        header("Location: marketing.php?view=email-campaigns&msg=recipient_added"); exit();
    }

    if ($_POST && isset($_POST['create_blog_post'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        $author_id   = !empty($_POST['author_id']) ? (int)$_POST['author_id'] : $_SESSION['user_id'];
        $author_name = $_SESSION['username'];
        if ($author_id !== (int)$_SESSION['user_id']) {
            $ar = $db->prepare("SELECT username FROM users WHERE id=?"); $ar->execute([$author_id]);
            $row = $ar->fetch(PDO::FETCH_ASSOC); if ($row) $author_name = $row['username'];
        }
        $slug = trim(strtolower(preg_replace('/[^A-Za-z0-9]+/','-',trim(Security::sanitizeInput($_POST['title'])))),'-') . '-' . substr(md5(uniqid()),0,6);
        $db->prepare("INSERT INTO blog_posts (client_id,campaign_id,slug,title,content,author,author_id,category,tags,published_at,status,featured_image,excerpt) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
           ->execute([!empty($_POST['client_id'])?(int)$_POST['client_id']:null,!empty($_POST['campaign_id'])?(int)$_POST['campaign_id']:null,$slug,Security::sanitizeInput($_POST['title']),Security::sanitizeInput($_POST['content']),$author_name,$author_id,Security::sanitizeInput($_POST['category']),Security::sanitizeInput($_POST['tags']),Security::sanitizeInput($_POST['publish_date']),Security::sanitizeInput($_POST['status']),Security::sanitizeInput($_POST['featured_image']??''),Security::sanitizeInput($_POST['excerpt']??'')]);
        header("Location: marketing.php?view=blog-posts&msg=post_created"); exit();
    }

    if ($_POST && isset($_POST['update_blog_post'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        $db->prepare("UPDATE blog_posts SET title=?,content=?,category=?,tags=?,published_at=?,status=?,featured_image=?,excerpt=?,updated_at=CURRENT_TIMESTAMP WHERE id=?")
           ->execute([Security::sanitizeInput($_POST['title']),Security::sanitizeInput($_POST['content']),Security::sanitizeInput($_POST['category']),Security::sanitizeInput($_POST['tags']),Security::sanitizeInput($_POST['publish_date']),Security::sanitizeInput($_POST['status']),Security::sanitizeInput($_POST['featured_image']??''),Security::sanitizeInput($_POST['excerpt']??''),(int)$_POST['post_id']]);
        header("Location: marketing.php?view=blog-posts&msg=post_updated"); exit();
    }

    if ($_POST && isset($_POST['delete_blog_post'])) {
        Security::checkCSRFToken();
        Security::requireWriteAccess('Marketing');
        $db->prepare("UPDATE blog_posts SET status='deleted',updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([(int)$_POST['post_id']]);
        header("Location: marketing.php?view=blog-posts&msg=post_deleted"); exit();
    }

    $view = $_GET['view'] ?? 'overview';
    $msg  = $_GET['msg']  ?? '';

    $campaigns = $db->query("SELECT mc.*,c.name as client_name,c.company as client_company FROM marketing_campaigns mc LEFT JOIN clients c ON mc.client_id=c.id ORDER BY mc.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $clients   = $db->query("SELECT id,name,company FROM clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $client_overview = $db->query("SELECT c.*,COUNT(mc.id) as total_campaigns,SUM(CASE WHEN mc.status='active' THEN 1 ELSE 0 END) as active_campaigns,SUM(mc.budget) as total_budget FROM clients c LEFT JOIN marketing_campaigns mc ON c.id=mc.client_id WHERE c.status='active' GROUP BY c.id ORDER BY total_campaigns DESC")->fetchAll(PDO::FETCH_ASSOC);
    $social_posts    = $db->query("SELECT smp.*,c.name as client_name,mc.campaign_name FROM social_media_posts smp LEFT JOIN clients c ON smp.client_id=c.id LEFT JOIN marketing_campaigns mc ON smp.campaign_id=mc.id ORDER BY smp.scheduled_for ASC")->fetchAll(PDO::FETCH_ASSOC);
    $email_campaigns = $db->query("SELECT ec.*,c.name as client_name,c.company as client_company,mc.campaign_name FROM email_campaigns ec LEFT JOIN marketing_campaigns mc ON ec.marketing_campaign_id=mc.id LEFT JOIN clients c ON mc.client_id=c.id ORDER BY ec.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

    $email_recipients = [];
    foreach ($email_campaigns as $ec) {
        $s = $db->prepare("SELECT * FROM email_recipients WHERE email_campaign_id=? ORDER BY created_at ASC"); $s->execute([$ec['id']]);
        $email_recipients[$ec['id']] = $s->fetchAll(PDO::FETCH_ASSOC);
    }

    $blog_posts = $db->query("SELECT bp.*,c.name as client_name,c.company as client_company,mc.campaign_name,DATE(bp.published_at) as publish_date FROM blog_posts bp LEFT JOIN clients c ON bp.client_id=c.id LEFT JOIN marketing_campaigns mc ON bp.campaign_id=mc.id WHERE bp.status!='deleted' ORDER BY bp.published_at DESC,bp.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

    function getCalendarData($posts,$campaigns,$year,$month) {
        $out=[];
        foreach ($posts as $p) {
            if (!($p['scheduled_for']??null)) continue;
            if (date('Y',strtotime($p['scheduled_for']))==$year && date('m',strtotime($p['scheduled_for']))==$month) {
                $d=date('j',strtotime($p['scheduled_for'])); $p['type']='social_post'; $out[$d][]=$p;
            }
        }
        foreach ($campaigns as $c) {
            foreach (['start_date'=>'campaign_start','end_date'=>'campaign_end'] as $field=>$type) {
                if (!($c[$field]??null)) continue;
                if ($type==='campaign_end' && $c['end_date']===$c['start_date']) continue;
                if (date('Y',strtotime($c[$field]))==$year && date('m',strtotime($c[$field]))==$month) {
                    $d=date('j',strtotime($c[$field])); $c['type']=$type; $c['display_date']=$c[$field]; $out[$d][]=$c;
                }
            }
        }
        return $out;
    }

    $current_year  = date('Y');
    $current_month = date('m');
    $calendar_year  = $_GET['year']  ?? $current_year;
    $calendar_month = $_GET['month'] ?? $current_month;
    $calendar_posts = getCalendarData($social_posts,$campaigns,$calendar_year,$calendar_month);

    $all_users = $db->query("SELECT id,username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

    $total_campaigns    = count($campaigns);
    $active_campaigns   = count(array_filter($campaigns, fn($c) => $c['status']==='active'));
    $total_budget       = array_sum(array_column($campaigns,'budget'));
    $total_social_posts = count($social_posts);
    $scheduled_posts    = count(array_filter($social_posts, fn($p) => $p['status']==='scheduled'));
    $total_blog_posts   = count($blog_posts);
    $total_emails       = count($email_campaigns);

    $avatar_colors   = ['#ec4899','#8b5cf6','#6366f1','#0ea5e9','#f59e0b','#22c55e','#ef4444','#14b8a6'];
    $cat_icons       = ['technology'=>'💻','business'=>'💼','marketing'=>'📣','industry-insights'=>'🔍','case-study'=>'📋','tutorial'=>'🎓','news'=>'📰','company-update'=>'🏢'];
    $platform_emojis = ['Instagram'=>'📸','Facebook'=>'👍','Twitter'=>'🐦','LinkedIn'=>'💼','TikTok'=>'🎵'];
    $blog_categories = ['technology','business','marketing','industry-insights','case-study','tutorial','news','company-update'];
    $campaign_types  = ['social_media'=>'Social Media','email'=>'Email Marketing','brand_work'=>'Brand Work','seo'=>'SEO'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Marketing Department — KConsulting Hub</title>
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<link rel="icon" type="image/png" href="../img/KConsultingLogo1.png">
    <link rel="stylesheet" href="../css/main.css">
<style>
/* ── HERO ── */
.mkt-hero{background:linear-gradient(135deg,#ec4899 0%,#8b5cf6 100%);border-radius:14px;padding:1.75rem 2rem;color:#fff;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;}
.mkt-hero h2{margin:0 0 .2rem;font-size:1.5rem;font-weight:800;}
.mkt-hero p{margin:0;font-size:.875rem;opacity:.85;}
.mkt-hero-actions{display:flex;gap:.6rem;flex-wrap:wrap;}
.hero-btn{padding:.55rem 1.1rem;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;border:2px solid rgba(255,255,255,.4);background:rgba(255,255,255,.15);color:#fff;text-decoration:none;transition:all .2s;}
.hero-btn:hover{background:rgba(255,255,255,.3);border-color:rgba(255,255,255,.7);}
.hero-btn.primary{background:#fff;color:#ec4899;border-color:#fff;}
.hero-btn.primary:hover{background:#fdf2f8;}

/* ── STATS ── */
.mkt-stats{display:grid;grid-template-columns:repeat(8,1fr);gap:.85rem;margin-bottom:1.5rem;}
@media(max-width:1100px){.mkt-stats{grid-template-columns:repeat(4,1fr);}}
@media(max-width:600px){.mkt-stats{grid-template-columns:repeat(2,1fr);}}
.mkt-stat{background:#fff;border-radius:10px;padding:.9rem 1rem;box-shadow:0 1px 4px rgba(0,0,0,.07);text-align:center;}
.mkt-stat .n{font-size:1.5rem;font-weight:800;color:#111827;line-height:1;}
.mkt-stat .l{font-size:.72rem;color:#6b7280;margin-top:.2rem;}

/* ── TABS ── */
.mkt-tabs{display:flex;background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.07);overflow:hidden;margin-bottom:1.5rem;flex-wrap:wrap;}
.mkt-tab{flex:1;min-width:90px;padding:.85rem .6rem;text-decoration:none;color:#6b7280;text-align:center;font-size:.8rem;font-weight:600;border-right:1px solid #f3f4f6;transition:all .2s;white-space:nowrap;}
.mkt-tab:last-child{border-right:none;}
.mkt-tab.active{background:#ec4899;color:#fff;}
.mkt-tab:hover:not(.active){background:#fdf2f8;color:#ec4899;}

/* ── SECTION HEADER ROW ── */
.section-header-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.6rem;}
.section-header-row h3{margin:0;font-size:1rem;font-weight:700;color:#111827;}
.create-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.55rem 1.1rem;background:linear-gradient(135deg,#ec4899,#8b5cf6);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;transition:opacity .2s;}
.create-btn:hover{opacity:.88;}
.create-btn.open{background:linear-gradient(135deg,#6b7280,#9ca3af);}

/* ── CONTROLS ROW ── */
.controls-row{display:flex;gap:.6rem;align-items:center;background:#fff;border-radius:10px;padding:.75rem 1rem;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:1.25rem;flex-wrap:wrap;}
.controls-row input[type=text]{flex:1;min-width:180px;padding:.5rem .85rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.875rem;}
.controls-row input[type=text]:focus{outline:none;border-color:#ec4899;box-shadow:0 0 0 3px rgba(236,72,153,.1);}
.controls-row select{padding:.5rem .75rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.875rem;background:#fafafa;cursor:pointer;}
.controls-row select:focus{outline:none;border-color:#ec4899;}
.result-count{margin-left:auto;font-size:.8rem;color:#9ca3af;white-space:nowrap;}

/* ── CARDS ── */
.mkt-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;}
.mkt-card{background:#fff;border-radius:12px;border:1px solid #f3f4f6;box-shadow:0 2px 6px rgba(0,0,0,.06);overflow:hidden;transition:transform .2s,box-shadow .2s;}
.mkt-card:hover{transform:translateY(-3px);box-shadow:0 6px 16px rgba(0,0,0,.1);}
.mkt-card[style*="display:none"]{transform:none;box-shadow:none;}
.mkt-card-top{padding:1rem 1.25rem .75rem;}
.mkt-card-foot{padding:.6rem 1.25rem;border-top:1px solid #f9fafb;background:#fafafa;display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap;}

/* Platform badges */
.badge{display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700;white-space:nowrap;}
.p-instagram{background:linear-gradient(45deg,#f09433,#dc2743,#bc1888);color:#fff;}
.p-facebook{background:#1877f2;color:#fff;}
.p-twitter{background:#1da1f2;color:#fff;}
.p-linkedin{background:#0a66c2;color:#fff;}
.p-tiktok{background:#010101;color:#fff;}
.p-other{background:#6b7280;color:#fff;}
.b-draft{background:#fef9c3;color:#854d0e;}
.b-scheduled{background:#dbeafe;color:#1e40af;}
.b-published{background:#dcfce7;color:#166534;}
.b-active{background:#dcfce7;color:#166534;}
.b-sent{background:#ede9fe;color:#6d28d9;}
.b-planning{background:#f3f4f6;color:#6b7280;}
.b-completed{background:#dcfce7;color:#166534;}
.b-paused{background:#fef3c7;color:#92400e;}
.b-pending{background:#fef9c3;color:#854d0e;}

.cat-chip{display:inline-flex;align-items:center;gap:.25rem;padding:.2rem .55rem;border-radius:20px;font-size:.7rem;font-weight:700;background:#fdf2f8;color:#ec4899;}
.tag-chip{display:inline-flex;align-items:center;padding:.15rem .45rem;border-radius:20px;font-size:.68rem;font-weight:600;background:#f3f4f6;color:#6b7280;margin:.1rem;}

/* ── FORM CARD ── */
.form-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden;margin-bottom:1.5rem;}
.form-card-head{background:linear-gradient(135deg,#ec4899,#8b5cf6);padding:1.25rem 1.75rem;color:#fff;}
.form-card-head h3{margin:0 0 .1rem;font-size:1.1rem;font-weight:700;}
.form-card-head p{margin:0;font-size:.8rem;opacity:.85;}
.form-card-body{padding:1.75rem;}
.form-section h4{font-size:.88rem;font-weight:700;color:#111827;margin:0 0 .9rem;padding-bottom:.45rem;border-bottom:2px solid #f3f4f6;}
.fg{margin-bottom:.9rem;}
.fg label{display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:.35rem;}
.fg label .req{color:#ef4444;}
.fg label .opt{color:#9ca3af;font-weight:400;}
.fg input,.fg select,.fg textarea{width:100%;padding:.55rem .85rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.875rem;color:#111827;box-sizing:border-box;transition:border .15s;}
.fg input:focus,.fg select:focus,.fg textarea:focus{outline:none;border-color:#ec4899;box-shadow:0 0 0 3px rgba(236,72,153,.12);}
.form-2col{display:grid;grid-template-columns:1fr 1fr;gap:.9rem;}
@media(max-width:600px){.form-2col{grid-template-columns:1fr;}}
.form-actions{display:flex;gap:.75rem;align-items:center;margin-top:1.25rem;flex-wrap:wrap;}

/* Inline edit */
.inline-edit{display:none;padding:.85rem 1.25rem 1rem;border-top:2px dashed #f3f4f6;}
.inline-edit h5{font-size:.78rem;font-weight:700;color:#6b7280;margin:0 0 .85rem;text-transform:uppercase;letter-spacing:.5px;}

/* Campaign card */
.campaign-card-meta{display:grid;grid-template-columns:1fr 1fr;gap:.35rem .75rem;font-size:.78rem;color:#374151;margin:.75rem 0;}
.cm-label{color:#9ca3af;font-size:.7rem;display:block;}

/* Blog image */
.blog-img{width:100%;height:130px;object-fit:cover;display:block;}
.blog-img-placeholder{width:100%;height:90px;background:linear-gradient(135deg,#fdf2f8,#ede9fe);display:flex;align-items:center;justify-content:center;font-size:2rem;}

/* Quick actions */
.quick-actions{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:1rem;margin-bottom:1.5rem;}
.qa-card{background:#fff;border-radius:12px;border:1px solid #f3f4f6;box-shadow:0 2px 6px rgba(0,0,0,.06);padding:1.25rem 1rem;text-decoration:none;color:#111827;text-align:center;transition:all .2s;}
.qa-card:hover{transform:translateY(-3px);box-shadow:0 6px 16px rgba(0,0,0,.1);border-color:#ec4899;}
.qa-icon{font-size:1.75rem;margin-bottom:.5rem;}
.qa-label{font-size:.85rem;font-weight:600;color:#374151;}

/* Calendar */
.mkt-cal{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden;}
.mkt-cal-head{background:linear-gradient(135deg,#ec4899,#8b5cf6);color:#fff;padding:1.1rem 1.5rem;display:flex;justify-content:space-between;align-items:center;}
.mkt-cal-head h3{margin:0;font-size:1rem;font-weight:700;}
.cal-nav{display:flex;align-items:center;gap:.75rem;}
.cal-nav-btn{background:rgba(255,255,255,.2);border:none;color:#fff;padding:.4rem .85rem;border-radius:6px;cursor:pointer;font-size:.82rem;font-weight:600;transition:background .2s;}
.cal-nav-btn:hover{background:rgba(255,255,255,.35);}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:1px;background:#f3f4f6;}
.cal-day-hdr{background:#f8f9fa;padding:.6rem;text-align:center;font-size:.72rem;font-weight:700;color:#6b7280;text-transform:uppercase;}
.cal-day{background:#fff;min-height:100px;padding:.5rem;}
.cal-day.other-month{background:#fafafa;}
.cal-day-num{font-size:.8rem;font-weight:700;color:#374151;margin-bottom:.35rem;}
.cal-day.today .cal-day-num{background:#ec4899;color:#fff;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;}
.cal-event{border-radius:4px;padding:.15rem .4rem;margin-bottom:.2rem;font-size:.67rem;font-weight:600;cursor:pointer;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}
.cal-event.instagram{background:#fce4ec;color:#c2185b;border-left:3px solid #e91e63;}
.cal-event.facebook{background:#e8eaf6;color:#283593;border-left:3px solid #3f51b5;}
.cal-event.twitter{background:#e0f7fa;color:#00838f;border-left:3px solid #00bcd4;}
.cal-event.linkedin{background:#fff3e0;color:#e65100;border-left:3px solid #ff9800;}
.cal-event.campaign-start{background:#e8f5e9;color:#2e7d32;border-left:3px solid #4caf50;}
.cal-event.campaign-end{background:#ffebee;color:#c62828;border-left:3px solid #f44336;}

/* Flash */
.flash{padding:.75rem 1.1rem;border-radius:8px;margin-bottom:1.1rem;font-size:.875rem;font-weight:500;}
.flash-success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}

/* Empty */
.empty-box{text-align:center;padding:3rem 1.5rem;background:#fff;border-radius:12px;border:2px dashed #e5e7eb;}
.empty-box .emoji{font-size:3rem;margin-bottom:.75rem;}
.empty-box h3{color:#374151;margin-bottom:.4rem;}
.empty-box p{color:#9ca3af;margin-bottom:1.25rem;font-size:.875rem;}

/* Btn */
.btn-xs{padding:.28rem .6rem;font-size:.73rem;border-radius:6px;border:1px solid #e5e7eb;background:#fafafa;color:#374151;cursor:pointer;transition:all .15s;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:.25rem;}
.btn-xs:hover{background:#ec4899;color:#fff;border-color:#ec4899;}
.btn-xs.danger:hover{background:#ef4444;border-color:#ef4444;}

/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:1rem;}
.modal-overlay.open{display:flex;}
.modal-box{background:#fff;border-radius:14px;width:90%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25);}
.modal-head{background:linear-gradient(135deg,#ec4899,#8b5cf6);padding:1.25rem 1.5rem;color:#fff;border-radius:14px 14px 0 0;display:flex;align-items:center;justify-content:space-between;}
.modal-head h3{margin:0;font-size:1rem;font-weight:700;}
.modal-close{background:rgba(255,255,255,.2);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;}
.modal-body{padding:1.5rem;}

/* Recent */
.recent-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;}
@media(max-width:768px){.recent-grid{grid-template-columns:1fr;}}
.recent-section h4{font-size:.9rem;font-weight:700;color:#111827;margin:0 0 .75rem;}
.recent-item{background:#fff;border-radius:10px;border:1px solid #f3f4f6;padding:.85rem 1rem;margin-bottom:.6rem;}
.recent-item-title{font-size:.85rem;font-weight:600;color:#111827;margin-bottom:.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.recent-item-sub{font-size:.73rem;color:#6b7280;}

/* No results */
.no-results{text-align:center;padding:2rem;background:#fff;border-radius:12px;border:2px dashed #f3f4f6;color:#9ca3af;font-size:.875rem;display:none;}

/* ── EMAIL CAMPAIGN CARDS ── */
.ec-campaign-list{display:flex;flex-direction:column;gap:1.25rem;}
.ec-campaign-card{background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.07);overflow:hidden;border:1px solid #f0f0f8;transition:transform .2s,box-shadow .2s;}
.ec-campaign-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(139,92,246,.12);}
.ec-card-hero{background:linear-gradient(135deg,#7c3aed 0%,#ec4899 100%);padding:1.1rem 1.5rem;display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;}
.ec-card-name{font-size:1rem;font-weight:700;color:#fff;margin-bottom:.25rem;line-height:1.3;}
.ec-card-client{font-size:.78rem;color:rgba(255,255,255,.75);}
.ec-subject-row{display:flex;align-items:flex-start;gap:.85rem;padding:.9rem 1.5rem;border-bottom:1px solid #f3f4f6;background:#fafbff;}
.ec-subject-icon{font-size:1.25rem;flex-shrink:0;margin-top:.05rem;}
.ec-subject-label{font-size:.67rem;font-weight:700;color:#a78bfa;text-transform:uppercase;letter-spacing:.6px;margin-bottom:.15rem;}
.ec-subject-text{font-size:.92rem;font-weight:600;color:#1e1b4b;}
.ec-metrics-grid{display:grid;border-bottom:1px solid #f3f4f6;}
.ec-metrics-grid.no-stats{grid-template-columns:repeat(2,1fr);}
.ec-metrics-grid.has-stats{grid-template-columns:repeat(4,1fr);}
.ec-metric-cell{padding:.9rem 1rem;text-align:center;border-right:1px solid #f3f4f6;}
.ec-metric-cell:last-child{border-right:none;}
.ec-metric-val{font-size:1.3rem;font-weight:800;color:#111827;line-height:1;margin-bottom:.25rem;}
.ec-metric-small{font-size:.82rem !important;font-weight:700 !important;}
.ec-metric-lbl{font-size:.68rem;color:#9ca3af;}
.ec-progress-section{padding:.9rem 1.5rem;border-bottom:1px solid #f3f4f6;background:#f8f7ff;}
.ec-prog-item{margin-bottom:.6rem;}
.ec-prog-item:last-child{margin-bottom:0;}
.ec-prog-label{display:flex;justify-content:space-between;font-size:.72rem;font-weight:600;color:#6b7280;margin-bottom:.3rem;}
.ec-prog-track{height:8px;background:#ede9fe;border-radius:20px;overflow:hidden;}
.ec-prog-fill{height:100%;border-radius:20px;transition:width .6s ease;}
.ec-prog-open{background:linear-gradient(90deg,#22c55e,#16a34a);}
.ec-prog-click{background:linear-gradient(90deg,#8b5cf6,#6d28d9);}
.ec-recipients-preview{display:flex;align-items:center;gap:.65rem;padding:.75rem 1.5rem;border-bottom:1px solid #f3f4f6;}
.ec-recipient-avatars{display:flex;}
.ec-avatar{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;color:#fff;margin-right:-8px;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,.15);}
.ec-recipients-more{font-size:.75rem;color:#6b7280;margin-left:1rem;}
.ec-card-foot{display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.5rem;flex-wrap:wrap;gap:.5rem;background:#fafafa;}
.ec-action-row{display:flex;gap:.4rem;flex-wrap:wrap;}

/* Quill editor */
.ql-toolbar.ql-snow{border:1px solid #e5e7eb !important;border-bottom:none !important;border-radius:8px 8px 0 0 !important;background:#f9fafb !important;}
.ql-container.ql-snow{border:1px solid #e5e7eb !important;border-top:none !important;border-radius:0 0 8px 8px !important;font-family:inherit !important;}
.ql-editor{min-height:200px;font-size:.9rem;line-height:1.65;}
.ql-editor.ql-blank::before{color:#9ca3af;font-style:normal;}
.ql-editor h1{font-size:1.4rem;}.ql-editor h2{font-size:1.2rem;}.ql-editor h3{font-size:1rem;}
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

<?php $flash_map=['campaign_added'=>'✅ Campaign created.','campaign_updated'=>'✅ Campaign updated.','post_added'=>'✅ Social post created.','post_updated'=>'✅ Post updated.','post_deleted'=>'✅ Post deleted.','email_added'=>'✅ Email campaign created.','email_updated'=>'✅ Email campaign updated.','email_sent'=>'✅ Campaign sent.','recipient_added'=>'✅ Recipient added.','post_created'=>'✅ Blog post created.','post_updated'=>'✅ Blog post updated.'];
if ($msg && isset($flash_map[$msg])): ?>
<div class="flash flash-success" id="flashMsg"><?= $flash_map[$msg] ?></div>
<?php endif; ?>

<!-- Hero -->
<div class="mkt-hero">
    <div>
        <h2>📣 Marketing Department</h2>
        <p>Campaign management, social media, email &amp; content</p>
    </div>
    <?php if (in_array($role,['admin','manager'])): ?>
    <div class="mkt-hero-actions">
        <a href="?view=social-posts"    class="hero-btn">📱 Social Post</a>
        <a href="?view=email-campaigns" class="hero-btn">✉️ Email</a>
        <a href="?view=campaigns"       class="hero-btn primary">📣 Campaign</a>
    </div>
    <?php endif; ?>
</div>

<!-- Stats -->
<div class="mkt-stats">
    <div class="mkt-stat"><div class="n"><?= $total_campaigns ?></div><div class="l">Campaigns</div></div>
    <div class="mkt-stat"><div class="n" style="color:#22c55e"><?= $active_campaigns ?></div><div class="l">Active</div></div>
    <div class="mkt-stat"><div class="n" style="color:#ec4899;font-size:1rem;">R <?= number_format($total_budget,0) ?></div><div class="l">Total Budget</div></div>
    <div class="mkt-stat"><div class="n" style="color:#0ea5e9"><?= count($client_overview) ?></div><div class="l">Clients</div></div>
    <div class="mkt-stat"><div class="n" style="color:#8b5cf6"><?= $total_social_posts ?></div><div class="l">Social Posts</div></div>
    <div class="mkt-stat"><div class="n" style="color:<?= $scheduled_posts>0?'#3b82f6':'#22c55e' ?>"><?= $scheduled_posts ?></div><div class="l">Scheduled</div></div>
    <div class="mkt-stat"><div class="n" style="color:#f59e0b"><?= $total_emails ?></div><div class="l">Emails</div></div>
    <div class="mkt-stat"><div class="n" style="color:#22c55e"><?= $total_blog_posts ?></div><div class="l">Blog Posts</div></div>
</div>

<!-- Tabs -->
<div class="mkt-tabs">
    <a href="?view=overview"        class="mkt-tab <?= $view==='overview'?'active':'' ?>">📊 Overview</a>
    <a href="?view=social-calendar" class="mkt-tab <?= $view==='social-calendar'?'active':'' ?>">📅 Calendar</a>
    <a href="?view=social-posts"    class="mkt-tab <?= $view==='social-posts'?'active':'' ?>">📱 Social (<?= $total_social_posts ?>)</a>
    <a href="?view=email-campaigns" class="mkt-tab <?= $view==='email-campaigns'?'active':'' ?>">✉️ Email (<?= $total_emails ?>)</a>
    <a href="?view=blog-posts"      class="mkt-tab <?= $view==='blog-posts'?'active':'' ?>">📝 Blog (<?= $total_blog_posts ?>)</a>
    <a href="?view=campaigns"       class="mkt-tab <?= $view==='campaigns'?'active':'' ?>">📣 Campaigns (<?= $total_campaigns ?>)</a>
</div>

<!-- ══ OVERVIEW ══ -->
<?php if ($view==='overview'): ?>
<div class="quick-actions">
    <a href="?view=social-posts"    class="qa-card"><div class="qa-icon">📱</div><div class="qa-label">Social Post</div></a>
    <a href="?view=email-campaigns" class="qa-card"><div class="qa-icon">✉️</div><div class="qa-label">Email Campaign</div></a>
    <a href="?view=campaigns"       class="qa-card"><div class="qa-icon">📣</div><div class="qa-label">New Campaign</div></a>
    <a href="?view=blog-posts"      class="qa-card"><div class="qa-icon">📝</div><div class="qa-label">Blog Post</div></a>
    <a href="?view=social-calendar" class="qa-card"><div class="qa-icon">📅</div><div class="qa-label">View Calendar</div></a>
</div>
<div class="recent-grid">
    <div class="recent-section">
        <h4>📱 Latest Social Posts</h4>
        <?php if (empty($social_posts)): ?>
        <div class="empty-box" style="padding:1.5rem;"><p style="margin:0;font-size:.8rem;">No social posts yet.</p></div>
        <?php else: ?>
        <?php foreach (array_slice($social_posts,0,4) as $sp):
            $emoji=$platform_emojis[$sp['platform']]??'📌';
        ?>
        <div class="recent-item">
            <div class="recent-item-title"><?= $emoji ?> <?= Security::escapeHTML($sp['client_name']??'Unknown') ?></div>
            <div style="margin:.25rem 0;font-size:.78rem;color:#374151;"><?= Security::escapeHTML(mb_strimwidth($sp['content'],0,80,'…')) ?></div>
            <div class="recent-item-sub">
                <span class="badge p-<?= strtolower($sp['platform']) ?>"><?= Security::escapeHTML($sp['platform']) ?></span>&nbsp;
                <span class="badge b-<?= $sp['status'] ?>"><?= ucfirst($sp['status']) ?></span>&nbsp;
                <?= date('M j, Y',strtotime($sp['scheduled_for'])) ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="recent-section">
        <h4>✉️ Recent Email Campaigns</h4>
        <?php if (empty($email_campaigns)): ?>
        <div class="empty-box" style="padding:1.5rem;"><p style="margin:0;font-size:.8rem;">No email campaigns yet.</p></div>
        <?php else: ?>
        <?php foreach (array_slice($email_campaigns,0,4) as $ec): ?>
        <div class="recent-item">
            <div class="recent-item-title"><?= Security::escapeHTML($ec['campaign_name']) ?></div>
            <div style="margin:.25rem 0;font-size:.78rem;color:#374151;"><strong>Subject:</strong> <?= Security::escapeHTML(mb_strimwidth($ec['subject'],0,60,'…')) ?></div>
            <div class="recent-item-sub"><span class="badge b-<?= $ec['status'] ?>"><?= ucfirst($ec['status']) ?></span>&nbsp;<?= Security::escapeHTML($ec['client_name']??'No client') ?></div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ══ CALENDAR ══ -->
<?php elseif ($view==='social-calendar'): ?>
<div class="mkt-cal">
    <div class="mkt-cal-head">
        <h3>📅 Marketing Calendar</h3>
        <div class="cal-nav">
            <button class="cal-nav-btn" onclick="changeMonth(-1)">← Prev</button>
            <span style="font-weight:700;"><?= date('F Y',mktime(0,0,0,$calendar_month,1,$calendar_year)) ?></span>
            <button class="cal-nav-btn" onclick="changeMonth(1)">Next →</button>
        </div>
    </div>
    <div class="cal-grid">
        <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
        <div class="cal-day-hdr"><?= $d ?></div>
        <?php endforeach; ?>
        <?php
        $today_str     = date('Y-m-d');
        $first_day     = mktime(0,0,0,$calendar_month,1,$calendar_year);
        $days_in_month = date('t',$first_day);
        $start_day     = date('w',$first_day);
        $prev_days     = date('t',mktime(0,0,0,$calendar_month-1,1,$calendar_year));
        for ($i=$start_day-1;$i>=0;$i--) {
            $d=$prev_days-$i;
            echo "<div class='cal-day other-month'><div class='cal-day-num' style='color:#d1d5db;'>$d</div></div>";
        }
        for ($day=1;$day<=$days_in_month;$day++) {
            $day_str=sprintf('%04d-%02d-%02d',$calendar_year,$calendar_month,$day);
            $is_today=($day_str===$today_str);
            echo "<div class='cal-day".($is_today?' today':'')."'><div class='cal-day-num'>$day</div>";
            if (isset($calendar_posts[$day])) {
                foreach ($calendar_posts[$day] as $item) {
                    if ($item['type']==='social_post') {
                        $pc=strtolower($item['platform']);
                        echo "<div class='cal-event $pc'>".Security::escapeHTML(ucfirst($item['platform'])).": ".Security::escapeHTML(mb_strimwidth($item['content'],0,20,'…'))."</div>";
                    } elseif ($item['type']==='campaign_start') {
                        echo "<div class='cal-event campaign-start'>🚀 ".Security::escapeHTML(mb_strimwidth($item['campaign_name'],0,20,'…'))."</div>";
                    } elseif ($item['type']==='campaign_end') {
                        echo "<div class='cal-event campaign-end'>🏁 ".Security::escapeHTML(mb_strimwidth($item['campaign_name'],0,20,'…'))."</div>";
                    }
                }
            }
            echo "</div>";
        }
        $remaining=42-($start_day+$days_in_month);
        for ($d=1;$d<=$remaining;$d++) echo "<div class='cal-day other-month'><div class='cal-day-num' style='color:#d1d5db;'>$d</div></div>";
        ?>
    </div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;padding:.85rem 1.25rem;border-top:1px solid #f3f4f6;background:#fafafa;">
        <span style="font-size:.72rem;font-weight:600;color:#6b7280;">Legend:</span>
        <span class="cal-event instagram" style="position:static;display:inline-block;">📸 Instagram</span>
        <span class="cal-event facebook"  style="position:static;display:inline-block;">👍 Facebook</span>
        <span class="cal-event twitter"   style="position:static;display:inline-block;">🐦 Twitter</span>
        <span class="cal-event linkedin"  style="position:static;display:inline-block;">💼 LinkedIn</span>
        <span class="cal-event campaign-start" style="position:static;display:inline-block;">🚀 Start</span>
        <span class="cal-event campaign-end"   style="position:static;display:inline-block;">🏁 End</span>
    </div>
</div>

<!-- ══ SOCIAL POSTS ══ -->
<?php elseif ($view==='social-posts'): ?>

<?php if (in_array($role,['admin','manager'])): ?>
<div class="section-header-row">
    <h3>📱 Social Media Posts</h3>
    <button class="create-btn" id="sp-toggle" onclick="toggleForm('sp-form-wrap','sp-toggle','➕ New Post','✕ Cancel')">➕ New Post</button>
</div>
<div id="sp-form-wrap" style="display:none;">
    <div class="form-card">
        <div class="form-card-head"><h3>📱 Create Social Media Post</h3><p>Schedule a post across any platform</p></div>
        <div class="form-card-body">
            <form method="post">
                <?= Security::getCSRFTokenField() ?>
                <div class="form-section"><h4>📋 Post Details</h4>
                    <div class="form-2col">
                        <div class="fg"><label>Client <span class="req">*</span></label>
                            <select name="client_id" required><option value="">— Select Client —</option>
                                <?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"><?= Security::escapeHTML($c['name']) ?> — <?= Security::escapeHTML($c['company']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Campaign <span class="opt">(optional)</span></label>
                            <select name="campaign_id"><option value="">— None —</option>
                                <?php foreach ($campaigns as $c): ?><option value="<?= $c['id'] ?>"><?= Security::escapeHTML($c['campaign_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Platform <span class="req">*</span></label>
                            <select name="platform" required>
                                <?php foreach (['Instagram','Facebook','Twitter','LinkedIn','TikTok'] as $pl): ?>
                                <option value="<?= $pl ?>"><?= $platform_emojis[$pl]??'' ?> <?= $pl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Scheduled Date &amp; Time <span class="req">*</span></label><input type="datetime-local" name="scheduled_date" required></div>
                    </div>
                </div>
                <div class="form-section" style="margin-top:1.25rem;"><h4>✍️ Content</h4>
                    <div class="fg"><textarea name="content" rows="4" required placeholder="Write your post content here…"></textarea></div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="create_social_post" class="btn" style="background:#ec4899;color:#fff;border-color:#ec4899;padding:.65rem 1.5rem;">📱 Create Post</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm('sp-form-wrap','sp-toggle','➕ New Post','✕ Cancel')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
<div class="section-header-row"><h3>📱 Social Media Posts</h3></div>
<?php endif; ?>

<!-- Filters -->
<div class="controls-row">
    <input type="text" id="sp-search" placeholder="🔍  Search content, client…" oninput="filterMkt('sp-grid','sp-search',{platform:'sp-filter-platform',status:'sp-filter-status'},'sp-count')">
    <select id="sp-filter-platform" onchange="filterMkt('sp-grid','sp-search',{platform:'sp-filter-platform',status:'sp-filter-status'},'sp-count')">
        <option value="">All Platforms</option>
        <?php foreach (['Instagram','Facebook','Twitter','LinkedIn','TikTok'] as $pl): ?>
        <option value="<?= $pl ?>"><?= $platform_emojis[$pl]??'' ?> <?= $pl ?></option>
        <?php endforeach; ?>
    </select>
    <select id="sp-filter-status" onchange="filterMkt('sp-grid','sp-search',{platform:'sp-filter-platform',status:'sp-filter-status'},'sp-count')">
        <option value="">All Statuses</option>
        <option value="draft">Draft</option>
        <option value="scheduled">Scheduled</option>
        <option value="published">Published</option>
    </select>
    <div class="lc-date-filter">
        <select id="sp-date-range">
            <option value="all">All Dates</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
            <option value="lastyear">Last Year</option>
            <option value="custom">Custom Range</option>
        </select>
        <span class="lc-custom-range">
            <input type="date" id="sp-date-from">
            <span>to</span>
            <input type="date" id="sp-date-to">
        </span>
    </div>
    <span class="result-count" id="sp-count"><?= count($social_posts) ?> post<?= count($social_posts)!=1?'s':'' ?></span>
</div>

<?php if (empty($social_posts)): ?>
<div class="empty-box"><div class="emoji">📱</div><h3>No social posts yet</h3><p>Create your first scheduled post above.</p></div>
<?php else: ?>
<div class="no-results" id="sp-no-results">No posts match your filters.</div>
<div class="mkt-grid" id="sp-grid">
    <?php foreach ($social_posts as $sp):
        $plat=strtolower($sp['platform']);
        $emoji=$platform_emojis[$sp['platform']]??'📌';
        $schTs=strtotime($sp['scheduled_for']);
        $isPast=$schTs<time();
        $searchText=strtolower(($sp['client_name']??'').' '.$sp['content'].' '.$sp['platform']);
    ?>
    <div class="mkt-card"
         data-search="<?= htmlspecialchars($searchText,ENT_QUOTES) ?>"
         data-platform="<?= Security::escapeHTML($sp['platform']) ?>"
         data-status="<?= $sp['status'] ?>"
         data-date="<?= !empty($sp['scheduled_for']) ? date('Y-m-d', strtotime($sp['scheduled_for'])) : '' ?>">
        <div class="mkt-card-top">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.6rem;">
                <div>
                    <span class="badge p-<?= $plat ?>"><?= $emoji ?> <?= Security::escapeHTML($sp['platform']) ?></span>
                    &nbsp;<span class="badge b-<?= $sp['status'] ?>"><?= ucfirst($sp['status']) ?></span>
                </div>
                <span style="font-size:.72rem;color:<?= $isPast?'#ef4444':'#6b7280' ?>;"><?= date('M j, g:ia',$schTs) ?></span>
            </div>
            <div style="font-size:.88rem;font-weight:700;color:#111827;margin-bottom:.3rem;"><?= Security::escapeHTML($sp['client_name']??'Unknown') ?></div>
            <?php if ($sp['campaign_name']): ?><div style="font-size:.73rem;color:#9ca3af;">📣 <?= Security::escapeHTML($sp['campaign_name']) ?></div><?php endif; ?>
            <div style="font-size:.82rem;color:#374151;margin-top:.6rem;line-height:1.5;"><?= Security::escapeHTML(mb_strimwidth($sp['content'],0,120,'…')) ?></div>
        </div>
        <div class="mkt-card-foot">
            <span style="font-size:.72rem;color:#9ca3af;"><?= date('M j, Y',strtotime($sp['created_at'])) ?></span>
            <?php if (in_array($role,['admin','manager'])): ?>
            <div style="display:flex;gap:.4rem;">
                <button class="btn-xs" onclick="toggleInlineEdit('sp-<?= $sp['id'] ?>')">✏️ Edit</button>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this post?')">
                    <?= Security::getCSRFTokenField() ?><input type="hidden" name="post_id" value="<?= $sp['id'] ?>">
                    <button type="submit" name="delete_social_post" class="btn-xs danger">🗑 Delete</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php if (in_array($role,['admin','manager'])): ?>
        <div id="sp-<?= $sp['id'] ?>" class="inline-edit">
            <h5>✏️ Edit Post</h5>
            <form method="post">
                <?= Security::getCSRFTokenField() ?><input type="hidden" name="post_id" value="<?= $sp['id'] ?>">
                <div class="form-2col">
                    <div class="fg"><label>Platform</label>
                        <select name="platform">
                            <?php foreach (['Instagram','Facebook','Twitter','LinkedIn','TikTok'] as $pl): ?>
                            <option value="<?= $pl ?>" <?= $sp['platform']===$pl?'selected':'' ?>><?= $platform_emojis[$pl]??'' ?> <?= $pl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg"><label>Status</label>
                        <select name="status">
                            <?php foreach (['draft'=>'Draft','scheduled'=>'Scheduled','published'=>'Published'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= $sp['status']===$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg" style="grid-column:1/-1;"><label>Scheduled Date</label><input type="datetime-local" name="scheduled_date" value="<?= date('Y-m-d\TH:i',strtotime($sp['scheduled_for'])) ?>" required></div>
                </div>
                <div class="fg"><label>Content</label><textarea name="content" rows="3" required><?= Security::escapeHTML($sp['content']) ?></textarea></div>
                <div class="fg"><label>Engagement Metrics <span class="opt">(JSON)</span></label><input type="text" name="engagement_metrics" value="<?= Security::escapeHTML($sp['engagement_stats']??'') ?>" placeholder='{"likes":100}'></div>
                <div class="form-actions">
                    <button type="submit" name="update_social_post" class="btn" style="background:#ec4899;color:#fff;border-color:#ec4899;">💾 Save</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleInlineEdit('sp-<?= $sp['id'] ?>')">Cancel</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<div class="lc-pagination" id="sp-pagination"></div>
<?php endif; ?>

<!-- ══ EMAIL CAMPAIGNS ══ -->
<?php elseif ($view==='email-campaigns'): ?>

<?php if (in_array($role,['admin','manager'])): ?>
<div class="section-header-row">
    <h3>✉️ Email Campaigns</h3>
    <button class="create-btn" id="ec-toggle" onclick="toggleForm('ec-form-wrap','ec-toggle','➕ New Campaign','✕ Cancel')">➕ New Campaign</button>
</div>
<div id="ec-form-wrap" style="display:none;">
    <div class="form-card">
        <div class="form-card-head"><h3>✉️ Create Email Campaign</h3><p>Draft and schedule a new email campaign</p></div>
        <div class="form-card-body">
            <form method="post" id="ec-create-form">
                <?= Security::getCSRFTokenField() ?>
                <div class="form-section"><h4>📋 Campaign Details</h4>
                    <div class="form-2col">
                        <div class="fg"><label>Campaign Name <span class="req">*</span></label><input type="text" name="campaign_name" required placeholder="e.g. June Newsletter"></div>
                        <div class="fg"><label>Client <span class="req">*</span></label>
                            <select name="client_id" required><option value="">— Select Client —</option>
                                <?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"><?= Security::escapeHTML($c['name']) ?> — <?= Security::escapeHTML($c['company']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Send Date &amp; Time <span class="req">*</span></label><input type="datetime-local" name="send_date" required></div>
                        <div class="fg"><label>Subject Line <span class="req">*</span></label><input type="text" name="subject" required placeholder="Compelling subject line…"></div>
                    </div>
                </div>
                <div class="form-section" style="margin-top:1.25rem;"><h4>✍️ Email Content</h4>
                    <div class="fg" style="margin-bottom:0;">
                        <div id="ec-quill-editor" style="min-height:280px;"></div>
                        <input type="hidden" name="content" id="ec-content-hidden">
                    </div>
                </div>
                <div class="form-actions" style="margin-top:1.25rem;">
                    <button type="submit" name="create_email_campaign" class="btn" style="background:#8b5cf6;color:#fff;border-color:#8b5cf6;padding:.65rem 1.5rem;">✉️ Create Campaign</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm('ec-form-wrap','ec-toggle','➕ New Campaign','✕ Cancel')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
<div class="section-header-row"><h3>✉️ Email Campaigns</h3></div>
<?php endif; ?>

<!-- Filters -->
<div class="controls-row">
    <input type="text" id="ec-search" placeholder="🔍  Search campaign name, subject…" oninput="filterMkt('ec-list','ec-search',{status:'ec-filter-status'},'ec-count')">
    <select id="ec-filter-status" onchange="filterMkt('ec-list','ec-search',{status:'ec-filter-status'},'ec-count')">
        <option value="">All Statuses</option>
        <option value="draft">Draft</option>
        <option value="scheduled">Scheduled</option>
        <option value="sent">Sent</option>
    </select>
    <div class="lc-date-filter">
        <select id="ec-date-range">
            <option value="all">All Dates</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
            <option value="lastyear">Last Year</option>
            <option value="custom">Custom Range</option>
        </select>
        <span class="lc-custom-range">
            <input type="date" id="ec-date-from">
            <span>to</span>
            <input type="date" id="ec-date-to">
        </span>
    </div>
    <span class="result-count" id="ec-count"><?= count($email_campaigns) ?> campaign<?= count($email_campaigns)!=1?'s':'' ?></span>
</div>

<?php if (empty($email_campaigns)): ?>
<div class="empty-box"><div class="emoji">✉️</div><h3>No email campaigns yet</h3><p>Create your first email campaign above.</p></div>
<?php else: ?>
<div class="no-results" id="ec-no-results">No campaigns match your filters.</div>
<div class="ec-campaign-list" id="ec-list">
    <?php foreach ($email_campaigns as $ec):
        $rcpCount    = count($email_recipients[$ec['id']] ?? []);
        $hasSentStats= ($ec['open_rate'] > 0 || $ec['click_rate'] > 0);
        $searchText  = strtolower($ec['campaign_name'].' '.$ec['subject'].' '.($ec['client_name']??''));
    ?>
    <div class="ec-campaign-card"
         data-search="<?= htmlspecialchars($searchText, ENT_QUOTES) ?>"
         data-status="<?= $ec['status'] ?>"
         data-date="<?= !empty($ec['send_date']) ? date('Y-m-d', strtotime($ec['send_date'])) : '' ?>">

        <!-- Hero gradient header -->
        <div class="ec-card-hero">
            <div class="ec-card-hero-info">
                <div class="ec-card-name"><?= Security::escapeHTML($ec['campaign_name']) ?></div>
                <div class="ec-card-client">
                    🏢 <?= Security::escapeHTML($ec['client_name'] ?? 'No client') ?>
                    <?= $ec['client_company'] ? ' · '.Security::escapeHTML($ec['client_company']) : '' ?>
                    <?= $ec['campaign_name'] ? ' · 📣 '.Security::escapeHTML($ec['campaign_name']) : '' ?>
                </div>
            </div>
            <span class="badge b-<?= $ec['status'] ?>" style="flex-shrink:0;font-size:.78rem;padding:.3rem .75rem;"><?= ucfirst($ec['status']) ?></span>
        </div>

        <!-- Subject line -->
        <div class="ec-subject-row">
            <div class="ec-subject-icon">✉️</div>
            <div>
                <div class="ec-subject-label">Subject Line</div>
                <div class="ec-subject-text"><?= Security::escapeHTML($ec['subject']) ?></div>
            </div>
        </div>

        <!-- Metrics grid -->
        <div class="ec-metrics-grid <?= $hasSentStats ? 'has-stats' : 'no-stats' ?>">
            <div class="ec-metric-cell">
                <div class="ec-metric-val"><?= $rcpCount ?></div>
                <div class="ec-metric-lbl">👥 Recipients</div>
            </div>
            <div class="ec-metric-cell">
                <div class="ec-metric-val ec-metric-small"><?= $ec['send_date'] ? date('M j, Y', strtotime($ec['send_date'])) : '—' ?></div>
                <div class="ec-metric-lbl">📅 Send Date</div>
            </div>
            <?php if ($hasSentStats): ?>
            <div class="ec-metric-cell">
                <div class="ec-metric-val" style="color:#22c55e;"><?= number_format($ec['open_rate'], 1) ?>%</div>
                <div class="ec-metric-lbl">📬 Open Rate</div>
            </div>
            <div class="ec-metric-cell">
                <div class="ec-metric-val" style="color:#8b5cf6;"><?= number_format($ec['click_rate'], 1) ?>%</div>
                <div class="ec-metric-lbl">🖱️ Click Rate</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Progress bars (sent campaigns with stats) -->
        <?php if ($hasSentStats): ?>
        <div class="ec-progress-section">
            <div class="ec-prog-item">
                <div class="ec-prog-label"><span>Open Rate</span><span><?= number_format($ec['open_rate'], 1) ?>%</span></div>
                <div class="ec-prog-track"><div class="ec-prog-fill ec-prog-open" style="width:<?= min((float)$ec['open_rate'], 100) ?>%;"></div></div>
            </div>
            <div class="ec-prog-item">
                <div class="ec-prog-label"><span>Click Rate</span><span><?= number_format($ec['click_rate'], 1) ?>%</span></div>
                <div class="ec-prog-track"><div class="ec-prog-fill ec-prog-click" style="width:<?= min((float)$ec['click_rate'], 100) ?>%;"></div></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recipient avatars -->
        <?php if ($rcpCount > 0): ?>
        <div class="ec-recipients-preview">
            <div class="ec-recipient-avatars">
                <?php foreach (array_slice($email_recipients[$ec['id']], 0, 6) as $i => $r):
                    $col = $avatar_colors[$i % count($avatar_colors)];
                    $ini = strtoupper(substr($r['name'] ?? 'U', 0, 1));
                ?>
                <div class="ec-avatar" style="background:<?= $col ?>;" title="<?= Security::escapeHTML($r['name']) ?> &lt;<?= Security::escapeHTML($r['email']) ?>&gt;"><?= $ini ?></div>
                <?php endforeach; ?>
            </div>
            <span class="ec-recipients-more">
                <?php if ($rcpCount > 6): ?>+<?= $rcpCount - 6 ?> more · <?php endif; ?>
                <?= $rcpCount ?> recipient<?= $rcpCount !== 1 ? 's' : '' ?>
            </span>
        </div>
        <?php endif; ?>

        <!-- Footer actions -->
        <div class="ec-card-foot">
            <span style="font-size:.72rem;color:#9ca3af;">Created <?= date('M j, Y', strtotime($ec['created_at'])) ?></span>
            <?php if (in_array($role, ['admin','manager'])): ?>
            <div class="ec-action-row">
                <button class="btn-xs" onclick="toggleInlineEditEC('ec-<?= $ec['id'] ?>')">✏️ Edit</button>
                <button class="btn-xs" onclick="openRecipientModal(<?= $ec['id'] ?>)" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534;">👤 Add Recipient</button>
                <?php if ($ec['status'] === 'draft' && $rcpCount > 0): ?>
                <form method="post" style="display:inline;" onsubmit="return confirm('Send to <?= $rcpCount ?> recipient<?= $rcpCount!==1?'s':'' ?>?')">
                    <?= Security::getCSRFTokenField() ?><input type="hidden" name="campaign_id" value="<?= $ec['id'] ?>">
                    <button type="submit" name="send_email_campaign" class="btn-xs" style="background:#ede9fe;border-color:#c4b5fd;color:#6d28d9;">🚀 Send Now</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Inline edit -->
        <?php if (in_array($role, ['admin','manager'])): ?>
        <div id="ec-<?= $ec['id'] ?>" class="inline-edit" style="display:none;">
            <h5>✏️ Edit Campaign</h5>
            <form method="post" class="ec-edit-form" data-id="<?= $ec['id'] ?>">
                <?= Security::getCSRFTokenField() ?><input type="hidden" name="campaign_id" value="<?= $ec['id'] ?>">
                <div class="form-2col">
                    <div class="fg"><label>Send Date</label><input type="datetime-local" name="send_date" value="<?= date('Y-m-d\TH:i', strtotime($ec['send_date'])) ?>" required></div>
                    <div class="fg"><label>Status</label>
                        <select name="status" required>
                            <?php foreach (['draft'=>'Draft','scheduled'=>'Scheduled','sent'=>'Sent'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= $ec['status']===$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg" style="grid-column:1/-1;"><label>Subject</label><input type="text" name="subject" value="<?= Security::escapeHTML($ec['subject']) ?>" required></div>
                </div>
                <div class="fg">
                    <label>Content</label>
                    <div id="ec-edit-quill-<?= $ec['id'] ?>" style="min-height:200px;"><?= $ec['content'] ?></div>
                    <input type="hidden" name="content" id="ec-edit-content-<?= $ec['id'] ?>">
                </div>
                <div class="form-actions">
                    <button type="submit" name="update_email_campaign" class="btn" style="background:#8b5cf6;color:#fff;border-color:#8b5cf6;">💾 Save</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleInlineEditEC('ec-<?= $ec['id'] ?>')">Cancel</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<div class="lc-pagination" id="ec-pagination"></div>
<?php endif; ?>

<!-- ══ BLOG POSTS ══ -->
<?php elseif ($view==='blog-posts'): ?>

<?php if (in_array($role,['admin','manager'])): ?>
<div class="section-header-row">
    <h3>📝 Blog Posts</h3>
    <button class="create-btn" id="bp-toggle" onclick="toggleForm('bp-form-wrap','bp-toggle','➕ New Post','✕ Cancel')">➕ New Post</button>
</div>
<div id="bp-form-wrap" style="display:none;">
    <div class="form-card">
        <div class="form-card-head"><h3>📝 Create Blog Post</h3><p>Write and publish content for your clients</p></div>
        <div class="form-card-body">
            <form method="post">
                <?= Security::getCSRFTokenField() ?>
                <div class="form-section"><h4>📋 Post Details</h4>
                    <div class="form-2col">
                        <div class="fg" style="grid-column:1/-1;"><label>Title <span class="req">*</span></label><input type="text" name="title" required placeholder="e.g. 10 Tips for Digital Marketing Success"></div>
                        <div class="fg"><label>Author <span class="req">*</span></label>
                            <select name="author_id" required>
                                <?php foreach ($all_users as $u): ?><option value="<?= $u['id'] ?>" <?= $u['id']===$_SESSION['user_id']?'selected':'' ?>><?= Security::escapeHTML($u['username']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Category <span class="req">*</span></label>
                            <select name="category" required><option value="">— Select —</option>
                                <?php foreach ($blog_categories as $cat): ?><option value="<?= $cat ?>"><?= ($cat_icons[$cat]??'📄').' '.ucwords(str_replace('-',' ',$cat)) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Status <span class="req">*</span></label>
                            <select name="status" required>
                                <option value="draft">Draft</option><option value="scheduled">Scheduled</option><option value="published">Published</option>
                            </select>
                        </div>
                        <div class="fg"><label>Publish Date <span class="req">*</span></label><input type="datetime-local" name="publish_date" value="<?= date('Y-m-d\TH:i') ?>" required></div>
                        <div class="fg"><label>Tags <span class="opt">(comma-separated)</span></label><input type="text" name="tags" placeholder="seo, marketing, growth"></div>
                    </div>
                </div>
                <div class="form-section" style="margin-top:1.25rem;"><h4>🔗 Association <span style="color:#9ca3af;font-weight:400;font-size:.8rem;">(optional)</span></h4>
                    <div class="form-2col">
                        <div class="fg"><label>Client</label>
                            <select name="client_id"><option value="">— None —</option>
                                <?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"><?= Security::escapeHTML($c['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Campaign</label>
                            <select name="campaign_id"><option value="">— None —</option>
                                <?php foreach ($campaigns as $c): ?><option value="<?= $c['id'] ?>"><?= Security::escapeHTML($c['campaign_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg" style="grid-column:1/-1;"><label>Featured Image URL</label><input type="url" name="featured_image" placeholder="https://example.com/image.jpg"></div>
                    </div>
                </div>
                <div class="form-section" style="margin-top:1.25rem;"><h4>✍️ Content</h4>
                    <div class="fg"><label>Excerpt <span class="opt">(brief description)</span></label><textarea name="excerpt" rows="2" placeholder="Short summary for previews and SEO…"></textarea></div>
                    <div class="fg"><label>Body Content <span class="req">*</span></label><textarea name="content" rows="10" required placeholder="Write your full blog post here…"></textarea></div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="create_blog_post" class="btn" style="background:#ec4899;color:#fff;border-color:#ec4899;padding:.65rem 1.5rem;">📝 Publish Post</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm('bp-form-wrap','bp-toggle','➕ New Post','✕ Cancel')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
<div class="section-header-row"><h3>📝 Blog Posts</h3></div>
<?php endif; ?>

<!-- Filters -->
<div class="controls-row">
    <input type="text" id="bp-search" placeholder="🔍  Search title, author, tags…" oninput="filterMkt('bp-grid','bp-search',{category:'bp-filter-cat',status:'bp-filter-status'},'bp-count')">
    <select id="bp-filter-cat" onchange="filterMkt('bp-grid','bp-search',{category:'bp-filter-cat',status:'bp-filter-status'},'bp-count')">
        <option value="">All Categories</option>
        <?php foreach ($blog_categories as $cat): ?><option value="<?= $cat ?>"><?= ($cat_icons[$cat]??'📄').' '.ucwords(str_replace('-',' ',$cat)) ?></option><?php endforeach; ?>
    </select>
    <select id="bp-filter-status" onchange="filterMkt('bp-grid','bp-search',{category:'bp-filter-cat',status:'bp-filter-status'},'bp-count')">
        <option value="">All Statuses</option>
        <option value="draft">Draft</option>
        <option value="scheduled">Scheduled</option>
        <option value="published">Published</option>
    </select>
    <div class="lc-date-filter">
        <select id="bp-date-range">
            <option value="all">All Dates</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
            <option value="lastyear">Last Year</option>
            <option value="custom">Custom Range</option>
        </select>
        <span class="lc-custom-range">
            <input type="date" id="bp-date-from">
            <span>to</span>
            <input type="date" id="bp-date-to">
        </span>
    </div>
    <span class="result-count" id="bp-count"><?= count($blog_posts) ?> post<?= count($blog_posts)!=1?'s':'' ?></span>
</div>

<?php if (empty($blog_posts)): ?>
<div class="empty-box"><div class="emoji">📝</div><h3>No blog posts yet</h3><p>Create your first blog post above.</p></div>
<?php else: ?>
<div class="no-results" id="bp-no-results">No posts match your filters.</div>
<div class="mkt-grid" id="bp-grid">
    <?php foreach ($blog_posts as $bp):
        $catIcon=$cat_icons[$bp['category']]??'📄';
        $searchText=strtolower($bp['title'].' '.($bp['author']??'').' '.($bp['tags']??'').' '.($bp['category']??''));
    ?>
    <div class="mkt-card"
         data-search="<?= htmlspecialchars($searchText,ENT_QUOTES) ?>"
         data-category="<?= Security::escapeHTML($bp['category']??'') ?>"
         data-status="<?= $bp['status'] ?>"
         data-date="<?= date('Y-m-d', strtotime($bp['published_at']??$bp['created_at'])) ?>">
        <?php if ($bp['featured_image']): ?>
        <img src="<?= Security::escapeHTML($bp['featured_image']) ?>" alt="" class="blog-img" onerror="this.style.display='none'">
        <?php else: ?>
        <div class="blog-img-placeholder"><?= $catIcon ?></div>
        <?php endif; ?>
        <div class="mkt-card-top">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
                <span class="cat-chip"><?= $catIcon ?> <?= ucwords(str_replace('-',' ',$bp['category']??'')) ?></span>
                <span class="badge b-<?= $bp['status'] ?>"><?= ucfirst($bp['status']) ?></span>
            </div>
            <div style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:.35rem;line-height:1.35;"><?= Security::escapeHTML($bp['title']) ?></div>
            <div style="font-size:.73rem;color:#9ca3af;margin-bottom:.5rem;">✍️ <?= Security::escapeHTML($bp['author']) ?> · <?= date('M j, Y',strtotime($bp['published_at']??$bp['created_at'])) ?></div>
            <?php if ($bp['excerpt']): ?><div style="font-size:.8rem;color:#6b7280;line-height:1.5;margin-bottom:.6rem;"><?= Security::escapeHTML(mb_strimwidth($bp['excerpt'],0,100,'…')) ?></div><?php endif; ?>
            <?php if ($bp['tags']): ?>
            <div style="margin-bottom:.4rem;">
                <?php foreach (array_slice(array_map('trim',explode(',',$bp['tags'])),0,4) as $tag): ?>
                <?php if ($tag): ?><span class="tag-chip">#<?= Security::escapeHTML($tag) ?></span><?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($bp['client_name']||$bp['campaign_name']): ?>
            <div style="font-size:.72rem;color:#9ca3af;margin-top:.35rem;">
                <?= $bp['client_name']?'🏢 '.Security::escapeHTML($bp['client_name']):'' ?>
                <?= ($bp['client_name']&&$bp['campaign_name'])?' · ':'' ?>
                <?= $bp['campaign_name']?'📣 '.Security::escapeHTML($bp['campaign_name']):'' ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="mkt-card-foot">
            <span style="font-size:.72rem;color:#9ca3af;">#<?= $bp['id'] ?></span>
            <?php if (in_array($role,['admin','manager'])): ?>
            <div style="display:flex;gap:.4rem;">
                <button class="btn-xs" onclick="toggleInlineEdit('bp-<?= $bp['id'] ?>')">✏️ Edit</button>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this post?')">
                    <?= Security::getCSRFTokenField() ?><input type="hidden" name="post_id" value="<?= $bp['id'] ?>">
                    <button type="submit" name="delete_blog_post" class="btn-xs danger">🗑 Delete</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php if (in_array($role,['admin','manager'])): ?>
        <div id="bp-<?= $bp['id'] ?>" class="inline-edit">
            <h5>✏️ Edit Blog Post</h5>
            <form method="post">
                <?= Security::getCSRFTokenField() ?><input type="hidden" name="post_id" value="<?= $bp['id'] ?>">
                <div class="form-2col">
                    <div class="fg" style="grid-column:1/-1;"><label>Title</label><input type="text" name="title" value="<?= Security::escapeHTML($bp['title']) ?>" required></div>
                    <div class="fg"><label>Category</label>
                        <select name="category" required>
                            <?php foreach ($blog_categories as $cat): ?><option value="<?= $cat ?>" <?= $bp['category']===$cat?'selected':'' ?>><?= ($cat_icons[$cat]??'📄').' '.ucwords(str_replace('-',' ',$cat)) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg"><label>Status</label>
                        <select name="status" required>
                            <?php foreach (['draft'=>'Draft','scheduled'=>'Scheduled','published'=>'Published'] as $v=>$l): ?><option value="<?= $v ?>" <?= $bp['status']===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg"><label>Publish Date</label><input type="datetime-local" name="publish_date" value="<?= date('Y-m-d\TH:i',strtotime($bp['published_at']??'now')) ?>" required></div>
                    <div class="fg"><label>Tags</label><input type="text" name="tags" value="<?= Security::escapeHTML($bp['tags']??'') ?>"></div>
                    <div class="fg" style="grid-column:1/-1;"><label>Featured Image URL</label><input type="url" name="featured_image" value="<?= Security::escapeHTML($bp['featured_image']??'') ?>"></div>
                </div>
                <div class="fg"><label>Excerpt</label><textarea name="excerpt" rows="2"><?= Security::escapeHTML($bp['excerpt']??'') ?></textarea></div>
                <div class="fg"><label>Content</label><textarea name="content" rows="6" required><?= Security::escapeHTML($bp['content']) ?></textarea></div>
                <div class="form-actions">
                    <button type="submit" name="update_blog_post" class="btn" style="background:#ec4899;color:#fff;border-color:#ec4899;">💾 Save</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleInlineEdit('bp-<?= $bp['id'] ?>')">Cancel</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<div class="lc-pagination" id="bp-pagination"></div>
<?php endif; ?>

<!-- ══ CAMPAIGNS ══ -->
<?php elseif ($view==='campaigns'): ?>

<?php if (in_array($role,['admin','manager'])): ?>
<div class="section-header-row">
    <h3>📣 Marketing Campaigns</h3>
    <button class="create-btn" id="cp-toggle" onclick="toggleForm('cp-form-wrap','cp-toggle','➕ New Campaign','✕ Cancel')">➕ New Campaign</button>
</div>
<div id="cp-form-wrap" style="display:none;">
    <div class="form-card">
        <div class="form-card-head"><h3>📣 Create New Campaign</h3><p>Launch a new marketing campaign for a client</p></div>
        <div class="form-card-body">
            <form method="post">
                <?= Security::getCSRFTokenField() ?>
                <div class="form-section"><h4>📋 Campaign Info</h4>
                    <div class="form-2col">
                        <div class="fg"><label>Campaign Name <span class="req">*</span></label><input type="text" name="campaign_name" required placeholder="e.g. Q3 Brand Awareness"></div>
                        <div class="fg"><label>Client <span class="req">*</span></label>
                            <select name="client_id" required><option value="">— Select Client —</option>
                                <?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"><?= Security::escapeHTML($c['name']) ?> — <?= Security::escapeHTML($c['company']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Campaign Type <span class="req">*</span></label>
                            <select name="campaign_type" required>
                                <?php foreach ($campaign_types as $v=>$l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Budget (R) <span class="req">*</span></label><input type="number" name="budget" step="0.01" min="0" required placeholder="0.00"></div>
                        <div class="fg"><label>Start Date <span class="req">*</span></label><input type="date" name="start_date" required></div>
                        <div class="fg"><label>End Date <span class="req">*</span></label><input type="date" name="end_date" required></div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="create_campaign" class="btn" style="background:#ec4899;color:#fff;border-color:#ec4899;padding:.65rem 1.5rem;">📣 Create Campaign</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm('cp-form-wrap','cp-toggle','➕ New Campaign','✕ Cancel')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
<div class="section-header-row"><h3>📣 Marketing Campaigns</h3></div>
<?php endif; ?>

<!-- Filters -->
<div class="controls-row">
    <input type="text" id="cp-search" placeholder="🔍  Search name, client…" oninput="filterMkt('cp-grid','cp-search',{type:'cp-filter-type',status:'cp-filter-status'},'cp-count')">
    <select id="cp-filter-type" onchange="filterMkt('cp-grid','cp-search',{type:'cp-filter-type',status:'cp-filter-status'},'cp-count')">
        <option value="">All Types</option>
        <?php foreach ($campaign_types as $v=>$l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
    </select>
    <select id="cp-filter-status" onchange="filterMkt('cp-grid','cp-search',{type:'cp-filter-type',status:'cp-filter-status'},'cp-count')">
        <option value="">All Statuses</option>
        <option value="planning">Planning</option>
        <option value="active">Active</option>
        <option value="completed">Completed</option>
        <option value="paused">Paused</option>
    </select>
    <div class="lc-date-filter">
        <select id="cp-date-range">
            <option value="all">All Dates</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
            <option value="lastyear">Last Year</option>
            <option value="custom">Custom Range</option>
        </select>
        <span class="lc-custom-range">
            <input type="date" id="cp-date-from">
            <span>to</span>
            <input type="date" id="cp-date-to">
        </span>
    </div>
    <span class="result-count" id="cp-count"><?= count($campaigns) ?> campaign<?= count($campaigns)!=1?'s':'' ?></span>
</div>

<?php if (empty($campaigns)): ?>
<div class="empty-box"><div class="emoji">📣</div><h3>No campaigns yet</h3><p>Create your first campaign above.</p></div>
<?php else: ?>
<div class="no-results" id="cp-no-results">No campaigns match your filters.</div>
<div class="mkt-grid" id="cp-grid">
    <?php
    $status_colors=['active'=>'#22c55e','planning'=>'#6b7280','completed'=>'#3b82f6','paused'=>'#f59e0b'];
    foreach ($campaigns as $c):
        $sDate=$c['start_date']?date('M j, Y',strtotime($c['start_date'])):'—';
        $eDate=$c['end_date']?date('M j, Y',strtotime($c['end_date'])):'—';
        $bgCol=$status_colors[$c['status']]??'#6b7280';
        $searchText=strtolower($c['campaign_name'].' '.($c['client_name']??'').' '.($c['campaign_type']??''));
    ?>
    <div class="mkt-card" style="border-top:3px solid <?= $bgCol ?>;"
         data-search="<?= htmlspecialchars($searchText,ENT_QUOTES) ?>"
         data-type="<?= Security::escapeHTML($c['campaign_type']??'') ?>"
         data-status="<?= $c['status'] ?>"
         data-date="<?= !empty($c['start_date']) ? date('Y-m-d', strtotime($c['start_date'])) : '' ?>">
        <div class="mkt-card-top">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem;flex-wrap:wrap;gap:.4rem;">
                <div style="font-size:.95rem;font-weight:700;color:#111827;"><?= Security::escapeHTML($c['campaign_name']) ?></div>
                <span class="badge b-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span>
            </div>
            <div style="font-size:.78rem;color:#6b7280;margin-bottom:.65rem;"><?= Security::escapeHTML($c['client_name']??'Unknown') ?><?= $c['client_company']?' — '.Security::escapeHTML($c['client_company']):'' ?></div>
            <div class="campaign-card-meta">
                <div><span class="cm-label">Type</span><?= Security::escapeHTML(str_replace('_',' ',ucfirst($c['campaign_type']??''))) ?></div>
                <div><span class="cm-label">Budget</span><strong style="color:#ec4899;">R <?= number_format($c['budget']??0,2) ?></strong></div>
                <div><span class="cm-label">Start</span><?= $sDate ?></div>
                <div><span class="cm-label">End</span><?= $eDate ?></div>
            </div>
            <?php if (!empty($c['metrics'])): $met=json_decode($c['metrics'],true); if ($met): ?>
            <div style="background:#fdf2f8;border-radius:8px;padding:.6rem .85rem;margin-top:.5rem;">
                <?php foreach ($met as $mk=>$mv): ?><span style="font-size:.73rem;color:#6b7280;">📊 <?= ucfirst(str_replace('_',' ',$mk)) ?>: <strong style="color:#ec4899;"><?= Security::escapeHTML((string)$mv) ?></strong>&nbsp;&nbsp;</span><?php endforeach; ?>
            </div>
            <?php endif; endif; ?>
        </div>
        <div class="mkt-card-foot">
            <span style="font-size:.72rem;color:#9ca3af;">Created <?= date('M j, Y',strtotime($c['created_at'])) ?></span>
            <?php if (in_array($role,['admin','manager'])): ?>
            <button class="btn-xs" onclick="toggleInlineEdit('cp-<?= $c['id'] ?>')">✏️ Update</button>
            <?php endif; ?>
        </div>
        <?php if (in_array($role,['admin','manager'])): ?>
        <div id="cp-<?= $c['id'] ?>" class="inline-edit">
            <h5>✏️ Update Campaign</h5>
            <form method="post">
                <?= Security::getCSRFTokenField() ?><input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
                <div class="form-2col">
                    <div class="fg"><label>Status</label>
                        <select name="status" required>
                            <?php foreach (['planning'=>'Planning','active'=>'Active','completed'=>'Completed','paused'=>'Paused'] as $v=>$l): ?><option value="<?= $v ?>" <?= $c['status']===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg"><label>Metrics <span class="opt">(JSON)</span></label><input type="text" name="metrics" value="<?= Security::escapeHTML($c['metrics']??'') ?>" placeholder='{"reach":1000}'></div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="update_campaign" class="btn" style="background:#ec4899;color:#fff;border-color:#ec4899;">💾 Save</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleInlineEdit('cp-<?= $c['id'] ?>')">Cancel</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<div class="lc-pagination" id="cp-pagination"></div>
<?php endif; ?>

<?php endif; ?>

</div><!-- /.main-content -->

<!-- Recipient Modal -->
<div id="recipientModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-head">
            <h3>👤 Add Recipient</h3>
            <button class="modal-close" onclick="closeModal('recipientModal')">✕</button>
        </div>
        <div class="modal-body">
            <form method="post">
                <?= Security::getCSRFTokenField() ?>
                <input type="hidden" id="modal-campaign-id" name="campaign_id" value="">
                <div class="fg"><label>Email Address <span class="req">*</span></label><input type="email" name="email" required placeholder="recipient@example.com"></div>
                <div class="fg"><label>Full Name <span class="req">*</span></label><input type="text" name="name" required placeholder="Jane Smith"></div>
                <div class="form-actions">
                    <button type="submit" name="add_email_recipient" class="btn" style="background:#8b5cf6;color:#fff;border-color:#8b5cf6;">➕ Add Recipient</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('recipientModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../js/notification.js"></script>
<script src="../js/list-controls.js"></script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
// Toggle create form visibility
function toggleForm(wrapId, btnId, labelOpen, labelClose) {
    const wrap = document.getElementById(wrapId);
    const btn  = document.getElementById(btnId);
    const isOpen = wrap.style.display === 'block';
    wrap.style.display = isOpen ? 'none' : 'block';
    if (btn) {
        btn.textContent = isOpen ? labelOpen : labelClose;
        btn.classList.toggle('open', !isOpen);
    }
    if (!isOpen) wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Toggle inline edit section inside a card
function toggleInlineEdit(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'block' ? 'none' : 'block';
}

// Client-side filter for card grids / flex lists
function filterMkt(containerId, searchInputId, filterMap, countId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const searchVal = (document.getElementById(searchInputId)?.value || '').toLowerCase().trim();
    const filterVals = {};
    for (const [dataKey, selectId] of Object.entries(filterMap)) {
        filterVals[dataKey] = document.getElementById(selectId)?.value || '';
    }
    const cards = container.querySelectorAll('[data-search]');
    let visible = 0;
    cards.forEach(card => {
        let show = true;
        if (searchVal && !(card.dataset.search || '').includes(searchVal)) show = false;
        for (const [dataKey, val] of Object.entries(filterVals)) {
            if (val && card.dataset[dataKey] !== val) show = false;
        }
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const countEl = document.getElementById(countId);
    if (countEl) {
        const noun = countEl.textContent.replace(/^\d+\s*/,'').replace(/s$/,'');
        countEl.textContent = visible + ' ' + noun + (visible !== 1 ? 's' : '');
    }
    // Show "no results" message
    const noRes = document.getElementById(containerId.replace('-grid','-no-results').replace('-list','-no-results'));
    if (noRes) noRes.style.display = visible === 0 ? 'block' : 'none';

    const prefix = containerId.replace('-grid','').replace('-list','');
    ListControls.applyDateFilterAndPaginate(containerId, '[data-search]', prefix+'-date-range', prefix+'-date-from', prefix+'-date-to', prefix+'-pagination');
}

// Init pagination + date-range filters for Marketing lists (only the active view's elements exist)
document.addEventListener('DOMContentLoaded', function() {
    ListControls.initDateRangeControl('sp-date-range', 'sp-date-from', 'sp-date-to', function() {
        filterMkt('sp-grid','sp-search',{platform:'sp-filter-platform',status:'sp-filter-status'},'sp-count');
    });
    ListControls.initDateRangeControl('ec-date-range', 'ec-date-from', 'ec-date-to', function() {
        filterMkt('ec-list','ec-search',{status:'ec-filter-status'},'ec-count');
    });
    ListControls.initDateRangeControl('bp-date-range', 'bp-date-from', 'bp-date-to', function() {
        filterMkt('bp-grid','bp-search',{category:'bp-filter-cat',status:'bp-filter-status'},'bp-count');
    });
    ListControls.initDateRangeControl('cp-date-range', 'cp-date-from', 'cp-date-to', function() {
        filterMkt('cp-grid','cp-search',{type:'cp-filter-type',status:'cp-filter-status'},'cp-count');
    });
    filterMkt('sp-grid','sp-search',{platform:'sp-filter-platform',status:'sp-filter-status'},'sp-count');
    filterMkt('ec-list','ec-search',{status:'ec-filter-status'},'ec-count');
    filterMkt('bp-grid','bp-search',{category:'bp-filter-cat',status:'bp-filter-status'},'bp-count');
    filterMkt('cp-grid','cp-search',{type:'cp-filter-type',status:'cp-filter-status'},'cp-count');
});

// Modal
function openRecipientModal(id) {
    document.getElementById('modal-campaign-id').value = id;
    document.getElementById('recipientModal').classList.add('open');
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if (e.target===o) o.classList.remove('open'); }));

// Calendar nav
function changeMonth(dir) {
    const url = new URL(window.location);
    let month = parseInt(url.searchParams.get('month') || '<?= $current_month ?>');
    let year  = parseInt(url.searchParams.get('year')  || '<?= $current_year ?>');
    month += dir;
    if (month > 12) { month = 1; year++; } else if (month < 1) { month = 12; year--; }
    url.searchParams.set('month', month.toString().padStart(2,'0'));
    url.searchParams.set('year',  year.toString());
    window.location.href = url.toString();
}

// ── Quill rich-text editors ──
const EC_TOOLBAR = [
    [{ header: [1, 2, 3, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ color: [] }, { background: [] }],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link', 'blockquote'],
    ['clean']
];
const EC_TOOLBAR_COMPACT = [
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link'],
    ['clean']
];

let ecCreateQuill = null;
const ecEditQuills = {};

// Init create-form Quill on page load (runs even when form is hidden — OK for Quill)
(function () {
    const el = document.getElementById('ec-quill-editor');
    if (!el) return;
    ecCreateQuill = new Quill('#ec-quill-editor', {
        theme: 'snow',
        placeholder: 'Write your email content here, use the toolbar to format headings, lists, links and more…',
        modules: { toolbar: EC_TOOLBAR }
    });
    const form = document.getElementById('ec-create-form');
    if (form) {
        form.addEventListener('submit', () => {
            document.getElementById('ec-content-hidden').value = ecCreateQuill.root.innerHTML;
        });
    }
})();

// Lazily init Quill for an inline edit when it opens
function toggleInlineEditEC(id) {
    const el = document.getElementById(id);
    const isOpen = el.style.display === 'block';
    el.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) {
        const numId = id.replace('ec-', '');
        if (!ecEditQuills[numId]) {
            const editorEl = document.getElementById('ec-edit-quill-' + numId);
            if (editorEl) {
                const savedHTML = editorEl.innerHTML;
                ecEditQuills[numId] = new Quill('#ec-edit-quill-' + numId, {
                    theme: 'snow',
                    modules: { toolbar: EC_TOOLBAR_COMPACT }
                });
                // Quill clears innerHTML on init — restore saved content as delta-safe HTML
                ecEditQuills[numId].root.innerHTML = savedHTML;
            }
        }
        // Wire submit once
        const form = el.querySelector('.ec-edit-form');
        if (form && !form.dataset.wired) {
            form.dataset.wired = '1';
            form.addEventListener('submit', () => {
                const fid = form.dataset.id;
                if (ecEditQuills[fid]) {
                    document.getElementById('ec-edit-content-' + fid).value = ecEditQuills[fid].root.innerHTML;
                }
            });
        }
    }
}

// Auto-dismiss flash
const flash = document.getElementById('flashMsg');
if (flash) setTimeout(() => { flash.style.transition='opacity .5s'; flash.style.opacity=0; setTimeout(()=>flash.remove(),500); }, 3500);
</script>
</body>
</html>

