<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    die('Não autorizado');
}

// Verifica se foi fornecido um ID de pedido
if (!isset($_GET['id'])) {
    die('Pedido não especificado');
}

$database = new Database();
$db = $database->getConnection();

// Busca configurações da loja
$query = "SELECT nome_loja FROM configuracoes LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC); 

// Busca o pedido
$query = "SELECT p.*, u.nome as cliente_nome, u.telefone, 
          CONCAT(p.logradouro, ', ', p.numero, 
                CASE WHEN p.complemento IS NOT NULL AND p.complemento != '' 
                     THEN CONCAT(' - ', p.complemento) 
                     ELSE '' 
                END,
                ' - ', p.bairro) as endereco,
          fp.nome as forma_pagamento,
          c.codigo as cupom_codigo,
          c.tipo as cupom_tipo,
          c.valor as cupom_valor
          FROM pedidos p 
          JOIN usuarios u ON p.usuario_id = u.id 
          LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id 
          LEFT JOIN cupons c ON p.cupom_id = c.id
          WHERE p.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_GET['id']]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die('Pedido não encontrado');
}

// Busca os itens do pedido com complementos e observações
$query = "SELECT i.*, p.nome as produto_nome, p.descricao,
          pc.complemento_nome, pc.opcao_nome, pc.opcao_preco
          FROM itens_pedido i 
          JOIN produtos p ON i.produto_id = p.id 
          LEFT JOIN pedido_complementos pc ON i.id = pc.pedido_item_id
          WHERE i.pedido_id = ?
          ORDER BY i.id, pc.id";

$stmt = $db->prepare($query);
$stmt->execute([$_GET['id']]);
$itens_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar os itens com seus complementos
$itens = [];
$item_atual = null;
$total_complementos = 0;

foreach ($itens_raw as $row) {
    if (!isset($itens[$row['id']])) {
        $itens[$row['id']] = [
            'id' => $row['id'],
            'produto_nome' => $row['produto_nome'],
            'descricao' => $row['descricao'],
            'quantidade' => $row['quantidade'],
            'preco_unitario' => $row['preco_unitario'],
            'observacoes' => $row['observacoes'],
            'complementos' => []
        ];
    }
    
    if ($row['complemento_nome']) {
        $itens[$row['id']]['complementos'][] = [
            'nome' => $row['complemento_nome'],
            'opcao' => $row['opcao_nome'],
            'preco' => $row['opcao_preco']
        ];
        $total_complementos += $row['opcao_preco'] * $row['quantidade'];
    }
}

// Calcular subtotal e total
$subtotal = 0;
foreach ($itens as $item) {
    // Valor do item principal
    $subtotal += $item['preco_unitario'] * $item['quantidade'];
    
    // Valor dos complementos
    foreach ($item['complementos'] as $complemento) {
        $subtotal += $complemento['preco'] * $item['quantidade'];
    }
}

// Buscar taxa de entrega do pedido
$taxa_entrega = $pedido['taxa_entrega'] ?? 0;

// Se houver cupom, calcular o desconto
if ($pedido['cupom_id']) {
    $desconto = 0;
    $taxa_entrega = floatval($pedido['taxa_entrega']);
    $valor_cupom = floatval($pedido['cupom_valor']);
    
    switch ($pedido['cupom_tipo']) {
        case 'valor_total':
            $desconto = $valor_cupom;
            break;
        case 'porcentagem_total':
            $desconto = ($subtotal * $valor_cupom) / 100;
            break;
        case 'frete_valor':
            $desconto = min($valor_cupom, $taxa_entrega);
            break;
        case 'porcentagem_frete': 
            $desconto = ($taxa_entrega * $valor_cupom) / 100;
            break;
    }
    $pedido['desconto_cupom'] = round($desconto, 2);
}

