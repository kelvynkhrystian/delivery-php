<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Inicializa a conexão
$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
        exit;
    }

    try {
        // Faz exclusão lógica ao invés de física
        $stmt = $conn->prepare("UPDATE faixas_distancia SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir faixa: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
