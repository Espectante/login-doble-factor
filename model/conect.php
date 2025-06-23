<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Usuario predeterminado de XAMPP
define('DB_PASS', '');          // Contraseña predeterminada de XAMPP (vacía)
define('DB_NAME', 'login_doble_factor');

// Función para conectar a la base de datos
function conectarDB() {
    try {
        // Primero, intentar conectar sin seleccionar la base de datos
        $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        
        // Crear la base de datos si no existe
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Conectar a la base de datos específica
        $pdo->exec("USE `" . DB_NAME . "`");
        
        // Verificar si la tabla de usuarios existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
        if ($stmt->rowCount() == 0) {
            // Crear tabla de usuarios si no existe
            $pdo->exec("CREATE TABLE IF NOT EXISTS `usuarios` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `usuario` varchar(50) NOT NULL,
                `password` varchar(255) NOT NULL,
                `email` varchar(100) NOT NULL,
                `activo` tinyint(1) NOT NULL DEFAULT '1',
                `intentos` int(11) NOT NULL DEFAULT '0',
                `bloqueado_until` datetime DEFAULT NULL,
                `ultimo_acceso` datetime DEFAULT NULL,
                `last_login_attempt` datetime DEFAULT NULL,
                `codigo_2fa` varchar(10) DEFAULT NULL,
                `codigo_2fa_expiracion` datetime DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `usuario` (`usuario`),
                UNIQUE KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Crear tabla de intentos de inicio de sesión
            $pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(100) NOT NULL,
                `ip_address` varchar(45) NOT NULL,
                `user_agent` text,
                `success` tinyint(1) NOT NULL DEFAULT '0',
                `referer` varchar(255) DEFAULT NULL,
                `country` varchar(100) DEFAULT NULL,
                `city` varchar(100) DEFAULT NULL,
                `http_accept` varchar(255) DEFAULT NULL,
                `http_accept_language` varchar(100) DEFAULT NULL,
                `http_accept_encoding` varchar(100) DEFAULT NULL,
                `http_connection` varchar(50) DEFAULT NULL,
                `http_upgrade_insecure_requests` int(11) DEFAULT '0',
                `request_time` datetime NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `ip_address` (`ip_address`),
                KEY `username` (`username`),
                KEY `request_time` (`request_time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Crear tabla de IPs bloqueadas
            $pdo->exec("CREATE TABLE IF NOT EXISTS `blocked_ips` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ip_address` varchar(45) NOT NULL,
                `block_until` datetime NOT NULL,
                `reason` varchar(255) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `ip_address` (`ip_address`),
                KEY `block_until` (`block_until`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            // Asegurarse de que el usuario admin exista (contraseña: Admin123!)
            $admin_password = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password, email, activo) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE password = VALUES(password), activo = 1");
            $stmt->execute(['admin', $admin_password, 'admin@ejemplo.com']);
        }
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Mostrar mensaje de error detallado en desarrollo
        $error_message = "Error de conexión: " . $e->getMessage();
        error_log($error_message);
        
        // Mostrar un mensaje genérico al usuario
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        
        die("<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;'>
                <h2 style='color: #d32f2f;'>Error de conexión</h2>
                <p>No se pudo conectar a la base de datos. Por favor, verifica lo siguiente:</p>
                <ol>
                    <li>Que el servidor MySQL esté en ejecución.</li>
                    <li>Que las credenciales de la base de datos sean correctas.</li>
                    <li>Que el usuario tenga permisos para acceder a la base de datos.</li>
                </ol>
                <p><strong>Detalles del error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <p><a href='setup_database.php' class='btn btn-primary'>Configurar Base de Datos</a></p>
            </div>");
    }
}

// Establecer conexión global
try {
    $con = conectarDB();
} catch (Exception $e) {
    die("<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;'>
            <h2 style='color: #d32f2f;'>Error en la aplicación</h2>
            <p>Ocurrió un error al conectar con la base de datos.</p>
            <p><strong>Detalles del error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p><a href='setup_database.php' class='btn btn-primary'>Configurar Base de Datos</a></p>
        </div>");
}
