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
$id_tipo_ingreso = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$errores = [];
$mensaje_exito = '';

switch ($accion) {
    case 'crear':
        // Validar permisos para crear
        if (!verificarPermiso('tipos_ingresos', 'crear', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('tipos_ingresos', 'crear', null, 'danger', 'No tienes permisos para crear tipos de ingresos.');
        }

        // Validar campos
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (empty($nombre)) { $errores[] = "El nombre del tipo de ingreso es obligatorio"; }
        
        // Verificar si el nombre del tipo de ingreso ya existe
        $sqlVerificar = "SELECT COUNT(*) FROM tbltiposingresos WHERE NombreTipoIngreso = :nombre";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':nombre' => $nombre]);
        if ($stmtVerificar->fetchColumn() > 0) {
            $errores[] = "Ya existe un tipo de ingreso con este nombre";
        }
        
        if (empty($errores)) {
            try {
                $sql = "INSERT INTO tbltiposingresos (NombreTipoIngreso) 
                        VALUES (:nombre)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre
                ]);
                logAction($pdo, $_SESSION['user_id'], 'tipos_ingresos', 'crear', "Tipo de ingreso '{$nombre}' creado.");
                $mensaje_exito = "Tipo de ingreso creado correctamente.";
                redireccionar('tipos_ingresos', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al guardar: " . $e->getMessage();
                redireccionar('tipos_ingresos', 'crear', null, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('tipos_ingresos', 'crear', null, 'danger', implode(', ', $errores));
        }
        break;

    case 'editar':
        // Validar permisos para editar
        if (!verificarPermiso('tipos_ingresos', 'editar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('tipos_ingresos', 'editar', $id_tipo_ingreso, 'danger', 'No tienes permisos para editar tipos de ingresos.');
        }

        if ($id_tipo_ingreso <= 0) {
            redireccionar('tipos_ingresos', 'index', null, 'danger', 'ID de tipo de ingreso inválido para editar.');
        }

        // Validar campos
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (empty($nombre)) { $errores[] = "El nombre del tipo de ingreso es obligatorio"; }
        
        // Verificar si el nombre del tipo de ingreso ya existe para otro registro
        $sqlVerificar = "SELECT COUNT(*) FROM tbltiposingresos WHERE NombreTipoIngreso = :nombre AND IdTipoIngreso != :id";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':nombre' => $nombre, ':id' => $id_tipo_ingreso]);
        if ($stmtVerificar->fetchColumn() > 0) {
            $errores[] = "Ya existe otro tipo de ingreso con este nombre";
        }

        if (empty($errores)) {
            try {
                $sql = "UPDATE tbltiposingresos SET 
                        NombreTipoIngreso = :nombre
                        WHERE IdTipoIngreso = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':id' => $id_tipo_ingreso
                ]);
                
                logAction($pdo, $_SESSION['user_id'], 'tipos_ingresos', 'editar', "Tipo de ingreso '{$nombre}' (ID: {$id_tipo_ingreso}) actualizado.");
                $mensaje_exito = "Tipo de ingreso actualizado correctamente.";
                redireccionar('tipos_ingresos', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al actualizar: " . $e->getMessage();
                redireccionar('tipos_ingresos', 'editar', $id_tipo_ingreso, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('tipos_ingresos', 'editar', $id_tipo_ingreso, 'danger', implode(', ', $errores));
        }
        break;

    case 'eliminar':
        // Validar permisos para eliminar
        if (!verificarPermiso('tipos_ingresos', 'eliminar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('tipos_ingresos', 'index', null, 'danger', 'No tienes permisos para eliminar tipos de ingresos.');
        }

        if ($id_tipo_ingreso <= 0) {
            redireccionar('tipos_ingresos', 'index', null, 'danger', 'ID de tipo de ingreso inválido para eliminar.');
        }

        // Verificar si el tipo de ingreso existe
        $sqlVerificar = "SELECT NombreTipoIngreso FROM tbltiposingresos WHERE IdTipoIngreso = :id";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':id' => $id_tipo_ingreso]);
        $tipo_ingreso_a_eliminar = $stmtVerificar->fetch();

        if (!$tipo_ingreso_a_eliminar) {
            redireccionar('tipos_ingresos', 'index', null, 'danger', 'Tipo de ingreso no encontrado para eliminar.');
        }

        // TODO: Opcional: Verificar si hay ingresos asociados a este tipo antes de eliminar
        // $sqlIngresosAsociados = "SELECT COUNT(*) FROM tblingresos WHERE IdTipoIngreso = :id_tipo_ingreso";
        // $stmtIngresosAsociados = $pdo->prepare($sqlIngresosAsociados);
        // $stmtIngresosAsociados->execute([':id_tipo_ingreso' => $id_tipo_ingreso]);
        // $num_ingresos = $stmtIngresosAsociados->fetchColumn();

        // if ($num_ingresos > 0) {
        //     redireccionar('tipos_ingresos', 'index', null, 'danger', 'No se puede eliminar el tipo de ingreso porque tiene ' . $num_ingresos . ' ingreso(s) asociado(s).');
        // }

        try {
            $sql = "DELETE FROM tbltiposingresos WHERE IdTipoIngreso = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_tipo_ingreso]);
            
            logAction($pdo, $_SESSION['user_id'], 'tipos_ingresos', 'eliminar', "Tipo de ingreso '{$tipo_ingreso_a_eliminar['NombreTipoIngreso']}' (ID: {$id_tipo_ingreso}) eliminado.");
            $mensaje_exito = "El tipo de ingreso " . htmlspecialchars($tipo_ingreso_a_eliminar['NombreTipoIngreso']) . " ha sido eliminado correctamente.";
            redireccionar('tipos_ingresos', 'index', null, 'success', $mensaje_exito);
        } catch (PDOException $e) {
            $errores[] = "Error al eliminar el tipo de ingreso: " . $e->getMessage();
            redireccionar('tipos_ingresos', 'index', null, 'danger', implode(', ', $errores));
        }
        break;

    default:
        redireccionar('tipos_ingresos', 'index', null, 'danger', 'Acción no válida.');
        break;
}
?>
