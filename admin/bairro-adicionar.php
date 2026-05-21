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

if (!$data || !isset($data['nome']) || !isset($data['valor'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Verifica se o bairro já existe
    $stmt = $db->prepare("SELECT nome FROM bairros_entrega WHERE nome = ? AND ativo = 1");
    $stmt->execute([$data['nome']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Este bairro já existe']);
        exit;
    }

    // Adiciona o bairro
    $stmt = $db->prepare("INSERT INTO bairros_entrega (nome, valor) VALUES (?, ?)");
    $success = $stmt->execute([$data['nome'], $data['valor']]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao adicionar bairro']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
