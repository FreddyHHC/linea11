<?php
// Verificar permisos
if (!verificarPermiso('permisos', 'ver', $_SESSION['user_perfil_id'], $pdo)) {
    echo '<div class="alert alert-danger">No tienes permisos para ver detalles de permisos.</div>';
    return;
}

// Obtener ID del permiso
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger">ID de permiso inválido.</div>';
    return;
}

// Obtener datos del permiso, perfil y módulo
$sqlPermiso = "SELECT tp.*, p.NombrePerfil, m.NombreModulo, m.DescripcionModulo AS DescripcionModuloCompleta
               FROM tblpermisos tp
               INNER JOIN tblperfiles p ON tp.IdPerfil = p.IdPerfil
               INNER JOIN tblmodulos m ON tp.IdModulo = m.IdModulo
               WHERE tp.IdPermiso = :id";
$stmtPermiso = $pdo->prepare($sqlPermiso);
$stmtPermiso->execute([':id' => $id]);
$permiso_data = $stmtPermiso->fetch();

if (!$permiso_data) {
    echo '<div class="alert alert-danger">Permiso no encontrado.</div>';
    return;
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-info-circle"></i> Detalles del Permiso</h2>
            <div>
                <?php if (verificarPermiso('permisos', 'editar', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a href="?modulo=permisos&accion=gestionar&id_perfil=<?php echo $permiso_data['IdPerfil']; ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit"></i> Editar Permisos del Perfil
                </a>
                <?php endif; ?>
                <a href="?modulo=permisos" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Gestión de Permisos
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="card-title">Información del Permiso</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 25%">ID Permiso:</th>
                                <td><?php echo $permiso_data['IdPermiso']; ?></td>
                            </tr>
                            <tr>
                                <th>Perfil Asociado:</th>
                                <td><?php echo htmlspecialchars($permiso_data['NombrePerfil']); ?> (ID: <?php echo $permiso_data['IdPerfil']; ?>)</td>
                            </tr>
                            <tr>
                                <th>Módulo Asociado:</th>
                                <td><?php echo htmlspecialchars($permiso_data['NombreModulo']); ?> (ID: <?php echo $permiso_data['IdModulo']; ?>)</td>
                            </tr>
                            <tr>
                                <th>Descripción del Módulo:</th>
                                <td><?php echo htmlspecialchars($permiso_data['DescripcionModuloCompleta']); ?></td>
                            </tr>
                            <tr>
                                <th>Puede Ver:</th>
                                <td>
                                    <span class="badge <?php echo $permiso_data['PuedeVer'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $permiso_data['PuedeVer'] ? 'Sí' : 'No'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Puede Agregar:</th>
                                <td>
                                    <span class="badge <?php echo $permiso_data['PuedeAgregar'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $permiso_data['PuedeAgregar'] ? 'Sí' : 'No'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Puede Editar:</th>
                                <td>
                                    <span class="badge <?php echo $permiso_data['PuedeEditar'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $permiso_data['PuedeEditar'] ? 'Sí' : 'No'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Puede Eliminar:</th>
                                <td>
                                    <span class="badge <?php echo $permiso_data['PuedeEliminar'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $permiso_data['PuedeEliminar'] ? 'Sí' : 'No'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Permiso Especial:</th>
                                <td>
                                    <span class="badge <?php echo $permiso_data['PermisoEspecial'] ? 'bg-info' : 'bg-secondary'; ?>">
                                        <?php echo $permiso_data['PermisoEspecial'] ? 'Sí' : 'No'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Fecha Creación:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($permiso_data['FechaCreacion'])); ?></td>
                            </tr>
                            <tr>
                                <th>Última Actualización:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($permiso_data['FechaActualizacion'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
