<?php
// Habilitar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir archivo de conexión
require_once 'model/conect.php';

// Función para limpiar la salida
function clean_output($data) {
    if (is_array($data) || is_object($data)) {
        return '<pre>' . htmlspecialchars(print_r($data, true), ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Función para generar un código 2FA
function generate_2fa_code($length = 6) {
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

echo "<h1>Prueba Directa de Guardado 2FA</h1>";

try {
    // 1. Conectar a la base de datos
    $con = conectarDB();
    if (!$con) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    echo "<p style='color:green'>✓ Conexión a la base de datos exitosa</p>";
    
    // 2. Obtener el usuario admin
    $stmt = $con->prepare("SELECT id, usuario FROM usuarios WHERE usuario = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        throw new Exception("El usuario 'admin' no existe en la base de datos");
    }
    
    echo "<p>Usuario: " . htmlspecialchars($admin['usuario']) . " (ID: " . $admin['id'] . ")</p>";
    
    // 3. Generar código 2FA
    $two_factor_code = generate_2fa_code();
    $expiry_time = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    $hashed_code = password_hash($two_factor_code, PASSWORD_DEFAULT);
    
    echo "<h3>Datos generados:</h3>";
    echo "<p>Código: $two_factor_code</p>";
    echo "<p>Hash: " . htmlspecialchars($hashed_code) . "</p>";
    echo "<p>Expira: $expiry_time</p>";
    
    // 4. Actualizar directamente en la base de datos
    echo "<h3>Actualizando base de datos...</h3>";
    
    // Primero, limpiar cualquier código existente
    $con->exec("UPDATE usuarios SET codigo_2fa = NULL, codigo_2fa_expiracion = NULL WHERE id = " . $admin['id']);
    
    // Preparar la consulta SQL
    $sql = "UPDATE usuarios SET 
            codigo_2fa = :codigo, 
            codigo_2fa_expiracion = :expiracion,
            last_login_attempt = NOW(),
            ultimo_acceso = NOW()
            WHERE id = :id";
    
    // Preparar la sentencia
    $stmt = $con->prepare($sql);
    
    // Ejecutar con los parámetros
    $params = [
        ':codigo' => $hashed_code,
        ':expiracion' => $expiry_time,
        ':id' => $admin['id']
    ];
    
    echo "<h4>Parámetros de la consulta:</h4>";
    echo clean_output($params);
    
    $result = $stmt->execute($params);
    
    // Verificar si hubo error en la ejecución
    if ($result === false) {
        $error = $stmt->errorInfo();
        throw new Exception("Error al ejecutar la consulta: " . ($error[2] ?? 'Error desconocido'));
    }
    
    // Verificar si se actualizó alguna fila
    $rows_affected = $stmt->rowCount();
    echo "<p>Filas afectadas: $rows_affected</p>";
    
    // Verificar que los datos se guardaron
    $checkStmt = $con->prepare("SELECT codigo_2fa, codigo_2fa_expiracion FROM usuarios WHERE id = :id");
    $checkStmt->execute([':id' => $admin['id']]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Datos en la base de datos después de actualizar:</h3>";
    echo clean_output($result);
    
    // Verificar si el hash coincide
    if (!empty($result['codigo_2fa'])) {
        $hash_match = password_verify($two_factor_code, $result['codigo_2fa']);
        echo "<p>Verificación del hash: " . ($hash_match ? '✓ Coincide' : '✗ No coincide') . "</p>";
        
        // Mostrar información del hash
        $hash_info = password_get_info($result['codigo_2fa']);
        echo "<h4>Información del hash guardado:</h4>";
        echo clean_output($hash_info);
    } else {
        echo "<p style='color:red'>No se encontró código 2FA en la base de datos</p>";
        
        // Verificar si hay algún problema con los permisos
        try {
            $testStmt = $con->prepare("UPDATE usuarios SET last_login_attempt = NOW() WHERE id = :id");
            $testResult = $testStmt->execute([':id' => $admin['id']]);
            echo "<p>Prueba de actualización simple: " . ($testResult ? '✓ Éxito' : '✗ Falló') . "</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>Error en prueba de actualización: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color:red; padding:10px; border:1px solid red; margin:10px 0;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h4>Stack trace:</h4>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>

<hr>
<p><a href="debug_2fa.php">Volver a la depuración 2FA</a> | 
<a href="test_2fa_update.php">Otra prueba de actualización</a></p>
