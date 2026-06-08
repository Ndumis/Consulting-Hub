<div class="header">
    <div class="logo-section">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">☰</button>
        <div class="logo-placeholder">KC FIRM</div>
        <h2>Business Management System</h2>
    </div>
    <div class="notifications-section">
        <?php
        $notification_count = 0;
        try {
            $query = "SELECT COUNT(DISTINCT pa.id) as count FROM project_assignments pa 
                        JOIN projects p ON pa.project_id = p.id 
                        WHERE pa.user_id = ? AND pa.assigned_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $assignments = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            $query = "SELECT COUNT(DISTINCT pc.id) as count FROM project_comments pc 
                        JOIN project_assignments pa ON pc.project_id = pa.project_id 
                        WHERE pa.user_id = ? AND pc.user_id != ? AND pc.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id, $user_id]);
            $comments = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            $notification_count = $assignments + $comments;
        } catch (Exception $e) {
            $notification_count = 0;
        }
        ?>
        <div class="notification-icon" onclick="toggleNotifications()">
            <span class="notification-bell">🔔</span>
            <?php if ($notification_count > 0): ?>
            <span class="notification-badge"><?php echo $notification_count; ?></span>
            <?php endif; ?>
        </div>
        
        <div id="notificationsDropdown" class="notifications-dropdown" style="display: none;">
            <div class="notifications-header">
                <h4>Notifications</h4>
                <span class="close-notifications" onclick="toggleNotifications()">&times;</span>
            </div>
            <div class="notifications-body">
                <?php
                try {
                    $notifications = [];
                    
                    $query = "SELECT DISTINCT p.name as title, p.description, pa.assigned_at, pa.role, 'assignment' as type
                                FROM project_assignments pa 
                                JOIN projects p ON pa.project_id = p.id 
                                WHERE pa.user_id = ? AND pa.assigned_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                                ORDER BY pa.assigned_at DESC LIMIT 5";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id]);
                    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $query = "SELECT DISTINCT p.name as title, pc.comment, pc.created_at, u.username, 'comment' as type
                                FROM project_comments pc 
                                JOIN project_assignments pa ON pc.project_id = pa.project_id 
                                JOIN projects p ON pc.project_id = p.id
                                JOIN users u ON pc.user_id = u.id
                                WHERE pa.user_id = ? AND pc.user_id != ? AND pc.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                                ORDER BY pc.created_at DESC LIMIT 5";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$user_id, $user_id]);
                    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $notifications = array_merge($assignments, $comments);
                    
                    usort($notifications, function($a, $b) {
                        $time_a = $a['type'] == 'assignment' ? $a['assigned_at'] : $a['created_at'];
                        $time_b = $b['type'] == 'assignment' ? $b['assigned_at'] : $b['created_at'];
                        return strtotime($time_b) - strtotime($time_a);
                    });
                    
                    if (empty($notifications)): ?>
                        <div class="notification-item no-notifications">
                            <p>No recent notifications</p>
                        </div>
                    <?php else:
                        foreach (array_slice($notifications, 0, 5) as $notification): ?>
                            <div class="notification-item">
                                <?php if ($notification['type'] == 'assignment'): ?>
                                    <div class="notification-icon-type">📋</div>
                                    <div class="notification-content">
                                        <p class="notification-title">New Project Assignment</p>
                                        <p class="notification-text">Assigned to: <strong><?php echo Security::escapeHTML($notification['title']); ?></strong></p>
                                        <p class="notification-meta">Role: <?php echo Security::escapeHTML($notification['role']); ?> • <?php echo date('M j, g:i A', strtotime($notification['assigned_at'])); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="notification-icon-type">💬</div>
                                    <div class="notification-content">
                                        <p class="notification-title">New Comment on <?php echo Security::escapeHTML($notification['title']); ?></p>
                                        <p class="notification-text"><?php echo Security::escapeHTML(substr($notification['comment'], 0, 50) . (strlen($notification['comment']) > 50 ? '...' : '')); ?></p>
                                        <p class="notification-meta">By <?php echo Security::escapeHTML($notification['username']); ?> • <?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; 
                    endif;
                } catch (Exception $e) {
                    echo '<div class="notification-item no-notifications"><p>Unable to load notifications</p></div>';
                }
                ?>
            </div>
        </div>
    </div>
    <div class="user-info">
        <span>Welcome, <?php echo Security::escapeHTML($username ?? $_SESSION['username']); ?> (<?php echo Security::escapeHTML(ucfirst($role ?? $_SESSION['role'])); ?>)</span>
        <a href="../auth/logout.php" class="logout-btn">Logout</a>
    </div>
</div>