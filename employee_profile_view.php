<?php
require_once __DIR__ . '/common.php';
require_login();
require_role('HR Personnel');

$intern_id = intval($_GET['intern_id'] ?? 0);
if (!$intern_id) {
    header('Location: interview_management.php');
    exit;
}

// Fetch profile data
$stmt = $pdo->prepare('SELECT ep.*, u.full_name, u.email FROM employee_profiles ep JOIN users u ON ep.intern_id = u.id WHERE ep.intern_id = ? LIMIT 1');
$stmt->execute([$intern_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    header('Location: interview_management.php');
    exit;
}

// Fetch MOA record
$stmt = $pdo->prepare('SELECT ir.* FROM internship_records ir WHERE ir.intern_id = ? LIMIT 1');
$stmt->execute([$intern_id]);
$moa_record = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle MOA upload to intern and internship record updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_moa_to_intern'])) {
    $company_rep_name = trim($_POST['company_rep_name'] ?? '');
    $school_name = trim($_POST['school_name'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $required_hours = intval($_POST['required_hours'] ?? 0);
    $upload_error = '';

    if (!isset($_FILES['moa_file']) || $_FILES['moa_file']['error'] !== UPLOAD_ERR_OK) {
        $upload_error = 'Please choose a MOA file to upload.';
    } else {
        $file = $_FILES['moa_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'doc', 'docx'])) {
            $upload_error = 'Only PDF, DOC, and DOCX files are allowed.';
        } elseif ($file['size'] > 5242880) {
            $upload_error = 'File is too large. Maximum size is 5MB.';
        }
    }

    if ($upload_error === '') {
        $upload_dir = __DIR__ . '/uploads/moa/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = 'hr_moa_' . $intern_id . '_' . time() . '.' . $ext;
        $target_path = $upload_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $moa_file_path = 'uploads/moa/' . $filename;
            if (!$moa_record) {
                $stmt = $pdo->prepare('INSERT INTO internship_records (intern_id, company_rep_name, school_name, end_date, required_hours, moa_file_path, is_moa_signed, verification_status) VALUES (?, ?, ?, ?, ?, ?, 0, ? )');
                $stmt->execute([$intern_id, $company_rep_name, $school_name, $end_date ?: null, $required_hours, $moa_file_path, 'Pending']);
            } else {
                $stmt = $pdo->prepare('UPDATE internship_records SET company_rep_name = ?, school_name = ?, end_date = ?, required_hours = ?, moa_file_path = ?, is_moa_signed = 0, verification_status = ? WHERE intern_id = ?');
                $stmt->execute([$company_rep_name, $school_name, $end_date ?: null, $required_hours, $moa_file_path, 'Pending', $intern_id]);
            }

            $_SESSION['moa_upload_success'] = 'MOA uploaded to intern. Waiting for signed MOA return.';
        } else {
            $_SESSION['moa_upload_error'] = 'Unable to save the uploaded MOA file. Please try again.';
        }
    } else {
        $_SESSION['moa_upload_error'] = $upload_error;
    }

    header('Location: employee_profile_view.php?intern_id=' . $intern_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Employee Profile - <?php echo sanitize_text($profile['full_name']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="dashboard_hr.php">Home</a>
                <a href="interview_management.php" class="active">Interview Management</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Employee Profile</h1>
                <div>Welcome, <?php echo sanitize_text(current_user()['full_name']); ?></div>
            </header>

            <div class="profile-container">
                <?php if (isset($_SESSION['moa_uploaded_success'])): ?>
                    <div class="alert alert-success">MOA file uploaded successfully.</div>
                    <?php unset($_SESSION['moa_uploaded_success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['moa_upload_error'])): ?>
                    <div class="alert alert-error"><?php echo sanitize_text($_SESSION['moa_upload_error']); ?></div>
                    <?php unset($_SESSION['moa_upload_error']); ?>
                <?php endif; ?>

                <div class="profile-header">
                    <h1><?php echo sanitize_text($profile['full_name']); ?></h1>
                    <div class="profile-info">
                        <div class="profile-info-item">
                            <strong>Position Applied</strong>
                            <span><?php echo sanitize_text($profile['position_applied'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <strong>Business Unit</strong>
                            <span><?php echo sanitize_text($profile['business_unit'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <strong>Interview Date</strong>
                            <span><?php echo $profile['interview_date'] ? date('M d, Y', strtotime($profile['interview_date'])) : 'N/A'; ?></span>
                        </div>
                        <div class="profile-info-item">
                            <strong>Email</strong>
                            <span><?php echo sanitize_text($profile['email']); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <strong>Total Rating</strong>
                            <span><?php echo $profile['total_rating']; ?> / 45</span>
                        </div>
                        <div class="profile-info-item">
                            <strong>Hiring Status</strong>
                            <span><?php echo sanitize_text($profile['hiring_status']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-content">
                    <!-- Competency Ratings Section -->
                    <div class="section">
                        <h2 class="section-title">Competency Ratings</h2>
                        <div class="rating-grid">
                            <div class="rating-item">
                                <strong>Academic Qualifications</strong>
                                <div class="rating-value"><?php echo $profile['academic_qualifications']; ?><span>/5</span></div>
                            </div>
                            <div class="rating-item">
                                <strong>Relevant Work Experience</strong>
                                <div class="rating-value"><?php echo $profile['work_experience']; ?><span>/5</span></div>
                            </div>
                            <div class="rating-item">
                                <strong>Technical Knowledge</strong>
                                <div class="rating-value"><?php echo $profile['technical_knowledge']; ?><span>/5</span></div>
                            </div>
                            <div class="rating-item">
                                <strong>Industry Knowledge</strong>
                                <div class="rating-value"><?php echo $profile['industry_knowledge']; ?><span>/5</span></div>
                            </div>
                            <div class="rating-item">
                                <strong>Communication Skills</strong>
                                <div class="rating-value"><?php echo $profile['communication_skills']; ?><span>/5</span></div>
                            </div>
                            <div class="rating-item">
                                <strong>Potential for Growth</strong>
                                <div class="rating-value"><?php echo $profile['potential_for_growth']; ?><span>/5</span></div>
                            </div>
                            <div class="rating-item">
                                <strong>People Management</strong>
                                <div class="rating-value"><?php echo $profile['people_management']; ?><span>/5</span></div>
                            </div>
                            <div class="rating-item">
                                <strong>Culture Fit</strong>
                                <div class="rating-value"><?php echo $profile['culture_fit']; ?><span>/5</span></div>
                            </div>
                            <div class="rating-item">
                                <strong>Problem Solving</strong>
                                <div class="rating-value"><?php echo $profile['problem_solving']; ?><span>/5</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- MOA Management Section -->
                    <div class="section moa-section">
                        <h2 class="section-title">MOA Management</h2>
                        <p>Upload the MOA file to send to the intern for signing. The record stays pending until the intern returns the signed copy.</p>

                        <?php if (isset($_SESSION['moa_upload_success'])): ?>
                            <div class="alert alert-success"><?php echo sanitize_text($_SESSION['moa_upload_success']); ?></div>
                            <?php unset($_SESSION['moa_upload_success']); ?>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['moa_upload_error'])): ?>
                            <div class="alert alert-error"><?php echo sanitize_text($_SESSION['moa_upload_error']); ?></div>
                            <?php unset($_SESSION['moa_upload_error']); ?>
                        <?php endif; ?>

                        <div class="section-card">
                            <h3>Internship Record</h3>
                            <form method="POST" enctype="multipart/form-data" class="compact-form">
                                <input type="hidden" name="upload_moa_to_intern" value="1" />
                                <label>Company Rep Name</label>
                                <input type="text" name="company_rep_name" value="<?php echo sanitize_text($moa_record['company_rep_name'] ?? ''); ?>" />

                                <label>School Name</label>
                                <input type="text" name="school_name" value="<?php echo sanitize_text($moa_record['school_name'] ?? ''); ?>" />

                                <label>End Date</label>
                                <input type="date" name="end_date" value="<?php echo sanitize_text($moa_record['end_date'] ?? ''); ?>" />

                                <label>Required Hours</label>
                                <input type="number" name="required_hours" min="0" value="<?php echo sanitize_text($moa_record['required_hours'] ?? ''); ?>" />

                                <label>MOA File to send to intern</label>
                                <input type="file" name="moa_file" accept=".pdf,.doc,.docx" />

                                <button type="submit" class="btn btn-primary">Upload MOA to Intern</button>
                            </form>
                        </div>

                        <?php if ($moa_record): ?>
                            <div class="moa-status">
                                <div class="moa-status-item">
                                    <label>MOA Signed Status:</label>
                                    <span class="status-badge <?php echo $moa_record['is_moa_signed'] ? 'signed' : 'pending'; ?>">
                                        <?php echo $moa_record['is_moa_signed'] ? '✓ Signed & Uploaded' : '○ Pending Signature'; ?>
                                    </span>
                                </div>
                                <div class="moa-status-item">
                                    <label>Notarization Status:</label>
                                    <span class="status-badge <?php echo $moa_record['is_notarized'] ? 'signed' : 'pending'; ?>">
                                        <?php echo $moa_record['is_notarized'] ? '✓ Notarized' : '○ Pending'; ?>
                                    </span>
                                </div>
                                <div class="moa-status-item">
                                    <label>Verification Status:</label>
                                    <span class="status-badge <?php echo $moa_record['verification_status'] === 'Verified' ? 'verified' : 'pending'; ?>">
                                        <?php echo sanitize_text($moa_record['verification_status']); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($moa_record['is_moa_signed'] && !empty($moa_record['moa_file_path'])): ?>
                                <div class="section-card">
                                    <strong>Uploaded Signed MOA:</strong><br>
                                    <a href="<?php echo sanitize_text($moa_record['moa_file_path']); ?>" class="btn btn-secondary">
                                        📥 Download Signed MOA
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="section-card">
                                    <strong>Signed MOA not yet returned</strong><br>
                                    <span>Please wait for the intern to upload the signed MOA. The download link will appear once the signed copy is recorded in the database.</span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="moa-actions">
                            <?php if ($moa_record && $moa_record['is_moa_signed'] && $moa_record['verification_status'] !== 'Verified'): ?>
                                <button type="button" class="btn btn-success" onclick="verifyMOA(<?php echo $intern_id; ?>)">✓ Verify MOA</button>
                            <?php endif; ?>
                            <a href="interview_management.php" class="btn btn-secondary">Back to Candidates</a>
                        </div>
                    </div>

                    <!-- Interviewer Comments Section -->
                    <div class="section">
                        <h2 class="section-title">Interviewer Comments</h2>
                        <div class="comments-box">
                            <?php echo sanitize_text($profile['interviewer_comments'] ?? 'No comments provided.'); ?>
                        </div>
                    </div>

                    <!-- Additional Info Section -->
                    <div class="section">
                        <h2 class="section-title">Additional Information</h2>
                        <div class="profile-info">
                            <div>
                                <strong>Panel Member:</strong><br>
                                <span><?php echo sanitize_text($profile['panel_member_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div>
                                <strong>Expected Salary/Benefits:</strong><br>
                                <span><?php echo sanitize_text($profile['expected_salary_benefits'] ?? 'Not specified'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="btn-group">
                        <a href="interview_management.php" class="btn btn-secondary">← Back to Candidates</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        async function verifyMOA(internId) {
            if (!confirm('Are you sure you want to verify this MOA?')) {
                return;
            }
            
            const response = await fetch('api/interviews.php?action=verify_moa', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'intern_id=' + internId
            });
            
            const data = await response.json();
            if (data.success) {
                alert('MOA has been verified successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unable to verify MOA'));
            }
        }

    </script>
</body>
</html>
