<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_POST) {
    Security::checkCSRFToken();
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, username, email, password, role, department FROM users WHERE username = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $username);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        if (password_verify($password, $row['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['department'] = $row['department'];
            $_SESSION['email'] = $row['email'];
            
            // Log successful login activity
            try {
                require_once '../includes/ActivityLogger.php';
                $logger = new ActivityLogger($db);
                $logger->logLogin("User '{$row['username']}' logged in successfully");
            } catch (Exception $e) {
                // Don't break login if logging fails
                error_log("Activity logging failed: " . $e->getMessage());
            }
            
            header("Location: ../dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Invalid username.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Management - Login</title>
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo-placeholder">
            <h1>Your Company Logo</h1>
            <p>Business Management System</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo Security::escapeHTML($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo Security::escapeHTML($success); ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php echo Security::getCSRFTokenField(); ?>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="demo-info">
            <h4>Demo Accounts:</h4>
            <p><strong>Admin:</strong> admin / admin123</p>
            <p><strong>IT Manager:</strong> john_doe / password123</p>
            <p><strong>Marketing Manager:</strong> sarah_smith / password123</p>
        </div>
    </div>
</body>
</html>