<?php
require_once __DIR__ . '/common.php';
require_login();
require_role('HR Personnel');
$user = current_user();

// Handle assessment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assessment'])) {
    $intern_id = intval($_POST['intern_id']);
    $interview_date = $_POST['interview_date'];
    $position_applied = $_POST['position_applied'];
    $business_unit = $_POST['business_unit'];
    $academic = intval($_POST['academic_qualifications']);
    $work_exp = intval($_POST['work_experience']);
    $technical = intval($_POST['technical_knowledge']);
    $industry = intval($_POST['industry_knowledge']);
    $communication = intval($_POST['communication_skills']);
    $growth = intval($_POST['potential_for_growth']);
    $people = intval($_POST['people_management']);
    $culture = intval($_POST['culture_fit']);
    $problem = intval($_POST['problem_solving']);
    $comments = trim($_POST['interviewer_comments'] ?? '');
    $rowComments = [];
    $commentFields = [
        'Academic Qualifications' => $_POST['comment_academic'] ?? '',
        'Relevant Work Experience' => $_POST['comment_work_exp'] ?? '',
        'Technical Knowledge' => $_POST['comment_technical'] ?? '',
        'Industry Knowledge' => $_POST['comment_industry'] ?? '',
        'Communication Skills' => $_POST['comment_communication'] ?? '',
        'Potential for Growth' => $_POST['comment_growth'] ?? '',
        'People Management' => $_POST['comment_people'] ?? '',
        'Culture Fit' => $_POST['comment_culture'] ?? '',
        'Problem Solving' => $_POST['comment_problem'] ?? '',
    ];
    foreach ($commentFields as $title => $text) {
        $trimmed = trim($text);
        if ($trimmed !== '') {
            $rowComments[] = "$title: $trimmed";
        }
    }
    if ($rowComments) {
        $comments = trim($comments . "\n" . implode("\n", $rowComments));
    }
    $status = $_POST['hiring_status'];
    $panel = $_POST['panel_member_name'];
    $salary = $_POST['expected_salary_benefits'];
    $total = $academic + $work_exp + $technical + $industry + $communication + $growth + $people + $culture + $problem;

    $stmt = $pdo->prepare('INSERT INTO employee_profiles (intern_id, interview_date, position_applied, business_unit, academic_qualifications, work_experience, technical_knowledge, industry_knowledge, communication_skills, potential_for_growth, people_management, culture_fit, problem_solving, interviewer_comments, hiring_status, panel_member_name, expected_salary_benefits, total_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$intern_id, $interview_date, $position_applied, $business_unit, $academic, $work_exp, $technical, $industry, $communication, $growth, $people, $culture, $problem, $comments, $status, $panel, $salary, $total]);
    // Update pending_applicants status
    $pdo->prepare('UPDATE pending_applicants SET status = "Interviewed" WHERE intern_id = ?')->execute([$intern_id]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Interview Management</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="dashboard_hr.php">Home</a>
                <a href="dashboard_hr.php#requirements">Manage Requirements</a>
                <a href="dashboard_hr.php#policies">Manage Policies</a>
                <a href="dashboard_hr.php#reviews">Review Submissions</a>
                <a href="dashboard_hr.php#approve">Approve Applicants</a>
                <a href="interview_management.php" class="active">Interview Management</a>
                <a href="schedule_management.php">Schedule Management</a>
                <a href="moa_management.php">MOA Management</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Interview Management</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>
            <section class="section-card">
                <div class="section-tab-list">
                    <button type="button" class="tab-button active" data-tab="pending-panel">Pending Applicants</button>
                    <button type="button" class="tab-button" data-tab="interviewed-panel">Interviewed Candidates</button>
                    <button type="button" class="tab-button" data-tab="assessment-panel">Assessment Form</button>
                    <button type="button" class="tab-button" data-tab="schedule-panel">Online Interview Settings</button>
                </div>
                <div id="pending-panel" class="tab-panel">
                    <div class="section-header">
                        <h2>Pending Applicants</h2>
                    </div>
                    <div id="pending-applicants" class="table-scroll"></div>
                </div>
                <div id="interviewed-panel" class="tab-panel" style="display:none;">
                    <div class="section-header">
                        <h2>Interviewed Candidates</h2>
                    </div>
                    <div id="interviewed-applicants" class="table-scroll"></div>
                </div>
                <div id="assessment-panel" class="tab-panel" style="display:none;">
                    <div class="section-header">
                        <h2>Candidate Assessment Form</h2>
                    </div>
                    <form id="assessment-form" class="compact-form">
                        <input type="hidden" name="assessment" value="1" />
                        <input type="hidden" name="intern_id" required />
                        <div class="form-grid">
                            <div>
                                <label>Candidate Name</label>
                                <input type="text" name="candidate_name" readonly />
                            </div>
                            <div>
                                <label>Interview Date</label>
                                <input type="datetime-local" name="interview_date" required />
                            </div>
                            <div>
                                <label>Position Applied</label>
                                <input type="text" name="position_applied" required />
                            </div>
                            <div>
                                <label>Business Unit</label>
                                <input type="text" name="business_unit" />
                            </div>
                            <div>
                                <label>Source</label>
                                <input type="text" name="source" placeholder="Referral, Job Fair, Online" />
                            </div>
                            <div>
                                <label>Nationality</label>
                                <input type="text" name="nationality" />
                            </div>
                        </div>
                        <div class="assessment-legend">
                            <strong>Rating Scale:</strong>
                            <span>5 - Outstanding</span>
                            <span>4 - Excellent (Exceeds requirements)</span>
                            <span>3 - Competent (Acceptable proficiency)</span>
                            <span>2 - Below Average (Does not meet requirements)</span>
                            <span>1 - Unable to determine or not applicable</span>
                        </div>
                        <table class="assessment-table">
                            <thead>
                                <tr>
                                    <th>Competency</th>
                                    <th>Description</th>
                                    <th>5</th>
                                    <th>4</th>
                                    <th>3</th>
                                    <th>2</th>
                                    <th>1</th>
                                    <th>Interviewer Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Academic Qualifications</td>
                                    <td>Does the candidate have the appropriate educational qualifications or training for this position?</td>
                                    <td><input type="radio" name="academic_qualifications" value="5" checked /></td>
                                    <td><input type="radio" name="academic_qualifications" value="4" /></td>
                                    <td><input type="radio" name="academic_qualifications" value="3" /></td>
                                    <td><input type="radio" name="academic_qualifications" value="2" /></td>
                                    <td><input type="radio" name="academic_qualifications" value="1" /></td>
                                    <td><textarea name="comment_academic" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Relevant Work Experience</td>
                                    <td>Has the candidate acquired similar skills/qualifications throughout the past work experiences?</td>
                                    <td><input type="radio" name="work_experience" value="5" checked /></td>
                                    <td><input type="radio" name="work_experience" value="4" /></td>
                                    <td><input type="radio" name="work_experience" value="3" /></td>
                                    <td><input type="radio" name="work_experience" value="2" /></td>
                                    <td><input type="radio" name="work_experience" value="1" /></td>
                                    <td><textarea name="comment_work_exp" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Technical Knowledge</td>
                                    <td>Does the candidate have the technical skills necessary for this position?</td>
                                    <td><input type="radio" name="technical_knowledge" value="5" checked /></td>
                                    <td><input type="radio" name="technical_knowledge" value="4" /></td>
                                    <td><input type="radio" name="technical_knowledge" value="3" /></td>
                                    <td><input type="radio" name="technical_knowledge" value="2" /></td>
                                    <td><input type="radio" name="technical_knowledge" value="1" /></td>
                                    <td><textarea name="comment_technical" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Industry Knowledge</td>
                                    <td>Please rate if it is compatible with industry or similar.</td>
                                    <td><input type="radio" name="industry_knowledge" value="5" checked /></td>
                                    <td><input type="radio" name="industry_knowledge" value="4" /></td>
                                    <td><input type="radio" name="industry_knowledge" value="3" /></td>
                                    <td><input type="radio" name="industry_knowledge" value="2" /></td>
                                    <td><input type="radio" name="industry_knowledge" value="1" /></td>
                                    <td><textarea name="comment_industry" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Communication Skills</td>
                                    <td>Please rate if responses were readily understood. Articulate, used good grammar & expressed thoughts concisely, professional appearance, body language.</td>
                                    <td><input type="radio" name="communication_skills" value="5" checked /></td>
                                    <td><input type="radio" name="communication_skills" value="4" /></td>
                                    <td><input type="radio" name="communication_skills" value="3" /></td>
                                    <td><input type="radio" name="communication_skills" value="2" /></td>
                                    <td><input type="radio" name="communication_skills" value="1" /></td>
                                    <td><textarea name="comment_communication" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Potential for Growth</td>
                                    <td>Please rate if the candidate has the ingredients for career progression with track record of taking ownership of self-development.</td>
                                    <td><input type="radio" name="potential_for_growth" value="5" checked /></td>
                                    <td><input type="radio" name="potential_for_growth" value="4" /></td>
                                    <td><input type="radio" name="potential_for_growth" value="3" /></td>
                                    <td><input type="radio" name="potential_for_growth" value="2" /></td>
                                    <td><input type="radio" name="potential_for_growth" value="1" /></td>
                                    <td><textarea name="comment_growth" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>People Management</td>
                                    <td>Please rate if the candidate displayed the ability to coach & support others, delegate effectively and empower others to act.</td>
                                    <td><input type="radio" name="people_management" value="5" checked /></td>
                                    <td><input type="radio" name="people_management" value="4" /></td>
                                    <td><input type="radio" name="people_management" value="3" /></td>
                                    <td><input type="radio" name="people_management" value="2" /></td>
                                    <td><input type="radio" name="people_management" value="1" /></td>
                                    <td><textarea name="comment_people" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Culture Fit</td>
                                    <td>Please rate if candidate exhibits core values and expected attitude.</td>
                                    <td><input type="radio" name="culture_fit" value="5" checked /></td>
                                    <td><input type="radio" name="culture_fit" value="4" /></td>
                                    <td><input type="radio" name="culture_fit" value="3" /></td>
                                    <td><input type="radio" name="culture_fit" value="2" /></td>
                                    <td><input type="radio" name="culture_fit" value="1" /></td>
                                    <td><textarea name="comment_culture" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Problem Solving</td>
                                    <td>Does the candidate take responsibilities, likes challenges, and accountable?</td>
                                    <td><input type="radio" name="problem_solving" value="5" checked /></td>
                                    <td><input type="radio" name="problem_solving" value="4" /></td>
                                    <td><input type="radio" name="problem_solving" value="3" /></td>
                                    <td><input type="radio" name="problem_solving" value="2" /></td>
                                    <td><input type="radio" name="problem_solving" value="1" /></td>
                                    <td><textarea name="comment_problem" rows="1"></textarea></td>
                                </tr>
                            </tbody>
                        </table>
                        <label>Additional Interviewer Comments</label>
                        <textarea name="interviewer_comments" rows="3"></textarea>
                        <label>Hiring Status</label>
                        <select name="hiring_status">
                            <option value="Recommended">Recommended</option>
                            <option value="With Reservations">With Reservations</option>
                            <option value="Not Recommended">Not Recommended</option>
                            <option value="Further Interview">Further Interview</option>
                        </select>
                        <label>Panel Member Name</label>
                        <input type="text" name="panel_member_name" />
                        <label>Expected Salary/Benefits</label>
                        <textarea name="expected_salary_benefits" rows="2"></textarea>
                        <button type="submit" class="btn btn-primary">Submit Assessment</button>
                    </form>
                </div>
                <div id="schedule-panel" class="tab-panel" style="display:none;">
                    <div class="section-header">
                        <h2>Online Interview Settings</h2>
                    </div>
                    <form id="schedule-form" class="compact-form">
                        <input type="hidden" name="schedule" value="1" />
                        <label>Intern ID</label>
                        <input type="number" name="intern_id" required readonly />
                        <label>Candidate Name</label>
                        <input type="text" name="candidate_name" readonly />
                        <label>Interview Date</label>
                        <input type="datetime-local" name="interview_date" required />
                        <label>Interview Mode</label>
                        <select name="interview_mode" id="interview-mode">
                            <option value="Online">Online</option>
                            <option value="Face to Face">Face to Face</option>
                        </select>
                        <div id="online-fields">
                            <label>Meeting Link</label>
                            <input type="url" name="interview_link" placeholder="https://meet.example.com/..." />
                        </div>
                        <div id="face-fields" style="display:none;">
                            <label>Face-to-Face Location</label>
                            <input type="text" name="interview_location" placeholder="e.g. Main Clinic, 2nd Floor" />
                        </div>
                        <label>Notification Message</label>
                        <textarea name="notification_message" rows="3">Your interview has been scheduled. Please check the mode and time above.</textarea>
                        <button type="submit" class="btn btn-primary">Save Interview Schedule</button>
                    </form>
                </div>
            </section>
        </main>
    </div>
    <script>
        function setActiveTab(tabId) {
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.style.display = panel.id === tabId ? 'block' : 'none';
            });
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tab === tabId);
            });
        }

        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => setActiveTab(button.dataset.tab));
        });

        // Load interviewed candidates when tab is clicked
        document.querySelector('[data-tab="interviewed-panel"]').addEventListener('click', loadInterviewedApplicants);

        function handleInterviewModeChange() {
            const mode = document.querySelector('#interview-mode').value;
            document.querySelector('#online-fields').style.display = mode === 'Online' ? 'block' : 'none';
            document.querySelector('#face-fields').style.display = mode === 'Face to Face' ? 'block' : 'none';
        }

        document.querySelector('#interview-mode').addEventListener('change', handleInterviewModeChange);
        handleInterviewModeChange();

        function populateAssessmentForm(internId, candidateName) {
            const form = document.querySelector('#assessment-form');
            form.intern_id.value = internId;
            form.candidate_name.value = candidateName;
            setActiveTab('assessment-panel');
        }

        function populateScheduleForm(internId, candidateName) {
            const form = document.querySelector('#schedule-form');
            form.intern_id.value = internId;
            form.candidate_name.value = candidateName;
            setActiveTab('schedule-panel');
        }

        async function loadPendingApplicants() {
            const response = await fetch('api/interviews.php?action=list_pending');
            const data = await response.json();
            const rows = data.applicants.map(app => `
                <tr>
                    <td>${app.name}</td>
                    <td>${app.position_applied}</td>
                    <td>${app.status}</td>
                    <td>${app.interview_date ? new Date(app.interview_date).toLocaleString() : '-'}</td>
                    <td>${app.interview_mode || '-'}</td>
                    <td>${app.interview_mode === 'Online' ? (app.interview_link ? `<a href="${app.interview_link}" target="_blank">Link</a>` : '-') : (app.interview_location || '-')}</td>
                    <td><button class="action-btn assess-btn" data-id="${app.intern_id}" data-name="${app.name}">Assess</button> <button class="action-btn schedule-btn" data-id="${app.intern_id}" data-name="${app.name}">Schedule</button></td>
                </tr>
            `).join('');
            document.querySelector('#pending-applicants').innerHTML = `<table><thead><tr><th>Name</th><th>Position</th><th>Status</th><th>Interview Date</th><th>Mode</th><th>Details</th><th>Action</th></tr></thead><tbody>${rows}</tbody></table>`;
            document.querySelectorAll('.assess-btn').forEach(btn => {
                btn.addEventListener('click', () => populateAssessmentForm(btn.dataset.id, btn.dataset.name));
            });
            document.querySelectorAll('.schedule-btn').forEach(btn => {
                btn.addEventListener('click', () => populateScheduleForm(btn.dataset.id, btn.dataset.name));
            });
        }

        async function loadInterviewedApplicants() {
            const response = await fetch('api/interviews.php?action=list_interviewed');
            const data = await response.json();
            if (!data.applicants || data.applicants.length === 0) {
                document.querySelector('#interviewed-applicants').innerHTML = '<p>No interviewed candidates.</p>';
                return;
            }
            const rows = data.applicants.map(app => `
                <tr>
                    <td>${app.full_name}</td>
                    <td>${app.position_applied}</td>
                    <td>${app.total_rating || 'N/A'}</td>
                    <td>${app.hiring_status || '-'}</td>
                    <td>${app.interview_date ? new Date(app.interview_date).toLocaleDateString() : '-'}</td>
                    <td><button class="action-btn view-profile-btn" data-id="${app.intern_id}" data-name="${app.full_name}">View Profile</button></td>
                </tr>
            `).join('');
            document.querySelector('#interviewed-applicants').innerHTML = `<table><thead><tr><th>Name</th><th>Position</th><th>Total Rating</th><th>Status</th><th>Interview Date</th><th>Action</th></tr></thead><tbody>${rows}</tbody></table>`;
            document.querySelectorAll('.view-profile-btn').forEach(btn => {
                btn.addEventListener('click', () => viewEmployeeProfile(btn.dataset.id, btn.dataset.name));
            });
        }

        async function viewEmployeeProfile(internId, candidateName) {
            // Open profile in new page
            window.location.href = 'employee_profile_view.php?intern_id=' + internId;
        }

        document.querySelector('#assessment-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.target;
            const data = new FormData(form);
            const response = await fetch('interview_management.php', {
                method: 'POST',
                body: data
            });
            if (response.ok) {
                alert('Assessment submitted successfully! Candidate moved to Interviewed Candidates.');
                form.reset();
                await loadPendingApplicants();
                await loadInterviewedApplicants();
                setActiveTab('pending-panel');
            } else {
                alert('Failed to submit assessment.');
            }
        });

        document.querySelector('#schedule-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.target;
            const data = new FormData(form);
            const response = await fetch('api/interviews.php?action=schedule', {
                method: 'POST',
                body: data
            });
            const result = await response.json();
            if (result.success) {
                alert('Interview schedule saved. Intern will be notified by message.');
                form.reset();
                handleInterviewModeChange();
                loadPendingApplicants();
                setActiveTab('pending-panel');
            } else {
                alert(result.message || 'Unable to save schedule.');
            }
        });

        loadPendingApplicants();
    </script>
</body>
</html>