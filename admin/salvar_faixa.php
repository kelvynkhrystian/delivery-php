<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Validar dados recebidos
    if (!isset($_POST['km_inicial']) || !isset($_POST['km_final']) || !isset($_POST['valor']) || !isset($_POST['tempo'])) {
        throw new Exception('Todos os campos são obrigatórios');
    }

    $km_inicial = floatval($_POST['km_inicial']);
    $km_final = floatval($_POST['km_final']);
    $valor = floatval($_POST['valor']);
    $tempo = $_POST['tempo'];

    // Validações adicionais
    if ($km_inicial >= $km_final) {
        throw new Exception('A distância inicial deve ser menor que a final');
    }

    if ($valor < 0) {
        throw new Exception('O valor não pode ser negativo');
    }

    // Verificar sobreposição de faixas
    $stmt = $db->prepare("SELECT COUNT(*) FROM faixas_distancia WHERE 
        (? BETWEEN km_inicial AND km_final) OR 
        (? BETWEEN km_inicial AND km_final) OR
        (km_inicial BETWEEN ? AND ?) OR
        (km_final BETWEEN ? AND ?)");
    $stmt->execute([$km_inicial, $km_final, $km_inicial, $km_final, $km_inicial, $km_final]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Existe sobreposição com outra faixa de distância');
    }

    // Inserir nova faixa
    $stmt = $db->prepare("INSERT INTO faixas_distancia (km_inicial, km_final, valor, tempo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$km_inicial, $km_final, $valor, $tempo]);

    echo json_encode(['success' => true, 'message' => 'Faixa adicionada com sucesso']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar faixa: ' . $e->getMessage()]);
}
