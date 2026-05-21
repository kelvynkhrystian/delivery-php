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
    
    // Validações básicas
    if (empty($data['nome'])) {
        throw new Exception('Nome é obrigatório');
    }
    if (empty($data['email'])) {
        throw new Exception('Email é obrigatório');
    }
    if (empty($data['telefone'])) {
        throw new Exception('Telefone é obrigatório');
    }
    
    // Verifica se o email já está em uso por outro usuário
    $query = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data['email'], $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        throw new Exception('Este email já está em uso');
    }
    
    // Atualiza o perfil
    $query = "UPDATE usuarios SET 
        nome = :nome,
        email = :email,
        telefone = :telefone
        WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'nome' => $data['nome'],
        'email' => $data['email'],
        'telefone' => $data['telefone'],
        'id' => $_SESSION['user_id']
    ]);
    
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
