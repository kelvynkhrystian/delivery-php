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

if (!isset($data['raio_entrega'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Raio de entrega não fornecido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Atualiza o raio de entrega
    $stmt = $db->prepare("UPDATE configuracoes SET maps_raio_entrega = ?");
    $stmt->execute([$data['raio_entrega']]);
    
    echo json_encode(['success' => true, 'message' => 'Raio de entrega salvo com sucesso']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar raio de entrega: ' . $e->getMessage()
    ]);
}
