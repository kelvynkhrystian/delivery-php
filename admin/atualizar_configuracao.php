<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Pega o conteúdo JSON enviado
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    switch ($data['tipo']) {
        case 'entrega':
            // Atualiza o tipo de entrega
            $stmt = $db->prepare("UPDATE configuracoes SET tipo_entrega = ? WHERE id = (SELECT id FROM (SELECT id FROM configuracoes ORDER BY id DESC LIMIT 1) as t)");
            $stmt->execute([$data['modo']]);
            echo json_encode(['success' => true, 'message' => 'Tipo de entrega atualizado com sucesso']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tipo de configuração inválido']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar configuração: ' . $e->getMessage()]);
}
