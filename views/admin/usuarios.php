<?php 
// Incluir configuración de la base de datos
require_once '../../config/database.php';

// Inicializar variables de búsqueda y filtro
$search = $_GET['search'] ?? '';
$estado = $_GET['estado'] ?? 'todos';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Construir la consulta base
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

// Contar el total de registros para la paginación
$countStmt = $pdo->prepare(str_replace('SELECT *', 'SELECT COUNT(*) as total', $query));
$countStmt->execute($params);
$total_usuarios = $countStmt->fetch()['total'];
$total_paginas = ceil($total_usuarios / $por_pagina);

// Aplicar paginación
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $por_pagina;
$params[] = $offset;

// Obtener los usuarios
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir el encabezado
include '../../includes/header.php'; 
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Usuarios</h1>
        <a href="/rumora/views/admin/dashboard_admin.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Panel
        </a>
    </div>

    <!-- Barra de búsqueda y filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="/admin/usuarios" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Buscar usuarios..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select" onchange="this.form.submit()">
                        <option value="todos" <?php echo ($_GET['estado'] ?? 'todos') === 'todos' ? 'selected' : ''; ?>>Todos los estados</option>
                        <option value="activos" <?php echo ($_GET['estado'] ?? '') === 'activos' ? 'selected' : ''; ?>>Solo activos</option>
                        <option value="inactivos" <?php echo ($_GET['estado'] ?? '') === 'inactivos' ? 'selected' : ''; ?>>Solo inactivos</option>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <a href="/admin/usuarios/exportar" class="btn btn-success">
                        <i class="fas fa-file-export"></i> Exportar a Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="card">
        <div class="card-body">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type'] === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                    <?php 
                    echo htmlspecialchars($_SESSION['message']); 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Teléfono</th>
                            <th>Ubicación</th>
                            <th>Registro</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($usuarios) > 0): ?>
                            <?php foreach ($usuarios as $usuario): 
                                $esActivo = $usuario['last_seen'] && strtotime($usuario['last_seen']) > (time() - 86400 * 30); // Activo en los últimos 30 días
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($usuario['avatar'])): ?>
                                            <img src="<?php echo htmlspecialchars($usuario['avatar']); ?>" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($usuario['nombre_usuario']); ?>
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
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-<?php echo ($usuario['is_public'] ?? 1) ? 'success' : 'warning'; ?>" 
                                                onclick="cambiarVisibilidad(<?php echo $usuario['id']; ?>, <?php echo ($usuario['is_public'] ?? 1) ? '0' : '1'; ?>)"
                                                data-bs-toggle="tooltip" title="<?php echo ($usuario['is_public'] ?? 1) ? 'Ocultar' : 'Mostrar'; ?> perfil">
                                            <i class="fas fa-eye<?php echo ($usuario['is_public'] ?? 1) ? '' : '-slash'; ?>"></i>
                                        </button>
                                        <?php if ($usuario['is_admin'] != 1): ?>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="confirmarEliminar(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['nombre_usuario']); ?>')"
                                                data-bs-toggle="tooltip" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
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
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Paginación de usuarios" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($estado) ? '&estado='.urlencode($estado) : ''; ?>">Anterior</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($estado) ? '&estado='.urlencode($estado) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($estado) ? '&estado='.urlencode($estado) : ''; ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

            <?php if ($total_usuarios > 0): ?>
            <div class="text-muted text-center mt-2">
                Mostrando <?php echo count($usuarios); ?> de <?php echo $total_usuarios; ?> usuarios
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Script para cambiar visibilidad del perfil -->
<script>
function cambiarVisibilidad(id, nuevoEstado) {
    if (confirm('¿Estás seguro de que deseas ' + (nuevoEstado == 1 ? 'mostrar' : 'ocultar') + ' este perfil?')) {
        fetch(`/rumora/views/admin/cambiar_visibilidad.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}&is_public=${nuevoEstado}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Error al actualizar la visibilidad del perfil');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        });
    }
}

// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include '../includes/footer.php'; ?>
