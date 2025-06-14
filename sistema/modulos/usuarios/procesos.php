<?php
session_start();
require_once '../../../config/database.php';

$database = new Database();
$pdo = $database->getConnection();

// 1. Validar sesión y token al inicio de procesos.php
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
$id_usuario = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$errores = [];
$mensaje_exito = '';

switch ($accion) {
    case 'crear':
        // 2. Validar permisos para crear
        if (!verificarPermiso('usuarios', 'crear', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('usuarios', 'crear', null, 'danger', 'No tienes permisos para crear usuarios.');
        }

        // Validar campos
        $run = trim($_POST['run'] ?? '');
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';
        $perfil = (int)($_POST['perfil'] ?? 0);
        $estado = $_POST['estado'] ?? 'activo';
        
        if (empty($run)) { $errores[] = "El RUN es obligatorio"; }
        if (empty($nombres)) { $errores[] = "Los nombres son obligatorios"; }
        if (empty($apellidos)) { $errores[] = "Los apellidos son obligatorios"; }
        if (empty($correo)) {
            $errores[] = "El correo es obligatorio";
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El formato del correo es inválido";
        } else {
            $sqlVerificar = "SELECT COUNT(*) FROM tblusuarios WHERE CorreoUsuario = :correo";
            $stmtVerificar = $pdo->prepare($sqlVerificar);
            $stmtVerificar->execute([':correo' => $correo]);
            if ($stmtVerificar->fetchColumn() > 0) {
                $errores[] = "El correo ya está registrado";
            }
        }
        if (empty($password)) {
            $errores[] = "La contraseña es obligatoria";
        } elseif (strlen($password) < 8) {
            $errores[] = "La contraseña debe tener al menos 8 caracteres";
        } elseif ($password !== $confirmar_password) {
            $errores[] = "Las contraseñas no coinciden";
        }
        if ($perfil <= 0) { $errores[] = "Debe seleccionar un perfil"; }
        
        if (empty($errores)) {
            try {
                $sql = "INSERT INTO tblusuarios (RunUsuario, NombresUsuario, ApellidosUsuario, CorreoUsuario, 
                        PasswordUsuario, EstadoUsuario, IdPerfil) 
                        VALUES (:run, :nombres, :apellidos, :correo, :password, :estado, :perfil)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':run' => $run,
                    ':nombres' => $nombres,
                    ':apellidos' => $apellidos,
                    ':correo' => $correo,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':estado' => $estado,
                    ':perfil' => $perfil
                ]);
                logAction($pdo, $_SESSION['user_id'], 'usuarios', 'crear', "Usuario '{$nombres} {$apellidos}' ({$correo}) creado.");
                $mensaje_exito = "Usuario creado correctamente.";
                redireccionar('usuarios', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al guardar: " . $e->getMessage();
                redireccionar('usuarios', 'crear', null, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('usuarios', 'crear', null, 'danger', implode(', ', $errores));
        }
        break;

    case 'editar':
        // 2. Validar permisos para editar
        if (!verificarPermiso('usuarios', 'editar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('usuarios', 'editar', $id_usuario, 'danger', 'No tienes permisos para editar usuarios.');
        }

        if ($id_usuario <= 0) {
            redireccionar('usuarios', 'index', null, 'danger', 'ID de usuario inválido para editar.');
        }

        // Validar campos
        $run = trim($_POST['run'] ?? '');
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';
        $perfil = (int)($_POST['perfil'] ?? 0);
        $estado = $_POST['estado'] ?? 'activo';
        
        if (empty($run)) { $errores[] = "El RUN es obligatorio"; }
        if (empty($nombres)) { $errores[] = "Los nombres son obligatorios"; }
        if (empty($apellidos)) { $errores[] = "Los apellidos son obligatorios"; }
        if (empty($correo)) {
            $errores[] = "El correo es obligatorio";
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El formato del correo es inválido";
        } else {
            $sqlVerificar = "SELECT COUNT(*) FROM tblusuarios WHERE CorreoUsuario = :correo AND IdUsuario != :id";
            $stmtVerificar = $pdo->prepare($sqlVerificar);
            $stmtVerificar->execute([':correo' => $correo, ':id' => $id_usuario]);
            if ($stmtVerificar->fetchColumn() > 0) {
                $errores[] = "El correo ya está registrado por otro usuario";
            }
        }
        if (!empty($password)) {
            if (strlen($password) < 8) {
                $errores[] = "La nueva contraseña debe tener al menos 8 caracteres";
            } elseif ($password !== $confirmar_password) {
                $errores[] = "Las contraseñas no coinciden";
            }
        }
        if ($perfil <= 0) { $errores[] = "Debe seleccionar un perfil"; }

        if (empty($errores)) {
            try {
                $sql = "UPDATE tblusuarios SET 
                        RunUsuario = :run, 
                        NombresUsuario = :nombres, 
                        ApellidosUsuario = :apellidos, 
                        CorreoUsuario = :correo, 
                        EstadoUsuario = :estado, 
                        IdPerfil = :perfil";
                
                $params = [
                    ':run' => $run,
                    ':nombres' => $nombres,
                    ':apellidos' => $apellidos,
                    ':correo' => $correo,
                    ':estado' => $estado,
                    ':perfil' => $perfil,
                    ':id' => $id_usuario
                ];
                
                if (!empty($password)) {
                    $sql .= ", PasswordUsuario = :password";
                    $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE IdUsuario = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                // Actualizar datos del usuario en la sesión si es el usuario actual
                if ($id_usuario == $_SESSION['user_id']) {
                    $_SESSION['user_run'] = $run;
                    $_SESSION['user_nombre'] = $nombres . ' ' . $apellidos;
                    $_SESSION['user_correo'] = $correo;
                    
                    if ($perfil != $_SESSION['user_perfil_id']) {
                        $sqlPerfil = "SELECT NombrePerfil FROM tblperfiles WHERE IdPerfil = :id";
                        $stmtPerfil = $pdo->prepare($sqlPerfil);
                        $stmtPerfil->execute([':id' => $perfil]);
                        $perfilNombre = $stmtPerfil->fetchColumn();
                        
                        $_SESSION['user_perfil_id'] = $perfil;
                        $_SESSION['user_perfil'] = $perfilNombre;
                    }
                }
                logAction($pdo, $_SESSION['user_id'], 'usuarios', 'editar', "Usuario '{$nombres} {$apellidos}' ({$correo}) editado (ID: {$id_usuario}).");
                $mensaje_exito = "Usuario actualizado correctamente.";
                redireccionar('usuarios', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al actualizar: " . $e->getMessage();
                redireccionar('usuarios', 'editar', $id_usuario, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('usuarios', 'editar', $id_usuario, 'danger', implode(', ', $errores));
        }
        break;

    case 'eliminar':
        // 2. Validar permisos para eliminar
        if (!verificarPermiso('usuarios', 'eliminar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('usuarios', 'index', null, 'danger', 'No tienes permisos para eliminar usuarios.');
        }

        if ($id_usuario <= 0) {
            redireccionar('usuarios', 'index', null, 'danger', 'ID de usuario inválido para eliminar.');
        }

        // Verificar si el usuario existe
        $sqlVerificar = "SELECT NombresUsuario, ApellidosUsuario FROM tblusuarios WHERE IdUsuario = :id";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':id' => $id_usuario]);
        $usuario_a_eliminar = $stmtVerificar->fetch();

        if (!$usuario_a_eliminar) {
            redireccionar('usuarios', 'index', null, 'danger', 'Usuario no encontrado para eliminar.');
        }

        try {
            // En lugar de eliminar físicamente, cambiamos el estado a inactivo
            $sql = "DELETE FROM tblusuarios WHERE IdUsuario = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_usuario]);
            
            logAction($pdo, $_SESSION['user_id'], 'usuarios', 'eliminar', "Usuario '{$usuario_a_eliminar['NombresUsuario']} {$usuario_a_eliminar['ApellidosUsuario']}' (ID: {$id_usuario}) eliminado.");
            $mensaje_exito = "El usuario " . htmlspecialchars($usuario_a_eliminar['NombresUsuario'] . ' ' . $usuario_a_eliminar['ApellidosUsuario']) . " ha sido eliminado correctamente.";
            redireccionar('usuarios', 'index', null, 'success', $mensaje_exito);
        } catch (PDOException $e) {
            $errores[] = "Error al desactivar el usuario: " . $e->getMessage();
            redireccionar('usuarios', 'index', null, 'danger', implode(', ', $errores));
        }
        break;

    default:
        redireccionar('usuarios', 'index', null, 'danger', 'Acción no válida.');
        break;
}
?>
