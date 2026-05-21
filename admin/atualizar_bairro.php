<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Recebe o JSON do corpo da requisição
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['nome_antigo']) || !isset($data['nome']) || !isset($data['valor'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Se o nome novo for diferente do antigo, verifica se já existe
    if ($data['nome'] !== $data['nome_antigo']) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM bairros_entrega WHERE nome = ? AND nome != ?");
        $stmt->execute([$data['nome'], $data['nome_antigo']]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Já existe um bairro com este nome']);
            exit;
        }
    }

    $stmt = $db->prepare("UPDATE bairros_entrega SET nome = ?, valor = ? WHERE nome = ?");
    $stmt->execute([$data['nome'], $data['valor'], $data['nome_antigo']]);
    
    echo json_encode(['success' => true, 'message' => 'Bairro atualizado com sucesso']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar bairro: ' . $e->getMessage()]);
}
