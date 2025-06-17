<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$blocked_id = isset($_POST['blocked_id']) ? intval($_POST['blocked_id']) : 0;

if ($blocked_id <= 0 || $blocked_id == $current_user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID to block.']);
    exit();
}

try {
    // Insert into user_blocks table
    $stmt = $pdo->prepare("INSERT IGNORE INTO user_blocks (blocker_id, blocked_id) VALUES (?, ?)");
    if ($stmt->execute([$current_user_id, $blocked_id])) {
        // Also remove from friends if they were friends
        $stmt_remove_friend = $pdo->prepare("DELETE FROM user_friends WHERE user_id = ? AND friend_id = ?");
        $stmt_remove_friend->execute([$current_user_id, $blocked_id]);

        echo json_encode(['success' => true, 'message' => 'User blocked successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to block user.']);
    }
} catch (PDOException $e) {
    error_log("Error blocking user: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>