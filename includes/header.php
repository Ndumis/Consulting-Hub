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

/* Notifications from notifications table */
$_hdr_notif_count = 0;
$_hdr_notifs      = [];
try {
    $s = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $s->execute([$_hdr_uid]);
    $_hdr_notif_count = (int)$s->fetchColumn();

    $s = $db->prepare("SELECT id, type, title, message, link, is_read, created_at
        FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 12");
    $s->execute([$_hdr_uid]);
    $_hdr_notifs = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $_hdr_notif_count = 0;
    $_hdr_notifs      = [];
}

/* Type → icon map */
$_notif_icons = [
    'leave'   => '📅', 'invoice' => '💰', 'project' => '📋',
    'hr'      => '👥', 'success' => '✅', 'warning' => '⚠️',
    'info'    => 'ℹ️', 'comment' => '💬', 'assignment' => '📌',
];
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
                    <h4>Notifications
                        <?php if ($_hdr_notif_count > 0): ?>
                        <span class="notif-count-label"><?= $_hdr_notif_count ?> unread</span>
                        <?php endif; ?>
                    </h4>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <?php if ($_hdr_notif_count > 0): ?>
                        <button class="notif-mark-all-btn" onclick="markAllNotifRead()">Mark all read</button>
                        <?php endif; ?>
                        <span class="close-notifications" onclick="toggleNotifications()">&times;</span>
                    </div>
                </div>
                <div class="notifications-body" id="notifBody">
                    <?php if (empty($_hdr_notifs)): ?>
                        <div class="notif-empty">
                            <div style="font-size:2rem;margin-bottom:6px;">🔔</div>
                            <p>You're all caught up!</p>
                        </div>
                    <?php else: foreach ($_hdr_notifs as $n):
                        $icon = $_notif_icons[$n['type']] ?? 'ℹ️';
                        $unread_cls = $n['is_read'] ? '' : ' notif-unread';
                        $link_open  = $n['link'] ? '<a href="'.Security::escapeHTML($n['link']).'" style="text-decoration:none;color:inherit;">' : '<div>';
                        $link_close = $n['link'] ? '</a>' : '</div>';
                    ?>
                        <?= $link_open ?>
                        <div class="notification-item<?= $unread_cls ?>" data-id="<?= $n['id'] ?>">
                            <div class="notification-icon-type"><?= $icon ?></div>
                            <div class="notification-content">
                                <p class="notification-title"><?= Security::escapeHTML($n['title']) ?></p>
                                <?php if ($n['message']): ?>
                                <p class="notification-text"><?= Security::escapeHTML(mb_strimwidth($n['message'], 0, 70, '…')) ?></p>
                                <?php endif; ?>
                                <p class="notification-meta"><?= time_ago($n['created_at']) ?></p>
                            </div>
                            <?php if (!$n['is_read']): ?><div class="notif-dot"></div><?php endif; ?>
                        </div>
                        <?= $link_close ?>
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

/* Notification extras */
.notif-count-label { font-size:.7rem;font-weight:600;background:#fee2e2;color:#dc2626;padding:1px 7px;border-radius:20px;margin-left:6px;vertical-align:middle; }
.notif-mark-all-btn { font-size:.74rem;color:#6b7280;background:none;border:1px solid #e5e7eb;border-radius:6px;padding:3px 8px;cursor:pointer; }
.notif-mark-all-btn:hover { background:#f9fafb;color:#374151; }
.notif-unread { background:#eff6ff !important; }
.notif-dot { width:8px;height:8px;border-radius:50%;background:#2563eb;flex-shrink:0;margin-top:4px; }
.notification-item { display:flex;align-items:flex-start;gap:10px;padding:11px 14px;border-bottom:1px solid #f3f4f6;cursor:default;transition:background .12s; }
.notification-item:last-child { border-bottom:none; }
.notification-item:hover { background:#f9fafb; }
.notif-empty { text-align:center;padding:28px 14px;color:#9ca3af;font-size:.84rem; }
</style>

<script>
const _notifApiBase = '<?= rtrim($_ab,'/')?>/api/notifications.php';

function toggleNotifications() {
    const dd  = document.getElementById('notificationsDropdown');
    const open = dd.style.display === 'none';
    dd.style.display = open ? 'block' : 'none';
    // Close user menu
    document.getElementById('hdrUserMenu')?.classList.remove('open');
    document.querySelector('.hdr-user-btn')?.setAttribute('aria-expanded','false');
    // Mark all read when opening
    if (open) markAllNotifRead();
}

function markAllNotifRead() {
    fetch(_notifApiBase, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'mark_all_read'}) })
        .then(r => r.json()).then(() => {
            document.querySelector('.notification-badge')?.remove();
            document.querySelector('.notif-count-label')?.remove();
            document.querySelector('.notif-mark-all-btn')?.remove();
            document.querySelectorAll('.notif-unread').forEach(el => el.classList.remove('notif-unread'));
            document.querySelectorAll('.notif-dot').forEach(el => el.remove());
        }).catch(() => {});
}

// Auto-refresh unread count every 60 s
function refreshNotifCount() {
    fetch(_notifApiBase + '?action=count')
        .then(r => r.json()).then(d => {
            let badge = document.querySelector('.notification-badge');
            if (d.count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'notification-badge';
                    document.querySelector('.header-notif-btn')?.appendChild(badge);
                }
                badge.textContent = Math.min(d.count, 99);
            } else {
                badge?.remove();
            }
        }).catch(() => {});
}
setInterval(refreshNotifCount, 60000);

function toggleUserMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('hdrUserMenu');
    const btn  = e.currentTarget;
    const open = menu.classList.toggle('open');
    btn.setAttribute('aria-expanded', open);
    if (open) {
        document.getElementById('notificationsDropdown').style.display = 'none';
    }
}
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('hdrUserWrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('hdrUserMenu')?.classList.remove('open');
        wrap.querySelector('.hdr-user-btn')?.setAttribute('aria-expanded','false');
    }
    const notifWrap = document.querySelector('.header-notif-wrap');
    if (notifWrap && !notifWrap.contains(e.target)) {
        const dd = document.getElementById('notificationsDropdown');
        if (dd) dd.style.display = 'none';
    }
});
</script>

