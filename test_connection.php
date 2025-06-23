<?php
// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Prueba de conexión a la base de datos</h2>";

// Intentar conectar directamente
$host = 'localhost';
$dbname = 'login_doble_factor';
$user = 'root';
$pass = '';

try {
    // Intento de conexión sin base de datos primero
    $dsn = "mysql:host=$host;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    echo "<p>Intentando conectar a MySQL...</p>";
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color:green;'>✓ Conexión exitosa a MySQL</p>";
    
    // Verificar si la base de datos existe
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>✓ La base de datos '$dbname' existe</p>";
        
        // Conectar a la base de datos específica
        $pdo->exec("USE `$dbname`");
        
        // Verificar tabla de usuarios
        $tables = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetchAll();
        if (count($tables) > 0) {
            echo "<p style='color:green;'>✓ La tabla 'usuarios' existe</p>";
            
            // Contar usuarios
            $count = $pdo->query("SELECT COUNT(*) as total FROM usuarios")->fetch()['total'];
            echo "<p>Total de usuarios en la base de datos: $count</p>";
            
            // Mostrar usuarios (solo los primeros 5 por privacidad)
            $users = $pdo->query("SELECT id, usuario, email, activo FROM usuarios LIMIT 5")->fetchAll();
            echo "<h3>Usuarios (máx 5):</h3>";
            echo "<pre>";
            print_r($users);
            echo "</pre>";
        } else {
            echo "<p style='color:red;'>✗ La tabla 'usuarios' NO existe</p>";
        }
    } else {
        echo "<p style='color:red;'>✗ La base de datos '$dbname' NO existe</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>✗ Error de conexión: " . $e->getMessage() . "</p>";
    
    // Intentar conectar sin especificar la base de datos
    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        echo "<p style='color:green;'>✓ Conexión exitosa a MySQL (sin base de datos)</p>";
        
        // Intentar crear la base de datos
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<p style='color:green;'>✓ Base de datos '$dbname' creada exitosamente</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red;'>✗ No se pudo crear la base de datos: " . $e->getMessage() . "</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>✗ No se pudo conectar a MySQL: " . $e->getMessage() . "</p>";
        echo "<p>Por favor verifica que:</p>";
        echo "<ol>";
        echo "<li>El servicio MySQL de XAMPP esté en ejecución</li>";
        echo "<li>El usuario 'root' tenga los permisos adecuados</li>";
        echo "<li>La contraseña sea correcta (está configurada como vacía por defecto)</li>";
        echo "</ol>";
    }
}
?>
