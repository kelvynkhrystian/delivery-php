<?php
// Define a codificação
header('Content-Type: application/json; charset=utf-8');

// Desativa a exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../config/database.php';

try {
    // Verifica se o usuário está logado
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não está logado'
        ]);
        exit;
    }

    // Recebe os dados do pedido
    $dados = json_decode(file_get_contents('php://input'), true);

    if (!$dados || !isset($dados['carrinho']) || !isset($dados['forma_pagamento_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Dados inválidos'
        ]);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Verifica se o usuário existe
        $query = "SELECT id FROM usuarios WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute(['id' => $_SESSION['usuario']['id']]);
        
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Usuário não encontrado. Por favor, faça login novamente.');
        }

        // Verifica se a forma de pagamento existe
        $query = "SELECT id FROM formas_pagamento WHERE id = :id AND ativo = 1";
        $stmt = $db->prepare($query);
        $stmt->execute(['id' => $dados['forma_pagamento_id']]);
        
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Forma de pagamento inválida');
        }

        // Inicia a transação antes de qualquer operação de escrita
        $db->beginTransaction();

        try {
            // Debug dos dados recebidos
            error_log("Dados do pedido recebidos: " . json_encode($dados));

            // Calcula o total do pedido
            $total = 0;
            foreach ($dados['carrinho'] as $item) {
                error_log("Verificando produto ID: " . $item['produto_id']);
                
                // Busca o preço do produto
                $query = "SELECT id, preco FROM produtos WHERE id = ? AND ativo = 1";
                $stmt = $db->prepare($query);
                $stmt->execute([$item['produto_id']]);
                $produto = $stmt->fetch(PDO::FETCH_ASSOC);

                error_log("Resultado da busca: " . json_encode($produto));

                if (!$produto) {
                    // Debug da query
                    error_log("Query executada: " . str_replace('?', $item['produto_id'], $query));
                    error_log("Produto não encontrado - ID: " . $item['produto_id']);
                    throw new Exception("Produto não encontrado (ID: " . $item['produto_id'] . ")");
                }

                $subtotal = $produto['preco'] * $item['quantidade'];
                
                // Adiciona o preço dos complementos
                if (isset($item['complementos']) && !empty($item['complementos'])) {
                    foreach ($item['complementos'] as $complemento) {
                        if (isset($complemento['opcao_preco']) && is_numeric($complemento['opcao_preco'])) {
                            $subtotal += floatval($complemento['opcao_preco']) * $item['quantidade'];
                        }
                    }
                }

                $total += $subtotal;
            }

            // Busca os dados do endereço se fornecido
            $endereco = null;
            if (isset($dados['endereco_id']) && $dados['endereco_id']) {
                $query = "SELECT * FROM enderecos_usuario WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$dados['endereco_id']]);
                $endereco = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // Insere o pedido
            $query = "INSERT INTO pedidos (
                usuario_id, 
                total, 
                forma_pagamento_id, 
                observacoes,
                precisa_troco,
                troco_para,
                taxa_entrega,
                cupom_id,
                desconto_cupom,
                endereco_id,
                logradouro,
                numero,
                complemento,
                bairro,
                cidade,
                estado,
                cep,
                status,
                data_criacao
            ) VALUES (
                :usuario_id,
                :total,
                :forma_pagamento_id,
                :observacoes,
                :precisa_troco,
                :troco_para,
                :taxa_entrega,
                :cupom_id,
                :desconto_cupom,
                :endereco_id,
                :logradouro,
                :numero,
                :complemento,
                :bairro,
                :cidade,
                :estado,
                :cep,
                'pendente',
                NOW()
            )";

            $stmt = $db->prepare($query);
            $stmt->execute([
                'usuario_id' => $_SESSION['usuario']['id'],
                'total' => $total + ($dados['taxa_entrega'] ?? 0) - ($dados['desconto_cupom'] ?? 0),
                'forma_pagamento_id' => $dados['forma_pagamento_id'],
                'observacoes' => $dados['observacoes'] ?? null,
                'precisa_troco' => $dados['precisa_troco'] ?? 0,
                'troco_para' => $dados['troco_para'] ?? null,
                'taxa_entrega' => $dados['taxa_entrega'] ?? 0,
                'cupom_id' => $dados['cupom_id'] ?? null,
                'desconto_cupom' => $dados['desconto_cupom'] ?? 0,
                'endereco_id' => $dados['endereco_id'] ?? null,
                'logradouro' => $endereco ? $endereco['logradouro'] : null,
                'numero' => $endereco ? $endereco['numero'] : null,
                'complemento' => $endereco ? $endereco['complemento'] : null,
                'bairro' => $endereco ? $endereco['bairro'] : null,
                'cidade' => $endereco ? $endereco['cidade'] : null,
                'estado' => $endereco ? $endereco['estado'] : null,
                'cep' => $endereco ? $endereco['cep'] : null
            ]);
            $pedido_id = $db->lastInsertId();

            // Insere os itens do pedido
            foreach ($dados['carrinho'] as $itemId => $item) {
                $query = "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, observacoes) VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    $pedido_id,
                    $item['produto_id'],
                    $item['quantidade'],
                    $produto['preco'],
                    $item['observacoes'] ?? null
                ]);
                $item_id = $db->lastInsertId();

                // Insere os complementos do item
                if (isset($item['complementos']) && is_array($item['complementos'])) {
                    foreach ($item['complementos'] as $complemento) {
                        $query = "INSERT INTO pedido_complementos (pedido_item_id, complemento_nome, opcao_nome, opcao_preco) 
                                 VALUES (?, ?, ?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            $item_id,
                            $complemento['complemento_nome'],
                            $complemento['opcao_nome'],
                            $complemento['opcao_preco']
                        ]);
                    }
                }
            }

            // Se chegou até aqui sem erros, confirma a transação
            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Pedido realizado com sucesso',
                'pedido_id' => $pedido_id
            ]);

        } catch (Exception $e) {
            // Se houver qualquer erro durante a transação, faz o rollback
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e; // Re-lança a exceção para ser capturada pelo try-catch externo
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao finalizar pedido: ' . $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar pedido: ' . $e->getMessage()
    ]);
    exit;
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
    exit;
}
