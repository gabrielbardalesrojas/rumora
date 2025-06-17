<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$friend_id = isset($_POST['friend_id']) ? intval($_POST['friend_id']) : 0;

if ($friend_id <= 0 || $friend_id == $current_user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID to add as friend.']);
    exit();
}

try {
    // Ensure the user is not blocking the friend, and the friend is not blocking the user
    $stmt_check_blocked = $pdo->prepare("SELECT COUNT(*) FROM user_blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)");
    $stmt_check_blocked->execute([$current_user_id, $friend_id, $friend_id, $current_user_id]);
    if ($stmt_check_blocked->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot add friend: one user has blocked the other.']);
        exit();
    }

    // Insert into user_friends table
    $stmt = $pdo->prepare("INSERT IGNORE INTO user_friends (user_id, friend_id) VALUES (?, ?)");
    if ($stmt->execute([$current_user_id, $friend_id])) {
        echo json_encode(['success' => true, 'message' => 'User added to friends.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add friend.']);
    }
} catch (PDOException $e) {
    error_log("Error adding friend: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>