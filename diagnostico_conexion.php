<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnóstico de Conexión</h2>";

// 1. Verificar si PDO está habilitado
echo "<h3>1. Verificación de PDO</h3>";
if (extension_loaded('pdo')) {
    echo "<p style='color:green;'>✓ PDO está habilitado</p>";
    
    // Verificar controladores PDO disponibles
    echo "<p>Controladores PDO disponibles: " . implode(', ', PDO::getAvailableDrivers()) . "</p>";
    
    if (!in_array('mysql', PDO::getAvailableDrivers())) {
        echo "<p style='color:red;'>✗ El controlador PDO para MySQL no está instalado</p>";
    }
} else {
    echo "<p style='color:red;'>✗ PDO no está habilitado en PHP</p>";
}

// 2. Probar conexión directa a MySQL
echo "<h3>2. Prueba de conexión a MySQL</h3>";
try {
    $pdo = new PDO("mysql:host=localhost", 'root', '');
    echo "<p style='color:green;'>✓ Conexión exitosa al servidor MySQL</p>";
    
    // Verificar si la base de datos existe
    $stmt = $pdo->query("SHOW DATABASES LIKE 'login_doble_factor'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>✓ La base de datos 'login_doble_factor' existe</p>";
        
        // Verificar tablas
        $pdo->exec("USE login_doble_factor");
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Tablas encontradas: " . implode(', ', $tables) . "</p>";
        
        // Verificar usuario admin
        $stmt = $pdo->query("SELECT * FROM usuarios WHERE usuario = 'admin'");
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "<p style='color:green;'>✓ Usuario 'admin' encontrado</p>";
            echo "<pre>" . print_r($admin, true) . "</pre>";
        } else {
            echo "<p style='color:orange;'>⚠ Usuario 'admin' no encontrado</p>";
        }
    } else {
        echo "<p style='color:red;'>✗ La base de datos 'login_doble_factor' no existe</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>✗ Error de conexión a MySQL: " . $e->getMessage() . "</p>";
}

// 3. Probar la función conectarDB
echo "<h3>3. Prueba de la función conectarDB</h3>";
require_once 'model/conect.php';

try {
    $con = conectarDB();
    echo "<p style='color:green;'>✓ Función conectarDB ejecutada correctamente</p>";
    
    // Verificar si hay usuarios
    $stmt = $con->query("SELECT COUNT(*) as total FROM usuarios");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Total de usuarios en la base de datos: $count</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Error en conectarDB: " . $e->getMessage() . "</p>";
}

// 4. Verificar sesión
echo "<h3>4. Verificación de sesión</h3>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color:green;'>✓ Sesión iniciada correctamente</p>";
    echo "<p>ID de sesión: " . session_id() . "</p>";
    
    // Mostrar datos de sesión si existen
    if (!empty($_SESSION)) {
        echo "<p>Datos de sesión:</p>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    } else {
        echo "<p>No hay datos en la sesión actual</p>";
    }
} else {
    echo "<p style='color:red;'>✗ No se pudo iniciar la sesión</p>";
}

// 5. Verificar archivos importantes
echo "<h3>5. Verificación de archivos</h3>";
$files = [
    'model/conect.php' => 'Archivo de conexión',
    'model/auth.php' => 'Archivo de autenticación',
    'model/login_control.php' => 'Controlador de login',
    'login.php' => 'Página de login'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "<p style='color:green;'>✓ $description ($file) existe (permisos: $perms)</p>";
    } else {
        echo "<p style='color:red;'>✗ $description ($file) no existe</p>";
    }
}

// 6. Verificar permisos de directorios
echo "<h3>6. Verificación de permisos de directorios</h3>";
$dirs = [
    'model' => 'Directorio de modelos',
    'assets' => 'Directorio de assets',
    'uploads' => 'Directorio de subidas'
];

foreach ($dirs as $dir => $description) {
    if (is_dir($dir)) {
        $writable = is_writable($dir) ? 'escribible' : 'no escribible';
        $readable = is_readable($dir) ? 'leíble' : 'no leíble';
        echo "<p>$description ($dir): $readable, $writable</p>";
    } else {
        echo "<p style='color:orange;'>⚠ $description ($dir) no existe</p>";
    }
}

// 7. Información del servidor
echo "<h3>7. Información del servidor</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Sistema operativo: " . php_uname() . "</p>";
echo "<p>Servidor web: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// 8. Verificar configuración de PHP
echo "<h3>8. Configuración de PHP</h3>";
$settings = [
    'display_errors',
    'error_reporting',
    'session.auto_start',
    'session.save_path',
    'upload_max_filesize',
    'post_max_size',
    'memory_limit',
    'max_execution_time'
];

foreach ($settings as $setting) {
    echo "<p>$setting: " . ini_get($setting) . "</p>";
}
?>

<h3>Instrucciones</h3>
<ol>
    <li>Ejecuta este script y comparte la salida para identificar el problema.</li>
    <li>Si ves algún error en rojo, es probable que sea la causa del problema.</li>
    <li>Verifica que el servicio de MySQL esté en ejecución en XAMPP.</li>
    <li>Comprueba que las credenciales en model/conect.php sean correctas.</li>
</ol>

<p>Si el problema persiste, por favor comparte la salida completa de este diagnóstico.</p>
