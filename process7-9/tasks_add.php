<?php
require_once "config/database.php";
require_once "includes/common.php";
require_role("hr_personnel");

$conn->query(
    "CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )"
);

$columnCheck = $conn->query("SHOW COLUMNS FROM tasks LIKE 'attachment_path'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE tasks ADD COLUMN attachment_path VARCHAR(255) NULL AFTER deadline");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $employeeName = sanitize_input($_POST["employee_name"] ?? "");
    $taskName = sanitize_input($_POST["task_name"] ?? "");
    $description = sanitize_input($_POST["description"] ?? "");
    $status = sanitize_input($_POST["status"] ?? "");
    $startDate = sanitize_input($_POST["start_date"] ?? "");
    $deadline = sanitize_input($_POST["deadline"] ?? "");
    $attachmentPath = null;

    if ($employeeName === "" || $taskName === "" || $description === "" || $status === "" || $startDate === "" || $deadline === "") {
        $error = "All fields are required.";
    } elseif (!in_array($status, ["To Do", "In Progress", "Done"], true)) {
        $error = "Invalid status selected.";
    } elseif (strtotime($deadline) < strtotime($startDate)) {
        $error = "Deadline must be on or after start date.";
    } else {
        if (!empty($_FILES["task_file"]["name"])) {
            $allowedExtensions = ["pdf", "doc", "docx", "xlsx", "xls", "png", "jpg", "jpeg", "txt"];
            $fileName = $_FILES["task_file"]["name"];
            $fileTmp = $_FILES["task_file"]["tmp_name"];
            $fileError = (int) $_FILES["task_file"]["error"];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileError !== UPLOAD_ERR_OK) {
                $error = "Failed to upload task file.";
            } elseif (!in_array($fileExtension, $allowedExtensions, true)) {
                $error = "Invalid file type. Allowed: pdf, doc, docx, xlsx, xls, png, jpg, jpeg, txt.";
            } else {
                $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "task_files";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $safeFileName = uniqid("task_", true) . "." . $fileExtension;
                $targetFile = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;

                if (!move_uploaded_file($fileTmp, $targetFile)) {
                    $error = "Could not save the uploaded file.";
                } else {
                    $attachmentPath = "uploads/task_files/" . $safeFileName;
                }
            }
        }

        if ($error === "") {
            $stmt = $conn->prepare("INSERT INTO tasks (employee_name, task_name, description, status, start_date, deadline, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                $error = "Database error while saving task: " . $conn->error;
            } else {
                $stmt->bind_param("sssssss", $employeeName, $taskName, $description, $status, $startDate, $deadline, $attachmentPath);
                $stmt->execute();
                $newTaskId = (int) $conn->insert_id;
                $stmt->close();

                $internUsers = $conn->query("SELECT user_id FROM users WHERE role = 'intern'");
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");

                if ($internUsers && $notifStmt) {
                    $title = "New Task Assigned";
                    $message = "Task #" . $newTaskId . " - " . $taskName . " was posted by HR for " . $employeeName . ".";

                    while ($intern = $internUsers->fetch_assoc()) {
                        $internId = (int) $intern["user_id"];
                        $notifStmt->bind_param("iss", $internId, $title, $message);
                        $notifStmt->execute();
                    }
                    $notifStmt->close();
                }

                header("Location: tasks_list.php?message=Task added and interns were notified.");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Task</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="card role-admin">
            <div class="nav">
                <a href="admin_process7_8.php">Back to Workspace</a>
                <a href="tasks_list.php">Task List</a>
            </div>

            <span class="badge badge-admin">HR PERSONNEL</span>
            <h1>Add Task</h1>
            <?php if ($error !== ""): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
        <label>Employee Name:</label><br>
        <input type="text" name="employee_name" required><br><br>

        <label>Task:</label><br>
        <input type="text" name="task_name" required><br><br>

        <label>Description:</label><br>
        <textarea name="description" required></textarea><br><br>

        <label>Status:</label><br>
        <select name="status" required>
            <option value="">Select Status</option>
            <option value="To Do">To Do</option>
            <option value="In Progress">In Progress</option>
            <option value="Done">Done</option>
        </select><br><br>

        <label>Start Date:</label><br>
        <input type="date" name="start_date" required><br><br>

        <label>Deadline:</label><br>
        <input type="date" name="deadline" required><br><br>

        <label>Task File (Optional):</label><br>
        <input type="file" name="task_file" accept=".pdf,.doc,.docx,.xlsx,.xls,.png,.jpg,.jpeg,.txt"><br><br>

        <button type="submit">Save Task</button>
            </form>
        </div>
    </div>
</body>
</html>
