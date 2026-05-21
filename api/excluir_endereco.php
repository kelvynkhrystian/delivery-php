<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Usuário não autenticado'
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['endereco_id'])) {
        throw new Exception('ID do endereço é obrigatório');
    }
    
    // Verifica se o endereço pertence ao usuário
    $query = "SELECT padrao FROM enderecos_usuario WHERE id = ? AND usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data['endereco_id'], $_SESSION['user_id']]);
    $endereco = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$endereco) {
        throw new Exception('Endereço não encontrado');
    }
    
    // Não permite excluir o endereço padrão se houver outros endereços
    if ($endereco['padrao']) {
        $query = "SELECT COUNT(*) FROM enderecos_usuario WHERE usuario_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        if ($stmt->fetchColumn() > 1) {
            throw new Exception('Não é possível excluir o endereço padrão. Defina outro endereço como padrão primeiro.');
        }
    }
    
    // Exclui o endereço
    $query = "DELETE FROM enderecos_usuario WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data['endereco_id']]);
    
    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
