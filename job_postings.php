<?php
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Simple HTML escape function
function escape($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

// Simple date format function
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

// Get all job postings
$job_postings = $db->query("SELECT jp.*, u.username as posted_by_name 
                           FROM job_postings jp 
                           LEFT JOIN users u ON jp.posted_by = u.id 
                           ORDER BY jp.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get candidate count
$candidate_count = $db->query("SELECT COUNT(*) as total FROM candidates")->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings - Business Management</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        .job-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .job-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 1rem;
        }
        .job-title {
            color: #333;
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .job-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .meta-item {
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }
        .meta-label {
            font-weight: 600;
            color: #555;
            margin-right: 0.5rem;
        }
        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active {
            background: #e8f5e8;
            color: #2e7d2e;
        }
        .status-closed {
            background: #fee;
            color: #d00;
        }
        .job-description, .job-requirements {
            margin-top: 1.5rem;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.75rem;
        }
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .header-section {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .header-title {
            font-size: 2.5rem;
            margin: 0 0 1rem 0;
            font-weight: 700;
        }
        .breadcrumb {
            margin-bottom: 2rem;
        }
        .breadcrumb a {
            color: #666;
            text-decoration: none;
            margin-right: 0.5rem;
        }
        .breadcrumb a:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="job-container">
        <!-- Header Section -->
        <div class="header-section">
            <h1 class="header-title">💼 Job Postings</h1>
            <p style="font-size: 1.1rem; margin: 0; opacity: 0.9;">All available positions and career opportunities</p>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard.php">← Back to Dashboard</a> | <a href="departments/hr.php">HR Department</a>
        </div>

        <!-- Statistics -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($job_postings); ?></div>
                <div class="stat-label">Total Job Postings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($job_postings, function($job) { return $job['status'] === 'active'; })); ?></div>
                <div class="stat-label">Active Positions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_unique(array_column($job_postings, 'department'))); ?></div>
                <div class="stat-label">Departments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $candidate_count; ?></div>
                <div class="stat-label">Total Candidates</div>
            </div>
        </div>

        <!-- Job Postings List -->
        <?php if (empty($job_postings)): ?>
            <div style="text-align: center; padding: 3rem; background: #f9f9f9; border-radius: 12px;">
                <h3>No job postings available</h3>
                <p>Check back later for new opportunities!</p>
            </div>
        <?php else: ?>
            <?php foreach ($job_postings as $job): ?>
                <div class="job-card">
                    <div class="job-header">
                        <h2 class="job-title"><?php echo escape($job['title']); ?></h2>
                        <span class="status-badge status-<?php echo $job['status']; ?>">
                            <?php echo ucfirst($job['status']); ?>
                        </span>
                    </div>

                    <div class="job-meta">
                        <div class="meta-item">
                            <span class="meta-label">🏢 Department:</span>
                            <?php echo escape($job['department']); ?>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">💰 Salary:</span>
                            <?php echo escape($job['salary_range'] ?? 'Not specified'); ?>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">👤 Posted by:</span>
                            <?php echo escape($job['posted_by_name'] ?? 'System'); ?>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">📅 Posted:</span>
                            <?php echo formatDate($job['created_at']); ?>
                        </div>
                    </div>

                    <?php if ($job['description']): ?>
                        <div class="job-description">
                            <div class="section-title">📋 Job Description</div>
                            <div style="line-height: 1.6; color: #555; white-space: pre-wrap;">
                                <?php echo escape($job['description']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($job['requirements']): ?>
                        <div class="job-requirements">
                            <div class="section-title">✅ Requirements</div>
                            <div style="line-height: 1.6; color: #555; white-space: pre-wrap;">
                                <?php echo escape($job['requirements']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($job['status'] === 'active'): ?>
                        <div style="margin-top: 2rem; text-align: center;">
                            <a href="apply.php?job_id=<?php echo $job['id']; ?>" 
                               class="btn" 
                               style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                                🚀 Apply Now
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Footer Actions -->
        <div style="text-align: center; margin-top: 3rem;">
            <a href="candidates.php" class="btn" style="margin: 0 1rem;">👥 View All Candidates</a>
            <a href="apply.php" class="btn" style="margin: 0 1rem;">📝 Submit Application</a>
        </div>
    </div>
</body>
</html>