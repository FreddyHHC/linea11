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
$id_tipo_egreso = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$errores = [];
$mensaje_exito = '';

switch ($accion) {
    case 'crear':
        // Validar permisos para crear
        if (!verificarPermiso('tipos_egresos', 'crear', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('tipos_egresos', 'crear', null, 'danger', 'No tienes permisos para crear tipos de egresos.');
        }

        // Validar campos
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (empty($nombre)) { $errores[] = "El nombre del tipo de egreso es obligatorio"; }
        
        // Verificar si el nombre del tipo de egreso ya existe
        $sqlVerificar = "SELECT COUNT(*) FROM tbltiposegresos WHERE NombreTipoEgreso = :nombre";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':nombre' => $nombre]);
        if ($stmtVerificar->fetchColumn() > 0) {
            $errores[] = "Ya existe un tipo de egreso con este nombre";
        }
        
        if (empty($errores)) {
            try {
                $sql = "INSERT INTO tbltiposegresos (NombreTipoEgreso) 
                        VALUES (:nombre)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre
                ]);
                logAction($pdo, $_SESSION['user_id'], 'tipos_egresos', 'crear', "Tipo de egreso '{$nombre}' creado.");
                $mensaje_exito = "Tipo de egreso creado correctamente.";
                redireccionar('tipos_egresos', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al guardar: " . $e->getMessage();
                redireccionar('tipos_egresos', 'crear', null, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('tipos_egresos', 'crear', null, 'danger', implode(', ', $errores));
        }
        break;

    case 'editar':
        // Validar permisos para editar
        if (!verificarPermiso('tipos_egresos', 'editar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('tipos_egresos', 'editar', $id_tipo_egreso, 'danger', 'No tienes permisos para editar tipos de egresos.');
        }

        if ($id_tipo_egreso <= 0) {
            redireccionar('tipos_egresos', 'index', null, 'danger', 'ID de tipo de egreso inválido para editar.');
        }

        // Validar campos
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (empty($nombre)) { $errores[] = "El nombre del tipo de egreso es obligatorio"; }
        
        // Verificar si el nombre del tipo de egreso ya existe para otro registro
        $sqlVerificar = "SELECT COUNT(*) FROM tbltiposegresos WHERE NombreTipoEgreso = :nombre AND IdTipoEgreso != :id";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':nombre' => $nombre, ':id' => $id_tipo_egreso]);
        if ($stmtVerificar->fetchColumn() > 0) {
            $errores[] = "Ya existe otro tipo de egreso con este nombre";
        }

        if (empty($errores)) {
            try {
                $sql = "UPDATE tbltiposegresos SET 
                        NombreTipoEgreso = :nombre
                        WHERE IdTipoEgreso = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':id' => $id_tipo_egreso
                ]);
                
                logAction($pdo, $_SESSION['user_id'], 'tipos_egresos', 'editar', "Tipo de egreso '{$nombre}' (ID: {$id_tipo_egreso}) actualizado.");
                $mensaje_exito = "Tipo de egreso actualizado correctamente.";
                redireccionar('tipos_egresos', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al actualizar: " . $e->getMessage();
                redireccionar('tipos_egresos', 'editar', $id_tipo_egreso, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('tipos_egresos', 'editar', $id_tipo_egreso, 'danger', implode(', ', $errores));
        }
        break;

    case 'eliminar':
        // Validar permisos para eliminar
        if (!verificarPermiso('tipos_egresos', 'eliminar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('tipos_egresos', 'index', null, 'danger', 'No tienes permisos para eliminar tipos de egresos.');
        }

        if ($id_tipo_egreso <= 0) {
            redireccionar('tipos_egresos', 'index', null, 'danger', 'ID de tipo de egreso inválido para eliminar.');
        }

        // Verificar si el tipo de egreso existe
        $sqlVerificar = "SELECT NombreTipoEgreso FROM tbltiposegresos WHERE IdTipoEgreso = :id";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':id' => $id_tipo_egreso]);
        $tipo_egreso_a_eliminar = $stmtVerificar->fetch();

        if (!$tipo_egreso_a_eliminar) {
            redireccionar('tipos_egresos', 'index', null, 'danger', 'Tipo de egreso no encontrado para eliminar.');
        }

        // TODO: Opcional: Verificar si hay egresos asociados a este tipo antes de eliminar
        // $sqlEgresosAsociados = "SELECT COUNT(*) FROM tblegresos WHERE IdTipoEgreso = :id_tipo_egreso";
        // $stmtEgresosAsociados = $pdo->prepare($sqlEgresosAsociados);
        // $stmtEgresosAsociados->execute([':id_tipo_egreso' => $id_tipo_egreso]);
        // $num_egresos = $stmtEgresosAsociados->fetchColumn();

        // if ($num_egresos > 0) {
        //     redireccionar('tipos_egresos', 'index', null, 'danger', 'No se puede eliminar el tipo de egreso porque tiene ' . $num_egresos . ' egreso(s) asociado(s).');
        // }

        try {
            $sql = "DELETE FROM tbltiposegresos WHERE IdTipoEgreso = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_tipo_egreso]);
            
            logAction($pdo, $_SESSION['user_id'], 'tipos_egresos', 'eliminar', "Tipo de egreso '{$tipo_egreso_a_eliminar['NombreTipoEgreso']}' (ID: {$id_tipo_egreso}) eliminado.");
            $mensaje_exito = "El tipo de egreso " . htmlspecialchars($tipo_egreso_a_eliminar['NombreTipoEgreso']) . " ha sido eliminado correctamente.";
            redireccionar('tipos_egresos', 'index', null, 'success', $mensaje_exito);
        } catch (PDOException $e) {
            $errores[] = "Error al eliminar el tipo de egreso: " . $e->getMessage();
            redireccionar('tipos_egresos', 'index', null, 'danger', implode(', ', $errores));
        }
        break;

    default:
        redireccionar('tipos_egresos', 'index', null, 'danger', 'Acción no válida.');
        break;
}
?>
