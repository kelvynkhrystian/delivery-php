<?php
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
    // Debug - Log todos os dados recebidos
    error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
    error_log('POST data: ' . print_r($_POST, true));
    error_log('RAW input: ' . file_get_contents('php://input'));
    error_log('Content-Type: ' . $_SERVER['CONTENT_TYPE']);

    // Verifica se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Verifica se os dados necessários foram recebidos
    if (!isset($_POST['pedido_id']) || !isset($_POST['novo_status'])) {
        error_log('Dados faltando - pedido_id: ' . (isset($_POST['pedido_id']) ? 'sim' : 'não'));
        error_log('Dados faltando - novo_status: ' . (isset($_POST['novo_status']) ? 'sim' : 'não'));
        throw new Exception('Dados incompletos');
    }

    $pedido_id = intval($_POST['pedido_id']);
    $status = $_POST['novo_status'];

    error_log('Dados processados - pedido_id: ' . $pedido_id);
    error_log('Dados processados - status: ' . $status);

    // Status válidos
    $status_validos = ['pendente', 'confirmado', 'em_preparo', 'em_entrega', 'entregue', 'cancelado'];

    if (!in_array($status, $status_validos)) {
        throw new Exception('Status inválido');
    }

    // Conecta ao banco de dados
    $database = new Database();
    $db = $database->getConnection();

    // Atualiza o status do pedido
    $query = "UPDATE pedidos SET status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$status, $pedido_id]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Status atualizado com sucesso'
        ]);
    } else {
        error_log('Erro no execute - errorInfo: ' . print_r($stmt->errorInfo(), true));
        throw new Exception('Erro ao atualizar status');
    }

} catch (Exception $e) {
    error_log('Erro na atualização: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
