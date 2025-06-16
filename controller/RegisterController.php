<?php
// Este archivo procesa el formulario de registro.
// Asegúrate de que session_start() se haya llamado al inicio de index.php
// y que 'config/database.php' ya esté incluido.

// Se asume que $pdo (conexión a la base de datos) y $dashboard_path están disponibles
// desde el archivo que incluye este controlador (ej. index.php).

/**
 * Función para generar un nombre de usuario aleatorio.
 * @return string Nombre de usuario único y aleatorio.
 */
function generateRandomUsername() {
    $adjectives = ['Misterioso', 'Silencioso', 'Veloz', 'Astuto', 'Brillante', 'Invisible', 'Sabio', 'Curioso'];
    $nouns = ['Susurro', 'Eco', 'Sombra', 'Viento', 'Fuente', 'Enigma', 'Voz', 'Pista'];
    $randomAdj = $adjectives[array_rand($adjectives)];
    $randomNoun = $nouns[array_rand($nouns)];
    $randomNumber = rand(100, 999);
    return $randomAdj . $randomNoun . $randomNumber;
}

/**
 * Función para generar una URL de avatar aleatoria (usando Placehold.co).
 * @return string URL del avatar.
 */
function generateRandomAvatar() {
    $colors = ['FF6F61', 'FFB642', 'D946EF', '06B6D4', '84CC16', 'A855F7'];
    $randomColor = $colors[array_rand($colors)];
    $initials = strtoupper(substr(generateRandomUsername(), 0, 2)); // Usar las primeras 2 letras de un username para el texto del avatar
    return "https://placehold.co/100x100/{$randomColor}/ffffff?text={$initials}";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $numero = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $contrasena = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $genero = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
    $is_foreign = isset($_POST['is_foreign']);
    $departamento = $is_foreign ? 'Extranjero' : filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING);
    $provincia = $is_foreign ? 'Extranjero' : filter_input(INPUT_POST, 'province', FILTER_SANITIZE_STRING);

    // Validaciones básicas
    if (empty($numero) || empty($contrasena) || empty($genero) || (!$is_foreign && (empty($departamento) || empty($provincia))) || strlen($contrasena) < 6) {
        $_SESSION['message'] = "Por favor, completa todos los campos correctamente.";
        $_SESSION['message_type'] = "error";
    } else {
        try {
            // Hashear la contraseña antes de almacenarla
            $contrasena_hasheada = password_hash($contrasena, PASSWORD_BCRYPT);

            // Generar avatar y nombre de usuario aleatorios
            $avatar_url = generateRandomAvatar();
            $nombre_usuario = generateRandomUsername();

            // Preparar la consulta SQL para insertar el nuevo usuario
            $stmt = $pdo->prepare("INSERT INTO users (numero, contrasena, genero, departamento, provincia, avatar, nombre_usuario) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$numero, $contrasena_hasheada, $genero, $departamento, $provincia, $avatar_url, $nombre_usuario]);

            // Iniciar sesión automáticamente después del registro exitoso
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $nombre_usuario;
            $_SESSION['avatar'] = $avatar_url;
            $_SESSION['message'] = "¡Registro exitoso! Bienvenido a RUMORA.";
            $_SESSION['message_type'] = "success";

            header("Location: " . $dashboard_path); // Redirige al dashboard
            exit();

        } catch (PDOException $e) {
            // Manejo de errores (ej. número de teléfono ya registrado)
            if ($e->getCode() == 23000) { // Código de error para duplicado de entrada
                $_SESSION['message'] = "El número de teléfono ya está registrado.";
                $_SESSION['message_type'] = "error";
            } else {
                $_SESSION['message'] = "Error al registrar: " . $e->getMessage();
                $_SESSION['message_type'] = "error";
            }
        }
    }
}
?>
