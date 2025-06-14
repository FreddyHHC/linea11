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

// Directorio de subida de archivos (ajusta según tu estructura de carpetas)
// Asegúrate de que esta carpeta exista y tenga permisos de escritura (ej. 755 o 777)
$upload_dir = 'uploads/';

// Crear el directorio si no existe
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Obtener la acción a realizar
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$id_documento = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$errores = [];
$mensaje_exito = '';

switch ($accion) {
    case 'crear':
        // Validar permisos para crear
        if (!verificarPermiso('respaldos_documentos', 'crear', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('respaldos_documentos', 'crear', null, 'danger', 'No tienes permisos para cargar documentos.');
        }

        // Validar campos
        $observacion = trim($_POST['observacion'] ?? '');
        $fecha_carga = date('Y-m-d'); // Fecha actual
        $usuario_documento = $_SESSION['user_run'] ?? 'N/A'; // RUN del usuario logueado

        if (empty($observacion)) { $errores[] = "La observación es obligatoria"; }

        $ruta_archivo = '';
        if (isset($_FILES['documento_file']) && $_FILES['documento_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['documento_file']['tmp_name'];
            $file_name = $_FILES['documento_file']['name'];
            $file_size = $_FILES['documento_file']['size'];
            $file_type = $_FILES['documento_file']['type'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_ext, $allowed_extensions)) {
                $errores[] = "Tipo de archivo no permitido. Solo se permiten PDF, JPG, PNG, DOCX, XLSX.";
            }
            if ($file_size > $max_file_size) {
                $errores[] = "El archivo es demasiado grande. El tamaño máximo permitido es 5MB.";
            }

            if (empty($errores)) {
                $new_file_name = uniqid('doc_', true) . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $destination)) {
                    $ruta_archivo = $new_file_name; // Ruta relativa para guardar en DB
                } else {
                    $errores[] = "Error al mover el archivo subido.";
                }
            }
        } else {
            $errores[] = "Debe seleccionar un archivo para cargar.";
        }
        
        if (empty($errores)) {
            try {
                $sql = "INSERT INTO tbldocumentos (NombreDocumento, ObservacionDocumento, FechaCargaDocumento, UsuarioDocumento) 
                        VALUES (:nombre, :observacion, :fecha_carga, :usuario_documento)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $new_file_name,
                    ':observacion' => $observacion,
                    ':fecha_carga' => $fecha_carga,
                    ':usuario_documento' => $usuario_documento
                ]);
                logAction($pdo, $_SESSION['user_id'], 'respaldos_documentos', 'crear', "Documento '{$new_file_name}' cargado.");
                $mensaje_exito = "Documento cargado correctamente.";
                redireccionar('respaldos_documentos', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                // Si hay un error en la DB, intentar eliminar el archivo subido
                if (file_exists($upload_dir . basename($ruta_archivo))) {
                    unlink($upload_dir . basename($ruta_archivo));
                }
                $errores[] = "Error al guardar el documento en la base de datos: " . $e->getMessage();
                redireccionar('respaldos_documentos', 'crear', null, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('respaldos_documentos', 'crear', null, 'danger', implode(', ', $errores));
        }
        break;

    case 'editar':
        // Validar permisos para editar
        if (!verificarPermiso('respaldos_documentos', 'editar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('respaldos_documentos', 'editar', $id_documento, 'danger', 'No tienes permisos para editar documentos.');
        }

        if ($id_documento <= 0) {
            redireccionar('respaldos_documentos', 'index', null, 'danger', 'ID de documento inválido para editar.');
        }

        // Obtener datos actuales del documento para la ruta del archivo antiguo
        $sqlOldDoc = "SELECT NombreDocumento FROM tbldocumentos WHERE IdDocumento = :id";
        $stmtOldDoc = $pdo->prepare($sqlOldDoc);
        $stmtOldDoc->execute([':id' => $id_documento]);
        $old_documento_data = $stmtOldDoc->fetch();
        $old_ruta_archivo = $old_documento_data['NombreDocumento'] ?? '';

        // Validar campos
        $nombre = trim($_POST['NombreDocumento'] ?? '');
        $observacion = trim($_POST['observacion'] ?? '');
        $ruta_archivo = $old_ruta_archivo; // Mantener la ruta actual por defecto

        /*if (empty($nombre)) { $errores[] = "El nombre del documento es obligatorio"; }*/
        if (empty($observacion)) { $errores[] = "La observación es obligatoria"; }

        // Manejar la subida de un nuevo archivo si se proporciona
        if (isset($_FILES['documento_file']) && $_FILES['documento_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['documento_file']['tmp_name'];
            $file_name = $_FILES['documento_file']['name'];
            $file_size = $_FILES['documento_file']['size'];
            $file_type = $_FILES['documento_file']['type'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_ext, $allowed_extensions)) {
                $errores[] = "Tipo de archivo no permitido. Solo se permiten PDF, JPG, PNG, DOCX, XLSX.";
            }
            if ($file_size > $max_file_size) {
                $errores[] = "El archivo es demasiado grande. El tamaño máximo permitido es 5MB.";
            }

            if (empty($errores)) {
                $new_file_name = uniqid('doc_', true) . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $destination)) {
                    // Eliminar el archivo antiguo si existe y es diferente al nuevo
                    if (!empty($old_ruta_archivo) && file_exists($upload_dir . basename($old_ruta_archivo))) {
                        unlink($upload_dir . basename($old_ruta_archivo));
                    }
                    $ruta_archivo = $new_file_name; // Actualizar ruta en DB
                } else {
                    $errores[] = "Error al mover el nuevo archivo subido.";
                }
            }
        } elseif (isset($_FILES['documento_file']) && $_FILES['documento_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Si hubo un error en la subida del archivo (no es UPLOAD_ERR_NO_FILE)
            $errores[] = "Error al subir el archivo: " . $_FILES['documento_file']['error'];
        }

        if (empty($errores)) {
            try {
                $file_to_save_in_db = $new_file_name ?? $old_ruta_archivo;

                $sql = "UPDATE tbldocumentos SET
                        NombreDocumento = :nombre_documento,
                        ObservacionDocumento = :observacion
                        WHERE IdDocumento = :id"; // Removed the trailing comma

                $params = [
                    ':nombre_documento' => $file_to_save_in_db, // This variable holds the new or old unique file name
                    ':observacion' => $observacion,
                    ':id' => $id_documento
                ];

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                logAction($pdo, $_SESSION['user_id'], 'respaldos_documentos', 'editar', "Documento (ID: {$id_documento}) actualizado. Archivo: {$file_to_save_in_db}");
                $mensaje_exito = "Documento actualizado correctamente.";
                redireccionar('respaldos_documentos', 'index', null, 'success', $mensaje_exito);
            } catch (PDOException $e) {
                $errores[] = "Error al actualizar el documento en la base de datos: " . $e->getMessage();
                redireccionar('respaldos_documentos', 'editar', $id_documento, 'danger', implode(', ', $errores));
            }
        } else {
            redireccionar('respaldos_documentos', 'editar', $id_documento, 'danger', implode(', ', $errores));
        }
        break;

    case 'eliminar':
        // Validar permisos para eliminar
        if (!verificarPermiso('respaldos_documentos', 'eliminar', $_SESSION['user_perfil_id'], $pdo)) {
            redireccionar('respaldos_documentos', 'index', null, 'danger', 'No tienes permisos para eliminar documentos.');
        }

        if ($id_documento <= 0) {
            redireccionar('respaldos_documentos', 'index', null, 'danger', 'ID de documento inválido para eliminar.');
        }

        // Verificar si el documento existe y obtener su ruta de archivo
        $sqlVerificar = "SELECT NombreDocumento FROM tbldocumentos WHERE IdDocumento = :id";
        $stmtVerificar = $pdo->prepare($sqlVerificar);
        $stmtVerificar->execute([':id' => $id_documento]);
        $documento_a_eliminar = $stmtVerificar->fetch();

        if (!$documento_a_eliminar) {
            redireccionar('respaldos_documentos', 'index', null, 'danger', 'Documento no encontrado para eliminar.');
        }

        try {
            $pdo->beginTransaction();

            // Eliminar el archivo físico si existe
            $file_to_delete = $upload_dir . basename($documento_a_eliminar['NombreDocumento']);
            if (!empty($documento_a_eliminar['NombreDocumento']) && file_exists($file_to_delete)) {
                if (!unlink($file_to_delete)) {
                    throw new Exception("No se pudo eliminar el archivo físico: " . $file_to_delete);
                }
            }

            // Eliminar el registro de la base de datos
            $sql = "DELETE FROM tbldocumentos WHERE IdDocumento = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id_documento]);
            
            $pdo->commit();
            logAction($pdo, $_SESSION['user_id'], 'respaldos_documentos', 'eliminar', "Documento '{$documento_a_eliminar['NombreDocumento']}' (ID: {$id_documento}) eliminado.");
            $mensaje_exito = "El documento " . htmlspecialchars($documento_a_eliminar['NombreDocumento']) . " ha sido eliminado correctamente.";
            redireccionar('respaldos_documentos', 'index', null, 'success', $mensaje_exito);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = "Error al eliminar el documento: " . $e->getMessage();
            redireccionar('respaldos_documentos', 'index', null, 'danger', implode(', ', $errores));
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores[] = "Error de base de datos al eliminar el documento: " . $e->getMessage();
            redireccionar('respaldos_documentos', 'index', null, 'danger', implode(', ', $errores));
        }
        break;

    default:
        redireccionar('respaldos_documentos', 'index', null, 'danger', 'Acción no válida.');
        break;
}
?>
