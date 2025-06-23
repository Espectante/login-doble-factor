<?php
// Conectar a la base de datos
require_once 'model/conect.php';
$con = conectarDB();

try {
    // Verificar si la columna last_login_attempt ya existe
    $stmt = $con->query("SHOW COLUMNS FROM usuarios LIKE 'last_login_attempt'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // Agregar la columna faltante
        $con->exec("ALTER TABLE usuarios ADD COLUMN last_login_attempt DATETIME DEFAULT NULL AFTER bloqueado_until");
        echo "<p style='color:green;'>✓ Columna 'last_login_attempt' agregada correctamente.</p>";
    } else {
        echo "<p>La columna 'last_login_attempt' ya existe en la tabla usuarios.</p>";
    }
    
    // Verificar la estructura actual de la tabla
    echo "<h3>Estructura actual de la tabla usuarios:</h3>";
    $stmt = $con->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Valor por defecto</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><a href='login.php'>Volver al inicio de sesión</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error al actualizar la tabla: " . htmlspecialchars($e->getMessage()) . "</p>";
}
