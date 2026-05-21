<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Processar exclusão
if (isset($_POST['excluir'])) {
    $categoria = $_POST['categoria'];
    // Primeiro, atualiza produtos dessa categoria para 'Sem categoria'
    $query = "UPDATE produtos SET categoria = 'Sem categoria' WHERE categoria = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$categoria]);
}

// Processar adição/edição
if (isset($_POST['salvar'])) {
    $categoria_antiga = $_POST['categoria_antiga'] ?? '';
    $categoria_nova = $_POST['categoria_nova'];
    
    if ($categoria_antiga) {
        // Atualizar categoria existente
        $query = "UPDATE produtos SET categoria = ? WHERE categoria = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$categoria_nova, $categoria_antiga]);
    }
}

// Buscar categorias e quantidade de produtos
$query = "SELECT categoria, COUNT(*) as total_produtos 
          FROM produtos 
          GROUP BY categoria 
          ORDER BY categoria";
$stmt = $db->query($query);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias - Delivery App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Gerenciar Categorias</h1>
            <a href="dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Voltar
            </a>
        </div>

        <!-- Botão Adicionar Categoria -->
        <button onclick="document.getElementById('modal-categoria').classList.remove('hidden'); document.getElementById('form-categoria').reset();"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mb-6">
            <i class="fas fa-plus mr-2"></i>Adicionar Categoria
        </button>

        <!-- Lista de Categorias -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total de Produtos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($categorias)): ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                            Nenhuma categoria cadastrada
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($categorias as $cat): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($cat['categoria']); ?></td>
                            <td class="px-6 py-4"><?php echo $cat['total_produtos']; ?></td>
                            <td class="px-6 py-4">
                                <button onclick="editarCategoria('<?php echo htmlspecialchars($cat['categoria']); ?>')"
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($cat['total_produtos'] == 0): ?>
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($cat['categoria']); ?>">
                                    <button type="submit" name="excluir" 
                                            onclick="return confirm('Tem certeza que deseja excluir esta categoria?')"
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Categoria -->
    <div id="modal-categoria" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-[90%] max-w-md shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-medium text-gray-900" id="modal-titulo">Adicionar Categoria</h3>
                <button onclick="document.getElementById('modal-categoria').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="form-categoria" method="POST" class="space-y-4">
                <input type="hidden" name="categoria_antiga" id="categoria-antiga">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome da Categoria</label>
                    <input type="text" name="categoria_nova" id="categoria-nova" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" 
                            onclick="document.getElementById('modal-categoria').classList.add('hidden')"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" name="salvar"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editarCategoria(categoria) {
        document.getElementById('modal-titulo').textContent = 'Editar Categoria';
        document.getElementById('categoria-antiga').value = categoria;
        document.getElementById('categoria-nova').value = categoria;
        document.getElementById('modal-categoria').classList.remove('hidden');
    }
    </script>
</body>
</html>
