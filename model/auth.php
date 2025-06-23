<?php
// Definir el entorno de la aplicación
define('ENVIRONMENT', 'development'); // Cambiar a 'production' en producción

// Configuración de seguridad de la sesión
if (session_status() === PHP_SESSION_NONE) {
    // Configuración de cookies seguras
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 3600, // 1 hora
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Configuración adicional de seguridad
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Iniciar la sesión
    session_start();
    
    // Regenerar ID de sesión para prevenir fijación de sesión
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutos
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Verificar si la sesión ha sido secuestrada
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        session_unset();
        session_destroy();
        die('Sesión inválida.');
    }
    
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        die('Sesión inválida.');
    }
}

// Función para limpiar entradas
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Función para generar token CSRF
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// Función para generar código de doble factor
function generate_2fa_code() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Función para verificar el código de doble factor
function verify_2fa_code($user_code, $stored_code, $expiry_time) {
    // Verificación directa (sin hashing)
    return $stored_code === $user_code;
}

// Función para verificar si una IP está bloqueada
function is_ip_blocked($con, $ip) {
    try {
        // Verificar si la IP está en la tabla de IPs bloqueadas
        $stmt = $con->prepare("SELECT * FROM blocked_ips WHERE ip_address = :ip AND block_until > NOW()");
        $stmt->execute([':ip' => $ip]);
        $blocked = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($blocked) {
            return true;
        }
        
        // Verificar si hay demasiados intentos fallidos recientes
        $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $stmt = $con->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                              WHERE ip = :ip 
                              AND success = 0 
                              AND created_at > :one_hour_ago");
        $stmt->execute([
            ':ip' => $ip,
            ':one_hour_ago' => $one_hour_ago
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si hay más de 5 intentos fallidos en la última hora, bloquear la IP
        if ($result && $result['attempts'] > 5) {
            // Bloquear la IP por 1 hora
            $block_until = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $stmt = $con->prepare("INSERT INTO blocked_ips (ip_address, block_until, reason) 
                                  VALUES (:ip, :block_until, 'Demasiados intentos fallidos')
                                  ON DUPLICATE KEY UPDATE block_until = VALUES(block_until), reason = VALUES(reason)");
            $stmt->execute([
                ':ip' => $ip,
                ':block_until' => $block_until
            ]);
            
            return true;
        }
        
        return false;
        
    } catch (PDOException $e) {
        // En caso de error, registrar pero no bloquear al usuario
        error_log("Error en is_ip_blocked: " . $e->getMessage());
        return false;
    }
}

// Función para autenticar usuario
function authenticate_user($con, $username, $password) {
    try {
        // Buscar usuario en la base de datos
        $stmt = $con->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['status' => 'error', 'message' => 'Usuario o contraseña incorrectos'];
        }
        
        // Verificar si la cuenta está bloqueada
        if ($user['bloqueado_until'] && strtotime($user['bloqueado_until']) > time()) {
            return [
                'status' => 'error', 
                'message' => 'Cuenta bloqueada temporalmente. Intente más tarde.'
            ];
        }
        
        // Verificar contraseña
        if (password_verify($password, $user['password'])) {
            // Verificar si requiere 2FA
            if (!empty($user['codigo_2fa'])) {
                return [
                    'status' => '2fa_required',
                    'user_id' => $user['id'],
                    'test_code' => $user['codigo_2fa'] // Solo para pruebas
                ];
            } else {
                // Iniciar sesión sin 2FA
                session_regenerate_id(true);
                $_SESSION['authenticated'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['usuario'] = $user['usuario'];
                
                // Actualizar último acceso
                $update = $con->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                $update->execute([$user['id']]);
                
                return ['status' => 'success'];
            }
        } else {
            // Contraseña incorrecta
            $intentos = $user['intentos'] + 1;
            $bloqueado_until = null;
            
            // Bloquear después de 3 intentos fallidos por 30 minutos
            if ($intentos >= 3) {
                $bloqueado_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            }
            
            // Actualizar contador de intentos
            $update = $con->prepare("UPDATE usuarios SET intentos = ?, bloqueado_until = ? WHERE id = ?");
            $update->execute([$intentos, $bloqueado_until, $user['id']]);
            
            $mensaje = 'Usuario o contraseña incorrectos';
            if ($intentos >= 3) {
                $mensaje = 'Demasiados intentos fallidos. Su cuenta ha sido bloqueada por 30 minutos.';
            }
            
            return ['status' => 'error', 'message' => $mensaje];
        }
    } catch (PDOException $e) {
        error_log("Error en authenticate_user: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Error en la autenticación'];
    }
}

// Función para registrar intentos de inicio de sesión
function log_login_attempt($con, $username, $success, $ip) {
    try {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $http_accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $http_accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $http_accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        $http_connection = $_SERVER['HTTP_CONNECTION'] ?? '';
        $http_upgrade_insecure_requests = $_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] ?? 0;
        $request_time = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO login_attempts (
                    username, 
                    ip, 
                    user_agent, 
                    success, 
                    referer, 
                    http_accept, 
                    http_accept_language, 
                    http_accept_encoding, 
                    http_connection, 
                    http_upgrade_insecure_requests, 
                    request_time
                ) VALUES (
                    :username, 
                    :ip, 
                    :user_agent, 
                    :success, 
                    :referer, 
                    :http_accept, 
                    :http_accept_language, 
                    :http_accept_encoding, 
                    :http_connection, 
                    :http_upgrade_insecure_requests, 
                    :request_time
                )";
        
        $stmt = $con->prepare($sql);
        $stmt->execute([
            ':username' => $username,
            ':ip' => $ip,
            ':user_agent' => $user_agent,
            ':success' => $success ? 1 : 0,
            ':referer' => $referer,
            ':http_accept' => $http_accept,
            ':http_accept_language' => $http_accept_language,
            ':http_accept_encoding' => $http_accept_encoding,
            ':http_connection' => $http_connection,
            ':http_upgrade_insecure_requests' => $http_upgrade_insecure_requests,
            ':request_time' => $request_time
        ]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Error en log_login_attempt: " . $e->getMessage());
        return false;
    }
}