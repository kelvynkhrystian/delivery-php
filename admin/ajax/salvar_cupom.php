<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    error_log('Dados recebidos: ' . print_r($data, true));
    
    // Validação dos campos obrigatórios
    if (empty($data['codigo']) || empty($data['tipo']) || !isset($data['valor'])) {
        throw new Exception('Campos obrigatórios não preenchidos');
    }

    if (!empty($data['id'])) {
        error_log('Atualizando cupom existente: ' . $data['id']);
        // Atualização
        $query = "UPDATE cupons SET 
                    codigo = :codigo, 
                    tipo = :tipo, 
                    valor = :valor, 
                    data_inicio = :data_inicio, 
                    data_fim = :data_fim, 
                    valor_minimo_pedido = :valor_minimo_pedido,
                    ativo = :ativo
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $success = $stmt->execute([
            ':codigo' => $data['codigo'],
            ':tipo' => $data['tipo'],
            ':valor' => $data['valor'],
            ':data_inicio' => $data['data_inicio'],
            ':data_fim' => empty($data['data_fim']) ? null : $data['data_fim'],
            ':valor_minimo_pedido' => empty($data['valor_minimo_pedido']) ? null : $data['valor_minimo_pedido'],
            ':ativo' => $data['ativo'],
            ':id' => $data['id']
        ]);
    } else {
        error_log('Inserindo novo cupom');
        // Inserção
        $query = "INSERT INTO cupons (codigo, tipo, valor, data_inicio, data_fim, valor_minimo_pedido, ativo) 
                  VALUES (:codigo, :tipo, :valor, :data_inicio, :data_fim, :valor_minimo_pedido, :ativo)";
        
        $stmt = $db->prepare($query);
        $success = $stmt->execute([
            ':codigo' => $data['codigo'],
            ':tipo' => $data['tipo'],
            ':valor' => $data['valor'],
            ':data_inicio' => $data['data_inicio'],
            ':data_fim' => empty($data['data_fim']) ? null : $data['data_fim'],
            ':valor_minimo_pedido' => empty($data['valor_minimo_pedido']) ? null : $data['valor_minimo_pedido'],
            ':ativo' => $data['ativo']
        ]);
    }

    if ($success) {
        error_log('Operação realizada com sucesso');
        echo json_encode(['success' => true]);
    } else {
        error_log('Erro no banco de dados: ' . print_r($stmt->errorInfo(), true));
        throw new Exception('Erro ao salvar cupom');
    }

} catch (Exception $e) {
    error_log('Erro na operação: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
