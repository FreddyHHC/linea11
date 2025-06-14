<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug: Entrando a usuarios/index.php -->";

// Verificar permisos
if (!verificarPermiso('usuarios', 'ver', $_SESSION['user_perfil_id'], $pdo)) {
    echo '<div class="alert alert-danger">No tienes permisos para ver usuarios.</div>';
    echo "<!-- Debug: Permiso 'ver' para 'usuarios' DENEGADO para perfil ID: " . $_SESSION['user_perfil_id'] . " -->";
    return;
}
echo "<!-- Debug: Permiso 'ver' para 'usuarios' CONCEDIDO para perfil ID: " . $_SESSION['user_perfil_id'] . " -->";

// --- Configuración de Paginación ---
$records_per_page = 50; // Número de usuarios por página
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Obtener el total de usuarios para la paginación
$sql_count = "SELECT COUNT(*) FROM tblusuarios";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute();
$total_users = $stmt_count->fetchColumn();
$total_pages = ceil($total_users / $records_per_page);

// Asegurarse de que la página actual no exceda el total de páginas
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $records_per_page;
} elseif ($total_pages == 0) {
    $current_page = 1;
    $offset = 0;
}

// Obtener usuarios con LIMIT y OFFSET para la paginación
$sql = "SELECT u.*, p.NombrePerfil 
        FROM tblusuarios u 
        INNER JOIN tblperfiles p ON u.IdPerfil = p.IdPerfil 
        ORDER BY u.NombresUsuario, u.ApellidosUsuario
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll();
echo "<!-- Debug: Número de usuarios encontrados en la página actual: " . count($usuarios) . " -->";

// Obtener mensajes de la URL (desde procesos.php)
$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users"></i> Gestión de Usuarios</h2>
            <?php if (verificarPermiso('usuarios', 'crear', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a href="?modulo=usuarios&accion=crear" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Usuario
                </a>
            <?php endif; ?>
        </div>
        
        <?php if ($status === 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php elseif ($status === 'danger'): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <li><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($message); ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>RUN</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Perfil</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($usuarios) > 0): ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['IdUsuario']; ?></td>
                                    <td><?php echo htmlspecialchars($usuario['RunUsuario']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['NombresUsuario'] . ' ' . $usuario['ApellidosUsuario']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['CorreoUsuario']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['NombrePerfil']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $usuario['EstadoUsuario'] === 'activo' ? 'bg-success' : 
                                                ($usuario['EstadoUsuario'] === 'inactivo' ? 'bg-danger' : 'bg-warning'); 
                                        ?>">
                                            <?php echo ucfirst($usuario['EstadoUsuario']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?modulo=usuarios&accion=ver&id=<?php echo $usuario['IdUsuario']; ?>" 
                                               class="btn btn-outline-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (verificarPermiso('usuarios', 'editar', $_SESSION['user_perfil_id'], $pdo)): ?>
                                            <a href="?modulo=usuarios&accion=editar&id=<?php echo $usuario['IdUsuario']; ?>" 
                                               class="btn btn-outline-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (verificarPermiso('usuarios', 'eliminar', $_SESSION['user_perfil_id'], $pdo)): ?>
                                            <a href="modulos/usuarios/procesos.php?accion=eliminar&id=<?php echo $usuario['IdUsuario']; ?>" 
                                               class="btn btn-outline-danger" title="Eliminar"
                                               onclick="return confirm('¿Estás seguro de ELIMINAR este usuario de forma permanente? Esta acción no se puede deshacer.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No hay usuarios registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?modulo=usuarios&page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?modulo=usuarios&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?modulo=usuarios&page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
