<?php
session_start();

// Si hay un usuario logueado, invalidar su token
if (isset($_SESSION['user_id']) && isset($_SESSION['user_token'])) {
    require_once 'config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Invalidar el token en la base de datos
    $sql = "UPDATE tblusuarios SET TokenSesion = NULL, ExpiracionToken = NULL WHERE IdUsuario = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $_SESSION['user_id']]);
}

// Destruir la sesión
session_unset();
session_destroy();

// Redireccionar al login
header('Location: login.php');
exit;
?>
