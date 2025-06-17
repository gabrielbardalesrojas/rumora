<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$chat_with_id = isset($_GET['chat_with_id']) ? intval($_GET['chat_with_id']) : 0;

if ($chat_with_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid chat partner ID.']);
    exit();
}

$messages = [];
try {
    // Check if the current user is blocked by the chat partner
    $stmt_blocked = $pdo->prepare("SELECT COUNT(*) FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
    $stmt_blocked->execute([$chat_with_id, $current_user_id]);
    if ($stmt_blocked->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'You have been blocked by this user.']);
        exit();
    }

    $stmt = $pdo->prepare("SELECT sender_id, message_content, timestamp FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY timestamp ASC");
    $stmt->execute([$current_user_id, $chat_with_id, $chat_with_id, $current_user_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = $row;
    }

    // Mark messages as read for the current user from this sender
    $stmt_mark_read = $pdo->prepare("UPDATE messages SET is_read = TRUE WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE");
    $stmt_mark_read->execute([$chat_with_id, $current_user_id]);

    echo json_encode(['success' => true, 'messages' => $messages]);

} catch (PDOException $e) {
    error_log("Error fetching messages: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>