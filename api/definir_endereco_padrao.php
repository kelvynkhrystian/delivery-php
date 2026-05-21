<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Usuário não autenticado'
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['endereco_id'])) {
        throw new Exception('ID do endereço é obrigatório');
    }
    
    // Verifica se o endereço pertence ao usuário
    $query = "SELECT id FROM enderecos_usuario WHERE id = ? AND usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data['endereco_id'], $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Endereço não encontrado');
    }
    
    // Remove o padrão de todos os endereços do usuário
    $query = "UPDATE enderecos_usuario SET padrao = 0 WHERE usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    
    // Define o novo endereço padrão
    $query = "UPDATE enderecos_usuario SET padrao = 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data['endereco_id']]);
    
    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
