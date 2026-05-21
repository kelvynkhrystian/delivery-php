<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Pega o conteúdo JSON enviado
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['nome'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Exclusão lógica do bairro
    $stmt = $db->prepare("UPDATE bairros_entrega SET ativo = 0 WHERE nome = ?");
    $success = $stmt->execute([$data['nome']]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir bairro']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
