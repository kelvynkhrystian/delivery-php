<?php
session_start();
require_once '../includes/conexao.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não está logado']);
    exit;
}

// Recebe os dados do POST
$data = json_decode(file_get_contents('php://input'), true);
$usuario_id = $_SESSION['usuario']['id'];

if (!isset($data['endereco_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do endereço não fornecido']);
    exit;
}

// Verifica se o endereço pertence ao usuário
$query = "SELECT id FROM enderecos_usuario WHERE id = ? AND usuario_id = ?";
$stmt = $conexao->prepare($query);
$stmt->execute([$data['endereco_id'], $usuario_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Endereço não encontrado']);
    exit;
}

try {
    // Inicia a transação
    $conexao->beginTransaction();

    // Remove o status principal de todos os endereços do usuário
    $query = "UPDATE enderecos_usuario SET principal = 0 WHERE usuario_id = ?";
    $stmt = $conexao->prepare($query);
    $stmt->execute([$usuario_id]);

    // Define o novo endereço principal
    $query = "UPDATE enderecos_usuario SET principal = 1 WHERE id = ? AND usuario_id = ?";
    $stmt = $conexao->prepare($query);
    $stmt->execute([$data['endereco_id'], $usuario_id]);

    // Confirma a transação
    $conexao->commit();
    
    echo json_encode(['success' => true, 'message' => 'Endereço principal atualizado com sucesso']);
} catch (PDOException $e) {
    // Desfaz a transação em caso de erro
    $conexao->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar endereço principal: ' . $e->getMessage()]);
}
