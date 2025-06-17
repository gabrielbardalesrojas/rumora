<?php
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php');
    exit();
}

// Incluir configuración de la base de datos
require_once '../../config/database.php';

// Obtener estadísticas generales
$stats = [
    'total_usuarios' => 0,
    'usuarios_activos' => 0,
    'nuevos_hoy' => 0,
    'nuevos_semana' => 0,
    'crecimiento_semanal' => 0
];

// Obtener total de usuarios
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['total_usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Obtener usuarios activos (últimos 15 minutos)
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE last_seen >= NOW() - INTERVAL 15 MINUTE");
$stmt->execute();
$stats['usuarios_activos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Obtener nuevos usuarios hoy
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$stats['nuevos_hoy'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Obtener nuevos usuarios en la última semana
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute();
$stats['nuevos_semana'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calcular crecimiento semanal
$stats['crecimiento_semanal'] = $stats['nuevos_semana'] > 0 ? 
    round(($stats['nuevos_semana'] / $stats['total_usuarios']) * 100, 1) : 0;

// Obtener actividad por hora del día actual
$horas_actividad = [];
$stmt = $pdo->query("
    SELECT 
        HOUR(created_at) as hora,
        COUNT(*) as total
    FROM users
    WHERE DATE(created_at) = CURDATE()
    GROUP BY HOUR(created_at)
    ORDER BY hora
");
$actividad_por_hora = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inicializar todas las horas con 0
for ($i = 0; $i < 24; $i++) {
    $hora = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
    $horas_actividad[$i] = ['hora' => $hora, 'total' => 0];
}

// Asignar los valores reales
foreach ($actividad_por_hora as $actividad) {
    $hora = (int)$actividad['hora'];
    if (isset($horas_actividad[$hora])) {
        $horas_actividad[$hora]['total'] = (int)$actividad['total'];
    }
}

// Obtener actividad por día de la semana
$dias_semana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$dias_actividad = [];

$stmt = $pdo->query("
    SELECT 
        DAYOFWEEK(created_at) as dia_semana,
        COUNT(*) as total
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DAYOFWEEK(created_at)
    ORDER BY dia_semana
");
$actividad_por_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inicializar todos los días con 0
foreach ($dias_semana as $index => $dia) {
    $dias_actividad[] = ['dia' => $dia, 'total' => 0];
}

// Asignar los valores reales (DAYOFWEEK devuelve 1=domingo, 2=lunes, etc.)
foreach ($actividad_por_dia as $actividad) {
    $indice = ($actividad['dia_semana'] + 5) % 7; // Convertir a índice 0=lunes, 6=domingo
    if (isset($dias_actividad[$indice])) {
        $dias_actividad[$indice]['total'] = (int)$actividad['total'];
    }
}

// Obtener actividad mensual de los últimos 6 meses
$meses_actividad = [];
$meses = [];

$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%b') as mes,
        COUNT(*) as total
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b')
    ORDER BY MIN(created_at) ASC
    LIMIT 6
");
$actividad_por_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rellenar con los últimos 6 meses
for ($i = 5; $i >= 0; $i--) {
    $mes = date('M', strtotime("-$i months"));
    $meses[] = $mes;
    
    // Buscar si hay datos para este mes
    $total = 0;
    foreach ($actividad_por_mes as $actividad) {
        if ($actividad['mes'] === $mes) {
            $total = (int)$actividad['total'];
            break;
        }
    }
    
    $meses_actividad[] = ['mes' => $mes, 'total' => $total];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Actividad - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #ffffff;
            --text-color: #212529;
            --card-bg: #ffffff;
            --card-border: #e9ecef;
            --header-bg: #f8f9fa;
            --header-border: rgba(0, 0, 0, 0.05);
            --dropdown-bg: #ffffff;
            --dropdown-hover: #f8f9fa;
            --shadow-color: rgba(0, 0, 0, 0.075);
            --stat-value: #0d6efd;
            --stat-label: #6c757d;
            --stat-icon: #6c757d;
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
            --stat-value: #4da3ff;
            --stat-label: #adb5bd;
            --stat-icon: #adb5bd;
        }

        /* Aplicar estilos */
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .stat-card {
            border-left: 4px solid var(--stat-value);
            transition: all 0.3s ease;
            background-color: var(--card-bg);
            border-color: var(--card-border);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem var(--shadow-color);
        }
        
        .stat-card .card-body {
            padding: 1.25rem 1.5rem;
        }
        
        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--stat-value);
            margin-bottom: 0.25rem;
        }
        
        .stat-card .stat-label {
            color: var(--stat-label);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .stat-card .stat-icon {
            font-size: 1.5rem;
            color: var(--stat-icon);
            opacity: 0.8;
        }
        
        .card {
            border: 1px solid var(--card-border);
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem var(--shadow-color);
            margin-bottom: 1.5rem;
            background-color: var(--card-bg);
            color: var(--text-color);
        }
        
        .card-header {
            background-color: var(--header-bg);
            border-bottom: 1px solid var(--header-border);
            font-weight: 600;
            padding: 1rem 1.5rem;
            color: var(--text-color);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .dropdown-menu {
            border: 1px solid var(--card-border);
            background-color: var(--dropdown-bg);
            box-shadow: 0 0.5rem 1rem var(--shadow-color);
            border-radius: 0.5rem;
            padding: 0.5rem 0;
        }
        
        .dropdown-item {
            padding: 0.5rem 1.5rem;
            font-size: 0.875rem;
            color: var(--text-color);
        }
        
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: var(--dropdown-hover);
            color: var(--text-color);
        }
        
        .dropdown-item.active, .dropdown-item:active {
            background-color: var(--dropdown-hover);
            color: var(--stat-value);
        }
        
        .btn-outline-secondary {
            border-color: var(--card-border);
            color: var(--text-color);
        }
        
        .form-check-input:checked {
            background-color: var(--stat-value);
            border-color: var(--stat-value);
        }
        
        .form-switch .form-check-input:focus {
            border-color: var(--stat-value);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .nav-link {
            color: var(--text-color);
        }
        
        .nav-link:hover, .nav-link:focus {
            color: var(--stat-value);
        }
        
        .nav-link.active {
            color: var(--stat-value);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <a href="dashboard_admin.php" class="btn btn-outline-secondary btn-sm me-3" title="Volver al panel">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <h1 class="h3 mb-0">Estadísticas de Actividad</h1>
            </div>
            <div class="d-flex align-items-center">
                <div class="form-check form-switch me-3">
                    <input class="form-check-input" type="checkbox" id="darkModeToggle" style="cursor: pointer;">
                    <label class="form-check-label" for="darkModeToggle" style="cursor: pointer;">
                        <i class="fas fa-moon"></i>
                    </label>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-calendar-alt me-1"></i> Este mes
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item active" href="#">Este mes</a></li>
                        <li><a class="dropdown-item" href="#">Mes pasado</a></li>
                        <li><a class="dropdown-item" href="#">Últimos 3 meses</a></li>
                        <li><a class="dropdown-item" href="#">Últimos 6 meses</a></li>
                        <li><a class="dropdown-item" href="#">Este año</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tarjetas de resumen -->
        <div class="row">
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card border-start border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="stat-label">Usuarios Totales</h6>
                                <div class="stat-value text-primary"><?php echo number_format($stats['total_usuarios']); ?></div>
                                <div class="text-muted small mt-1">
                                    <span class="trend-up">
                                        <i class="fas fa-arrow-up"></i> <?php echo $stats['crecimiento_semanal']; ?>% esta semana
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users fa-2x text-primary opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card border-start border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="stat-label">Usuarios Activos</h6>
                                <div class="stat-value text-success"><?php echo number_format($stats['usuarios_activos']); ?></div>
                                <div class="text-muted small mt-1">
                                    <span class="text-success">
                                        <i class="fas fa-circle"></i> En línea ahora
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-check fa-2x text-success opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card border-start border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="stat-label">Nuevos Hoy</h6>
                                <div class="stat-value text-warning">+<?php echo number_format($stats['nuevos_hoy']); ?></div>
                                <div class="text-muted small mt-1">
                                    <span class="trend-up">
                                        <i class="fas fa-arrow-up"></i> 12% ayer
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-plus fa-2x text-warning opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card border-start border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="stat-label">Nuevos (7d)</h6>
                                <div class="stat-value text-info">+<?php echo number_format($stats['nuevos_semana']); ?></div>
                                <div class="text-muted small mt-1">
                                    <span class="trend-up">
                                        <i class="fas fa-arrow-up"></i> <?php echo $stats['crecimiento_semanal']; ?>% vs semana pasada
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-chart-line fa-2x text-info opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Actividad Mensual</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="mb-0">Actividad por Día</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dailyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Actividad por Hora</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="hourlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Función para aplicar el tema
        function applyTheme(isDark) {
            const html = document.documentElement;
            const toggle = document.getElementById('darkModeToggle');
            
            if (isDark) {
                html.setAttribute('data-bs-theme', 'dark');
                toggle.checked = true;
                localStorage.setItem('darkMode', 'enabled');
            } else {
                html.removeAttribute('data-bs-theme');
                toggle.checked = false;
                localStorage.setItem('darkMode', 'disabled');
            }
            
            // Actualizar los gráficos para que se adapten al tema
            if (window.myCharts) {
                window.myCharts.forEach(chart => chart.update());
            }
        }
        
        // Verificar preferencia guardada o del sistema
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
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
                icon.className = darkModeToggle.checked ? 'fas fa-sun' : 'fas fa-moon';
            };
            
            darkModeToggle.addEventListener('change', updateToggleIcon);
            updateToggleIcon(); // Estado inicial
        });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Datos para las gráficas
        const hourlyData = {
            labels: <?php echo json_encode(array_column($horas_actividad, 'hora')); ?>,
            datasets: [{
                label: 'Usuarios activos',
                data: <?php echo json_encode(array_column($horas_actividad, 'total')); ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderColor: '#0d6efd',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        };

        const dailyData = {
            labels: <?php echo json_encode(array_column($dias_actividad, 'dia')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($dias_actividad, 'total')); ?>,
                backgroundColor: [
                    'rgba(13, 110, 253, 0.8)',
                    'rgba(25, 135, 84, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(13, 202, 240, 0.8)',
                    'rgba(111, 66, 193, 0.8)',
                    'rgba(253, 126, 20, 0.8)'
                ],
                borderWidth: 1
            }]
        };

        const monthlyData = {
            labels: <?php echo json_encode($meses); ?>,
            datasets: [{
                label: 'Usuarios activos',
                data: <?php echo json_encode(array_column($meses_actividad, 'total')); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        };

        // Configuración común para gráficos
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 14
                    },
                    padding: 12
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        };

        // Crear gráficos
        new Chart(document.getElementById('hourlyChart'), {
            type: 'line',
            data: hourlyData,
            options: chartOptions
        });

        new Chart(document.getElementById('dailyChart'), {
            type: 'doughnut',
            data: dailyData,
            options: {
                ...chartOptions,
                cutout: '70%',
                plugins: {
                    ...chartOptions.plugins,
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: monthlyData,
            options: chartOptions
        });
    });
    </script>
</body>
</html>
