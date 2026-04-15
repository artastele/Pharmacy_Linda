<?php
// Process 10-14 Helper Functions
// Common functions used by process10-14 integrated files

// Safe escape function for database queries
function esc($conn, $val) {
    return $conn->real_escape_string(trim($val));
}

// Navigation bar function for process10-14 pages
function navBar($pageTitle, $backUrl = null) {
    $currentUser = function_exists('current_user') ? current_user() : null;
    $role = $currentUser['role'] ?? $_SESSION['role'] ?? 'Unknown';
    $name = $currentUser['full_name'] ?? $_SESSION['full_name'] ?? 'User';

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($pageTitle) . ' — Pharmacy Linda</title>
    <link rel="stylesheet" href="/Pharmacy_Linda/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    </head><body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Linda</div>
            <nav>
                ' . ($role === 'Intern' ? '<a href="/Pharmacy_Linda/create_inventory_report.php">Create Inventory Report</a>' : '<a href="/Pharmacy_Linda/dashboard_technician.php">Technician Dashboard</a><a href="/Pharmacy_Linda/stock_report_dashboard.php">Stock Status</a><a href="/Pharmacy_Linda/requisition_form.php">Requisition Form</a>') . '
                <a href="/Pharmacy_Linda/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <div>
                    <h1>' . htmlspecialchars($pageTitle) . '</h1>' .
                    ($backUrl ? '<a href="/Pharmacy_Linda/' . ltrim($backUrl, '/') . '" class="btn btn-secondary">Back</a>' : '') . '
                </div>
                <div>' . htmlspecialchars($name) . ' — ' . htmlspecialchars($role) . '</div>
            </header>';
}
