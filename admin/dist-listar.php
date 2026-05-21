<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Busca todas as faixas ativas ordenadas por início
    $stmt = $db->query("SELECT id, inicio, fim, valor FROM faixas_distancia WHERE ativo = 1 ORDER BY inicio ASC");
    $faixas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'faixas' => $faixas]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao listar faixas: ' . $e->getMessage()]);
}
