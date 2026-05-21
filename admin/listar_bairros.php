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
    $stmt = $db->query("SELECT nome, valor FROM bairros_entrega WHERE ativo = 1 ORDER BY nome");
    $bairros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($bairros);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([]);
}
