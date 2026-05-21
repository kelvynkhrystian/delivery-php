<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Busca as categorias
$stmt = $db->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca todos os produtos com suas categorias
$stmt = $db->query("SELECT p.*, c.nome as categoria_nome 
                    FROM produtos p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id 
                    ORDER BY p.nome");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .preview-container {
            width: 200px;
            height: 200px;
            border: 2px dashed #ccc;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }
        #preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            cursor: pointer;
            display: none;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Gerenciar Produtos</h1>
            <button onclick="document.getElementById('modal-produto').classList.remove('hidden')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i>Novo Produto
            </button>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Produto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Descrição
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($produtos)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Nenhum produto cadastrado
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($produtos as $produto): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if ($produto['imagem']): ?>
                                        <img src="<?php echo $produto['imagem']; ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>" 
                                             class="h-10 w-10 rounded-full object-cover mr-3">
                                    <?php endif; ?>
                                    <div><?php echo htmlspecialchars($produto['nome']); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($produto['descricao']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($produto['categoria_nome']); ?></td>
                            <td class="px-6 py-4">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $produto['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $produto['ativo'] ? 'Disponível' : 'Indisponível'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick='editarProduto(<?php echo json_encode([
                                    "id" => $produto['id'],
                                    "nome" => $produto['nome'],
                                    "descricao" => $produto['descricao'],
                                    "categoria_id" => $produto['categoria_id'],
                                    "preco" => $produto['preco'],
                                    "ativo" => $produto['ativo'],
                                    "imagem" => $produto['imagem']
                                ]); ?>)'
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="excluirProduto(<?php echo $produto['id']; ?>)"
                                        class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal de Produto -->
        <div id="modal-produto" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-[90%] max-w-2xl shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-medium text-gray-900" id="modal-titulo">Adicionar Produto</h3>
                    <button onclick="document.getElementById('modal-produto').classList.add('hidden')"
                            class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="form-produto" class="space-y-4">
                    <input type="hidden" name="id" id="produto-id">
                    <input type="hidden" name="imagem" id="imagem-base64">
                    
                    <!-- Preview da Imagem -->
                    <div class="preview-container mx-auto">
                        <img id="preview-image" src="" alt="Preview" style="display: none;">
                        <div id="upload-placeholder" class="text-center">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                            <p class="text-gray-500">Clique ou arraste uma imagem</p>
                        </div>
                        <span class="remove-image" onclick="removerImagem()">Remover</span>
                    </div>

                    <!-- Input de Imagem -->
                    <div class="text-center">
                        <input type="file" id="imagem" accept=".jpg,.jpeg,.png,.webp" 
                               class="hidden" onchange="previewImagem(this)">
                        <button type="button" onclick="document.getElementById('imagem').click()"
                                class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">
                            <i class="fas fa-image mr-2"></i>Escolher Imagem
                        </button>
                        <p class="text-sm text-gray-500 mt-1">
                            Formatos aceitos: JPG, JPEG, PNG, WEBP (máx. 5MB)
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="produto-nome" class="block text-sm font-medium text-gray-700">Nome do Produto *</label>
                            <input type="text" id="produto-nome" name="nome" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="produto-categoria" class="block text-sm font-medium text-gray-700">Categoria *</label>
                            <select id="produto-categoria" name="categoria_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>">
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="produto-descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
                        <textarea id="produto-descricao" name="descricao" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>

                    <div>
                        <label for="produto-preco" class="block text-sm font-medium text-gray-700">Preço *</label>
                        <input type="number" id="produto-preco" name="preco" step="0.01" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="ativo" id="produto-ativo"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 block text-sm text-gray-900">
                            Produto disponível para venda
                        </label>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="document.getElementById('modal-produto').classList.add('hidden')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            let imagemAtual = null;

            // Preview da imagem
            function previewImagem(input) {
                const preview = document.getElementById('preview-image');
                const placeholder = document.getElementById('upload-placeholder');
                const file = input.files[0];

                if (file) {
                    // Verifica o tipo do arquivo
                    const fileType = file.type.toLowerCase();
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                    
                    if (!validTypes.includes(fileType)) {
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Tipo de arquivo não permitido. Use apenas jpg, jpeg, png ou webp',
                            icon: 'error'
                        });
                        input.value = '';
                        return;
                    }

                    // Verifica o tamanho (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Arquivo muito grande. Tamanho máximo: 5MB',
                            icon: 'error'
                        });
                        input.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        placeholder.style.display = 'none';
                        document.querySelector('.remove-image').style.display = 'block';
                        document.getElementById('imagem-base64').value = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            }

            // Remover imagem
            function removerImagem() {
                const preview = document.getElementById('preview-image');
                const placeholder = document.getElementById('upload-placeholder');
                const input = document.getElementById('imagem');
                const base64Input = document.getElementById('imagem-base64');
                
                preview.src = '';
                preview.style.display = 'none';
                placeholder.style.display = 'block';
                input.value = '';
                base64Input.value = '';
                document.querySelector('.remove-image').style.display = 'none';
                imagemAtual = null;
            }

            // Enviar formulário
            document.getElementById('form-produto').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = {
                    id: formData.get('id'),
                    nome: formData.get('nome'),
                    descricao: formData.get('descricao'),
                    categoria_id: formData.get('categoria_id'),
                    preco: formData.get('preco'),
                    ativo: formData.get('ativo') ? 1 : 0,
                    imagem: formData.get('imagem')
                };

                try {
                    const response = await fetch('salvar_produto.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        Swal.fire({
                            title: 'Sucesso!',
                            text: 'Produto salvo com sucesso',
                            icon: 'success'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        throw new Error(result.error || 'Erro ao salvar produto');
                    }
                } catch (error) {
                    Swal.fire({
                        title: 'Erro!',
                        text: error.message,
                        icon: 'error'
                    });
                }
            });

            function editarProduto(produto) {
                document.getElementById('modal-titulo').textContent = 'Editar Produto';
                document.getElementById('produto-id').value = produto.id;
                document.getElementById('produto-nome').value = produto.nome;
                document.getElementById('produto-descricao').value = produto.descricao;
                document.getElementById('produto-categoria').value = produto.categoria_id;
                document.getElementById('produto-preco').value = produto.preco;
                document.getElementById('produto-ativo').checked = produto.ativo == 1;

                // Carrega a imagem se existir
                const preview = document.getElementById('preview-image');
                const placeholder = document.getElementById('upload-placeholder');
                const base64Input = document.getElementById('imagem-base64');
                
                if (produto.imagem) {
                    preview.src = produto.imagem;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                    document.querySelector('.remove-image').style.display = 'block';
                    base64Input.value = produto.imagem;
                    imagemAtual = produto.imagem;
                } else {
                    preview.style.display = 'none';
                    placeholder.style.display = 'block';
                    document.querySelector('.remove-image').style.display = 'none';
                    base64Input.value = '';
                    imagemAtual = null;
                }

                document.getElementById('modal-produto').classList.remove('hidden');
            }

            // Excluir produto
            function excluirProduto(id) {
                Swal.fire({
                    title: 'Tem certeza?',
                    text: "Esta ação não poderá ser revertida!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('excluir_produto.php', {
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
                                    'Excluído!',
                                    'Produto excluído com sucesso.',
                                    'success'
                                ).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                throw new Error(data.error || 'Erro ao excluir produto');
                            }
                        })
                        .catch(error => {
                            Swal.fire(
                                'Erro!',
                                error.message,
                                'error'
                            );
                        });
                    }
                });
            }
        </script>
    </div>
</body>
</html>
