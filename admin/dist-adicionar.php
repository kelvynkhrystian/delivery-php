<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Pega o conteúdo JSON enviado
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['distancia_km']) || !isset($data['valor_entrega'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$distancia_km = floatval($data['distancia_km']);
$valor_entrega = floatval($data['valor_entrega']);

// Validações
if ($distancia_km <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'A distância deve ser maior que zero']);
    exit;
}

if ($valor_entrega < 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'O valor não pode ser negativo']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Verifica se já existe uma faixa com a mesma distância
    $stmt = $db->prepare("SELECT id FROM faixas_distancia WHERE distancia_km = :distancia_km");
    $stmt->bindParam(':distancia_km', $distancia_km);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Já existe uma faixa com esta distância']);
        exit;
    }

    // Insere a nova faixa
    $stmt = $db->prepare("INSERT INTO faixas_distancia (distancia_km, valor_entrega) VALUES (:distancia_km, :valor_entrega)");
    $stmt->bindParam(':distancia_km', $distancia_km);
    $stmt->bindParam(':valor_entrega', $valor_entrega);
    $stmt->execute();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Faixa de distância adicionada com sucesso']);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar faixa de distância: ' . $e->getMessage()]);
}
