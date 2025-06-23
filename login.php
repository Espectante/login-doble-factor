<?php
require_once 'model/auth.php';

// Si ya está autenticado, redirigir al índice
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: index.php');
    exit;
}

// Inicializar mensajes de error
$error_message = '';
$show_captcha = false;

// Verificar si se requiere CAPTCHA
if (isset($_SESSION['captcha_required']) && $_SESSION['captcha_required']) {
    $show_captcha = true;
}

// Verificar si hay un mensaje de error de la verificación de 2FA
if (isset($_SESSION['2fa_error'])) {
    $error_message = $_SESSION['2fa_error'];
    unset($_SESSION['2fa_error']);
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    generate_csrf_token();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title>IDENTIFICATE - SISTEMA ERP ESFERA</title>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no' name='viewport' />
    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css" />
    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link href="./assets/css/now-ui-kit.css" rel="stylesheet" />
    <link href="./assets/css/login.css" rel="stylesheet" />
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <style>
        .login-page {
            min-height: 100vh;
            background: #f8f9fa;
        }
        .page-header {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-login {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem 0 rgba(0, 0, 0, 0.1);
        }
        .card-login .card-body {
            padding: 2rem;
        }
    </style>
</head>

<body class="login-page">
    <!-- Navbar -->
    <nav class="navbar navbar-toggleable-md bg-primary fixed-top navbar-transparent " color-on-scroll="500">
        <div class="container">
            <div class="dropdown button-dropdown">
                <a href="#pablo" class="dropdown-toggle" id="navbarDropdown" data-toggle="dropdown">
                    <span class="button-bar"></span>
                    <span class="button-bar"></span>
                    <span class="button-bar"></span>
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-header">Dropdown header</a>
                    <a class="dropdown-item" href="#">Action</a>
                    <a class="dropdown-item" href="#">Another action</a>
                    <a class="dropdown-item" href="#">Something else here</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#">Separated link</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#">One more separated link</a>
                </div>
            </div>
            <div class="navbar-translate">
                <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navigation" aria-controls="navigation-index" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-bar bar1"></span>
                    <span class="navbar-toggler-bar bar2"></span>
                    <span class="navbar-toggler-bar bar3"></span>
                </button>
                <a class="navbar-brand" href="http://codexperu.com" rel="tooltip" title="Desarrollado por Codex Peru" data-placement="bottom" target="_blank">
                    SISTEMA ERP ESFERA 
                </a>
            </div>
            
        </div>
    </nav>
    <!-- End Navbar -->
    <div class="page-header" filter-color="orange">
        <div class="page-header-image" style="background-image:url(./assets/img/login.jpg)"></div>
         <?php   if (!isset($_SESSION['inactivo1day'])) {            
        //En caso estar activado la sesión del captcha, se ativa el formulario captcha.         
        ?>
        <div class="container">
            <div class="col-md-4 content-center">
                <div class="card card-login card-plain">
                    <form class="form" id="login-form" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="header header-primary text-center">
                            <div class="logo-container">
                                <img src="./assets/img/now-logo.png" alt="">
                            </div>
                        </div>
                                <div id="result-login">
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                                <?php endif; ?>
                            </div>
                        <div class="content">
                            <div class="input-group form-group-no-border input-lg">
                                <span class="input-group-addon">
                                    <i class="now-ui-icons users_circle-08"></i>
                                </span>
                                <input type="text" class="form-control" name="usuario" id="usuario" placeholder="Usuario..." required="">
                            </div>
                            <div class="input-group form-group-no-border input-lg">
                                <span class="input-group-addon">
                                    <i class="now-ui-icons ui-1_lock-circle-open"></i>
                                </span>
                                <input type="password" id="password" name="password"placeholder="Password..." class="form-control" / required="">
                            </div>
                        </div>
                        <?php if ($show_captcha): ?>
                            <div class="form-group mt-3">
                                <label for="captcha_code">Ingrese el código de seguridad</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <img src="captcha_code.php" id="captcha-image" style="height: 38px;" alt="Código CAPTCHA" />
                                            <button type="button" class="btn btn-link p-0 ml-2" onclick="refreshCaptcha()" title="Actualizar código">
                                                <i class="now-ui-icons arrows-1_refresh-69"></i>
                                            </button>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control" id="captcha_code" name="captcha_code" 
                                           placeholder="Ingrese el código" required>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Modal 2FA - Versión simplificada -->
                        <style>
                            #twoFactorModal {
                                display: none;
                                position: fixed;
                                top: 0;
                                left: 0;
                                width: 100%;
                                height: 100%;
                                background: rgba(0,0,0,0.5);
                                z-index: 9999;
                            }
                            #twoFactorModal .modal-content {
                                background: white;
                                width: 90%;
                                max-width: 400px;
                                margin: 50px auto;
                                padding: 20px;
                                border-radius: 5px;
                                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                            }
                            #twoFactorModal h3 {
                                color: #333;
                                margin-top: 0;
                                margin-bottom: 15px;
                            }
                            #twoFactorModal p {
                                color: #666;
                                margin-bottom: 15px;
                            }
                            #twoFactorModal input[type="text"] {
                                width: 100%;
                                padding: 12px;
                                font-size: 18px;
                                margin: 10px 0;
                                border: 1px solid #ddd;
                                border-radius: 4px;
                                text-align: center;
                                letter-spacing: 5px;
                            }
                            #twoFactorModal .buttons {
                                display: flex;
                                gap: 10px;
                                margin-top: 20px;
                            }
                            #twoFactorModal .btn {
                                flex: 1;
                                padding: 10px;
                                border: none;
                                border-radius: 4px;
                                cursor: pointer;
                                font-weight: bold;
                            }
                            #twoFactorModal .btn-primary {
                                background: #007bff;
                                color: white;
                            }
                            #twoFactorModal .btn-secondary {
                                background: #6c757d;
                                color: white;
                            }
                            #twoFactorModal .btn:hover {
                                opacity: 0.9;
                            }
                            #two_factor_error {
                                color: #dc3545;
                                margin: 10px 0;
                                display: none;
                                padding: 10px;
                                background: #f8d7da;
                                border-radius: 4px;
                                border: 1px solid #f5c6cb;
                            }
                        </style>
                        
                        <div id="twoFactorModal">
                            <div class="modal-content">
                                <h3>Verificación en dos pasos</h3>
                                <p>Ingresa el código de 6 dígitos que aparece en la consola:</p>
                                
                                <input type="text" 
                                       id="two_factor_code" 
                                       placeholder="123456" 
                                       maxlength="6"
                                       autofocus>
                                <input type="hidden" id="two_factor_user_id">
                                
                                <div id="two_factor_error"></div>
                                
                                <div class="buttons">
                                    <button type="button" class="btn btn-primary" id="verify_2fa_btn">
                                        Verificar
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="cancel_2fa_btn">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="footer text-center">
                            
                            <button type="submit" class="btn btn-primary btn-round btn-lg btn-block">INGRESAR</button>
                        </div>
                        <div class="pull-left">
                            <h6>
                                <a href="#pablo" class="link">Contacto Admnistrador</a>
                            </h6>
                        </div>
                        <div class="pull-right">
                            <h6>
                                <a href="#pablo" class="link">Manuales</a>
                            </h6>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <footer class="footer">
            <div class="container">
                <nav>
                    <ul>
                        <li>
                            <a href="https://www.codexperu.com/soporte">
                                Soporte Codex Peru
                            </a>
                        </li>
                        
                    </ul>
                </nav>
                <div class="copyright">
                    &copy;
                    <script>
                        document.write(new Date().getFullYear())
                    </script>, Desarrollado por
                    
                    <a href="https://www.codexperu.com" target="_blank">Codex Peru</a>.
                </div>
            </div>
        </footer>
    </div>
    <!-- Primero jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    
    <!-- Luego Popper.js y Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js" integrity="sha384-7+zCNj/IqJ95wo16oMtfsKbZ9ccEh31eOz1HGyDuCQ6wgnyJNSYdrPa03rtR1zdB" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    
    <!-- SweetAlert2 CSS y JS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jQuery Validation -->
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/additional-methods.min.js"></script>
</body>

