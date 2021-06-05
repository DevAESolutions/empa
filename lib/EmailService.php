<?php

require_once("phpmailer/class.phpmailer.php");
require_once('phpmailer/PHPMailerAutoload.php');

class EmailService {

    public static function sendEmail(Email $email) {
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPDebug = 0;        
        $mail->SMTPAuth = true;		// Autenticação ativada
	$mail->SMTPSecure = 'tls';	// SSL REQUERIDO pelo GMail
	$mail->Host = CONST_SMTP_PADRAO;	// SMTP utilizado
	$mail->Port = 587;  		
        $mail->Username = $email->getFrom();
        $mail->Password = $email->getFromPassword();
        $mail->setFrom($email->getFrom(), $email->getFromName());
        $mail->Subject = $email->getSubject();
        $mail->Body = $email->getBody();
        $mail->AddAddress($email->getAdress());
        $mail->IsHTML(true);
        $enviado = $mail->Send();
        if (!$enviado) {
            return 1;
            //      echo "<br>Mailer Error: " . $mail->ErrorInfo;
        }
        return 0;
    }

}
