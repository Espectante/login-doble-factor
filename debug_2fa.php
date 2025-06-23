<?php
// Script de diagnóstico para el sistema de autenticación 2FA
require_once 'model/conect.php';
require_once 'model/auth.php';

// Configurar encabezados para JSON
header('Content-Type: application/json');

// Función para formatear la salida
echo "<pre>\n";

// 1. Verificar conexión a la base de datos
try {
    $con = conectarDB();
    echo "✅ Conexión a la base de datos exitosa\n\n";
    
    // 2. Verificar tabla de usuarios
    $stmt = $con->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabla 'usuarios' encontrada\n";
        
        // 3. Verificar usuario administrador
        $stmt = $con->query("SELECT id, usuario, email, activo, bloqueado_until, codigo_2fa, codigo_2fa_expiracion FROM usuarios WHERE usuario = 'admin'");
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "✅ Usuario 'admin' encontrado\n";
            echo "   - ID: {$admin['id']}\n";
            echo "   - Email: {$admin['email']}\n";
            echo "   - Activo: " . ($admin['activo'] ? 'Sí' : 'No') . "\n";
            echo "   - Bloqueado hasta: " . ($admin['bloqueado_until'] ?: 'No bloqueado') . "\n";
            echo "   - Código 2FA: " . ($admin['codigo_2fa'] ? 'Configurado' : 'No configurado') . "\n";
            
            if ($admin['codigo_2fa_expiracion']) {
                $expira = strtotime($admin['codigo_2fa_expiracion']);
                $ahora = time();
                $restante = $expira - $ahora;
                $minutos = floor($restante / 60);
                $segundos = $restante % 60;
                echo "   - Expira en: " . ($restante > 0 ? "$minutos minutos y $segundos segundos" : "Expirado") . "\n";
            }
            
            // 4. Probar verificación 2FA
            if ($admin['codigo_2fa']) {
                echo "\n🔍 Probando verificación 2FA...\n";
                
                // Código de prueba (123456)
                $test_code = '123456';
                echo "   - Código de prueba: $test_code\n";
                
                // Llamar directamente a la función de verificación
                $result = verify_2fa_code($test_code, $admin['codigo_2fa'], $admin['codigo_2fa_expiracion']);
                
                if ($result === true) {
                    echo "   ✅ Código 2FA VÁLIDO\n";
                } else {
                    echo "   ❌ Código 2FA INVÁLIDO\n";
                    echo "   - Razón: " . ($result === false ? 'Error desconocido' : $result) . "\n";
                    
                    // Mostrar información de depuración
                    echo "\n🔍 Información de depuración:\n";
                    echo "   - Código almacenado: " . substr($admin['codigo_2fa'], 0, 10) . (strlen($admin['codigo_2fa']) > 10 ? '...' : '') . "\n";
                    echo "   - Longitud: " . strlen($admin['codigo_2fa']) . " caracteres\n";
                    echo "   - ¿Es un hash? " . (password_get_info($admin['codigo_2fa'])['algoName'] !== 'unknown' ? 'Sí' : 'No') . "\n";
                }
            }
            
        } else {
            echo "⚠️ Usuario 'admin' no encontrado\n";
        }
        
    } else {
        echo "❌ Error: No se encontró la tabla 'usuarios'\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    if (isset($e->xdebug_message)) {
        echo "\n" . $e->xdebug_message . "\n";
    }
}

// 5. Verificar directorio de logs
$log_dir = __DIR__ . '/model/2fa_logs';
if (is_dir($log_dir)) {
    echo "\n📂 Directorio de logs: $log_dir\n";
    echo "   - Permisos: " . substr(sprintf('%o', fileperms($log_dir)), -4) . "\n";
    
    // Mostrar archivos de log
    $logs = glob("$log_dir/2fa_verify_*.log");
    if (count($logs) > 0) {
        echo "   - Archivos de log encontrados:\n";
        foreach ($logs as $log) {
            $size = filesize($log);
            echo "     - " . basename($log) . " (" . number_format($size / 1024, 2) . " KB)\n";
        }
        
        // Mostrar las últimas 10 líneas del log más reciente
        if (count($logs) > 0) {
            $latest_log = $logs[count($logs) - 1];
            echo "\n📝 Últimas líneas de " . basename($latest_log) . ":\n";
            $lines = file($latest_log);
            $last_lines = array_slice($lines, -10);
            echo implode("", $last_lines);
        }
    } else {
        echo "   - No se encontraron archivos de log\n";
    }
} else {
    echo "\n⚠️ El directorio de logs no existe: $log_dir\n";
}

echo "\n✅ Diagnóstico completado\n";
?>
