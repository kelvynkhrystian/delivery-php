<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('ID do cupom não informado');
    }

    // Primeiro verifica se o cupom existe
    $query = "SELECT id FROM cupons WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data['id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Cupom não encontrado');
    }

    // Exclui o cupom
    $query = "DELETE FROM cupons WHERE id = ?";
    $stmt = $db->prepare($query);
    $success = $stmt->execute([$data['id']]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Erro ao excluir cupom');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
