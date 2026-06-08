<?php
if (!class_exists('Security')) {
class Security {
    
    /**
     * Generate CSRF token and store in session
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token input field for forms
     */
    public static function getCSRFTokenField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Check if user has permission to access department
     */
    public static function canAccessDepartment($userRole, $userDepartment, $targetDepartment) {
        // Admin can access all departments
        if ($userRole === 'admin') {
            return true;
        }
        
        // Managers and employees can only access their own department
        return $userDepartment === $targetDepartment;
    }
    
    /**
     * Check if user can write/modify data in department
     */
    public static function canWriteInDepartment($userRole, $userDepartment, $targetDepartment) {
        // Admin can write anywhere
        if ($userRole === 'admin') {
            return true;
        }
        
        // Managers can write in their own department
        if ($userRole === 'manager' && $userDepartment === $targetDepartment) {
            return true;
        }
        
        // Employees have limited write access (only to their own records)
        return false;
    }
    
    /**
     * Check if user can modify specific record (for employee-level access)
     */
    public static function canModifyRecord($userRole, $userDepartment, $targetDepartment, $recordOwnerId = null, $userId = null) {
        // Admin and managers have full access
        if (self::canWriteInDepartment($userRole, $userDepartment, $targetDepartment)) {
            return true;
        }
        
        // Employees can only modify their own records if they're in the right department
        if ($userRole === 'employee' && $userDepartment === $targetDepartment && $recordOwnerId && $userId) {
            return $recordOwnerId == $userId;
        }
        
        return false;
    }
    
    /**
     * Redirect unauthorized users
     */
    public static function redirectUnauthorized($targetPage = '../dashboard.php') {
        header("Location: $targetPage");
        exit();
    }
    
    /**
     * Sanitize and escape HTML output
     */
    public static function escapeHTML($data) {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize array of data for HTML output
     */
    public static function escapeHTMLArray($array) {
        $escaped = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $escaped[$key] = self::escapeHTMLArray($value);
            } else {
                $escaped[$key] = self::escapeHTML($value);
            }
        }
        return $escaped;
    }
    
    /**
     * Validate and sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitized[$key] = self::sanitizeInput($value);
            }
            return $sanitized;
        }
        
        // Remove potential HTML/script tags and trim whitespace
        return trim(strip_tags($data ?? ''));
    }
    
    /**
     * Check CSRF token from POST data
     */
    public static function checkCSRFToken() {
        if ($_POST && !self::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            die('CSRF token validation failed. Please refresh the page and try again.');
        }
    }
    
    /**
     * Require specific role and department access
     */
    public static function requireDepartmentAccess($requiredDepartment) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['department'])) {
            header("Location: ../auth/login.php");
            exit();
        }
        
        if (!self::canAccessDepartment($_SESSION['role'], $_SESSION['department'], $requiredDepartment)) {
            http_response_code(403);
            die('Access denied. You do not have permission to access this department.');
        }
    }
    
    /**
     * Require write permissions for department
     */
    public static function requireWriteAccess($targetDepartment) {
        if (!self::canWriteInDepartment($_SESSION['role'], $_SESSION['department'], $targetDepartment)) {
            http_response_code(403);
            die('Access denied. You do not have write permissions for this department.');
        }
    }
}
}
?>