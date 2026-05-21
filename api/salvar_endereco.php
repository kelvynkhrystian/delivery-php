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
    $required = ['nome', 'cep', 'rua', 'numero', 'bairro', 'cidade', 'estado'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo {$field} é obrigatório");
        }
    }
    
    // Se for endereço padrão, remove o padrão dos outros
    if (!empty($data['padrao'])) {
        $query = "UPDATE enderecos_usuario SET padrao = 0 WHERE usuario_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    // Insere o novo endereço
    $query = "INSERT INTO enderecos_usuario (
        usuario_id, nome, cep, rua, numero, complemento, 
        bairro, cidade, estado, padrao
    ) VALUES (
        :usuario_id, :nome, :cep, :rua, :numero, :complemento,
        :bairro, :cidade, :estado, :padrao
    )";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'usuario_id' => $_SESSION['user_id'],
        'nome' => $data['nome'],
        'cep' => $data['cep'],
        'rua' => $data['rua'],
        'numero' => $data['numero'],
        'complemento' => $data['complemento'] ?? null,
        'bairro' => $data['bairro'],
        'cidade' => $data['cidade'],
        'estado' => $data['estado'],
        'padrao' => !empty($data['padrao'])
    ]);
    
    $endereco_id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'endereco_id' => $endereco_id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
