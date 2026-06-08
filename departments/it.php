<?php
require_once '../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';

Security::requireDepartmentAccess('IT');

$database = new Database();
$db       = $database->getConnection();
$role     = $_SESSION['role'];

// ── POST HANDLERS ─────────────────────────────────────────────────────────

if ($_POST && isset($_POST['add_asset'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin','manager'])) { http_response_code(403); die(); }
    $db->prepare("INSERT INTO it_assets (asset_name,asset_type,brand,model,serial_number,purchase_date,warranty_expiry,assigned_to,status,location,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([Security::sanitizeInput($_POST['asset_name']),Security::sanitizeInput($_POST['asset_type']),Security::sanitizeInput($_POST['brand']??''),Security::sanitizeInput($_POST['model']??''),Security::sanitizeInput($_POST['serial_number']??''),$_POST['purchase_date']?:null,$_POST['warranty_expiry']?:null,!empty($_POST['assigned_to'])?(int)$_POST['assigned_to']:null,Security::sanitizeInput($_POST['status']),Security::sanitizeInput($_POST['location']??''),Security::sanitizeInput($_POST['notes']??''),$_SESSION['user_id']]);
    header("Location: it.php?view=assets&msg=asset_added"); exit();
}

if ($_POST && isset($_POST['update_asset'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin','manager'])) { http_response_code(403); die(); }
    $db->prepare("UPDATE it_assets SET asset_name=?,asset_type=?,brand=?,model=?,serial_number=?,purchase_date=?,warranty_expiry=?,assigned_to=?,status=?,location=?,notes=? WHERE id=?")
       ->execute([Security::sanitizeInput($_POST['asset_name']),Security::sanitizeInput($_POST['asset_type']),Security::sanitizeInput($_POST['brand']??''),Security::sanitizeInput($_POST['model']??''),Security::sanitizeInput($_POST['serial_number']??''),$_POST['purchase_date']?:null,$_POST['warranty_expiry']?:null,!empty($_POST['assigned_to'])?(int)$_POST['assigned_to']:null,Security::sanitizeInput($_POST['status']),Security::sanitizeInput($_POST['location']??''),Security::sanitizeInput($_POST['notes']??''),(int)$_POST['asset_id']]);
    header("Location: it.php?view=assets&msg=asset_updated"); exit();
}

if ($_POST && isset($_POST['add_license'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin','manager'])) { http_response_code(403); die(); }
    $db->prepare("INSERT INTO it_licenses (software_name,vendor,license_key,license_type,seats,seats_used,purchase_date,expiry_date,cost,status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([Security::sanitizeInput($_POST['software_name']),Security::sanitizeInput($_POST['vendor']??''),Security::sanitizeInput($_POST['license_key']??''),Security::sanitizeInput($_POST['license_type']),(int)($_POST['seats']??1),(int)($_POST['seats_used']??0),$_POST['purchase_date']?:null,$_POST['expiry_date']?:null,!empty($_POST['cost'])?(float)$_POST['cost']:null,Security::sanitizeInput($_POST['status']),Security::sanitizeInput($_POST['notes']??''),$_SESSION['user_id']]);
    header("Location: it.php?view=licenses&msg=license_added"); exit();
}

if ($_POST && isset($_POST['update_license'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin','manager'])) { http_response_code(403); die(); }
    $db->prepare("UPDATE it_licenses SET software_name=?,vendor=?,license_type=?,seats=?,seats_used=?,expiry_date=?,cost=?,status=?,notes=? WHERE id=?")
       ->execute([Security::sanitizeInput($_POST['software_name']),Security::sanitizeInput($_POST['vendor']??''),Security::sanitizeInput($_POST['license_type']),(int)$_POST['seats'],(int)$_POST['seats_used'],$_POST['expiry_date']?:null,!empty($_POST['cost'])?(float)$_POST['cost']:null,Security::sanitizeInput($_POST['status']),Security::sanitizeInput($_POST['notes']??''),(int)$_POST['license_id']]);
    header("Location: it.php?view=licenses&msg=license_updated"); exit();
}

// ── DATA ──────────────────────────────────────────────────────────────────

$search_asset   = trim($_GET['search_asset']   ?? '');
$filter_type    = $_GET['filter_type']    ?? '';
$filter_status  = $_GET['filter_status']  ?? '';

$search_license  = trim($_GET['search_license']  ?? '');
$filter_vendor   = $_GET['filter_vendor']   ?? '';
$filter_lstatus  = $_GET['filter_lstatus']  ?? '';
$filter_ltype    = $_GET['filter_ltype']    ?? '';

$asset_where = ['1=1']; $ap = [];
if ($search_asset)  { $asset_where[] = "(a.asset_name LIKE ? OR a.brand LIKE ? OR a.model LIKE ? OR a.location LIKE ?)"; $l="%$search_asset%"; $ap=array_merge($ap,[$l,$l,$l,$l]); }
if ($filter_type)   { $asset_where[] = "a.asset_type=?";   $ap[]=$filter_type; }
if ($filter_status) { $asset_where[] = "a.status=?";       $ap[]=$filter_status; }
$aw = implode(' AND ',$asset_where);

$lic_where = ['1=1']; $lp = [];
if ($search_license) { $lic_where[] = "(software_name LIKE ? OR vendor LIKE ?)"; $ll="%$search_license%"; $lp=array_merge($lp,[$ll,$ll]); }
if ($filter_vendor)  { $lic_where[] = "vendor=?";        $lp[]=$filter_vendor; }
if ($filter_lstatus) { $lic_where[] = "status=?";        $lp[]=$filter_lstatus; }
if ($filter_ltype)   { $lic_where[] = "license_type=?";  $lp[]=$filter_ltype; }
$lw = implode(' AND ',$lic_where);

$stmt = $db->prepare("SELECT a.*, u.username as assigned_username FROM it_assets a LEFT JOIN users u ON a.assigned_to=u.id WHERE $aw ORDER BY a.asset_type, a.asset_name");
$stmt->execute($ap);
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$all_assets_raw = $db->query("SELECT a.*, u.username as assigned_username FROM it_assets a LEFT JOIN users u ON a.assigned_to=u.id ORDER BY a.asset_type")->fetchAll(PDO::FETCH_ASSOC);

$lstmt = $db->prepare("SELECT * FROM it_licenses WHERE $lw ORDER BY FIELD(status,'active','expired','cancelled'), expiry_date IS NULL, expiry_date ASC");
$lstmt->execute($lp);
$licenses = $lstmt->fetchAll(PDO::FETCH_ASSOC);

$all_licenses_raw = $db->query("SELECT * FROM it_licenses")->fetchAll(PDO::FETCH_ASSOC);
$all_users        = $db->query("SELECT id, username, department FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

$now = time();
$total_assets    = count($all_assets_raw);
$assigned_count  = count(array_filter($all_assets_raw, fn($a)=>$a['status']==='assigned'));
$available_count = count(array_filter($all_assets_raw, fn($a)=>$a['status']==='available'));
$maintenance_count=count(array_filter($all_assets_raw, fn($a)=>$a['status']==='maintenance'));
$total_licenses  = count($all_licenses_raw);
$active_lic      = count(array_filter($all_licenses_raw, fn($l)=>$l['status']==='active'));
$expiring_soon   = count(array_filter($all_licenses_raw, fn($l)=>$l['expiry_date'] && strtotime($l['expiry_date'])>=$now && strtotime($l['expiry_date'])<=strtotime('+30 days') && $l['status']==='active'));
$expired_count   = count(array_filter($all_licenses_raw, fn($l)=>$l['status']==='expired'));
$total_lic_cost  = array_sum(array_column($all_licenses_raw, 'cost'));

$asset_types_list  = $db->query("SELECT DISTINCT asset_type FROM it_assets ORDER BY asset_type")->fetchAll(PDO::FETCH_COLUMN);
$vendor_list       = $db->query("SELECT DISTINCT vendor FROM it_licenses WHERE vendor IS NOT NULL AND vendor != '' ORDER BY vendor")->fetchAll(PDO::FETCH_COLUMN);
$asset_types_form  = ['Computer','Laptop','Monitor','Printer','Router','Switch','Server','Phone','Tablet','Other'];
$asset_statuses    = ['available','assigned','maintenance','retired'];
$license_types     = ['perpetual','subscription','trial','open_source'];
$license_statuses  = ['active','expired','cancelled'];

$view = $_GET['view'] ?? 'assets';
$msg  = $_GET['msg']  ?? '';

$type_icons = ['Computer'=>'🖥','Laptop'=>'💻','Monitor'=>'🖵','Printer'=>'🖨','Router'=>'📡','Switch'=>'🔀','Server'=>'🗄','Phone'=>'📱','Tablet'=>'📱','Other'=>'📦'];
$vendor_colors = ['#6366f1','#0ea5e9','#f59e0b','#22c55e','#ec4899','#ef4444','#8b5cf6','#14b8a6'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IT Department — KConsulting Hub</title>
<link rel="stylesheet" href="../css/main.css">
<style>
/* ── PAGE HERO ── */
.it-hero { background:linear-gradient(135deg,#0ea5e9 0%,#6366f1 100%); border-radius:14px; padding:1.75rem 2rem; color:#fff; margin-bottom:1.5rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.it-hero h2 { margin:0 0 .2rem; font-size:1.5rem; font-weight:800; }
.it-hero p  { margin:0; font-size:.875rem; opacity:.85; }
.it-hero-actions { display:flex; gap:.6rem; flex-wrap:wrap; }
.hero-btn { padding:.55rem 1.1rem; border-radius:8px; font-size:.85rem; font-weight:600; cursor:pointer; border:2px solid rgba(255,255,255,.4); background:rgba(255,255,255,.15); color:#fff; text-decoration:none; transition:all .2s; }
.hero-btn:hover { background:rgba(255,255,255,.3); border-color:rgba(255,255,255,.7); }
.hero-btn.primary { background:#fff; color:#6366f1; border-color:#fff; }
.hero-btn.primary:hover { background:#f0f9ff; }

/* ── STATS ── */
.it-stats { display:grid; grid-template-columns:repeat(8,1fr); gap:.85rem; margin-bottom:1.5rem; }
@media(max-width:1100px){ .it-stats{ grid-template-columns:repeat(4,1fr); } }
@media(max-width:600px) { .it-stats{ grid-template-columns:repeat(2,1fr); } }
.it-stat { background:#fff; border-radius:10px; padding:.9rem 1rem; box-shadow:0 1px 4px rgba(0,0,0,.07); text-align:center; }
.it-stat .n { font-size:1.5rem; font-weight:800; color:#111827; line-height:1; }
.it-stat .l { font-size:.72rem; color:#6b7280; margin-top:.2rem; }

/* ── TAB BAR ── */
.it-tabs { display:flex; gap:0; background:#fff; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,.07); overflow:hidden; margin-bottom:1.5rem; }
.it-tab  { flex:1; padding:.85rem 1rem; text-decoration:none; color:#6b7280; text-align:center; font-size:.875rem; font-weight:600; border-right:1px solid #f3f4f6; transition:all .2s; }
.it-tab:last-child { border-right:none; }
.it-tab.active { background:#0ea5e9; color:#fff; }
.it-tab:hover:not(.active) { background:#f8faff; color:#0ea5e9; }

/* ── CONTROLS ── */
.controls-row { display:flex; gap:.6rem; align-items:center; background:#fff; border-radius:10px; padding:.75rem 1rem; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:1.25rem; flex-wrap:wrap; }
.controls-row input[type=text] { flex:1; min-width:180px; padding:.5rem .85rem; border:1px solid #e5e7eb; border-radius:8px; font-size:.875rem; }
.controls-row input[type=text]:focus { outline:none; border-color:#0ea5e9; box-shadow:0 0 0 3px rgba(14,165,233,.1); }
.controls-row select { padding:.5rem .75rem; border:1px solid #e5e7eb; border-radius:8px; font-size:.875rem; background:#fafafa; cursor:pointer; }
.controls-row select:focus { outline:none; border-color:#0ea5e9; }

/* ── ASSET CARDS ── */
.asset-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1rem; }
.asset-card { background:#fff; border-radius:12px; border:1px solid #f3f4f6; box-shadow:0 2px 6px rgba(0,0,0,.06); overflow:hidden; transition:transform .2s,box-shadow .2s; }
.asset-card:hover { transform:translateY(-3px); box-shadow:0 6px 16px rgba(0,0,0,.1); }
.asset-card-top { padding:1rem 1.25rem .75rem; display:flex; align-items:flex-start; gap:.85rem; }
.asset-icon { width:44px; height:44px; border-radius:10px; background:linear-gradient(135deg,#0ea5e9,#6366f1); display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
.asset-info { flex:1; min-width:0; }
.asset-name { font-size:.95rem; font-weight:700; color:#111827; margin:0 0 .2rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.asset-model { font-size:.78rem; color:#6b7280; }
.asset-card-body { padding:.5rem 1.25rem .85rem; display:grid; grid-template-columns:1fr 1fr; gap:.35rem .75rem; }
.ac-row { font-size:.78rem; color:#374151; }
.ac-label { color:#9ca3af; font-size:.7rem; display:block; }
.asset-card-foot { padding:.6rem 1.25rem; border-top:1px solid #f9fafb; background:#fafafa; display:flex; align-items:center; justify-content:space-between; }

/* ── STATUS / PRIORITY BADGES ── */
.badge { display:inline-flex; align-items:center; padding:.2rem .6rem; border-radius:20px; font-size:.72rem; font-weight:700; white-space:nowrap; }
.b-available   { background:#dcfce7; color:#166534; }
.b-assigned    { background:#dbeafe; color:#1e40af; }
.b-maintenance { background:#fef9c3; color:#854d0e; }
.b-retired     { background:#f3f4f6; color:#6b7280; }
.b-active      { background:#dcfce7; color:#166534; }
.b-expired     { background:#fee2e2; color:#991b1b; }
.b-cancelled   { background:#f3f4f6; color:#6b7280; }
.b-expiring    { background:#fef9c3; color:#854d0e; }

/* Warranty chip */
.w-ok      { color:#6b7280; }
.w-expired { color:#ef4444; font-weight:600; }
.w-soon    { color:#f59e0b; font-weight:600; }

/* ── LICENSE CARDS ── */
.lic-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:1rem; }
.lic-card { background:#fff; border-radius:12px; border:1px solid #f3f4f6; box-shadow:0 2px 6px rgba(0,0,0,.06); overflow:hidden; transition:transform .2s,box-shadow .2s; }
.lic-card:hover { transform:translateY(-3px); box-shadow:0 6px 16px rgba(0,0,0,.1); }
.lic-card.expired  { border-color:#fca5a5; }
.lic-card.expiring { border-color:#fde68a; }
.lic-top { padding:1rem 1.25rem .75rem; display:flex; align-items:center; gap:.9rem; }
.lic-logo { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; font-weight:800; color:#fff; flex-shrink:0; }
.lic-name { font-size:.95rem; font-weight:700; color:#111827; margin:0 0 .15rem; }
.lic-vendor { font-size:.78rem; color:#6b7280; }
.lic-body { padding:.25rem 1.25rem .9rem; }
.seat-section { margin-bottom:.75rem; }
.seat-header { display:flex; justify-content:space-between; font-size:.78rem; margin-bottom:.35rem; }
.seat-bar { height:7px; background:#f3f4f6; border-radius:10px; overflow:hidden; }
.seat-fill { height:100%; border-radius:10px; background:#0ea5e9; transition:width .5s; }
.seat-fill.full    { background:#ef4444; }
.seat-fill.high    { background:#f59e0b; }
.lic-meta { display:grid; grid-template-columns:1fr 1fr; gap:.3rem .75rem; }
.lm-item { font-size:.78rem; color:#374151; }
.lm-label{ color:#9ca3af; font-size:.7rem; display:block; }
.lic-foot { padding:.6rem 1.25rem; border-top:1px solid #f9fafb; background:#fafafa; display:flex; align-items:center; justify-content:space-between; }

/* ── FORMS ── */
.form-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); overflow:hidden; }
.form-card-head { background:linear-gradient(135deg,#0ea5e9,#6366f1); padding:1.25rem 1.75rem; color:#fff; }
.form-card-head h3 { margin:0 0 .15rem; font-size:1.1rem; font-weight:700; }
.form-card-head p  { margin:0; font-size:.8rem; opacity:.85; }
.form-card-body { padding:1.75rem; }
.form-section h4 { font-size:.88rem; font-weight:700; color:#111827; margin:0 0 .9rem; padding-bottom:.45rem; border-bottom:2px solid #f3f4f6; }
.fg { margin-bottom:.9rem; }
.fg label { display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
.fg label span { color:#ef4444; }
.fg input, .fg select, .fg textarea {
    width:100%; padding:.55rem .85rem; border:1px solid #e5e7eb; border-radius:8px;
    font-size:.875rem; color:#111827; box-sizing:border-box; transition:border .15s;
}
.fg input:focus,.fg select:focus,.fg textarea:focus { outline:none; border-color:#0ea5e9; box-shadow:0 0 0 3px rgba(14,165,233,.12); }
.form-2col { display:grid; grid-template-columns:1fr 1fr; gap:.9rem; }
@media(max-width:600px){ .form-2col{ grid-template-columns:1fr; } }
.form-actions { display:flex; gap:.75rem; align-items:center; margin-top:1.25rem; }

/* ── MODAL ── */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; align-items:center; justify-content:center; padding:1rem; }
.modal-overlay.open { display:flex; }
.modal-box { background:#fff; border-radius:14px; width:90%; max-width:620px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.25); }
.modal-head { background:linear-gradient(135deg,#0ea5e9,#6366f1); padding:1.25rem 1.5rem; color:#fff; border-radius:14px 14px 0 0; display:flex; align-items:center; justify-content:space-between; }
.modal-head h3 { margin:0; font-size:1rem; font-weight:700; }
.modal-close { background:rgba(255,255,255,.2); border:none; color:#fff; width:28px; height:28px; border-radius:50%; cursor:pointer; font-size:1rem; display:flex; align-items:center; justify-content:center; }
.modal-body { padding:1.5rem; }

/* Flash */
.flash { padding:.75rem 1.1rem; border-radius:8px; margin-bottom:1.1rem; font-size:.875rem; font-weight:500; }
.flash-success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }

/* Empty */
.empty-box { text-align:center; padding:3rem 1.5rem; background:#fff; border-radius:12px; border:2px dashed #e5e7eb; }
.empty-box .emoji { font-size:3rem; margin-bottom:.75rem; }
.empty-box h3 { color:#374151; margin-bottom:.4rem; }
.empty-box p  { color:#9ca3af; margin-bottom:1.25rem; font-size:.875rem; }

/* Btn variants */
.btn-xs { padding:.28rem .6rem; font-size:.73rem; border-radius:6px; border:1px solid #e5e7eb; background:#fafafa; color:#374151; cursor:pointer; transition:all .15s; white-space:nowrap; }
.btn-xs:hover { background:#0ea5e9; color:#fff; border-color:#0ea5e9; }

@media(max-width:600px){ .asset-grid,.lic-grid{ grid-template-columns:1fr; } }
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

    <!-- Flash messages -->
    <?php $flash_map = ['asset_added'=>'✅ Asset added.','asset_updated'=>'✅ Asset updated.','license_added'=>'✅ License added.','license_updated'=>'✅ License updated.']; ?>
    <?php if ($msg && isset($flash_map[$msg])): ?>
    <div class="flash flash-success" id="flashMsg"><?= $flash_map[$msg] ?></div>
    <?php endif; ?>

    <!-- Hero -->
    <div class="it-hero">
        <div>
            <h2>💻 IT Department</h2>
            <p>Asset inventory, software licenses &amp; infrastructure management</p>
        </div>
        <?php if (in_array($role, ['admin','manager'])): ?>
        <div class="it-hero-actions">
            <a href="?view=add_asset"   class="hero-btn">➕ Add Asset</a>
            <a href="?view=add_license" class="hero-btn primary">🔑 Add License</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="it-stats">
        <div class="it-stat"><div class="n"><?= $total_assets ?></div><div class="l">Total Assets</div></div>
        <div class="it-stat"><div class="n" style="color:#3b82f6"><?= $assigned_count ?></div><div class="l">Assigned</div></div>
        <div class="it-stat"><div class="n" style="color:#22c55e"><?= $available_count ?></div><div class="l">Available</div></div>
        <div class="it-stat"><div class="n" style="color:<?= $maintenance_count>0?'#f59e0b':'#22c55e' ?>"><?= $maintenance_count ?></div><div class="l">Maintenance</div></div>
        <div class="it-stat"><div class="n"><?= $total_licenses ?></div><div class="l">Licenses</div></div>
        <div class="it-stat"><div class="n" style="color:#22c55e"><?= $active_lic ?></div><div class="l">Active Lic.</div></div>
        <div class="it-stat"><div class="n" style="color:<?= $expiring_soon>0?'#f59e0b':'#22c55e' ?>"><?= $expiring_soon ?></div><div class="l">Expiring (30d)</div></div>
        <div class="it-stat"><div class="n" style="color:<?= $expired_count>0?'#ef4444':'#22c55e' ?>"><?= $expired_count ?></div><div class="l">Expired Lic.</div></div>
    </div>

    <!-- Tabs -->
    <div class="it-tabs">
        <a href="?view=assets"      class="it-tab <?= $view==='assets'     ?'active':'' ?>">🖥️ Assets (<?= $total_assets ?>)</a>
        <a href="?view=licenses"    class="it-tab <?= $view==='licenses'   ?'active':'' ?>">🔑 Licenses (<?= $total_licenses ?>)</a>
        <?php if (in_array($role, ['admin','manager'])): ?>
        <a href="?view=add_asset"   class="it-tab <?= $view==='add_asset'  ?'active':'' ?>">➕ Add Asset</a>
        <a href="?view=add_license" class="it-tab <?= $view==='add_license'?'active':'' ?>">➕ Add License</a>
        <?php endif; ?>
    </div>

    <!-- ══ ASSETS VIEW ══ -->
    <?php if ($view === 'assets'): ?>

    <form method="GET" id="assetFilterForm">
        <input type="hidden" name="view" value="assets">
        <div class="controls-row">
            <input type="text" name="search_asset" value="<?= Security::escapeHTML($search_asset) ?>" placeholder="🔍  Search assets, brand, location…" oninput="clearTimeout(window._st);window._st=setTimeout(()=>this.form.submit(),400)">
            <select name="filter_type" onchange="this.form.submit()">
                <option value="">All Types</option>
                <?php foreach ($asset_types_list as $t): ?>
                <option value="<?= $t ?>" <?= $filter_type===$t?'selected':'' ?>><?= $type_icons[$t]??'📦' ?> <?= $t ?></option>
                <?php endforeach; ?>
            </select>
            <select name="filter_status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach (['available'=>'✅ Available','assigned'=>'🔵 Assigned','maintenance'=>'🔧 Maintenance','retired'=>'⬛ Retired'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $filter_status===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($search_asset||$filter_type||$filter_status): ?>
            <a href="?view=assets" class="btn btn-secondary btn-small">✕ Clear</a>
            <?php endif; ?>
            <span style="margin-left:auto;font-size:.8rem;color:#9ca3af;"><?= count($assets) ?> result<?= count($assets)!=1?'s':'' ?></span>
        </div>
    </form>

    <?php if (empty($assets)): ?>
    <div class="empty-box">
        <div class="emoji">🖥️</div>
        <h3>No assets found</h3>
        <p><?= ($search_asset||$filter_type||$filter_status) ? 'Try adjusting your filters.' : 'No assets have been added yet.' ?></p>
        <?php if (in_array($role, ['admin','manager'])): ?><a href="?view=add_asset" class="btn" style="background:#0ea5e9;color:#fff;">Add First Asset</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="asset-grid">
        <?php foreach ($assets as $a):
            $ws   = $a['warranty_expiry'] ? strtotime($a['warranty_expiry']) : null;
            $wDaysLeft = $ws ? (int)(($ws - $now) / 86400) : null;
            $wClass = !$ws ? 'w-ok' : ($ws < $now ? 'w-expired' : ($wDaysLeft <= 90 ? 'w-soon' : 'w-ok'));
            $wText  = !$ws ? '—' : ($ws < $now ? 'Expired '.date('M Y',$ws) : ($wDaysLeft<=90 ? "Exp. in {$wDaysLeft}d" : date('M j, Y',$ws)));
            $icon   = $type_icons[$a['asset_type']] ?? '📦';
        ?>
        <div class="asset-card">
            <div class="asset-card-top">
                <div class="asset-icon"><?= $icon ?></div>
                <div class="asset-info">
                    <div class="asset-name"><?= Security::escapeHTML($a['asset_name']) ?></div>
                    <div class="asset-model"><?= Security::escapeHTML(trim($a['brand'].' '.$a['model'])) ?></div>
                </div>
                <span class="badge b-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span>
            </div>
            <div class="asset-card-body">
                <div class="ac-row">
                    <span class="ac-label">Type</span>
                    <?= Security::escapeHTML($a['asset_type']) ?>
                </div>
                <div class="ac-row">
                    <span class="ac-label">Assigned To</span>
                    <?= $a['assigned_username'] ? Security::escapeHTML($a['assigned_username']) : '<span style="color:#9ca3af;">Unassigned</span>' ?>
                </div>
                <div class="ac-row">
                    <span class="ac-label">Serial No.</span>
                    <span style="font-family:monospace;font-size:.72rem;"><?= Security::escapeHTML($a['serial_number']??'—') ?></span>
                </div>
                <div class="ac-row">
                    <span class="ac-label">Location</span>
                    <?= Security::escapeHTML($a['location']??'—') ?>
                </div>
                <div class="ac-row" style="grid-column:1/-1;">
                    <span class="ac-label">Warranty</span>
                    <span class="<?= $wClass ?>"><?= $wText ?></span>
                </div>
                <?php if (!empty($a['notes'])): ?>
                <div class="ac-row" style="grid-column:1/-1;margin-top:.2rem;">
                    <span class="ac-label">Notes</span>
                    <span style="color:#6b7280;font-size:.75rem;"><?= Security::escapeHTML(mb_strimwidth($a['notes'],0,80,'…')) ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="asset-card-foot">
                <span style="font-size:.75rem;color:#9ca3af;"><?= $a['purchase_date'] ? date('M Y', strtotime($a['purchase_date'])) : '' ?></span>
                <?php if (in_array($role, ['admin','manager'])): ?>
                <button class="btn-xs" onclick='openEditAsset(<?= htmlspecialchars(json_encode($a),ENT_QUOTES) ?>)'>✏️ Edit</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ══ LICENSES VIEW ══ -->
    <?php elseif ($view === 'licenses'): ?>

    <form method="GET" id="licFilterForm">
        <input type="hidden" name="view" value="licenses">
        <div class="controls-row">
            <input type="text" name="search_license" value="<?= Security::escapeHTML($search_license) ?>" placeholder="🔍  Search software, vendor…" oninput="clearTimeout(window._lst);window._lst=setTimeout(()=>this.form.submit(),400)">
            <select name="filter_vendor" onchange="this.form.submit()">
                <option value="">All Vendors</option>
                <?php foreach ($vendor_list as $v): ?>
                <option value="<?= Security::escapeHTML($v) ?>" <?= $filter_vendor===$v?'selected':'' ?>><?= Security::escapeHTML($v) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="filter_ltype" onchange="this.form.submit()">
                <option value="">All Types</option>
                <?php foreach ($license_types as $t): ?>
                <option value="<?= $t ?>" <?= $filter_ltype===$t?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$t)) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="filter_lstatus" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach (['active'=>'✅ Active','expired'=>'❌ Expired','cancelled'=>'⬛ Cancelled'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $filter_lstatus===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($search_license||$filter_vendor||$filter_lstatus||$filter_ltype): ?>
            <a href="?view=licenses" class="btn btn-secondary btn-small">✕ Clear</a>
            <?php endif; ?>
            <span style="margin-left:auto;font-size:.8rem;color:#9ca3af;"><?= count($licenses) ?> result<?= count($licenses)!=1?'s':'' ?></span>
        </div>
    </form>

    <?php if (empty($licenses)): ?>
    <div class="empty-box">
        <div class="emoji">🔑</div>
        <h3>No licenses found</h3>
        <p><?= ($search_license||$filter_vendor||$filter_lstatus||$filter_ltype) ? 'Try adjusting your filters.' : 'Start tracking your software licenses here.' ?></p>
        <?php if (in_array($role, ['admin','manager'])): ?><a href="?view=add_license" class="btn" style="background:#0ea5e9;color:#fff;">Add First License</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="lic-grid">
        <?php foreach ($licenses as $l):
            $expTs  = $l['expiry_date'] ? strtotime($l['expiry_date']) : null;
            $expDays= $expTs ? (int)(($expTs - $now) / 86400) : null;
            $isExpiringSoon = $expTs && $expTs >= $now && $expTs <= strtotime('+30 days') && $l['status']==='active';
            $isExpired = $l['status']==='expired' || ($expTs && $expTs < $now);
            $cardClass = $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring' : '');
            $badgeClass= $isExpired ? 'b-expired' : ($isExpiringSoon ? 'b-expiring' : 'b-'.$l['status']);
            $badgeText = $isExpired ? 'Expired' : ($isExpiringSoon ? "Expiring in {$expDays}d" : ucfirst($l['status']));
            $pct = $l['seats'] > 0 ? min(100, round($l['seats_used']/$l['seats']*100)) : 0;
            $barClass = $pct >= 100 ? 'full' : ($pct >= 80 ? 'high' : '');
            $logoColor = $vendor_colors[crc32($l['software_name']) % count($vendor_colors)];
            $logoChar  = strtoupper(mb_substr($l['software_name'], 0, 1));
            $expiryText = !$expTs ? 'Perpetual' : ($isExpired ? 'Expired '.date('M j, Y',$expTs) : date('M j, Y',$expTs));
        ?>
        <div class="lic-card <?= $cardClass ?>">
            <div class="lic-top">
                <div class="lic-logo" style="background:<?= $logoColor ?>"><?= $logoChar ?></div>
                <div style="flex:1;min-width:0;">
                    <div class="lic-name"><?= Security::escapeHTML($l['software_name']) ?></div>
                    <div class="lic-vendor"><?= Security::escapeHTML($l['vendor']??'—') ?> · <?= ucfirst(str_replace('_',' ',$l['license_type'])) ?></div>
                </div>
                <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
            </div>
            <div class="lic-body">
                <div class="seat-section">
                    <div class="seat-header">
                        <span style="font-weight:600;color:#374151;">Seats used</span>
                        <span style="font-weight:700;color:<?= $pct>=100?'#ef4444':($pct>=80?'#f59e0b':'#374151') ?>"><?= $l['seats_used'] ?> / <?= $l['seats'] ?> &nbsp;(<?= $pct ?>%)</span>
                    </div>
                    <div class="seat-bar"><div class="seat-fill <?= $barClass ?>" style="width:<?= $pct ?>%"></div></div>
                </div>
                <div class="lic-meta">
                    <div class="lm-item">
                        <span class="lm-label">Expiry</span>
                        <span style="color:<?= $isExpired?'#ef4444':($isExpiringSoon?'#f59e0b':'#374151') ?>;font-weight:<?= ($isExpired||$isExpiringSoon)?'700':'400' ?>;"><?= $expiryText ?></span>
                    </div>
                    <div class="lm-item">
                        <span class="lm-label">Annual Cost</span>
                        <?= $l['cost'] ? 'R '.number_format($l['cost'],2) : '—' ?>
                    </div>
                    <?php if (!empty($l['notes'])): ?>
                    <div class="lm-item" style="grid-column:1/-1;margin-top:.2rem;">
                        <span class="lm-label">Notes</span>
                        <span style="color:#6b7280;font-size:.75rem;"><?= Security::escapeHTML(mb_strimwidth($l['notes'],0,90,'…')) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (in_array($role, ['admin','manager'])): ?>
            <div class="lic-foot">
                <span style="font-size:.75rem;color:#9ca3af;"><?= $l['purchase_date'] ? 'Purchased '.date('M Y',strtotime($l['purchase_date'])) : '' ?></span>
                <button class="btn-xs" onclick='openEditLicense(<?= htmlspecialchars(json_encode($l),ENT_QUOTES) ?>)'>✏️ Edit</button>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ══ ADD ASSET FORM ══ -->
    <?php elseif ($view === 'add_asset' && in_array($role, ['admin','manager'])): ?>
    <div class="form-card">
        <div class="form-card-head">
            <h3>🖥️ Add New Asset</h3>
            <p>Register a new hardware asset to the IT inventory</p>
        </div>
        <div class="form-card-body">
            <form method="post">
                <?= Security::getCSRFTokenField() ?>

                <div class="form-section">
                    <h4>🔧 Hardware Details</h4>
                    <div class="form-2col">
                        <div class="fg">
                            <label>Asset Name <span>*</span></label>
                            <input type="text" name="asset_name" required placeholder="e.g. Dev Laptop #4">
                        </div>
                        <div class="fg">
                            <label>Asset Type <span>*</span></label>
                            <select name="asset_type" required>
                                <?php foreach ($asset_types_form as $t): ?>
                                <option value="<?= $t ?>"><?= $type_icons[$t]??'📦' ?> <?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg">
                            <label>Brand</label>
                            <input type="text" name="brand" placeholder="e.g. Dell, HP, Apple">
                        </div>
                        <div class="fg">
                            <label>Model</label>
                            <input type="text" name="model" placeholder="e.g. XPS 15 9520">
                        </div>
                        <div class="fg" style="grid-column:1/-1;">
                            <label>Serial Number</label>
                            <input type="text" name="serial_number" placeholder="e.g. DL-XPS-00999" style="font-family:monospace;">
                        </div>
                    </div>
                </div>

                <div class="form-section" style="margin-top:1.4rem;">
                    <h4>📍 Assignment &amp; Location</h4>
                    <div class="form-2col">
                        <div class="fg">
                            <label>Status <span>*</span></label>
                            <select name="status" required>
                                <?php foreach ($asset_statuses as $s): ?>
                                <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg">
                            <label>Assigned To</label>
                            <select name="assigned_to">
                                <option value="">— Unassigned —</option>
                                <?php foreach ($all_users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= Security::escapeHTML($u['username']) ?><?= $u['department'] ? ' ('.$u['department'].')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg" style="grid-column:1/-1;">
                            <label>Location</label>
                            <input type="text" name="location" placeholder="e.g. Office Floor 2, Desk 7">
                        </div>
                    </div>
                </div>

                <div class="form-section" style="margin-top:1.4rem;">
                    <h4>📅 Dates</h4>
                    <div class="form-2col">
                        <div class="fg">
                            <label>Purchase Date</label>
                            <input type="date" name="purchase_date">
                        </div>
                        <div class="fg">
                            <label>Warranty Expiry</label>
                            <input type="date" name="warranty_expiry">
                        </div>
                    </div>
                </div>

                <div class="form-section" style="margin-top:1.4rem;">
                    <h4>📝 Notes</h4>
                    <div class="fg">
                        <textarea name="notes" rows="3" placeholder="Any additional notes about this asset…"></textarea>
                    </div>
                </div>

                <div class="form-actions" style="margin-top:1.5rem;">
                    <button type="submit" name="add_asset" class="btn" style="background:#0ea5e9;color:#fff;border-color:#0ea5e9;padding:.65rem 1.5rem;">💾 Save Asset</button>
                    <a href="?view=assets" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ══ ADD LICENSE FORM ══ -->
    <?php elseif ($view === 'add_license' && in_array($role, ['admin','manager'])): ?>
    <div class="form-card">
        <div class="form-card-head">
            <h3>🔑 Add Software License</h3>
            <p>Track a new software license in the IT registry</p>
        </div>
        <div class="form-card-body">
            <form method="post">
                <?= Security::getCSRFTokenField() ?>

                <div class="form-section">
                    <h4>📦 Software Details</h4>
                    <div class="form-2col">
                        <div class="fg">
                            <label>Software Name <span>*</span></label>
                            <input type="text" name="software_name" required placeholder="e.g. Microsoft 365">
                        </div>
                        <div class="fg">
                            <label>Vendor</label>
                            <input type="text" name="vendor" placeholder="e.g. Microsoft">
                        </div>
                        <div class="fg">
                            <label>License Type <span>*</span></label>
                            <select name="license_type" required>
                                <?php foreach ($license_types as $t): ?>
                                <option value="<?= $t ?>"><?= ucfirst(str_replace('_',' ',$t)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg">
                            <label>Status <span>*</span></label>
                            <select name="status" required>
                                <?php foreach ($license_statuses as $s): ?>
                                <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg" style="grid-column:1/-1;">
                            <label>License Key</label>
                            <input type="text" name="license_key" placeholder="XXXXX-XXXXX-XXXXX-XXXXX" style="font-family:monospace;">
                        </div>
                    </div>
                </div>

                <div class="form-section" style="margin-top:1.4rem;">
                    <h4>👥 Seat Usage</h4>
                    <div class="form-2col">
                        <div class="fg">
                            <label>Total Seats</label>
                            <input type="number" name="seats" value="1" min="1">
                        </div>
                        <div class="fg">
                            <label>Seats Currently Used</label>
                            <input type="number" name="seats_used" value="0" min="0">
                        </div>
                    </div>
                </div>

                <div class="form-section" style="margin-top:1.4rem;">
                    <h4>📅 Dates &amp; Cost</h4>
                    <div class="form-2col">
                        <div class="fg">
                            <label>Purchase Date</label>
                            <input type="date" name="purchase_date">
                        </div>
                        <div class="fg">
                            <label>Expiry Date <span style="color:#9ca3af;font-weight:400;">(leave blank for perpetual)</span></label>
                            <input type="date" name="expiry_date">
                        </div>
                        <div class="fg" style="grid-column:1/-1;">
                            <label>Annual Cost (R)</label>
                            <input type="number" name="cost" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="form-section" style="margin-top:1.4rem;">
                    <h4>📝 Notes</h4>
                    <div class="fg">
                        <textarea name="notes" rows="3" placeholder="Usage notes, renewal reminders, contact info…"></textarea>
                    </div>
                </div>

                <div class="form-actions" style="margin-top:1.5rem;">
                    <button type="submit" name="add_license" class="btn" style="background:#6366f1;color:#fff;border-color:#6366f1;padding:.65rem 1.5rem;">💾 Save License</button>
                    <a href="?view=licenses" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.main-content -->

<!-- ══ EDIT ASSET MODAL ══ -->
<?php if (in_array($role, ['admin','manager'])): ?>
<div id="editAssetModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-head">
            <h3>✏️ Edit Asset</h3>
            <button class="modal-close" onclick="closeModal('editAssetModal')">✕</button>
        </div>
        <div class="modal-body">
            <form method="post" id="editAssetForm">
                <?= Security::getCSRFTokenField() ?>
                <input type="hidden" name="asset_id" id="ea_id">
                <div class="form-section">
                    <h4 style="font-size:.82rem;font-weight:700;color:#111827;margin:0 0 .85rem;padding-bottom:.4rem;border-bottom:2px solid #f3f4f6;">🔧 Hardware Details</h4>
                    <div class="form-2col">
                        <div class="fg"><label>Asset Name <span>*</span></label><input type="text" name="asset_name" id="ea_name" required></div>
                        <div class="fg"><label>Type <span>*</span></label>
                            <select name="asset_type" id="ea_type">
                                <?php foreach ($asset_types_form as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Brand</label><input type="text" name="brand" id="ea_brand"></div>
                        <div class="fg"><label>Model</label><input type="text" name="model" id="ea_model"></div>
                        <div class="fg" style="grid-column:1/-1;"><label>Serial No.</label><input type="text" name="serial_number" id="ea_serial" style="font-family:monospace;"></div>
                    </div>
                </div>
                <div class="form-section" style="margin-top:1.1rem;">
                    <h4 style="font-size:.82rem;font-weight:700;color:#111827;margin:0 0 .85rem;padding-bottom:.4rem;border-bottom:2px solid #f3f4f6;">📍 Assignment &amp; Location</h4>
                    <div class="form-2col">
                        <div class="fg"><label>Status</label>
                            <select name="status" id="ea_status">
                                <?php foreach ($asset_statuses as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Assigned To</label>
                            <select name="assigned_to" id="ea_assigned">
                                <option value="">— Unassigned —</option>
                                <?php foreach ($all_users as $u): ?><option value="<?= $u['id'] ?>"><?= Security::escapeHTML($u['username']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg" style="grid-column:1/-1;"><label>Location</label><input type="text" name="location" id="ea_location"></div>
                    </div>
                </div>
                <div class="form-section" style="margin-top:1.1rem;">
                    <h4 style="font-size:.82rem;font-weight:700;color:#111827;margin:0 0 .85rem;padding-bottom:.4rem;border-bottom:2px solid #f3f4f6;">📅 Dates</h4>
                    <div class="form-2col">
                        <div class="fg"><label>Purchase Date</label><input type="date" name="purchase_date" id="ea_purchase"></div>
                        <div class="fg"><label>Warranty Expiry</label><input type="date" name="warranty_expiry" id="ea_warranty"></div>
                    </div>
                </div>
                <div class="fg" style="margin-top:1rem;"><label style="font-size:.82rem;font-weight:600;color:#374151;display:block;margin-bottom:.35rem;">📝 Notes</label><textarea name="notes" id="ea_notes" rows="2" style="width:100%;padding:.55rem .85rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.875rem;box-sizing:border-box;"></textarea></div>
                <div class="form-actions" style="margin-top:1.25rem;">
                    <button type="submit" name="update_asset" class="btn" style="background:#0ea5e9;color:#fff;border-color:#0ea5e9;">💾 Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editAssetModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ EDIT LICENSE MODAL ══ -->
<div id="editLicModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-head">
            <h3>✏️ Edit License</h3>
            <button class="modal-close" onclick="closeModal('editLicModal')">✕</button>
        </div>
        <div class="modal-body">
            <form method="post" id="editLicForm">
                <?= Security::getCSRFTokenField() ?>
                <input type="hidden" name="license_id" id="el_id">
                <div class="form-section">
                    <h4 style="font-size:.82rem;font-weight:700;color:#111827;margin:0 0 .85rem;padding-bottom:.4rem;border-bottom:2px solid #f3f4f6;">📦 Software Details</h4>
                    <div class="form-2col">
                        <div class="fg"><label>Software Name <span>*</span></label><input type="text" name="software_name" id="el_name" required></div>
                        <div class="fg"><label>Vendor</label><input type="text" name="vendor" id="el_vendor"></div>
                        <div class="fg"><label>License Type</label>
                            <select name="license_type" id="el_type">
                                <?php foreach ($license_types as $t): ?><option value="<?= $t ?>"><?= ucfirst(str_replace('_',' ',$t)) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg"><label>Status</label>
                            <select name="status" id="el_status">
                                <?php foreach ($license_statuses as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-section" style="margin-top:1.1rem;">
                    <h4 style="font-size:.82rem;font-weight:700;color:#111827;margin:0 0 .85rem;padding-bottom:.4rem;border-bottom:2px solid #f3f4f6;">👥 Seat Usage</h4>
                    <div class="form-2col">
                        <div class="fg"><label>Total Seats</label><input type="number" name="seats" id="el_seats" min="1"></div>
                        <div class="fg"><label>Seats Used</label><input type="number" name="seats_used" id="el_used" min="0"></div>
                    </div>
                </div>
                <div class="form-section" style="margin-top:1.1rem;">
                    <h4 style="font-size:.82rem;font-weight:700;color:#111827;margin:0 0 .85rem;padding-bottom:.4rem;border-bottom:2px solid #f3f4f6;">📅 Dates &amp; Cost</h4>
                    <div class="form-2col">
                        <div class="fg"><label>Expiry Date <span style="color:#9ca3af;font-weight:400;">(blank = perpetual)</span></label><input type="date" name="expiry_date" id="el_expiry"></div>
                        <div class="fg"><label>Annual Cost (R)</label><input type="number" name="cost" id="el_cost" step="0.01" min="0"></div>
                    </div>
                </div>
                <div class="fg" style="margin-top:1rem;"><label style="font-size:.82rem;font-weight:600;color:#374151;display:block;margin-bottom:.35rem;">📝 Notes</label><textarea name="notes" id="el_notes" rows="2" style="width:100%;padding:.55rem .85rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.875rem;box-sizing:border-box;"></textarea></div>
                <div class="form-actions" style="margin-top:1.25rem;">
                    <button type="submit" name="update_license" class="btn" style="background:#6366f1;color:#fff;border-color:#6366f1;">💾 Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editLicModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="../js/notification.js"></script>
<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); }));

function openEditAsset(a) {
    document.getElementById('ea_id').value       = a.id;
    document.getElementById('ea_name').value     = a.asset_name;
    document.getElementById('ea_type').value     = a.asset_type;
    document.getElementById('ea_brand').value    = a.brand || '';
    document.getElementById('ea_model').value    = a.model || '';
    document.getElementById('ea_serial').value   = a.serial_number || '';
    document.getElementById('ea_status').value   = a.status;
    document.getElementById('ea_assigned').value = a.assigned_to || '';
    document.getElementById('ea_location').value = a.location || '';
    document.getElementById('ea_purchase').value = a.purchase_date || '';
    document.getElementById('ea_warranty').value = a.warranty_expiry || '';
    document.getElementById('ea_notes').value    = a.notes || '';
    openModal('editAssetModal');
}

function openEditLicense(l) {
    document.getElementById('el_id').value     = l.id;
    document.getElementById('el_name').value   = l.software_name;
    document.getElementById('el_vendor').value = l.vendor || '';
    document.getElementById('el_type').value   = l.license_type;
    document.getElementById('el_status').value = l.status;
    document.getElementById('el_seats').value  = l.seats;
    document.getElementById('el_used').value   = l.seats_used;
    document.getElementById('el_expiry').value = l.expiry_date || '';
    document.getElementById('el_cost').value   = l.cost || '';
    document.getElementById('el_notes').value  = l.notes || '';
    openModal('editLicModal');
}

// Auto-dismiss flash
const flash = document.getElementById('flashMsg');
if (flash) setTimeout(() => { flash.style.transition='opacity .5s'; flash.style.opacity=0; setTimeout(()=>flash.remove(),500); }, 3000);
</script>
</body>
</html>
