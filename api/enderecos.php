<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Recebe os dados do POST
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    // Verifica se já existem 2 endereços cadastrados
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM enderecos_usuario WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['usuario']['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] >= 2) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Limite máximo de 2 endereços atingido']);
        exit;
    }

    // Validar latitude e longitude
    if (!isset($data['latitude']) || !isset($data['longitude']) || 
        !is_numeric($data['latitude']) || !is_numeric($data['longitude'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Coordenadas inválidas']);
        exit;
    }

    // Se for o primeiro endereço, marca como principal
    $principal = $result['total'] == 0 ? 1 : 0;

    try {
        $stmt = $db->prepare("INSERT INTO enderecos_usuario (
            usuario_id, logradouro, numero, complemento, bairro, 
            cidade, estado, cep, principal, latitude, longitude
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?
        )");
        
        if ($stmt->execute([
            $_SESSION['usuario']['id'],
            $data['logradouro'],
            $data['numero'],
            $data['complemento'] ?? '',
            $data['bairro'],
            $data['cidade'],
            $data['estado'],
            $data['cep'],
            $principal,
            $data['latitude'],
            $data['longitude']
        ])) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar endereço']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar endereço: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
