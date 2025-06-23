<?php
// Configuración de la base de datos
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'login_doble_factor';

// Mensajes
$messages = [];

// Función para ejecutar consultas SQL desde un archivo
function executeSQLFromFile($pdo, $file) {
    // Leer el archivo SQL
    $sql = file_get_contents($file);
    
    // Eliminar comentarios (/* */ y -- )
    $sql = preg_replace('/\/\*[\s\S]*?\*\//', '', $sql);
    $sql = preg_replace('/--.*$/m', '', $sql);
    
    // Dividir en consultas individuales
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = true;
    $executed = 0;
    $errors = [];
    
    // Ejecutar cada consulta
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        try {
            $pdo->exec($query);
            $executed++;
        } catch (PDOException $e) {
            $errors[] = "Error en la consulta: " . $e->getMessage();
            $success = false;
        }
    }
    
    return [
        'success' => $success,
        'executed' => $executed,
        'errors' => $errors
    ];
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Conectar a MySQL (sin seleccionar base de datos)
        $pdo = new PDO("mysql:host={$db_host}", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        
        // Crear la base de datos si no existe
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db_name}`");
        
        // Ejecutar el script SQL
        $result = executeSQLFromFile($pdo, __DIR__ . '/database.sql');
        
        if ($result['success']) {
            $messages[] = [
                'type' => 'success',
                'message' => "Base de datos creada exitosamente. Se ejecutaron {$result['executed']} consultas."
            ];
            
            // Verificar si el usuario de prueba se creó correctamente
            $stmt = $pdo->query("SELECT * FROM `usuarios` WHERE `usuario` = 'admin'");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $messages[] = [
                    'type' => 'info',
                    'message' => "Usuario de prueba creado:<br>Usuario: admin<br>Contraseña: Admin123!"
                ];
            }
        } else {
            $messages[] = [
                'type' => 'danger',
                'message' => "Se encontraron errores al ejecutar el script SQL."
            ];
            foreach ($result['errors'] as $error) {
                $messages[] = [
                    'type' => 'danger',
                    'message' => $error
                ];
            }
        }
        
        // Verificar las tablas creadas
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (count($tables) > 0) {
            $messages[] = [
                'type' => 'success',
                'message' => "Tablas creadas: " . implode(', ', $tables)
            ];
        }
        
    } catch (PDOException $e) {
        $messages[] = [
            'type' => 'danger',
            'message' => "Error de conexión: " . $e->getMessage()
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de la Base de Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .card { max-width: 800px; margin: 0 auto; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2 class="h4 mb-0">Configuración de la Base de Datos</h2>
            </div>
            <div class="card-body">
                <?php foreach ($messages as $message): ?>
                    <div class="alert alert-<?php echo $message['type']; ?>" role="alert">
                        <?php echo $message['message']; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($messages) || $messages[0]['type'] !== 'success'): ?>
                    <p>Este asistente configurará la base de datos necesaria para el sistema de autenticación.</p>
                    <p>Por favor, asegúrese de que el servidor MySQL esté en ejecución y que las credenciales sean correctas.</p>
                    
                    <form method="post" class="mt-4">
                        <div class="mb-3">
                            <label for="host" class="form-label">Servidor MySQL:</label>
                            <input type="text" class="form-control" id="host" name="host" value="<?php echo htmlspecialchars($db_host); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="user" class="form-label">Usuario MySQL:</label>
                            <input type="text" class="form-control" id="user" name="user" value="<?php echo htmlspecialchars($db_user); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="pass" class="form-label">Contraseña MySQL:</label>
                            <input type="password" class="form-control" id="pass" name="pass" value="<?php echo htmlspecialchars($db_pass); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="dbname" class="form-label">Nombre de la base de datos:</label>
                            <input type="text" class="form-control" id="dbname" name="dbname" value="<?php echo htmlspecialchars($db_name); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Configurar Base de Datos</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">
                        <h4>¡Configuración completada con éxito!</h4>
                        <p>La base de datos y las tablas se han creado correctamente.</p>
                        <p>Puedes acceder al <a href="login.php" class="alert-link">sistema de inicio de sesión</a>.</p>
                    </div>
                    
                    <div class="alert alert-info">
                        <h5>Datos de acceso de prueba:</h5>
                        <p><strong>Usuario:</strong> admin</p>
                        <p><strong>Contraseña:</strong> Admin123!</p>
                    </div>
                    
                    <div class="alert alert-warning">
                        <h5>Importante:</h5>
                        <p>Por seguridad, elimina o renombra este archivo (setup_database.php) después de completar la instalación.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted">
                Sistema de Autenticación - &copy; <?php echo date('Y'); ?>
            </div>
        </div>
        
        <?php if (!empty($tables)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="h5 mb-0">Estructura de la Base de Datos</h3>
            </div>
            <div class="card-body">
                <h4>Tablas creadas:</h4>
                <ul>
                    <?php foreach ($tables as $table): ?>
                        <li><?php echo htmlspecialchars($table); ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <h4 class="mt-4">Estructura de la tabla 'usuarios':</h4>
                <pre><?php 
                    $stmt = $pdo->query("SHOW CREATE TABLE `usuarios`");
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo htmlspecialchars($row['Create Table']);
                ?></pre>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
