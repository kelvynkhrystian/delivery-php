<?php
session_start();
require_once '../../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'adicionar':
            // Validações
            if (!isset($data['tipo']) || !isset($data['nome']) || !isset($data['valor'])) {
                throw new Exception('Dados incompletos');
            }

            if ($data['tipo'] === 'km' && (!isset($data['km_inicial']) || !isset($data['km_final']))) {
                throw new Exception('Dados de KM incompletos');
            }

            // Insere a taxa
            $query = "INSERT INTO taxas_entrega (tipo, nome, valor, km_inicial, km_final) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $data['tipo'],
                $data['nome'],
                $data['valor'],
                $data['km_inicial'] ?? null,
                $data['km_final'] ?? null
            ]);

            $id = $db->lastInsertId();
            $message = 'Taxa adicionada com sucesso';
            break;

        case 'excluir':
            if (!isset($data['id'])) {
                throw new Exception('ID não fornecido');
            }

            $query = "DELETE FROM taxas_entrega WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$data['id']]);

            $message = 'Taxa excluída com sucesso';
            break;

        default:
            throw new Exception('Ação inválida');
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'id' => $id ?? null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ]);
}
