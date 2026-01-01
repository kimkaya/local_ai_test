<?php
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'test') {
    echo json_encode(['success' => true, 'message' => 'Test passed']);
} else {
    echo json_encode(['success' => false, 'error' => 'No action']);
}
?>