<?php
	if (isset($_GET['mensaje'])){
	   switch($_GET['mensaje']) {
    case 1:?>
        <script type="text/javascript"> Swal.fire({
    icon: 'error',
    title: 'Usuario Bloqueado',
    text: 'Por favor, consulte al administrador del sistema.',
    confirmButtonText: 'Aceptar'
}); </script>
        <?php
        break;
    case 2:
        break;
        }
	}
?>
<script type="application/javascript">
// Función para mostrar mensaje de usuario bloqueado
function msj_bloqueado() {
    Swal.fire({
        title: 'Usuario Bloqueado',
        text: 'Por favor, consulte al administrador del sistema.',
        icon: 'error',
        confirmButtonText: 'Aceptar'
    });
}
</script>
</html>
<?php
    } else {
        echo $msg;
    }
?>      
<script>
// Asegurarse de que jQuery esté disponible
if (typeof jQuery == 'undefined') {
    console.error('jQuery no se ha cargado correctamente');
}

// Función para actualizar el CAPTCHA
function refreshCaptcha() {
    var timestamp = new Date().getTime();
    $('#captcha-image').attr('src', 'captcha_code.php?t=' + timestamp);
}

// Mostrar/ocultar contraseña
function togglePassword() {
    var passwordInput = $('#password');
    var type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
    passwordInput.attr('type', type);
    $(this).find('i').toggleClass('fa-eye fa-eye-slash');
}

