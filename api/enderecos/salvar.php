<?php
session_start();
require_once '../../config/database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Recebe os dados do endereço
$data = json_decode(file_get_contents('php://input'), true);

$database = new Database();
$db = $database->getConnection();

try {
    // Verifica se já tem 3 endereços
    if (!isset($data['id'])) {
        $query = "SELECT COUNT(*) FROM enderecos WHERE usuario_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['usuario']['id']]);
        $count = $stmt->fetchColumn();
        
        if ($count >= 3) {
            echo json_encode([
                'success' => false,
                'message' => 'Você já atingiu o limite de 3 endereços'
            ]);
            exit;
        }
    }

    // Se for o primeiro endereço, marca como padrão
    $query = "SELECT COUNT(*) FROM enderecos WHERE usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario']['id']]);
    $count = $stmt->fetchColumn();
    $padrao = ($count == 0) ? 1 : 0;

    if (isset($data['id'])) {
        // Atualiza endereço existente
        $query = "UPDATE enderecos SET 
                    nome = ?, cep = ?, logradouro = ?, numero = ?, 
                    complemento = ?, bairro = ?, cidade = ?, estado = ?
                 WHERE id = ? AND usuario_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $data['nome'], $data['cep'], $data['logradouro'], $data['numero'],
            $data['complemento'], $data['bairro'], $data['cidade'], $data['estado'],
            $data['id'], $_SESSION['usuario']['id']
        ]);
        
        $message = 'Endereço atualizado com sucesso';
    } else {
        // Insere novo endereço
        $query = "INSERT INTO enderecos 
                    (usuario_id, nome, cep, logradouro, numero, complemento, 
                     bairro, cidade, estado, padrao) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $_SESSION['usuario']['id'], $data['nome'], $data['cep'], 
            $data['logradouro'], $data['numero'], $data['complemento'],
            $data['bairro'], $data['cidade'], $data['estado'], $padrao
        ]);
        
        $message = 'Endereço adicionado com sucesso';
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar endereço: ' . $e->getMessage()
    ]);
}
