<?php
// delete_log.php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_id'])) {
    $log_id = filter_var($_POST['log_id'], FILTER_VALIDATE_INT);
    
    if ($log_id) {
        $stmt = $conn->prepare("DELETE FROM email_logs WHERE id = ?");
        $stmt->bind_param("i", $log_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting log']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid log ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>