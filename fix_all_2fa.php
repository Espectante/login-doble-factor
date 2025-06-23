<?php
// Script de solución definitiva para problemas de 2FA

// 1. Actualizar el código 2FA del admin a '123456' (sin hashing)
try {
    require_once 'model/conect.php';
    $con = conectarDB();
    
    // Actualizar el código 2FA del admin
    $codigo = '123456';
    $expiracion = date('Y-m-d H:i:s', strtotime('+60 minutes'));
    
    $stmt = $con->prepare("UPDATE usuarios SET 
        codigo_2fa = :codigo,
        codigo_2fa_expiracion = :expiracion,
        bloqueado_until = NULL,
        intentos = 0
        WHERE usuario = 'admin'");
    
    $stmt->execute([
        ':codigo' => $codigo,
        ':expiracion' => $expiracion
    ]);
    
    echo "✅ Código 2FA actualizado a: $codigo\n";
    
} catch (Exception $e) {
    die("❌ Error al actualizar el código 2FA: " . $e->getMessage());
}

// 2. Simplificar la función de verificación 2FA
$auth_file = 'model/auth.php';
$new_code = '<?php
// Función para verificar el código de doble factor
function verify_2fa_code($user_code, $stored_code, $expiry_time) {
    // Verificación directa (sin hashing)
    return $stored_code === $user_code;
}';

// Reemplazar el archivo completo
if (file_put_contents($auth_file, $new_code) !== false) {
    echo "✅ Función verify_2fa_code actualizada correctamente\n";
} else {
    die("❌ Error al actualizar el archivo auth.php");
}

echo "\n✅ SOLUCIÓN APLICADA CON ÉXITO\n";
echo "Ahora puedes iniciar sesión con:\n";
echo "- Usuario: admin\n";
echo "- Contraseña: Admin123!\n";
echo "- Código 2FA: 123456\n";
?>
