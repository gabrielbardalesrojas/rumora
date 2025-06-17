<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'new_likes' => 0, 'action' => 'none'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confession_id = filter_var($_POST['confession_id'] ?? '', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$confession_id) {
        $response['message'] = 'ID de confesión no válido.';
        echo json_encode($response);
        exit();
    }

    try {
        // Check if the user has already liked this confession
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM confession_likes WHERE confession_id = ? AND user_id = ?");
        $stmt->execute([$confession_id, $user_id]);
        $already_liked = $stmt->fetchColumn();

        $pdo->beginTransaction();

        if ($already_liked) {
            // User already liked, so unlike it
            $stmt = $pdo->prepare("DELETE FROM confession_likes WHERE confession_id = ? AND user_id = ?");
            $stmt->execute([$confession_id, $user_id]);

            $stmt = $pdo->prepare("UPDATE confessions SET likes = likes - 1 WHERE confession_id = ?");
            $stmt->execute([$confession_id]);
            $response['action'] = 'unliked';
        } else {
            // User has not liked, so like it
            $stmt = $pdo->prepare("INSERT INTO confession_likes (confession_id, user_id) VALUES (?, ?)");
            $stmt->execute([$confession_id, $user_id]);

            $stmt = $pdo->prepare("UPDATE confessions SET likes = likes + 1 WHERE confession_id = ?");
            $stmt->execute([$confession_id]);
            $response['action'] = 'liked';
        }

        // Get the new like count
        $stmt = $pdo->prepare("SELECT likes FROM confessions WHERE confession_id = ?");
        $stmt->execute([$confession_id]);
        $new_likes = $stmt->fetchColumn();

        $pdo->commit();

        $response['success'] = true;
        $response['new_likes'] = $new_likes;
        $response['message'] = 'Estado del like actualizado.';

    } catch (PDOException $e) {
        $pdo->rollBack();
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Método de solicitud no válido.';
}

echo json_encode($response);
?>
