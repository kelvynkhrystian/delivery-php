<?php
session_start();
require_once '../config/database.php';

// Verificar se o usuário está logado como admin
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso não autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Receber os dados da subscription
$subscription = json_decode(file_get_contents('php://input'), true);
$admin_id = $_SESSION['admin_id'];

try {
    // Verificar se já existe uma subscription para este admin
    $query = "SELECT id FROM push_subscriptions WHERE admin_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$admin_id]);
    
    if ($stmt->rowCount() > 0) {
        // Atualizar subscription existente
        $query = "UPDATE push_subscriptions SET subscription = ?, updated_at = NOW() WHERE admin_id = ?";
    } else {
        // Criar nova subscription
        $query = "INSERT INTO push_subscriptions (admin_id, subscription, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute([$admin_id, json_encode($subscription)]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao salvar subscription']);
}
