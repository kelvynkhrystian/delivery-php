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

if (!isset($data['inicio']) || !isset($data['fim']) || !isset($data['valor'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$inicio = floatval($data['inicio']);
$fim = floatval($data['fim']);
$valor = floatval($data['valor']);

// Validações adicionais
if ($inicio >= $fim) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'O valor inicial deve ser menor que o valor final']);
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
    // Verifica se já existe uma faixa que se sobrepõe
    $stmt = $db->prepare("SELECT id FROM faixas_distancia WHERE 
        (inicio <= :fim AND fim >= :inicio) AND ativo = 1");
    $stmt->bindParam(':inicio', $inicio);
    $stmt->bindParam(':fim', $fim);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Já existe uma faixa de distância que se sobrepõe a esta']);
        exit;
    }

    // Insere a nova faixa
    $stmt = $db->prepare("INSERT INTO faixas_distancia (inicio, fim, valor, ativo) VALUES (:inicio, :fim, :valor, 1)");
    $stmt->bindParam(':inicio', $inicio);
    $stmt->bindParam(':fim', $fim);
    $stmt->bindParam(':valor', $valor);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Faixa de distância adicionada com sucesso']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao adicionar faixa de distância']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao processar a requisição: ' . $e->getMessage()]);
}
