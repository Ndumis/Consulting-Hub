<?php
/* Path bases — callers set $asset_base / $nav_base before including */
$_ab  = $asset_base ?? '../';    // asset prefix
$_nb  = $nav_base   ?? '';       // dept link prefix: '' inside departments/, 'departments/' at root

/* Active-tab detection */
$_cur = basename($_SERVER['PHP_SELF']);
$_tab = match($_cur) {
    'it.php'        => 'it',
    'projects.php'  => 'projects',
    'marketing.php', 'marketing_detail.php' => 'marketing',
    'bd.php'        => 'bd',
    'finance.php'   => 'finance',
    'hr.php'        => 'hr',
    'clients.php', 'client_detail.php'     => 'clients',
    'insights.php'  => 'insights',
    'reports.php'   => 'reports',
    'dashboard.php' => 'dashboard',
    'profile.php'   => 'profile',
    default         => '',
};
$_role_s = $_SESSION['role'] ?? '';

function _nav($href, $icon, $label, $active) {
    $cls = $active ? ' active' : '';
    return "<a href=\"{$href}\" class=\"nav-item{$cls}\"><span class=\"nav-icon\">{$icon}</span><span class=\"nav-label\">{$label}</span></a>";
}
?>
<!-- Overlay for mobile -->
<div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

<aside class="app-sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <?= _nav($_ab . 'dashboard.php', '📊', 'Dashboard', $_tab === 'dashboard') ?>

        <div class="nav-section-label">Departments</div>
        <?= _nav($_nb . 'projects.php',  '📋', 'Projects',              $_tab === 'projects')  ?>
        <?= _nav($_nb . 'it.php',        '💻', 'IT Dept',               $_tab === 'it')        ?>
        <?= _nav($_nb . 'marketing.php', '📈', 'Marketing',            $_tab === 'marketing') ?>
        <?= _nav($_nb . 'bd.php',        '🎯', 'Business Development', $_tab === 'bd')        ?>
        <?= _nav($_nb . 'finance.php',   '💰', 'Finance',              $_tab === 'finance')   ?>
        <?= _nav($_nb . 'hr.php',        '👥', 'HR',                   $_tab === 'hr')        ?>

        <div class="nav-divider"></div>
        <div class="nav-section-label">Management</div>
        <?= _nav($_nb . 'clients.php',   '🏢', 'Clients',   $_tab === 'clients')  ?>
        <?= _nav($_nb . 'insights.php',  '📉', 'Insights',  $_tab === 'insights') ?>
        <?= _nav($_nb . 'reports.php',   '📑', 'Reports',   $_tab === 'reports')  ?>

    </nav>
</aside>
