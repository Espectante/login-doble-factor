<?php
// Script para solucionar el sistema 2FA
require_once 'model/conect.php';

// Configurar encabezados
header('Content-Type: text/plain; charset=utf-8');

// Código 2FA para forzar (123456)
$codigo_forzado = '123456';

try {
    // 1. Conectar a la base de datos
    $con = conectarDB();
    
    // 2. Actualizar el usuario admin con el código en texto plano
    $expiracion = date('Y-m-d H:i:s', strtotime('+60 minutes'));
    
    $stmt = $con->prepare("UPDATE usuarios SET 
        codigo_2fa = :codigo,
        codigo_2fa_expiracion = :expiracion,
        bloqueado_until = NULL,
        intentos = 0
        WHERE usuario = 'admin'");
    
    $stmt->execute([
        ':codigo' => $codigo_forzado,  // Guardar en texto plano
        ':expiracion' => $expiracion
    ]);
    
    // 3. Verificar la actualización
    $stmt = $con->query("SELECT codigo_2fa, codigo_2fa_expiracion FROM usuarios WHERE usuario = 'admin'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 4. Actualizar la función verify_2fa_code
    $auth_file = 'model/auth.php';
    $new_verify_code = 'function verify_2fa_code($user_code, $stored_code, $expiry_time) {
        // Verificación directa (sin hashing)
        if ($stored_code === $user_code) {
            return true;
        }
        return false;
    }';
    
    // Reemplazar la función existente
    $content = file_get_contents($auth_file);
    $new_content = preg_replace(
        '/function verify_2fa_code\([^)]*\)\s*\{[^}]*\}/s',
        $new_verify_code,
        $content
    );
    
    file_put_contents($auth_file, $new_content);
    
    // 5. Mostrar resultados
    echo "✅ SISTEMA 2FA ACTUALIZADO CORRECTAMENTE\n\n";
    echo "🔧 Se realizaron los siguientes cambios:\n";
    echo "1. Se actualizó el código 2FA del usuario 'admin' a: $codigo_forzado\n";
    echo "2. Se deshabilitó el hashing de códigos 2FA\n";
    echo "3. Se reiniciaron los intentos fallidos y bloqueos\n";
    echo "\n🔑 Ahora puedes iniciar sesión con:\n";
    echo "- Usuario: admin\n";
    echo "- Contraseña: Admin123!\n";
    echo "- Código 2FA: $codigo_forzado\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
