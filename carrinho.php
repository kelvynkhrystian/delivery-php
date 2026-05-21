<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Buscar endereços do usuário
$enderecos_usuario = [];
if (isset($_SESSION['usuario'])) {
    try {
        $query = "SELECT * FROM enderecos_usuario WHERE usuario_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['usuario']['id']]);
        $enderecos_usuario = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $enderecos_usuario = [];
        error_log("Erro ao buscar endereços: " . $e->getMessage());
    }
}

// Buscar todos os produtos para ter as informações disponíveis
$query = "SELECT id, nome, preco, descricao, imagem, categoria_id FROM produtos WHERE ativo = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$todos_produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug para verificar os produtos carregados
error_log("Produtos carregados: " . json_encode($todos_produtos));

// Buscar formas de pagamento
$query = "SELECT * FROM formas_pagamento WHERE ativo = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$formas_pagamento = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar configurações da loja para cálculo de distância
$config_loja = [];
if (isset($_SESSION['usuario'])) {
    $query = "SELECT maps_latitude, maps_longitude, maps_endereco, maps_raio_entrega FROM configuracoes LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $config_loja = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar faixas de distância
try {
    $query = "SELECT * FROM faixas_distancia WHERE ativo = 1 ORDER BY inicio";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $faixas_distancia = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $faixas_distancia = [];
    error_log("Erro ao buscar faixas de distância: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho - Delivery</title>
    <?php include 'includes/header.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/calcular-frete.js"></script>
    <link rel="stylesheet" href="assets/css/menu.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        :root {
            --theme-color: <?php echo $config['cor_tema'] ?? '#8B5CF6'; ?>;
        }

        .text-theme {
            color: var(--theme-color) !important;
        }

        .bg-theme {
            background-color: var(--theme-color) !important;
        }

        .hover\:text-theme:hover {
            color: var(--theme-color) !important;
        }

        .hover\:bg-theme:hover {
            background-color: var(--theme-color) !important;
        }

        .border-theme {
            border-color: var(--theme-color) !important;
        }

        .theme-primary {
            background-color: var(--theme-color) !important;
        }

        .bg-theme\/10 {
            background-color: color-mix(in srgb, var(--theme-color) 10%, transparent) !important;
        }

        /* Substituindo cores do Tailwind */
        .text-purple-600 {
            color: var(--theme-color) !important;
        }

        .hover\:text-purple-800:hover {
            color: var(--theme-color) !important;
            filter: brightness(0.8);
        }

        .text-purple-500 {
            color: var(--theme-color) !important;
        }

        /* Botões e elementos interativos */
        button.theme-button {
            background-color: var(--theme-color);
            color: white;
            transition: all 0.3s ease;
        }

        button.theme-button:hover {
            filter: brightness(0.9);
        }

        button.theme-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Inputs e seleção */
        .theme-radio:checked {
            background-color: var(--theme-color) !important;
            border-color: var(--theme-color) !important;
        }

        .theme-border-focus:focus {
            border-color: var(--theme-color) !important;
            ring-color: var(--theme-color) !important;
        }

        /* Estilos para endereços */
        .address-option {
            transition: all 0.3s ease;
        }
        .address-option:hover {
            background-color: rgba(var(--theme-rgb), 0.05);
        }
        .address-option.selected {
            border-color: var(--theme-color);
            background-color: rgba(var(--theme-rgb), 0.05);
        }
        .address-option.selected i,
        .address-option.selected .font-medium {
            color: var(--theme-color);
        }
        .address-option i.principal {
            color: var(--theme-color);
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'includes/menu.php'; ?>
    
    <div class="container mx-auto px-4 py-8 pb-24">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Meu Carrinho</h1>
        </div>
        <div id="carrinho-container" class="min-h-[200px]">
            <!-- Loader enquanto carrega -->
            <div class="flex items-center justify-center h-[200px]">
                <i class="fas fa-spinner fa-spin text-theme text-4xl"></i>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Script iniciado');
        
        // Produtos e formas de pagamento disponíveis
        const todosProdutos = <?php echo json_encode($todos_produtos); ?>;
        const formasPagamento = <?php echo json_encode($formas_pagamento); ?>;
        const enderecosUsuario = <?php echo json_encode($enderecos_usuario); ?>;
        console.log('Produtos disponíveis:', todosProdutos);
        console.log('Formas de pagamento:', formasPagamento);
        console.log('Endereços do usuário:', enderecosUsuario);
        
        // Funções auxiliares
        function formatarPreco(preco) {
            return parseFloat(preco).toFixed(2).replace('.', ',');
        }

        // Retorna o ícone adequado para cada forma de pagamento
        function getIconeFormaPagamento(nome) {
            const icones = {
                'PIX': 'fas fa-qrcode',
                'Dinheiro': 'fas fa-money-bill-wave',
                'Cartão de Crédito': 'fas fa-credit-card',
                'Cartão de Débito': 'fas fa-credit-card'
            };
            return icones[nome] || 'fas fa-money-bill';
        }

        // Variável global para armazenar o valor do frete
        let valorFreteAtual = 0;

        // Variável global para controlar se o endereço está dentro da área de entrega
        let enderecoForaArea = false;

        let cupomAtual = null;
        let descontoCupom = 0;

        window.aplicarCupom = async function() {
            const codigo = document.getElementById('cupom').value.trim();
            if (!codigo) {
                mostrarErroCupom('Digite um código de cupom');
                return;
            }

            try {
                const response = await fetch('ajax/validar_cupom.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        codigo: codigo,
                        subtotal: calcularSubtotal(),
                        frete: valorFreteAtual
                    })
                });

                const data = await response.json();
                console.log('Resposta do servidor:', data);

                if (data.success) {
                    cupomAtual = data.cupom;
                    descontoCupom = data.desconto;
                    
                    // Debug
                    console.log('Debug do cupom:', data.debug);
                    console.log('Tipo do cupom:', cupomAtual.tipo);
                    console.log('Valor do cupom:', cupomAtual.valor);
                    console.log('Desconto aplicado:', descontoCupom);
                    
                    // Salva o cupom no localStorage
                    localStorage.setItem('cupom_carrinho', JSON.stringify({
                        cupom: cupomAtual,
                        desconto: descontoCupom
                    }));
                    
                    // Mostra informações do cupom
                    document.getElementById('cupom_info').classList.remove('hidden');
                    document.getElementById('cupom_codigo').textContent = cupomAtual.codigo.toUpperCase();
                    document.getElementById('cupom_erro').classList.add('hidden');
                    document.getElementById('cupom').value = '';
                    
                    // Mostra o desconto
                    document.getElementById('cupom_desconto_container').classList.remove('hidden');
                    document.getElementById('cupom_desconto').textContent = formatarPreco(descontoCupom);
                    
                    // Desabilita o input e botão
                    document.getElementById('cupom').disabled = true;
                    document.getElementById('btn_aplicar_cupom').disabled = true;
                    
                    calcularTotal();
                } else {
                    mostrarErroCupom(data.message || 'Cupom inválido');
                    document.getElementById('cupom').value = '';
                }
            } catch (error) {
                console.error('Erro ao aplicar cupom:', error);
                mostrarErroCupom('Erro ao validar cupom');
                document.getElementById('cupom').value = '';
            }
        };

        window.removerCupom = function() {
            cupomAtual = null;
            descontoCupom = 0;
            // Remove o cupom do localStorage
            localStorage.removeItem('cupom_carrinho');
            document.getElementById('cupom_info').classList.add('hidden');
            document.getElementById('cupom_desconto_container').classList.add('hidden');
            document.getElementById('cupom_erro').classList.add('hidden');
            document.getElementById('cupom').value = '';
            
            // Habilita o input e botão
            document.getElementById('cupom').disabled = false;
            document.getElementById('btn_aplicar_cupom').disabled = false;
            
            calcularTotal();
        };

        // Função para carregar o cupom salvo
        function carregarCupomSalvo() {
            const cupomSalvo = localStorage.getItem('cupom_carrinho');
            if (cupomSalvo) {
                const dados = JSON.parse(cupomSalvo);
                cupomAtual = dados.cupom;
                descontoCupom = dados.desconto;
                
                if (cupomAtual) {
                    document.getElementById('cupom_info').classList.remove('hidden');
                    document.getElementById('cupom_codigo').textContent = cupomAtual.codigo;
                    document.getElementById('cupom_desconto_container').classList.remove('hidden');
                    document.getElementById('cupom_desconto').textContent = formatarPreco(descontoCupom);
                    
                    // Desabilita o input e botão
                    document.getElementById('cupom').disabled = true;
                    document.getElementById('btn_aplicar_cupom').disabled = true;
                    
                    calcularTotal();
                }
            }
        }

        function mostrarErroCupom(mensagem) {
            const erro = document.getElementById('cupom_erro');
            erro.querySelector('span').textContent = mensagem;
            erro.classList.remove('hidden');
        }

        function calcularSubtotal() {
            const carrinhoSalvo = localStorage.getItem('carrinho');
            if (!carrinhoSalvo) return 0;

            const carrinho = JSON.parse(carrinhoSalvo);
            let subtotal = 0;

            for (const item of Object.values(carrinho)) {
                const produto = todosProdutos.find(p => String(p.id) === String(item.produto_id));
                
                if (!produto) {
                    console.error('Produto não encontrado:', item.produto_id);
                    continue;
                }

                let itemTotal = produto.preco * item.quantidade;
                if (item.complementos) {
                    for (const complemento of item.complementos) {
                        itemTotal += parseFloat(complemento.opcao_preco) * item.quantidade;
                    }
                }
                subtotal += itemTotal;
            }

            return subtotal;
        }

        function calcularTotal() {
            const subtotal = calcularSubtotal();
            const total = subtotal + valorFreteAtual - descontoCupom;
            
            document.getElementById('subtotal').textContent = `R$ ${formatarPreco(subtotal)}`;
            document.getElementById('frete').textContent = `R$ ${formatarPreco(valorFreteAtual)}`;
            document.getElementById('total').textContent = `R$ ${formatarPreco(Math.max(0, total))}`;
            
            // Mostra informações do cupom no total
            const cupomContainer = document.getElementById('cupom_total_container');
            if (cupomAtual && descontoCupom > 0) {
                cupomContainer.classList.remove('hidden');
                document.getElementById('cupom_codigo_total').textContent = cupomAtual.codigo || '';
                document.getElementById('cupom_valor_total').textContent = formatarPreco(descontoCupom);

                // Debug
                console.log('Cupom atual:', cupomAtual);
                console.log('Desconto:', descontoCupom);
            } else {
                cupomContainer.classList.add('hidden');
            }
            
            return total;
        }

        // Função para renderizar o carrinho
        function renderizarCarrinho() {
            console.log('Iniciando renderização do carrinho');
            const container = document.getElementById('carrinho-container');
            if (!container) {
                console.error('Container do carrinho não encontrado');
                return;
            }

            try {
                // Carrega o carrinho do localStorage
                const carrinhoSalvo = localStorage.getItem('carrinho');
                console.log('Carrinho salvo:', carrinhoSalvo);
                
                let carrinho = {};
                if (carrinhoSalvo) {
                    carrinho = JSON.parse(carrinhoSalvo);
                }
                console.log('Carrinho carregado:', carrinho);

                // Verifica se o carrinho está vazio
                if (!carrinho || Object.keys(carrinho).length === 0) {
                    container.innerHTML = `
                        <div class="bg-white rounded-lg shadow p-6 text-center">
                            <i class="fas fa-shopping-cart text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-600">Seu carrinho está vazio</p>
                            <a href="index.php" class="mt-4 inline-block text-theme hover:text-theme hover:brightness-90">
                                Continuar Comprando
                            </a>
                        </div>
                    `;
                    return;
                }

                // Processa os itens do carrinho
                let html = '<div class="space-y-4">';
                let total = 0;

                for (const [itemId, item] of Object.entries(carrinho)) {
                    console.log('Processando item:', item);
                    const produto = todosProdutos.find(p => String(p.id) === String(item.produto_id));
                    
                    if (!produto) {
                        console.error('Produto não encontrado:', item.produto_id);
                        continue;
                    }

                    console.log('Item completo:', item);
                    console.log('Complementos:', item.complementos);
                    
                    let itemTotal = produto.preco * item.quantidade;
                    if (item.complementos) {
                        for (const complemento of item.complementos) {
                            itemTotal += parseFloat(complemento.opcao_preco) * item.quantidade;
                        }
                    }
                    total += itemTotal;

                    html += `
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="flex">
                                <!-- Imagem -->
                                <div class="w-24 h-24 flex-shrink-0">
                                    <img src="${produto.imagem || 'assets/img/no-image.png'}" 
                                         alt="${produto.nome}"
                                         class="w-full h-full object-cover rounded-lg">
                                </div>
                                     
                                <!-- Nome e Descrição -->
                                <div class="flex-1 ml-4">
                                    <h3 class="text-lg font-semibold">${produto.nome}</h3>
                                    ${produto.descricao ? `
                                        <p class="text-gray-600 text-sm mt-1">${produto.descricao}</p>
                                    ` : ''}
                                </div>
                                
                                <!-- Controles -->
                                <div class="ml-4">
                                    <div class="flex items-center justify-end space-x-2">
                                        <div class="flex border rounded">
                                            <button onclick="alterarQuantidade('${itemId}', -1)" class="px-2 py-1 hover:bg-gray-100 text-theme">-</button>
                                            <span class="px-2 py-1 border-x">${item.quantidade}</span>
                                            <button onclick="alterarQuantidade('${itemId}', 1)" class="px-2 py-1 hover:bg-gray-100 text-theme">+</button>
                                        </div>
                                        <button onclick="removerItem('${itemId}')" class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="text-right mt-2">
                                        <div class="text-lg font-bold">
                                            R$ ${formatarPreco(itemTotal)}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Complementos e Observações -->
                            <div class="mt-3 pt-3 border-t text-sm">
                                ${item.complementos && item.complementos.length > 0 ? `
                                    <div class="mb-2">
                                        <span class="font-medium">Complementos:</span>
                                        <div class="text-gray-600 ml-2">
                                            ${item.complementos.map(complemento => `
                                                <div>• ${complemento.complemento_nome}: ${complemento.opcao_nome} (+ R$ ${formatarPreco(complemento.opcao_preco)})</div>
                                            `).join('')}
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${item.observacoes ? `
                                    <div>
                                        <span class="font-medium">Observações:</span>
                                        <div class="text-gray-600 ml-2">
                                            ${item.observacoes}
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }

                // Campo de cupom, formas de pagamento e total
                html += `
                    <div class="space-y-4">
                        <!-- Campo de cupom -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex flex-col space-y-2">
                                <label for="cupom" class="block text-sm font-medium text-gray-700">
                                    Cupom de desconto
                                </label>
                                <div class="flex w-full gap-2">
                                    <input type="text" 
                                           id="cupom" 
                                           placeholder="Digite seu cupom" 
                                           class="flex-1 min-w-0 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-theme focus:border-theme outline-none disabled:bg-gray-100 disabled:cursor-not-allowed">
                                    <button type="button"
                                            id="btn_aplicar_cupom"
                                            onclick="window.aplicarCupom()" 
                                            class="whitespace-nowrap bg-theme text-white px-6 py-2 rounded-lg hover:brightness-90 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                        Aplicar
                                    </button>
                                </div>
                                <div id="cupom_info" class="hidden mt-2">
                                    <div class="flex items-center justify-between bg-gray-50 p-2 rounded">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-ticket-alt text-theme"></i>
                                            <span id="cupom_codigo" class="font-medium"></span>
                                        </div>
                                        <button onclick="window.removerCupom()" 
                                                type="button"
                                                class="text-red-500 hover:text-red-700">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="cupom_erro" class="hidden mt-2">
                                    <div class="flex items-center gap-2 text-red-600">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span></span>
                                    </div>
                                </div>
                                <div id="cupom_desconto_container" class="hidden mt-2">
                                    <p class="text-sm text-gray-600">Desconto: R$ <span id="cupom_desconto"></span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Endereço de Entrega -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 bg-theme/10 rounded-lg">
                                    <i class="fas fa-map-marker-alt text-theme"></i>
                                </div>
                                <h3 class="text-lg font-semibold">Endereço de Entrega</h3>
                            </div>
                            <?php if (isset($config_loja['maps_raio_entrega'])): ?>
                            <p class="text-sm text-gray-500 mb-4">Entregamos até <?php echo $config_loja['maps_raio_entrega']; ?> km de distância</p>
                            <?php endif; ?>

                            ${<?php echo isset($_SESSION['usuario']) ? 'true' : 'false' ?> ? `
                                ${enderecosUsuario.length > 0 ? `
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        ${enderecosUsuario.map(endereco => `
                                            <div class="relative">
                                                <input type="radio" 
                                                       name="endereco_entrega" 
                                                       id="endereco_${endereco.id}" 
                                                       value="${endereco.id}"
                                                       data-lat="${endereco.latitude}"
                                                       data-lng="${endereco.longitude}"
                                                       onchange="calcularFrete(this)" 
                                                       ${endereco.principal === '1' || endereco.principal === 1 ? 'checked' : ''}
                                                       class="hidden peer">
                                                <label for="endereco_${endereco.id}" 
                                                       class="flex items-start gap-3 p-4 w-full border rounded-lg cursor-pointer transition-all
                                                              hover:bg-gray-50 peer-checked:border-theme peer-checked:bg-white
                                                              ${endereco.principal === 1 || endereco.principal === '1' ? 'border-theme bg-white' : ''}">
                                                    <div class="flex items-start gap-3">
                                                        <i class="fas fa-home text-theme mt-1"></i>
                                                        <div>
                                                            <p class="font-medium">
                                                                ${endereco.logradouro}, ${endereco.numero}
                                                                ${endereco.complemento ? ` - ${endereco.complemento}` : ''}
                                                            </p>
                                                            <p class="text-sm text-gray-600">${endereco.bairro}</p>
                                                            <p class="text-sm text-gray-600">
                                                                ${endereco.cidade} - ${endereco.estado}, ${endereco.cep}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        `).join('')}
                                    </div>
                                    <div id="frete_info" class="hidden mt-4 p-4 bg-gray-50 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-truck text-gray-400"></i>
                                                <span class="font-medium">Taxa de Entrega:</span>
                                            </div>
                                            <span id="valor_frete" class="font-bold">Calculando...</span>
                                        </div>
                                        <p id="distancia_info" class="text-sm text-gray-600 mt-1"></p>
                                    </div>
                                ` : `
                                    <div class="text-center py-6">
                                        <div class="text-gray-400 mb-3">
                                            <i class="fas fa-map-marker-alt text-4xl"></i>
                                        </div>
                                        <p class="text-gray-500">Você ainda não possui endereços cadastrados</p>
                                        <a href="conta.php" class="text-purple-600 hover:text-purple-800 mt-2 inline-block">
                                            Cadastrar Endereço
                                        </a>
                                    </div>
                                `}
                            ` : `
                                <div class="text-center py-6">
                                    <div class="text-gray-400 mb-3">
                                        <i class="fas fa-user text-4xl"></i>
                                    </div>
                                    <p class="text-gray-500">Faça login para selecionar um endereço de entrega</p>
                                    <a href="conta.php" class="text-purple-600 hover:text-purple-800 mt-2 inline-block">
                                        Fazer Login
                                    </a>
                                </div>
                            `}
                        </div>

                        <!-- Formas de Pagamento -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="p-2 bg-theme/10 rounded-lg">
                                    <i class="fas fa-credit-card text-theme"></i>
                                </div>
                                <h3 class="text-lg font-semibold">Forma de Pagamento</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                ${formasPagamento.map(forma => `
                                    <div class="relative">
                                        <input type="radio" 
                                               name="forma_pagamento" 
                                               id="forma_${forma.id}" 
                                               value="${forma.id}"
                                               onchange="handlePaymentSelection(this, '${forma.nome}')"
                                               class="hidden">
                                        <label for="forma_${forma.id}" 
                                               class="address-option flex items-center gap-3 p-4 w-full border rounded-lg cursor-pointer">
                                            <div class="flex items-center gap-3 flex-1">
                                                <i class="${getIconeFormaPagamento(forma.nome)} text-lg text-gray-600"></i>
                                                <span class="font-medium">${forma.nome}</span>
                                            </div>
                                        </label>
                                    </div>
                                `).join('')}
                            </div>

                            <!-- Opção de Troco -->
                            <div id="opcao_troco" class="hidden mt-6 space-y-4 border-t pt-4">
                                <div class="relative">
                                    <input type="checkbox" 
                                           id="precisa_troco" 
                                           name="precisa_troco" 
                                           class="hidden"
                                           onchange="handleTrocoSelection(this)">
                                    <label for="precisa_troco" 
                                           class="address-option flex items-center gap-3 p-4 w-full border rounded-lg cursor-pointer">
                                        <div class="flex items-center gap-3 flex-1">
                                            <i class="fas fa-exchange-alt text-lg text-gray-600"></i>
                                            <span class="font-medium">Precisa de troco?</span>
                                        </div>
                                    </label>
                                </div>
                                <div id="valor_troco_container" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Troco para quanto?</label>
                                    <input type="number" 
                                           id="troco_para" 
                                           name="troco_para" 
                                           step="0.01"
                                           class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-theme focus:border-theme outline-none"
                                           placeholder="Digite o valor">
                                </div>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="bg-white rounded-lg shadow p-6" id="total_container">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-lg">Subtotal:</span>
                                <span class="text-lg" id="subtotal"></span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-lg">Frete:</span>
                                <span class="text-lg" id="frete"></span>
                            </div>
                            <div id="cupom_total_container" class="hidden">
                                <div class="flex justify-between items-center mb-2 text-green-600">
                                    <span class="text-lg">Cupom <span id="cupom_codigo_total"></span>:</span>
                                    <span class="text-lg">-R$ <span id="cupom_valor_total"></span></span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center border-t pt-4 mt-4">
                                <span class="text-xl font-bold">Total:</span>
                                <span class="text-xl font-bold text-theme" id="total"></span>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button onclick="finalizarPedido()" 
                                    class="w-full bg-theme text-white py-3 rounded-lg hover:brightness-90 transition-colors">
                                Finalizar Pedido
                            </button>
                        </div>
                    </div>
                `;
                container.innerHTML = html;
                
                // Atualiza o total separadamente
                calcularTotal();

                // Reaplica o cálculo de frete para o endereço selecionado
                const enderecoSelecionado = document.querySelector('input[name="endereco_entrega"]:checked');
                if (enderecoSelecionado) {
                    calcularFrete(enderecoSelecionado);
                }

            } catch (error) {
                console.error('Erro ao renderizar carrinho:', error);
                container.innerHTML = `
                    <div class="bg-white rounded-lg shadow p-6 text-center">
                        <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                        <p class="text-gray-600">Ocorreu um erro ao carregar o carrinho</p>
                        <p class="text-sm text-gray-500 mt-2">${error.message}</p>
                        <a href="index.php" class="mt-4 inline-block text-purple-600 hover:text-purple-800">
                            Voltar para a página inicial
                        </a>
                    </div>
                `;
            }
        }

        // Função para remover item
        window.removerItem = function(itemId) {
            try {
                Swal.fire({
                    title: 'Confirmar remoção',
                    text: 'Deseja realmente remover este item do carrinho?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, remover',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                }).then((result) => {
                    if (result.isConfirmed) {
                        const carrinhoSalvo = localStorage.getItem('carrinho');
                        if (!carrinhoSalvo) return;

                        let carrinho = JSON.parse(carrinhoSalvo);
                        delete carrinho[itemId];
                        localStorage.setItem('carrinho', JSON.stringify(carrinho));
                        
                        renderizarCarrinho();
                        
                        Swal.fire({
                            title: 'Item removido!',
                            icon: 'success',
                            confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                        });
                    }
                });
            } catch (error) {
                console.error('Erro ao remover item:', error);
                Swal.fire({
                    title: 'Erro!',
                    text: 'Não foi possível remover o item',
                    icon: 'error',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                });
            }
        };

        // Função para alterar a quantidade
        window.alterarQuantidade = function(itemId, delta) {
            try {
                const carrinhoSalvo = localStorage.getItem('carrinho');
                if (!carrinhoSalvo) return;

                let carrinho = JSON.parse(carrinhoSalvo);
                if (!carrinho[itemId]) return;

                const novaQuantidade = carrinho[itemId].quantidade + delta;
                if (novaQuantidade < 1) {
                    removerItem(itemId);
                    return;
                }

                carrinho[itemId].quantidade = novaQuantidade;
                localStorage.setItem('carrinho', JSON.stringify(carrinho));
                renderizarCarrinho();
            } catch (error) {
                console.error('Erro ao alterar quantidade:', error);
                Swal.fire({
                    title: 'Erro!',
                    text: 'Não foi possível alterar a quantidade',
                    icon: 'error',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                });
            }
        };

        // Função para calcular o frete
        window.calcularFrete = async function(param) {
            const freteInfo = document.getElementById('frete_info');
            const valorFreteElement = document.getElementById('valor_frete');
            const distanciaInfo = document.getElementById('distancia_info');
            
            if (!freteInfo || !valorFreteElement || !distanciaInfo) return;
            
            freteInfo.classList.remove('hidden');
            valorFreteElement.textContent = 'Calculando...';
            
            try {
                let latitude, longitude;
                
                // Verifica se param é um elemento HTML (input radio)
                if (param instanceof HTMLElement) {
                    latitude = param.dataset.lat;
                    longitude = param.dataset.lng;
                } else {
                    // Se for um ID, busca o endereço correspondente
                    const endereco = enderecosUsuario.find(e => e.id === param);
                    if (endereco) {
                        latitude = endereco.latitude;
                        longitude = endereco.longitude;
                    }
                }

                // Verifica se as coordenadas foram encontradas
                if (!latitude || !longitude) {
                    console.error('Coordenadas não encontradas para o endereço');
                    valorFreteElement.textContent = 'Erro: Coordenadas não encontradas';
                    valorFreteAtual = 0;
                    enderecoForaArea = true;
                    calcularTotal();
                    return;
                }

                const response = await fetch('ajax/calcular_frete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        latitude: latitude,
                        longitude: longitude
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    if (data.message.includes('fora do raio') || data.message.includes('fora da área')) {
                        valorFreteElement.innerHTML = `<span class="text-red-600 font-bold">Fora da área de entrega</span>`;
                        distanciaInfo.innerHTML = data.raio_entrega ? 
                            `<span class="text-red-600">Distância: ${data.distancia} km (máximo ${data.raio_entrega} km)</span>` :
                            `<span class="text-red-600">Distância: ${data.distancia} km (fora da área de entrega)</span>`;
                        enderecoForaArea = true;
                    } else {
                        valorFreteElement.textContent = data.message || 'Erro ao calcular frete';
                        distanciaInfo.textContent = data.distancia ? `Distância: ${data.distancia} km` : '';
                        enderecoForaArea = true;
                    }
                    valorFreteAtual = 0;
                    calcularTotal();
                    return;
                }
                
                enderecoForaArea = false;
                valorFreteAtual = parseFloat(data.valor_frete);
                valorFreteElement.textContent = `R$ ${formatarPreco(valorFreteAtual)}`;
                distanciaInfo.textContent = `Distância: ${data.distancia} km`;
                
                calcularTotal();
                
            } catch (error) {
                console.error('Erro ao calcular frete:', error);
                valorFreteElement.textContent = 'Erro ao calcular frete';
                valorFreteAtual = 0;
                enderecoForaArea = true;
                calcularTotal();
            }
        };

        // Função para finalizar pedido
        window.finalizarPedido = function() {
            // Carregar dados do carrinho
            const carrinho = JSON.parse(localStorage.getItem('carrinho') || '{}');
            console.log('Carrinho completo:', carrinho);
            console.log('Produtos disponíveis:', todosProdutos);
            
            // Validar se há itens no carrinho
            if (Object.keys(carrinho).length === 0) {
                Swal.fire({
                    title: 'Carrinho Vazio',
                    text: 'Adicione produtos ao carrinho antes de finalizar o pedido',
                    icon: 'warning',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                });
                return;
            }

            // Validar se todos os produtos existem
            const produtosInvalidos = [];
            const carrinhoArray = Object.entries(carrinho).map(([id, item]) => {
                console.log('Verificando produto:', {
                    itemId: id,
                    produtoId: item.produto_id,
                    tipo: typeof item.produto_id
                });
                
                const produto = todosProdutos.find(p => {
                    console.log('Comparando:', {
                        produtoBanco: p.id,
                        tipoBanco: typeof p.id,
                        produtoCarrinho: item.produto_id,
                        tipoCarrinho: typeof item.produto_id,
                        iguais: String(p.id) === String(item.produto_id)
                    });
                    return String(p.id) === String(item.produto_id);
                });

                if (!produto) {
                    console.error('Produto não encontrado:', {
                        idBuscado: item.produto_id,
                        idsDisponiveis: todosProdutos.map(p => p.id)
                    });
                    produtosInvalidos.push(item.produto_id);
                } else {
                    console.log('Produto encontrado:', produto);
                }

                return {
                    produto_id: item.produto_id,
                    quantidade: item.quantidade,
                    complementos: item.complementos || [],
                    observacoes: item.observacoes || ''
                };
            });
            
            // Se houver produtos inválidos, mostrar erro
            if (produtosInvalidos.length > 0) {
                console.error('Produtos não encontrados:', produtosInvalidos);
                Swal.fire({
                    title: 'Erro no Carrinho',
                    text: 'Alguns produtos não estão mais disponíveis. Por favor, atualize seu carrinho.',
                    icon: 'error',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                }).then(() => {
                    // Remove produtos inválidos do carrinho
                    const carrinhoAtualizado = {...carrinho};
                    produtosInvalidos.forEach(prodId => {
                        Object.entries(carrinhoAtualizado).forEach(([key, item]) => {
                            if (String(item.produto_id) === String(prodId)) {
                                delete carrinhoAtualizado[key];
                            }
                        });
                    });
                    localStorage.setItem('carrinho', JSON.stringify(carrinhoAtualizado));
                    location.reload();
                });
                return;
            }

            const formaPagamento = document.querySelector('input[name="forma_pagamento"]:checked');
            if (!formaPagamento) {
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Por favor, selecione uma forma de pagamento',
                    icon: 'warning',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                });
                return;
            }

            const enderecoSelecionado = document.querySelector('input[name="endereco_entrega"]:checked');
            if (!enderecoSelecionado) {
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Por favor, selecione um endereço de entrega',
                    icon: 'warning',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                });
                return;
            }

            // Verifica se o endereço está fora da área de entrega
            if (enderecoForaArea) {
                Swal.fire({
                    title: 'Endereço fora da área!',
                    text: 'Não é possível finalizar o pedido para este endereço pois está fora da nossa área de entrega.',
                    icon: 'error',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                });
                return;
            }

            // Verifica se há valor de frete calculado
            if (valorFreteAtual === 0) {
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Não foi possível calcular o frete para este endereço',
                    icon: 'warning',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                });
                return;
            }

            // Informações do troco
            const precisaTroco = document.getElementById('precisa_troco')?.checked || false;
            const trocoPara = document.getElementById('troco_para')?.value || '';

            // Preparar dados do pedido
            const dadosPedido = {
                endereco_id: enderecoSelecionado.value,
                forma_pagamento_id: formaPagamento.value,
                carrinho: carrinhoArray,
                taxa_entrega: valorFreteAtual,
                precisa_troco: precisaTroco ? 1 : 0,
                troco_para: trocoPara || null,
                cupom_id: cupomAtual?.id || null,
                desconto_cupom: descontoCupom || 0
            };

            <?php if (!isset($_SESSION['usuario'])): ?>
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            // Validar valor do troco quando necessário
            if (precisaTroco && formaPagamento.value === '1') { // Assumindo que 1 é o ID do pagamento em dinheiro
                const valorTotal = Object.values(carrinho).reduce((total, item) => {
                    const produto = todosProdutos.find(p => String(p.id) === String(item.produto_id));
                    if (produto) {
                        let itemTotal = produto.preco * item.quantidade;
                        if (item.complementos) {
                            for (const complemento of item.complementos) {
                                itemTotal += parseFloat(complemento.opcao_preco) * item.quantidade;
                            }
                        }
                        return total + itemTotal;
                    }
                    return total;
                }, 0) + valorFreteAtual;

                const valorTroco = parseFloat(trocoPara);
                if (!trocoPara || isNaN(valorTroco) || valorTroco < valorTotal) {
                    Swal.fire({
                        title: 'Atenção!',
                        text: 'Por favor, informe um valor válido para o troco (deve ser maior que o total do pedido)',
                        icon: 'warning',
                        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                    });
                    return;
                }
            }

            console.log('Dados do pedido:', dadosPedido); // Debug

            // Enviar pedido para o servidor
            fetch('api/finalizar_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dadosPedido)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Erro ao finalizar pedido');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Limpa o carrinho
                    localStorage.removeItem('carrinho');
                    
                    Swal.fire({
                        title: 'Sucesso!',
                        text: 'Seu pedido foi realizado com sucesso!',
                        icon: 'success',
                        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                    }).then(() => {
                        window.location.href = 'pedidos.php';
                    });
                } else {
                    throw new Error(data.message || 'Erro ao finalizar pedido');
                }
            })
            .catch(error => {
                console.error('Erro ao finalizar pedido:', error);
                Swal.fire({
                    title: 'Erro!',
                    text: error.message || 'Ocorreu um erro ao finalizar o pedido',
                    icon: 'error',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                });
            });
        };

        // Inicializa a interface
        renderizarCarrinho();
        // Removido o carregamento automático do cupom
        // carregarCupomSalvo();

        // Adiciona evento de clique aos labels dos endereços
        document.addEventListener('click', function(e) {
            const label = e.target.closest('label[for^="endereco_"]');
            if (label) {
                // Remove a seleção de todos os labels
                document.querySelectorAll('label[for^="endereco_"]').forEach(l => {
                    l.classList.remove('border-theme', 'bg-white');
                });
                
                // Adiciona a seleção ao label clicado
                label.classList.add('border-theme', 'bg-white');
                
                // Marca o radio correspondente
                const radio = document.getElementById(label.getAttribute('for'));
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            }
        });

        // Selecionar automaticamente o endereço principal
        const enderecoPrincipal = enderecosUsuario.find(e => e.principal == 1);
        if (enderecoPrincipal) {
            const radioEndereco = document.querySelector(`input[name="endereco_entrega"][value="${enderecoPrincipal.id}"]`);
            if (radioEndereco) {
                radioEndereco.checked = true;
                calcularFrete(enderecoPrincipal.id);
            }
        }
    });

    // Função para lidar com a seleção de pagamento
    function handlePaymentSelection(input, formaNome) {
        // Remove a classe selected de todas as opções
        document.querySelectorAll('.address-option').forEach(option => {
            option.classList.remove('selected');
        });

        // Adiciona a classe selected na opção selecionada
        input.nextElementSibling.classList.add('selected');

        // Mostra/esconde opção de troco para pagamento em dinheiro
        const opcaoTroco = document.getElementById('opcao_troco');
        if (formaNome.toLowerCase().includes('dinheiro')) {
            opcaoTroco.classList.remove('hidden');
        } else {
            opcaoTroco.classList.add('hidden');
            // Reseta opções de troco
            document.getElementById('precisa_troco').checked = false;
            document.getElementById('valor_troco_container').classList.add('hidden');
        }
    }

    // Função para lidar com a seleção de troco
    function handleTrocoSelection(input) {
        // Remove a classe selected de todas as opções
        document.querySelectorAll('.address-option').forEach(option => {
            option.classList.remove('selected');
        });

        // Adiciona a classe selected na opção selecionada
        if (input.checked) {
            input.nextElementSibling.classList.add('selected');
            document.getElementById('valor_troco_container').classList.remove('hidden');
        } else {
            input.nextElementSibling.classList.remove('selected');
            document.getElementById('valor_troco_container').classList.add('hidden');
        }
    }
    </script>
</body>
</html>
