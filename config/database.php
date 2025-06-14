<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'bd_linea11_2025'; // Nombre de la BD actualizado
    private $username = 'root';
    private $password = ''; // Sin contraseña
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Función para verificar permisos
function verificarPermiso($modulo, $accion, $idPerfil, $pdo) {
    // Mapeo de acciones a campos de permisos
    $mapeoAcciones = [
        'ver' => 'PuedeVer',
        'crear' => 'PuedeAgregar',
        'agregar' => 'PuedeAgregar',
        'editar' => 'PuedeEditar',
        'eliminar' => 'PuedeEliminar',
        'especial' => 'PermisoEspecial',
        'gestionar' => 'PermisoEspecial' // CAMBIO AQUÍ: Ahora usa PermisoEspecial
    ];
    
    // Verificar que la acción sea válida
    if (!isset($mapeoAcciones[$accion])) {
        return false;
    }
    
    $campoPermiso = $mapeoAcciones[$accion];
    
    $sql = "SELECT p.{$campoPermiso} 
            FROM tblpermisos p 
            INNER JOIN tblmodulos m ON p.IdModulo = m.IdModulo 
            WHERE m.NombreModulo = :modulo 
            AND p.IdPerfil = :idPerfil 
            AND m.EstadoModulo = 'activo'";

    // --- INICIO DEBUG ADICIONAL (verificarPermiso) ---
    echo "<!-- Debug (verificarPermiso - SQL Params): Modulo: " . htmlspecialchars($modulo) . ", Accion: " . htmlspecialchars($accion) . ", Campo Permiso: " . htmlspecialchars($campoPermiso) . ", IdPerfil: " . htmlspecialchars($idPerfil) . " -->";
    echo "<!-- Debug (verificarPermiso - SQL Query): " . htmlspecialchars($sql) . " -->";
    // --- FIN DEBUG ADICIONAL (verificarPermiso) ---
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':modulo' => $modulo,
        ':idPerfil' => $idPerfil
    ]);
    
    $resultado = $stmt->fetch();

    // --- INICIO DEBUG ADICIONAL (verificarPermiso - Raw Result) ---
    echo "<!-- Debug (verificarPermiso - Raw Result): " . ($resultado ? json_encode($resultado) : "null") . " -->";
    // --- FIN DEBUG ADICIONAL (verificarPermiso - Resultado Crudo) ---
    
    // --- INICIO DEBUG (verificarPermiso) ---
    echo "<!-- Debug (verificarPermiso): Verificando modulo: " . htmlspecialchars($modulo) . ", accion: " . htmlspecialchars($accion) . " (campo: " . htmlspecialchars($campoPermiso) . "), perfil ID: " . htmlspecialchars($idPerfil) . " -->";
    echo "<!-- Debug (verificarPermiso): Resultado SQL: " . ($resultado ? json_encode($resultado) : "null") . " -->";
    echo "<!-- Debug (verificarPermiso): Permiso final: " . (($resultado && $resultado[$campoPermiso] == 1) ? "TRUE" : "FALSE") . " -->";
    // --- FIN DEBUG (verificarPermiso) ---

    return ($resultado && $resultado[$campoPermiso] == 1);
}

function logAction($pdo, $idUsuario, $modulo, $accion, $descripcion) {
    try {
        $ip_origen = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
        $token_sesion_log = $_SESSION['user_token'] ?? null;

        $sql = "INSERT INTO tbllogs (IdUsuario, Modulo, Accion, Descripcion, IP_Origen, UserAgent, TokenSesionLog)
                VALUES (:id_usuario, :modulo, :accion, :descripcion, :ip_origen, :user_agent, :token_sesion_log)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':modulo' => $modulo,
            ':accion' => $accion,
            ':descripcion' => $descripcion,
            ':ip_origen' => $ip_origen,
            ':user_agent' => $user_agent,
            ':token_sesion_log' => $token_sesion_log
        ]);
    } catch (PDOException $e) {
        // Log the error internally, but don't stop the main process
        error_log("Error al registrar log: " . $e->getMessage());
    }
}

function redireccionar($modulo, $accion = 'index', $id_param_value = null, $tipo_mensaje = 'danger', $mensaje = 'Ocurrió un error desconocido.') {
    $url = "../../index.php?modulo={$modulo}";
    if ($accion !== 'index') {
        $url .= "&accion={$accion}";
    }

    // Determine the correct parameter name based on module and action
    $id_param_name = 'id'; // Default parameter name
    if ($modulo === 'permisos' && $accion === 'gestionar') {
        $id_param_name = 'id_perfil'; // Use 'id_perfil' for 'permisos' module and 'gestionar' action
    }
    
    if ($id_param_value !== null) {
        $url .= "&{$id_param_name}={$id_param_value}";
    }
    $url .= "&status={$tipo_mensaje}&message=" . urlencode($mensaje);
    header("Location: {$url}");
    exit;
}

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
