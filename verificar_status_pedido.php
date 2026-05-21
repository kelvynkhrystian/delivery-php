<?php
session_start();
require_once 'config/database.php';
require_once 'config/firebase-config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso não autorizado']);
    exit;
}

// Verifica se o ID do pedido foi fornecido
if (!isset($_GET['pedido_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do pedido não fornecido']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$pedido_id = intval($_GET['pedido_id']);
$usuario_id = $_SESSION['usuario_id'];

try {
    // Buscar status do pedido e informações do cliente
    $query = "SELECT p.status, p.usuario_id, u.nome as cliente_nome 
              FROM pedidos p 
              JOIN usuarios u ON p.usuario_id = u.id 
              WHERE p.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$pedido_id]);
    
    if ($stmt->rowCount() > 0) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Buscar token do cliente para enviar notificação
        $queryToken = "SELECT token FROM firebase_tokens WHERE user_id = ? AND user_type = 'client' ORDER BY updated_at DESC LIMIT 1";
        $stmtToken = $db->prepare($queryToken);
        $stmtToken->execute([$result['usuario_id']]);
        
        if ($stmtToken->rowCount() > 0) {
            $tokenData = $stmtToken->fetch(PDO::FETCH_ASSOC);
            $firebase = new FirebaseNotification();
            
            // Preparar mensagem baseada no status
            $mensagem = "Seu pedido #$pedido_id foi atualizado para: " . $result['status'];
            
            // Enviar notificação
            $firebase->sendOrderStatusNotification(
                $tokenData['token'],
                $pedido_id,
                $result['status'],
                $mensagem
            );
        }
        
        echo json_encode([
            'success' => true,
            'status' => $result['status']
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Pedido não encontrado']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao verificar status do pedido']);
}
