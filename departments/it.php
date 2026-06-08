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
$db = $database->getConnection();

$role = $_SESSION['role'];

// ── HANDLE POST ACTIONS ──────────────────────────────────────────────────────

// Add asset
if ($_POST && isset($_POST['add_asset'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin', 'manager'])) { http_response_code(403); die('Access denied.'); }
    $s = $db->prepare("INSERT INTO it_assets (asset_name, asset_type, brand, model, serial_number, purchase_date, warranty_expiry, assigned_to, status, location, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $s->execute([
        Security::sanitizeInput($_POST['asset_name']),
        Security::sanitizeInput($_POST['asset_type']),
        Security::sanitizeInput($_POST['brand'] ?? ''),
        Security::sanitizeInput($_POST['model'] ?? ''),
        Security::sanitizeInput($_POST['serial_number'] ?? ''),
        $_POST['purchase_date'] ?: null,
        $_POST['warranty_expiry'] ?: null,
        !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
        Security::sanitizeInput($_POST['status']),
        Security::sanitizeInput($_POST['location'] ?? ''),
        Security::sanitizeInput($_POST['notes'] ?? ''),
        $_SESSION['user_id'],
    ]);
    header("Location: it.php?view=assets&msg=asset_added"); exit();
}

// Update asset
if ($_POST && isset($_POST['update_asset'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin', 'manager'])) { http_response_code(403); die('Access denied.'); }
    $s = $db->prepare("UPDATE it_assets SET asset_name=?, asset_type=?, brand=?, model=?, serial_number=?, purchase_date=?, warranty_expiry=?, assigned_to=?, status=?, location=?, notes=? WHERE id=?");
    $s->execute([
        Security::sanitizeInput($_POST['asset_name']),
        Security::sanitizeInput($_POST['asset_type']),
        Security::sanitizeInput($_POST['brand'] ?? ''),
        Security::sanitizeInput($_POST['model'] ?? ''),
        Security::sanitizeInput($_POST['serial_number'] ?? ''),
        $_POST['purchase_date'] ?: null,
        $_POST['warranty_expiry'] ?: null,
        !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
        Security::sanitizeInput($_POST['status']),
        Security::sanitizeInput($_POST['location'] ?? ''),
        Security::sanitizeInput($_POST['notes'] ?? ''),
        (int)$_POST['asset_id'],
    ]);
    header("Location: it.php?view=assets"); exit();
}

// Add license
if ($_POST && isset($_POST['add_license'])) {
    Security::checkCSRFToken();
    if (!in_array($role, ['admin', 'manager'])) { http_response_code(403); die('Access denied.'); }
    $s = $db->prepare("INSERT INTO it_licenses (software_name, vendor, license_key, license_type, seats, seats_used, purchase_date, expiry_date, cost, status, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $s->execute([
        Security::sanitizeInput($_POST['software_name']),
        Security::sanitizeInput($_POST['vendor'] ?? ''),
        Security::sanitizeInput($_POST['license_key'] ?? ''),
        Security::sanitizeInput($_POST['license_type']),
        (int)($_POST['seats'] ?? 1),
        (int)($_POST['seats_used'] ?? 0),
        $_POST['purchase_date'] ?: null,
        $_POST['expiry_date'] ?: null,
        !empty($_POST['cost']) ? (float)$_POST['cost'] : null,
        Security::sanitizeInput($_POST['status']),
        Security::sanitizeInput($_POST['notes'] ?? ''),
        $_SESSION['user_id'],
    ]);
    header("Location: it.php?view=licenses&msg=license_added"); exit();
}

// ── FETCH DATA ───────────────────────────────────────────────────────────────

$assets = $db->query("SELECT a.*, u.username as assigned_username FROM it_assets a LEFT JOIN users u ON a.assigned_to = u.id ORDER BY a.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$licenses = $db->query("SELECT * FROM it_licenses ORDER BY expiry_date IS NULL, expiry_date ASC")->fetchAll(PDO::FETCH_ASSOC);

$all_users = $db->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_assets      = count($assets);
$assigned_assets   = count(array_filter($assets, fn($a) => $a['status'] === 'assigned'));
$maintenance       = count(array_filter($assets, fn($a) => $a['status'] === 'maintenance'));
$total_licenses    = count($licenses);
$expiring_soon     = count(array_filter($licenses, fn($l) => $l['expiry_date'] && strtotime($l['expiry_date']) <= strtotime('+30 days') && $l['status'] === 'active'));

$view = $_GET['view'] ?? 'assets';
$msg  = $_GET['msg']  ?? '';

$asset_types   = ['Computer', 'Laptop', 'Monitor', 'Printer', 'Router', 'Switch', 'Server', 'Phone', 'Tablet', 'Other'];
$asset_statuses = ['available', 'assigned', 'maintenance', 'retired'];
$license_types  = ['perpetual', 'subscription', 'trial', 'open_source'];
$license_statuses = ['active', 'expired', 'cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Department - KConsulting Hub</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .nav-tabs { display:flex; background:#fff; border-radius:8px; margin-bottom:2rem; box-shadow:0 2px 4px rgba(0,0,0,.1); overflow:hidden; }
        .nav-tab  { flex:1; padding:1rem 2rem; text-decoration:none; color:#666; background:#fff; border:none; border-right:1px solid #eee; text-align:center; font-weight:500; transition:all .3s; }
        .nav-tab:last-child { border-right:none; }
        .nav-tab.active { background:#0ea5e9; color:#fff; }
        .nav-tab:hover:not(.active) { background:#f8f9fa; }
        .it-table { width:100%; border-collapse:collapse; }
        .it-table th { background:#f8f9fa; padding:.75rem 1rem; text-align:left; font-size:.85rem; font-weight:600; color:#374151; border-bottom:2px solid #e5e7eb; }
        .it-table td { padding:.75rem 1rem; border-bottom:1px solid #f3f4f6; font-size:.875rem; }
        .it-table tr:hover td { background:#f9fafb; }
        .badge { display:inline-block; padding:.2rem .6rem; border-radius:20px; font-size:.75rem; font-weight:600; }
        .badge-available   { background:#d1fae5; color:#065f46; }
        .badge-assigned    { background:#dbeafe; color:#1e40af; }
        .badge-maintenance { background:#fef3c7; color:#92400e; }
        .badge-retired     { background:#f3f4f6; color:#6b7280; }
        .badge-active      { background:#d1fae5; color:#065f46; }
        .badge-expired     { background:#fee2e2; color:#991b1b; }
        .badge-cancelled   { background:#f3f4f6; color:#6b7280; }
        .badge-expiring    { background:#fef3c7; color:#92400e; }
        .msg-success { background:#d1fae5; color:#065f46; padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem; }
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal { background:#fff; border-radius:12px; padding:2rem; width:90%; max-width:600px; max-height:90vh; overflow-y:auto; }
        .modal h3 { margin:0 0 1.5rem; }
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

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?= $total_assets ?></div><div class="stat-label">Total Assets</div></div>
            <div class="stat-card"><div class="stat-number"><?= $assigned_assets ?></div><div class="stat-label">Assigned</div></div>
            <div class="stat-card"><div class="stat-number" style="color:<?= $maintenance > 0 ? '#f59e0b' : '#22c55e' ?>"><?= $maintenance ?></div><div class="stat-label">In Maintenance</div></div>
            <div class="stat-card"><div class="stat-number"><?= $total_licenses ?></div><div class="stat-label">Licenses</div></div>
            <div class="stat-card"><div class="stat-number" style="color:<?= $expiring_soon > 0 ? '#ef4444' : '#22c55e' ?>"><?= $expiring_soon ?></div><div class="stat-label">Expiring (30d)</div></div>
        </div>

        <?php if ($msg === 'asset_added'): ?>
        <div class="msg-success">✅ Asset added successfully.</div>
        <?php elseif ($msg === 'license_added'): ?>
        <div class="msg-success">✅ License added successfully.</div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="nav-tabs">
            <a href="?view=assets"          class="nav-tab <?= $view === 'assets'          ? 'active' : '' ?>">🖥️ Assets</a>
            <a href="?view=licenses"        class="nav-tab <?= $view === 'licenses'        ? 'active' : '' ?>">🔑 Licenses</a>
            <?php if (in_array($role, ['admin', 'manager'])): ?>
            <a href="?view=add_asset"       class="nav-tab <?= $view === 'add_asset'       ? 'active' : '' ?>">➕ Add Asset</a>
            <a href="?view=add_license"     class="nav-tab <?= $view === 'add_license'     ? 'active' : '' ?>">➕ Add License</a>
            <?php endif; ?>
        </div>

        <?php if ($view === 'assets'): ?>
        <!-- ── ASSETS TABLE ── -->
        <div class="section">
            <div class="section-header">🖥️ IT Assets (<?= $total_assets ?>)</div>
            <div class="section-content" style="overflow-x:auto;">
                <?php if (empty($assets)): ?>
                <p style="text-align:center;color:#9ca3af;padding:2rem;">No assets recorded yet. <a href="?view=add_asset">Add your first asset →</a></p>
                <?php else: ?>
                <table class="it-table">
                    <thead>
                        <tr>
                            <th>Asset</th><th>Type</th><th>Brand / Model</th><th>Serial No.</th>
                            <th>Assigned To</th><th>Status</th><th>Warranty</th><th>Location</th>
                            <?php if (in_array($role, ['admin', 'manager'])): ?><th></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($assets as $a):
                        $ws = $a['warranty_expiry'] ? strtotime($a['warranty_expiry']) : null;
                        $wExpired = $ws && $ws < time();
                    ?>
                        <tr>
                            <td><strong><?= Security::escapeHTML($a['asset_name']) ?></strong></td>
                            <td><?= Security::escapeHTML($a['asset_type']) ?></td>
                            <td><?= Security::escapeHTML(trim($a['brand'] . ' ' . $a['model'])) ?></td>
                            <td style="font-family:monospace;font-size:.8rem;"><?= Security::escapeHTML($a['serial_number'] ?? '—') ?></td>
                            <td><?= $a['assigned_username'] ? Security::escapeHTML($a['assigned_username']) : '<span style="color:#9ca3af;">Unassigned</span>' ?></td>
                            <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                            <td>
                                <?php if ($a['warranty_expiry']): ?>
                                <span style="color:<?= $wExpired ? '#ef4444' : '#374151' ?>;font-size:.8rem;">
                                    <?= date('M j, Y', strtotime($a['warranty_expiry'])) ?>
                                    <?= $wExpired ? ' ⚠' : '' ?>
                                </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td style="font-size:.8rem;"><?= Security::escapeHTML($a['location'] ?? '—') ?></td>
                            <?php if (in_array($role, ['admin', 'manager'])): ?>
                            <td>
                                <button onclick="openEdit(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)" class="btn btn-small" style="font-size:.75rem;">Edit</button>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($view === 'licenses'): ?>
        <!-- ── LICENSES TABLE ── -->
        <div class="section">
            <div class="section-header">🔑 Software Licenses (<?= $total_licenses ?>)</div>
            <div class="section-content" style="overflow-x:auto;">
                <?php if (empty($licenses)): ?>
                <p style="text-align:center;color:#9ca3af;padding:2rem;">No licenses recorded yet. <a href="?view=add_license">Add your first license →</a></p>
                <?php else: ?>
                <table class="it-table">
                    <thead>
                        <tr>
                            <th>Software</th><th>Vendor</th><th>Type</th>
                            <th>Seats (Used/Total)</th><th>Expiry</th><th>Cost</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($licenses as $l):
                        $expiry = $l['expiry_date'] ? strtotime($l['expiry_date']) : null;
                        $isExpiringSoon = $expiry && $expiry <= strtotime('+30 days') && $l['status'] === 'active';
                        $badgeClass = $l['status'] === 'active' ? ($isExpiringSoon ? 'badge-expiring' : 'badge-active') : 'badge-'.$l['status'];
                    ?>
                        <tr>
                            <td><strong><?= Security::escapeHTML($l['software_name']) ?></strong></td>
                            <td><?= Security::escapeHTML($l['vendor'] ?? '—') ?></td>
                            <td><?= ucfirst(str_replace('_', ' ', $l['license_type'])) ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:.5rem;">
                                    <div style="flex:1;background:#f3f4f6;border-radius:10px;height:6px;overflow:hidden;">
                                        <div style="width:<?= $l['seats'] > 0 ? min(100, round($l['seats_used']/$l['seats']*100)) : 0 ?>%;height:100%;background:#0ea5e9;"></div>
                                    </div>
                                    <span style="font-size:.8rem;"><?= $l['seats_used'] ?>/<?= $l['seats'] ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if ($l['expiry_date']): ?>
                                <span style="font-size:.8rem;color:<?= $isExpiringSoon ? '#ef4444' : '#374151' ?>;">
                                    <?= date('M j, Y', strtotime($l['expiry_date'])) ?>
                                    <?= $isExpiringSoon ? ' ⚠' : '' ?>
                                </span>
                                <?php else: ?><span style="color:#9ca3af;font-size:.8rem;">Perpetual</span><?php endif; ?>
                            </td>
                            <td><?= $l['cost'] ? 'R '.number_format($l['cost'], 2) : '—' ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= $isExpiringSoon ? 'Expiring Soon' : ucfirst($l['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($view === 'add_asset' && in_array($role, ['admin', 'manager'])): ?>
        <!-- ── ADD ASSET FORM ── -->
        <div class="section">
            <div class="section-header">➕ Add New Asset</div>
            <div class="section-content">
                <form method="post">
                    <?= Security::getCSRFTokenField() ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Asset Name *</label>
                            <input type="text" name="asset_name" required placeholder="e.g. Dev Laptop #3">
                        </div>
                        <div class="form-group">
                            <label>Asset Type *</label>
                            <select name="asset_type" required>
                                <?php foreach ($asset_types as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Brand</label>
                            <input type="text" name="brand" placeholder="e.g. Dell, HP, Lenovo">
                        </div>
                        <div class="form-group">
                            <label>Model</label>
                            <input type="text" name="model" placeholder="e.g. XPS 15 9500">
                        </div>
                        <div class="form-group">
                            <label>Serial Number</label>
                            <input type="text" name="serial_number">
                        </div>
                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" required>
                                <?php foreach ($asset_statuses as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Assigned To</label>
                            <select name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($all_users as $u): ?><option value="<?= $u['id'] ?>"><?= Security::escapeHTML($u['username']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" placeholder="e.g. Office Floor 2, Desk 14">
                        </div>
                        <div class="form-group">
                            <label>Purchase Date</label>
                            <input type="date" name="purchase_date">
                        </div>
                        <div class="form-group">
                            <label>Warranty Expiry</label>
                            <input type="date" name="warranty_expiry">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_asset" class="btn">Save Asset</button>
                    <a href="?view=assets" class="btn btn-secondary" style="margin-left:.5rem;">Cancel</a>
                </form>
            </div>
        </div>

        <?php elseif ($view === 'add_license' && in_array($role, ['admin', 'manager'])): ?>
        <!-- ── ADD LICENSE FORM ── -->
        <div class="section">
            <div class="section-header">➕ Add Software License</div>
            <div class="section-content">
                <form method="post">
                    <?= Security::getCSRFTokenField() ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Software Name *</label>
                            <input type="text" name="software_name" required placeholder="e.g. Microsoft 365">
                        </div>
                        <div class="form-group">
                            <label>Vendor</label>
                            <input type="text" name="vendor" placeholder="e.g. Microsoft">
                        </div>
                        <div class="form-group">
                            <label>License Key</label>
                            <input type="text" name="license_key" placeholder="XXXXX-XXXXX-XXXXX-XXXXX">
                        </div>
                        <div class="form-group">
                            <label>License Type *</label>
                            <select name="license_type" required>
                                <?php foreach ($license_types as $t): ?><option value="<?= $t ?>"><?= ucfirst(str_replace('_', ' ', $t)) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Total Seats</label>
                            <input type="number" name="seats" value="1" min="1">
                        </div>
                        <div class="form-group">
                            <label>Seats Used</label>
                            <input type="number" name="seats_used" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label>Purchase Date</label>
                            <input type="date" name="purchase_date">
                        </div>
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date">
                        </div>
                        <div class="form-group">
                            <label>Cost (R)</label>
                            <input type="number" name="cost" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" required>
                                <?php foreach ($license_statuses as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_license" class="btn">Save License</button>
                    <a href="?view=licenses" class="btn btn-secondary" style="margin-left:.5rem;">Cancel</a>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.main-content -->

    <!-- Edit Asset Modal -->
    <?php if (in_array($role, ['admin', 'manager'])): ?>
    <div id="editModal" class="modal-overlay">
        <div class="modal">
            <h3>✏️ Edit Asset</h3>
            <form method="post" id="editForm">
                <?= Security::getCSRFTokenField() ?>
                <input type="hidden" name="asset_id" id="edit_asset_id">
                <div class="form-grid">
                    <div class="form-group"><label>Asset Name *</label><input type="text" name="asset_name" id="edit_asset_name" required></div>
                    <div class="form-group"><label>Type *</label>
                        <select name="asset_type" id="edit_asset_type">
                            <?php foreach ($asset_types as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Brand</label><input type="text" name="brand" id="edit_brand"></div>
                    <div class="form-group"><label>Model</label><input type="text" name="model" id="edit_model"></div>
                    <div class="form-group"><label>Serial No.</label><input type="text" name="serial_number" id="edit_serial"></div>
                    <div class="form-group"><label>Status</label>
                        <select name="status" id="edit_status">
                            <?php foreach ($asset_statuses as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Assigned To</label>
                        <select name="assigned_to" id="edit_assigned_to">
                            <option value="">Unassigned</option>
                            <?php foreach ($all_users as $u): ?><option value="<?= $u['id'] ?>"><?= Security::escapeHTML($u['username']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Location</label><input type="text" name="location" id="edit_location"></div>
                    <div class="form-group"><label>Purchase Date</label><input type="date" name="purchase_date" id="edit_purchase_date"></div>
                    <div class="form-group"><label>Warranty Expiry</label><input type="date" name="warranty_expiry" id="edit_warranty"></div>
                </div>
                <div class="form-group"><label>Notes</label><textarea name="notes" id="edit_notes" rows="3"></textarea></div>
                <button type="submit" name="update_asset" class="btn">Save Changes</button>
                <button type="button" onclick="closeEdit()" class="btn btn-secondary" style="margin-left:.5rem;">Cancel</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="../js/notification.js"></script>
    <script>
    function openEdit(asset) {
        document.getElementById('edit_asset_id').value    = asset.id;
        document.getElementById('edit_asset_name').value  = asset.asset_name;
        document.getElementById('edit_asset_type').value  = asset.asset_type;
        document.getElementById('edit_brand').value       = asset.brand || '';
        document.getElementById('edit_model').value       = asset.model || '';
        document.getElementById('edit_serial').value      = asset.serial_number || '';
        document.getElementById('edit_status').value      = asset.status;
        document.getElementById('edit_assigned_to').value = asset.assigned_to || '';
        document.getElementById('edit_location').value   = asset.location || '';
        document.getElementById('edit_purchase_date').value = asset.purchase_date || '';
        document.getElementById('edit_warranty').value   = asset.warranty_expiry || '';
        document.getElementById('edit_notes').value      = asset.notes || '';
        document.getElementById('editModal').classList.add('open');
    }
    function closeEdit() {
        document.getElementById('editModal').classList.remove('open');
    }
    document.getElementById('editModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeEdit();
    });
    </script>
</body>
</html>
