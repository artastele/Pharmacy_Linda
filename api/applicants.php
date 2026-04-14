<?php
require_once '../common.php';

if (!is_logged_in() || $_SESSION['role'] !== 'hr') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list_pending_approval':
        $stmt = $pdo->query("SELECT id, full_name, email FROM users WHERE role = 'intern' AND id NOT IN (SELECT intern_id FROM pending_applicants)");
        $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['applicants' => $applicants]);
        break;
    case 'approve':
        $id = $_GET['id'] ?? '';
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO pending_applicants (intern_id, status) VALUES (?, 'Pending Interview')");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>