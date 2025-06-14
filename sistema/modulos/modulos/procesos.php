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
$id_modulo = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$errores = [];
$mensaje_exito = '';

switch ($accion) {
    case 'crear':
        // Validar permisos para crear
        if (!verificarPermiso('modulos', 'crear', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('modulos', 'crear', null, 'danger', 'No tienes permisos para crear módulos.');
        }

        // Validar campos
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'activo';
        
        if (empty($nombre)) { $errores[] = "El nombre del módulo es obligatorio"; }
        
        // Verificar si el nombre del módulo ya existe
        $sqlVerificar = "SELECT COUNT(*) FROM tblmodulos WHERE NombreModulo = :nombre";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':nombre' => $nombre]);
        if ($stmtVerificar->fetchColumn() > 0) {
            $errores[] = "Ya existe un módulo con este nombre";
        }
        
        if (empty($errores)) {
            try {
                $sql = "INSERT INTO tblmodulos (NombreModulo, DescripcionModulo, EstadoModulo) 
                        VALUES (:nombre, :descripcion, :estado)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':descripcion' => $descripcion,
                    ':estado' => $estado
                ]);
                $mensaje_exito = "Módulo creado correctamente.";
                logAction($pdo, $_SESSION['user_id'], 'modulos', 'crear', "Módulo '{$nombre}' creado.");
                redireccionar('modulos', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al guardar: " . $e->getMessage();
                redireccionar('modulos', 'crear', null, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('modulos', 'crear', null, 'danger', implode(', ', $errores));
        }
        break;

    case 'editar':
        // Validar permisos para editar
        if (!verificarPermiso('modulos', 'editar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('modulos', 'editar', $id_modulo, 'danger', 'No tienes permisos para editar módulos.');
        }

        if ($id_modulo <= 0) {
            redireccionar('modulos', 'index', null, 'danger', 'ID de módulo inválido para editar.');
        }

        // Validar campos
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'activo';
        
        if (empty($nombre)) { $errores[] = "El nombre del módulo es obligatorio"; }
        
        // Verificar si el nombre del módulo ya existe para otro módulo
        $sqlVerificar = "SELECT COUNT(*) FROM tblmodulos WHERE NombreModulo = :nombre AND IdModulo != :id";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':nombre' => $nombre, ':id' => $id_modulo]);
        if ($stmtVerificar->fetchColumn() > 0) {
            $errores[] = "Ya existe otro módulo con este nombre";
        }

        if (empty($errores)) {
            try {
                $sql = "UPDATE tblmodulos SET 
                        NombreModulo = :nombre, 
                        DescripcionModulo = :descripcion, 
                        EstadoModulo = :estado
                        WHERE IdModulo = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':descripcion' => $descripcion,
                    ':estado' => $estado,
                    ':id' => $id_modulo
                ]);
                
                $mensaje_exito = "Módulo actualizado correctamente.";
                logAction($pdo, $_SESSION['user_id'], 'modulos', 'editar', "Módulo '{$nombre}' (ID: {$id_modulo}) actualizado.");
                redireccionar('modulos', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al actualizar: " . $e->getMessage();
                redireccionar('modulos', 'editar', $id_modulo, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('modulos', 'editar', $id_modulo, 'danger', implode(', ', $errores));
        }
        break;

    case 'eliminar':
        // Validar permisos para eliminar
        if (!verificarPermiso('modulos', 'eliminar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('modulos', 'index', null, 'danger', 'No tienes permisos para eliminar módulos.');
        }

        if ($id_modulo <= 0) {
            redireccionar('modulos', 'index', null, 'danger', 'ID de módulo inválido para eliminar.');
        }

        // Verificar si el módulo existe
        $sqlVerificar = "SELECT NombreModulo FROM tblmodulos WHERE IdModulo = :id";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':id' => $id_modulo]);
        $modulo_a_eliminar = $stmtVerificar->fetch();

        if (!$modulo_a_eliminar) {
            redireccionar('modulos', 'index', null, 'danger', 'Módulo no encontrado para eliminar.');
        }

        // Verificar si hay permisos asociados a este módulo
        $sqlPermisosAsociados = "SELECT COUNT(*) FROM tblpermisos WHERE IdModulo = :id_modulo";
        $stmtPermisosAsociados = $pdo->prepare($sqlPermisosAsociados);
        $stmtPermisosAsociados->execute([':id_modulo' => $id_modulo]);
        $num_permisos = $stmtPermisosAsociados->fetchColumn();

        if ($num_permisos > 0) {
            redireccionar('modulos', 'index', null, 'danger', 'No se puede eliminar el módulo porque tiene ' . $num_permisos . ' permiso(s) asociado(s).');
        }

        try {
            // Eliminar físicamente el módulo
            $sql = "DELETE FROM tblmodulos WHERE IdModulo = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_modulo]);
            
            $mensaje_exito = "El módulo " . htmlspecialchars($modulo_a_eliminar['NombreModulo']) . " ha sido eliminado correctamente.";
            logAction($pdo, $_SESSION['user_id'], 'modulos', 'eliminar', "Módulo '{$modulo_a_eliminar['NombreModulo']}' (ID: {$id_modulo}) eliminado.");
            redireccionar('modulos', 'index', null, 'success', $mensaje_exito);
        } catch (PDOException $e) {
            $errores[] = "Error al eliminar el módulo: " . $e->getMessage();
            redireccionar('modulos', 'index', null, 'danger', implode(', ', $errores));
        }
        break;

    default:
        redireccionar('modulos', 'index', null, 'danger', 'Acción no válida.');
        break;
}
?>
