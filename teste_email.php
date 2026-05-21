<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    echo "<pre>";
    echo "=== Teste de Email ===\n\n";
    
    $mail = new PHPMailer(true);
    echo "PHPMailer criado\n";

    //Server settings
    $mail->SMTPDebug = 3; // Debug ainda mais detalhado
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'senhadeliverytemporaria@gmail.com';
    $mail->Password = 'exyc lozp puwt eurc';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    echo "Configurações SMTP definidas\n";

    //Recipients
    $mail->setFrom('senhadeliverytemporaria@gmail.com', 'Sistema de Gestão');
    $mail->addAddress('senhadeliverytemporaria@gmail.com');
    echo "Destinatários configurados\n";

    //Content
    $mail->isHTML(false);
    $mail->Subject = 'Teste de Email - ' . date('H:i:s');
    $mail->Body = "Este é um email de teste enviado em: " . date('d/m/Y H:i:s');
    echo "Conteúdo do email definido\n";

    echo "\nTentando enviar email...\n";
    $mail->send();
    echo "\nEmail enviado com sucesso!\n";

} catch (Exception $e) {
    echo "\nErro ao enviar email:\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    
    if (isset($mail) && $mail->smtp) {
        echo "\nÚltima resposta SMTP:\n";
        echo $mail->smtp->getLastResponse() . "\n";
        echo "\nErro SMTP detalhado:\n";
        echo $mail->smtp->getError() . "\n";
    }
}

echo "\n=== Informações do PHP ===\n";
echo "OpenSSL instalado: " . (extension_loaded('openssl') ? 'Sim' : 'Não') . "\n";
echo "Versão OpenSSL: " . OPENSSL_VERSION_TEXT . "\n";
echo "Versão PHP: " . phpversion() . "\n";
echo "</pre>";
