<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Recebe e valida os dados
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
    exit;
}

$id = intval($data['id']);

// Conecta ao banco de dados
$database = new Database();
$db = $database->getConnection();

try {
    // Marca a faixa como inativa ao invés de excluir fisicamente
    $stmt = $db->prepare("UPDATE faixas_distancia SET ativo = 0 WHERE id = :id");
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Faixa de distância excluída com sucesso']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir faixa de distância']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao processar a requisição: ' . $e->getMessage()]);
}
