<?php
require_once __DIR__ . '/../common.php';
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        require_login();
        require_role('HR Personnel');
        $stmt = $pdo->query('SELECT r.*, u.full_name FROM internship_records r JOIN users u ON r.intern_id = u.id ORDER BY r.date_created DESC');
        send_json(['success' => true, 'records' => $stmt->fetchAll()]);
        break;

    case 'get':
        require_login();
        require_role('HR Personnel');
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare('SELECT * FROM internship_records WHERE record_id = ?');
        $stmt->execute([$id]);
        send_json(['success' => true, 'record' => $stmt->fetch()]);
        break;

    default:
        send_json(['success' => false, 'message' => 'Invalid action.'], 400);
}
