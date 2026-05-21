<?php
session_start();
require_once '../config/database.php';
require_once 'includes/load_config.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Carrega as configurações
$config = carregarConfiguracoes($db);

// Busca todos os produtos
$query = "SELECT p.*, c.nome as categoria_nome 
          FROM produtos p 
          LEFT JOIN categorias c ON p.categoria_id = c.id 
          ORDER BY p.nome";
$stmt = $db->prepare($query);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Conta total de produtos ativos
$query = "SELECT COUNT(*) as total, 
          SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativos 
          FROM produtos";
$stmt = $db->prepare($query);
$stmt->execute();
$totais = $stmt->fetch(PDO::FETCH_ASSOC);

// Busca as categorias para o formulário
$query = "SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
    <style>
        :root {
            --theme-color: <?php echo $config['cor_tema'] ?? '#8B5CF6'; ?>;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-2xl font-semibold">Produtos</span>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex h-screen bg-gray-100">
        <!-- Menu Lateral -->
        <?php include 'includes/menu.php'; ?>

        <!-- Conteúdo Principal -->
        <div class="flex-1 overflow-auto p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Cabeçalho com Estatísticas -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <!-- Botão Novo Produto -->
                    <div class="sm:col-span-1">
                        <a href="adicionar_produto.php" class="w-full sm:w-auto bg-[var(--theme-color)] text-white px-6 py-3 rounded-lg hover:brightness-90 transition-colors duration-200 inline-flex items-center justify-center">
                            <i class="fas fa-plus mr-2"></i>Novo Produto
                        </a>
                    </div>
                    
                    <!-- Cards de Estatísticas -->
                    <div class="sm:col-span-2 grid grid-cols-2 gap-4">
                        <div class="bg-white px-6 py-3 rounded-lg shadow-sm">
                            <div class="text-sm text-gray-500">Total de Produtos</div>
                            <div class="text-xl font-semibold"><?php echo $totais['total']; ?></div>
                        </div>
                        <div class="bg-white px-6 py-3 rounded-lg shadow-sm">
                            <div class="text-sm text-gray-500">Produtos Ativos</div>
                            <div class="text-xl font-semibold text-green-600"><?php echo $totais['ativos']; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Produtos -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <!-- Filtros e Pesquisa -->
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex flex-col md:flex-row md:items-center md:space-x-4 space-y-4 md:space-y-0">
                            <div class="flex-1">
                                <input type="text" id="pesquisa" placeholder="Pesquisar produtos..." 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                            <div class="flex-1">
                                <select id="filtro_categoria" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                    <option value="">Todas as categorias</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex-1">
                                <select id="filtro_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                    <option value="">Todos os status</option>
                                    <option value="1">Ativo</option>
                                    <option value="0">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Aviso de Duplo Clique -->
                    <div class="bg-blue-50 p-4 border-b border-blue-100">
                        <div class="flex items-center text-blue-800">
                            <i class="fas fa-info-circle mr-2 text-xl"></i>
                            <span>Dê um duplo clique em qualquer produto para editar ou excluir</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Produto
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Categoria
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Preço
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="lista-produtos">
                                <?php foreach ($produtos as $produto): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150 cursor-pointer" ondblclick="mostrarProduto(<?php echo htmlspecialchars(json_encode([
                                    'id' => $produto['id'],
                                    'nome' => $produto['nome'],
                                    'imagem' => !empty($produto['imagem']) ? '../' . $produto['imagem'] : '../assets/img/produto-padrao.svg',
                                    'descricao' => $produto['descricao'] ?? '',
                                    'preco' => number_format($produto['preco'], 2, ',', '.'),
                                    'categoria' => $produto['categoria_nome'] ?? 'Sem categoria',
                                    'ativo' => $produto['ativo']
                                ])); ?>)">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-12 w-12">
                                                <img class="h-12 w-12 rounded-lg object-cover shadow-sm" 
                                                     src="<?php echo !empty($produto['imagem']) ? '../' . $produto['imagem'] : '../assets/img/produto-padrao.svg'; ?>" 
                                                     alt="">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($produto['nome']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500 max-w-xs truncate">
                                                    <?php echo htmlspecialchars($produto['descricao'] ?? ''); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 text-sm rounded-full <?php echo $produto['categoria_nome'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo htmlspecialchars($produto['categoria_nome'] ?? 'Sem categoria'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 text-sm rounded-full <?php echo $produto['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $produto['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Ações -->
    <div id="modalProduto" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 overflow-hidden transform transition-all">
            <div class="relative">
                <img id="modal-imagem" class="w-full h-48 object-cover" src="" alt="">
                <button onclick="fecharModal()" class="absolute top-2 right-2 text-white hover:text-gray-200 transition-colors duration-200">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div class="p-6">
                <h2 id="modalTitulo" class="text-2xl font-bold mb-2"></h2>
                <p id="modal-descricao" class="text-gray-600 mb-4"></p>
                
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <span class="text-sm text-gray-500">Categoria</span>
                        <div id="modal-categoria" class="font-medium"></div>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Preço</span>
                        <div id="modal-preco" class="font-medium"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <button onclick="editarProduto()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-edit mr-2"></i>Editar
                    </button>
                    <button onclick="duplicarProduto()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-copy mr-2"></i>Duplicar
                    </button>
                    <button onclick="excluirProdutoConfirm()" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-trash mr-2"></i>Excluir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let produtoSelecionado = null;

        function mostrarProduto(produto) {
            produtoSelecionado = produto;
            
            // Atualiza o conteúdo do modal
            document.getElementById('modal-imagem').src = produto.imagem;
            document.getElementById('modalTitulo').textContent = produto.nome;
            document.getElementById('modal-descricao').textContent = produto.descricao;
            document.getElementById('modal-categoria').textContent = produto.categoria;
            document.getElementById('modal-preco').textContent = 'R$ ' + produto.preco;

            // Mostra o modal com animação
            document.getElementById('modalProduto').classList.remove('hidden');
            document.getElementById('modalProduto').classList.add('flex');
        }

        function fecharModal() {
            // Esconde o modal com animação
            document.getElementById('modalProduto').classList.remove('flex');
            document.getElementById('modalProduto').classList.add('hidden');
        }

        async function excluirProdutoConfirm() {
            if (!produtoSelecionado) return;

            const result = await Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não pode ser desfeita!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch('excluir_produto.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: produtoSelecionado.id
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: 'Produto excluído com sucesso!',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: data.message || 'Erro ao excluir produto'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Erro ao excluir produto: ' + error.message
                    });
                }
            }

            fecharModal();
        }

        async function duplicarProduto() {
            if (!produtoSelecionado) return;

            try {
                const response = await fetch('duplicar_produto.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: produtoSelecionado.id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: data.message
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao duplicar produto: ' + error.message
                });
            }

            fecharModal();
        }

        function editarProduto() {
            if (produtoSelecionado) {
                window.location.href = `editar_produto.php?id=${produtoSelecionado.id}`;
            }
        }

        // Função para filtrar produtos
        function filtrarProdutos() {
            const pesquisa = document.getElementById('pesquisa').value.toLowerCase();
            const categoria = document.getElementById('filtro_categoria').value;
            const status = document.getElementById('filtro_status').value;
            const linhas = document.querySelectorAll('#lista-produtos tr');

            linhas.forEach(linha => {
                const nome = linha.querySelector('.text-gray-900').textContent.toLowerCase();
                const categoriaLinha = linha.querySelector('td:nth-child(2)').textContent.trim();
                const statusLinha = linha.querySelector('td:nth-child(4)').textContent.trim() === 'Ativo' ? '1' : '0';
                
                const matchPesquisa = nome.includes(pesquisa);
                const matchCategoria = !categoria || categoriaLinha.includes(document.getElementById('filtro_categoria').options[document.getElementById('filtro_categoria').selectedIndex].text);
                const matchStatus = !status || statusLinha === status;

                linha.style.display = matchPesquisa && matchCategoria && matchStatus ? '' : 'none';
            });
        }

        // Adicionar eventos de filtro
        document.getElementById('pesquisa').addEventListener('input', filtrarProdutos);
        document.getElementById('filtro_categoria').addEventListener('change', filtrarProdutos);
        document.getElementById('filtro_status').addEventListener('change', filtrarProdutos);

        // Fecha o modal se clicar fora dele
        document.getElementById('modalProduto').addEventListener('click', function(e) {
            if (e.target === document.getElementById('modalProduto')) {
                fecharModal();
            }
        });

        // Fecha o modal com a tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('modalProduto').classList.contains('hidden')) {
                fecharModal();
            }
        });

        function toggleMenu() {
            const menu = document.querySelector('.menu-lateral');
            menu.classList.toggle('hidden');
        }
    </script>
</body>
</html>
