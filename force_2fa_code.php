<?php
// Script para forzar un código 2FA específico para el usuario admin
require_once 'model/conect.php';

// Configurar encabezados
header('Content-Type: text/plain; charset=utf-8');

// Código 2FA para forzar (123456)
$codigo_forzado = '123456';

// No hashear el código (lo almacenaremos en texto plano para pruebas)
$hash_codigo = $codigo_forzado;

try {
    // Conectar a la base de datos
    $con = conectarDB();
    
    // Establecer tiempo de expiración (60 minutos en el futuro)
    $expiracion = date('Y-m-d H:i:s', strtotime('+60 minutes'));
    
    // Actualizar el usuario admin con el código forzado
    $stmt = $con->prepare("UPDATE usuarios SET 
        codigo_2fa = :codigo,
        codigo_2fa_expiracion = :expiracion,
        bloqueado_until = NULL,
        intentos = 0
        WHERE usuario = 'admin'");
    
    $stmt->execute([
        ':codigo' => $hash_codigo,
        ':expiracion' => $expiracion
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Código 2FA forzado exitosamente para el usuario 'admin'\n";
        echo "- Código: $codigo_forzado (sin hashear)\n";
        echo "- Expira: $expiracion\n";
        
        // Verificar que se actualizó correctamente
        $stmt = $con->query("SELECT codigo_2fa, codigo_2fa_expiracion FROM usuarios WHERE usuario = 'admin'");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\n✅ Verificación en la base de datos:\n";
        echo "- Código almacenado: " . $user['codigo_2fa'] . "\n";
        echo "- Expiración almacenada: " . $user['codigo_2fa_expiracion'] . "\n";
        
        echo "\n🔧 Ahora puedes intentar iniciar sesión con:\n";
        echo "- Usuario: admin\n";
        echo "- Contraseña: Admin123!\n";
        echo "- Código 2FA: $codigo_forzado\n";
    } else {
        echo "⚠️ No se pudo actualizar el código 2FA. El usuario 'admin' podría no existir.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error al forzar el código 2FA: " . $e->getMessage() . "\n";
}
?>
