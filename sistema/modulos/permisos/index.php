<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar permisos para ver la lista de perfiles (que es el punto de entrada para gestionar permisos)
if (!verificarPermiso('permisos', 'ver', $_SESSION['user_perfil_id'], $pdo)) {
    echo '<div class="alert alert-danger">No tienes permisos para ver la gestión de permisos.</div>';
    return;
}

// --- Configuración de Paginación ---
$records_per_page = 10; // Número de perfiles por página
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Obtener el total de perfiles para la paginación
$sql_count = "SELECT COUNT(*) FROM tblperfiles";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute();
$total_perfiles = $stmt_count->fetchColumn();
$total_pages = ceil($total_perfiles / $records_per_page);

// Asegurarse de que la página actual no exceda el total de páginas
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $records_per_page;
} elseif ($total_pages == 0) {
    $current_page = 1;
    $offset = 0;
}

// Obtener perfiles con LIMIT y OFFSET para la paginación
$sql = "SELECT * FROM tblperfiles ORDER BY NombrePerfil LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$perfiles = $stmt->fetchAll();

// Obtener mensajes de la URL (desde procesos.php)
$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-key"></i> Gestión de Permisos por Perfil</h2>
            <!-- No hay un botón "Nuevo Permiso" directo aquí, se gestionan por perfil -->
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
                                <th>ID Perfil</th>
                                <th>Nombre Perfil</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($perfiles) > 0): ?>
                                <?php foreach ($perfiles as $perfil): ?>
                                <tr>
                                    <td><?php echo $perfil['IdPerfil']; ?></td>
                                    <td><?php echo htmlspecialchars($perfil['NombrePerfil']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($perfil['DescripcionPerfil'], 0, 70)) . (strlen($perfil['DescripcionPerfil']) > 70 ? '...' : ''); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $perfil['EstadoPerfil'] === 'activo' ? 'bg-success' : 'bg-danger'; 
                                        ?>">
                                            <?php echo ucfirst($perfil['EstadoPerfil']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (verificarPermiso('permisos', 'editar', $_SESSION['user_perfil_id'], $pdo)): ?>
                                            <a href="?modulo=permisos&accion=gestionar&id_perfil=<?php echo $perfil['IdPerfil']; ?>" 
                                               class="btn btn-outline-primary" title="Gestionar Permisos">
                                                <i class="fas fa-cogs"></i> Gestionar
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay perfiles registrados para gestionar permisos.</td>
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
                                <a class="page-link" href="?modulo=permisos&page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?modulo=permisos&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?modulo=permisos&page=<?php echo $current_page + 1; ?>" aria-label="Next">
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
