<?php
// controller/AdminLoginController.php

// Asegúrate de que session_start() se haya llamado al inicio de index.php
// y que 'config/database.php' ya esté incluido.

// Se asume que $pdo (conexión a la base de datos) está disponible
// desde el archivo que incluye este controlador (ej. index.php).

// Define la ruta del dashboard de admin (ajusta según tu estructura)
$admin_dashboard_path = 'views/admin/dashboard_admin.php'; // Example path

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login_submit'])) {
    $email = filter_input(INPUT_POST, 'admin_email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'admin_password');

    if (empty($email) || empty($password)) {
        $_SESSION['message'] = "Por favor, ingresa correo y contraseña de administrador.";
        $_SESSION['message_type'] = "error";
        header("Location: index.php"); // Redirect back to index.php
        exit();
    }

    try {
        // Buscar al administrador por correo
        // IMPORTANT: Replace 'admins' with your actual admin table name
        // And 'email_column', 'password_column' with your actual column names
        $stmt = $pdo->prepare("SELECT id, email, password FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Contraseña correcta, iniciar sesión de administrador
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['message'] = "¡Inicio de sesión de administrador exitoso!";
            $_SESSION['message_type'] = "success";

            header("Location: " . $admin_dashboard_path);
            exit();
        } else {
            $_SESSION['message'] = "Credenciales de administrador incorrectas.";
            $_SESSION['message_type'] = "error";
            header("Location: index.php"); // Redirect back to index.php
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error al iniciar sesión de administrador: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: index.php"); // Redirect back to index.php
        exit();
    }
}
?>