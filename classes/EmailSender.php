<?php
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailSender {
    private $mail;

    public function __construct() {
        try {
            $this->mail = new PHPMailer(true);
            
            // Ativar debug
            $this->mail->SMTPDebug = 2; // Mostra mensagens detalhadas
            $this->mail->Debugoutput = 'html'; // Mostra na tela
            
            // Configurações do Gmail
            $this->mail->isSMTP();
            $this->mail->Host = 'smtp.gmail.com';
            $this->mail->SMTPAuth = true;
            $this->mail->Username = 'senhadeliverytemporaria@gmail.com';
            $this->mail->Password = 'exyc lozp puwt eurc';
            $this->mail->SMTPSecure = 'tls';
            $this->mail->Port = 587;
            $this->mail->CharSet = 'UTF-8';
            
            // Configurações do remetente
            $this->mail->setFrom('senhadeliverytemporaria@gmail.com', 'Delivery App Online (KelvynK)');
        } catch (Exception $e) {
            throw new Exception("Erro ao configurar email: " . $e->getMessage());
        }
    }

    public function enviarEmail($para, $assunto, $mensagem) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($para);
            $this->mail->Subject = $assunto;
            $this->mail->Body = $mensagem;
            $this->mail->isHTML(false);

            if (!$this->mail->send()) {
                throw new Exception($this->mail->ErrorInfo);
            }
            error_log("[EmailSender] Email enviado com sucesso para: " . $para);
            return true;
        } catch (Exception $e) {
            error_log("[EmailSender][Erro] " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw new Exception("Erro ao enviar email: " . $e->getMessage());
        }
    }
}
