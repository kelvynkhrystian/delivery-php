<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['admin'])) {
        throw new Exception('Acesso não autorizado');
    }

    $database = new Database();
    $db = $database->getConnection();

    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $ativo = $_POST['ativo'];
    $id = $_POST['id'] ?? null;

    if (empty($nome)) {
        throw new Exception('Nome da categoria é obrigatório');
    }

    // Verifica se já existe uma categoria com este nome
    $query = "SELECT id FROM categorias WHERE nome = ? AND id != ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$nome, $id ?? 0]);
    if ($stmt->fetch()) {
        throw new Exception('Já existe uma categoria com este nome');
    }

    if ($id) {
        // Atualizar categoria existente
        $query = "UPDATE categorias SET nome = ?, descricao = ?, ativo = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$nome, $descricao, $ativo, $id]);
        $message = 'Categoria atualizada com sucesso!';
    } else {
        // Inserir nova categoria
        $query = "INSERT INTO categorias (nome, descricao, ativo) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$nome, $descricao, $ativo]);
        $message = 'Categoria criada com sucesso!';
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
