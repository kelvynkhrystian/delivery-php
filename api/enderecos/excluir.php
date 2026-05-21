<?php
session_start();
require_once '../../config/database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Recebe o ID do endereço
$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verifica se o endereço pertence ao usuário
    $query = "SELECT padrao FROM enderecos WHERE id = ? AND usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id, $_SESSION['usuario']['id']]);
    $endereco = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$endereco) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endereço não encontrado']);
        exit;
    }

    // Se for o endereço padrão, verifica se tem outro endereço para tornar padrão
    if ($endereco['padrao']) {
        $query = "SELECT id FROM enderecos WHERE usuario_id = ? AND id != ? LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['usuario']['id'], $id]);
        $outro_endereco = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($outro_endereco) {
            $query = "UPDATE enderecos SET padrao = 1 WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$outro_endereco['id']]);
        }
    }

    // Exclui o endereço
    $query = "DELETE FROM enderecos WHERE id = ? AND usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id, $_SESSION['usuario']['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Endereço excluído com sucesso'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir endereço: ' . $e->getMessage()
    ]);
}
