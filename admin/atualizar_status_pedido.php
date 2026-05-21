<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autorizado'
    ]);
    exit;
}

// Recebe os dados do pedido
$dados = json_decode(file_get_contents('php://input'), true);

// Log para debug
error_log('Dados recebidos: ' . print_r($dados, true));

if (!$dados || !isset($dados['pedido_id']) || !isset($dados['status'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Dados inválidos',
        'dados' => $dados
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Log do SQL e parâmetros
    error_log('SQL: UPDATE pedidos SET status = :status WHERE id = :pedido_id');
    error_log('Parâmetros: ' . print_r(['status' => $dados['status'], 'pedido_id' => $dados['pedido_id']], true));

    // Atualiza o status do pedido
    $query = "UPDATE pedidos SET status = :status WHERE id = :pedido_id";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        'status' => $dados['status'],
        'pedido_id' => $dados['pedido_id']
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Status atualizado com sucesso'
        ]);
    } else {
        error_log('Nenhuma linha afetada. ID do pedido: ' . $dados['pedido_id']);
        throw new Exception('Pedido não encontrado ou status igual ao atual');
    }

} catch (Exception $e) {
    error_log('Erro ao atualizar status: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
