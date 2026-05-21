<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Inicializa a conexão
$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $inicio = $data['inicio'] ?? '';
    $fim = $data['fim'] ?? '';
    $valor = $data['valor'] ?? '';

    if (empty($inicio) || empty($fim) || empty($valor)) {
        echo json_encode(['success' => false, 'message' => 'Campos obrigatórios não preenchidos']);
        exit;
    }

    if ($inicio >= $fim) {
        echo json_encode(['success' => false, 'message' => 'KM inicial deve ser menor que KM final']);
        exit;
    }

    try {
        // Verifica se já existe uma faixa que se sobrepõe
        $stmt = $conn->prepare("SELECT COUNT(*) FROM faixas_distancia WHERE (? BETWEEN inicio AND fim OR ? BETWEEN inicio AND fim) AND ativo = 1");
        $stmt->execute([$inicio, $fim]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => 'Já existe uma faixa que se sobrepõe a esta']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO faixas_distancia (inicio, fim, valor) VALUES (?, ?, ?)");
        $stmt->execute([$inicio, $fim, $valor]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar faixa: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
