<?php
header('Content-Type: application/json');
require_once '../includes/conexao.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['token']) || !isset($data['user_type']) || !isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados incompletos']);
    exit;
}

$token = $data['token'];
$userType = $data['user_type']; // 'client' ou 'admin'
$userId = $data['user_id'];

try {
    // Verifica se já existe um token para este usuário
    $stmt = $pdo->prepare("SELECT id FROM firebase_tokens WHERE user_id = ? AND user_type = ?");
    $stmt->execute([$userId, $userType]);
    
    if ($stmt->rowCount() > 0) {
        // Atualiza o token existente
        $stmt = $pdo->prepare("UPDATE firebase_tokens SET token = ?, updated_at = NOW() WHERE user_id = ? AND user_type = ?");
        $stmt->execute([$token, $userId, $userType]);
    } else {
        // Insere novo token
        $stmt = $pdo->prepare("INSERT INTO firebase_tokens (user_id, user_type, token, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$userId, $userType, $token]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao salvar token']);
}
?>
