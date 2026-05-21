<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
require_once 'config/database.php';
require_once __DIR__ . '/includes/header.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $database = new Database();
    $db = $database->getConnection();

    // Busca configurações da loja
    $query = "SELECT * FROM configuracoes WHERE id = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// Busca categorias
$query = "SELECT c.id, c.nome FROM categorias c WHERE c.ativo = 1 ORDER BY c.nome";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca produtos
$query = "SELECT p.*, c.nome as categoria_nome 
            FROM produtos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.ativo = 1 
            ORDER BY c.nome, p.nome";
$stmt = $db->prepare($query);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['nome_loja'] ?? 'Delivery App'); ?></title>
    
    <!-- Favicon -->
    <?php if (!empty($config['favicon'])): ?>
        <?php
        $favicon = $config['favicon'];
        $ext = strtolower(pathinfo($favicon, PATHINFO_EXTENSION));
        $mime = [
            'ico' => 'image/x-icon',
            'png' => 'image/png',
            'svg' => 'image/svg+xml'
        ];
        $type = $mime[$ext] ?? 'image/x-icon';
        ?>
        <link rel="icon" type="<?php echo $type; ?>" href="<?php echo htmlspecialchars($favicon); ?>">
    <?php else: ?>
        <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .banner {
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .banner::before {
            content: '';
            display: block;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* Banner Mobile (até 768px) */
        @media (max-width: 768px) {
            .banner {
                height: 140px;
            }
            .banner::before {
                background: url('<?php echo !empty($config['banner']) ? htmlspecialchars($config['banner']) : 'assets/images/banner.svg'; ?>');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
            }
        }

        /* Banner PC (acima de 768px) */
        @media (min-width: 769px) {
            .banner {
                height: 140px;
                background-color: #f5f5f5;
            }
            .banner::before {
                background: url('<?php echo !empty($config['banner_pc']) ? htmlspecialchars($config['banner_pc']) : 'assets/images/banner.svg'; ?>');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
            }
        }
        .store-info {
            padding: 0.5rem;
            margin-left: 120px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding-top: 0.4rem;
            margin-bottom: 25px;
        }
        .store-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .store-name {
            font-size: 1.6rem;
            font-weight: bold;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .store-slogan {
            font-size: 1rem;
            color: #4b5563;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .store-logo {
            width: 100px;
            height: 100px;
            background: white;
            padding: 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            border: 3px solid white;
            position: absolute;
            left: 1rem;
            top: 110px;
            z-index: 10;
        }
        .store-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 12px;
        }
        .categoria-btn {
            background-color: #e5e7eb !important; /* Cinza mais escuro para botões não selecionados */
            color: #4b5563;
            transition: all 0.2s;
        }
        .categoria-btn.active {
            background-color: var(--theme-color) !important;
            color: white !important;
            border: none !important;
        }
        .categoria-btn:not(.active):hover {
            background-color: #d1d5db !important; /* Cinza um pouco mais escuro no hover */
        }
        .produto-card {
            transition: opacity 0.3s;
        }
        .hidden {
            display: none;
        }
        .share-button {
            padding: 0.25rem 0.5rem;
            color: #4b5563;
            border-radius: 6px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .share-button:hover {
            color: #2563eb;
            background-color: #f3f4f6;
        }

        /* Menu fixo na parte inferior - Novo estilo */
        .bottom-menu {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -1px 4px rgba(0,0,0,0.1);
            z-index: 50;
            padding: 0.75rem 0;
        }
        .menu-item {
            position: relative;
            transition: transform 0.2s ease;
        }
        .menu-item.active {
            color: #2563eb;
            transform: translateY(-2px);
        }
        .menu-item:not(.active) {
            color: #6b7280;
        }
        .menu-item:hover {
            color: #2563eb;
        }
        .menu-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 9999px;
            padding: 2px 6px;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }
        
        /* Ajuste para o conteúdo não ficar atrás do menu */
        .pb-menu {
            padding-bottom: 5rem;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 9999px !important;
            overflow: hidden;
        }
        .status-open {
            background-color: #10B981;
            box-shadow: 0 0 8px #10B981;
        }
        .status-closed {
            background-color: #EF4444;
            box-shadow: 0 0 8px #EF4444;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        }

        .modal-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            max-width: 90%;
            width: 400px;
        }

        .close-modal {
            position: absolute;
            right: 1rem;
            top: 0.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .close-modal:hover {
            color: #333;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="relative">
        <div class="banner"></div>
        
        <!-- Status no topo -->
        <div class="absolute top-3 right-3 z-50">
            <?php 
            require_once 'includes/verificar_horario.php';
            $status = verificarHorarioFuncionamento();
            ?>
            <span id="status-loja" class="inline-flex items-center px-4 py-1 rounded-lg bg-gray-50 border border-gray-200">
                <span class="relative w-2.5 h-2.5 mr-2 flex items-center justify-center status-indicator">
                    <span class="absolute inline-flex h-full w-full rounded-full opacity-75 <?php echo $status['status'] === 'open' ? 'bg-green-500' : 'bg-red-500'; ?> animate-ping"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 <?php echo $status['status'] === 'open' ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                </span>
                <span id="status-texto" class="text-sm font-medium <?php echo $status['status'] === 'open' ? 'text-green-800' : 'text-red-800'; ?>">
                    <?php 
                    $mensagem = strtolower($status['mensagem']);
                    echo '<span class="font-bold">' . ucfirst(substr($mensagem, 0, 1)) . '</span>' . substr($mensagem, 1);
                    ?>
                </span>
                <button class="ml-2 text-gray-400 hover:text-gray-600" onclick="openModal()">
                    <i class="far fa-clock"></i>
                </button>
            </span>
        </div>

        <!-- Modal de Horários -->
        <div id="horarioModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Horário de Funcionamento</h3>
                <div class="space-y-2">
                    <?php
                    $horarios_json = isset($config['horarios_funcionamento']) && !empty($config['horarios_funcionamento']) 
                        ? $config['horarios_funcionamento'] 
                        : '{"segunda":{"inicio":"","fim":""},"terca":{"inicio":"","fim":""},"quarta":{"inicio":"","fim":""},"quinta":{"inicio":"","fim":""},"sexta":{"inicio":"","fim":""},"sabado":{"inicio":"","fim":""},"domingo":{"inicio":"","fim":""}}';
                    $horarios = json_decode($horarios_json, true);
                    if (!is_array($horarios)) {
                        $horarios = [];
                    }
                    $dias_semana = [
                        'segunda' => 'Segunda',
                        'terca' => 'Terça',
                        'quarta' => 'Quarta',
                        'quinta' => 'Quinta',
                        'sexta' => 'Sexta',
                        'sabado' => 'Sábado',
                        'domingo' => 'Domingo'
                    ];
                    
                    foreach ($dias_semana as $dia_key => $dia_nome) {
                        $horario = isset($horarios[$dia_key]) ? "{$horarios[$dia_key]['inicio']} - {$horarios[$dia_key]['fim']}" : 'Fechado';
                        echo "<p class='flex justify-between py-1 border-b border-gray-100'>";
                        echo "<span class='text-gray-600'>{$dia_nome}:</span>";
                        echo "<span class='text-gray-800 font-medium'>{$horario}</span>";
                        echo "</p>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <script>
            function openModal() {
                document.getElementById('horarioModal').style.display = 'block';
            }

            function closeModal() {
                document.getElementById('horarioModal').style.display = 'none';
            }

            // Fechar modal quando clicar fora
            window.onclick = function(event) {
                const modal = document.getElementById('horarioModal');
                if (event.target == modal) {
                    closeModal();
                }
            }
        </script>

        <div class="store-logo">
            <img src="<?php echo !empty($config['logo']) ? htmlspecialchars($config['logo']) : 'assets/images/logo.svg'; ?>" alt="Logo">
        </div>

        <div>
            <div class="store-info">
                <div class="store-header">
                    <h1 class="store-name"><?php echo htmlspecialchars($config['nome_loja'] ?? ''); ?></h1>
                    <button class="share-button" onclick="compartilhar()">
                        <i class="fas fa-share-alt"></i>
                    </button>
                </div>
                <p class="store-slogan"><?php echo htmlspecialchars($config['slogan'] ?? ''); ?></p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4" style="margin-top: 0.6rem; margin-bottom: 1rem;">
            <div class="flex justify-center gap-8">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-theme mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-center">
                        <p class="font-medium text-gray-700">Pedido Min.</p>
                        <p class="text-gray-500">R$ <?php echo number_format($config['pedido_minimo'] ?? 0, 2, ',', '.'); ?></p>
                    </div>
                </div>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-theme mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <div class="text-sm text-center">
                        <p class="font-medium text-gray-700">Entrega</p>
                        <p class="text-gray-500"><?php echo htmlspecialchars($config['tempo_entrega'] ?? 30); ?> min</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-theme mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <div class="text-sm text-center">
                        <p class="font-medium text-gray-700">Local</p>
                        <p class="text-gray-500"><?php echo !empty($config['local']) ? htmlspecialchars($config['local']) : 'Local não definido'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navbar simplificada -->
        <nav class="bg-white shadow-lg sticky top-0 z-40 mt-[25px]">
            <div class="max-w-7xl mx-auto px-4">
                <!-- Barra de pesquisa -->
                <div class="py-4">
                    <div class="relative">
                        <input type="text" 
                               id="pesquisa" 
                               placeholder="Pesquisar produtos..." 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                            <i class="fas fa-search"></i>
                        </span>
                    </div>
                </div>
            </div>
        </nav>

        <div class="min-h-screen bg-gray-100 pb-24">
            <!-- Conteúdo Principal -->
            <div class="max-w-7xl mx-auto px-4 py-6">
                <!-- Categorias -->
                <div class="mb-8 overflow-x-auto">
                    <div class="flex space-x-4 pb-3">
                        <button onclick="filtrarPorCategoria(null)" 
                                class="categoria-btn px-6 py-3 rounded-full text-base font-medium bg-gray-100 text-gray-600"
                                data-categoria="">
                            Todas
                        </button>
                        <?php foreach ($categorias as $categoria): ?>
                            <button onclick="filtrarPorCategoria('<?php echo htmlspecialchars($categoria['id']); ?>')"
                                    class="categoria-btn px-6 py-3 rounded-full text-base font-medium bg-gray-100 text-gray-600"
                                    data-categoria="<?php echo htmlspecialchars($categoria['id']); ?>">
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <style>
                    .categoria-btn {
                        transition: all 0.2s ease;
                        background-color: #e5e7eb !important; /* Cinza mais escuro para botões não selecionados */
                    }
                    .categoria-btn.active {
                        background-color: var(--theme-color) !important;
                        color: white !important;
                        border: none !important;
                    }
                    .categoria-btn:not(.active):hover {
                        background-color: #d1d5db !important; /* Cinza um pouco mais escuro no hover */
                    }
                </style>

                <!-- Lista de Produtos -->
                <div id="lista-produtos" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pb-menu">
                    <?php foreach ($produtos as $produto): ?>
                    <div class="produto-card bg-white rounded-lg shadow-md overflow-hidden relative" 
                         data-nome="<?php echo htmlspecialchars(strtolower($produto['nome'])); ?>"
                         data-categoria="<?php echo htmlspecialchars($produto['categoria_id']); ?>">
                        <a href="produto.php?id=<?php echo $produto['id']; ?>" class="block">
                            <div class="flex h-full">
                                <div class="w-1/3 sm:w-1/4 relative">
                                    <?php if (!empty($produto['imagem'])): ?>
                                        <img src="<?php echo htmlspecialchars($produto['imagem']); ?>" 
                                             alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                                             class="absolute inset-0 w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="absolute inset-0 bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400 text-4xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="w-2/3 sm:w-3/4 p-4 flex flex-col">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                        <?php echo htmlspecialchars($produto['nome']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-500 mb-3">
                                        <?php echo htmlspecialchars($produto['descricao']); ?>
                                    </p>
                                    <div class="mt-auto">
                                        <span class="text-lg font-bold text-gray-900">
                                            R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
        const pesquisaInput = document.getElementById('pesquisa');
        const botoesCategorias = document.querySelectorAll('.categoria-btn');
        let categoriaAtual = null;

        // Função para filtrar produtos
        function filtrarProdutos() {
            const termoPesquisa = document.getElementById('pesquisa').value.toLowerCase();
            const produtos = document.querySelectorAll('.produto-card');
            
            produtos.forEach(produto => {
                const nome = produto.dataset.nome.toLowerCase();
                const categoria = produto.dataset.categoria;
                
                const correspondeNome = nome.includes(termoPesquisa);
                const correspondeCategoria = categoriaAtual === null || categoria === categoriaAtual;
                
                if (correspondeNome && correspondeCategoria) {
                    produto.style.display = '';
                } else {
                    produto.style.display = 'none';
                }
            });
        }

        function filtrarPorCategoria(categoria) {
            categoriaAtual = categoria;
            
            // Remove a classe active de todos os botões
            botoesCategorias.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Adiciona a classe active no botão selecionado
            const botaoAtivo = document.querySelector(`.categoria-btn[data-categoria="${categoria || ''}"]`);
            if (botaoAtivo) {
                botaoAtivo.classList.add('active');
            }
            
            filtrarProdutos();
        }

        // Event listener para pesquisa
        document.getElementById('pesquisa').addEventListener('input', filtrarProdutos);

        // Inicializa com "Todas" selecionado
        filtrarPorCategoria(null);
        </script>

        <script>
            function compartilhar() {
                if (navigator.share) {
                    navigator.share({
                        title: '<?php echo htmlspecialchars($config['nome_loja'] ?? ''); ?>',
                        text: '<?php echo htmlspecialchars($config['slogan'] ?? ''); ?>',
                        url: window.location.href
                    })
                    .catch(console.error);
                } else {
                    // Fallback para copiar o link
                    const el = document.createElement('textarea');
                    el.value = window.location.href;
                    document.body.appendChild(el);
                    el.select();
                    document.execCommand('copy');
                    document.body.removeChild(el);
                    alert('Link copiado para a área de transferência!');
                }
            }
        </script>

        <script>
            // Função para atualizar o status da loja
            function atualizarStatusLoja() {
                fetch('includes/verificar_horario.php', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const statusLoja = document.getElementById('status-loja');
                    const statusTexto = document.getElementById('status-texto');
                    const statusDot = statusLoja.querySelector('.relative');
                    const pingDot = statusLoja.querySelector('.animate-ping');
                    const staticDot = statusLoja.querySelector('.relative:not(.animate-ping)');
                    
                    if (data.status === 'open') {
                        pingDot.classList.remove('bg-red-500');
                        pingDot.classList.add('bg-green-500');
                        staticDot.classList.remove('bg-red-500');
                        staticDot.classList.add('bg-green-500');
                        statusTexto.classList.remove('text-red-800');
                        statusTexto.classList.add('text-green-800');
                    } else {
                        pingDot.classList.remove('bg-green-500');
                        pingDot.classList.add('bg-red-500');
                        staticDot.classList.remove('bg-green-500');
                        staticDot.classList.add('bg-red-500');
                        statusTexto.classList.remove('text-green-800');
                        statusTexto.classList.add('text-red-800');
                    }
                    
                    const mensagem = data.mensagem.toLowerCase();
                    statusTexto.innerHTML = '<span class="font-bold">' + 
                        mensagem.charAt(0).toUpperCase() + 
                        '</span>' + 
                        mensagem.slice(1);
                })
                .catch(error => console.error('Erro ao atualizar status:', error));
            }

            // Atualiza o status a cada 30 segundos
            setInterval(atualizarStatusLoja, 30000);

            function openModal() {
                document.getElementById('horarioModal').style.display = 'block';
            }

            function closeModal() {
                document.getElementById('horarioModal').style.display = 'none';
            }

            // Fechar modal quando clicar fora
            window.onclick = function(event) {
                const modal = document.getElementById('horarioModal');
                if (event.target == modal) {
                    closeModal();
                }
            }
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const botoesCategorias = document.querySelectorAll('.categoria-btn');
                let categoriaAtual = null;

                // Inicializa com "Todas" selecionado
                document.querySelector('.categoria-btn[data-categoria=""]').classList.add('active');

                function filtrarPorCategoria(categoria) {
                    categoriaAtual = categoria;
                    
                    // Remove a classe active de todos os botões
                    botoesCategorias.forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    // Adiciona a classe active no botão selecionado
                    const botaoAtivo = document.querySelector(`.categoria-btn[data-categoria="${categoria || ''}"]`);
                    if (botaoAtivo) {
                        botaoAtivo.classList.add('active');
                    }
                    
                    filtrarProdutos();
                }

                function filtrarProdutos() {
                    const produtos = document.querySelectorAll('.produto-card');
                    produtos.forEach(produto => {
                        if (!categoriaAtual || categoriaAtual === null || produto.dataset.categoria === categoriaAtual) {
                            produto.style.display = '';
                        } else {
                            produto.style.display = 'none';
                        }
                    });
                }

                botoesCategorias.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const categoria = btn.dataset.categoria;
                        filtrarPorCategoria(categoria);
                    });
                });
            });
        </script>

        <script>
            // Função para atualizar o status da loja
            function atualizarStatusLoja() {
                fetch('includes/verificar_horario.php', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const statusLoja = document.getElementById('status-loja');
                    const statusTexto = document.getElementById('status-texto');
                    const statusDot = statusLoja.querySelector('.relative');
                    const pingDot = statusLoja.querySelector('.animate-ping');
                    const staticDot = statusLoja.querySelector('.relative:not(.animate-ping)');
                    
                    if (data.status === 'open') {
                        pingDot.classList.remove('bg-red-500');
                        pingDot.classList.add('bg-green-500');
                        staticDot.classList.remove('bg-red-500');
                        staticDot.classList.add('bg-green-500');
                        statusTexto.classList.remove('text-red-800');
                        statusTexto.classList.add('text-green-800');
                    } else {
                        pingDot.classList.remove('bg-green-500');
                        pingDot.classList.add('bg-red-500');
                        staticDot.classList.remove('bg-green-500');
                        staticDot.classList.add('bg-red-500');
                        statusTexto.classList.remove('text-green-800');
                        statusTexto.classList.add('text-red-800');
                    }
                    
                    const mensagem = data.mensagem.toLowerCase();
                    statusTexto.innerHTML = '<span class="font-bold">' + 
                        mensagem.charAt(0).toUpperCase() + 
                        '</span>' + 
                        mensagem.slice(1);
                })
                .catch(error => console.error('Erro ao atualizar status:', error));
            }

            // Atualiza o status a cada 30 segundos
            setInterval(atualizarStatusLoja, 30000);

            function openModal() {
                document.getElementById('horarioModal').style.display = 'block';
            }

            function closeModal() {
                document.getElementById('horarioModal').style.display = 'none';
            }

            // Fechar modal quando clicar fora
            window.onclick = function(event) {
                const modal = document.getElementById('horarioModal');
                if (event.target == modal) {
                    closeModal();
                }
            }
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const horarioBtn = document.getElementById('horarioBtn');
                const horarioPopup = document.getElementById('horarioPopup');
                
                if (horarioBtn && horarioPopup) {
                    horarioBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        horarioPopup.classList.toggle('hidden');
                    });

                    document.addEventListener('click', function(e) {
                        if (!horarioPopup.contains(e.target) && e.target !== horarioBtn) {
                            horarioPopup.classList.add('hidden');
                        }
                    });
                }
            });
        </script>
    </div>

    <?php include 'includes/menu.php'; ?>
</body>
</html>
