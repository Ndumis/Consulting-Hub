<?php
class ActivityLogger {
    private $db;
    
    public function __construct($database_connection = null) {
        if ($database_connection) {
            $this->db = $database_connection;
        } else {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $this->db = $database->getConnection();
        }
    }
    
    /**
     * Log user activity
     */
    public function logActivity($activity_type, $description, $options = []) {
        try {
            // Get current user info from session
            $user_id = $_SESSION['user_id'] ?? null;
            $username = $_SESSION['username'] ?? 'guest';
            
            // Get request information
            $page_url = $_SERVER['REQUEST_URI'] ?? '';
            $ip_address = $this->getUserIP();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $session_id = session_id();
            
            // Prepare activity data
            $activity_data = [
                'user_id' => $user_id,
                'username' => $username,
                'activity_type' => $activity_type,
                'description' => $description,
                'page_url' => $page_url,
                'resource_type' => $options['resource_type'] ?? null,
                'resource_id' => $options['resource_id'] ?? null,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'session_id' => $session_id,
                'additional_data' => isset($options['additional_data']) ? json_encode($options['additional_data']) : null
            ];
            
            // Insert activity record
            $query = "INSERT INTO user_activities (user_id, username, activity_type, description, page_url, resource_type, resource_id, ip_address, user_agent, session_id, additional_data) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $activity_data['user_id'],
                $activity_data['username'],
                $activity_data['activity_type'],
                $activity_data['description'],
                $activity_data['page_url'],
                $activity_data['resource_type'],
                $activity_data['resource_id'],
                $activity_data['ip_address'],
                $activity_data['user_agent'],
                $activity_data['session_id'],
                $activity_data['additional_data']
            ]);
            
            return true;
            
        } catch (Throwable $e) {
            // Log error but don't break the application
            error_log("ActivityLogger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Convenience methods for different activity types
     */
    public function logLogin($description = 'User logged in') {
        return $this->logActivity('login', $description);
    }
    
    public function logLogout($description = 'User logged out') {
        return $this->logActivity('logout', $description);
    }
    
    public function logPageVisit($page_name, $description = null) {
        $description = $description ?? "Visited {$page_name} page";
        return $this->logActivity('page_visit', $description);
    }
    
    public function logCreate($resource_type, $resource_id, $description, $additional_data = null) {
        return $this->logActivity('create', $description, [
            'resource_type' => $resource_type,
            'resource_id' => $resource_id,
            'additional_data' => $additional_data
        ]);
    }
    
    public function logEdit($resource_type, $resource_id, $description, $additional_data = null) {
        return $this->logActivity('edit', $description, [
            'resource_type' => $resource_type,
            'resource_id' => $resource_id,
            'additional_data' => $additional_data
        ]);
    }
    
    public function logDelete($resource_type, $resource_id, $description, $additional_data = null) {
        return $this->logActivity('delete', $description, [
            'resource_type' => $resource_type,
            'resource_id' => $resource_id,
            'additional_data' => $additional_data
        ]);
    }
    
    public function logSave($resource_type, $resource_id, $description, $additional_data = null) {
        return $this->logActivity('save', $description, [
            'resource_type' => $resource_type,
            'resource_id' => $resource_id,
            'additional_data' => $additional_data
        ]);
    }
    
    public function logView($resource_type, $resource_id, $description, $additional_data = null) {
        return $this->logActivity('view', $description, [
            'resource_type' => $resource_type,
            'resource_id' => $resource_id,
            'additional_data' => $additional_data
        ]);
    }
    
    /**
     * Get recent activities for a user
     */
    public function getRecentActivities($user_id = null, $limit = 50) {
        try {
            if ($user_id) {
                $query = "SELECT * FROM user_activities WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$user_id, $limit]);
            } else {
                $query = "SELECT * FROM user_activities ORDER BY created_at DESC LIMIT ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$limit]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Throwable $e) {
            error_log("ActivityLogger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get activity statistics
     */
    public function getActivityStats($days = 7) {
        try {
            $query = "SELECT 
                        activity_type,
                        COUNT(*) as count,
                        DATE_TRUNC('day', created_at) as activity_date
                      FROM user_activities 
                      WHERE created_at >= CURRENT_TIMESTAMP - INTERVAL '1 day' * ?
                      GROUP BY activity_type, DATE_TRUNC('day', created_at)
                      ORDER BY activity_date DESC, activity_type";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Throwable $e) {
            error_log("ActivityLogger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user's IP address (handles proxies and load balancers)
     */
    private function getUserIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
?>