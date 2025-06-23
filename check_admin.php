<?php
// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'model/conect.php';

// Verificar conexión
if (!isset($con)) {
    die("Error: No se pudo establecer conexión con la base de datos");
}

echo "<h2>Verificando cuenta de administrador</h2>";

try {
    // Obtener datos del admin
    $stmt = $con->prepare("SELECT * FROM usuarios WHERE usuario = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<h3>Datos del usuario admin:</h3>";
        echo "<pre>";
        print_r($admin);
        echo "</pre>";
        
        // Verificar contraseña
        $password = 'Admin123!';
        $isPasswordCorrect = password_verify($password, $admin['password']);
        
        echo "<h3>Verificación de contraseña:</h3>";
        echo "Contraseña 'Admin123!' es " . ($isPasswordCorrect ? "<span style='color:green;'>CORRECTA</span>" : "<span style='color:red;'>INCORRECTA</span>") . "<br>";
        
        // Verificar estado de la cuenta
        $isActive = $admin['activo'] == 1;
        $isBlocked = $admin['bloqueado_until'] && strtotime($admin['bloqueado_until']) > time();
        
        echo "<h3>Estado de la cuenta:</h3>";
        echo "Activa: " . ($isActive ? "Sí" : "No") . "<br>";
        echo "Bloqueada: " . ($isBlocked ? "Sí (hasta: " . $admin['bloqueado_until'] . ")" : "No") . "<br>";
        echo "Último acceso: " . ($admin['ultimo_acceso'] ?: 'Nunca') . "<br>";
        
        // Verificar configuración 2FA
        echo "<h3>Configuración 2FA:</h3>";
        echo "Código 2FA: " . ($admin['codigo_2fa'] ?: 'No establecido') . "<br>";
        echo "Expiración 2FA: " . ($admin['codigo_2fa_expiracion'] ?: 'No establecida') . "<br>";
        
        // Verificar intentos fallidos
        echo "<h3>Intentos fallidos:</h3>";
        echo "Número de intentos: " . ($admin['intentos'] ?: '0') . "<br>";
        
        // Verificar tablas necesarias
        $tables = ['login_attempts', 'sessions'];
        echo "<h3>Tablas del sistema:</h3>";
        foreach ($tables as $table) {
            $stmt = $con->query("SHOW TABLES LIKE '$table'");
            echo "Tabla '$table': " . ($stmt->rowCount() > 0 ? "<span style='color:green;'>Existe</span>" : "<span style='color:red;'>No existe</span>") . "<br>";
        }
        
    } else {
        echo "<p style='color:red;'>No se encontró el usuario 'admin' en la base de datos.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error en la consulta: " . $e->getMessage() . "</p>";
}

// Verificar si hay sesiones activas
echo "<h3>Sesión actual:</h3>";
print_r($_SESSION);
?>
