<?php
session_start();
require_once '../../config/database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Recebe os dados
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verifica se o endereço pertence ao usuário
    $query = "SELECT id FROM enderecos WHERE id = ? AND usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id, $_SESSION['usuario']['id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endereço não encontrado']);
        exit;
    }

    // Remove o padrão de todos os endereços do usuário
    $query = "UPDATE enderecos SET padrao = 0 WHERE usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario']['id']]);

    // Define o novo endereço padrão
    $query = "UPDATE enderecos SET padrao = 1 WHERE id = ? AND usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id, $_SESSION['usuario']['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Endereço definido como padrão'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao definir endereço padrão: ' . $e->getMessage()
    ]);
}
