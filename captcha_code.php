<?php
session_start();

// Configurar encabezados para evitar caché
header('Expires: 0');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Type: image/png');

// Crear una imagen de 120x30 píxeles
$width = 120;
$height = 40;
$image = imagecreatetruecolor($width, $height);

// Colores
$bg_color = imagecolorallocate($image, 255, 255, 255); // Fondo blanco
$text_color = imagecolorallocate($image, 0, 0, 0); // Texto negro
$line_color = imagecolorallocate($image, 200, 200, 200); // Líneas grises
$pixel_color = imagecolorallocate($image, 148, 148, 148); // Píxeles grises

// Rellenar el fondo
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Generar un texto aleatorio de 6 caracteres
$chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$captcha_text = '';
for ($i = 0; $i < 6; $i++) {
    $captcha_text .= $chars[rand(0, strlen($chars) - 1)];
}

// Guardar el texto en la sesión
$_SESSION['captcha_code'] = $captcha_text;

// Añadir líneas aleatorias para dificultar el reconocimiento
for ($i = 0; $i < 5; $i++) {
    imageline($image, 0, rand() % $height, $width, rand() % $height, $line_color);
}

// Añadir puntos aleatorios
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, rand() % $width, rand() % $height, $pixel_color);
}

// Añadir el texto a la imagen
$font_size = 20;
$font_angle = rand(-5, 5);
$text_box = imagettfbbox($font_size, $font_angle, __DIR__ . '/assets/fonts/arial.ttf', $captcha_text);
$text_width = $text_box[4] - $text_box[6];
$text_height = $text_box[3] - $text_box[5];
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2 + $font_size;

// Usar una fuente TrueType (asegúrate de tener los permisos adecuados)
$font = __DIR__ . '/assets/fonts/arial.ttf';

// Si no se encuentra la fuente, usar una fuente por defecto
if (!file_exists($font)) {
    // Intentar con una fuente del sistema
    $font = 'arial';
    
    // Dibujar el texto sin fuente TrueType
    imagestring($image, 5, $x, ($height - 20) / 2, $captcha_text, $text_color);
} else {
    // Dibujar el texto con la fuente TrueType
    imagettftext($image, $font_size, $font_angle, $x, $y, $text_color, $font, $captcha_text);
}

// Generar y mostrar la imagen
imagepng($image);

// Liberar memoria
imagedestroy($image);
