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
    
    // Inicia a transação
    $db->beginTransaction();
    
    // Remove o status principal de todos os endereços do usuário
    $stmt = $db->prepare("
        UPDATE enderecos_usuario 
        SET principal = 0 
        WHERE usuario_id = ?
    ");
    $stmt->execute([$_SESSION['usuario']['id']]);
    
    // Define o endereço selecionado como principal
    $stmt = $db->prepare("
        UPDATE enderecos_usuario 
        SET principal = 1 
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['usuario']['id']]);
    
    $db->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao definir endereço como principal']);
}
