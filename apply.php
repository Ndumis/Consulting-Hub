<?php
// Public External Candidates Application Page
// No authentication required - accessible to external candidates

// Start session for CSRF protection
session_start();

require_once 'config/database.php';
require_once 'config/security.php';
require_once 'includes/functions.php';
require_once 'includes/file_upload.php';

$success_message = '';
$error_message = '';

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$database = new Database();
$db = $database->getConnection();

// Fetch available job postings for the dropdown
$job_postings = $db->query("SELECT * FROM job_postings WHERE status = 'active' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Handle external candidate application submission
if ($_POST && isset($_POST['create_candidate'])) {
    // Add CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error_message = "Invalid request. Please try again.";
    } else {
    try {
        $db->beginTransaction();
        
        // Sanitize basic information
        $first_name = Security::sanitizeInput($_POST['first_name']);
        $last_name = Security::sanitizeInput($_POST['last_name']);
        $email = Security::sanitizeInput($_POST['email']);
        $phone = Security::sanitizeInput($_POST['phone']);
        $job_posting_id = (int)$_POST['job_posting_id'];
        $years_experience = !empty($_POST['years_experience']) ? (int)$_POST['years_experience'] : null;
        $salary_expectation = !empty($_POST['salary_expectation']) ? floatval($_POST['salary_expectation']) : null;
        $availability_date = !empty($_POST['availability_date']) ? Security::sanitizeInput($_POST['availability_date']) : null;
        $preferred_location = Security::sanitizeInput($_POST['preferred_location']);
        $willing_to_relocate = isset($_POST['willing_to_relocate']) ? 't' : 'f';
        $work_authorization = Security::sanitizeInput($_POST['work_authorization']);
        $linkedin_profile = Security::sanitizeInput($_POST['linkedin_profile']);
        $portfolio_website = Security::sanitizeInput($_POST['portfolio_website']);
        $cover_letter = Security::sanitizeInput($_POST['cover_letter']);
        
        // Insert main candidate record (using candidates table to match HR workflow)
        $query = "INSERT INTO candidates (
                    job_posting_id, first_name, last_name, email, phone, cover_letter,
                    salary_expectation, availability_date, preferred_location, willing_to_relocate,
                    work_authorization, linkedin_profile, portfolio_website, years_experience, status
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending') RETURNING id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $job_posting_id, $first_name, $last_name, $email, $phone, $cover_letter,
            $salary_expectation, $availability_date, $preferred_location, $willing_to_relocate,
            $work_authorization, $linkedin_profile, $portfolio_website, $years_experience
        ]);
        
        $candidate_id = $stmt->fetchColumn();
        
        // Handle CV upload with correct API
        if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
            $upload_result = FileUpload::uploadFile($_FILES['resume_file'], $candidate_id);
            if ($upload_result['success']) {
                $resume_filename = $upload_result['filename'];
                // Update candidate with resume filename
                $stmt = $db->prepare("UPDATE candidates SET resume_file = ? WHERE id = ?");
                $stmt->execute([$resume_filename, $candidate_id]);
            } else {
                throw new Exception('CV upload failed: ' . implode(', ', $upload_result['errors']));
            }
        }
        
        // Insert work experience entries
        if (isset($_POST['work_experience']) && is_array($_POST['work_experience'])) {
            foreach ($_POST['work_experience'] as $experience) {
                if (!empty($experience['company_name']) && !empty($experience['position_title'])) {
                    $exp_query = "INSERT INTO candidate_work_experience (
                                    candidate_id, company_name, position_title, start_date, 
                                    end_date, is_current, responsibilities, achievements
                                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $exp_stmt = $db->prepare($exp_query);
                    $exp_stmt->execute([
                        $candidate_id,
                        Security::sanitizeInput($experience['company_name']),
                        Security::sanitizeInput($experience['position_title']),
                        !empty($experience['start_date']) ? Security::sanitizeInput($experience['start_date']) : null,
                        isset($experience['is_current']) ? null : (!empty($experience['end_date']) ? Security::sanitizeInput($experience['end_date']) : null),
                        isset($experience['is_current']) ? 't' : 'f',
                        Security::sanitizeInput($experience['responsibilities'] ?? ''),
                        Security::sanitizeInput($experience['achievements'] ?? '')
                    ]);
                }
            }
        }
        
        // Insert education entries
        if (isset($_POST['education']) && is_array($_POST['education'])) {
            foreach ($_POST['education'] as $education) {
                if (!empty($education['institution_name']) && !empty($education['degree_type'])) {
                    $edu_query = "INSERT INTO candidate_education (
                                    candidate_id, institution_name, degree_type, field_of_study, 
                                    start_year, end_year, is_current, gpa, honors, description
                                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $edu_stmt = $db->prepare($edu_query);
                    $edu_stmt->execute([
                        $candidate_id,
                        Security::sanitizeInput($education['institution_name']),
                        Security::sanitizeInput($education['degree_type']),
                        Security::sanitizeInput($education['field_of_study'] ?? ''),
                        !empty($education['start_year']) ? (int)$education['start_year'] : null,
                        isset($education['is_current']) ? null : (!empty($education['end_year']) ? (int)$education['end_year'] : null),
                        isset($education['is_current']) ? 't' : 'f',
                        !empty($education['gpa']) ? floatval($education['gpa']) : null,
                        Security::sanitizeInput($education['honors'] ?? ''),
                        Security::sanitizeInput($education['description'] ?? '')
                    ]);
                }
            }
        }
        
        $db->commit();
        $success_message = "Application submitted successfully! We will review your application and contact you soon.";
        
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Error submitting application: " . $e->getMessage();
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Position - Business Management System</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .application-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .form-container {
            padding: 40px;
        }
        
        .form-section {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .form-section-title {
            background: #f8f9fa;
            padding: 15px 20px;
            font-weight: 600;
            color: #495057;
            border-bottom: 1px solid #e0e0e0;
            font-size: 1.1rem;
        }
        
        .form-section-content {
            padding: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .dynamic-section {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            float: right;
        }
        
        .add-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .file-upload {
            border: 2px dashed #ced4da;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            transition: border-color 0.3s;
        }
        
        .file-upload:hover {
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .application-container {
                margin: 10px;
            }
            
            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="application-container">
        <div class="header">
            <h1>🚀 Join Our Team</h1>
            <p>Apply for an exciting career opportunity with us</p>
        </div>
        
        <div class="form-container">
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo Security::escapeHTML($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo Security::escapeHTML($error_message); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="candidateForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <!-- Basic Information Section -->
                <div class="form-section">
                    <div class="form-section-title">📋 Basic Information</div>
                    <div class="form-section-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Position of Interest *:</label>
                                <select name="job_posting_id" required>
                                    <option value="">Select Position</option>
                                    <?php foreach ($job_postings as $job): ?>
                                        <option value="<?php echo $job['id']; ?>">
                                            <?php echo Security::escapeHTML($job['title'] . ' - ' . $job['department']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>First Name *:</label>
                                <input type="text" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name *:</label>
                                <input type="text" name="last_name" required>
                            </div>
                            <div class="form-group">
                                <label>Email *:</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label>Phone:</label>
                                <input type="tel" name="phone" placeholder="+27-11-123-4567">
                            </div>
                            <div class="form-group">
                                <label>Years of Experience:</label>
                                <input type="number" name="years_experience" min="0" max="50">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Professional Details Section -->
                <div class="form-section">
                    <div class="form-section-title">💼 Professional Details</div>
                    <div class="form-section-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Expected Salary (ZAR):</label>
                                <input type="number" name="salary_expectation" step="0.01" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>Availability Date:</label>
                                <input type="date" name="availability_date">
                            </div>
                            <div class="form-group">
                                <label>Preferred Location:</label>
                                <input type="text" name="preferred_location" placeholder="Cape Town, Johannesburg, etc.">
                            </div>
                            <div class="form-group">
                                <label>Work Authorization:</label>
                                <select name="work_authorization">
                                    <option value="">Select Status</option>
                                    <option value="citizen">South African Citizen</option>
                                    <option value="permanent_resident">Permanent Resident</option>
                                    <option value="work_permit">Work Permit</option>
                                    <option value="visa_required">Visa Required</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>LinkedIn Profile:</label>
                                <input type="url" name="linkedin_profile" placeholder="https://linkedin.com/in/yourprofile">
                            </div>
                            <div class="form-group">
                                <label>Portfolio/Website:</label>
                                <input type="url" name="portfolio_website" placeholder="https://yourportfolio.com">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>
                                    <input type="checkbox" name="willing_to_relocate" value="1">
                                    Willing to relocate if necessary
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- CV Upload Section -->
                <div class="form-section">
                    <div class="form-section-title">📄 Resume/CV Upload</div>
                    <div class="form-section-content">
                        <div class="file-upload">
                            <input type="file" name="resume_file" accept=".pdf,.doc,.docx" id="cvFile">
                            <p>Upload your Resume/CV (PDF, DOC, DOCX - Max 5MB)</p>
                        </div>
                    </div>
                </div>
                
                <!-- Work Experience Section -->
                <div class="form-section">
                    <div class="form-section-title">💼 Work Experience</div>
                    <div class="form-section-content">
                        <div id="workExperienceContainer">
                            <div class="dynamic-section work-experience-item">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Company Name:</label>
                                        <input type="text" name="work_experience[0][company_name]">
                                    </div>
                                    <div class="form-group">
                                        <label>Position Title:</label>
                                        <input type="text" name="work_experience[0][position_title]">
                                    </div>
                                    <div class="form-group">
                                        <label>Start Date:</label>
                                        <input type="date" name="work_experience[0][start_date]">
                                    </div>
                                    <div class="form-group">
                                        <label>End Date:</label>
                                        <input type="date" name="work_experience[0][end_date]" class="end-date">
                                    </div>
                                    <div class="form-group" style="grid-column: 1 / -1;">
                                        <label>
                                            <input type="checkbox" name="work_experience[0][is_current]" class="current-job">
                                            Currently working here
                                        </label>
                                    </div>
                                    <div class="form-group" style="grid-column: 1 / -1;">
                                        <label>Key Responsibilities:</label>
                                        <textarea name="work_experience[0][responsibilities]" rows="3"></textarea>
                                    </div>
                                    <div class="form-group" style="grid-column: 1 / -1;">
                                        <label>Achievements:</label>
                                        <textarea name="work_experience[0][achievements]" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="add-btn" onclick="addWorkExperience()">+ Add Work Experience</button>
                    </div>
                </div>
                
                <!-- Education Section -->
                <div class="form-section">
                    <div class="form-section-title">🎓 Education</div>
                    <div class="form-section-content">
                        <div id="educationContainer">
                            <div class="dynamic-section education-item">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Institution Name:</label>
                                        <input type="text" name="education[0][institution_name]">
                                    </div>
                                    <div class="form-group">
                                        <label>Degree Type:</label>
                                        <select name="education[0][degree_type]">
                                            <option value="">Select Degree</option>
                                            <option value="bachelor">Bachelor's Degree</option>
                                            <option value="master">Master's Degree</option>
                                            <option value="doctorate">Doctorate/PhD</option>
                                            <option value="diploma">Diploma</option>
                                            <option value="certificate">Certificate</option>
                                            <option value="matric">Matric/Grade 12</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Field of Study:</label>
                                        <input type="text" name="education[0][field_of_study]">
                                    </div>
                                    <div class="form-group">
                                        <label>Start Year:</label>
                                        <input type="number" name="education[0][start_year]" min="1950" max="2030">
                                    </div>
                                    <div class="form-group">
                                        <label>End Year:</label>
                                        <input type="number" name="education[0][end_year]" min="1950" max="2030" class="end-year">
                                    </div>
                                    <div class="form-group">
                                        <label>GPA/Grade:</label>
                                        <input type="text" name="education[0][gpa]" placeholder="3.8 or 85%">
                                    </div>
                                    <div class="form-group" style="grid-column: 1 / -1;">
                                        <label>
                                            <input type="checkbox" name="education[0][is_current]" class="current-study">
                                            Currently studying here
                                        </label>
                                    </div>
                                    <div class="form-group" style="grid-column: 1 / -1;">
                                        <label>Honors/Awards:</label>
                                        <input type="text" name="education[0][honors]" placeholder="Magna Cum Laude, Dean's List, etc.">
                                    </div>
                                    <div class="form-group" style="grid-column: 1 / -1;">
                                        <label>Additional Details:</label>
                                        <textarea name="education[0][description]" rows="2" placeholder="Thesis topic, relevant coursework, etc."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="add-btn" onclick="addEducation()">+ Add Education</button>
                    </div>
                </div>
                
                <!-- Cover Letter Section -->
                <div class="form-section">
                    <div class="form-section-title">📝 Cover Letter</div>
                    <div class="form-section-content">
                        <div class="form-group">
                            <label>Why are you interested in this position?</label>
                            <textarea name="cover_letter" rows="6" placeholder="Tell us about yourself, your interest in the position, and what you can bring to our team..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" name="create_candidate" class="btn">🚀 Submit Application</button>
                    <a href="index.php" class="btn btn-secondary">← Back to Home</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let workExperienceCount = 1;
        let educationCount = 1;
        
        function addWorkExperience() {
            const container = document.getElementById('workExperienceContainer');
            const newSection = document.createElement('div');
            newSection.className = 'dynamic-section work-experience-item';
            newSection.innerHTML = `
                <button type="button" class="remove-btn" onclick="removeSection(this)">Remove</button>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Company Name:</label>
                        <input type="text" name="work_experience[${workExperienceCount}][company_name]">
                    </div>
                    <div class="form-group">
                        <label>Position Title:</label>
                        <input type="text" name="work_experience[${workExperienceCount}][position_title]">
                    </div>
                    <div class="form-group">
                        <label>Start Date:</label>
                        <input type="date" name="work_experience[${workExperienceCount}][start_date]">
                    </div>
                    <div class="form-group">
                        <label>End Date:</label>
                        <input type="date" name="work_experience[${workExperienceCount}][end_date]" class="end-date">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>
                            <input type="checkbox" name="work_experience[${workExperienceCount}][is_current]" class="current-job">
                            Currently working here
                        </label>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Key Responsibilities:</label>
                        <textarea name="work_experience[${workExperienceCount}][responsibilities]" rows="3"></textarea>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Achievements:</label>
                        <textarea name="work_experience[${workExperienceCount}][achievements]" rows="2"></textarea>
                    </div>
                </div>
            `;
            container.appendChild(newSection);
            workExperienceCount++;
        }
        
        function addEducation() {
            const container = document.getElementById('educationContainer');
            const newSection = document.createElement('div');
            newSection.className = 'dynamic-section education-item';
            newSection.innerHTML = `
                <button type="button" class="remove-btn" onclick="removeSection(this)">Remove</button>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Institution Name:</label>
                        <input type="text" name="education[${educationCount}][institution_name]">
                    </div>
                    <div class="form-group">
                        <label>Degree Type:</label>
                        <select name="education[${educationCount}][degree_type]">
                            <option value="">Select Degree</option>
                            <option value="bachelor">Bachelor's Degree</option>
                            <option value="master">Master's Degree</option>
                            <option value="doctorate">Doctorate/PhD</option>
                            <option value="diploma">Diploma</option>
                            <option value="certificate">Certificate</option>
                            <option value="matric">Matric/Grade 12</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Field of Study:</label>
                        <input type="text" name="education[${educationCount}][field_of_study]">
                    </div>
                    <div class="form-group">
                        <label>Start Year:</label>
                        <input type="number" name="education[${educationCount}][start_year]" min="1950" max="2030">
                    </div>
                    <div class="form-group">
                        <label>End Year:</label>
                        <input type="number" name="education[${educationCount}][end_year]" min="1950" max="2030" class="end-year">
                    </div>
                    <div class="form-group">
                        <label>GPA/Grade:</label>
                        <input type="text" name="education[${educationCount}][gpa]" placeholder="3.8 or 85%">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>
                            <input type="checkbox" name="education[${educationCount}][is_current]" class="current-study">
                            Currently studying here
                        </label>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Honors/Awards:</label>
                        <input type="text" name="education[${educationCount}][honors]" placeholder="Magna Cum Laude, Dean's List, etc.">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Additional Details:</label>
                        <textarea name="education[${educationCount}][description]" rows="2" placeholder="Thesis topic, relevant coursework, etc."></textarea>
                    </div>
                </div>
            `;
            container.appendChild(newSection);
            educationCount++;
        }
        
        function removeSection(button) {
            button.parentElement.remove();
        }
        
        // Handle current job/study checkboxes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('current-job')) {
                const endDateInput = e.target.closest('.dynamic-section').querySelector('.end-date');
                if (e.target.checked) {
                    endDateInput.disabled = true;
                    endDateInput.value = '';
                } else {
                    endDateInput.disabled = false;
                }
            }
            
            if (e.target.classList.contains('current-study')) {
                const endYearInput = e.target.closest('.dynamic-section').querySelector('.end-year');
                if (e.target.checked) {
                    endYearInput.disabled = true;
                    endYearInput.value = '';
                } else {
                    endYearInput.disabled = false;
                }
            }
        });
        
        // Form validation
        document.getElementById('candidateForm').addEventListener('submit', function(e) {
            const workItems = document.querySelectorAll('.work-experience-item');
            const educationItems = document.querySelectorAll('.education-item');
            
            let hasWorkExperience = false;
            let hasEducation = false;
            
            // Check if at least one work experience has company name
            workItems.forEach(item => {
                const companyName = item.querySelector('input[name*="[company_name]"]').value.trim();
                if (companyName) hasWorkExperience = true;
            });
            
            // Check if at least one education has institution name
            educationItems.forEach(item => {
                const institutionName = item.querySelector('input[name*="[institution_name]"]').value.trim();
                if (institutionName) hasEducation = true;
            });
            
            if (!hasWorkExperience && !hasEducation) {
                alert('Please fill in at least one work experience or education entry.');
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>