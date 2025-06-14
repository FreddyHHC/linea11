<?php
// No se necesita verificar permisos aquí, ya se hace en index.php y procesos.php

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

// Obtener mensajes de la URL
$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-edit"></i> Editar Tipo de Egreso</h2>
            <a href="?modulo=tipos_egresos" class="btn btn-secondary">
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
                <form method="POST" action="modulos/tipos_egresos/procesos.php">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($tipo_egreso['IdTipoEgreso']); ?>">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nombre" class="form-label">Nombre del Tipo de Egreso *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required 
                                   value="<?php echo htmlspecialchars($tipo_egreso['NombreTipoEgreso']); ?>">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="?modulo=tipos_egresos" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
