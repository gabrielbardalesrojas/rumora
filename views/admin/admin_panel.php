<?php
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../../index.php");
    exit();
}

// Incluir configuración de la base de datos
require_once '../../config/database.php';

// Obtener estadísticas básicas
$stats = [
    'total_usuarios' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'usuarios_activos' => $pdo->query("SELECT COUNT(*) FROM users WHERE activo = 1")->fetchColumn(),
    'usuarios_bloqueados' => $pdo->query("SELECT COUNT(*) FROM users WHERE activo = 0")->fetchColumn(),
    'nuevos_usuarios' => $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - RUMORA</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,.125);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0 sidebar">
                <div class="d-flex flex-column p-3">
                    <a href="?" class="d-flex align-items-center mb-4 text-decoration-none">
                        <span class="fs-4">RUMORA Admin</span>
                    </a>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="?" class="nav-link active">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="?section=usuarios" class="nav-link">
                                <i class="fas fa-users me-2"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="?section=estadisticas" class="nav-link">
                                <i class="fas fa-chart-bar me-2"></i> Estadísticas
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i>
                            <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../../perfil.php">Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../index.php">Ir al Sitio</a></li>
                            <li><a class="dropdown-item text-danger" href="../../logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="col-md-9 ms-sm-auto p-4 main-content">
                <?php
                $section = $_GET['section'] ?? 'dashboard';
                
                switch($section) {
                    case 'usuarios':
                        include 'sections/usuarios.php';
                        break;
                    case 'estadisticas':
                        include 'sections/estadisticas.php';
                        break;
                    default:
                        include 'sections/dashboard.php';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>
