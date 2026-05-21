<?php
session_start();
require_once 'config/database.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: conta.php');
    exit;
}

// Verifica se foi fornecido um ID de pedido
if (!isset($_GET['id'])) {
    header('Location: pedidos.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Busca o pedido
$query = "SELECT p.*, fp.nome as forma_pagamento, u.nome as cliente_nome,
          p.taxa_entrega as taxa_entrega,
          e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.estado, e.cep
          FROM pedidos p 
          LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
          LEFT JOIN usuarios u ON p.usuario_id = u.id 
          LEFT JOIN enderecos_usuario e ON p.endereco_id = e.id
          WHERE p.id = :id AND p.usuario_id = :usuario_id";
$stmt = $db->prepare($query);
$stmt->execute(['id' => $_GET['id'], 'usuario_id' => $_SESSION['usuario']['id']]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header('Location: pedidos.php');
    exit;
}

// Busca os itens do pedido com seus complementos
$query = "SELECT pi.*, pr.nome as produto_nome, pr.imagem as produto_imagem, pr.descricao as produto_descricao,
          pc.complemento_nome, pc.opcao_nome, pc.opcao_preco
          FROM itens_pedido pi
          LEFT JOIN produtos pr ON pr.id = pi.produto_id
          LEFT JOIN pedido_complementos pc ON pc.pedido_item_id = pi.id
          WHERE pi.pedido_id = :pedido_id
          ORDER BY pi.id, pc.id";
$stmt = $db->prepare($query);
$stmt->execute(['pedido_id' => $pedido['id']]);

$items = [];
$item_atual = null;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!$item_atual || $item_atual['id'] !== $row['id']) {
        if ($item_atual) {
            $items[] = $item_atual;
        }
        $item_atual = [
            'id' => $row['id'],
            'produto_id' => $row['produto_id'],
            'produto_nome' => $row['produto_nome'],
            'produto_imagem' => $row['produto_imagem'],
            'produto_descricao' => $row['produto_descricao'],
            'quantidade' => $row['quantidade'],
            'preco_unitario' => $row['preco_unitario'],
            'observacoes' => isset($row['observacoes']) ? $row['observacoes'] : '',
            'complementos' => []
        ];
    }
    
    if ($row['complemento_nome']) {
        $item_atual['complementos'][] = [
            'complemento_nome' => $row['complemento_nome'],
            'opcao_nome' => $row['opcao_nome'],
            'opcao_preco' => $row['opcao_preco']
        ];
    }
}

