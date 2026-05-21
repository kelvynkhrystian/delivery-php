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
    $required = ['id', 'nome', 'cep', 'rua', 'numero', 'bairro', 'cidade', 'estado'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo {$field} é obrigatório");
        }
    }
    
    // Verifica se o endereço pertence ao usuário
    $query = "SELECT id FROM enderecos_usuario WHERE id = ? AND usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data['id'], $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Endereço não encontrado');
    }
    
    // Atualiza o endereço
    $query = "UPDATE enderecos_usuario SET 
        nome = :nome,
        cep = :cep,
        rua = :rua,
        numero = :numero,
        complemento = :complemento,
        bairro = :bairro,
        cidade = :cidade,
        estado = :estado
        WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'nome' => $data['nome'],
        'cep' => $data['cep'],
        'rua' => $data['rua'],
        'numero' => $data['numero'],
        'complemento' => $data['complemento'] ?? null,
        'bairro' => $data['bairro'],
        'cidade' => $data['cidade'],
        'estado' => $data['estado'],
        'id' => $data['id']
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
