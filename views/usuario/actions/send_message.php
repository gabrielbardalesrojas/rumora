<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
$message_content = isset($_POST['message_content']) ? trim($_POST['message_content']) : '';

if ($receiver_id <= 0 || empty($message_content)) {
    echo json_encode(['success' => false, 'message' => 'Invalid receiver or empty message.']);
    exit();
}

// Check if current user has blocked the receiver
$stmt_blocked_by_current = $pdo->prepare("SELECT COUNT(*) FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
$stmt_blocked_by_current->execute([$current_user_id, $receiver_id]);
if ($stmt_blocked_by_current->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'You have blocked this user. Cannot send message.']);
    exit();
}

// Check if receiver has blocked the current user
$stmt_receiver_blocked = $pdo->prepare("SELECT COUNT(*) FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
$stmt_receiver_blocked->execute([$receiver_id, $current_user_id]);
if ($stmt_receiver_blocked->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'This user has blocked you. Cannot send message.']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_content) VALUES (?, ?, ?)");
    if ($stmt->execute([$current_user_id, $receiver_id, $message_content])) {
        echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert message.']);
    }
} catch (PDOException $e) {
    error_log("Error sending message: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>