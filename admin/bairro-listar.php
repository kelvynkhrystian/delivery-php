<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Lista todos os bairros ativos
    $stmt = $db->prepare("SELECT nome, valor FROM bairros_entrega WHERE ativo = 1 ORDER BY nome");
    $stmt->execute();
    
    $bairros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'bairros' => $bairros]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
