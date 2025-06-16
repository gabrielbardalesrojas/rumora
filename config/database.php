<?php
/**
 * Archivo de Configuración de Base de Datos para RUMORA
 *
 * Contiene los parámetros para la conexión a la base de datos MySQL
 * utilizando PDO (PHP Data Objects) para mayor seguridad y flexibilidad.
 */

// Definir las constantes de conexión a la base de datos
define('DB_HOST', 'localhost'); // El host de tu base de datos (comúnmente localhost)
define('DB_NAME', 'rumora'); // El nombre de la base de datos que creaste (ej. rumora_db)
define('DB_USER', 'root');     // Tu usuario de base de datos
define('DB_PASS', '');         // Tu contraseña de base de datos (dejar vacío si no tienes en desarrollo)
define('DB_CHARSET', 'utf8mb4'); // Conjunto de caracteres para soportar emojis y otros caracteres especiales

// Opciones de PDO para una conexión robusta
$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanzar excepciones en caso de error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Devolver filas como arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                // Desactivar la emulación de prepared statements (más seguro y eficiente)
];

// Construir el DSN (Data Source Name)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

try {
    // Crear una nueva instancia de PDO para la conexión a la base de datos
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);

    // Si la conexión es exitosa, puedes ver este mensaje (para depuración)
    // echo "Conexión a la base de datos exitosa.";

} catch (PDOException $e) {
    // Si ocurre un error de conexión, capturarlo y mostrar un mensaje
    // En un entorno de producción, es mejor registrar el error y mostrar un mensaje genérico al usuario
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// La variable $pdo ahora contiene el objeto de conexión a la base de datos.
// Puedes incluir este archivo en tus scripts PHP donde necesites interactuar con la DB.
// Por ejemplo:
// require_once 'config/database.php';
// $stmt = $pdo->prepare("SELECT * FROM users WHERE numero = ?");
// $stmt->execute([$numero_usuario]);
// $user = $stmt->fetch();
?>
