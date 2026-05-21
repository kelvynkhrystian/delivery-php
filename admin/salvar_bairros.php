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

if (!is_array($data) || empty($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    foreach ($data as $bairro) {
        if (!isset($bairro['nome']) || !isset($bairro['valor'])) {
            continue;
        }

        // Verifica se o bairro já existe
        $stmt = $db->prepare("SELECT COUNT(*) FROM bairros_entrega WHERE nome = ?");
        $stmt->execute([$bairro['nome']]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Já existe um bairro com este nome']);
            exit;
        }

        // Insere o novo bairro
        $stmt = $db->prepare("INSERT INTO bairros_entrega (nome, valor) VALUES (?, ?)");
        $stmt->execute([$bairro['nome'], $bairro['valor']]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Bairros salvos com sucesso']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar bairros: ' . $e->getMessage()]);
}
