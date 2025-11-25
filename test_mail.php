<?php
/**
 * Script de prueba para MailCatcher
 * Ejecutar: http://localhost/planilla-innova/test_mail.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar variables de entorno manualmente (compatible CLI y Web)
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $value = trim($value, '"');

            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
            }
        }
    }
}

loadEnv(__DIR__ . '/.env');

echo "<h1>Test de MailCatcher</h1>";

try {
    $mail = new PHPMailer(true);

    // Configuración SMTP desde .env
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAIL_HOST'] ?? 'localhost';

    // Autenticación solo si hay username configurado
    $mailUsername = $_ENV['MAIL_USERNAME'] ?? '';
    if (!empty($mailUsername)) {
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailUsername;
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? '';
    } else {
        $mail->SMTPAuth   = false;
    }

    // Encriptación solo si está configurada
    $encryption = $_ENV['MAIL_ENCRYPTION'] ?? '';
    if (!empty($encryption)) {
        $mail->SMTPSecure = $encryption;
    }

    $mail->Port       = $_ENV['MAIL_PORT'] ?? 1025;
    $mail->CharSet    = 'UTF-8';

    // Debug
    $mail->SMTPDebug = 2; // Mostrar debug en pantalla

    // Remitente
    $mail->setFrom(
        $_ENV['MAIL_FROM_ADDRESS'] ?? 'test@planillas.local',
        $_ENV['MAIL_FROM_NAME'] ?? 'Test Sistema'
    );

    // Destinatario
    $mail->addAddress('admin@test.com', 'Usuario Test');

    // Contenido
    $mail->isHTML(true);
    $mail->Subject = 'Test MailCatcher - ' . date('Y-m-d H:i:s');

    $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2 style='color: #FF5722;'>✅ Test de MailCatcher Exitoso</h2>
            <p>Este es un email de prueba enviado desde PHPMailer.</p>
            <p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>
            <hr>
            <p style='color: #888; font-size: 12px;'>
                Configuración:<br>
                - Host: {$mail->Host}<br>
                - Port: {$mail->Port}<br>
                - From: {$mail->From}
            </p>
        </body>
        </html>
    ";

    $mail->AltBody = "Test de MailCatcher Exitoso\n\nFecha: " . date('Y-m-d H:i:s');

    $mail->send();

    echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; margin-top: 20px;'>";
    echo "<h2 style='color: #155724; margin: 0;'>✅ Email Enviado Exitosamente</h2>";
    echo "<p>Revisa MailCatcher en: <a href='http://localhost:1080' target='_blank'>http://localhost:1080</a></p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin-top: 20px;'>";
    echo "<h2 style='color: #721c24; margin: 0;'>❌ Error al Enviar Email</h2>";
    echo "<p><strong>Error:</strong> {$mail->ErrorInfo}</p>";
    echo "<p><strong>Exception:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Configuración Actual:</h3>";
echo "<pre>";
echo "MAIL_HOST: " . ($_ENV['MAIL_HOST'] ?? 'NO DEFINIDO') . "\n";
echo "MAIL_PORT: " . ($_ENV['MAIL_PORT'] ?? 'NO DEFINIDO') . "\n";
echo "MAIL_FROM_ADDRESS: " . ($_ENV['MAIL_FROM_ADDRESS'] ?? 'NO DEFINIDO') . "\n";
echo "MAIL_FROM_NAME: " . ($_ENV['MAIL_FROM_NAME'] ?? 'NO DEFINIDO') . "\n";
echo "</pre>";

echo "<hr>";
echo "<h3>Instrucciones:</h3>";
echo "<ol>";
echo "<li>Asegúrate de que MailCatcher esté corriendo (Laragon → Tools → MailCatcher)</li>";
echo "<li>Accede a <a href='http://localhost:1080' target='_blank'>http://localhost:1080</a> para ver los emails</li>";
echo "<li>Si ves el email en MailCatcher, todo funciona correctamente</li>";
echo "</ol>";
