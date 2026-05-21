<?php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$conexao = $database->getConnection();

if (!$conexao) {
    // Limpa qualquer saída anterior
    while (ob_get_level()) ob_end_clean();
    
    // Define o status code e cabeçalho JSON
    http_response_code(500);
    header('Content-Type: application/json');
    
    // Envia resposta de erro em JSON
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão com o banco de dados'
    ]);
    exit;
}
