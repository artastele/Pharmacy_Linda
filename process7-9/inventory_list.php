<?php
require_once "config/database.php";
require_once "includes/common.php";
require_role("intern");
$internUserId = (int) ($_SESSION["user_id"] ?? 0);

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

$requiredColumns = [
    "drug_name" => "ALTER TABLE product_inventory ADD COLUMN drug_name VARCHAR(255) NOT NULL AFTER product_id",
    "manufacturer" => "ALTER TABLE product_inventory ADD COLUMN manufacturer VARCHAR(255) NOT NULL AFTER drug_name",
    "record_date" => "ALTER TABLE product_inventory ADD COLUMN record_date DATE NOT NULL AFTER manufacturer",
    "invoice_no" => "ALTER TABLE product_inventory ADD COLUMN invoice_no VARCHAR(100) NOT NULL AFTER record_date",
    "current_inventory" => "ALTER TABLE product_inventory ADD COLUMN current_inventory INT NOT NULL AFTER invoice_no",
    "initial_comments" => "ALTER TABLE product_inventory ADD COLUMN initial_comments TEXT NOT NULL AFTER current_inventory"
];
foreach ($requiredColumns as $columnName => $alterSql) {
    $columnCheck = $conn->query("SHOW COLUMNS FROM product_inventory LIKE '" . $conn->real_escape_string($columnName) . "'");
    if ($columnCheck && $columnCheck->num_rows === 0) {
        $conn->query($alterSql);
    }
}

$taskAttachmentColumnExists = false;
$taskColumnCheck = $conn->query("SHOW COLUMNS FROM tasks LIKE 'attachment_path'");
if ($taskColumnCheck && $taskColumnCheck->num_rows > 0) {
    $taskAttachmentColumnExists = true;
}

if (isset($_GET["delete_id"])) {
    $deleteId = (int) $_GET["delete_id"];
    $stmt = $conn->prepare("DELETE FROM product_inventory WHERE product_id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $stmt->close();

    header("Location: inventory_list.php?message=Drug inventory check removed.");
    exit;
}

$unreadCount = 0;
$notifications = null;
if ($internUserId > 0) {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $countStmt->bind_param("i", $internUserId);
    $countStmt->execute();
    $unreadCountResult = $countStmt->get_result()->fetch_assoc();
    $unreadCount = (int) ($unreadCountResult["unread_count"] ?? 0);
    $countStmt->close();

    $notifStmt = $conn->prepare("SELECT notification_id, title, message, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY notification_id DESC LIMIT 8");
    $notifStmt->bind_param("i", $internUserId);
    $notifStmt->execute();
    $notifications = $notifStmt->get_result();
    $notifStmt->close();

    $markStmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $markStmt->bind_param("i", $internUserId);
    $markStmt->execute();
    $markStmt->close();
}

$products = $conn->query("SELECT product_id, drug_name, manufacturer, record_date, invoice_no, current_inventory, initial_comments FROM product_inventory ORDER BY product_id DESC");
$tasksQuery = $taskAttachmentColumnExists
    ? "SELECT task_id, employee_name, task_name, description, status, start_date, deadline, attachment_path FROM tasks ORDER BY task_id DESC"
    : "SELECT task_id, employee_name, task_name, description, status, start_date, deadline, NULL AS attachment_path FROM tasks ORDER BY task_id DESC";
$tasks = $conn->query($tasksQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conducting Product Inventory</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="process9-page">
    <div class="container">
        <div class="card role-intern">
            <div class="nav">
                <a class="nav-link" href="inventory_add.php">Check Drug</a>
                <a class="nav-link nav-link-muted" href="logout.php">Log Out</a>
            </div>

            <span class="badge badge-intern">INTERN</span>
            <h1>Conducting Product Inventory</h1>
            <p>Check and record drug details by Drug ID.</p>
            <?php show_message(); ?>
            <div class="notice-panel">
                <h2>Task Notifications</h2>
                <p>
                    <?php if ($unreadCount > 0): ?>
                        You have <?php echo $unreadCount; ?> new task notification(s).
                    <?php else: ?>
                        No new notifications right now.
                    <?php endif; ?>
                </p>

                <?php if ($notifications && $notifications->num_rows > 0): ?>
                    <ul class="notice-list">
                        <?php while ($notif = $notifications->fetch_assoc()): ?>
                            <li class="notice-item">
                                <strong><?php echo htmlspecialchars($notif["title"]); ?></strong>
                                <p><?php echo htmlspecialchars($notif["message"]); ?></p>
                                <span class="muted-text"><?php echo htmlspecialchars($notif["created_at"]); ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="muted-text">No task notifications yet.</p>
                <?php endif; ?>
            </div>

            <div class="notice-panel">
                <h2>Assigned Task Information</h2>
                <p>View full task details and attached file from HR.</p>
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
                        </tr>
                        <?php if ($tasks && $tasks->num_rows > 0): ?>
                            <?php while ($task = $tasks->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo (int) $task["task_id"]; ?></td>
                                    <td><?php echo htmlspecialchars($task["employee_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($task["task_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($task["description"]); ?></td>
                                    <td><?php echo htmlspecialchars($task["status"]); ?></td>
                                    <td><?php echo htmlspecialchars($task["start_date"]); ?></td>
                                    <td><?php echo htmlspecialchars($task["deadline"]); ?></td>
                                    <td>
                                        <?php if (!empty($task["attachment_path"])): ?>
                                            <a class="action-btn" href="<?php echo htmlspecialchars($task["attachment_path"]); ?>" target="_blank">View File</a>
                                        <?php else: ?>
                                            <span class="muted-text">No file</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="muted-text">No tasks available yet.</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="table-wrap">
            <table>
        <tr>
            <th>Drug ID</th>
            <th>Drug Name</th>
            <th>Manufacturer</th>
            <th>Date</th>
            <th>Invoice #</th>
            <th>Current Inventory</th>
            <th>Initial Comments</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $products->fetch_assoc()): ?>
            <tr>
                <td><?php echo (int) $row["product_id"]; ?></td>
                <td><?php echo htmlspecialchars($row["drug_name"]); ?></td>
                <td><?php echo htmlspecialchars($row["manufacturer"]); ?></td>
                <td><?php echo htmlspecialchars($row["record_date"]); ?></td>
                <td><?php echo htmlspecialchars($row["invoice_no"]); ?></td>
                <td><?php echo (int) $row["current_inventory"]; ?></td>
                <td><?php echo htmlspecialchars($row["initial_comments"]); ?></td>
                <td class="actions-cell">
                    <a class="action-btn" href="inventory_edit.php?drug_id=<?php echo (int) $row["product_id"]; ?>">Edit</a>
                    <a class="action-btn danger-btn" href="inventory_list.php?delete_id=<?php echo (int) $row["product_id"]; ?>" onclick="return confirm('Remove this drug inventory check?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
            </table>
            </div>
        </div>
    </div>
</body>
</html>
