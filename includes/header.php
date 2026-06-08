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

        <!-- User dropdown -->
        <div class="hdr-user-wrap" id="hdrUserWrap">
            <button class="hdr-user-btn" onclick="toggleUserMenu(event)" aria-haspopup="true">
                <div class="user-avatar"><?= Security::escapeHTML($_hdr_initials) ?></div>
                <div class="user-details">
                    <span class="user-name"><?= Security::escapeHTML($_hdr_user) ?></span>
                    <span class="user-role-label"><?= Security::escapeHTML(ucfirst($_hdr_role)) ?></span>
                </div>
                <span class="hdr-caret">▾</span>
            </button>
            <div class="hdr-user-menu" id="hdrUserMenu" aria-hidden="true">
                <div class="hdr-menu-info">
                    <div class="hdr-menu-avatar"><?= Security::escapeHTML($_hdr_initials) ?></div>
                    <div>
                        <div class="hdr-menu-name"><?= Security::escapeHTML($_hdr_user) ?></div>
                        <div class="hdr-menu-role"><?= Security::escapeHTML(ucfirst($_hdr_role)) ?></div>
                    </div>
                </div>
                <div class="hdr-menu-divider"></div>
                <a href="<?= $_ab ?>profile.php" class="hdr-menu-item">
                    <span class="hdr-menu-icon">👤</span> My Profile
                </a>
                <a href="<?= $_ab ?>admin/activity_log.php" class="hdr-menu-item">
                    <span class="hdr-menu-icon">📋</span> Activity Log
                </a>
                <div class="hdr-menu-divider"></div>
                <a href="<?= $_ab ?>auth/logout.php" class="hdr-menu-item hdr-menu-danger">
                    <span class="hdr-menu-icon">🚪</span> Sign Out
                </a>
            </div>
        </div>
    </div>
</header>

<style>
.hdr-user-wrap { position: relative; }
.hdr-user-btn {
    display: flex; align-items: center; gap: .55rem;
    background: transparent; border: none; cursor: pointer;
    padding: .3rem .5rem; border-radius: 8px; transition: background .15s;
}
.hdr-user-btn:hover { background: rgba(255,255,255,.08); }
.hdr-caret { font-size: .7rem; color: rgba(255,255,255,.55); margin-left: 1px; transition: transform .2s; }
.hdr-user-btn[aria-expanded="true"] .hdr-caret { transform: rotate(180deg); }

.hdr-user-menu {
    position: absolute; right: 0; top: calc(100% + 8px);
    background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,.13); min-width: 210px;
    z-index: 9999; overflow: hidden;
    opacity: 0; transform: translateY(-6px) scale(.97);
    pointer-events: none;
    transition: opacity .18s ease, transform .18s ease;
}
.hdr-user-menu.open {
    opacity: 1; transform: translateY(0) scale(1); pointer-events: auto;
}
.hdr-menu-info { display: flex; align-items: center; gap: 10px; padding: 12px 14px; }
.hdr-menu-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--nav-accent, #f59e0b); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; font-weight: 700; flex-shrink: 0;
}
.hdr-menu-name { font-size: .86rem; font-weight: 700; color: #111827; }
.hdr-menu-role { font-size: .72rem; color: #9ca3af; text-transform: capitalize; margin-top: 1px; }
.hdr-menu-divider { height: 1px; background: #f3f4f6; margin: 2px 0; }
.hdr-menu-item {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 14px; font-size: .85rem; color: #374151;
    text-decoration: none; transition: background .12s;
}
.hdr-menu-item:hover { background: #f9fafb; color: #111827; }
.hdr-menu-icon { font-size: 1rem; width: 20px; text-align: center; }
.hdr-menu-danger { color: #dc2626; }
.hdr-menu-danger:hover { background: #fef2f2; color: #b91c1c; }
</style>

<script>
function toggleUserMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('hdrUserMenu');
    const btn  = e.currentTarget;
    const open = menu.classList.toggle('open');
    btn.setAttribute('aria-expanded', open);
    if (open) {
        document.getElementById('notificationsDropdown')?.style && (document.getElementById('notificationsDropdown').style.display = 'none');
    }
}
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('hdrUserWrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('hdrUserMenu')?.classList.remove('open');
        wrap.querySelector('.hdr-user-btn')?.setAttribute('aria-expanded','false');
    }
});
</script>

