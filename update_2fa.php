<?php
// Script para actualizar el código 2FA del usuario admin
require_once 'model/conect.php';

// Configurar encabezados
header('Content-Type: text/plain; charset=utf-8');

try {
    // Conectar a la base de datos
    $con = conectarDB();
    
    // Generar un nuevo código 2FA (123456 para pruebas)
    $nuevo_codigo = '123456';
    $hash_codigo = password_hash($nuevo_codigo, PASSWORD_BCRYPT);
    
    // Establecer tiempo de expiración (10 minutos en el futuro)
    $expiracion = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Actualizar el usuario admin
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
        echo "✅ Código 2FA actualizado exitosamente para el usuario 'admin'\n";
        echo "- Código: $nuevo_codigo\n";
        echo "- Expira: $expiracion\n";
        echo "\nAhora puedes intentar iniciar sesión nuevamente.\n";
    } else {
        echo "⚠️ No se pudo actualizar el código 2FA. El usuario 'admin' podría no existir.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error al actualizar el código 2FA: " . $e->getMessage() . "\n";
}
?>
