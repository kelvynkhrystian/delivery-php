<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID do pedido não fornecido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Primeiro, buscar os dados do pedido
    $query = "SELECT p.*, u.nome, u.telefone, 
              CONCAT(e.logradouro, ', ', e.numero, 
                    CASE WHEN e.complemento IS NOT NULL AND e.complemento != '' 
                         THEN CONCAT(' - ', e.complemento) 
                         ELSE '' 
                    END,
                    ' - ', e.bairro) as endereco,
              fp.nome as forma_pagamento,
              c.codigo as cupom_codigo,
              c.tipo as cupom_tipo,
              c.valor as cupom_valor,
              CASE 
                  WHEN c.tipo IN ('frete_valor', 'frete_porcentagem') THEN 1 
                  ELSE 0 
              END as cupom_tipo_frete
              FROM pedidos p
              LEFT JOIN usuarios u ON p.usuario_id = u.id
              LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
              LEFT JOIN enderecos_usuario e ON p.endereco_id = e.id
              LEFT JOIN cupons c ON p.cupom_id = c.id
              WHERE p.id = ?";

    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        throw new Exception('Pedido não encontrado');
    }

    // Buscar itens do pedido
    $queryItens = "SELECT * FROM itens_pedido WHERE pedido_id = ?";
    $stmtItens = $db->prepare($queryItens);
    $stmtItens->execute([$_GET['id']]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
    $pedido['itens'] = $itens;

    // Calcular subtotal
    $subtotal = 0;
    foreach ($itens as $item) {
        $subtotal += $item['preco_unitario'] * $item['quantidade'];
    }
    $pedido['subtotal'] = $subtotal;

    // Se houver cupom, calcular o desconto
    if ($pedido['cupom_id']) {
        $desconto = 0;
        $taxa_entrega = floatval($pedido['taxa_entrega']);
        $valor_cupom = floatval($pedido['cupom_valor']);
        
        switch ($pedido['cupom_tipo']) {
            case 'valor_total':
                $desconto = $valor_cupom;
                break;
            case 'porcentagem_total':
                $desconto = ($subtotal * $valor_cupom) / 100;
                break;
            case 'frete_valor':
                $desconto = min($valor_cupom, $taxa_entrega);
                break;
            case 'porcentagem_frete': 
                $desconto = ($taxa_entrega * $valor_cupom) / 100;
                break;
        }
        $pedido['desconto_cupom'] = round($desconto, 2);
    }

    // Calcular total final
    $total = $subtotal + floatval($pedido['taxa_entrega']) - floatval($pedido['desconto_cupom'] ?? 0);
    $pedido['total'] = round($total, 2);

    header('Content-Type: application/json');
    echo json_encode($pedido);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao buscar pedido: ' . $e->getMessage()]);
}
