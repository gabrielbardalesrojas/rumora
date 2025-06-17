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
    echo json_encode(['success' => false, 'message' => 'Invalid user ID to remove from friends.']);
    exit();
}

try {
    // Delete from user_friends table
    $stmt = $pdo->prepare("DELETE FROM user_friends WHERE user_id = ? AND friend_id = ?");
    if ($stmt->execute([$current_user_id, $friend_id])) {
        echo json_encode(['success' => true, 'message' => 'User removed from friends.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove friend.']);
    }
} catch (PDOException $e) {
    error_log("Error removing friend: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>