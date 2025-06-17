<?php
session_start();

// Si el usuario no está logueado, redirigir al index
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Incluir configuración de base de datos
require_once '../../config/database.php';

// Obtener información del usuario incluyendo si es administrador
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT nombre_usuario as username, avatar, is_admin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: ../../index.php");
    exit();
}

$username = $user['username'] ?? 'Usuario RUMORA';
$avatar = $user['avatar'] ?? 'https://placehold.co/100x100/A855F7/ffffff?text=RU';
$is_admin = $user['is_admin'] ?? false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de RUMORA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1f2937 0%, #0d1117 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #e0e6f0;
            text-align: center;
            padding: 2rem;
            flex-direction: column;
        }
        .dashboard-card {
            background-color: #1f2937;
            border-radius: 1.5rem;
            box-shadow: 0 15px 30px rgba(0,0,0,0.4), 0 6px 12px rgba(0,0,0,0.2);
            padding: 3rem;
            max-width: 600px;
            width: 100%;
            border: 1px solid #2f3b4d;
        }
        .btn-logout {
            transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55), box-shadow 0.3s ease-in-out;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08);
        }
        .btn-logout:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2), 0 4px 8px rgba(0,0,0,0.1);
        }
        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #FF6F61; /* Borde con color vibrante de RUMORA */
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="dashboard-card">
        <div class="flex flex-col items-center mb-8">
            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar del usuario" class="avatar mb-6">
            <h1 class="text-4xl font-extrabold text-white mb-4">¡Bienvenido, <?php echo htmlspecialchars($username); ?>!</h1>
            <p class="text-xl text-gray-300 mb-8">Has iniciado sesión con éxito en RUMORA.</p>
        </div>
        
        <p class="text-md text-gray-400 mb-8">
            Aquí es donde iría el contenido principal de tu aplicación de chat.
            ¡Prepárate para conectar, expresar y descubrir!
        </p>

        <div class="flex flex-col sm:flex-row gap-4 mt-6">
            <?php if ($is_admin): ?>
            <a href="/admin/dashboard" class="py-3 px-6 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition-all flex items-center justify-center gap-2 shadow-lg">
                <i class="fas fa-shield-alt"></i> Panel de Administración
            </a>
            <?php endif; ?>
            
            <a href="../../logout.php" class="py-3 px-6 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 transition-all flex items-center justify-center gap-2 shadow-lg">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </div>
</body>
</html>
