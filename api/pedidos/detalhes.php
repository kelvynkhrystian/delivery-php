<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

session_start();
if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do pedido não fornecido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar informações do pedido
    $query = "SELECT p.*, u.nome as cliente_nome, u.telefone as cliente_telefone
              FROM pedidos p
              LEFT JOIN usuarios u ON p.usuario_id = u.id
              WHERE p.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
        exit;
    }
    
    // Buscar itens do pedido com seus complementos
    $query = "SELECT pi.*, p.nome, pc.complemento_nome, pc.opcao_nome, pc.opcao_preco
              FROM pedido_items pi
              LEFT JOIN produtos p ON pi.produto_id = p.id
              LEFT JOIN pedido_complementos pc ON pc.pedido_item_id = pi.id
              WHERE pi.pedido_id = ?
              ORDER BY pi.id, pc.id";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    
    $items = [];
    $item_atual = null;
    $subtotal = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!$item_atual || $item_atual['id'] !== $row['id']) {
            if ($item_atual) {
                // Calcular o total do item antes de adicioná-lo ao array
                $item_subtotal = $item_atual['quantidade'] * $item_atual['preco_unitario'];
                $complementos_total = 0;
                foreach ($item_atual['complementos'] as $complemento) {
                    $complementos_total += $complemento['opcao_preco'] * $item_atual['quantidade'];
                }
                $item_atual['total_item'] = $item_subtotal + $complementos_total;
                $subtotal += $item_atual['total_item'];
                $items[] = $item_atual;
            }
            $item_atual = [
                'id' => $row['id'],
                'nome' => $row['nome'],
                'quantidade' => $row['quantidade'],
                'preco_unitario' => (float)$row['preco_unitario'],
                'complementos' => []
            ];
        }
        
        if ($row['complemento_nome']) {
            $item_atual['complementos'][] = [
                'complemento_nome' => $row['complemento_nome'],
                'opcao_nome' => $row['opcao_nome'],
                'opcao_preco' => (float)$row['opcao_preco']
            ];
        }
    }
    
    if ($item_atual) {
        // Calcular o total do último item
        $item_subtotal = $item_atual['quantidade'] * $item_atual['preco_unitario'];
        $complementos_total = 0;
        foreach ($item_atual['complementos'] as $complemento) {
            $complementos_total += $complemento['opcao_preco'] * $item_atual['quantidade'];
        }
        $item_atual['total_item'] = $item_subtotal + $complementos_total;
        $subtotal += $item_atual['total_item'];
        $items[] = $item_atual;
    }
    
    $response = [
        'success' => true,
        'pedido' => [
            'id' => $pedido['id'],
            'cliente_nome' => $pedido['cliente_nome'],
            'cliente_telefone' => $pedido['cliente_telefone'],
            'endereco_entrega' => $pedido['endereco_entrega'],
            'status' => $pedido['status'],
            'subtotal' => $subtotal,
            'taxa_entrega' => 5.00,
            'total' => (float)$pedido['valor_total'],
            'forma_pagamento' => $pedido['forma_pagamento'],
            'precisa_troco' => (bool)$pedido['precisa_troco'],
            'troco_para' => $pedido['precisa_troco'] ? (float)$pedido['troco_para'] : null,
            'items' => $items
        ]
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar detalhes do pedido: ' . $e->getMessage()
    ]);
}
