<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->query("SELECT km_inicial, km_final, valor FROM faixas_distancia ORDER BY km_inicial");
    $faixas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($faixas);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([]);
}
