<?php
require_once 'vendor/autoload.php';
require_once 'classes/EmailSender.php';

$emailSender = new EmailSender();
$resultado = $emailSender->enviarEmail(
    'delivery-senha-temporaria@kelvynk.com.br', // Enviando para o próprio email para teste
    'Teste de Envio de Email',
    "Este é um email de teste do sistema de recuperação de senha.\n\n" .
    "Se você recebeu este email, significa que a configuração está funcionando corretamente!"
);

if ($resultado) {
    echo "Email enviado com sucesso!";
} else {
    echo "Erro ao enviar email. Verifique o log de erros.";
}
