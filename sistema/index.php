<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_token'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$database = new Database();
$pdo = $database->getConnection();

// Verificar validez del token
$sql = "SELECT ExpiracionToken FROM tblusuarios WHERE IdUsuario = :id AND TokenSesion = :token";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':id' => $_SESSION['user_id'],
    ':token' => $_SESSION['user_token']
]);
$tokenData = $stmt->fetch();

if (!$tokenData || strtotime($tokenData['ExpiracionToken']) < time()) {
    // Token expirado o inválido
    session_destroy();
    header('Location: ../login.php?error=sesion_expirada');
    exit;
}

// Obtener el módulo y acción solicitados
$modulo = $_GET['modulo'] ?? 'dashboard';
$accion = $_GET['accion'] ?? 'index';

// Obtener lista de módulos activos
$sqlModulos = "SELECT NombreModulo FROM tblmodulos WHERE EstadoModulo = 'activo'";
$stmtModulos = $pdo->query($sqlModulos);
$modulos_validos = $stmtModulos->fetchAll(PDO::FETCH_COLUMN);

// Agregar dashboard que es un módulo especial
$modulos_validos[] = 'dashboard';

// Verificar si el módulo es válido
if (!in_array($modulo, $modulos_validos)) {
    $modulo = 'dashboard';
}

// Verificar permisos para el módulo (excepto dashboard)
if ($modulo !== 'dashboard') {
    $accion_permiso = ($accion === 'index' || $accion === 'ver') ? 'ver' : $accion;
    
    if (!verificarPermiso($modulo, $accion_permiso, $_SESSION['user_perfil_id'], $pdo)) {
        $error_permiso = "No tienes permisos para acceder a esta sección.";
        $modulo = 'dashboard';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaxiPack V2.0 - Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <!-- Header -->
        <?php include 'src/includes/header.php'; ?>
        
        <!-- Sidebar -->
        <?php include 'src/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="container-fluid p-4">
                <?php if (isset($error_permiso)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_permiso; ?>
                    </div>
                <?php endif; ?>
                
                <?php
                // Cargar el contenido del módulo
                switch ($modulo) {
                    case 'dashboard':
                        echo '<div class="row">
                                <div class="col-12">
                                    <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
                                    <div class="card">
                                        <div class="card-body">
                                            <p>Bienvenido al sistema TaxiPack V2.0, ' . htmlspecialchars($_SESSION['user_nombre']) . '</p>
                                            <p>Perfil: ' . htmlspecialchars($_SESSION['user_perfil']) . '</p>
                                        </div>
                                    </div>
                                </div>
                              </div>';
                        break;
                        
                    default:
                        // Cargar módulo específico
                        $archivo_modulo = "modulos/{$modulo}/{$accion}.php";
                        if (file_exists($archivo_modulo)) {
                            include $archivo_modulo;
                        } else {
                            echo '<div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    Módulo no encontrado: ' . htmlspecialchars($modulo) . '/' . htmlspecialchars($accion) . '.php
                                  </div>';
                        }
                        break;
                }
                ?>
            </div>
        </main>
        
        <!-- Footer -->
        <?php include 'src/includes/footer.php'; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
</body>
</html>
