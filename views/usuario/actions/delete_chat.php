<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$chat_with_id = isset($_POST['chat_with_id']) ? intval($_POST['chat_with_id']) : 0;

if ($chat_with_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid chat partner ID.']);
    exit();
}

try {
    // Delete messages where current user is sender AND chat_with_id is receiver
    // OR where chat_with_id is sender AND current user is receiver
    $stmt = $pdo->prepare("DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    if ($stmt->execute([$current_user_id, $chat_with_id, $chat_with_id, $current_user_id])) {
        echo json_encode(['success' => true, 'message' => 'Chat deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete chat.']);
    }
} catch (PDOException $e) {
    error_log("Error deleting chat: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>