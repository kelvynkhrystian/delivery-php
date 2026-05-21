<?php
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'admin') {
    echo json_encode([
        'error' => 'Não autorizado'
    ]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Pega o timestamp da última verificação da sessão
$ultima_verificacao = isset($_SESSION['ultima_verificacao_pedidos']) 
    ? $_SESSION['ultima_verificacao_pedidos'] 
    : date('Y-m-d H:i:s', strtotime('-1 minute'));

// Busca pedidos novos desde a última verificação
$query = "SELECT COUNT(*) as novos_pedidos FROM pedidos WHERE created_at > ?";
$stmt = $db->prepare($query);
$stmt->execute([$ultima_verificacao]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// Busca total de pedidos pendentes
$query = "SELECT COUNT(*) as total_pedidos FROM pedidos WHERE status = 'pendente'";
$stmt = $db->prepare($query);
$stmt->execute();
$total = $stmt->fetch(PDO::FETCH_ASSOC);

// Atualiza o timestamp da última verificação
$_SESSION['ultima_verificacao_pedidos'] = date('Y-m-d H:i:s');

echo json_encode([
    'novos_pedidos' => (int)$result['novos_pedidos'],
    'total_pedidos' => (int)$total['total_pedidos']
]);
