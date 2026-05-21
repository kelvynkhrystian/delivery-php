<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$dados = $_POST;

if (!isset($dados['endereco_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do endereço não fornecido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verifica se o endereço pertence ao usuário
    $stmt = $db->prepare("SELECT id FROM enderecos_usuario WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$dados['endereco_id'], $_SESSION['usuario']['id']]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Endereço não pertence ao usuário']);
        exit;
    }
    
    // Atualiza o endereço
    $stmt = $db->prepare("
        UPDATE enderecos_usuario 
        SET logradouro = ?, 
            numero = ?, 
            complemento = ?, 
            bairro = ?, 
            cidade = ?, 
            estado = ?, 
            cep = ?
        WHERE id = ? AND usuario_id = ?
    ");
    
    $stmt->execute([
        $dados['logradouro'],
        $dados['numero'],
        $dados['complemento'],
        $dados['bairro'],
        $dados['cidade'],
        $dados['estado'],
        $dados['cep'],
        $dados['endereco_id'],
        $_SESSION['usuario']['id']
    ]);
    
    $_SESSION['mensagem'] = 'Endereço atualizado com sucesso!';
    $_SESSION['mensagem_tipo'] = 'sucesso';
    
    header('Location: ../conta.php');
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    $_SESSION['mensagem'] = 'Erro ao atualizar endereço';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: ../conta.php');
    exit;
}
