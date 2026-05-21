<?php
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// Log para debug
error_log('Sessão completa: ' . print_r($_SESSION, true));
error_log('Usuário na sessão: ' . print_r($_SESSION['usuario'] ?? 'não definido', true));
error_log('JSON recebido: ' . file_get_contents('php://input'));

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não está logado'
    ]);
    exit;
}

// Recebe os dados do pedido
$data = json_decode(file_get_contents('php://input'), true);

// Log para debug
error_log('Dados decodificados: ' . print_r($data, true));

if (!isset($data['items']) || empty($data['items'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Carrinho vazio'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Inicia a transação
    $db->beginTransaction();
    
    // Insere o pedido
    $query = "INSERT INTO pedidos (usuario_id, total, status, created_at) VALUES (?, ?, 'pendente', NOW())";
    $stmt = $db->prepare($query);
    
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id'])) {
        throw new Exception('Usuário não está logado corretamente. Dados da sessão: ' . print_r($_SESSION, true));
    }
    
    $usuario_id = $_SESSION['usuario']['id'];
    error_log('ID do usuário: ' . $usuario_id);
    
    $stmt->execute([$usuario_id, $data['total']]);
    $pedido_id = $db->lastInsertId();
    
    // Insere os itens do pedido
    $query = "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, observacoes) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    foreach ($data['items'] as $item) {
        // Log para debug
        error_log('Processando item: ' . print_r($item, true));
        
        $stmt->execute([
            $pedido_id,
            $item['produto_id'],
            $item['quantidade'],
            $item['preco_unitario'],
            $item['observacoes'] ?? ''
        ]);
        $item_pedido_id = $db->lastInsertId();

        // Salva os complementos do item
        if (!empty($item['complementos'])) {
            error_log('Salvando complementos: ' . print_r($item['complementos'], true));
            
            $query = "INSERT INTO pedido_complementos (pedido_id, produto_id, complemento_id, opcao_id) VALUES (?, ?, ?, ?)";
            $stmt_comp = $db->prepare($query);
            
            foreach ($item['complementos'] as $complemento) {
                error_log('Salvando complemento: ' . print_r($complemento, true));
                
                $stmt_comp->execute([
                    $pedido_id,
                    $item['produto_id'],
                    $complemento['complemento_id'],
                    $complemento['opcao_id']
                ]);
            }
        }
    }
    
    // Confirma a transação
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pedido criado com sucesso',
        'pedido_id' => $pedido_id
    ]);
} catch (Exception $e) {
    // Em caso de erro, desfaz a transação
    if (isset($db)) {
        $db->rollBack();
    }
    
    error_log('Erro ao criar pedido: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao criar pedido: ' . $e->getMessage()
    ]);
}
