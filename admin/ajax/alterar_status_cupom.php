<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['ativo'])) {
        throw new Exception('Dados incompletos');
    }

    $query = "UPDATE cupons SET ativo = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $success = $stmt->execute([$data['ativo'], $data['id']]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Erro ao alterar status do cupom');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