// Total final
$total = $subtotal + $taxa_entrega - ($pedido['desconto_cupom'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?php echo $pedido['id']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            font-size: 12px;
        }
        .paper-58mm {
            width: 58mm;
            margin: 0 auto;
            font-size: 10px;
        }
        .paper-78mm {
            width: 78mm;
            margin: 0 auto;
            font-size: 11px;
        }
        .paper-80mm {
            width: 80mm;
            margin: 0 auto;
            font-size: 12px;
        }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .border-t { border-top: 1px solid #000; }
        .border-b { border-bottom: 1px solid #000; }
        .pt-2 { padding-top: 0.5rem; }
        .pb-2 { padding-bottom: 0.5rem; }
        .text-sm { font-size: 0.875em; }
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .print-controls {
            max-width: 300px;
            margin: 20px auto;
            text-align: center;
        }
        .print-controls select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .print-controls button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 5px;
        }
        .print-controls button:hover {
            background-color: #45a049;
        }
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print print-controls">
        <select id="paperSize" class="mb-2">
            <option value="58">58mm (Impressora Térmica Pequena)</option>
            <option value="78">78mm (Impressora Térmica Média)</option>
            <option value="80" selected>80mm (Impressora Térmica Padrão)</option>
        </select>
        <div>
            <button onclick="window.print()">Imprimir</button>
            <button onclick="window.close()">Fechar</button>
        </div>
    </div>

    <div id="printContent" class="paper-80mm">
        <!-- Cabeçalho -->
        <div class="text-center mb-4">
            <h1 style="font-size: 18px; margin-bottom: 8px;"><?php echo $config['nome_loja']; ?></h1>
            <h2 style="font-size: 16px; margin: 0;">Pedido #<?php echo $pedido['id']; ?></h2>
            <p class="text-sm mb-2"><?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?></p>
        </div>

        <!-- Dados do Cliente -->
        <div class="mb-4 border-t border-b pt-2 pb-2">
            <div class="flex justify-between mb-2">
                <span class="font-bold">Cliente:</span>
                <span><?php echo $pedido['cliente_nome']; ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-bold">Telefone:</span>
                <span><?php echo $pedido['telefone']; ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-bold">Endereço:</span>
                <span><?php echo $pedido['endereco']; ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-bold">Forma de Pagamento:</span>
                <span><?php echo $pedido['forma_pagamento']; ?></span>
            </div>
            <?php if ($pedido['precisa_troco'] && $pedido['forma_pagamento'] === 'Dinheiro' && floatval($pedido['troco_para']) > 0): ?>
            <div class="flex justify-between mb-2">
                <span class="font-bold">Troco para:</span>
                <span>R$ <?php echo number_format($pedido['troco_para'], 2, ',', '.'); ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-bold">Valor do troco:</span>
                <span>R$ <?php echo number_format($pedido['troco_para'] - $total, 2, ',', '.'); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Itens do Pedido -->
        <div class="mb-4">
            <p class="font-bold mb-2 text-center border-b pb-2">ITENS DO PEDIDO</p>
            <?php foreach ($itens as $item): ?>
                <div class="mb-2 pb-2 border-b">
                    <div class="flex justify-between">
                        <span><?php echo $item['quantidade']; ?>x <?php echo $item['produto_nome']; ?></span>
                        <span>R$ <?php echo number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.'); ?></span>
                    </div>
                    <?php if (!empty($item['complementos'])): ?>
                        <?php foreach ($item['complementos'] as $comp): ?>
                            <div class="flex justify-between text-sm" style="padding-left: 12px;">
                                <span>+ <?php echo $comp['nome']; ?>: <?php echo $comp['opcao']; ?></span>
                                <span>R$ <?php echo number_format($comp['preco'] * $item['quantidade'], 2, ',', '.'); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($item['observacoes']): ?>
                        <div class="text-sm" style="padding-left: 12px;">
                            <span>Obs: <?php echo $item['observacoes']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Totais -->
        <div class="border-t pt-2">
            <div class="flex justify-between mb-2">
                <span>Subtotal:</span>
                <span>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span>Taxa de Entrega:</span>
                <span>R$ <?php echo number_format($taxa_entrega, 2, ',', '.'); ?></span>
            </div>
            <?php if ($pedido['cupom_codigo']): ?>
            <div class="flex justify-between mb-2">
                <span>
                    Cupom <?php echo $pedido['cupom_codigo']; ?>
                    <br>
                    <small>
                        (<?php 
                        if (strpos($pedido['cupom_tipo'], 'frete') !== false) {
                            echo 'Desconto no Frete ' . $pedido['cupom_valor'] . '%';
                        } else {
                            echo 'Desconto no Total ' . $pedido['cupom_valor'];
                            echo strpos($pedido['cupom_tipo'], 'porcentagem') !== false ? '%' : ' reais';
                        }
                        ?>)
                    </small>
                </span>
                <span class="text-success">- R$ <?php echo number_format($pedido['desconto_cupom'], 2, ',', '.'); ?></span>
            </div>
            <?php endif; ?>
            <div class="flex justify-between font-bold" style="font-size: 14px;">
                <span>Total:</span>
                <span>R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
            </div>
        </div>

        <!-- Observações do Pedido -->
        <?php if ($pedido['observacoes']): ?>
        <div class="border-t pt-2 mt-2">
            <p><strong>Observações:</strong> <?php echo $pedido['observacoes']; ?></p>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('paperSize').addEventListener('change', function() {
            const printContent = document.getElementById('printContent');
            const size = this.value;
            
            // Remove todas as classes de tamanho
            printContent.classList.remove('paper-58mm', 'paper-78mm', 'paper-80mm');
            
            // Adiciona a classe correspondente ao tamanho selecionado
            printContent.classList.add(`paper-${size}mm`);
        });
    </script>
</body>
</html>
