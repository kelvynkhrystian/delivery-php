<?php
require_once '../config/database.php';
require_once '../includes/verificar_admin.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->query("SELECT * FROM configuracoes ORDER BY id DESC LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($config) {
        echo json_encode($config);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Nenhuma configuração encontrada']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar configurações: ' . $e->getMessage()]);
}
