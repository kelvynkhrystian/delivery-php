<?php
session_start();
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Busca as formas de pagamento ativas
    $query = "SELECT id, nome FROM formas_pagamento WHERE ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $formas_pagamento = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'formas_pagamento' => $formas_pagamento
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar formas de pagamento: ' . $e->getMessage()
    ]);
}
