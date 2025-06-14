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
$id_perfil = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$errores = [];
$mensaje_exito = '';

switch ($accion) {
    case 'crear':
        // Validar permisos para crear
        if (!verificarPermiso('perfiles', 'crear', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('perfiles', 'crear', null, 'danger', 'No tienes permisos para crear perfiles.');
        }

        // Validar campos
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'activo';
        
        if (empty($nombre)) { $errores[] = "El nombre del perfil es obligatorio"; }
        
        // Verificar si el nombre del perfil ya existe
        $sqlVerificar = "SELECT COUNT(*) FROM tblperfiles WHERE NombrePerfil = :nombre";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':nombre' => $nombre]);
        if ($stmtVerificar->fetchColumn() > 0) {
            $errores[] = "Ya existe un perfil con este nombre";
        }
        
        if (empty($errores)) {
            try {
                $sql = "INSERT INTO tblperfiles (NombrePerfil, DescripcionPerfil, EstadoPerfil) 
                        VALUES (:nombre, :descripcion, :estado)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':descripcion' => $descripcion,
                    ':estado' => $estado
                ]);
                logAction($pdo, $_SESSION['user_id'], 'perfiles', 'crear', "Perfil '{$nombre}' creado.");
                $mensaje_exito = "Perfil creado correctamente.";
                redireccionar('perfiles', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al guardar: " . $e->getMessage();
                redireccionar('perfiles', 'crear', null, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('perfiles', 'crear', null, 'danger', implode(', ', $errores));
        }
        break;

    case 'editar':
        // Validar permisos para editar
        if (!verificarPermiso('perfiles', 'editar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('perfiles', 'editar', $id_perfil, 'danger', 'No tienes permisos para editar perfiles.');
        }

        if ($id_perfil <= 0) {
            redireccionar('perfiles', 'index', null, 'danger', 'ID de perfil inválido para editar.');
        }

        // Validar campos
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'activo';
        
        if (empty($nombre)) { $errores[] = "El nombre del perfil es obligatorio"; }
        
        // Verificar si el nombre del perfil ya existe para otro perfil
        $sqlVerificar = "SELECT COUNT(*) FROM tblperfiles WHERE NombrePerfil = :nombre AND IdPerfil != :id";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':nombre' => $nombre, ':id' => $id_perfil]);
        if ($stmtVerificar->fetchColumn() > 0) {
            $errores[] = "Ya existe otro perfil con este nombre";
        }

        if (empty($errores)) {
            try {
                $sql = "UPDATE tblperfiles SET 
                        NombrePerfil = :nombre, 
                        DescripcionPerfil = :descripcion, 
                        EstadoPerfil = :estado
                        WHERE IdPerfil = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':descripcion' => $descripcion,
                    ':estado' => $estado,
                    ':id' => $id_perfil
                ]);
                
                logAction($pdo, $_SESSION['user_id'], 'perfiles', 'editar', "Perfil '{$nombre}' (ID: {$id_perfil}) actualizado.");
                $mensaje_exito = "Perfil actualizado correctamente.";
                redireccionar('perfiles', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al actualizar: " . $e->getMessage();
                redireccionar('perfiles', 'editar', $id_perfil, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('perfiles', 'editar', $id_perfil, 'danger', implode(', ', $errores));
        }
        break;

    case 'eliminar':
        // Validar permisos para eliminar
        if (!verificarPermiso('perfiles', 'eliminar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('perfiles', 'index', null, 'danger', 'No tienes permisos para eliminar perfiles.');
        }

        if ($id_perfil <= 0) {
            redireccionar('perfiles', 'index', null, 'danger', 'ID de perfil inválido para eliminar.');
        }

        // Verificar si el perfil existe
        $sqlVerificar = "SELECT NombrePerfil FROM tblperfiles WHERE IdPerfil = :id";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':id' => $id_perfil]);
        $perfil_a_eliminar = $stmtVerificar->fetch();

        if (!$perfil_a_eliminar) {
            redireccionar('perfiles', 'index', null, 'danger', 'Perfil no encontrado para eliminar.');
        }

        // Verificar si hay usuarios asociados a este perfil
        $sqlUsuariosAsociados = "SELECT COUNT(*) FROM tblusuarios WHERE IdPerfil = :id_perfil";
        $stmtUsuariosAsociados = $pdo->prepare($sqlUsuariosAsociados);
        $stmtUsuariosAsociados->execute([':id_perfil' => $id_perfil]);
        $num_usuarios = $stmtUsuariosAsociados->fetchColumn();

        if ($num_usuarios > 0) {
            redireccionar('perfiles', 'index', null, 'danger', 'No se puede eliminar el perfil porque tiene ' . $num_usuarios . ' usuario(s) asociado(s).');
        }

        try {
            // Eliminar físicamente el perfil
            $sql = "DELETE FROM tblperfiles WHERE IdPerfil = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_perfil]);
            
            logAction($pdo, $_SESSION['user_id'], 'perfiles', 'eliminar', "Perfil '{$perfil_a_eliminar['NombrePerfil']}' (ID: {$id_perfil}) eliminado.");
            $mensaje_exito = "El perfil " . htmlspecialchars($perfil_a_eliminar['NombrePerfil']) . " ha sido eliminado correctamente.";
            redireccionar('perfiles', 'index', null, 'success', $mensaje_exito);
        } catch (PDOException $e) {
            $errores[] = "Error al eliminar el perfil: " . $e->getMessage();
            redireccionar('perfiles', 'index', null, 'danger', implode(', ', $errores));
        }
        break;

    default:
        redireccionar('perfiles', 'index', null, 'danger', 'Acción no válida.');
        break;
}
?>
