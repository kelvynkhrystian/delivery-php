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
    
    // Verifica se o endereço pertence ao usuário
    $stmt = $db->prepare("
        SELECT principal FROM enderecos_usuario 
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['usuario']['id']]);
    $endereco = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$endereco) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Endereço não encontrado']);
        exit;
    }
    
    // Se for o endereço principal, não permite excluir
    if ($endereco['principal']) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Não é possível excluir o endereço principal']);
        exit;
    }
    
    // Exclui o endereço
    $stmt = $db->prepare("
        DELETE FROM enderecos_usuario 
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['usuario']['id']]);
    
    $db->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao excluir endereço']);
}
