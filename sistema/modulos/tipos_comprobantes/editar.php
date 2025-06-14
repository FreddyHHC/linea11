<?php
// No se necesita verificar permisos aquí, ya se hace en index.php y procesos.php

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

// Obtener mensajes de la URL
$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-edit"></i> Editar Tipo de Comprobante</h2>
            <a href="?modulo=tipos_comprobantes" class="btn btn-secondary">
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
                <form method="POST" action="modulos/tipos_comprobantes/procesos.php">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($tipo_comprobante['IdComprobante']); ?>">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nombre" class="form-label">Nombre del Tipo de Comprobante *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required 
                                   value="<?php echo htmlspecialchars($tipo_comprobante['NombreComprobante']); ?>">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="?modulo=tipos_comprobantes" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
