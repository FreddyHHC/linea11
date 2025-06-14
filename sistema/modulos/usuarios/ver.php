<?php
// Verificar permisos
if (!verificarPermiso('usuarios', 'ver', $_SESSION['user_perfil_id'], $pdo)) {
    echo '<div class="alert alert-danger">No tienes permisos para ver usuarios.</div>';
    return;
}

// Obtener ID del usuario
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="alert alert-danger">ID de usuario inválido.</div>';
    return;
}

// Obtener datos del usuario
$sqlUsuario = "SELECT u.*, p.NombrePerfil 
               FROM tblusuarios u 
               INNER JOIN tblperfiles p ON u.IdPerfil = p.IdPerfil 
               WHERE u.IdUsuario = :id";
$stmtUsuario = $pdo->prepare($sqlUsuario);
$stmtUsuario->execute([':id' => $id]);
$usuario = $stmtUsuario->fetch();

if (!$usuario) {
    echo '<div class="alert alert-danger">Usuario no encontrado.</div>';
    return;
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-user"></i> Detalles del Usuario</h2>
            <div>
                <?php if (verificarPermiso('usuarios', 'editar', $_SESSION['user_perfil_id'], $pdo)): ?>
                <a href="?modulo=usuarios&accion=editar&id=<?php echo $usuario['IdUsuario']; ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <?php endif; ?>
                <a href="?modulo=usuarios" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-title">Información Personal</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 30%">ID:</th>
                                <td><?php echo $usuario['IdUsuario']; ?></td>
                            </tr>
                            <tr>
                                <th>RUN:</th>
                                <td><?php echo htmlspecialchars($usuario['RunUsuario']); ?></td>
                            </tr>
                            <tr>
                                <th>Nombres:</th>
                                <td><?php echo htmlspecialchars($usuario['NombresUsuario']); ?></td>
                            </tr>
                            <tr>
                                <th>Apellidos:</th>
                                <td><?php echo htmlspecialchars($usuario['ApellidosUsuario']); ?></td>
                            </tr>
                            <tr>
                                <th>Correo:</th>
                                <td><?php echo htmlspecialchars($usuario['CorreoUsuario']); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5 class="card-title">Información del Sistema</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 30%">Perfil:</th>
                                <td><?php echo htmlspecialchars($usuario['NombrePerfil']); ?></td>
                            </tr>
                            <tr>
                                <th>Estado:</th>
                                <td>
                                    <span class="badge <?php 
                                        echo $usuario['EstadoUsuario'] === 'activo' ? 'bg-success' : 
                                            ($usuario['EstadoUsuario'] === 'inactivo' ? 'bg-danger' : 'bg-warning'); 
                                    ?>">
                                        <?php echo ucfirst($usuario['EstadoUsuario']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Fecha Creación:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['FechaCreacion'])); ?></td>
                            </tr>
                            <tr>
                                <th>Última Actualización:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['FechaActualizacion'])); ?></td>
                            </tr>
                            <tr>
                                <th>Último Acceso:</th>
                                <td>
                                    <?php 
                                    if ($usuario['ExpiracionToken'] && strtotime($usuario['ExpiracionToken']) > time()) {
                                        echo 'Actualmente en línea';
                                    } elseif ($usuario['ExpiracionToken']) {
                                        echo date('d/m/Y H:i', strtotime($usuario['ExpiracionToken']) - 86400); // 24 horas antes
                                    } else {
                                        echo 'Nunca ha iniciado sesión';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
