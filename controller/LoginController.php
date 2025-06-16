<?php
// Este archivo procesa el formulario de inicio de sesión.
// Asegúrate de que session_start() se haya llamado al inicio de index.php
// y que 'config/database.php' ya esté incluido.

// Se asume que $pdo (conexión a la base de datos) y $dashboard_path están disponibles
// desde el archivo que incluye este controlador (ej. index.php).

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $numero = filter_input(INPUT_POST, 'phone');
    $contrasena = filter_input(INPUT_POST, 'password');

    if (empty($numero) || empty($contrasena)) {
        $_SESSION['message'] = "Por favor, ingresa tu número de teléfono y contraseña.";
        $_SESSION['message_type'] = "error";
        header("Location: index.php"); // <--- ADD THIS LINE
        exit(); // <--- ADD THIS LINE
    } else {
        try {
            // Buscar al usuario por número de teléfono
            $stmt = $pdo->prepare("SELECT id, contrasena, nombre_usuario, avatar FROM users WHERE numero = ?");
            $stmt->execute([$numero]);
            $user = $stmt->fetch();

            if ($user && password_verify($contrasena, $user['contrasena'])) {
                // Contraseña correcta, iniciar sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['nombre_usuario'];
                $_SESSION['avatar'] = $user['avatar'];
                $_SESSION['message'] = "¡Inicio de sesión exitoso! Bienvenido de nuevo.";
                $_SESSION['message_type'] = "success";

                // Actualizar last_seen
                $updateStmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                header("Location: " . $dashboard_path); // Redirige al dashboard
                exit();
            } else {
                $_SESSION['message'] = "Número de teléfono o contraseña incorrectos.";
                $_SESSION['message_type'] = "error";
                header("Location: index.php"); // <--- ADD THIS LINE
                exit(); // <--- ADD THIS LINE
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error al iniciar sesión: " . $e->getMessage();
            $_SESSION['message_type'] = "error";
            header("Location: index.php"); // <--- ADD THIS LINE
            exit(); // <--- ADD THIS LINE
        }
    }
}
?>