<?php
require_once 'model/auth.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

// Verificar inactividad (30 minutos)
$inactivity_timeout = 1800; // 30 minutos en segundos
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_timeout)) {
    // Destruir la sesión y redirigir al login
    session_unset();
    session_destroy();
    header('Location: login.php?session_expired=1');
    exit;
}

// Actualizar tiempo de última actividad
$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="es-ES">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - SISTEMA ERP ESFERA</title>
    <!-- Incluir los mismos estilos que en login.php para consistencia -->
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="./assets/css/now-ui-kit.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css" />
    <style>
        body {
            padding-top: 70px;
            background-color: #f4f4f4;
        }
        .welcome-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        .user-info {
            text-align: center;
            margin-bottom: 30px;
        }
        .user-info i {
            font-size: 60px;
            color: #f96332;
            margin-bottom: 15px;
        }
        .logout-btn {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-toggleable-md bg-primary fixed-top navbar-transparent" color-on-scroll="500">
        <div class="container">
            <div class="dropdown button-dropdown">
                <a href="#pablo" class="dropdown-toggle" id="navbarDropdown" data-toggle="dropdown">
                    <span class="button-bar"></span>
                    <span class="button-bar"></span>
                    <span class="button-bar"></span>
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-header">Menú de usuario</a>
                    <a class="dropdown-item" href="#"><i class="now-ui-icons users_single-02"></i> Mi perfil</a>
                    <a class="dropdown-item" href="#"><i class="now-ui-icons ui-1_settings-gear-63"></i> Configuración</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="logout.php"><i class="now-ui-icons media-1_button-power"></i> Cerrar sesión</a>
                </div>
            </div>
            <div class="navbar-translate">
                <a class="navbar-brand" href="index.php">
                    SISTEMA ERP ESFERA
                </a>
            </div>
        </div>
    </nav>
    <!-- End Navbar -->

    <div class="wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="welcome-container">
                        <div class="user-info">
                            <i class="now-ui-icons users_circle-08"></i>
                            <h3>Bienvenid@, <?php echo htmlspecialchars($_SESSION['usuario'] ?? 'Usuario'); ?></h3>
                            <p class="text-muted">Último acceso: <?php echo date('d/m/Y H:i:s'); ?></p>
                        </div>
                        
                        <div class="text-center">
                            <p>Has iniciado sesión correctamente en el sistema.</p>
                            <a href="logout.php" class="btn btn-primary btn-round logout-btn">
                                <i class="now-ui-icons media-1_button-power"></i> Cerrar sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--   Core JS Files   -->
    <script src="./assets/js/core/jquery.min.js" type="text/javascript"></script>
    <script src="./assets/js/core/tether.min.js" type="text/javascript"></script>
    <script src="./assets/js/core/bootstrap.min.js" type="text/javascript"></script>
    
    <!-- Script para manejar la inactividad -->
    <script>
        // Función para resetear el temporizador de inactividad
        function resetInactivityTimer() {
            // Enviar una solicitud AJAX para actualizar la última actividad
            $.ajax({
                url: 'update_activity.php',
                type: 'POST',
                data: { csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>' },
                success: function(response) {
                    console.log('Actividad actualizada');
                }
            });
        }

        // Resetear el temporizador en eventos de interacción del usuario
        $(document).on('mousemove keydown mousedown touchstart', resetInactivityTimer);

        // Verificar inactividad cada minuto
        setInterval(function() {
            $.get('check_session.php', function(response) {
                if (response.status === 'expired') {
                    window.location.href = 'login.php?session_expired=1';
                }
            }, 'json');
        }, 60000); // Verificar cada minuto
    </script>
</body>
</html>