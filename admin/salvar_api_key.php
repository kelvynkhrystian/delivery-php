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

if (!isset($data['api_key'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'API Key não fornecida']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Atualiza a API key na tabela de configurações
    $stmt = $db->prepare("UPDATE configuracoes SET maps_api_key = ?");
    $stmt->execute([$data['api_key']]);
    
    echo json_encode(['success' => true, 'message' => 'API Key salva com sucesso']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar API Key: ' . $e->getMessage()
    ]);
}
