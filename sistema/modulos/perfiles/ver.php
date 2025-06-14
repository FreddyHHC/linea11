<?php
// Verificar permisos
if (!verificarPermiso('perfiles', 'ver', $_SESSION['user_perfil_id'], $pdo)) {
    echo '<div class="alert alert-danger">No tienes permisos para ver perfiles.</div>';
    return;
}

// Obtener ID del perfil
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger">ID de perfil inválido.</div>';
    return;
}

// Obtener datos del perfil
$sqlPerfil = "SELECT * FROM tblperfiles WHERE IdPerfil = :id";
$stmtPerfil = $pdo->prepare($sqlPerfil);
$stmtPerfil->execute([':id' => $id]);
$perfil = $stmtPerfil->fetch();

if (!$perfil) {
    echo '<div class="alert alert-danger">Perfil no encontrado.</div>';
    return;
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-user-tag"></i> Detalles del Perfil</h2>
            <div>
                <?php if (verificarPermiso('perfiles', 'editar', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a href="?modulo=perfiles&accion=editar&id=<?php echo $perfil['IdPerfil']; ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <?php endif; ?>
                <a href="?modulo=perfiles" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="card-title">Información del Perfil</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 20%">ID:</th>
                                <td><?php echo $perfil['IdPerfil']; ?></td>
                            </tr>
                            <tr>
                                <th>Nombre:</th>
                                <td><?php echo htmlspecialchars($perfil['NombrePerfil']); ?></td>
                            </tr>
                            <tr>
                                <th>Descripción:</th>
                                <td><?php echo htmlspecialchars($perfil['DescripcionPerfil']); ?></td>
                            </tr>
                            <tr>
                                <th>Estado:</th>
                                <td>
                                    <span class="badge <?php 
                                        echo $perfil['EstadoPerfil'] === 'activo' ? 'bg-success' : 'bg-danger'; 
                                    ?>">
                                        <?php echo ucfirst($perfil['EstadoPerfil']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Fecha Creación:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($perfil['FechaCreacion'])); ?></td>
                            </tr>
                            <tr>
                                <th>Última Actualización:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($perfil['FechaActualizacion'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
