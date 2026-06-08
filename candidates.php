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

// Get all candidates with job information
$candidates = $db->query("SELECT c.*, jp.title as job_title, jp.department as job_department
                         FROM candidates c 
                         JOIN job_postings jp ON c.job_posting_id = jp.id 
                         ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Applications - Business Management</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        .candidate-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .candidate-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .candidate-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 1rem;
        }
        .candidate-name {
            color: #333;
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
        }
        .candidate-meta {
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
        .status-applied { background: #e3f2fd; color: #1565c0; }
        .status-review { background: #fff3e0; color: #ef6c00; }
        .status-interview_scheduled { background: #f3e5f5; color: #7b1fa2; }
        .status-offer_made { background: #e8f5e8; color: #2e7d2e; }
        .status-rejected { background: #ffebee; color: #c62828; }
        .status-pending { background: #f5f5f5; color: #616161; }
        .status-screening { background: #fff8e1; color: #f57c00; }
        
        .cover-letter {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            border-left: 4px solid #667eea;
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
    <div class="candidate-container">
        <!-- Header Section -->
        <div class="header-section">
            <h1 class="header-title">👥 Candidate Applications</h1>
            <p style="font-size: 1.1rem; margin: 0; opacity: 0.9;">All job applications and candidate profiles</p>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard.php">← Back to Dashboard</a> | <a href="departments/hr.php">HR Department</a> | <a href="job_postings.php">Job Postings</a>
        </div>

        <!-- Statistics -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($candidates); ?></div>
                <div class="stat-label">Total Candidates</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($candidates, function($c) { return $c['status'] === 'applied'; })); ?></div>
                <div class="stat-label">New Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($candidates, function($c) { return $c['status'] === 'interview_scheduled'; })); ?></div>
                <div class="stat-label">Interviews Scheduled</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_unique(array_column($candidates, 'job_department'))); ?></div>
                <div class="stat-label">Departments</div>
            </div>
        </div>

        <!-- Candidates List -->
        <?php if (empty($candidates)): ?>
            <div style="text-align: center; padding: 3rem; background: #f9f9f9; border-radius: 12px;">
                <h3>No candidate applications yet</h3>
                <p>Applications will appear here as they are submitted.</p>
            </div>
        <?php else: ?>
            <?php foreach ($candidates as $candidate): ?>
                <div class="candidate-card">
                    <div class="candidate-header">
                        <div>
                            <h2 class="candidate-name">
                                <?php echo escape($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                            </h2>
                            <div style="color: #666; font-size: 1rem;">
                                Applied for: <strong><?php echo escape($candidate['job_title']); ?></strong> (<?php echo escape($candidate['job_department']); ?>)
                            </div>
                        </div>
                        <span class="status-badge status-<?php echo $candidate['status']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $candidate['status'])); ?>
                        </span>
                    </div>

                    <div class="candidate-meta">
                        <div class="meta-item">
                            <span class="meta-label">📧 Email:</span>
                            <?php echo escape($candidate['email']); ?>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">📱 Phone:</span>
                            <?php echo escape($candidate['phone'] ?? 'Not provided'); ?>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">🎓 Experience:</span>
                            <?php echo $candidate['years_experience'] ? $candidate['years_experience'] . ' years' : 'Not specified'; ?>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">💰 Salary Expectation:</span>
                            <?php echo $candidate['salary_expectation'] ? 'R' . number_format($candidate['salary_expectation']) : 'Not specified'; ?>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">📅 Applied:</span>
                            <?php echo formatDate($candidate['created_at']); ?>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">🌍 Location:</span>
                            <?php echo escape($candidate['preferred_location'] ?? 'Not specified'); ?>
                        </div>
                    </div>

                    <?php if ($candidate['cover_letter']): ?>
                        <div class="cover-letter">
                            <div class="section-title">✍️ Cover Letter</div>
                            <div style="line-height: 1.6; font-style: italic; white-space: pre-wrap;">
                                <?php echo escape($candidate['cover_letter']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Footer Actions -->
        <div style="text-align: center; margin-top: 3rem;">
            <a href="job_postings.php" class="btn" style="margin: 0 1rem;">💼 View Job Postings</a>
            <a href="apply.php" class="btn" style="margin: 0 1rem;">📝 Submit New Application</a>
        </div>
    </div>
</body>
</html>