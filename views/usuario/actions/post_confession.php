<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuario no autenticado.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');

    if (empty($content)) {
        $response['message'] = 'La confesión no puede estar vacía.';
        echo json_encode($response);
        exit();
    }

    if (mb_strlen($content) > 500) {
        $response['message'] = 'La confesión excede el límite de 500 caracteres.';
        echo json_encode($response);
        exit();
    }

    try {
        // user_id is NULL for anonymous confessions as per table definition
        $stmt = $pdo->prepare("INSERT INTO confessions (content, user_id) VALUES (?, ?)");
        // If confessions are truly anonymous, pass NULL for user_id
        // If you want to track who posted but display as anonymous, pass $current_user_id
        $stmt->execute([$content, $_SESSION['user_id']]); // Linking to user_id, but can be NULL if desired

        $response['success'] = true;
        $response['message'] = 'Confesión publicada exitosamente.';
    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Método de solicitud no válido.';
}

echo json_encode($response);
?>
