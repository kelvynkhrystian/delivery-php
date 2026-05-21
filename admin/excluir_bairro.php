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

if (!isset($data['nome'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nome do bairro não informado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("DELETE FROM bairros_entrega WHERE nome = ?");
    $stmt->execute([$data['nome']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Bairro excluído com sucesso']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Bairro não encontrado']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir bairro: ' . $e->getMessage()]);
}
