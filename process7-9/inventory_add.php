<?php
require_once "config/database.php";
require_once "includes/common.php";
require_role("intern");

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

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $drugId = (int) ($_POST["drug_id"] ?? 0);
    $drugName = sanitize_input($_POST["drug_name"] ?? "");
    $manufacturer = sanitize_input($_POST["manufacturer"] ?? "");
    $recordDate = sanitize_input($_POST["record_date"] ?? "");
    $invoiceNo = sanitize_input($_POST["invoice_no"] ?? "");
    $currentInventory = (int) ($_POST["current_inventory"] ?? -1);
    $initialComments = sanitize_input($_POST["initial_comments"] ?? "");

    if ($drugId <= 0 || $drugName === "" || $manufacturer === "" || $recordDate === "" || $invoiceNo === "" || $initialComments === "") {
        $error = "All fields are required.";
    } elseif ($currentInventory < 0) {
        $error = "Current inventory must be 0 or greater.";
    } else {
        $stmt = $conn->prepare("INSERT INTO product_inventory (product_id, drug_name, manufacturer, record_date, invoice_no, current_inventory, initial_comments) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssis", $drugId, $drugName, $manufacturer, $recordDate, $invoiceNo, $currentInventory, $initialComments);
        $stmt->execute();
        if ($stmt->errno !== 0) {
            $error = "Drug ID already exists. Please use a different Drug ID.";
            $stmt->close();
        } else {
        $stmt->close();

            header("Location: inventory_list.php?message=Drug inventory check saved.");
            exit;
        }
    }
}
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
                <a href="inventory_list.php">Back to Drug Inventory</a>
            </div>

            <span class="badge badge-intern">INTERN</span>
            <h1>Conducting Product Inventory</h1>
            <?php if ($error !== ""): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post">
        <label>Drug ID:</label><br>
        <input type="number" name="drug_id" min="1" required><br><br>

        <label>Drug Name:</label><br>
        <input type="text" name="drug_name" required><br><br>

        <label>Manufacturer:</label><br>
        <input type="text" name="manufacturer" required><br><br>

        <label>Date:</label><br>
        <input type="date" name="record_date" required><br><br>

        <label>Invoice #:</label><br>
        <input type="text" name="invoice_no" required><br><br>

        <label>Current Inventory:</label><br>
        <input type="number" name="current_inventory" min="0" required><br><br>

        <label>Initial Comments:</label><br>
        <textarea name="initial_comments" required></textarea><br><br>

        <button type="submit">Save Drug Check</button>
            </form>
        </div>
    </div>
</body>
</html>
