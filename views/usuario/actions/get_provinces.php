<?php
require_once '../../../config/database.php'; // Adjust path as needed

header('Content-Type: application/json');

$departamento = $_GET['departamento'] ?? '';

if (empty($departamento)) {
    echo json_encode([]);
    exit();
}

$provincias = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT provincia FROM users WHERE departamento = ? ORDER BY provincia ASC");
    $stmt->execute([$departamento]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $provincias[] = $row['provincia'];
    }
    echo json_encode($provincias);
} catch (PDOException $e) {
    error_log("Error fetching provinces: " . $e->getMessage());
    echo json_encode([]);
}
?>