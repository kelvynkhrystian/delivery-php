<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['admin'])) {
        throw new Exception('Acesso não autorizado');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id'])) {
        throw new Exception('ID da categoria não fornecido');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Verifica se existem produtos usando esta categoria
    $query = "SELECT COUNT(*) as total FROM produtos WHERE categoria_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        throw new Exception('Não é possível excluir esta categoria pois existem produtos vinculados a ela');
    }

    // Exclui a categoria
    $query = "DELETE FROM categorias WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Categoria excluída com sucesso!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
