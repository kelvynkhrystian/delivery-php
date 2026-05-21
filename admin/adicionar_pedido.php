<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Busca produtos ativos
$stmt = $db->query("SELECT p.*, c.nome as categoria_nome FROM produtos p 
                    INNER JOIN categorias c ON p.categoria_id = c.id 
                    WHERE p.ativo = 1 
                    ORDER BY c.nome, p.nome");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupa produtos por categoria
$produtos_por_categoria = [];
foreach ($produtos as $produto) {
    $categoria = $produto['categoria_nome'];
    if (!isset($produtos_por_categoria[$categoria])) {
        $produtos_por_categoria[$categoria] = [];
    }
    $produtos_por_categoria[$categoria][] = $produto;
}

// Busca usuários
$stmt = $db->query("SELECT * FROM usuarios ORDER BY nome");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Pedido - Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
    <?php include 'menu.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Adicionar Novo Pedido</h1>
            <a href="pedidos.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Voltar
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form id="formPedido" class="space-y-6">
                <!-- Cliente -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="usuario_id">
                        Cliente
                    </label>
                    <select id="usuario_id" name="usuario_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">Selecione um cliente</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?php echo $usuario['id']; ?>">
                                <?php echo htmlspecialchars($usuario['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Produtos -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Produtos
                    </label>
                    <div id="lista_produtos" class="space-y-4">
                        <!-- Template para linha de produto -->
                        <template id="template_produto">
                            <div class="produto-item bg-gray-50 p-4 rounded-lg flex flex-wrap items-start gap-4">
                                <select name="produtos[][id]" required class="produto-select flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                    <option value="">Selecione um produto</option>
                                    <?php foreach ($produtos_por_categoria as $categoria => $produtos_categoria): ?>
                                        <optgroup label="<?php echo htmlspecialchars($categoria); ?>">
                                            <?php foreach ($produtos_categoria as $produto): ?>
                                                <option value="<?php echo $produto['id']; ?>" 
                                                        data-preco="<?php echo $produto['preco']; ?>"
                                                        data-tem-complementos="<?php echo $produto['tem_complementos']; ?>">
                                                    <?php echo htmlspecialchars($produto['nome']); ?> - 
                                                    R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                
                                <input type="number" name="produtos[][quantidade]" value="1" min="1" required
                                       class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                       placeholder="Qtd">
                                
                                <div class="complementos-container hidden w-full">
                                    <!-- Os complementos serão carregados aqui via JavaScript -->
                                </div>

                                <button type="button" class="remover-produto text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <button type="button" id="adicionar_produto" 
                            class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Adicionar Produto
                    </button>
                </div>

                <!-- Observação -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="observacao">
                        Observação
                    </label>
                    <textarea id="observacao" name="observacao" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                              placeholder="Alguma observação especial para este pedido?"></textarea>
                </div>

                <!-- Total -->
                <div class="border-t pt-4">
                    <div class="flex justify-between items-center text-lg font-bold">
                        <span>Total:</span>
                        <span id="total" class="text-green-600">R$ 0,00</span>
                    </div>
                </div>

                <!-- Botões -->
                <div class="flex justify-end space-x-4">
                    <a href="pedidos.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        Salvar Pedido
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Função para adicionar nova linha de produto
        function adicionarLinhaProduto() {
            const template = document.querySelector('#template_produto');
            const clone = template.content.cloneNode(true);
            document.querySelector('#lista_produtos').appendChild(clone);

            // Adiciona evento para remover produto
            const novoProduto = document.querySelector('#lista_produtos').lastElementChild;
            novoProduto.querySelector('.remover-produto').addEventListener('click', function() {
                novoProduto.remove();
                atualizarTotal();
            });

            // Adiciona evento para atualizar complementos
            const select = novoProduto.querySelector('.produto-select');
            select.addEventListener('change', function() {
                const option = select.selectedOptions[0];
                if (option && option.dataset.temComplementos === '1') {
                    carregarComplementos(select.value, novoProduto.querySelector('.complementos-container'));
                } else {
                    novoProduto.querySelector('.complementos-container').innerHTML = '';
                    novoProduto.querySelector('.complementos-container').classList.add('hidden');
                }
                atualizarTotal();
            });

            // Adiciona evento para quantidade
            novoProduto.querySelector('input[type="number"]').addEventListener('change', atualizarTotal);
        }

        // Função para carregar complementos de um produto
        async function carregarComplementos(produtoId, container) {
            try {
                const response = await fetch(`buscar_complementos.php?produto_id=${produtoId}`);
                const data = await response.json();
                
                if (data.success && data.complementos.length > 0) {
                    let html = '<div class="w-full mt-4 space-y-4">';
                    
                    data.complementos.forEach(complemento => {
                        html += `
                            <div class="complemento-grupo bg-white p-4 rounded-lg border">
                                <h4 class="font-bold mb-2">${complemento.nome}</h4>
                                <p class="text-sm text-gray-600 mb-2">${complemento.descricao || ''}</p>
                                <p class="text-sm mb-2">
                                    ${complemento.obrigatorio ? '<span class="text-red-600">Obrigatório</span> • ' : ''}
                                    Escolha ${complemento.min_escolhas} a ${complemento.max_escolhas} opções
                                </p>
                                <div class="space-y-2">
                                    ${complemento.opcoes.map(opcao => `
                                        <label class="flex items-center space-x-2">
                                            <input type="${complemento.max_escolhas === 1 ? 'radio' : 'checkbox'}"
                                                   name="complementos[${produtoId}][${complemento.id}]${complemento.max_escolhas === 1 ? '' : '[]'}"
                                                   value="${opcao.id}"
                                                   data-preco="${opcao.preco}"
                                                   ${complemento.obrigatorio && complemento.max_escolhas === 1 ? 'required' : ''}>
                                            <span>${opcao.nome}</span>
                                            ${opcao.preco > 0 ? `<span class="text-sm text-gray-600">(+ R$ ${Number(opcao.preco).toFixed(2).replace('.', ',')})</span>` : ''}
                                        </label>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    });

                    html += '</div>';
                    container.innerHTML = html;
                    container.classList.remove('hidden');
                } else {
                    container.innerHTML = '';
                    container.classList.add('hidden');
                }
            } catch (error) {
                console.error('Erro ao carregar complementos:', error);
                container.innerHTML = '<p class="text-red-600">Erro ao carregar complementos</p>';
            }
        }

        // Função para atualizar o total
        function atualizarTotal() {
            let total = 0;
            
            document.querySelectorAll('.produto-item').forEach(item => {
                const select = item.querySelector('.produto-select');
                const quantidade = Number(item.querySelector('input[type="number"]').value);
                
                if (select.value) {
                    const preco = Number(select.selectedOptions[0].dataset.preco);
                    total += preco * quantidade;

                    // Soma complementos selecionados
                    item.querySelectorAll('input[type="checkbox"]:checked, input[type="radio"]:checked').forEach(input => {
                        total += Number(input.dataset.preco) * quantidade;
                    });
                }
            });

            document.getElementById('total').textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
        }

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Adiciona primeiro produto
            adicionarLinhaProduto();

            // Botão para adicionar mais produtos
            document.getElementById('adicionar_produto').addEventListener('click', adicionarLinhaProduto);

            // Submit do formulário
            document.getElementById('formPedido').addEventListener('submit', async function(e) {
                e.preventDefault();

                // Validações básicas
                const usuario = document.getElementById('usuario_id').value;
                if (!usuario) {
                    Swal.fire('Erro', 'Selecione um cliente', 'error');
                    return;
                }

                const produtos = document.querySelectorAll('.produto-item');
                let temProduto = false;
                produtos.forEach(item => {
                    if (item.querySelector('.produto-select').value) {
                        temProduto = true;
                    }
                });

                if (!temProduto) {
                    Swal.fire('Erro', 'Adicione pelo menos um produto', 'error');
                    return;
                }

                // Envia formulário
                try {
                    const formData = new FormData(this);
                    const response = await fetch('salvar_pedido.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        await Swal.fire('Sucesso', data.message, 'success');
                        window.location.href = 'pedidos.php';
                    } else {
                        Swal.fire('Erro', data.message, 'error');
                    }
                } catch (error) {
                    console.error('Erro ao salvar pedido:', error);
                    Swal.fire('Erro', 'Erro ao salvar pedido', 'error');
                }
            });
        });

        // Atualiza total quando mudar complementos
        document.addEventListener('change', function(e) {
            if (e.target.matches('input[type="checkbox"], input[type="radio"]')) {
                atualizarTotal();
            }
        });
    </script>
</body>
</html>
