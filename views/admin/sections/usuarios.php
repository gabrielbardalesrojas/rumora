<?php
// Obtener lista de usuarios
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Usuarios</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?section=usuarios&action=nuevo" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> Nuevo Usuario
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td>#<?= $usuario['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if (!empty($usuario['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($usuario['avatar']) ?>" class="rounded-circle me-2" width="32" height="32" alt="Avatar">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                        <?= strtoupper(substr($usuario['username'] ?? 'U', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($usuario['username'] ?? 'Sin nombre') ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($usuario['email'] ?? '') ?></td>
                        <td>
                            <?php if ($usuario['activo'] == 1): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($usuario['is_admin'] == 1): ?>
                                <span class="badge bg-primary">Administrador</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Usuario</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="?section=usuarios&action=editar&id=<?= $usuario['id'] ?>" class="btn btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-outline-<?= $usuario['activo'] ? 'warning' : 'success' ?>" 
                                            onclick="cambiarEstado(<?= $usuario['id'] ?>, <?= $usuario['activo'] ?>)" 
                                            title="<?= $usuario['activo'] ? 'Desactivar' : 'Activar' ?> Usuario">
                                        <i class="fas fa-<?= $usuario['activo'] ? 'user-slash' : 'user-check' ?>"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="confirmarEliminar(<?= $usuario['id'] ?>)" 
                                            title="Eliminar Usuario">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function cambiarEstado(id, estadoActual) {
    if (confirm(`¿Estás seguro de que deseas ${estadoActual ? 'desactivar' : 'activar'} este usuario?`)) {
        // Aquí iría la llamada AJAX para cambiar el estado
        alert(`Estado del usuario ${id} cambiado a ${estadoActual ? 'inactivo' : 'activo'}`);
        // Recargar la página para ver los cambios
        location.reload();
    }
}

function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
        // Aquí iría la llamada AJAX para eliminar el usuario
        alert(`Usuario ${id} eliminado`);
        // Recargar la página para ver los cambios
        location.reload();
    }
}
</script>
