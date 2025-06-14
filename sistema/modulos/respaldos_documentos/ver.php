<?php
// Verificar permisos
if (!verificarPermiso('respaldos_documentos', 'ver', $_SESSION['user_perfil_id'], $pdo)) {
    echo '<div class="alert alert-danger">No tienes permisos para ver detalles de documentos.</div>';
    return;
}

// Obtener ID del documento
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger">ID de documento inválido.</div>';
    return;
}

// Obtener datos del documento
$sqlDocumento = "SELECT * FROM tbldocumentos WHERE IdDocumento = :id";
$stmtDocumento = $pdo->prepare($sqlDocumento);
$stmtDocumento->execute([':id' => $id]);
$documento = $stmtDocumento->fetch();

if (!$documento) {
    echo '<div class="alert alert-danger">Documento no encontrado.</div>';
    return;
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-info-circle"></i> Detalles del Documento</h2>
            <div>
                <?php if (verificarPermiso('respaldos_documentos', 'editar', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a href="?modulo=respaldos_documentos&accion=editar&id=<?php echo $documento['IdDocumento']; ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <?php endif; ?>
                <a href="?modulo=respaldos_documentos" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="card-title">Información del Documento</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 25%">ID:</th>
                                <td><?php echo $documento['IdDocumento']; ?></td>
                            </tr>
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo htmlspecialchars($documento['NombreDocumento']); ?></td>
                            </tr>
                            <tr>
                                <th>Observación:</th>
                                <td><?php echo nl2br(htmlspecialchars($documento['ObservacionDocumento'])); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha de Carga:</th>
                                <td><?php echo date('d/m/Y', strtotime($documento['FechaCargaDocumento'])); ?></td>
                            </tr>
                            <tr>
                                <th>Usuario de Carga:</th>
                                <td><?php echo htmlspecialchars($documento['UsuarioDocumento']); ?></td>
                            </tr>
                            <tr>
                                <th>Archivo:</th>
                                <td>
                                    <?php if (!empty($documento['NombreDocumento'])): ?>
                                        <a href="<?php echo htmlspecialchars('modulos/respaldos_documentos/uploads/' . $documento['NombreDocumento']); ?>" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-download"></i> Descargar Archivo
                                        </a>
                                    <?php else: ?>
                                        No hay archivo asociado.
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Fecha Creación Registro:</th>
                                <td><?php echo date('d/m/Y', strtotime($documento['FechaCargaDocumento'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
