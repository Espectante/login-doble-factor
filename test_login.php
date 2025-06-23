<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos necesarios
require_once 'model/conect.php';
require_once 'model/auth.php';

// Usando clean_input() de auth.php

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener y limpiar datos del formulario
        $usuario = clean_input($_POST['usuario'] ?? '');
        $password = clean_input($_POST['password'] ?? '');
        $codigo_2fa = clean_input($_POST['codigo_2fa'] ?? '');
        
        // Verificar credenciales
        $stmt = $con->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Verificar contraseña
            if (password_verify($password, $user['password'])) {
                // Verificar 2FA
                if (!empty($user['codigo_2fa'])) {
                    if ($codigo_2fa === $user['codigo_2fa'] && 
                        strtotime($user['codigo_2fa_expiracion']) > time()) {
                        
                        // Iniciar sesión
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['usuario'] = $user['usuario'];
                        $_SESSION['logged_in'] = true;
                        
                        // Actualizar último acceso
                        $update = $con->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                        $update->execute([$user['id']]);
                        
                        $mensaje = "<div class='alert alert-success'>¡Inicio de sesión exitoso! Redirigiendo...</div>";
                        $redireccionar = true;
                    } else {
                        $error = "Código 2FA inválido o expirado";
                    }
                } else {
                    $error = "No se ha configurado 2FA para este usuario";
                }
            } else {
                $error = "Contraseña incorrecta";
            }
        } else {
            $error = "Usuario no encontrado";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Inicio de Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { max-width: 400px; margin: 100px auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card shadow">
                <div class="card-body">
                    <h2 class="text-center mb-4">Prueba de Inicio</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($mensaje)): ?>
                        <?php echo $mensaje; ?>
                        <?php if (isset($redireccionar)): ?>
                            <script>
                                setTimeout(function() {
                                    window.location.href = 'dashboard.php';
                                }, 2000);
                            </script>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label class="form-label">Usuario:</label>
                                <input type="text" name="usuario" class="form-control" value="admin" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contraseña:</label>
                                <input type="password" name="password" class="form-control" value="Admin123!" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Código 2FA:</label>
                                <input type="text" name="codigo_2fa" class="form-control" value="123456" required>
                                <div class="form-text">Usando código de prueba: 123456</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-3 text-center">
                <a href="index.php" class="text-decoration-none">← Volver al inicio de sesión normal</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
