<?php
// Auto-detect current tab from the PHP file name
$current_file = basename($_SERVER['PHP_SELF']);
$tab = '';

switch ($current_file) {
    case 'it.php':
        $tab = 'it';
        break;
    case 'marketing.php':
        $tab = 'marketing';
        break;
    case 'bd.php':
        $tab = 'bd';
        break;
    case 'finance.php':
        $tab = 'finance';
        break;
    case 'hr.php':
        $tab = 'hr';
        break;
    case 'clients.php':
        $tab = 'clients';
        break;
    case 'insights.php':
        $tab = 'insights';
        break;
    default:
        $tab = '';
}
?>

<div class="sidebar" id="sidebar">
    <a href="../dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">📊 Dashboard</a>
    <a href="it.php" class="nav-item <?php echo $tab === 'it' ? 'active' : ''; ?>">💻 IT Department</a>
    <a href="marketing.php" class="nav-item <?php echo $tab === 'marketing' ? 'active' : ''; ?>">📈 Marketing</a>
    <a href="bd.php" class="nav-item <?php echo $tab === 'bd' ? 'active' : ''; ?>">🎯 Business Development</a>
    <a href="finance.php" class="nav-item <?php echo $tab === 'finance' ? 'active' : ''; ?>">💰 Finance</a>
    <a href="hr.php" class="nav-item <?php echo $tab === 'hr' ? 'active' : ''; ?>">👥 HR</a>
    <a href="clients.php" class="nav-item <?php echo $tab === 'clients' ? 'active' : ''; ?>">🏢 Clients</a>
    <a href="insights.php" class="nav-item <?php echo $tab === 'insights' ? 'active' : ''; ?>">📊 Insights</a>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="../admin/activity_log.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'activity_log.php' ? 'active' : ''; ?>" style="border-top: 1px solid #404040; margin-top: 0.5rem; padding-top: 1rem;">🔧 Activity Log</a>
    <?php endif; ?>
</div>