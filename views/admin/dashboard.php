<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <h1>Panel de Administración</h1>
    
    <!-- Tarjetas de estadísticas -->
    <div class="row mt-4">
        <div class="col-md-4 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Usuarios</h5>
                    <h2 class="display-4"><?php echo $stats['total_usuarios']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Usuarios Activos (24h)</h5>
                    <h2 class="display-4"><?php echo $stats['usuarios_activos']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Nuevos (7 días)</h5>
                    <h2 class="display-4"><?php echo $stats['nuevos_usuarios']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Menú de navegación -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Acciones rápidas</h5>
                </div>
                <div class="card-body">
                    <a href="/admin/usuarios" class="btn btn-primary me-2">
                        <i class="fas fa-users"></i> Gestionar Usuarios
                    </a>
                    <a href="/admin/estadisticas" class="btn btn-info me-2 text-white">
                        <i class="fas fa-chart-line"></i> Ver Estadísticas
                    </a>
                    <a href="/admin/configuracion" class="btn btn-secondary">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
