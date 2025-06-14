<?php
session_start();
require_once '../../../config/database.php';

$database = new Database();
$pdo = $database->getConnection();

// Validar sesión y token al inicio de procesos.php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_token'])) {
    redireccionar('dashboard', 'index', null, 'danger', 'Sesión no iniciada o token inválido.');
}

$sql = "SELECT ExpiracionToken FROM tblusuarios WHERE IdUsuario = :id AND TokenSesion = :token";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':id' => $_SESSION['user_id'],
    ':token' => $_SESSION['user_token']
]);
$tokenData = $stmt->fetch();

if (!$tokenData || strtotime($tokenData['ExpiracionToken']) < time()) {
    session_destroy();
    redireccionar('dashboard', 'index', null, 'danger', 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
}

// Obtener la acción a realizar
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$id_tipo_comprobante = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$errores = [];
$mensaje_exito = '';

switch ($accion) {
    case 'crear':
        // Validar permisos para crear
        if (!verificarPermiso('tipos_comprobantes', 'crear', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('tipos_comprobantes', 'crear', null, 'danger', 'No tienes permisos para crear tipos de comprobantes.');
        }

        // Validar campos
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (empty($nombre)) { $errores[] = "El nombre del tipo de comprobante es obligatorio"; }
        
        // Verificar si el nombre del tipo de comprobante ya existe
        $sqlVerificar = "SELECT COUNT(*) FROM tblcomprobantes WHERE NombreComprobante = :nombre";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':nombre' => $nombre]);
        if ($stmtVerificar->fetchColumn() > 0) {
            $errores[] = "Ya existe un tipo de comprobante con este nombre";
        }
        
        if (empty($errores)) {
            try {
                $sql = "INSERT INTO tblcomprobantes (NombreComprobante) 
                        VALUES (:nombre)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre
                ]);
                logAction($pdo, $_SESSION['user_id'], 'tipos_comprobantes', 'crear', "Tipo de comprobante '{$nombre}' creado.");
                $mensaje_exito = "Tipo de comprobante creado correctamente.";
                redireccionar('tipos_comprobantes', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al guardar: " . $e->getMessage();
                redireccionar('tipos_comprobantes', 'crear', null, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('tipos_comprobantes', 'crear', null, 'danger', implode(', ', $errores));
        }
        break;

    case 'editar':
        // Validar permisos para editar
        if (!verificarPermiso('tipos_comprobantes', 'editar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('tipos_comprobantes', 'editar', $id_tipo_comprobante, 'danger', 'No tienes permisos para editar tipos de comprobantes.');
        }

        if ($id_tipo_comprobante <= 0) {
            redireccionar('tipos_comprobantes', 'index', null, 'danger', 'ID de tipo de comprobante inválido para editar.');
        }

        // Validar campos
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (empty($nombre)) { $errores[] = "El nombre del tipo de comprobante es obligatorio"; }
        
        // Verificar si el nombre del tipo de comprobante ya existe para otro registro
        $sqlVerificar = "SELECT COUNT(*) FROM tblcomprobantes WHERE NombreComprobante = :nombre AND IdComprobante != :id";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':nombre' => $nombre, ':id' => $id_tipo_comprobante]);
        if ($stmtVerificar->fetchColumn() > 0) {
            $errores[] = "Ya existe otro tipo de comprobante con este nombre";
        }

        if (empty($errores)) {
            try {
                $sql = "UPDATE tblcomprobantes SET 
                        NombreComprobante = :nombre
                        WHERE IdComprobante = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':id' => $id_tipo_comprobante
                ]);
                
                logAction($pdo, $_SESSION['user_id'], 'tipos_comprobantes', 'editar', "Tipo de comprobante '{$nombre}' (ID: {$id_tipo_comprobante}) actualizado.");
                $mensaje_exito = "Tipo de comprobante actualizado correctamente.";
                redireccionar('tipos_comprobantes', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al actualizar: " . $e->getMessage();
                redireccionar('tipos_comprobantes', 'editar', $id_tipo_comprobante, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('tipos_comprobantes', 'editar', $id_tipo_comprobante, 'danger', implode(', ', $errores));
        }
        break;

    case 'eliminar':
        // Validar permisos para eliminar
        if (!verificarPermiso('tipos_comprobantes', 'eliminar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('tipos_comprobantes', 'index', null, 'danger', 'No tienes permisos para eliminar tipos de comprobantes.');
        }

        if ($id_tipo_comprobante <= 0) {
            redireccionar('tipos_comprobantes', 'index', null, 'danger', 'ID de tipo de comprobante inválido para eliminar.');
        }

        // Verificar si el tipo de comprobante existe
        $sqlVerificar = "SELECT NombreComprobante FROM tblcomprobantes WHERE IdComprobante = :id";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':id' => $id_tipo_comprobante]);
        $tipo_comprobante_a_eliminar = $stmtVerificar->fetch();

        if (!$tipo_comprobante_a_eliminar) {
            redireccionar('tipos_comprobantes', 'index', null, 'danger', 'Tipo de comprobante no encontrado para eliminar.');
        }

        // TODO: Opcional: Verificar si hay comprobantes asociados a este tipo antes de eliminar
        // $sqlComprobantesAsociados = "SELECT COUNT(*) FROM tblcomprobantes_relacionadas WHERE IdTipoComprobante = :id_tipo_comprobante";
        // $stmtComprobantesAsociados = $pdo->prepare($sqlComprobantesAsociados);
        // $stmtComprobantesAsociados->execute([':id_tipo_comprobante' => $id_tipo_comprobante]);
        // $num_comprobantes = $stmtComprobantesAsociados->fetchColumn();

        // if ($num_comprobantes > 0) {
        //     redireccionar('tipos_comprobantes', 'index', null, 'danger', 'No se puede eliminar el tipo de comprobante porque tiene ' . $num_comprobantes . ' comprobante(s) asociado(s).');
        // }

        try {
            $sql = "DELETE FROM tblcomprobantes WHERE IdComprobante = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_tipo_comprobante]);
            
            logAction($pdo, $_SESSION['user_id'], 'tipos_comprobantes', 'eliminar', "Tipo de comprobante '{$tipo_comprobante_a_eliminar['NombreComprobante']}' (ID: {$id_tipo_comprobante}) eliminado.");
            $mensaje_exito = "El tipo de comprobante " . htmlspecialchars($tipo_comprobante_a_eliminar['NombreComprobante']) . " ha sido eliminado correctamente.";
            redireccionar('tipos_comprobantes', 'index', null, 'success', $mensaje_exito);
        } catch (PDOException $e) {
            $errores[] = "Error al eliminar el tipo de comprobante: " . $e->getMessage();
            redireccionar('tipos_comprobantes', 'index', null, 'danger', implode(', ', $errores));
        }
        break;

    default:
        redireccionar('tipos_comprobantes', 'index', null, 'danger', 'Acción no válida.');
        break;
}
?>
