<?php
    require 'Email.php';
    require 'config.php';
    require 'EmailService.php';   
    
    class functions {

        public function DiffData($DataInicial, $DataFinal) {
            $d1   = new DateTime($DataInicial);
            $d2   = new DateTime($DataFinal);
            $diff = $d1->diff($d2);
            $min  = $diff->i + ($diff->h * 60) + ($diff->s / 60 );
            return round($min, 2);
        }

        public function DiffDataSeconds($DataInicial, $DataFinal) {
            $d1   = new DateTime($DataInicial);
            $d2   = new DateTime($DataFinal);
            $diff = $d1->diff($d2);
            $min  = $diff->i + ($diff->h * 60) + ($diff->s / 60 );
            return round($min, 2);
        }

        public function SendEmail($subject, $body) {
            try {
                $body         = "<p><b>" . $body . "</p></b>";
                $email        = new Email();
                $email->setSubject($subject);
                $email->setBody($body);
                $email->setFrom(CONST_EMAIL_PADRAO);
                $email->setSmtpHost(CONST_SMTP_PADRAO);
                $email->setFromName(NOME_SITE);
                $email->setFromPassword(CONST_SENHA_EMAIL_PADRAO);
                $email->setAdress(CONST_EMAIL_ENVIO);
                $email->setAdressName(CONST_EMAIL_ENVIO);
                $emailService = new EmailService();
                return $emailService->sendEmail($email);
                echo "<script>alert('Sucess Update.');history.back();</script>";
                exit();
            } catch (Exception $exc) {
                return 0;
            }
        }
    }
    