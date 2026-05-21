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

if (!isset($data['api_key']) || !isset($data['latitude']) || !isset($data['longitude']) || !isset($data['endereco']) || !isset($data['raio_entrega'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Atualiza todas as configurações do mapa
    $stmt = $db->prepare("UPDATE configuracoes SET 
        maps_api_key = ?,
        maps_latitude = ?,
        maps_longitude = ?,
        maps_endereco = ?,
        maps_raio_entrega = ?");
    
    $stmt->execute([
        $data['api_key'],
        $data['latitude'],
        $data['longitude'],
        $data['endereco'],
        $data['raio_entrega']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar configurações: ' . $e->getMessage()
    ]);
}
