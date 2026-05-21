<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../classes/EmailSender.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $senha = $data['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        throw new Exception('Email e senha são obrigatórios');
    }

    // Salva temporariamente as configurações
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("UPDATE configuracoes SET email_sistema = ?, senha_email = ? WHERE id = 1");
    $stmt->execute([$email, $senha]);

    // Tenta enviar email de teste
    $emailSender = new EmailSender($db);
    $resultado = $emailSender->enviarEmail(
        $email,
        'Teste de Configuração de Email',
        "Este é um email de teste para verificar se as configurações de SMTP estão funcionando corretamente.\n\n" .
        "Se você recebeu este email, significa que a configuração foi bem-sucedida!"
    );

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Email de teste enviado com sucesso! Verifique sua caixa de entrada.'
        ]);
    } else {
        throw new Exception('Não foi possível enviar o email de teste');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
