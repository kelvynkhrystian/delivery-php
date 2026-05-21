<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do endereço não fornecido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("
        SELECT * FROM enderecos_usuario 
        WHERE id = ? AND usuario_id = ?
    ");
    
    $stmt->execute([$_GET['id'], $_SESSION['usuario']['id']]);
    $endereco = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$endereco) {
        http_response_code(404);
        echo json_encode(['error' => 'Endereço não encontrado']);
        exit;
    }
    
    echo json_encode($endereco);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar endereço']);
}
