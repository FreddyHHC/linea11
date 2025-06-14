<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_POST) {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($correo) && !empty($password)) {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $sql = "SELECT u.*, p.NombrePerfil 
                FROM tblusuarios u 
                INNER JOIN tblperfiles p ON u.IdPerfil = p.IdPerfil 
                WHERE u.CorreoUsuario = :correo AND u.EstadoUsuario = 'activo'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['PasswordUsuario'])) {
            // Generar token de sesión
            $token = bin2hex(random_bytes(32));
            $expiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Actualizar token en la base de datos
            $updateSql = "UPDATE tblusuarios SET TokenSesion = :token, ExpiracionToken = :expiracion 
                          WHERE IdUsuario = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':token' => $token,
                ':expiracion' => $expiracion,
                ':id' => $user['IdUsuario']
            ]);
            
            // Guardar datos en sesión
            $_SESSION['user_id'] = $user['IdUsuario'];
            $_SESSION['user_run'] = $user['RunUsuario'];
            $_SESSION['user_nombre'] = $user['NombresUsuario'] . ' ' . $user['ApellidosUsuario'];
            $_SESSION['user_correo'] = $user['CorreoUsuario'];
            $_SESSION['user_perfil_id'] = $user['IdPerfil'];
            $_SESSION['user_perfil'] = $user['NombrePerfil'];
            $_SESSION['user_token'] = $token;
            
            header('Location: sistema/index.php');
            exit;
        } else {
            $error = 'Correo o contraseña incorrectos';
        }
    } else {
        $error = 'Por favor complete todos los campos';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TaxiPack V2.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/styles.css" rel="stylesheet">
</head>
<body class="bg-dark">
    <div class="container vh-100 d-flex align-items-center justify-content-center">
        <div class="row w-100">
            <div class="col-md-4 mx-auto">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="assets/img/logo.jpg" alt="Logo" style="max-height: 80px;">
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correo" name="correo" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="index.php" class="text-decoration-none">← Volver al inicio</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
