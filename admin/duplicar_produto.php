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
        // Busca o produto original
        $query = "SELECT * FROM produtos WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$produto) {
            echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
            exit;
        }
        
        // Encontra o próximo número de cópia
        $nome_base = preg_replace('/ \(Cópia \d+\)$/', '', $produto['nome']);
        $query = "SELECT nome FROM produtos WHERE nome LIKE ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$nome_base . ' (Cópia %']);
        $copias = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $max_numero = 0;
        foreach ($copias as $copia) {
            if (preg_match('/\(Cópia (\d+)\)$/', $copia, $matches)) {
                $max_numero = max($max_numero, intval($matches[1]));
            }
        }
        $proximo_numero = $max_numero + 1;
        
        // Prepara o novo nome
        $novo_nome = $nome_base . ' (Cópia ' . $proximo_numero . ')';
        
        // Insere o novo produto
        $query = "INSERT INTO produtos (nome, descricao, preco, categoria_id, ativo, imagem) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $novo_nome,
            $produto['descricao'],
            $produto['preco'],
            $produto['categoria_id'],
            $produto['ativo'],
            $produto['imagem']
        ]);
        
        $novo_produto_id = $db->lastInsertId();
        
        // Busca os complementos do produto original
        $query = "SELECT c.* FROM complementos c
                 INNER JOIN produto_complementos pc ON c.id = pc.complemento_id
                 WHERE pc.produto_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $complementos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Para cada complemento, cria uma cópia
        foreach ($complementos as $complemento) {
            // Insere o novo complemento
            $query = "INSERT INTO complementos (nome, descricao, min_escolhas, max_escolhas, obrigatorio, ativo, ordem)
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $complemento['nome'],
                $complemento['descricao'],
                $complemento['min_escolhas'],
                $complemento['max_escolhas'],
                $complemento['obrigatorio'],
                $complemento['ativo'],
                $complemento['ordem']
            ]);
            
            $novo_complemento_id = $db->lastInsertId();
            
            // Vincula o novo complemento ao novo produto
            $query = "INSERT INTO produto_complementos (produto_id, complemento_id)
                     VALUES (?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$novo_produto_id, $novo_complemento_id]);
            
            // Busca e duplica as opções do complemento
            $query = "SELECT * FROM complemento_opcoes WHERE complemento_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$complemento['id']]);
            $opcoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($opcoes as $opcao) {
                $query = "INSERT INTO complemento_opcoes (complemento_id, nome, preco, ativo)
                         VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    $novo_complemento_id,
                    $opcao['nome'],
                    $opcao['preco'],
                    $opcao['ativo']
                ]);
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Produto duplicado com sucesso!',
            'novo_nome' => $novo_nome
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Erro ao duplicar produto: ' . $e->getMessage()
        ]);
    }
}
?>
