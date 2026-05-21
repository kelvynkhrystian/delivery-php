<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    exit('Acesso não autorizado');
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('ID não fornecido');
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Busca o produto
    $stmt = $db->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        http_response_code(404);
        exit('Produto não encontrado');
    }

    // Busca os complementos do produto
    $stmt = $db->prepare("
        SELECT c.*, co.id as opcao_id, co.nome as opcao_nome, co.preco as opcao_preco 
        FROM complementos c 
        INNER JOIN produto_complementos pc ON c.id = pc.complemento_id 
        LEFT JOIN complemento_opcoes co ON c.id = co.complemento_id 
        WHERE pc.produto_id = ?
        ORDER BY c.id, co.id
    ");
    $stmt->execute([$_GET['id']]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organiza os complementos e suas opções
    $complementos = [];
    foreach ($resultados as $row) {
        $complemento_id = $row['id'];
        if (!isset($complementos[$complemento_id])) {
            $complementos[$complemento_id] = [
                'id' => $row['id'],
                'nome' => $row['nome'],
                'descricao' => $row['descricao'],
                'min_escolhas' => $row['min_escolhas'],
                'max_escolhas' => $row['max_escolhas'],
                'obrigatorio' => $row['obrigatorio'],
                'opcoes' => []
            ];
        }
        if ($row['opcao_id']) {
            $complementos[$complemento_id]['opcoes'][] = [
                'id' => $row['opcao_id'],
                'nome' => $row['opcao_nome'],
                'preco' => $row['opcao_preco']
            ];
        }
    }

    // Adiciona os complementos ao produto
    $produto['complementos'] = array_values($complementos);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'produto' => $produto
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
