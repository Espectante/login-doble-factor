<?php
// Habilitar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once 'model/conect.php';
require_once 'model/auth.php';

// Función para limpiar la salida
function clean_output($data) {
    return htmlspecialchars(print_r($data, true), ENT_QUOTES, 'UTF-8');
}

echo "<h2>Diagnóstico del Sistema de Autenticación</h2>";

try {
    // 1. Conectar a la base de datos
    echo "<h3>1. Probando conexión a la base de datos...</h3>";
    $con = conectarDB();
    
    if ($con) {
        echo "<p style='color:green'>✓ Conexión exitosa a la base de datos</p>";
        
        // 2. Verificar tabla de usuarios
        echo "<h3>2. Verificando tabla de usuarios...</h3>";
        $stmt = $con->query("SHOW TABLES LIKE 'usuarios'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green'>✓ La tabla 'usuarios' existe</p>";
            
            // 3. Verificar estructura de la tabla
            $stmt = $con->query("DESCRIBE usuarios");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>Columnas en la tabla usuarios: " . implode(', ', $columns) . "</p>";
            
            // 4. Verificar usuario admin
            echo "<h3>3. Verificando usuario 'admin'...</h3>";
            $stmt = $con->prepare("SELECT * FROM usuarios WHERE usuario = 'admin'");
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                echo "<p style='color:green'>✓ Usuario 'admin' encontrado</p>";
                echo "<pre>" . clean_output($admin) . "</pre>";
                
                // 5. Verificar contraseña
                $password = 'Admin123!';
                $password_hashed = $admin['password'];
                $password_match = password_verify($password, $password_hashed);
                
                echo "<h3>4. Verificando contraseña...</h3>";
                if ($password_match) {
                    echo "<p style='color:green'>✓ La contraseña es correcta</p>";
                    
                    // 6. Verificar función authenticate_user
                    echo "<h3>5. Probando autenticación directa...</h3>";
                    
                    // Limpiar bloqueos temporales
                    $con->exec("UPDATE usuarios SET intentos = 0, bloqueado_until = NULL, last_login_attempt = NULL WHERE usuario = 'admin'");
                    
                    // Llamar a la función de autenticación
                    $result = authenticate_user($con, 'admin', 'Admin123!');
                    
                    echo "<h4>Resultado de authenticate_user:</h4>";
                    echo "<pre>" . clean_output($result) . "</pre>";
                    
                    // Mostrar estado de la sesión
                    echo "<h4>Estado de la sesión:</h4>";
                    echo "<pre>" . clean_output($_SESSION) . "</pre>";
                    
                    // Verificar si hay un código 2FA generado
                    if (isset($result['test_code'])) {
                        echo "<h4>Prueba de código 2FA:</h4>";
                        echo "<p>Usa este código para probar: <strong>" . htmlspecialchars($result['test_code']) . "</strong></p>";
                    }
                } else {
                    echo "<p style='color:red'>✗ La contraseña no coincide</p>";
                    echo "<p>Hash almacenado: " . htmlspecialchars($password_hashed) . "</p>";
                    
                    // Generar un nuevo hash para comparar
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    echo "<p>Nuevo hash generado: " . htmlspecialchars($new_hash) . "</p>";
                }
            } else {
                echo "<p style='color:red'>✗ El usuario 'admin' no fue encontrado</p>";
            }
        } else {
            echo "<p style='color:red'>✗ La tabla 'usuarios' no existe</p>";
        }
    } else {
        echo "<p style='color:red'>✗ No se pudo conectar a la base de datos</p>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color:red'>Error durante el diagnóstico:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h3>6. Información del servidor:</h3>";
echo "<pre>PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "PDO Available: " . (extension_loaded('pdo') ? 'Sí' : 'No') . "\n";
echo "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'Sí' : 'No') . "</pre>";

echo "<p><a href='login.php'>Volver al inicio de sesión</a></p>";
?>