// Esperar a que el DOM esté completamente cargado
jQuery(document).ready(function($) {
    console.log('jQuery cargado correctamente');
    
    // Verificar si SweetAlert2 está disponible
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 no se ha cargado correctamente');
    }
    
    // Verificar si jQuery Validation está disponible
    if (typeof $.fn.validate === 'undefined') {
        console.error('jQuery Validation no se ha cargado correctamente');
    }
            // Mostrar/ocultar contraseña
            $('.toggle-password').click(togglePassword);

            // Formulario de inicio de sesión
            $('#login-form').on('submit', function(e) {
                e.preventDefault();
                
                // Mostrar indicador de carga
                var submitBtn = $(this).find('button[type="submit"]');
                var originalBtnText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Verificando...');
                
                // Limpiar mensajes de error previos
                $('#result-login').html('');
                
                // Obtener datos del formulario usando FormData
                var formData = new FormData();
                formData.append('usuario', $('#usuario').val().trim());
                formData.append('password', $('#password').val());
                
                // Validar que los campos no estén vacíos
                if (!formData.get('usuario') || !formData.get('password')) {
                    $('#result-login').html('<div class="alert alert-danger">Usuario y contraseña son requeridos</div>');
                    submitBtn.prop('disabled', false).html(originalBtnText);
                    return false;
                }
                
                // Agregar código CAPTCHA si es necesario
                if ($('#captcha_code').length) {
                    formData.append('captcha_code', $('#captcha_code').val());
                }
                
                // Agregar token CSRF
                formData.append('csrf_token', $('input[name="csrf_token"]').val());
                
                // Enviar solicitud AJAX
                $.ajax({
                    type: 'POST',
                    url: 'model/login_control.php',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            // Redirigir al dashboard
                            window.location.href = response.redirect;
                        } else if (response.status === '2fa_required') {
                            // Mostrar modal de autenticación de dos factores
                            $('#two_factor_user_id').val(response.user_id);
                            $('#two_factor_code').val(''); // Limpiar código anterior
                            $('#two_factor_error').addClass('d-none').text(''); // Limpiar errores
                            
                            // Mostrar el modal
                            var modal = document.getElementById('twoFactorModal');
                            var input = document.getElementById('two_factor_code');
                            var errorDiv = document.getElementById('two_factor_error');
                            
                            // Limpiar valores anteriores
                            input.value = '';
                            errorDiv.style.display = 'none';
                            errorDiv.textContent = '';
                            
                            // Mostrar el modal
                            modal.style.display = 'block';
                            input.focus();
                            
                            // Manejar solo entrada numérica
                            input.addEventListener('input', function() {
                                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 6);
                            });
                            
                            // Cerrar al hacer clic fuera del contenido
                            modal.onclick = function(e) {
                                if (e.target === modal) {
                                    modal.style.display = 'none';
                                }
                            };
                            
                            // Manejar la tecla Enter
                            input.addEventListener('keypress', function(e) {
                                if (e.key === 'Enter') {
                                    document.getElementById('verify_2fa_btn').click();
                                }
                            });
                            
                            // Manejar el botón de cancelar
                            document.getElementById('cancel_2fa_btn').onclick = function() {
                                modal.style.display = 'none';
                            };
                            
                            // Mostrar el código de prueba en la interfaz
                            if (response.test_code) {
                                console.log('Código de prueba 2FA:', response.test_code);
                                // Mostrar el código en un mensaje informativo
                                $('#two_factor_info').removeClass('d-none').html('Código de prueba: <strong>' + response.test_code + '</strong> (solo para pruebas)');
                            } else {
                                $('#two_factor_info').addClass('d-none');
                            }
                        } else if (response.status === 'captcha_required') {
                            // Mostrar CAPTCHA y mensaje de error
                            $('#result-login').html('<div class="alert alert-warning">' + response.message + '</div>');
                            $('#captcha-container').removeClass('d-none');
                            refreshCaptcha();
                        } else {
                            // Mostrar mensaje de error
                            $('#result-login').html('<div class="alert alert-danger">' + response.message + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = 'Error en la conexión. Por favor, intente nuevamente.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        $('#result-login').html('<div class="alert alert-danger">' + errorMessage + '</div>');
                    },
                    complete: function() {
                        // Restaurar botón
                        submitBtn.prop('disabled', false).html(originalBtnText);
                    }
                });
                
                return false;
            });
            
            // Manejar verificación de código de dos factores
            $('#verify_2fa_btn').click(function() {
                var code = $('#two_factor_code').val().trim();
                var userId = $('#two_factor_user_id').val();
                var verifyBtn = $(this);
                var originalBtnText = verifyBtn.html();
                
                // Validar código
                if (!code || code.length !== 6) {
                    $('#two_factor_error').removeClass('d-none').text('Por favor ingrese un código válido de 6 dígitos');
                    return;
                }
                
                // Mostrar indicador de carga
                verifyBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Verificando...');
                $('#two_factor_error').addClass('d-none').text('');
                
                // Enviar código de verificación
                $.ajax({
                    type: 'POST',
                    url: 'model/login_control.php',
                    data: JSON.stringify({
                        action: 'verify_2fa',
                        user_id: userId,
                        code: code,
                        csrf_token: $('input[name="csrf_token"]').val()
                    }),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Respuesta del servidor:', response);
                        
                        if (response.status === 'success') {
                            console.log('Redirigiendo a:', response.redirect);
                            // Forzar recarga completa para asegurar que la sesión se cargue
                            window.location.replace(response.redirect || 'index.php');
                        } else {
                            console.error('Error en la respuesta:', response.message);
                            // Mostrar mensaje de error
                            $('#two_factor_error')
                                .removeClass('d-none')
                                .html(response.message || 'Código inválido o expirado<br>Detalles en consola (F12)');
                            
                            // Mostrar más detalles en consola
                            if (response.debug) {
                                console.log('Detalles de depuración:', response.debug);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error en la petición AJAX:', status, error);
                        $('#two_factor_error').removeClass('d-none')
                            .text('Error al verificar el código. Por favor, intente nuevamente.');
                    },
                    complete: function() {
                        verifyBtn.prop('disabled', false).html(originalBtnText);
                    }
                });
            });
            
            // Permitir presionar Enter en el campo de código 2FA
            $('#two_factor_code').keypress(function(e) {
                if (e.which === 13) {
                    $('#verify_2fa_btn').click();
                    return false;
                }
            });
        });
    </script>