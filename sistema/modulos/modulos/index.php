<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar permisos
if (!verificarPermiso('modulos', 'ver', $_SESSION['user_perfil_id'], $pdo)) {
    echo '<div class="alert alert-danger">No tienes permisos para ver módulos.</div>';
    return;
}

// --- Configuración de Paginación ---
$records_per_page = 50; // Número de módulos por página
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Obtener el total de módulos para la paginación
$sql_count = "SELECT COUNT(*) FROM tblmodulos";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute();
$total_modulos = $stmt_count->fetchColumn();
$total_pages = ceil($total_modulos / $records_per_page);

// Asegurarse de que la página actual no exceda el total de páginas
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $records_per_page;
} elseif ($total_pages == 0) {
    $current_page = 1;
    $offset = 0;
}

// Obtener módulos con LIMIT y OFFSET para la paginación
$sql = "SELECT * FROM tblmodulos ORDER BY NombreModulo LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$modulos = $stmt->fetchAll();

// Obtener mensajes de la URL (desde procesos.php)
$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-cubes"></i> Gestión de Módulos</h2>
            <?php if (verificarPermiso('modulos', 'crear', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a href="?modulo=modulos&accion=crear" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Módulo
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
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($modulos) > 0): ?>
                                <?php foreach ($modulos as $modulo_item): // Renombrado para evitar conflicto con $modulo global ?>
                                <tr>
                                    <td><?php echo $modulo_item['IdModulo']; ?></td>
                                    <td><?php echo htmlspecialchars($modulo_item['NombreModulo']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($modulo_item['DescripcionModulo'], 0, 70)) . (strlen($modulo_item['DescripcionModulo']) > 70 ? '...' : ''); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $modulo_item['EstadoModulo'] === 'activo' ? 'bg-success' : 'bg-danger'; 
                                        ?>">
                                            <?php echo ucfirst($modulo_item['EstadoModulo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?modulo=modulos&accion=ver&id=<?php echo $modulo_item['IdModulo']; ?>" 
                                               class="btn btn-outline-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (verificarPermiso('modulos', 'editar', $_SESSION['user_perfil_id'], $pdo)): ?>
                                            <a href="?modulo=modulos&accion=editar&id=<?php echo $modulo_item['IdModulo']; ?>" 
                                               class="btn btn-outline-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (verificarPermiso('modulos', 'eliminar', $_SESSION['user_perfil_id'], $pdo)): ?>
                                            <a href="modulos/modulos/procesos.php?accion=eliminar&id=<?php echo $modulo_item['IdModulo']; ?>" 
                                               class="btn btn-outline-danger" title="Eliminar"
                                               onclick="return confirm('¿Estás seguro de ELIMINAR este módulo de forma permanente? Esta acción no se puede deshacer.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay módulos registrados.</td>
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
                                <a class="page-link" href="?modulo=modulos&page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?modulo=modulos&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?modulo=modulos&page=<?php echo $current_page + 1; ?>" aria-label="Next">
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
