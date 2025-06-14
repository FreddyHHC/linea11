<?php
// Verificar permisos
if (!verificarPermiso('tipos_egresos', 'ver', $_SESSION['user_perfil_id'], $pdo)) {
    echo '<div class="alert alert-danger">No tienes permisos para ver tipos de egresos.</div>';
    return;
}

// Obtener ID del tipo de egreso
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger">ID de tipo de egreso inválido.</div>';
    return;
}

// Obtener datos del tipo de egreso
$sqlTipoEgreso = "SELECT * FROM tbltiposegresos WHERE IdTipoEgreso = :id";
$stmtTipoEgreso = $pdo->prepare($sqlTipoEgreso);
$stmtTipoEgreso->execute([':id' => $id]);
$tipo_egreso = $stmtTipoEgreso->fetch();

if (!$tipo_egreso) {
    echo '<div class="alert alert-danger">Tipo de egreso no encontrado.</div>';
    return;
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-info-circle"></i> Detalles del Tipo de Egreso</h2>
            <div>
                <?php if (verificarPermiso('tipos_egresos', 'editar', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a href="?modulo=tipos_egresos&accion=editar&id=<?php echo $tipo_egreso['IdTipoEgreso']; ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <?php endif; ?>
                <a href="?modulo=tipos_egresos" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="card-title">Información del Tipo de Egreso</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 25%">ID:</th>
                                <td><?php echo $tipo_egreso['IdTipoEgreso']; ?></td>
                            </tr>
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo htmlspecialchars($tipo_egreso['NombreTipoEgreso']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
