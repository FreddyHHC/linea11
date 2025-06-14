<?php
// No se necesita verificar permisos aquí, ya se hace en index.php y procesos.php
// Obtener perfiles para el select
$sqlPerfiles = "SELECT IdPerfil, NombrePerfil FROM tblperfiles WHERE EstadoPerfil = 'activo' ORDER BY NombrePerfil";
$stmtPerfiles = $pdo->query($sqlPerfiles);
$perfiles = $stmtPerfiles->fetchAll();

// Obtener mensajes de la URL
$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-user-plus"></i> Crear Usuario</h2>
            <a href="?modulo=usuarios" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
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
                <form method="POST" action="modulos/usuarios/procesos.php">
                    <input type="hidden" name="accion" value="crear">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="run" class="form-label">RUN *</label>
                            <input type="text" class="form-control" id="run" name="run" required 
                                   value="<?php echo isset($_POST['run']) ? htmlspecialchars($_POST['run']) : ''; ?>">
                            <small class="text-muted">Formato: 12345678-9</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="activo" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactivo" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                <option value="suspendido" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'suspendido') ? 'selected' : ''; ?>>Suspendido</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="nombres" class="form-label">Nombres *</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required 
                                   value="<?php echo isset($_POST['nombres']) ? htmlspecialchars($_POST['nombres']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="apellidos" class="form-label">Apellidos *</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" required 
                                   value="<?php echo isset($_POST['apellidos']) ? htmlspecialchars($_POST['apellidos']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="correo" class="form-label">Correo Electrónico *</label>
                            <input type="email" class="form-control" id="correo" name="correo" required 
                                   value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="perfil" class="form-label">Perfil *</label>
                            <select class="form-select" id="perfil" name="perfil" required>
                                <option value="">Seleccione un perfil</option>
                                <?php foreach ($perfiles as $perfil): ?>
                                    <option value="<?php echo $perfil['IdPerfil']; ?>" 
                                            <?php echo (isset($_POST['perfil']) && (int)$_POST['perfil'] === $perfil['IdPerfil']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($perfil['NombrePerfil']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Contraseña *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Mínimo 8 caracteres</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirmar_password" class="form-label">Confirmar Contraseña *</label>
                            <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" required>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <a href="?modulo=usuarios" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
