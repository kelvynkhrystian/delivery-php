<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID do produto não fornecido']);
        exit;
    }
    
    try {
        $db->beginTransaction();

        // 1. Primeiro busca os dados do produto
        $query = "SELECT * FROM produtos WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produto) {
            throw new Exception('Produto não encontrado');
        }

        // 2. Cria um backup do produto na tabela produtos_deletados se ela existir
        $checkTable = $db->query("SHOW TABLES LIKE 'produtos_deletados'");
        if ($checkTable->rowCount() > 0) {
            $query = "INSERT INTO produtos_deletados (produto_id, nome, descricao, preco, categoria_id, imagem) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $produto['id'],
                $produto['nome'],
                $produto['descricao'],
                $produto['preco'],
                $produto['categoria_id'],
                $produto['imagem']
            ]);
        }

        // 3. Atualiza os itens_pedido para remover a referência ao produto
        $query = "UPDATE itens_pedido SET produto_id = NULL, produto_nome = ? WHERE produto_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$produto['nome'], $id]);

        // 4. Agora podemos excluir o produto com segurança
        $query = "DELETE FROM produtos WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        // 5. Se houver imagem, exclui ela
        if (!empty($produto['imagem']) && file_exists('../' . $produto['imagem'])) {
            unlink('../' . $produto['imagem']);
        }

        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Produto excluído com sucesso'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao excluir produto: ' . $e->getMessage()
        ]);
    }
}
?>
