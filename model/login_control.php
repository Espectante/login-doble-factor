<?php
// Habilitar todos los errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Función para registrar mensajes de depuración
function log_debug($message, $data = null) {
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    if ($data !== null) {
        $log .= 'Datos: ' . print_r($data, true) . "\n";
    }
    file_put_contents('login_debug.log', $log, FILE_APPEND);
}

// Iniciar el registro de depuración
log_debug('=== INICIO DE SOLICITUD ===');
log_debug('Método: ' . $_SERVER['REQUEST_METHOD']);
log_debug('Datos POST:', $_POST);

require_once 'conect.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Obtener la IP del cliente
$ip = $_SERVER['REMOTE_ADDR'];
log_debug('IP del cliente: ' . $ip);

try {
    // Verificar si la IP está bloqueada
    if (is_ip_blocked($con, $ip)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Demasiados intentos fallidos. Su IP ha sido bloqueada temporalmente.'
        ]);
        exit;
    }

    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
        exit;
    }

    // Obtener datos del POST o del cuerpo JSON
    $data = $_POST; // Usar $_POST por defecto
    log_debug('Datos recibidos (POST):', $data);

    // Si no hay datos en $_POST, intentar decodificar JSON del cuerpo
    if (empty($data)) {
        $rawInput = file_get_contents('php://input');
        log_debug('Cuerpo RAW recibido:', $rawInput);
        $jsonData = json_decode($rawInput, true);
        if (is_array($jsonData)) {
            $data = $jsonData;
            log_debug('Datos decodificados desde JSON:', $data);
        }
    }
    
    // Verificar si hay datos vacíos
    if (empty($data)) {
        log_debug('Error: No se recibieron datos POST');
        echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos del formulario']);
        exit;
    }

    // Verificar si es una solicitud de inicio de sesión o verificación de 2FA
    if (isset($data['action']) && $data['action'] === 'verify_2fa') {
        log_debug('Solicitud de verificación 2FA recibida');
        error_log("=== INICIO VERIFICACIÓN 2FA ===");
        error_log("Datos recibidos: " . print_r($data, true));
        
        // Verificar código de doble factor
        if (!isset($data['user_id']) || !isset($data['code'])) {
            $error_msg = 'Datos incompletos. User_id: ' . (isset($data['user_id']) ? 'presente' : 'ausente') . 
                        ', Código: ' . (isset($data['code']) ? 'presente' : 'ausente');
            error_log("Error: $error_msg");
            echo json_encode(['status' => 'error', 'message' => $error_msg]);
            exit;
        }
        
        error_log("Llamando a verify_two_factor con user_id: {$data['user_id']}");
        $result = verify_two_factor($data['user_id'], $data['code']);
        error_log("Resultado de verify_two_factor: " . print_r($result, true));
        
        if ($result['status'] === 'success') {
            // Iniciar sesión si no está iniciada
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            
            // Establecer variables de sesión necesarias
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $data['user_id'];
            $_SESSION['login_time'] = time();
            
            // Obtener el nombre de usuario para la sesión
            $stmt = $con->prepare("SELECT usuario FROM usuarios WHERE id = :id");
            $stmt->execute([':id' => $data['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $_SESSION['usuario'] = $user['usuario'];
            }
            
            echo json_encode([
                'status' => 'success',
                'redirect' => 'index.php'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => $result['message'] ?? 'Código inválido o expirado'
            ]);
        }
        exit;
    }

    // Procesar inicio de sesión normal
    log_debug('Iniciando autenticación normal');
    
    if (!isset($data['usuario']) || !isset($data['password'])) {
        $error_msg = 'Usuario y contraseña son requeridos';
        log_debug('Error: ' . $error_msg);
        echo json_encode(['status' => 'error', 'message' => $error_msg]);
        exit;
    }

    // Limpiar entradas
    $username = clean_input($data['usuario']);
    $password = $data['password']; // No limpiar la contraseña para no afectar caracteres especiales

    // Verificar CAPTCHA si es necesario
    if (isset($_SESSION['captcha_required']) && $_SESSION['captcha_required']) {
        if (!isset($data['captcha_code']) || !isset($_SESSION['captcha_code']) || 
            $data['captcha_code'] !== $_SESSION['captcha_code']) {
            
            log_login_attempt($con, $username, false, $ip);
            echo json_encode([
                'status' => 'captcha_error',
                'message' => 'Código CAPTCHA incorrecto'
            ]);
            exit;
        }
        // CAPTCHA verificado, limpiar
        unset($_SESSION['captcha_required']);
        unset($_SESSION['captcha_code']);
    }

    // Autenticar usuario
    log_debug('Llamando a authenticate_user con usuario: ' . $username);
    $result = authenticate_user($con, $username, $password);
    log_debug('Resultado de authenticate_user:', $result);

    // Registrar intento de inicio de sesión
    log_login_attempt($con, $username, isset($result['status']) && $result['status'] === 'success', $ip);

    // Manejar respuesta
    if (isset($result['status'])) {
        if ($result['status'] === '2fa_required') {
            // Si se requiere autenticación de dos factores
            echo json_encode([
                'status' => '2fa_required',
                'user_id' => $result['user_id'],
                'test_code' => $result['test_code'] ?? null // Solo para pruebas
            ]);
            exit; // Importante: salir después de enviar la respuesta
        } elseif ($result['status'] === 'success') {
            // Inicio de sesión exitoso
            echo json_encode([
                'status' => 'success',
                'redirect' => 'index.php'
            ]);
            exit; // Importante: salir después de enviar la respuesta
        } else {
            // Error en la autenticación
            echo json_encode([
                'status' => 'error',
                'message' => $result['message'] ?? 'Error en la autenticación'
            ]);
            exit; // Importante: salir después de enviar la respuesta
        }
    } else {
        // Incrementar contador de intentos fallidos por IP
        $sql = "INSERT INTO login_attempts (ip, username, success, timestamp) 
                VALUES (:ip, :username, 0, NOW())
                ON DUPLICATE KEY UPDATE attempts = attempts + 1, timestamp = NOW()";
        
        $stmt = $con->prepare($sql);
        $stmt->execute([':ip' => $ip, ':username' => $username]);
        
        // Verificar si se debe requerir CAPTCHA
        $attempts = $con->query("SELECT attempts FROM login_attempts WHERE ip = '" . $ip . "'");
        $attempts = $attempts->fetch(PDO::FETCH_ASSOC)['attempts'] ?? 0;
        
        if ($attempts >= 3) {
            $_SESSION['captcha_required'] = true;
            // Generar código CAPTCHA simple (en producción usar una librería)
            $_SESSION['captcha_code'] = strtoupper(substr(md5(rand()), 0, 6));
            
            echo json_encode([
                'status' => 'captcha_required',
                'message' => $result['message'] ?? 'Demasiados intentos fallidos. Por favor, complete el CAPTCHA.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => $result['message'] ?? 'Error en la autenticación'
            ]);
        }
    }

} catch (PDOException $e) {
    $error_msg = 'Error en la base de datos: ' . $e->getMessage();
    log_debug($error_msg);
    log_debug('Trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la conexión. Por favor, intente nuevamente.',
        'debug' => (ENVIRONMENT === 'development') ? $e->getMessage() : null
    ]);
} catch (Exception $e) {
    $error_msg = 'Error inesperado: ' . $e->getMessage();
    log_debug($error_msg);
    log_debug('Trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Error inesperado. Por favor, intente nuevamente.',
        'debug' => (ENVIRONMENT === 'development') ? $e->getMessage() : null
    ]);
}

// Registrar fin de la solicitud
log_debug('=== FIN DE SOLICITUD ===\n\n');
