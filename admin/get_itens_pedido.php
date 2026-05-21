<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

if (!isset($_GET['pedido_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID do pedido não fornecido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Busca os itens do pedido com complementos e observações
    $query = "SELECT i.*, p.nome, p.descricao, 
              pc.complemento_nome, pc.opcao_nome, pc.opcao_preco
              FROM itens_pedido i 
              JOIN produtos p ON i.produto_id = p.id 
              LEFT JOIN pedido_complementos pc ON i.id = pc.pedido_item_id
              WHERE i.pedido_id = ?
              ORDER BY i.id, pc.id";

    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['pedido_id']]);
    $itens_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organizar os itens com seus complementos
    $itens = [];
    foreach ($itens_raw as $row) {
        $item_id = $row['id'];
        
        if (!isset($itens[$item_id])) {
            $itens[$item_id] = [
                'id' => $row['id'],
                'produto_id' => $row['produto_id'],
                'nome' => $row['nome'],
                'descricao' => $row['descricao'],
                'quantidade' => $row['quantidade'],
                'preco_unitario' => $row['preco_unitario'],
                'observacoes' => $row['observacoes'],
                'complementos' => []
            ];
        }
        
        if ($row['complemento_nome']) {
            $itens[$item_id]['complementos'][] = [
                'complemento_nome' => $row['complemento_nome'],
                'opcao_nome' => $row['opcao_nome'],
                'opcao_preco' => $row['opcao_preco']
            ];
        }
    }

    // Buscar configuração do tipo de entrega
    $query = "SELECT tipo_entrega FROM configuracoes LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    // Por enquanto, pegar a taxa do primeiro bairro ativo
    $query = "SELECT valor FROM bairros_entrega WHERE ativo = 1 ORDER BY id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $bairro = $stmt->fetch(PDO::FETCH_ASSOC);
    $taxa_entrega = $bairro ? $bairro['valor'] : 0;

    $response = [
        'itens' => array_values($itens),
        'taxa_entrega' => $taxa_entrega
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao buscar dados: ' . $e->getMessage()]);
}
