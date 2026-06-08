<?php
/* Resolve path bases — callers set $asset_base and $nav_base before including */
$_ab = $asset_base ?? '../';   // path prefix for assets: '' at root, '../' in departments/

/* Build user display values */
$_hdr_user = $username ?? $_SESSION['username'] ?? 'User';
$_hdr_role = $role     ?? $_SESSION['role']     ?? '';
$_hdr_uid  = $user_id  ?? $_SESSION['user_id']  ?? 0;

/* Initials for avatar */
$_hdr_parts    = preg_split('/\s+/', trim($_hdr_user));
$_hdr_initials = count($_hdr_parts) >= 2
    ? strtoupper(mb_substr($_hdr_parts[0], 0, 1) . mb_substr($_hdr_parts[count($_hdr_parts)-1], 0, 1))
    : strtoupper(mb_substr($_hdr_user, 0, 2));

/* Notification count */
$_hdr_notif_count = 0;
try {
    $q = "SELECT COUNT(DISTINCT pa.id) FROM project_assignments pa
          JOIN projects p ON pa.project_id = p.id
          WHERE pa.user_id = ? AND pa.assigned_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $s = $db->prepare($q); $s->execute([$_hdr_uid]);
    $_hdr_notif_count += (int)$s->fetchColumn();

    $q = "SELECT COUNT(DISTINCT pc.id) FROM project_comments pc
          JOIN project_assignments pa ON pc.project_id = pa.project_id
          WHERE pa.user_id = ? AND pc.user_id != ? AND pc.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $s = $db->prepare($q); $s->execute([$_hdr_uid, $_hdr_uid]);
    $_hdr_notif_count += (int)$s->fetchColumn();
} catch (Exception $e) {
    $_hdr_notif_count = 0;
}

/* Recent notifications for dropdown */
$_hdr_notifs = [];
try {
    $q = "SELECT p.name as title, pa.assigned_at as ts, pa.role, 'assignment' as type
          FROM project_assignments pa JOIN projects p ON pa.project_id = p.id
          WHERE pa.user_id = ? AND pa.assigned_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
          ORDER BY pa.assigned_at DESC LIMIT 5";
    $s = $db->prepare($q); $s->execute([$_hdr_uid]);
    $_hdr_notifs = $s->fetchAll(PDO::FETCH_ASSOC);

    $q = "SELECT p.name as title, pc.comment, pc.created_at as ts, u.username, 'comment' as type
          FROM project_comments pc
          JOIN project_assignments pa ON pc.project_id = pa.project_id
          JOIN projects p ON pc.project_id = p.id
          JOIN users u ON pc.user_id = u.id
          WHERE pa.user_id = ? AND pc.user_id != ? AND pc.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
          ORDER BY pc.created_at DESC LIMIT 5";
    $s = $db->prepare($q); $s->execute([$_hdr_uid, $_hdr_uid]);
    $_hdr_notifs = array_merge($_hdr_notifs, $s->fetchAll(PDO::FETCH_ASSOC));

    usort($_hdr_notifs, fn($a, $b) => strtotime($b['ts']) - strtotime($a['ts']));
    $_hdr_notifs = array_slice($_hdr_notifs, 0, 8);
} catch (Exception $e) {
    $_hdr_notifs = [];
}
?>
<header class="app-header">
    <div class="header-left">
        <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
            <span></span><span></span><span></span>
        </button>
        <a href="<?= $_ab ?>dashboard.php" class="header-brand">
            <img src="<?= $_ab ?>img/KConsultingLogo.png" alt="KConsulting" class="header-logo">
            <div class="header-brand-text">
                <span class="header-brand-name">KConsulting</span>
                <span class="header-brand-sub">Hub</span>
            </div>
        </a>
    </div>

    <div class="header-right">
        <!-- Notifications -->
        <div class="header-notif-wrap">
            <button class="header-notif-btn" onclick="toggleNotifications()" aria-label="Notifications">
                🔔
                <?php if ($_hdr_notif_count > 0): ?>
                    <span class="notification-badge"><?= min($_hdr_notif_count, 99) ?></span>
                <?php endif; ?>
            </button>
            <div id="notificationsDropdown" class="notifications-dropdown" style="display:none;">
                <div class="notifications-header">
                    <h4>Notifications</h4>
                    <span class="close-notifications" onclick="toggleNotifications()">&times;</span>
                </div>
                <div class="notifications-body">
                    <?php if (empty($_hdr_notifs)): ?>
                        <div class="notification-item">
                            <p class="no-notifications">No recent notifications</p>
                        </div>
                    <?php else: foreach ($_hdr_notifs as $n): ?>
                        <div class="notification-item">
                            <div class="notification-icon-type"><?= $n['type'] === 'assignment' ? '📋' : '💬' ?></div>
                            <div class="notification-content">
                                <?php if ($n['type'] === 'assignment'): ?>
                                    <p class="notification-title">New Project Assignment</p>
                                    <p class="notification-text">Assigned to: <strong><?= Security::escapeHTML($n['title']) ?></strong></p>
                                    <p class="notification-meta">Role: <?= Security::escapeHTML($n['role']) ?> &bull; <?= date('M j, g:i A', strtotime($n['ts'])) ?></p>
                                <?php else: ?>
                                    <p class="notification-title">Comment on <?= Security::escapeHTML($n['title']) ?></p>
                                    <p class="notification-text"><?= Security::escapeHTML(mb_strimwidth($n['comment'], 0, 60, '…')) ?></p>
                                    <p class="notification-meta">By <?= Security::escapeHTML($n['username']) ?> &bull; <?= date('M j, g:i A', strtotime($n['ts'])) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- User chip -->
        <div class="header-user">
            <div class="user-avatar" title="<?= Security::escapeHTML($_hdr_user) ?>">
                <?= Security::escapeHTML($_hdr_initials) ?>
            </div>
            <div class="user-details">
                <span class="user-name"><?= Security::escapeHTML($_hdr_user) ?></span>
                <span class="user-role-label"><?= Security::escapeHTML(ucfirst($_hdr_role)) ?></span>
            </div>
        </div>

        <a href="<?= $_ab ?>auth/logout.php" class="header-logout-btn">Sign out</a>
    </div>
</header>

