<?php
require_once "config/database.php";
require_once "includes/common.php";
require_role("hr_personnel");

if (isset($_GET["delete_id"])) {
    $deleteId = (int) $_GET["delete_id"];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE task_id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $stmt->close();

    header("Location: tasks_list.php?message=Task deleted successfully.");
    exit;
}

$result = $conn->query("SELECT task_id, employee_name, task_name, description, status, start_date, deadline, attachment_path FROM tasks ORDER BY task_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management Board</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="app-shell">
    <div class="container">
        <div class="card role-admin">
            <div class="nav">
                <a class="nav-link" href="tasks_add.php">New Task</a>
                <a class="nav-link nav-link-muted" href="logout.php">Log Out</a>
            </div>

            <span class="badge badge-admin">HR PERSONNEL</span>
            <h1>Task Management Board</h1>
            <p>All internship routine tasks in one clean view.</p>
            <?php show_message(); ?>

            <div class="table-wrap">
            <table>
        <tr>
            <th>Task ID</th>
            <th>Employee Name</th>
            <th>Task</th>
            <th>Description</th>
            <th>Status</th>
            <th>Start Date</th>
            <th>Deadline</th>
            <th>File</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo (int) $row["task_id"]; ?></td>
                <td><?php echo htmlspecialchars($row["employee_name"]); ?></td>
                <td><?php echo htmlspecialchars($row["task_name"]); ?></td>
                <td><?php echo htmlspecialchars($row["description"]); ?></td>
                <td><?php echo htmlspecialchars($row["status"]); ?></td>
                <td><?php echo htmlspecialchars($row["start_date"]); ?></td>
                <td><?php echo htmlspecialchars($row["deadline"]); ?></td>
                <td>
                    <?php if (!empty($row["attachment_path"])): ?>
                        <a class="action-btn" href="<?php echo htmlspecialchars($row["attachment_path"]); ?>" target="_blank">View File</a>
                    <?php else: ?>
                        <span class="muted-text">No file</span>
                    <?php endif; ?>
                </td>
                <td class="actions-cell">
                    <a class="action-btn" href="tasks_edit.php?task_id=<?php echo (int) $row["task_id"]; ?>">Edit</a>
                    <a class="action-btn danger-btn" href="tasks_list.php?delete_id=<?php echo (int) $row["task_id"]; ?>" onclick="return confirm('Delete this task?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
            </table>
            </div>
        </div>
    </div>
</body>
</html>
