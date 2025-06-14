<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar permisos
if (!verificarPermiso('respaldos_documentos', 'ver', $_SESSION['user_perfil_id'], $pdo)) {
    echo '<div class="alert alert-danger">No tienes permisos para ver documentos de respaldo.</div>';
    return;
}

// --- Configuración de Paginación ---
$records_per_page = 10; // Número de documentos por página
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Obtener el total de documentos para la paginación
$sql_count = "SELECT COUNT(*) FROM tbldocumentos";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute();
$total_documentos = $stmt_count->fetchColumn();
$total_pages = ceil($total_documentos / $records_per_page);

// Asegurarse de que la página actual no exceda el total de páginas
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $records_per_page;
} elseif ($total_pages == 0) {
    $current_page = 1;
    $offset = 0;
}

// Obtener documentos con LIMIT y OFFSET para la paginación
$sql = "SELECT * FROM tbldocumentos ORDER BY FechaCargaDocumento DESC, NombreDocumento LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$documentos = $stmt->fetchAll();

// Obtener mensajes de la URL (desde procesos.php)
$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-file-archive"></i> Gestión de Respaldos Documentales</h2>
            <?php if (verificarPermiso('respaldos_documentos', 'crear', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a href="?modulo=respaldos_documentos&accion=crear" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Documento
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
                                <th>Observación</th>
                                <th>Fecha Carga</th>
                                <th>Usuario Carga</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($documentos) > 0): ?>
                                <?php foreach ($documentos as $documento): ?>
                                <tr>
                                    <td><?php echo $documento['IdDocumento']; ?></td>
                                    <td><?php echo htmlspecialchars($documento['NombreDocumento']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($documento['ObservacionDocumento'], 0, 70)) . (strlen($documento['ObservacionDocumento']) > 70 ? '...' : ''); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($documento['FechaCargaDocumento'])); ?></td>
                                    <td><?php echo htmlspecialchars($documento['UsuarioDocumento']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?modulo=respaldos_documentos&accion=ver&id=<?php echo $documento['IdDocumento']; ?>" 
                                               class="btn btn-outline-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (verificarPermiso('respaldos_documentos', 'editar', $_SESSION['user_perfil_id'], $pdo)): ?>
                                            <a href="?modulo=respaldos_documentos&accion=editar&id=<?php echo $documento['IdDocumento']; ?>" 
                                               class="btn btn-outline-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (verificarPermiso('respaldos_documentos', 'eliminar', $_SESSION['user_perfil_id'], $pdo)): ?>
                                            <a href="modulos/respaldos_documentos/procesos.php?accion=eliminar&id=<?php echo $documento['IdDocumento']; ?>" 
                                               class="btn btn-outline-danger" title="Eliminar"
                                               onclick="return confirm('¿Estás seguro de ELIMINAR este documento de forma permanente? Esta acción también eliminará el archivo físico.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No hay documentos de respaldo registrados.</td>
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
                                <a class="page-link" href="?modulo=respaldos_documentos&page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?modulo=respaldos_documentos&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?modulo=respaldos_documentos&page=<?php echo $current_page + 1; ?>" aria-label="Next">
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
