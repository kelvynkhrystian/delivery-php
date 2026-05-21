<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "DELETE FROM cupons WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_POST['id']);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
