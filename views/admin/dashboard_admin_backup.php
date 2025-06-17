<?php
session_start();

// Si el usuario no está logueado, redirigir al index
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Incluir configuración de la base de datos
require_once '../../config/database.php';

// Obtener estadísticas
$stats = [
    'total_usuarios' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'usuarios_activos' => $pdo->query("SELECT COUNT(*) FROM users WHERE last_seen >= NOW() - INTERVAL 30 DAY")->fetchColumn(),
    'nuevos_usuarios' => $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn()
];

$username = $_SESSION['admin_email'] ?? 'Administrador';

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
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Barra de navegación -->
        <nav class="bg-indigo-700 text-white p-4">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-2xl font-bold">Panel de Administración</h1>
                <div class="flex items-center space-x-4">
                    <span class="hidden md:inline"><?php echo htmlspecialchars($username); ?></span>
                    <a href="../../logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-sign-out-alt mr-2"></i> Salir
                    </a>
                </div>
            </div>
        </nav>

        <!-- Contenido principal -->
        <div class="container mx-auto p-6">
            <h2 class="text-2xl font-bold mb-6">Resumen General</h2>
            
            <!-- Tarjetas de estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total de usuarios -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500">Total de Usuarios</p>
                            <p class="text-3xl font-bold"><?php echo $stats['total_usuarios']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Usuarios activos -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-user-check text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500">Usuarios Activos (30 días)</p>
                            <p class="text-3xl font-bold"><?php echo $stats['usuarios_activos']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Nuevos usuarios -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                            <i class="fas fa-user-plus text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500">Nuevos Usuarios (7 días)</p>
                            <p class="text-3xl font-bold"><?php echo $stats['nuevos_usuarios']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de acciones rápidas -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h3 class="text-xl font-bold mb-4">Acciones Rápidas</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="usuarios.php" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-users-cog text-2xl text-indigo-600 mb-2"></i>
                        <h4 class="font-bold">Gestionar Usuarios</h4>
                        <p class="text-sm text-gray-600">Administra los usuarios del sistema</p>
                    </a>
                    <a href="#" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-chart-bar text-2xl text-green-600 mb-2"></i>
                        <h4 class="font-bold">Estadísticas</h4>
                        <p class="text-sm text-gray-600">Ver estadísticas de uso</p>
                    </a>
                    <a href="#" class="p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-cog text-2xl text-yellow-600 mb-2"></i>
                        <h4 class="font-bold">Configuración</h4>
                        <p class="text-sm text-gray-600">Ajustes del sistema</p>
                    </a>
                </div>
            </div>

            <!-- Últimos usuarios registrados -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Últimos Usuarios Registrados</h3>
                    <a href="usuarios.php" class="text-indigo-600 hover:underline">Ver todos</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="py-2 px-4 text-left">Usuario</th>
                                <th class="py-2 px-4 text-left">Fecha de Registro</th>
                                <th class="py-2 px-4 text-left">Estado</th>
                                <th class="py-2 px-4 text-left">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT id, nombre_usuario, created_at, last_seen FROM users ORDER BY created_at DESC LIMIT 5");
                            while ($user = $stmt->fetch()):
                                $isActive = strtotime($user['last_seen']) > (time() - 86400); // Activo en las últimas 24h
                            ?>
                            <tr class="border-t">
                                <td class="py-3 px-4"><?php echo htmlspecialchars($user['nombre_usuario']); ?></td>
                                <td class="py-3 px-4"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded-full text-xs <?php echo $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $isActive ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <a href="usuarios/editar.php?id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:underline">Ver</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
