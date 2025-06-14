<?php
// Verificar permisos
if (!verificarPermiso('tipos_ingresos', 'ver', $_SESSION['user_perfil_id'], $pdo)) {
    echo '<div class="alert alert-danger">No tienes permisos para ver tipos de ingresos.</div>';
    return;
}

// Obtener ID del tipo de ingreso
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger">ID de tipo de ingreso inválido.</div>';
    return;
}

// Obtener datos del tipo de ingreso
$sqlTipoIngreso = "SELECT * FROM tbltiposingresos WHERE IdTipoIngreso = :id";
$stmtTipoIngreso = $pdo->prepare($sqlTipoIngreso);
$stmtTipoIngreso->execute([':id' => $id]);
$tipo_ingreso = $stmtTipoIngreso->fetch();

if (!$tipo_ingreso) {
    echo '<div class="alert alert-danger">Tipo de ingreso no encontrado.</div>';
    return;
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-info-circle"></i> Detalles del Tipo de Ingreso</h2>
            <div>
                <?php if (verificarPermiso('tipos_ingresos', 'editar', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a href="?modulo=tipos_ingresos&accion=editar&id=<?php echo $tipo_ingreso['IdTipoIngreso']; ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <?php endif; ?>
                <a href="?modulo=tipos_ingresos" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="card-title">Información del Tipo de Ingreso</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 25%">ID:</th>
                                <td><?php echo $tipo_ingreso['IdTipoIngreso']; ?></td>
                            </tr>
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo htmlspecialchars($tipo_ingreso['NombreTipoIngreso']); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha Creación:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($tipo_ingreso['FechaCreacion'])); ?></td>
                            </tr>
                            <tr>
                                <th>Última Actualización:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($tipo_ingreso['FechaActualizacion'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
