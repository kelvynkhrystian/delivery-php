<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Recebe o JSON do corpo da requisição
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['faixas']) || !is_array($data['faixas'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Inicia a transação
    $db->beginTransaction();

    // Atualiza a chave da API do Google Maps
    if (isset($data['google_maps_key'])) {
        $stmt = $db->prepare("UPDATE configuracoes SET google_maps_key = ? WHERE id = (SELECT id FROM (SELECT id FROM configuracoes ORDER BY id DESC LIMIT 1) as t)");
        $stmt->execute([$data['google_maps_key']]);
    }

    // Limpa a tabela de faixas
    $db->exec("DELETE FROM faixas_distancia");

    // Insere as novas faixas
    $stmt = $db->prepare("INSERT INTO faixas_distancia (km_inicial, km_final, valor) VALUES (?, ?, ?)");
    
    foreach ($data['faixas'] as $faixa) {
        $stmt->execute([
            $faixa['km_inicial'],
            $faixa['km_final'],
            $faixa['valor']
        ]);
    }

    // Confirma a transação
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso']);
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar configurações: ' . $e->getMessage()]);
}
