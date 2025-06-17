<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$unblocked_id = isset($_POST['unblocked_id']) ? intval($_POST['unblocked_id']) : 0;

if ($unblocked_id <= 0 || $unblocked_id == $current_user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID to unblock.']);
    exit();
}

try {
    // Delete from user_blocks table
    $stmt = $pdo->prepare("DELETE FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
    if ($stmt->execute([$current_user_id, $unblocked_id])) {
        echo json_encode(['success' => true, 'message' => 'User unblocked successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to unblock user.']);
    }
} catch (PDOException $e) {
    error_log("Error unblocking user: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>