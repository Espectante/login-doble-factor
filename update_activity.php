<?php
session_start();
header('Content-Type: application/json');

// Verificar el token CSRF si se proporciona
if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token']) && 
    hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    
    // Actualizar el tiempo de última actividad
    $_SESSION['last_activity'] = time();
    
    echo json_encode(['status' => 'success']);
} else {
    // Token CSRF inválido o no proporcionado
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido']);
}
