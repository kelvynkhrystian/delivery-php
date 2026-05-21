<?php
session_start();
require_once '../config/database.php';

// Log inicial
error_log("Iniciando salvar_bairro.php");

header('Content-Type: application/json');

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    error_log("Usuário não está logado como admin");
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Recebe o JSON do corpo da requisição
$json = file_get_contents('php://input');
error_log("JSON recebido: " . $json);

$data = json_decode($json, true);
error_log("Dados decodificados: " . print_r($data, true));

if (!isset($data['nome']) || !isset($data['valor'])) {
    error_log("Campos obrigatórios não preenchidos - nome: " . ($data['nome'] ?? 'não definido') . ", valor: " . ($data['valor'] ?? 'não definido'));
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Campos obrigatórios não preenchidos']);
    exit;
}

$nome = $data['nome'];
$valor = $data['valor'];

try {
    $database = new Database();
    $db = $database->getConnection();
    error_log("Conexão com banco de dados estabelecida");
    
    // Verifica se o bairro já existe
    $stmt = $db->prepare("SELECT COUNT(*) FROM bairros_entrega WHERE nome = ?");
    $stmt->execute([$nome]);
    $count = $stmt->fetchColumn();
    error_log("Verificação de bairro existente: " . ($count > 0 ? 'já existe' : 'não existe'));
    
    if ($count > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Já existe um bairro com este nome']);
        exit;
    }
    
    // Insere o novo bairro
    $stmt = $db->prepare("INSERT INTO bairros_entrega (nome, valor, tempo, ativo) VALUES (?, ?, NULL, true)");
    error_log("Query preparada: INSERT INTO bairros_entrega (nome, valor, tempo, ativo) VALUES ('{$nome}', '{$valor}', NULL, true)");
    
    $stmt->execute([$nome, $valor]);
    error_log("Bairro inserido com sucesso!");
    
    echo json_encode(['success' => true, 'message' => 'Bairro salvo com sucesso']);
} catch (PDOException $e) {
    error_log("Erro no PDO: " . $e->getMessage());
    error_log("Código do erro: " . $e->getCode());
    error_log("SQL State: " . $e->errorInfo[0]);
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao salvar bairro: ' . $e->getMessage()
    ]);
}
