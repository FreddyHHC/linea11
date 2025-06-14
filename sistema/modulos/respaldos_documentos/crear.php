<?php
// No se necesita verificar permisos aquí, ya se hace en index.php y procesos.php

// Obtener mensajes de la URL
$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-plus-square"></i> Cargar Nuevo Documento</h2>
            <a href="?modulo=respaldos_documentos" class="btn btn-secondary">
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
                <form method="POST" action="modulos/respaldos_documentos/procesos.php" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="crear">
                    <div class="row">                        
                        <div class="col-md-6 mb-3">
                            <label for="documento_file" class="form-label">Archivo del Documento *</label>
                            <input type="file" class="form-control" id="documento_file" name="documento_file" required>
                            <small class="text-muted">Formatos permitidos: PDF, JPG, PNG, DOCX, XLSX. Máx. 5MB.</small>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="observacion" class="form-label">Observación</label>
                            <textarea class="form-control" id="observacion" name="observacion" rows="3"><?php echo isset($_POST['observacion']) ? htmlspecialchars($_POST['observacion']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Cargar Documento
                        </button>
                        <a href="?modulo=respaldos_documentos" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
