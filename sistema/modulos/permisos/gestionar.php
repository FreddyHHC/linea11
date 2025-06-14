<?php
// No se necesita verificar permisos aquí, ya se hace en index.php y procesos.php

// Obtener ID del perfil
$id_perfil = isset($_GET['id_perfil']) ? (int)$_GET['id_perfil'] : 0;

if ($id_perfil <= 0) {
    echo '<div class="alert alert-danger">ID de perfil inválido para gestionar permisos.</div>';
    return;
}

// Obtener datos del perfil
$sqlPerfil = "SELECT IdPerfil, NombrePerfil FROM tblperfiles WHERE IdPerfil = :id_perfil";
$stmtPerfil = $pdo->prepare($sqlPerfil);
$stmtPerfil->execute([':id_perfil' => $id_perfil]);
$perfil_data = $stmtPerfil->fetch();

if (!$perfil_data) {
    echo '<div class="alert alert-danger">Perfil no encontrado.</div>';
    return;
}

// Obtener todos los módulos activos
$sqlModulos = "SELECT IdModulo, NombreModulo, DescripcionModulo FROM tblmodulos WHERE EstadoModulo = 'activo' ORDER BY NombreModulo";
$stmtModulos = $pdo->query($sqlModulos);
$modulos_disponibles = $stmtModulos->fetchAll();

// Obtener los permisos actuales para el perfil seleccionado
$sqlPermisosActuales = "SELECT IdModulo, PuedeVer, PuedeAgregar, PuedeEditar, PuedeEliminar, PermisoEspecial 
                        FROM tblpermisos 
                        WHERE IdPerfil = :id_perfil";
$stmtPermisosActuales = $pdo->prepare($sqlPermisosActuales);
$stmtPermisosActuales->execute([':id_perfil' => $id_perfil]);
$permisos_actuales = [];
while ($row = $stmtPermisosActuales->fetch(PDO::FETCH_ASSOC)) {
    $permisos_actuales[$row['IdModulo']] = $row;
}

// Obtener mensajes de la URL
$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-cogs"></i> Gestionar Permisos para: <?php echo htmlspecialchars($perfil_data['NombrePerfil']); ?></h2>
            <a href="?modulo=permisos" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Perfiles
            </a>
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
                <form method="POST" action="modulos/permisos/procesos.php">
                    <input type="hidden" name="accion" value="guardar_permisos">
                    <input type="hidden" name="id_perfil" value="<?php echo htmlspecialchars($perfil_data['IdPerfil']); ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Módulo</th>
                                    <th class="text-center">Ver</th>
                                    <th class="text-center">Agregar</th>
                                    <th class="text-center">Editar</th>
                                    <th class="text-center">Eliminar</th>
                                    <th class="text-center">Especial</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($modulos_disponibles) > 0): ?>
                                    <?php foreach ($modulos_disponibles as $modulo_item): ?>
                                    <?php 
                                        $current_perm = $permisos_actuales[$modulo_item['IdModulo']] ?? [
                                            'PuedeVer' => 0, 
                                            'PuedeAgregar' => 0, 
                                            'PuedeEditar' => 0, 
                                            'PuedeEliminar' => 0, 
                                            'PermisoEspecial' => 0
                                        ];
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($modulo_item['NombreModulo']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($modulo_item['DescripcionModulo']); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="permisos[<?php echo $modulo_item['IdModulo']; ?>][ver]" 
                                                       value="1" <?php echo $current_perm['PuedeVer'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="permisos[<?php echo $modulo_item['IdModulo']; ?>][agregar]" 
                                                       value="1" <?php echo $current_perm['PuedeAgregar'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="permisos[<?php echo $modulo_item['IdModulo']; ?>][editar]" 
                                                       value="1" <?php echo $current_perm['PuedeEditar'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="permisos[<?php echo $modulo_item['IdModulo']; ?>][eliminar]" 
                                                       value="1" <?php echo $current_perm['PuedeEliminar'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="permisos[<?php echo $modulo_item['IdModulo']; ?>][especial]" 
                                                       value="1" <?php echo $current_perm['PermisoEspecial'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No hay módulos activos para asignar permisos.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Permisos
                        </button>
                        <a href="?modulo=permisos" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
