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
$id_perfil = isset($_POST['id_perfil']) ? (int)$_POST['id_perfil'] : (isset($_GET['id_perfil']) ? (int)$_GET['id_perfil'] : 0);
$id_permiso = isset($_GET['id']) ? (int)$_GET['id'] : 0; // Para la acción de eliminar un permiso específico

$errores = [];
$mensaje_exito = '';

switch ($accion) {
    case 'guardar_permisos':
        // Validar permisos para editar permisos
        if (!verificarPermiso('permisos', 'editar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('permisos', 'gestionar', $id_perfil, 'danger', 'No tienes permisos para guardar permisos.');
        }

        if ($id_perfil <= 0) {
            redireccionar('permisos', 'index', null, 'danger', 'ID de perfil inválido para guardar permisos.');
        }

        $permisos_enviados = $_POST['permisos'] ?? []; // Array de permisos por módulo

        try {
            $pdo->beginTransaction();

            foreach ($permisos_enviados as $id_modulo => $flags) {
                $id_modulo = (int)$id_modulo;
                $puede_ver = isset($flags['ver']) ? 1 : 0;
                $puede_agregar = isset($flags['agregar']) ? 1 : 0;
                $puede_editar = isset($flags['editar']) ? 1 : 0;
                $puede_eliminar = isset($flags['eliminar']) ? 1 : 0;
                $permiso_especial = isset($flags['especial']) ? 1 : 0;

                // Verificar si el permiso ya existe para este perfil y módulo
                $sql_check = "SELECT IdPermiso FROM tblpermisos WHERE IdPerfil = :id_perfil AND IdModulo = :id_modulo";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->execute([':id_perfil' => $id_perfil, ':id_modulo' => $id_modulo]);
                $existing_permiso = $stmt_check->fetch();

                if ($existing_permiso) {
                    // Actualizar permiso existente
                    $sql_update = "UPDATE tblpermisos SET 
                                    PuedeVer = :puede_ver, 
                                    PuedeAgregar = :puede_agregar, 
                                    PuedeEditar = :puede_editar, 
                                    PuedeEliminar = :puede_eliminar, 
                                    PermisoEspecial = :permiso_especial 
                                    WHERE IdPermiso = :id_permiso";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->execute([
                        ':puede_ver' => $puede_ver,
                        ':puede_agregar' => $puede_agregar,
                        ':puede_editar' => $puede_editar,
                        ':puede_eliminar' => $puede_eliminar,
                        ':permiso_especial' => $permiso_especial,
                        ':id_permiso' => $existing_permiso['IdPermiso']
                    ]);
                } else {
                    // Insertar nuevo permiso
                    $sql_insert = "INSERT INTO tblpermisos (IdPerfil, IdModulo, PuedeVer, PuedeAgregar, PuedeEditar, PuedeEliminar, PermisoEspecial) 
                                    VALUES (:id_perfil, :id_modulo, :puede_ver, :puede_agregar, :puede_editar, :puede_eliminar, :permiso_especial)";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    $stmt_insert->execute([
                        ':id_perfil' => $id_perfil,
                        ':id_modulo' => $id_modulo,
                        ':puede_ver' => $puede_ver,
                        ':puede_agregar' => $puede_agregar,
                        ':puede_editar' => $puede_editar,
                        ':puede_eliminar' => $puede_eliminar,
                        ':permiso_especial' => $permiso_especial
                    ]);
                }
            }

            $pdo->commit();
            // Obtener el nombre del perfil para el log
            $sqlPerfilNombre = "SELECT NombrePerfil FROM tblperfiles WHERE IdPerfil = :id_perfil";
            $stmtPerfilNombre = $pdo->prepare($sqlPerfilNombre);
            $stmtPerfilNombre->execute([':id_perfil' => $id_perfil]);
            $nombre_perfil_log = $stmtPerfilNombre->fetchColumn();
            logAction($pdo, $_SESSION['user_id'], 'permisos', 'guardar_permisos', "Permisos actualizados para el perfil '{$nombre_perfil_log}' (ID: {$id_perfil}).");
            $mensaje_exito = "Permisos actualizados correctamente para el perfil.";
            redireccionar('permisos', 'gestionar', $id_perfil, 'success', $mensaje_exito);

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores[] = "Error al guardar permisos: " . $e->getMessage();
            redireccionar('permisos', 'gestionar', $id_perfil, 'danger', implode(', ', $errores));
        }
        break;

    case 'eliminar':
        // Validar permisos para eliminar un permiso específico (registro IdPermiso)
        if (!verificarPermiso('permisos', 'eliminar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('permisos', 'index', null, 'danger', 'No tienes permisos para eliminar permisos.');
        }

        if ($id_permiso <= 0) {
            redireccionar('permisos', 'index', null, 'danger', 'ID de permiso inválido para eliminar.');
        }

        try {
            // Obtener información del permiso antes de eliminar para el mensaje
            $sql_info = "SELECT p.NombrePerfil, m.NombreModulo 
                         FROM tblpermisos tp
                         INNER JOIN tblperfiles p ON tp.IdPerfil = p.IdPerfil
                         INNER JOIN tblmodulos m ON tp.IdModulo = m.IdModulo
                         WHERE tp.IdPermiso = :id_permiso";
            $stmt_info = $pdo->prepare($sql_info);
            $stmt_info->execute([':id_permiso' => $id_permiso]);
            $permiso_info = $stmt_info->fetch();

            if (!$permiso_info) {
                redireccionar('permisos', 'index', null, 'danger', 'Permiso no encontrado para eliminar.');
            }

            $sql_delete = "DELETE FROM tblpermisos WHERE IdPermiso = :id_permiso";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->execute([':id_permiso' => $id_permiso]);
            
            logAction($pdo, $_SESSION['user_id'], 'permisos', 'eliminar', "Permiso eliminado para el perfil '" . htmlspecialchars($permiso_info['NombrePerfil']) . "' en el módulo '" . htmlspecialchars($permiso_info['NombreModulo']) . "' (ID: {$id_permiso}).");
            $mensaje_exito = "El permiso para el perfil '" . htmlspecialchars($permiso_info['NombrePerfil']) . "' en el módulo '" . htmlspecialchars($permiso_info['NombreModulo']) . "' ha sido eliminado correctamente.";
            redireccionar('permisos', 'index', null, 'success', $mensaje_exito);
        } catch (PDOException $e) {
            $errores[] = "Error al eliminar el permiso: " . $e->getMessage();
            redireccionar('permisos', 'index', null, 'danger', implode(', ', $errores));
        }
        break;

    default:
        redireccionar('permisos', 'index', null, 'danger', 'Acción no válida.');
        break;
}
?>