if ($item_atual) {
    $items[] = $item_atual;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido #<?php echo $pedido['id']; ?> - Delivery App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 pb-24">
        <!-- Cabeçalho com botão voltar -->
        <div class="flex items-center mb-6">
            <a href="pedidos.php" class="text-gray-600 hover:text-gray-900 mr-4">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <h1 class="text-2xl font-bold">Pedido #<?php echo $pedido['id']; ?></h1>
        </div>

        <!-- Status do Pedido -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-4">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold">Status do Pedido</h2>
                <span class="px-3 py-1 rounded-full text-sm 
                    <?php
                        switch ($pedido['status']) {
                            case 'pendente':
                                echo 'bg-yellow-100 text-yellow-800';
                                break;
                            case 'confirmado':
                                echo 'bg-blue-100 text-blue-800';
                                break;
                            case 'em_preparo':
                                echo 'bg-purple-100 text-purple-800';
                                break;
                            case 'saiu_entrega':
                                echo 'bg-indigo-100 text-indigo-800';
                                break;
                            case 'entregue':
                                echo 'bg-green-100 text-green-800';
                                break;
                            case 'cancelado':
                                echo 'bg-red-100 text-red-800';
                                break;
                            default:
                                echo 'bg-gray-100 text-gray-800';
                        }
                    ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $pedido['status'])); ?>
                </span>
            </div>

            <!-- Timeline do Pedido -->
            <div class="mt-6">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white">
                        <i class="fas fa-check text-sm"></i>
                    </div>
                    <div class="ml-4">
                        <p class="font-semibold">Pedido Realizado</p>
                        <p class="text-sm text-gray-600">
                            <?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?>
                        </p>
                    </div>
                </div>

                <?php if ($pedido['status'] != 'pendente' && $pedido['status'] != 'cancelado'): ?>
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 rounded-full <?php echo $pedido['status'] != 'pendente' ? 'bg-green-500' : 'bg-gray-300'; ?> flex items-center justify-center text-white">
                            <i class="fas <?php echo $pedido['status'] != 'pendente' ? 'fa-check' : 'fa-clock'; ?> text-sm"></i>
                        </div>
                        <div class="ml-4">
                            <p class="font-semibold">Pedido Confirmado</p>
                            <p class="text-sm text-gray-600">Seu pedido foi confirmado pelo restaurante</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (in_array($pedido['status'], ['em_preparo', 'saiu_entrega', 'entregue'])): ?>
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 rounded-full <?php echo in_array($pedido['status'], ['em_preparo', 'saiu_entrega', 'entregue']) ? 'bg-green-500' : 'bg-gray-300'; ?> flex items-center justify-center text-white">
                            <i class="fas <?php echo in_array($pedido['status'], ['em_preparo', 'saiu_entrega', 'entregue']) ? 'fa-check' : 'fa-clock'; ?> text-sm"></i>
                        </div>
                        <div class="ml-4">
                            <p class="font-semibold">Em Preparo</p>
                            <p class="text-sm text-gray-600">Seu pedido está sendo preparado</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (in_array($pedido['status'], ['saiu_entrega', 'entregue'])): ?>
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 rounded-full <?php echo in_array($pedido['status'], ['saiu_entrega', 'entregue']) ? 'bg-green-500' : 'bg-gray-300'; ?> flex items-center justify-center text-white">
                            <i class="fas <?php echo in_array($pedido['status'], ['saiu_entrega', 'entregue']) ? 'fa-check' : 'fa-clock'; ?> text-sm"></i>
                        </div>
                        <div class="ml-4">
                            <p class="font-semibold">Saiu para Entrega</p>
                            <p class="text-sm text-gray-600">Seu pedido está a caminho</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($pedido['status'] == 'entregue'): ?>
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full <?php echo $pedido['status'] == 'entregue' ? 'bg-green-500' : 'bg-gray-300'; ?> flex items-center justify-center text-white">
                            <i class="fas <?php echo $pedido['status'] == 'entregue' ? 'fa-check' : 'fa-clock'; ?> text-sm"></i>
                        </div>
                        <div class="ml-4">
                            <p class="font-semibold">Entregue</p>
                            <p class="text-sm text-gray-600">Seu pedido foi entregue</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($pedido['status'] == 'cancelado'): ?>
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-red-500 flex items-center justify-center text-white">
                            <i class="fas fa-times text-sm"></i>
                        </div>
                        <div class="ml-4">
                            <p class="font-semibold">Pedido Cancelado</p>
                            <p class="text-sm text-gray-600">Seu pedido foi cancelado</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Itens do Pedido -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-4">
            <h2 class="text-lg font-semibold mb-4">Itens do Pedido</h2>
            
            <?php 
                // Calcular o subtotal (soma de todos os itens com complementos)
                $subtotal = 0;
                foreach ($items as $item) {
                    $item_subtotal = $item['quantidade'] * $item['preco_unitario'];
                    $complementos_total = 0;
                    foreach ($item['complementos'] as $complemento) {
                        $complementos_total += $complemento['opcao_preco'] * $item['quantidade'];
                    }
                    $subtotal += $item_subtotal + $complementos_total;
                }
                
                // Buscar configuração do tipo de entrega
                $query = "SELECT tipo_entrega FROM configuracoes LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $config = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Buscar taxa de entrega baseado no tipo configurado
                $taxa_entrega = $pedido['taxa_entrega'];
                
                // Total é subtotal + taxa de entrega
                $total = $subtotal + $taxa_entrega;
            ?>

            <?php foreach ($items as $item): ?>
                <div class="flex flex-col border-b py-4 last:border-b-0">
                    <div class="flex justify-between items-start">
                        <div class="flex items-start space-x-4">
                            <div class="w-20 h-20 flex-shrink-0">
                                <?php if ($item['produto_imagem']): ?>
                                    <img src="<?php echo $item['produto_imagem']; ?>" 
                                         alt="<?php echo $item['produto_nome']; ?>" 
                                         class="w-full h-full object-cover rounded">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400 text-2xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold"><?php echo $item['produto_nome']; ?></h3>
                                <?php if ($item['produto_descricao']): ?>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo $item['produto_descricao']; ?></p>
                                <?php endif; ?>
                                <p class="text-gray-600 mt-1">
                                    <?php echo $item['quantidade']; ?>x 
                                    R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?>
                                </p>

                                <!-- Complementos -->
                                <?php if (!empty($item['complementos'])): ?>
                                    <div class="mt-2">
                                        <p class="text-sm font-medium text-gray-700">Complementos:</p>
                                        <ul class="mt-1 space-y-1">
                                            <?php foreach ($item['complementos'] as $complemento): ?>
                                                <li class="text-sm text-gray-600 flex justify-between">
                                                    <span><?php echo $complemento['complemento_nome']; ?>: <?php echo $complemento['opcao_nome']; ?></span>
                                                    <span>+ R$ <?php echo number_format($complemento['opcao_preco'], 2, ',', '.'); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <!-- Observações -->
                                <?php if ($item['observacoes']): ?>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <strong>Obs:</strong> 
                                        <?php 
                                        $observacoes = $item['observacoes'];
                                        // Quebra o texto em palavras
                                        $palavras = explode(' ', $observacoes);
                                        $linha = '';
                                        $linhas = [];
                                        
                                        foreach ($palavras as $palavra) {
                                            if (strlen($linha . ' ' . $palavra) > 25) {
                                                $linhas[] = trim($linha);
                                                $linha = $palavra;
                                            } else {
                                                $linha .= ($linha === '' ? '' : ' ') . $palavra;
                                            }
                                        }
                                        if ($linha !== '') {
                                            $linhas[] = trim($linha);
                                        }
                                        
                                        echo implode('<br>', $linhas);
                                        ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <?php
                                $item_subtotal = $item['quantidade'] * $item['preco_unitario'];
                                $complementos_total = 0;
                                foreach ($item['complementos'] as $complemento) {
                                    $complementos_total += $complemento['opcao_preco'] * $item['quantidade'];
                                }
                                $total_item = $item_subtotal + $complementos_total;
                            ?>
                            <p class="font-semibold">
                                R$ <?php echo number_format($total_item, 2, ',', '.'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Total -->
            <div class="border-t mt-4 pt-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-600">Subtotal</span>
                    <span>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                </div>
                <?php if ($pedido['logradouro']): ?>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-600">Endereço de Entrega</span>
                    <span class="text-right">
                        <?php echo htmlspecialchars($pedido['logradouro']); ?>, 
                        <?php echo htmlspecialchars($pedido['numero']); ?>
                        <?php if ($pedido['complemento']): ?>
                            - <?php echo htmlspecialchars($pedido['complemento']); ?>
                        <?php endif; ?>
                        <br>
                        <?php echo htmlspecialchars($pedido['bairro']); ?>, 
                        <?php echo htmlspecialchars($pedido['cidade']); ?> - 
                        <?php echo htmlspecialchars($pedido['estado']); ?>
                        <br>
                        CEP: <?php echo htmlspecialchars($pedido['cep']); ?>
                    </span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-600">Taxa de Entrega</span>
                    <span>R$ <?php echo number_format($pedido['taxa_entrega'], 2, ',', '.'); ?></span>
                </div>
                <div class="flex justify-between items-center mb-2 text-gray-600">
                    <span>Forma de Pagamento</span>
                    <span><?php echo htmlspecialchars($pedido['forma_pagamento']); ?></span>
                </div>
                <?php if ($pedido['precisa_troco']): ?>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-600">Troco para</span>
                    <span>R$ <?php echo number_format($pedido['troco_para'], 2, ',', '.'); ?></span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-600">Valor do Troco</span>
                    <span>R$ <?php echo number_format($pedido['troco_para'] - $pedido['total'], 2, ',', '.'); ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between items-center text-lg font-bold mt-2 pt-2 border-t">
                    <span>Total</span>
                    <span>R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/menu.php'; ?>

    <script src="assets/js/main.js"></script>
    <script>
        // Inicializar o carrinho quando a página carregar
        document.addEventListener('DOMContentLoaded', () => {
            carrinho.carregarCarrinho();
        });
    </script>
</body>
</html>
