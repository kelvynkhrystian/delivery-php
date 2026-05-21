<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Recebe os IDs dos produtos do carrinho
    $data = json_decode(file_get_contents('php://input'), true);
    $produtos = $data['produtos'] ?? [];
    
    $response = [];
    
    if (!empty($produtos)) {
        $placeholders = str_repeat('?,', count($produtos) - 1) . '?';
        $query = "SELECT id, nome, preco, ativo FROM produtos WHERE id IN ($placeholders) AND ativo = 1";
        
        $stmt = $db->prepare($query);
        $stmt->execute(array_column($produtos, 'id'));
        
        $produtosValidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Criar um mapa de produtos válidos por ID
        $produtosValidosMap = array_column($produtosValidos, null, 'id');
        
        // Validar cada produto do carrinho
        foreach ($produtos as $produto) {
            $id = $produto['id'];
            if (isset($produtosValidosMap[$id])) {
                // Produto existe e está ativo
                $response[] = [
                    'id' => $id,
                    'valido' => true,
                    'preco_atual' => $produtosValidosMap[$id]['preco']
                ];
            } else {
                // Produto não existe ou está inativo
                $response[] = [
                    'id' => $id,
                    'valido' => false,
                    'mensagem' => 'Produto não disponível'
                ];
            }
        }
    }
    
    echo json_encode(['success' => true, 'produtos' => $response]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
