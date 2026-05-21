<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

try {
    if (isset($data['id']) && !empty($data['id'])) {
        // Atualizar produto existente
        $query = "UPDATE produtos SET 
                    nome = :nome,
                    descricao = :descricao,
                    preco = :preco,
                    imagem = :imagem,
                    categoria = :categoria
                 WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data['id']);
    } else {
        // Inserir novo produto
        $query = "INSERT INTO produtos (nome, descricao, preco, imagem, categoria) 
                 VALUES (:nome, :descricao, :preco, :imagem, :categoria)";
        
        $stmt = $db->prepare($query);
    }

    $stmt->bindParam(':nome', $data['nome']);
    $stmt->bindParam(':descricao', $data['descricao']);
    $stmt->bindParam(':preco', $data['preco']);
    $stmt->bindParam(':imagem', $data['imagem']);
    $stmt->bindParam(':categoria', $data['categoria']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar produto']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
