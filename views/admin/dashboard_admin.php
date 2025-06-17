<?php
session_start();

// Si el usuario no es administrador, redirigir al index
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Incluir configuración de la base de datos
require_once '../../config/database.php';

// Inicializar variables
$action = $_GET['action'] ?? 'dashboard';
$username = $_SESSION['admin_email'] ?? 'Administrador';
$message = '';
$message_type = '';

// Incluir la vista de actividad si es necesario
if ($action === 'actividad') {
    header("Location: actividad.php");
    exit();
}

// Inicializar variables para búsqueda y paginación
$search = $_GET['search'] ?? '';
$estado = $_GET['estado'] ?? 'todos';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$usuarios_por_pagina = 10;
$offset = ($pagina - 1) * $usuarios_por_pagina;
$usuarios = [];
$total_usuarios = 0;
$total_paginas = 1;

// Obtener estadísticas para el dashboard
if ($action === 'dashboard') {
    // Total de usuarios
    $total_usuarios = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // Usuarios activos (últimos 30 días)
    $usuarios_activos = $pdo->query("SELECT COUNT(*) FROM users WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
    
    // Nuevos usuarios (últimos 7 días)
    $nuevos_usuarios = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    
    // Usuarios con perfil oculto (is_public = 0)
    $usuarios_ocultos = $pdo->query("SELECT COUNT(*) FROM users WHERE is_public = 0")->fetchColumn();
    
    // Usuarios por mes (para el gráfico)
    $usuarios_por_mes = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as mes, 
            COUNT(*) as total 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY mes 
        ORDER BY mes ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar datos para el gráfico
    $meses = [];
    $totales = [];
    foreach ($usuarios_por_mes as $fila) {
        $meses[] = date('M Y', strtotime($fila['mes'] . '-01'));
        $totales[] = (int)$fila['total'];
    }
}

// Manejar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cambiar visibilidad de usuario
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_visibility') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $is_public = filter_input(INPUT_POST, 'is_public', FILTER_VALIDATE_INT);
        
        if ($id && $is_public !== null) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET is_public = ? WHERE id = ?");
                if ($stmt->execute([$is_public, $id])) {
                    $message = 'Visibilidad del perfil actualizada correctamente';
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Error al actualizar la visibilidad: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
    // Eliminar usuario
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if ($id) {
            try {
                // No permitir eliminar al propio administrador
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ? AND is_admin = 0");
                if ($stmt->execute([$id, $_SESSION['admin_id']])) {
                    $message = 'Usuario eliminado correctamente';
                    $message_type = 'success';
                } else {
                    $message = 'No se pudo eliminar el usuario';
                    $message_type = 'warning';
                }
            } catch (PDOException $e) {
                $message = 'Error al eliminar el usuario: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// Obtener estadísticas para el dashboard
$stats = [
    'total_usuarios' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'usuarios_activos' => $pdo->query("SELECT COUNT(*) FROM users WHERE last_seen >= NOW() - INTERVAL 30 DAY")->fetchColumn(),
    'nuevos_usuarios' => $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn()
];

// Obtener usuarios para la sección de gestión
if ($action === 'usuarios') {
    $search = $_GET['search'] ?? '';
    $estado = $_GET['estado'] ?? 'todos';
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $por_pagina = 10;
    $offset = ($pagina - 1) * $por_pagina;
    
    $query = "SELECT * FROM users WHERE 1=1";
    $params = [];
    
    // Aplicar filtros
    if (!empty($search)) {
        $query .= " AND (nombre_usuario LIKE ? OR numero LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($estado === 'activos') {
        $query .= " AND last_seen >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    } elseif ($estado === 'inactivos') {
        $query .= " AND (last_seen IS NULL OR last_seen < DATE_SUB(NOW(), INTERVAL 30 DAY))";
    }
    
    // Contar total de registros
    $countStmt = $pdo->prepare(str_replace('SELECT *', 'SELECT COUNT(*) as total', $query));
    $countStmt->execute($params);
    $total_usuarios = $countStmt->fetch()['total'];
    $total_paginas = ceil($total_usuarios / $por_pagina);
    
    // Aplicar paginación
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $por_pagina;
    $params[] = $offset;
    
    // Obtener usuarios
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - RUMORA</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Variables de tema */
        :root {
            --bg-color: #f8f9fa;
            --text-color: #212529;
            --card-bg: #ffffff;
            --card-border: #e9ecef;
            --header-bg: #f8f9fa;
            --header-border: rgba(0, 0, 0, 0.05);
            --dropdown-bg: #ffffff;
            --dropdown-hover: #f8f9fa;
            --shadow-color: rgba(0, 0, 0, 0.075);
            --primary-color: #0d6efd;
            --sidebar-bg: #343a40;
            --sidebar-text: #dee2e6;
            --sidebar-hover: #495057;
        }

        /* Estilos para el modo oscuro */
        [data-bs-theme="dark"] {
            --bg-color: #1a1a2e;
            --text-color: #f8f9fa;
            --card-bg: #16213e;
            --card-border: #2a3a5f;
            --header-bg: #0f3460;
            --header-border: rgba(255, 255, 255, 0.05);
            --dropdown-bg: #1a1a2e;
            --dropdown-hover: #2a3a5f;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --primary-color: #4da3ff;
            --sidebar-bg: #0f172a;
            --sidebar-text: #e2e8f0;
            --sidebar-hover: #1e293b;
        }

        /* Aplicar estilos generales */
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Sidebar */
        .sidebar {
            background-color: var(--sidebar-bg);
            min-height: 100vh;
            color: var(--sidebar-text);
        }

        .sidebar .nav-link {
            color: var(--sidebar-text);
            padding: 0.5rem 1rem;
            margin: 0.2rem 0;
            border-radius: 0.25rem;
        }

        .sidebar .nav-link:hover, 
        .sidebar .nav-link:focus,
        .sidebar .nav-link.active {
            background-color: var(--sidebar-hover);
            color: #fff;
        }

        .sidebar .nav-link i {
            width: 1.5rem;
            text-align: center;
            margin-right: 0.5rem;
        }

        /* Tarjetas */
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            box-shadow: 0 0.125rem 0.25rem var(--shadow-color);
        }

        .card-header {
            background-color: var(--header-bg);
            border-bottom: 1px solid var(--header-border);
        }

        /* Tablas */
        .table {
            color: var(--text-color);
        }

        .table-hover > tbody > tr:hover {
            --bs-table-accent-bg: var(--dropdown-hover);
        }

        /* Formularios */
        .form-control, 
        .form-select {
            background-color: var(--card-bg);
            border-color: var(--card-border);
            color: var(--text-color);
        }

        .form-control:focus, 
        .form-select:focus {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* Dropdowns */
        .dropdown-menu {
            background-color: var(--dropdown-bg);
            border: 1px solid var(--card-border);
        }

        .dropdown-item {
            color: var(--text-color);
        }

        .dropdown-item:hover, 
        .dropdown-item:focus {
            background-color: var(--dropdown-hover);
            color: var(--text-color);
        }

        /* Botones */
        .btn-outline-secondary {
            border-color: var(--card-border);
            color: var(--text-color);
        }

        .btn-outline-secondary:hover {
            background-color: var(--dropdown-hover);
            border-color: var(--card-border);
            color: var(--text-color);
        }

        /* Interruptor de tema */
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-switch .form-check-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
    <!-- Estilos personalizados -->
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .sidebar .nav-link {
            color: #333;
            border-radius: 5px;
            margin: 2px 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #e9ecef;
            color: #0d6efd;
        }
        .main-content {
            padding: 20px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .stat-card {
            border-left: 4px solid #0d6efd;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
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
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0 sidebar">
                <div class="d-flex flex-column p-3">
                    <a href="?action=dashboard" class="d-flex align-items-center mb-4 text-decoration-none">
                        <span class="fs-4 fw-bold text-primary">RUMORA</span>
                    </a>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="?action=dashboard" class="nav-link <?php echo $action === 'dashboard' ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="?action=usuarios" class="nav-link <?php echo $action === 'usuarios' ? 'active' : ''; ?>">
                                <i class="fas fa-users me-2"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="actividad.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'actividad.php' ? 'active' : ''; ?>">
                                <i class="fas fa-chart-line me-2"></i> Actividad
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="?action=configuracion" class="nav-link <?php echo $action === 'configuracion' ? 'active' : ''; ?>">
                                <i class="fas fa-cog me-2"></i> Configuración
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <strong><?php echo htmlspecialchars(explode('@', $username)[0]); ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item" href="#">Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../logout.php">Cerrar sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['show_activity_prompt'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <strong>¡Atención!</strong> Selecciona un usuario de la lista para ver su actividad.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($action === 'dashboard'): ?>
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Resumen General</h1>
                    </div>

                    <!-- Tarjetas de estadísticas -->
                    <div class="row mb-4">
                        <!-- Total de usuarios -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 stat-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="p-3 rounded bg-primary bg-opacity-10 text-primary me-3">
                                            <i class="fas fa-users fa-2x"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-1">Total de Usuarios</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['total_usuarios']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Usuarios activos -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 stat-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="p-3 rounded bg-success bg-opacity-10 text-success me-3">
                                            <i class="fas fa-user-check fa-2x"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-1">Usuarios Activos (30 días)</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['usuarios_activos']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nuevos usuarios -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 stat-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="p-3 rounded bg-warning bg-opacity-10 text-warning me-3">
                                            <i class="fas fa-user-plus fa-2x"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-1">Nuevos Usuarios (7 días)</h6>
                                            <h3 class="mb-0"><?php echo number_format($stats['nuevos_usuarios']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Usuarios bloqueados -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Perfiles Ocultos</div>
                                            <div class="h2 mb-0 font-weight-bold text-gray-800"><?php echo number_format($usuarios_ocultos); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-lock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de crecimiento -->
                    <div class="row">
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <!-- Card Header - Dropdown -->
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Registros de Usuarios (Últimos 12 meses)</h6>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="chart-area">
                                        <canvas id="usuariosChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico de dona -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <!-- Card Header - Dropdown -->
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Distribución de Usuarios</h6>
                                </div>
                                <!-- Card Body -->
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="distribucionUsuarios"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <span class="me-2">
                                            <i class="fas fa-circle text-primary"></i> Activos
                                        </span>
                                        <span class="me-2">
                                            <i class="fas fa-circle text-success"></i> Nuevos
                                        </span>
                                        <span class="me-2">
                                            <i class="fas fa-circle text-warning"></i> Inactivos
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de búsqueda y filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="get" action="" class="row g-3">
                                <input type="hidden" name="action" value="usuarios">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o teléfono..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search me-1"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <select name="estado" class="form-select" onchange="this.form.submit()">
                                        <option value="todos" <?php echo ($estado ?? 'todos') === 'todos' ? 'selected' : ''; ?>>Todos los estados</option>
                                        <option value="activos" <?php echo ($estado ?? '') === 'activos' ? 'selected' : ''; ?>>Solo activos</option>
                                        <option value="inactivos" <?php echo ($estado ?? '') === 'inactivos' ? 'selected' : ''; ?>>Solo inactivos</option>
                                    </select>
                                </div>
                                <div class="col-md-2 text-end">
                                    <a href="#" class="btn btn-success w-100">
                                        <i class="fas fa-file-export me-1"></i> Exportar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de usuarios -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Usuario</th>
                                            <th>Teléfono</th>
                                            <th>Ubicación</th>
                                            <th>Registro</th>
                                            <th>Estado</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($usuarios)): ?>
                                            <?php foreach ($usuarios as $usuario): 
                                                $esActivo = $usuario['last_seen'] && strtotime($usuario['last_seen']) > (time() - 86400 * 30);
                                                $esAdmin = $usuario['is_admin'] == 1;
                                            ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($usuario['id']); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($usuario['avatar'])): ?>
                                                            <img src="<?php echo htmlspecialchars($usuario['avatar']); ?>" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                                                        <?php else: ?>
                                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></div>
                                                            <?php if ($esAdmin): ?>
                                                                <span class="badge bg-warning text-dark">Admin</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($usuario['numero']); ?></td>
                                                <td><?php echo htmlspecialchars(($usuario['departamento'] ?? '') . (!empty($usuario['provincia']) ? ', ' . $usuario['provincia'] : '')); ?></td>
                                                <td><?php echo !empty($usuario['created_at']) ? date('d/m/Y', strtotime($usuario['created_at'])) : ''; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $esActivo ? 'success' : 'secondary'; ?>">
                                                        <?php echo $esActivo ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=editar_usuario&id=<?php echo $usuario['id']; ?>" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Cambiar visibilidad de este perfil?');">
                                                            <input type="hidden" name="action" value="toggle_visibility">
                                                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="is_public" value="<?php echo ($usuario['is_public'] ?? 1) ? 0 : 1; ?>">
                                                            <button type="submit" class="btn btn-outline-<?php echo ($usuario['is_public'] ?? 1) ? 'success' : 'warning'; ?>" data-bs-toggle="tooltip" title="<?php echo ($usuario['is_public'] ?? 1) ? 'Ocultar' : 'Mostrar'; ?> perfil">
                                                                <i class="fas fa-eye<?php echo ($usuario['is_public'] ?? 1) ? '' : '-slash'; ?>"></i>
                                                            </button>
                                                        </form>
                                                        <?php if (!$esAdmin): ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.');">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger" data-bs-toggle="tooltip" title="Eliminar">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">No se encontraron usuarios que coincidan con los criterios de búsqueda.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <?php if (isset($total_paginas) && $total_paginas > 1): ?>
                            <nav aria-label="Paginación de usuarios" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?action=usuarios&pagina=<?php echo $pagina - 1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($estado) ? '&estado='.urlencode($estado) : ''; ?>">Anterior</a>
                                    </li>
                                    
                                    <?php 
                                    $inicio = max(1, $pagina - 2);
                                    $fin = min($total_paginas, $pagina + 2);
                                    
                                    if ($inicio > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    
                                    for ($i = $inicio; $i <= $fin; $i++): ?>
                                        <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                                            <a class="page-link" href="?action=usuarios&pagina=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($estado) ? '&estado='.urlencode($estado) : ''; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php 
                                    endfor; 
                                    
                                    if ($fin < $total_paginas) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    ?>
                                    
                                    <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?action=usuarios&pagina=<?php echo $pagina + 1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($estado) ? '&estado='.urlencode($estado) : ''; ?>">Siguiente</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>

                            <?php if (isset($total_usuarios) && $total_usuarios > 0): ?>
                            <div class="text-muted text-center mt-2">
                                Mostrando <?php echo count($usuarios); ?> de <?php echo $total_usuarios; ?> usuarios
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($action === 'usuarios'): 
                    // Construir la consulta base
                    $sql = "SELECT * FROM users WHERE 1=1";
                    $params = [];
                    $where = [];

                    // Aplicar filtros de búsqueda
                    if (!empty($search)) {
                        $where[] = "(nombre_usuario LIKE ? OR numero LIKE ?)";
                        $params[] = "%$search%";
                        $params[] = "%$search%";
                    }

                    // Aplicar filtro de estado
                    if ($estado === 'activos') {
                        $where[] = "last_seen >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    } elseif ($estado === 'inactivos') {
                        $where[] = "(last_seen < DATE_SUB(NOW(), INTERVAL 30 DAY) OR last_seen IS NULL)";
                    } elseif ($estado === 'bloqueados') {
                        $where[] = "is_banned = 1";
                    }

                    // Aplicar condiciones WHERE si existen
                    if (!empty($where)) {
                        $sql .= " AND " . implode(" AND ", $where);
                    }

                    // Contar total de usuarios para la paginación
                    $count_sql = "SELECT COUNT(*) FROM (" . str_replace('SELECT *', 'SELECT id', $sql) . ") AS total";
                    $stmt = $pdo->prepare($count_sql);
                    $stmt->execute($params);
                    $total_usuarios = $stmt->fetchColumn();
                    $total_paginas = ceil($total_usuarios / $usuarios_por_pagina);

                    // Obtener usuarios para la página actual
                    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
                    $params[] = $usuarios_por_pagina;
                    $params[] = $offset;

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <div class="d-flex align-items-center">
                        <div class="form-check form-switch me-3">
                            <input class="form-check-input" type="checkbox" id="darkModeToggle" style="cursor: pointer;">
                            <label class="form-check-label" for="darkModeToggle" style="cursor: pointer;">
                                <i class="fas fa-moon"></i>
                            </label>
                        </div>
                        <span class="me-3">Bienvenido, <?php echo htmlspecialchars($username); ?></span>
                        <a href="../../logout.php" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-sign-out-alt me-1"></i> Cerrar sesión
                        </a>
                    </div>
                    </div>

                    <!-- Barra de búsqueda y filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="get" action="" class="row g-3">
                                <input type="hidden" name="action" value="usuarios">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o teléfono..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search me-1"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <select name="estado" class="form-select" onchange="this.form.submit()">
                                        <option value="todos" <?php echo ($estado ?? 'todos') === 'todos' ? 'selected' : ''; ?>>Todos los estados</option>
                                        <option value="activos" <?php echo ($estado ?? '') === 'activos' ? 'selected' : ''; ?>>Solo activos</option>
                                        <option value="inactivos" <?php echo ($estado ?? '') === 'inactivos' ? 'selected' : ''; ?>>Solo inactivos</option>
                                    </select>
                                </div>
                                <div class="col-md-2 text-end">
                                    <a href="#" class="btn btn-success w-100">
                                        <i class="fas fa-file-export me-1"></i> Exportar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de usuarios -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Usuario</th>
                                            <th>Teléfono</th>
                                            <th>Ubicación</th>
                                            <th>Registro</th>
                                            <th>Estado</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($usuarios)): ?>
                                            <?php foreach ($usuarios as $usuario): 
                                                $esActivo = $usuario['last_seen'] && strtotime($usuario['last_seen']) > (time() - 86400 * 30);
                                                $esAdmin = $usuario['is_admin'] == 1;
                                            ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($usuario['id']); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($usuario['avatar'])): ?>
                                                            <img src="<?php echo htmlspecialchars($usuario['avatar']); ?>" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                                                        <?php else: ?>
                                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></div>
                                                            <?php if ($esAdmin): ?>
                                                                <span class="badge bg-warning text-dark">Admin</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($usuario['numero']); ?></td>
                                                <td><?php echo htmlspecialchars(($usuario['departamento'] ?? '') . (!empty($usuario['provincia']) ? ', ' . $usuario['provincia'] : '')); ?></td>
                                                <td><?php echo !empty($usuario['created_at']) ? date('d/m/Y', strtotime($usuario['created_at'])) : ''; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $esActivo ? 'success' : 'secondary'; ?>">
                                                        <?php echo $esActivo ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=editar_usuario&id=<?php echo $usuario['id']; ?>" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Cambiar visibilidad de este perfil?');">
                                                            <input type="hidden" name="action" value="toggle_visibility">
                                                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="is_public" value="<?php echo ($usuario['is_public'] ?? 1) ? 0 : 1; ?>">
                                                            <button type="submit" class="btn btn-outline-<?php echo ($usuario['is_public'] ?? 1) ? 'success' : 'warning'; ?>" data-bs-toggle="tooltip" title="<?php echo ($usuario['is_public'] ?? 1) ? 'Ocultar' : 'Mostrar'; ?> perfil">
                                                                <i class="fas fa-eye<?php echo ($usuario['is_public'] ?? 1) ? '' : '-slash'; ?>"></i>
                                                            </button>
                                                        </form>
                                                        <?php if (!$esAdmin): ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.');">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger" data-bs-toggle="tooltip" title="Eliminar">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">No se encontraron usuarios que coincidan con los criterios de búsqueda.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <?php if (isset($total_paginas) && $total_paginas > 1): ?>
                            <nav aria-label="Paginación de usuarios" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?action=usuarios&pagina=<?php echo $pagina - 1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($estado) ? '&estado='.urlencode($estado) : ''; ?>">Anterior</a>
                                    </li>
                                    
                                    <?php 
                                    $inicio = max(1, $pagina - 2);
                                    $fin = min($total_paginas, $pagina + 2);
                                    
                                    if ($inicio > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    
                                    for ($i = $inicio; $i <= $fin; $i++): ?>
                                        <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                                            <a class="page-link" href="?action=usuarios&pagina=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($estado) ? '&estado='.urlencode($estado) : ''; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php 
                                    endfor; 
                                    
                                    if ($fin < $total_paginas) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    ?>
                                    
                                    <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?action=usuarios&pagina=<?php echo $pagina + 1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($estado) ? '&estado='.urlencode($estado) : ''; ?>">Siguiente</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>

                            <?php if (isset($total_usuarios) && $total_usuarios > 0): ?>
                            <div class="text-muted text-center mt-2">
                                Mostrando <?php echo count($usuarios); ?> de <?php echo $total_usuarios; ?> usuarios
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($action === 'estadisticas'): ?>
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Estadísticas</h1>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Usuarios Registrados</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="usuariosChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Distribución de Usuarios</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="distribucionChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Actividad Reciente</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Usuario</th>
                                                    <th>Acción</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="3" class="text-center py-4">
                                                        <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                                                        <p class="text-muted mb-0">No hay actividad reciente para mostrar</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($action === 'configuracion'): ?>
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Configuración del Sistema</h1>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Ajustes Generales</h5>
                                </div>
                                <div class="card-body">
                                    <form>
                                        <div class="mb-3">
                                            <label class="form-label">Nombre del Sitio</label>
                                            <input type="text" class="form-control" value="RUMORA">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Correo de Contacto</label>
                                            <input type="email" class="form-control" value="contacto@rumora.com">
                                        </div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="maintenanceMode" checked>
                                            <label class="form-check-label" for="maintenanceMode">Modo Mantenimiento</label>
                                        </div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="userRegistration" checked>
                                            <label class="form-check-label" for="userRegistration">Permitir registro de usuarios</label>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Seguridad</h5>
                                </div>
                                <div class="card-body">
                                    <form>
                                        <div class="mb-3">
                                            <label class="form-label">Número de intentos de inicio de sesión</label>
                                            <input type="number" class="form-control" value="5">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tiempo de bloqueo (minutos)</label>
                                            <input type="number" class="form-control" value="30">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Actualizar Configuración</button>
                                    </form>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Sistema</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Versión de PHP
                                            <span class="badge bg-primary rounded-pill"><?php echo phpversion(); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Versión de MySQL
                                            <span class="badge bg-primary rounded-pill"><?php echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Usuarios Registrados
                                            <span class="badge bg-success rounded-pill"><?php echo $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(); ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div> <!-- Cierre del div.col-md-9 -->
        </div> <!-- Cierre del div.row -->
    </div> <!-- Cierre del div.container-fluid -->

    <!-- Bootstrap JS y dependencias -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <!-- Script para el modo oscuro -->
    <script>
        // Función para aplicar el tema
        function applyTheme(isDark) {
            const html = document.documentElement;
            const toggle = document.getElementById('darkModeToggle');
            
            if (isDark) {
                html.setAttribute('data-bs-theme', 'dark');
                if (toggle) toggle.checked = true;
                localStorage.setItem('darkMode', 'enabled');
            } else {
                html.removeAttribute('data-bs-theme');
                if (toggle) toggle.checked = false;
                localStorage.setItem('darkMode', 'disabled');
            }
            
            // Actualizar los gráficos si existen
            if (window.myCharts) {
                window.myCharts.forEach(chart => chart.update());
            }
        }
        
        // Verificar preferencia guardada o del sistema al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            if (!darkModeToggle) return;
            
            const savedTheme = localStorage.getItem('darkMode');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            // Aplicar tema guardado o preferencia del sistema
            if (savedTheme === 'enabled' || (!savedTheme && prefersDark)) {
                applyTheme(true);
            } else {
                applyTheme(false);
            }
            
            // Manejar el cambio manual
            darkModeToggle.addEventListener('change', function() {
                applyTheme(this.checked);
            });
            
            // Actualizar el ícono del botón según el tema
            const updateToggleIcon = () => {
                const icon = darkModeToggle.nextElementSibling.querySelector('i');
                if (icon) {
                    icon.className = darkModeToggle.checked ? 'fas fa-sun' : 'fas fa-moon';
                }
            };
            
            darkModeToggle.addEventListener('change', updateToggleIcon);
            updateToggleIcon(); // Estado inicial
        });
    </script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Scripts personalizados -->
    <script>
    // Inicializar tooltips
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips de Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Gráfico de usuarios registrados por mes
        const ctx1 = document.getElementById('usuariosChart');
        if (ctx1) {
            const labels = <?php echo isset($meses) ? json_encode($meses) : '[]'; ?>;
            const data = {
                labels: labels,
                datasets: [{
                    label: 'Usuarios registrados',
                    data: <?php echo isset($totales) ? json_encode($totales) : '[]'; ?>,
                    fill: true,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    tension: 0.3
                }]
            };

            new Chart(ctx1, {
                type: 'line',
                data: data,
                options: {
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            left: 10,
                            right: 25,
                            top: 25,
                            bottom: 0
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                maxTicksLimit: 6
                            }
                        },
                        y: {
                            ticks: {
                                maxTicksLimit: 5,
                                padding: 10,
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            },
                            grid: {
                                color: 'rgb(234, 236, 244)',
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineColor: 'rgb(234, 236, 244)',
                                zeroLineBorderDash: [2],
                                zeroLineBorderDashOffset: [2]
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgb(255,255,255)',
                            bodyColor: '#858796',
                            titleMarginBottom: 10,
                            titleColor: '#6e707e',
                            titleFontSize: 14,
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: false,
                            intersect: false,
                            mode: 'index',
                            caretPadding: 10,
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y.toLocaleString();
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Gráfico de distribución de usuarios
        const ctx2 = document.getElementById('distribucionUsuarios');
        if (ctx2) {
            const totalUsuarios = <?php echo isset($total_usuarios) ? $total_usuarios : 0; ?>;
            const usuariosActivos = <?php echo isset($usuarios_activos) ? $usuarios_activos : 0; ?>;
            const usuariosInactivos = totalUsuarios - usuariosActivos;
            
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Activos', 'Inactivos'],
                    datasets: [{
                        data: [usuariosActivos, usuariosInactivos],
                        backgroundColor: ['#1cc88a', '#e74a3b'],
                        hoverBackgroundColor: ['#17a673', '#be2617'],
                        hoverBorderColor: 'rgba(234, 236, 244, 1)',
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        backgroundColor: 'rgb(255,255,255)',
                        bodyColor: '#858796',
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                    },
                    legend: {
                        display: false
                    },
                    cutout: '80%',
                },
            });
        }
    });
    </script>
</body>
</html>
    
<!-- Scripts personalizados -->
<script>
// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
