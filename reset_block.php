<?php
// Script para restablecer bloqueos de seguridad
require_once 'model/conect.php';

// Función para limpiar la salida del buffer
function clean_output($buffer) {
    return preg_replace('/<br\s*\/?>|\n|\r/i', '', $buffer);
}

// Iniciar el buffer de salida
ob_start("clean_output");

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Restablecer bloqueos</title>";
echo "<style>body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }</style>";
echo "</head><body>";
echo "<h2>Restableciendo bloqueos de seguridad...</h2><pre>";

try {
    $con = conectarDB();
    
    // 1. Limpiar bloqueos en la base de datos
    $stmt = $con->prepare("UPDATE usuarios SET bloqueado_until = NULL, intentos = 0, codigo_2fa = NULL, codigo_2fa_expiracion = NULL");
    $stmt->execute();
    $affected = $stmt->rowCount();
    echo "✅ Se restablecieron $affected usuarios\n";
    
    // 2. Limpiar intentos de inicio de sesión
    $stmt = $con->prepare("TRUNCATE TABLE login_attempts");
    $stmt->execute();
    echo "✅ Se limpió el historial de intentos de inicio de sesión\n";
    
    // 3. Crear usuario admin si no existe
    $password_hash = password_hash('Admin123!', PASSWORD_BCRYPT);
    $stmt = $con->prepare("INSERT IGNORE INTO usuarios (usuario, password, email, activo) VALUES (?, ?, 'admin@example.com', 1)");
    $stmt->execute(['admin', $password_hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Se creó el usuario admin (contraseña: Admin123!)\n";
    } else {
        echo "ℹ️ El usuario admin ya existe\n";
    }
    
    echo "\n✅ Proceso completado correctamente. <a href='login.php'>Volver al login</a>";
    
} catch (PDOException $e) {
    echo "\n❌ Error al restablecer bloqueos: " . $e->getMessage();
}

// Limpiar sesión si existe
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

echo "</pre></body></html>";

// Enviar la salida limpia
ob_end_flush();
?>
