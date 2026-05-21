<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Inicializa a conexão
$database = new Database();
$conn = $database->getConnection();

try {
    $stmt = $conn->prepare("SELECT id, inicio, fim, valor FROM faixas_distancia WHERE ativo = 1 ORDER BY inicio ASC");
    $stmt->execute();
    $faixas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'faixas' => $faixas]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao listar faixas: ' . $e->getMessage()]);
}
