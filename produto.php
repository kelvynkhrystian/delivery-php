<?php
session_start();
require_once 'config/database.php';
require_once 'includes/verificar_horario.php';
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = array();
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Verifica o horário de funcionamento
$status_loja = verificarHorarioFuncionamento();

// Busca o produto
$query = "SELECT p.*, c.nome as categoria_nome 
          FROM produtos p 
          LEFT JOIN categorias c ON p.categoria_id = c.id 
          WHERE p.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_GET['id']]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    header('Location: index.php');
    exit;
}

// Buscar complementos do produto
$query = "SELECT c.* 
          FROM complementos c 
          INNER JOIN produto_complementos pc ON c.id = pc.complemento_id 
          WHERE pc.produto_id = ? AND c.ativo = 1
          ORDER BY 
             CASE 
                 WHEN LOWER(c.nome) LIKE 'extra%' THEN 1 
                 ELSE 0 
             END,
             c.ordem ASC, 
             c.id ASC";
$stmt = $db->prepare($query);
$stmt->execute([$produto['id']]);
$complementos_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiza os complementos e suas opções
$complementos = [];
foreach ($complementos_raw as $row) {
    $complemento_id = $row['id'];
    if (!isset($complementos[$complemento_id])) {
        $complementos[$complemento_id] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'descricao' => $row['descricao'],
            'min_escolhas' => $row['min_escolhas'],
            'max_escolhas' => $row['max_escolhas'],
            'opcoes' => []
        ];
    }
    
    // Busca as opções do complemento
    $query = "SELECT co.id as opcao_id, co.nome as opcao_nome, co.preco as opcao_preco 
              FROM complemento_opcoes co 
              WHERE co.complemento_id = ? 
              ORDER BY co.nome ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([$complemento_id]);
    $opcoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adiciona as opções ao complemento
    $complementos[$complemento_id]['opcoes'] = $opcoes;
}
$complementos = array_values($complementos);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($produto['nome']); ?> - Cardápio Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/menu.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Estilo para o botão de adicionar ao carrinho */
        .add-to-cart-btn {
            background-color: var(--theme-color) !important;
            transition: opacity 0.2s;
        }
        .add-to-cart-btn:hover {
            opacity: 0.9;
        }

        /* Ícone na cor do tema */
        .theme-icon {
            color: var(--theme-color) !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="javascript:history.back()" class="text-gray-600 hover:text-gray-900 mr-4">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <h1 class="text-xl font-semibold text-gray-800">Detalhes do Produto</h1>
                </div>
            </div>
        </div>
    </nav>

    <!-- Imagem do Produto -->
    <div class="w-full h-64 md:h-96 bg-gray-200">
        <?php if ($produto['imagem']): ?>
            <img src="<?php echo htmlspecialchars($produto['imagem']); ?>" 
                 alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                 class="w-full h-full object-cover">
        <?php else: ?>
            <div class="w-full h-full flex items-center justify-center">
                <i class="fas fa-image text-gray-400 text-6xl"></i>
            </div>
        <?php endif; ?>
    </div>

    <!-- Detalhes do Produto -->
    <div class="container mx-auto px-4 py-8 pb-24">
        <div class="bg-white rounded-lg shadow-md p-6">
            <!-- Cabeçalho -->
            <div class="mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($produto['nome']); ?>
                        </h1>
                        <?php if ($produto['categoria_nome']): ?>
                        <span class="inline-block mt-2 px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800 rounded-full">
                            <?php echo htmlspecialchars($produto['categoria_nome']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-2xl font-bold text-gray-900">
                        R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                    </div>
                </div>
            </div>

            <!-- Descrição -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Descrição</h2>
                <p class="text-gray-700 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars($produto['descricao'])); ?>
                </p>
            </div>

            <!-- Complementos -->
            <?php if ($complementos): ?>
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Complementos</h2>
                <?php foreach ($complementos as $complemento): ?>
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($complemento['nome']); ?></h3>
                    <p class="text-gray-700 leading-relaxed"><?php echo htmlspecialchars($complemento['descricao']); ?></p>
                    
                    <!-- Informações do complemento -->
                    <div class="mt-2 space-y-1">
                        <p class="text-sm font-medium text-gray-700">
                            <i class="fas fa-check-circle mr-1 theme-icon"></i>
                            <?php if ($complemento['min_escolhas'] == 0 && $complemento['max_escolhas'] == 0): ?>
                                Escolha livremente
                            <?php elseif ($complemento['min_escolhas'] == $complemento['max_escolhas']): ?>
                                Escolha exatamente <?php echo $complemento['min_escolhas']; ?> <?php echo $complemento['min_escolhas'] == 1 ? 'opção' : 'opções'; ?>
                            <?php else: ?>
                                Escolha de <?php echo $complemento['min_escolhas']; ?> a <?php echo $complemento['max_escolhas']; ?> opções
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if ($complemento['opcoes']): ?>
                    <div class="mt-3">
                        <?php foreach ($complemento['opcoes'] as $opcao): ?>
                        <div class="flex items-center mb-2">
                            <input type="checkbox" 
                                   id="opcao-<?php echo $opcao['opcao_id']; ?>" 
                                   name="opcoes[]" 
                                   value="<?php echo $opcao['opcao_id']; ?>"
                                   data-preco="<?php echo $opcao['opcao_preco']; ?>"
                                   onchange="atualizarPrecoTotal()">
                            <label for="opcao-<?php echo $opcao['opcao_id']; ?>" class="ml-2 text-gray-700"><?php echo htmlspecialchars($opcao['opcao_nome']); ?> (R$ <?php echo number_format($opcao['opcao_preco'], 2, ',', '.'); ?>)</label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Observações -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Observações</h2>
                <textarea id="observacoes" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" 
                          rows="3" placeholder="Alguma observação para o seu pedido?"></textarea>
            </div>
        </div>
    </div>

    <!-- Menu inferior com controles de quantidade e adicionar ao carrinho -->
    <div class="fixed bottom-0 left-0 right-0 bg-white shadow-lg border-t">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <button onclick="diminuirQuantidade()" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                        <i class="fas fa-minus text-gray-600"></i>
                    </button>
                    <span id="quantidade" class="mx-4 text-xl font-semibold">1</span>
                    <button onclick="aumentarQuantidade()" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                        <i class="fas fa-plus text-gray-600"></i>
                    </button>
                </div>
                <button onclick="adicionarAoCarrinho()" 
                        class="add-to-cart-btn px-8 py-3 rounded-lg text-white font-semibold transition-colors duration-200">
                    Adicionar - R$ <span id="preco-total"><?php 
                        $preco_total = $produto['preco'];
                        // Soma os preços dos complementos selecionados
                        if (isset($_POST['opcoes'])) {
                            foreach ($_POST['opcoes'] as $opcao_id) {
                                foreach ($complementos as $complemento) {
                                    foreach ($complemento['opcoes'] as $opcao) {
                                        if ($opcao['opcao_id'] == $opcao_id) {
                                            $preco_total += $opcao['opcao_preco'];
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        echo number_format($preco_total, 2, ',', '.');
                    ?></span>
                </button>
            </div>
        </div>
    </div>

    <script>
        const produto = <?php echo json_encode($produto); ?>;
        const complementos = <?php echo json_encode($complementos); ?>;
        const statusLoja = <?php echo json_encode($status_loja); ?>;
        
        let quantidade = 1;

        function atualizarPrecoTotal() {
            let precoBase = parseFloat(produto.preco);
            let precoComplementos = 0;

            // Soma o preço dos complementos selecionados usando o data-preco
            document.querySelectorAll('input[name="opcoes[]"]:checked').forEach(checkbox => {
                precoComplementos += parseFloat(checkbox.dataset.preco || 0);
            });

            // Calcula o preço total
            const precoTotal = (precoBase + precoComplementos) * quantidade;
            
            // Atualiza o preço total na interface
            document.getElementById('preco-total').textContent = precoTotal.toFixed(2).replace('.', ',');
        }

        function diminuirQuantidade() {
            if (quantidade > 1) {
                quantidade--;
                document.getElementById('quantidade').textContent = quantidade;
                atualizarPrecoTotal();
            }
        }

        function aumentarQuantidade() {
            quantidade++;
            document.getElementById('quantidade').textContent = quantidade;
            atualizarPrecoTotal();
        }

        // Função para inicializar os eventos
        function inicializarEventos() {
            // Adiciona eventos aos checkboxes de complementos
            document.querySelectorAll('input[name="opcoes[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', atualizarPrecoTotal);
            });

            // Atualiza o preço inicial
            atualizarPrecoTotal();
        }

        // Garante que o código só será executado depois que a página estiver totalmente carregada
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarEventos);
        } else {
            inicializarEventos();
        }

        function adicionarAoCarrinho() {
            console.log('Função adicionarAoCarrinho chamada');

            // Verificar status da loja primeiro
            if (!statusLoja.aberto) {
                Swal.fire({
                    title: 'Loja Fechada',
                    text: 'No momento não estamos aceitando pedidos',
                    icon: 'warning',
                    confirmButtonColor: 'var(--theme-color)'
                });
                return;
            }

            // Validar complementos obrigatórios
            const erros = validarComplementos();
            if (erros.length > 0) {
                Swal.fire({
                    title: 'Atenção!',
                    html: erros.join('<br>'),
                    icon: 'warning',
                    confirmButtonColor: 'var(--theme-color)'
                });
                return;
            }

            // Verificar complementos não selecionados
            const complementosVazios = [];
            if (complementos && complementos.length > 0) {
                complementos.forEach(complemento => {
                    const opcoesChecked = Array.from(document.querySelectorAll(`input[name="opcoes[]"]:checked`))
                        .filter(opcao => complemento.opcoes.some(op => String(op.opcao_id) === String(opcao.value)));
                    
                    if (opcoesChecked.length === 0) {
                        complementosVazios.push(complemento.nome);
                    }
                });
            }

            // Se houver complementos não selecionados, mostrar confirmação
            if (complementosVazios.length > 0) {
                Swal.fire({
                    title: 'Tem certeza?',
                    html: `Você não selecionou nenhuma opção nos seguintes complementos:<br><br>` +
                          `<ul class="text-left list-disc list-inside">` +
                          complementosVazios.map(nome => `<li>${nome}</li>`).join('') +
                          `</ul><br>` +
                          `Deseja continuar mesmo assim?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, continuar',
                    cancelButtonText: 'Não, voltar',
                    confirmButtonColor: 'var(--theme-color)',
                    cancelButtonColor: '#ef4444'
                }).then((result) => {
                    if (result.isConfirmed) {
                        adicionarAoCarrinhoConfirmado();
                    }
                });
                return;
            }

            // Se não houver complementos vazios, adicionar direto
            adicionarAoCarrinhoConfirmado();
        }

        function adicionarAoCarrinhoConfirmado() {
            try {
                // Pegar complementos selecionados
                const complementosSelecionados = [];
                if (complementos && complementos.length > 0) {
                    const opcoesChecked = document.querySelectorAll('input[name="opcoes[]"]:checked');
                    console.log('Opções selecionadas:', opcoesChecked);
                    
                    opcoesChecked.forEach(opcaoElement => {
                        const opcaoId = opcaoElement.value;
                        console.log('Procurando opção:', opcaoId);
                        
                        // Encontrar o complemento e a opção correspondente
                        complementos.forEach(complemento => {
                            const opcao = complemento.opcoes.find(op => String(op.opcao_id) === String(opcaoId));
                            if (opcao) {
                                console.log('Complemento encontrado:', complemento.nome, 'Opção:', opcao.opcao_nome);
                                complementosSelecionados.push({
                                    complemento_id: complemento.id,
                                    complemento_nome: complemento.nome,
                                    opcao_id: opcao.opcao_id,
                                    opcao_nome: opcao.opcao_nome,
                                    opcao_preco: Number(opcao.opcao_preco)
                                });
                            }
                        });
                    });
                }
                console.log('Complementos selecionados:', complementosSelecionados);

                // Pegar observações
                const observacoes = document.getElementById('observacoes')?.value.trim() || '';

                try {
                    // Pega o carrinho atual do localStorage ou cria um novo
                    let carrinho = {};
                    const carrinhoAtual = localStorage.getItem('carrinho');
                    if (carrinhoAtual) {
                        try {
                            carrinho = JSON.parse(carrinhoAtual);
                        } catch (e) {
                            console.error('Erro ao ler carrinho:', e);
                            carrinho = {};
                        }
                    }
                    
                    // Cria um ID único para este item (produto + complementos)
                    const itemId = `${produto.id}_${Date.now()}`;
                    
                    // Adiciona o item ao carrinho
                    carrinho[itemId] = {
                        produto_id: produto.id,
                        nome: produto.nome,
                        preco: produto.preco,
                        quantidade: quantidade,
                        complementos: complementosSelecionados,
                        observacoes: observacoes
                    };
                    
                    console.log('Salvando no carrinho:', carrinho[itemId]);
                    console.log('Carrinho completo:', carrinho);
                    
                    // Salva no localStorage
                    localStorage.setItem('carrinho', JSON.stringify(carrinho));
                    
                    // Mostra mensagem de sucesso
                    Swal.fire({
                        title: 'Sucesso!',
                        text: 'Produto adicionado ao carrinho',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Ir para o Carrinho',
                        cancelButtonText: 'Continuar Comprando',
                        confirmButtonColor: 'var(--theme-color)'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'carrinho.php';
                        } else {
                            window.location.href = 'index.php';
                        }
                    });
                    
                    // Atualiza o badge do carrinho
                    if (typeof atualizarBadgeCarrinho === 'function') {
                        atualizarBadgeCarrinho();
                    }
                } catch (error) {
                    console.error('Erro ao adicionar ao carrinho:', error);
                    Swal.fire({
                        title: 'Erro',
                        text: 'Ocorreu um erro ao adicionar o produto ao carrinho',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: 'var(--theme-color)'
                    });
                }
            } catch (error) {
                console.error('Erro ao adicionar ao carrinho:', error);
                Swal.fire({
                    title: 'Erro',
                    text: 'Ocorreu um erro ao adicionar o produto ao carrinho',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: 'var(--theme-color)'
                });
            }
        }

        // Função para validar complementos
        function validarComplementos() {
            let erros = [];
            
            if (!complementos || complementos.length === 0) {
                return erros;
            }

            complementos.forEach(complemento => {
                const opcoesChecked = Array.from(document.querySelectorAll(`input[name="opcoes[]"]:checked`))
                    .filter(opcao => complemento.opcoes.some(op => String(op.opcao_id) === String(opcao.value)));
                
                // Se o complemento é obrigatório e tem min_escolhas maior que 0
                if (complemento.min_escolhas > 0 && opcoesChecked.length < complemento.min_escolhas) {
                    erros.push(`Selecione pelo menos ${complemento.min_escolhas} opção(ões) em "${complemento.nome}"`);
                }
                
                // Se tem max_escolhas definido e foi selecionado mais que o permitido
                if (complemento.max_escolhas > 0 && opcoesChecked.length > complemento.max_escolhas) {
                    erros.push(`Selecione no máximo ${complemento.max_escolhas} opção(ões) em "${complemento.nome}"`);
                }
            });
            
            return erros;
        }

        // Função para atualizar o badge do carrinho
        function atualizarBadgeCarrinho() {
            const badge = document.getElementById('carrinho-contador');
            if (!badge) return;
            
            try {
                const carrinho = JSON.parse(localStorage.getItem('carrinho')) || {};
                const quantidade = Object.values(carrinho).reduce((total, item) => total + (item.quantidade || 0), 0);
                
                badge.textContent = quantidade;
                badge.style.display = quantidade > 0 ? 'flex' : 'none';
            } catch (error) {
                console.error('Erro ao atualizar badge:', error);
            }
        }

        // Atualiza o badge ao carregar a página
        document.addEventListener('DOMContentLoaded', atualizarBadgeCarrinho);
    </script>
</body>
</html>
