<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/verificar_admin.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Busca configuração atual do tema
    $stmt = $db->query("SELECT cor_tema FROM configuracoes ORDER BY id DESC LIMIT 1");
    
    if ($config = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            'success' => true,
            'cor_tema' => $config['cor_tema'] ?? '#8B5CF6'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'cor_tema' => '#8B5CF6'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'cor_tema' => '#8B5CF6'
    ]);
}
