<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Inicializa a conexão
$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';
    $inicio = $data['inicio'] ?? '';
    $fim = $data['fim'] ?? '';
    $valor = $data['valor'] ?? '';

    if (empty($id) || empty($inicio) || empty($fim) || empty($valor)) {
        echo json_encode(['success' => false, 'message' => 'Campos obrigatórios não preenchidos']);
        exit;
    }

    if ($inicio >= $fim) {
        echo json_encode(['success' => false, 'message' => 'KM inicial deve ser menor que KM final']);
        exit;
    }

    try {
        // Verifica se já existe uma faixa que se sobrepõe (excluindo a própria faixa sendo editada)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM faixas_distancia WHERE (? BETWEEN inicio AND fim OR ? BETWEEN inicio AND fim) AND id != ? AND ativo = 1");
        $stmt->execute([$inicio, $fim, $id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => 'Já existe uma faixa que se sobrepõe a esta']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE faixas_distancia SET inicio = ?, fim = ?, valor = ? WHERE id = ? AND ativo = 1");
        $stmt->execute([$inicio, $fim, $valor, $id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar faixa: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
