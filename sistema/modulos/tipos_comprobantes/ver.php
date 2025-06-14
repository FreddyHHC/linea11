<?php
// Verificar permisos
if (!verificarPermiso('tipos_comprobantes', 'ver', $_SESSION['user_perfil_id'], $pdo)) {
    echo '<div class="alert alert-danger">No tienes permisos para ver tipos de comprobantes.</div>';
    return;
}

// Obtener ID del tipo de comprobante
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger">ID de tipo de comprobante inválido.</div>';
    return;
}

// Obtener datos del tipo de comprobante
$sqlTipoComprobante = "SELECT * FROM tblcomprobantes WHERE IdComprobante = :id";
$stmtTipoComprobante = $pdo->prepare($sqlTipoComprobante);
$stmtTipoComprobante->execute([':id' => $id]);
$tipo_comprobante = $stmtTipoComprobante->fetch();

if (!$tipo_comprobante) {
    echo '<div class="alert alert-danger">Tipo de comprobante no encontrado.</div>';
    return;
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-info-circle"></i> Detalles del Tipo de Comprobante</h2>
            <div>
                <?php if (verificarPermiso('tipos_comprobantes', 'editar', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a href="?modulo=tipos_comprobantes&accion=editar&id=<?php echo $tipo_comprobante['IdComprobante']; ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <?php endif; ?>
                <a href="?modulo=tipos_comprobantes" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="card-title">Información del Tipo de Comprobante</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 25%">ID:</th>
                                <td><?php echo $tipo_comprobante['IdComprobante']; ?></td>
                            </tr>
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo htmlspecialchars($tipo_comprobante['NombreComprobante']); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha Creación:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($tipo_comprobante['FechaCreacion'])); ?></td>
                            </tr>
                            <tr>
                                <th>Última Actualización:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($tipo_comprobante['FechaActualizacion'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
