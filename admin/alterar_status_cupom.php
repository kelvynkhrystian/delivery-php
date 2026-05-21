<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado como admin
if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Pega o corpo da requisição
$data = json_decode(file_get_contents('php://input'), true);

// Verifica se os dados necessários foram enviados
if (!isset($data['id']) || !isset($data['ativo'])) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Atualiza o status do cupom
    $query = "UPDATE cupons SET ativo = :ativo WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data['id']);
    $stmt->bindParam(':ativo', $data['ativo'], PDO::PARAM_BOOL);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Cupom não encontrado']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
