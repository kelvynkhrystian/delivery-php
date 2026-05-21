<?php
require_once '../../../config/database.php';

header('Content-Type: application/json');

// Pega o último ID conhecido
$ultimo_id = isset($_GET['ultimo_id']) ? intval($_GET['ultimo_id']) : 0;

// Conecta ao banco
$database = new Database();
$db = $database->getConnection();

// Busca pedidos mais recentes que o último ID
$query = "SELECT COUNT(*) as total FROM pedidos WHERE id > ? AND status = 'pendente'";
$stmt = $db->prepare($query);
$stmt->execute([$ultimo_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// Pega o ID do pedido mais recente
$query_ultimo = "SELECT MAX(id) as ultimo_id FROM pedidos";
$stmt_ultimo = $db->prepare($query_ultimo);
$stmt_ultimo->execute();
$ultimo = $stmt_ultimo->fetch(PDO::FETCH_ASSOC);

// Retorna os dados
echo json_encode([
    'success' => true,
    'novos_pedidos' => intval($result['total']),
    'ultimo_id' => intval($ultimo['ultimo_id'] ?? $ultimo_id)
]);
