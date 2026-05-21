<?php
// Previne erros PHP de aparecerem como HTML
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    require_once '../includes/conexao.php';
    
    if (!isset($conexao) || !$conexao) {
        throw new Exception('Erro de conexão com o banco de dados');
    }

    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('Nenhum dado recebido');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Dados inválidos: ' . json_last_error_msg());
    }

    $codigo = strtoupper($data['codigo'] ?? '');
    $subtotal = floatval($data['subtotal'] ?? 0);
    $frete = floatval($data['frete'] ?? 0);

    if (empty($codigo)) {
        throw new Exception('Código do cupom inválido');
    }

    // Debug do input
    error_log("Código recebido: " . $codigo);
    error_log("Subtotal recebido: " . $subtotal);
    error_log("Frete recebido: " . $frete);

    // Verifica se o cupom existe e está válido
    $sql = "SELECT *, DATE_FORMAT(data_inicio, '%d/%m/%Y') as data_inicio_formatada, 
            DATE_FORMAT(data_fim, '%d/%m/%Y') as data_fim_formatada 
            FROM cupons 
            WHERE codigo = ? AND ativo = 1";
    
    $stmt = $conexao->prepare($sql);
    $stmt->execute([$codigo]);
    $cupom = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug do cupom
    error_log("Data atual: " . date('Y-m-d'));
    if ($cupom) {
        error_log("Data início do cupom: " . $cupom['data_inicio']);
        error_log("Data fim do cupom: " . $cupom['data_fim']);
    }

    if (!$cupom) {
        throw new Exception('Cupom não encontrado ou inativo');
    }

    if ($cupom['data_fim'] && $cupom['data_fim'] < date('Y-m-d')) {
        throw new Exception('Cupom expirado. Válido até ' . $cupom['data_fim_formatada']);
    }

    if ($cupom['quantidade_maxima'] && $cupom['quantidade_usada'] >= $cupom['quantidade_maxima']) {
        throw new Exception('Limite de uso do cupom atingido');
    }

    if ($cupom['data_inicio'] > date('Y-m-d')) {
        throw new Exception('Cupom ainda não está válido. Será válido a partir de ' . $cupom['data_inicio_formatada']);
    }

    // Verifica valor mínimo do pedido
    if ($cupom['valor_minimo_pedido'] && $subtotal < $cupom['valor_minimo_pedido']) {
        throw new Exception('Valor mínimo do pedido não atingido: R$ ' . number_format($cupom['valor_minimo_pedido'], 2, ',', '.'));
    }

    // Calcula o desconto
    $desconto = 0;
    switch ($cupom['tipo']) {
        case 'valor_total':
            $desconto = $cupom['valor'];
            break;
        
        case 'porcentagem_total':
            $desconto = ($subtotal * $cupom['valor']) / 100;
            break;
        
        case 'valor_frete':
            $desconto = min($frete, $cupom['valor']);
            break;
        
        case 'porcentagem_frete':
            $desconto = ($frete * $cupom['valor']) / 100;
            break;
    }

    // Limita o desconto ao valor total do pedido + frete
    $total = $subtotal + $frete;
    $desconto = min($desconto, $total);

    // Debug
    error_log("Tipo do cupom: " . $cupom['tipo']);
    error_log("Valor do cupom: " . $cupom['valor']);
    error_log("Subtotal: " . $subtotal);
    error_log("Frete: " . $frete);
    error_log("Desconto calculado: " . $desconto);

    echo json_encode([
        'success' => true,
        'cupom' => [
            'id' => $cupom['id'],
            'codigo' => $cupom['codigo'],
            'tipo' => $cupom['tipo'],
            'valor' => $cupom['valor']
        ],
        'desconto' => $desconto,
        'debug' => [
            'subtotal' => $subtotal,
            'frete' => $frete,
            'tipo_cupom' => $cupom['tipo'],
            'valor_cupom' => $cupom['valor']
        ]
    ]);

} catch (PDOException $e) {
    error_log('Erro de banco de dados: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao conectar ao banco de dados: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Erro ao validar cupom: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
