<?php
require_once "config/database.php";
require_once "includes/common.php";
require_role("hr_personnel");

$columnCheck = $conn->query("SHOW COLUMNS FROM tasks LIKE 'attachment_path'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE tasks ADD COLUMN attachment_path VARCHAR(255) NULL AFTER deadline");
}

$taskId = isset($_GET["task_id"]) ? (int) $_GET["task_id"] : 0;
if ($taskId <= 0) {
    header("Location: tasks_list.php?message=Invalid task selected.");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $employeeName = sanitize_input($_POST["employee_name"] ?? "");
    $taskName = sanitize_input($_POST["task_name"] ?? "");
    $description = sanitize_input($_POST["description"] ?? "");
    $status = sanitize_input($_POST["status"] ?? "");
    $startDate = sanitize_input($_POST["start_date"] ?? "");
    $deadline = sanitize_input($_POST["deadline"] ?? "");
    $currentAttachment = sanitize_input($_POST["current_attachment"] ?? "");
    $attachmentPath = $currentAttachment;

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
            $stmt = $conn->prepare("UPDATE tasks SET employee_name = ?, task_name = ?, description = ?, status = ?, start_date = ?, deadline = ?, attachment_path = ? WHERE task_id = ?");
            $stmt->bind_param("sssssssi", $employeeName, $taskName, $description, $status, $startDate, $deadline, $attachmentPath, $taskId);
            $stmt->execute();
            $stmt->close();

            header("Location: tasks_list.php?message=Task updated successfully.");
            exit;
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM tasks WHERE task_id = ?");
$stmt->bind_param("i", $taskId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) {
    header("Location: tasks_list.php?message=Task not found.");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="card role-admin">
            <div class="nav">
                <a href="tasks_list.php">Back to Task List</a>
            </div>

            <span class="badge badge-admin">HR PERSONNEL</span>
            <h1>Edit Task</h1>
            <?php if ($error !== ""): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
        <label>Task ID:</label><br>
        <input type="text" value="<?php echo (int) $task["task_id"]; ?>" disabled><br><br>
        <input type="hidden" name="current_attachment" value="<?php echo htmlspecialchars($task["attachment_path"] ?? ""); ?>">

        <label>Employee Name:</label><br>
        <input type="text" name="employee_name" value="<?php echo htmlspecialchars($task["employee_name"]); ?>" required><br><br>

        <label>Task:</label><br>
        <input type="text" name="task_name" value="<?php echo htmlspecialchars($task["task_name"]); ?>" required><br><br>

        <label>Description:</label><br>
        <textarea name="description" required><?php echo htmlspecialchars($task["description"]); ?></textarea><br><br>

        <label>Status:</label><br>
        <select name="status" required>
            <option value="To Do" <?php echo $task["status"] === "To Do" ? "selected" : ""; ?>>To Do</option>
            <option value="In Progress" <?php echo $task["status"] === "In Progress" ? "selected" : ""; ?>>In Progress</option>
            <option value="Done" <?php echo $task["status"] === "Done" ? "selected" : ""; ?>>Done</option>
        </select><br><br>

        <label>Start Date:</label><br>
        <input type="date" name="start_date" value="<?php echo htmlspecialchars($task["start_date"]); ?>" required><br><br>

        <label>Deadline:</label><br>
        <input type="date" name="deadline" value="<?php echo htmlspecialchars($task["deadline"]); ?>" required><br><br>

        <label>Task File (Optional):</label><br>
        <input type="file" name="task_file" accept=".pdf,.doc,.docx,.xlsx,.xls,.png,.jpg,.jpeg,.txt"><br><br>

        <?php if (!empty($task["attachment_path"])): ?>
            <p>Current file: <a href="<?php echo htmlspecialchars($task["attachment_path"]); ?>" target="_blank">View File</a></p>
        <?php endif; ?>

        <button type="submit">Update Task</button>
            </form>
        </div>
    </div>
</body>
</html>
