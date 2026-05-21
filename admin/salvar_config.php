<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Recebe e valida os dados
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['tipo_entrega'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tipo de entrega não fornecido']);
    exit;
}

$tipo_entrega = $data['tipo_entrega'];

// Valida o tipo de entrega
if (!in_array($tipo_entrega, ['bairro', 'distancia'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tipo de entrega inválido']);
    exit;
}

// Conecta ao banco de dados
$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Atualiza a configuração
    $stmt = $db->prepare("UPDATE configuracoes SET tipo_entrega = :tipo_entrega");
    $stmt->bindParam(':tipo_entrega', $tipo_entrega);
    $stmt->execute();

    // Se solicitado, limpa os dados do tipo anterior
    if (isset($data['limpar_dados']) && $data['limpar_dados']) {
        // Apenas atualiza a configuração, não apaga mais os dados
        $stmt = $db->prepare("UPDATE configuracoes SET tipo_entrega = :tipo_entrega");
        $stmt->bindParam(':tipo_entrega', $tipo_entrega);
        $stmt->execute();
    }

    $db->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Configuração salva com sucesso']);
} catch (Exception $e) {
    $db->rollBack();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar configuração: ' . $e->getMessage()]);
}
