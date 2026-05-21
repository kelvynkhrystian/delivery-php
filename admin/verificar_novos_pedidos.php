<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso não autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Buscar o ID do último pedido
    $query_ultimo = "SELECT MAX(id) as ultimo_id FROM pedidos";
    $stmt_ultimo = $db->query($query_ultimo);
    $ultimo_pedido = $stmt_ultimo->fetch(PDO::FETCH_ASSOC);

    // Buscar pedidos pendentes com informações detalhadas
    $query = "SELECT p.*, u.nome as cliente_nome, u.telefone as cliente_telefone,
                     COUNT(ip.id) as total_itens,
                     GROUP_CONCAT(CONCAT(pr.nome, ' (', ip.quantidade, ')') SEPARATOR ', ') as itens,
                     fp.nome as forma_pagamento
              FROM pedidos p
              LEFT JOIN usuarios u ON p.usuario_id = u.id
              LEFT JOIN itens_pedido ip ON p.id = ip.pedido_id
              LEFT JOIN produtos pr ON ip.produto_id = pr.id
              LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
              WHERE LOWER(p.status) = 'pendente'
              GROUP BY p.id
              ORDER BY p.created_at DESC";
              
    $stmt = $db->query($query);
    $novos_pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total de pedidos (todos os status)
    $query_total = "SELECT COUNT(*) as total FROM pedidos";
    $stmt_total = $db->query($query_total);
    $total = $stmt_total->fetch(PDO::FETCH_ASSOC);
    
    // Contar total de pedidos pendentes
    $query_pendentes = "SELECT COUNT(*) as total FROM pedidos WHERE LOWER(status) = 'pendente'";
    $stmt_pendentes = $db->query($query_pendentes);
    $total_pendentes = $stmt_pendentes->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'ultimo_pedido' => $ultimo_pedido['ultimo_id'],
        'pedidos_pendentes' => $novos_pedidos,
        'total_pedidos' => $total['total'],
        'total_pendentes' => $total_pendentes['total']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
