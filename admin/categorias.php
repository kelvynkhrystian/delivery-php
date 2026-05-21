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

// Busca todas as categorias
$query = "SELECT * FROM categorias ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Conta total de categorias ativas
$query = "SELECT COUNT(*) as total, 
          SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativas 
          FROM categorias";
$stmt = $db->prepare($query);
$stmt->execute();
$totais = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
    <style>
        :root {
            --theme-color: <?php echo $config['cor_tema'] ?? '#8B5CF6'; ?>;
        }
        .bg-theme {
            background-color: var(--theme-color);
        }
        .bg-theme:hover {
            filter: brightness(90%);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-2xl font-semibold">Categorias</span>
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
                    <!-- Botão Nova Categoria -->
                    <div class="sm:col-span-1">
                        <button onclick="mostrarFormulario()" class="w-full sm:w-auto bg-theme text-white px-6 py-3 rounded-lg transition-colors duration-200 inline-flex items-center justify-center">
                            <i class="fas fa-plus mr-2"></i>Nova Categoria
                        </button>
                    </div>
                    
                    <!-- Cards de Estatísticas -->
                    <div class="sm:col-span-2 grid grid-cols-2 gap-4">
                        <div class="bg-white px-6 py-3 rounded-lg shadow-sm">
                            <div class="text-sm text-gray-500">Total de Categorias</div>
                            <div class="text-xl font-semibold"><?php echo $totais['total']; ?></div>
                        </div>
                        <div class="bg-white px-6 py-3 rounded-lg shadow-sm">
                            <div class="text-sm text-gray-500">Categorias Ativas</div>
                            <div class="text-xl font-semibold text-green-600"><?php echo $totais['ativas']; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Grid de Categorias -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($categorias as $categoria): ?>
                    <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div class="flex items-center">
                                <span class="w-3 h-3 rounded-full <?php echo $categoria['ativo'] ? 'bg-green-500' : 'bg-red-500'; ?> mr-2"></span>
                                <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($categoria['nome']); ?></h3>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="editarCategoria(<?php echo htmlspecialchars(json_encode($categoria)); ?>)"
                                        class="inline-flex items-center px-3 py-1.5 text-white bg-theme text-sm font-medium rounded-lg transition-colors duration-200">
                                    <i class="fas fa-edit mr-1.5"></i>
                                    <span>Editar</span>
                                </button>
                                <button onclick="excluirCategoria(<?php echo $categoria['id']; ?>)"
                                        class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 text-sm font-medium rounded-lg hover:bg-red-100 transition-colors duration-200">
                                    <i class="fas fa-trash mr-1.5"></i>
                                    <span>Excluir</span>
                                </button>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm">
                            <?php echo !empty($categoria['descricao']) ? htmlspecialchars($categoria['descricao']) : 'Sem descrição'; ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Categoria -->
    <div id="modalCategoria" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold" id="modalTitulo">Nova Categoria</h2>
                    <button onclick="fecharModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="formCategoria" class="space-y-4">
                    <input type="hidden" id="categoria_id" name="id">
                    
                    <!-- Nome -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="nome">
                            Nome da Categoria
                        </label>
                        <input type="text" id="nome" name="nome" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <!-- Descrição -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="descricao">
                            Descrição
                        </label>
                        <textarea id="descricao" name="descricao" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="ativo">
                            Status
                        </label>
                        <select id="ativo" name="ativo" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="1">Ativa</option>
                            <option value="0">Inativa</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-4">
                <button onclick="fecharModal()" 
                        class="px-4 py-2 text-gray-600 hover:text-gray-700">
                    Cancelar
                </button>
                <button onclick="salvarCategoria()" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Salvar
                </button>
            </div>
        </div>
    </div>

    <script>
        function mostrarFormulario() {
            document.getElementById('modalCategoria').classList.remove('hidden');
            document.getElementById('modalCategoria').classList.add('flex');
            document.getElementById('modalTitulo').textContent = 'Nova Categoria';
            document.getElementById('formCategoria').reset();
        }

        function fecharModal() {
            document.getElementById('modalCategoria').classList.add('hidden');
            document.getElementById('modalCategoria').classList.remove('flex');
        }

        function editarCategoria(categoria) {
            document.getElementById('categoria_id').value = categoria.id;
            document.getElementById('nome').value = categoria.nome;
            document.getElementById('descricao').value = categoria.descricao || '';
            document.getElementById('ativo').value = categoria.ativo;
            
            document.getElementById('modalTitulo').textContent = 'Editar Categoria';
            document.getElementById('modalCategoria').classList.remove('hidden');
            document.getElementById('modalCategoria').classList.add('flex');
        }

        function salvarCategoria() {
            const formData = new FormData(document.getElementById('formCategoria'));
            
            fetch('salvar_categoria.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
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
            });
        }

        function excluirCategoria(id) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não poderá ser revertida!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('excluir_categoria.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: id })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'Excluída!',
                                'A categoria foi excluída com sucesso.',
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Erro!',
                                data.message || 'Erro ao excluir a categoria.',
                                'error'
                            );
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
