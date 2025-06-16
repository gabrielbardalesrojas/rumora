<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Usuarios</h1>
        <a href="/admin/dashboard" class="btn btn-secondary">
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
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Teléfono</th>
                            <th>Ubicación</th>
                            <th>Registro</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo $usuario['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($usuario['avatar']); ?>" class="rounded-circle me-2" width="32" height="32" alt="Avatar">
                                    <?php echo htmlspecialchars($usuario['nombre_usuario']); ?>
                                    <?php if ($usuario['is_admin']): ?>
                                        <span class="badge bg-warning ms-2">Admin</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($usuario['numero']); ?></td>
                            <td>
                                <?php 
                                $ubicacion = [];
                                if (!empty($usuario['departamento'])) $ubicacion[] = $usuario['departamento'];
                                if (!empty($usuario['provincia'])) $ubicacion[] = $usuario['provincia'];
                                echo htmlspecialchars(implode(', ', $ubicacion));
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $usuario['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $usuario['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="/perfil/<?php echo $usuario['id']; ?>" class="btn btn-sm btn-info" title="Ver perfil">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/admin/usuarios/toggle/<?php echo $usuario['id']; ?>" class="btn btn-sm btn-<?php echo $usuario['is_active'] ? 'warning' : 'success'; ?>" 
                                       title="<?php echo $usuario['is_active'] ? 'Desactivar' : 'Activar'; ?> cuenta"
                                       onclick="return confirm('¿Estás seguro de <?php echo $usuario['is_active'] ? 'desactivar' : 'activar'; ?> esta cuenta?')">
                                        <i class="fas <?php echo $usuario['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                    </a>
                                    <?php if (!$usuario['is_admin']): ?>
                                    <a href="#" class="btn btn-sm btn-danger" 
                                       title="Eliminar usuario"
                                       onclick="return confirm('¿Estás seguro de eliminar permanentemente este usuario?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <nav aria-label="Navegación de usuarios" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Anterior</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Siguiente</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
