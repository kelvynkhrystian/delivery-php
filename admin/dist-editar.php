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

if (!isset($data['id']) || !isset($data['inicio']) || !isset($data['fim']) || !isset($data['valor'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$id = intval($data['id']);
$inicio = floatval($data['inicio']);
$fim = floatval($data['fim']);
$valor = floatval($data['valor']);

// Validações adicionais
if ($inicio < 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'A distância inicial não pode ser negativa']);
    exit;
}

if ($fim <= $inicio) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'A distância final deve ser maior que a inicial']);
    exit;
}

if ($valor < 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'O valor não pode ser negativo']);
    exit;
}

// Conecta ao banco de dados
$database = new Database();
$db = $database->getConnection();

try {
    // Verifica se já existe uma faixa que se sobrepõe (excluindo a própria faixa sendo editada)
    $stmt = $db->prepare("SELECT id FROM faixas_distancia WHERE 
        ((inicio <= :fim AND fim >= :inicio) OR (inicio <= :inicio AND fim >= :fim)) 
        AND id != :id AND ativo = 1");
    $stmt->bindParam(':inicio', $inicio);
    $stmt->bindParam(':fim', $fim);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Já existe uma faixa que se sobrepõe a esta']);
        exit;
    }

    // Atualiza a faixa
    $stmt = $db->prepare("UPDATE faixas_distancia SET inicio = :inicio, fim = :fim, valor = :valor WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':inicio', $inicio);
    $stmt->bindParam(':fim', $fim);
    $stmt->bindParam(':valor', $valor);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Faixa atualizada com sucesso']);
    } else {
        throw new Exception('Erro ao atualizar faixa');
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar faixa: ' . $e->getMessage()]);
}
