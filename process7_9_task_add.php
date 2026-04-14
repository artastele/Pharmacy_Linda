<?php
require_once __DIR__ . '/process7_9_helpers.php';
require_login();
require_role('HR Personnel');
ensure_process7_9_tasks_table();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeName = sanitize_text($_POST['employee_name'] ?? '');
    $taskName = sanitize_text($_POST['task_name'] ?? '');
    $description = sanitize_text($_POST['description'] ?? '');
    $status = sanitize_text($_POST['status'] ?? '');
    $startDate = sanitize_text($_POST['start_date'] ?? '');
    $deadline = sanitize_text($_POST['deadline'] ?? '');
    $attachmentPath = null;

    if ($employeeName === '' || $taskName === '' || $description === '' || $status === '' || $startDate === '' || $deadline === '') {
        $error = 'All fields are required.';
    } elseif (!in_array($status, ['To Do', 'In Progress', 'Done'], true)) {
        $error = 'Invalid status selected.';
    } elseif (strtotime($deadline) < strtotime($startDate)) {
        $error = 'Deadline must be on or after the start date.';
    } else {
        if (!empty($_FILES['task_file']['name'])) {
            $allowedExtensions = ['pdf', 'doc', 'docx', 'xlsx', 'xls', 'png', 'jpg', 'jpeg', 'txt'];
            $fileName = $_FILES['task_file']['name'];
            $fileTmp = $_FILES['task_file']['tmp_name'];
            $fileError = (int) ($_FILES['task_file']['error'] ?? UPLOAD_ERR_OK);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileError !== UPLOAD_ERR_OK) {
                $error = 'Failed to upload task file.';
            } elseif (!in_array($fileExtension, $allowedExtensions, true)) {
                $error = 'Invalid file type. Allowed: pdf, doc, docx, xlsx, xls, png, jpg, jpeg, txt.';
            } else {
                $uploadDir = __DIR__ . '/uploads/task_files';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $safeFileName = uniqid('task_', true) . '.' . $fileExtension;
                $targetFile = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;
                if (!move_uploaded_file($fileTmp, $targetFile)) {
                    $error = 'Could not save the uploaded file.';
                } else {
                    $attachmentPath = 'uploads/task_files/' . $safeFileName;
                }
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare('INSERT INTO tasks (employee_name, task_name, description, status, start_date, deadline, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$employeeName, $taskName, $description, $status, $startDate, $deadline, $attachmentPath]);
            $taskId = (int) $pdo->lastInsertId();

            $interns = get_intern_users();
            if (count($interns) > 0) {
                $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
                $title = 'New Task Assigned';
                $message = "Task #{$taskId} - {$taskName} was posted for {$employeeName}.";
                foreach ($interns as $intern) {
                    $notifStmt->execute([$intern['id'], $title, $message]);
                }
            }

            header('Location: process7_9_tasks.php?message=' . urlencode('Task added and interns were notified.'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Task</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="process7_9_tasks.php">Back to Tasks</a>
                <a href="dashboard_hr.php">HR Dashboard</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Add Task</h1>
                <div>Welcome, <?php echo sanitize_text(current_user()['full_name']); ?></div>
            </header>
            <section class="section-card">
                <?php if ($error !== ''): ?>
                    <div class="message error"><?php echo sanitize_text($error); ?></div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data" class="compact-form">
                    <label>Employee Name</label>
                    <input type="text" name="employee_name" value="<?php echo sanitize_text($_POST['employee_name'] ?? ''); ?>" required />

                    <label>Task Name</label>
                    <input type="text" name="task_name" value="<?php echo sanitize_text($_POST['task_name'] ?? ''); ?>" required />

                    <label>Description</label>
                    <textarea name="description" rows="4" required><?php echo sanitize_text($_POST['description'] ?? ''); ?></textarea>

                    <label>Status</label>
                    <select name="status" required>
                        <option value="">Choose status</option>
                        <option value="To Do"<?php echo (($_POST['status'] ?? '') === 'To Do') ? ' selected' : ''; ?>>To Do</option>
                        <option value="In Progress"<?php echo (($_POST['status'] ?? '') === 'In Progress') ? ' selected' : ''; ?>>In Progress</option>
                        <option value="Done"<?php echo (($_POST['status'] ?? '') === 'Done') ? ' selected' : ''; ?>>Done</option>
                    </select>

                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo sanitize_text($_POST['start_date'] ?? ''); ?>" required />

                    <label>Deadline</label>
                    <input type="date" name="deadline" value="<?php echo sanitize_text($_POST['deadline'] ?? ''); ?>" required />

                    <label>Task File (Optional)</label>
                    <input type="file" name="task_file" accept=".pdf,.doc,.docx,.xlsx,.xls,.png,.jpg,.jpeg,.txt" />

                    <button type="submit" class="btn btn-primary">Save Task</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
