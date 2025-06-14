<?php
// Verificar permisos
if (!verificarPermiso('modulos', 'ver', $_SESSION['user_perfil_id'], $pdo)) {
    echo '<div class="alert alert-danger">No tienes permisos para ver módulos.</div>';
    return;
}

// Obtener ID del módulo
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger">ID de módulo inválido.</div>';
    return;
}

// Obtener datos del módulo
$sqlModulo = "SELECT * FROM tblmodulos WHERE IdModulo = :id";
$stmtModulo = $pdo->prepare($sqlModulo);
$stmtModulo->execute([':id' => $id]);
$modulo_data = $stmtModulo->fetch(); // Renombrado para evitar conflicto con $modulo global

if (!$modulo_data) {
    echo '<div class="alert alert-danger">Módulo no encontrado.</div>';
    return;
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-cube"></i> Detalles del Módulo</h2>
            <div>
                <?php if (verificarPermiso('modulos', 'editar', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a href="?modulo=modulos&accion=editar&id=<?php echo $modulo_data['IdModulo']; ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <?php endif; ?>
                <a href="?modulo=modulos" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="card-title">Información del Módulo</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 20%">ID:</th>
                                <td><?php echo $modulo_data['IdModulo']; ?></td>
                            </tr>
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo htmlspecialchars($modulo_data['NombreModulo']); ?></td>
                            </tr>
                            <tr>
                                <th>Descripción:</th>
                                <td><?php echo htmlspecialchars($modulo_data['DescripcionModulo']); ?></td>
                            </tr>
                            <tr>
                                <th>Estado:</th>
                                <td>
                                    <span class="badge <?php 
                                        echo $modulo_data['EstadoModulo'] === 'activo' ? 'bg-success' : 'bg-danger'; 
                                    ?>">
                                        <?php echo ucfirst($modulo_data['EstadoModulo']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Fecha Creación:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($modulo_data['FechaCreacion'])); ?></td>
                            </tr>
                            <tr>
                                <th>Última Actualización:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($modulo_data['FechaActualizacion'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
