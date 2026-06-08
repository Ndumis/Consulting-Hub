<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Log logout activity before destroying session
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    try {
        require_once '../includes/ActivityLogger.php';
        $database = new Database();
        $db = $database->getConnection();
        $logger = new ActivityLogger($db);
        $logger->logLogout("User '{$_SESSION['username']}' logged out");
    } catch (Exception $e) {
        // Don't break logout if logging fails
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

session_destroy();
header("Location: login.php");
exit();
?>