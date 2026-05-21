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
$query = "SELECT * FROM produtos WHERE ativo = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$todos_produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="assets/css/menu.css">
    <link rel="stylesheet" href="assets/css/theme.css">
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
                <i class="fas fa-spinner fa-spin text-gray-400 text-4xl"></i>
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
                'PIX': 'qrcode',
                'Dinheiro': 'money-bill-wave',
                'Cartão de Crédito': 'credit-card',
                'Cartão de Débito': 'credit-card'
            };
            return icones[nome] || 'money-bill';
        }

        // Variável global para armazenar o valor do frete
        let valorFreteAtual = 0;

        // Variável global para controlar se o endereço está dentro da área de entrega
        let enderecoForaArea = false;

        // Função para atualizar apenas o total
        function atualizarTotal(subtotal) {
            const totalContainer = document.getElementById('total_container');
            if (!totalContainer) return;

            totalContainer.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <span class="text-lg">Subtotal</span>
                    <span class="text-lg">R$ ${formatarPreco(subtotal)}</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-lg">Frete</span>
                    <span class="text-lg" id="valor_frete">R$ ${formatarPreco(valorFreteAtual)}</span>
                </div>
                <div class="flex justify-between items-center border-t pt-4 mt-4">
                    <span class="text-xl font-bold">Total</span>
                    <span id="total_carrinho" class="text-xl font-bold">R$ ${formatarPreco(subtotal + valorFreteAtual)}</span>
                </div>
            `;
        }

        // Renderiza o carrinho
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
                            <a href="index.php" class="mt-4 inline-block text-purple-600 hover:text-purple-800">
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
                                            <button onclick="alterarQuantidade('${itemId}', -1)" class="px-2 py-1 hover:bg-gray-100">-</button>
                                            <span class="px-2 py-1 border-x">${item.quantidade}</span>
                                            <button onclick="alterarQuantidade('${itemId}', 1)" class="px-2 py-1 hover:bg-gray-100">+</button>
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
                                           class="flex-1 min-w-0 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-600 focus:border-purple-600 outline-none">
                                    <button onclick="aplicarCupom()" 
                                            class="whitespace-nowrap theme-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition-colors">
                                        Aplicar
                                    </button>
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
                                                       class="block p-4 w-full border rounded-lg cursor-pointer transition-all
                                                              hover:bg-purple-50 peer-checked:border-purple-600 peer-checked:bg-purple-50">
                                                    <div class="flex items-start gap-3">
                                                        <i class="fas fa-home text-gray-400 mt-1"></i>
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
                            <h3 class="text-lg font-semibold mb-4">Forma de Pagamento</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                ${formasPagamento.map(forma => `
                                    <div class="relative">
                                        <input type="radio" 
                                               name="forma_pagamento" 
                                               id="forma_${forma.id}" 
                                               value="${forma.id}"
                                               onchange="toggleTroco('${forma.nome}')"
                                               class="hidden peer">
                                        <label for="forma_${forma.id}" 
                                               class="flex items-center gap-3 p-4 w-full border rounded-lg cursor-pointer transition-all
                                                      hover:bg-purple-50 peer-checked:border-purple-600 peer-checked:bg-purple-50">
                                            <i class="fas fa-${getIconeFormaPagamento(forma.nome)} text-lg text-gray-600"></i>
                                            <span class="font-medium">${forma.nome}</span>
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
                                           class="hidden peer"
                                           onchange="toggleValorTroco(this)">
                                    <label for="precisa_troco" 
                                           class="flex items-center gap-3 p-4 w-full border rounded-lg cursor-pointer
                                                  hover:bg-purple-50 peer-checked:border-purple-600 peer-checked:bg-purple-50">
                                        <i class="fas fa-exchange-alt text-lg text-gray-600"></i>
                                        <span class="font-medium">Precisa de troco?</span>
                                    </label>
                                </div>
                                <div id="valor_troco_container" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Troco para quanto?</label>
                                    <input type="number" 
                                           id="troco_para" 
                                           name="troco_para" 
                                           step="0.01"
                                           class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-purple-600 outline-none"
                                           placeholder="Digite o valor">
                                </div>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="bg-white rounded-lg shadow p-6" id="total_container">
                        </div>
                        
                        <div class="mt-4">
                            <button onclick="finalizarPedido()" 
                                    class="w-full theme-primary text-white py-3 rounded-lg hover:opacity-90 transition-colors">
                                Finalizar Pedido
                            </button>
                        </div>
                    </div>
                `;
                container.innerHTML = html;
                
                // Atualiza o total separadamente
                atualizarTotal(total);

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
                const carrinho = JSON.parse(localStorage.getItem('carrinho') || '{}');
                delete carrinho[itemId];
                localStorage.setItem('carrinho', JSON.stringify(carrinho));
                renderizarCarrinho();
                
                // Atualiza o badge do carrinho
                const badge = document.getElementById('carrinho-contador');
                if (badge) {
                    const quantidade = Object.values(carrinho).reduce((total, item) => total + (item.quantidade || 0), 0);
                    badge.textContent = quantidade;
                    badge.style.display = quantidade > 0 ? 'flex' : 'none';
                }
            } catch (error) {
                console.error('Erro ao remover item:', error);
                Swal.fire({
                    title: 'Erro',
                    text: 'Não foi possível remover o item do carrinho',
                    icon: 'error'
                });
            }
        };

        // Função para alterar a quantidade
        window.alterarQuantidade = function(itemId, delta) {
            try {
                const carrinho = JSON.parse(localStorage.getItem('carrinho') || '{}');
                if (!carrinho[itemId]) return;

                const novaQuantidade = (carrinho[itemId].quantidade || 0) + delta;
                
                if (novaQuantidade <= 0) {
                    // Se a quantidade chegar a 0, remove o item
                    removerItem(itemId);
                    return;
                }

                carrinho[itemId].quantidade = novaQuantidade;
                localStorage.setItem('carrinho', JSON.stringify(carrinho));
                
                // Atualiza a interface
                renderizarCarrinho();
                
                // Atualiza o badge do carrinho
                const badge = document.getElementById('carrinho-contador');
                if (badge) {
                    const quantidade = Object.values(carrinho).reduce((total, item) => total + (item.quantidade || 0), 0);
                    badge.textContent = quantidade;
                    badge.style.display = quantidade > 0 ? 'flex' : 'none';
                }
            } catch (error) {
                console.error('Erro ao alterar quantidade:', error);
                Swal.fire({
                    title: 'Erro',
                    text: 'Não foi possível alterar a quantidade do item',
                    icon: 'error'
                });
            }
        };

        // Função para aplicar cupom (apenas visual por enquanto)
        window.aplicarCupom = function() {
            const cupom = document.getElementById('cupom').value.trim();
            if (!cupom) {
                Swal.fire({
                    title: 'Atenção',
                    text: 'Digite um cupom válido',
                    icon: 'warning'
                });
                return;
            }

            Swal.fire({
                title: 'Cupom aplicado!',
                text: 'Este é apenas um exemplo visual, o cupom ainda não tem função',
                icon: 'success'
            });
        };

        // Função para mostrar/esconder opção de troco
        window.toggleTroco = function(formaPagamento) {
            const opcaoTroco = document.getElementById('opcao_troco');
            if (formaPagamento.toLowerCase() === 'dinheiro') {
                opcaoTroco.classList.remove('hidden');
            } else {
                opcaoTroco.classList.add('hidden');
                document.getElementById('precisa_troco').checked = false;
                document.getElementById('valor_troco_container').classList.add('hidden');
                document.getElementById('troco_para').value = '';
            }
        }

        // Função para mostrar/esconder campo de valor do troco
        window.toggleValorTroco = function(checkbox) {
            const valorTrocoContainer = document.getElementById('valor_troco_container');
            if (checkbox.checked) {
                valorTrocoContainer.classList.remove('hidden');
            } else {
                valorTrocoContainer.classList.add('hidden');
                document.getElementById('troco_para').value = '';
            }
        }

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

        // Função para calcular o total
        function calcularTotal() {
            const carrinhoSalvo = localStorage.getItem('carrinho');
            if (!carrinhoSalvo) return;

            const carrinho = JSON.parse(carrinhoSalvo);
            let subtotal = 0;

            // Calcula o subtotal dos produtos
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

            // Atualiza o container do total
            const totalContainer = document.getElementById('total_container');
            if (totalContainer) {
                totalContainer.innerHTML = `
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-lg">Subtotal</span>
                        <span class="text-lg">R$ ${formatarPreco(subtotal)}</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-lg">Frete</span>
                        <span class="text-lg">R$ ${formatarPreco(valorFreteAtual)}</span>
                    </div>
                    <div class="flex justify-between items-center border-t pt-4 mt-4">
                        <span class="text-xl font-bold">Total</span>
                        <span class="text-xl font-bold">R$ ${formatarPreco(subtotal + valorFreteAtual)}</span>
                    </div>
                `;
            }
        }

        // Função para finalizar pedido
        window.finalizarPedido = function() {
            const formaPagamento = document.querySelector('input[name="forma_pagamento"]:checked');
            if (!formaPagamento) {
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Por favor, selecione uma forma de pagamento',
                    icon: 'warning'
                });
                return;
            }

            const enderecoSelecionado = document.querySelector('input[name="endereco_entrega"]:checked');
            if (!enderecoSelecionado) {
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Por favor, selecione um endereço de entrega',
                    icon: 'warning'
                });
                return;
            }

            // Verifica se o endereço está fora da área de entrega
            if (enderecoForaArea) {
                Swal.fire({
                    title: 'Endereço fora da área!',
                    text: 'Não é possível finalizar o pedido para este endereço pois está fora da nossa área de entrega.',
                    icon: 'error'
                });
                return;
            }

            // Verifica se há valor de frete calculado
            if (valorFreteAtual === 0) {
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Não foi possível calcular o frete para este endereço',
                    icon: 'warning'
                });
                return;
            }

            // Informações do troco
            const precisaTroco = document.getElementById('precisa_troco')?.checked || false;
            const trocoPara = document.getElementById('troco_para')?.value || '';

            // Carregar dados do carrinho
            const carrinho = JSON.parse(localStorage.getItem('carrinho') || '{}');
            
            // Preparar dados do pedido
            const carrinhoArray = Object.entries(carrinho).map(([id, item]) => ({
                produto_id: item.produto_id,
                quantidade: item.quantidade,
                complementos: item.complementos || [],
                observacoes: item.observacoes || ''
            }));

            const dadosPedido = {
                endereco_id: enderecoSelecionado.value,
                forma_pagamento_id: formaPagamento.value,
                carrinho: carrinhoArray,
                taxa_entrega: valorFreteAtual,
                precisa_troco: precisaTroco ? 1 : 0,
                troco_para: trocoPara || null
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
                        icon: 'warning'
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
                        icon: 'success'
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
                    icon: 'error'
                });
            });
        };

        // Inicializa a interface
        renderizarCarrinho();

        // Adiciona evento de clique aos labels dos endereços
        document.addEventListener('click', function(e) {
            const label = e.target.closest('label[for^="endereco_"]');
            if (label) {
                // Remove a seleção de todos os labels
                document.querySelectorAll('label[for^="endereco_"]').forEach(l => {
                    l.classList.remove('border-purple-600', 'bg-purple-50');
                });
                
                // Adiciona a seleção ao label clicado
                label.classList.add('border-purple-600', 'bg-purple-50');
                
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
    </script>
</body>
</html>
