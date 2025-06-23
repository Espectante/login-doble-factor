<?php
// Script de diagnóstico detallado para la verificación 2FA
require_once 'model/conect.php';
require_once 'model/auth.php';

// Configurar encabezados
header('Content-Type: text/plain; charset=utf-8');

// Función para formatear la salida
echo "=== DIAGNÓSTICO DE VERIFICACIÓN 2FA ===\n\n";

try {
    // 1. Conectar a la base de datos
    $con = conectarDB();
    echo "✅ Conexión a la base de datos exitosa\n\n";
    
    // 2. Obtener datos del usuario admin
    $stmt = $con->query("SELECT id, usuario, codigo_2fa, codigo_2fa_expiracion FROM usuarios WHERE usuario = 'admin'");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        throw new Exception("No se encontró el usuario 'admin'");
    }
    
    echo "🔍 Datos del usuario 'admin':\n";
    echo "- ID: {$admin['id']}\n";
    echo "- Código 2FA almacenado: " . ($admin['codigo_2fa'] ? substr($admin['codigo_2fa'], 0, 10) . '...' : 'NINGUNO') . "\n";
    echo "- Expiración: " . ($admin['codigo_2fa_expiracion'] ?: 'NO CONFIGURADA') . "\n\n";
    
    // 3. Verificar si el código está expirado
    $current_time = time();
    $expiry_time = strtotime($admin['codigo_2fa_expiracion']);
    
    if ($expiry_time === false) {
        echo "⚠️ No se pudo determinar la hora de expiración\n";
    } else {
        $time_remaining = $expiry_time - $current_time;
        $expired = $time_remaining < 0;
        
        echo "🕒 Estado de expiración:";
        echo "\n- Hora actual: " . date('Y-m-d H:i:s', $current_time);
        echo "\n- Hora de expiración: " . date('Y-m-d H:i:s', $expiry_time);
        echo "\n- Tiempo restante: " . ($expired ? 'EXPIRADO' : gmdate("H:i:s", $time_remaining)) . "\n\n";
    }
    
    // 4. Probar verificación manual
    echo "🔍 Probando verificación manual...\n";
    
    // Código de prueba (debería ser 123456 según el script anterior)
    $test_code = '123456';
    echo "- Código de prueba: $test_code\n";
    
    // Verificar si el código está vacío
    if (empty($admin['codigo_2fa'])) {
        echo "❌ No hay código 2FA configurado para el usuario\n";
    } else {
        // Verificar coincidencia directa
        $direct_match = ($admin['codigo_2fa'] === $test_code);
        echo "- Coincidencia directa: " . ($direct_match ? '✅ SÍ' : '❌ NO') . "\n";
        
        // Verificar con password_verify
        $password_verify = password_verify($test_code, $admin['codigo_2fa']);
        echo "- Verificación con password_verify: " . ($password_verify ? '✅ VÁLIDO' : '❌ INVÁLIDO') . "\n";
        
        // Mostrar información del hash
        $hash_info = password_get_info($admin['codigo_2fa']);
        echo "- Información del hash: " . json_encode($hash_info) . "\n";
        
        // Si ambos métodos fallan, mostrar el hash almacenado para depuración
        if (!$direct_match && !$password_verify) {
            echo "\n⚠️ Ambos métodos de verificación fallaron. Hash almacenado: " . $admin['codigo_2fa'] . "\n";
        }
    }
    
    // 5. Verificar la función verify_2fa_code
    echo "\n🔍 Probando función verify_2fa_code...\n";
    $result = verify_2fa_code($test_code, $admin['codigo_2fa'], $admin['codigo_2fa_expiracion']);
    
    if ($result === true) {
        echo "✅ La función verify_2fa_code devolvió: VÁLIDO\n";
    } else {
        echo "❌ La función verify_2fa_code falló: " . ($result === false ? 'Error desconocido' : $result) . "\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    if (isset($e->xdebug_message)) {
        echo "\n" . $e->xdebug_message . "\n";
    }
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
?>
