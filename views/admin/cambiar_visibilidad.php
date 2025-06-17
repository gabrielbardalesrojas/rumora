<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario es administrador
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener y validar los datos
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$is_public = filter_input(INPUT_POST, 'is_public', FILTER_VALIDATE_INT);

if (!$id || $is_public === null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit();
}

// Incluir configuración de la base de datos
require_once '../../../config/database.php';

try {
    // Actualizar la visibilidad del perfil
    $stmt = $pdo->prepare("UPDATE users SET is_public = ? WHERE id = ?");
    $success = $stmt->execute([$is_public, $id]);
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la visibilidad del perfil']);
    }
} catch (PDOException $e) {
    error_log('Error al cambiar visibilidad: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
