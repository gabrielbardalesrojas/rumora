<?php
session_start();
header('Content-Type: application/json');

require_once '../../../config/database.php'; // Adjust path as needed

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido.']);
    exit();
}

// Get and validate input
$setting_name = $_POST['setting_name'] ?? '';
$setting_value = $_POST['setting_value'] ?? null;

// Validate setting name and value
$allowed_settings = ['is_public', 'show_online_status', 'allow_stranger_messages'];

if (!in_array($setting_name, $allowed_settings)) {
    echo json_encode(['success' => false, 'message' => 'Nombre de configuración inválido.']);
    exit();
}

// Ensure setting_value is a valid boolean (0 or 1)
if (!is_numeric($setting_value) || ($setting_value != 0 && $setting_value != 1)) {
    echo json_encode(['success' => false, 'message' => 'Valor de configuración inválido.']);
    exit();
}

try {
    // Prepare the SQL statement dynamically based on the setting name
    $stmt = $pdo->prepare("UPDATE users SET " . $setting_name . " = ? WHERE id = ?");
    $stmt->execute([$setting_value, $current_user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Configuración actualizada con éxito.']);
    } else {
        // This might happen if the value didn't actually change, or if the user_id was not found
        // For settings where the value might not change, this is acceptable.
        // If it's critical that something changed, you might check $stmt->rowCount() > 0
        echo json_encode(['success' => true, 'message' => 'Configuración actualizada (o sin cambios necesarios).']);
    }

} catch (PDOException $e) {
    // Log the error for debugging (do not expose detailed error messages to the user)
    error_log("Error updating user setting: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor al actualizar la configuración.']);
}
?>