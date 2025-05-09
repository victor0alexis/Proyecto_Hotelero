<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../../vendor/autoload.php'; // Ajusta si el path es distinto

function enviarCodigoVerificacion($destinatario, $codigo) {
    $mail = new PHPMailer(true);



    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'victoralexisdelgado10@gmail.com';  // correo Gmail
        $mail->Password   = 'zqrhixtaeoihwbme'; // Contraseña de aplicación, "PHPMAILER"
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Remitente y destinatario
        $mail->setFrom('victoralexisdelgado10@gmail.com', 'Sistema Hotelero');    //mis datos
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = 'Verificacion de cuenta - Sistema Hotelero';     //Asunto
        $mail->Body = "    
            <div style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
                <div style='max-width: 500px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);'>
                    <h2 style='color: #2c3e50;'>¡Bienvenido al Sistema Hotelero!</h2>
                    <p style='font-size: 16px; color: #333;'>Gracias por registrarte. Para completar tu registro, por favor utiliza el siguiente código de verificación:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <span style='font-size: 30px; font-weight: bold; background-color: #f0f0f0; padding: 10px 20px; border-radius: 8px; color: #2980b9; letter-spacing: 2px;'>$codigo</span>
                    </div>
                    <p style='font-size: 14px; color: #777;'>Si no solicitaste esta verificación, puedes ignorar este correo.</p>
                    <hr style='margin: 20px 0;'>
                    <p style='font-size: 12px; color: #aaa; text-align: center;'>Sistema Hotelero &copy; " . date('Y') . "</p>
                </div>
            </div>
        ";        

        // Enviar correo
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
