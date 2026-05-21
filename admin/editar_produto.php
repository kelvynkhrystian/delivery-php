<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Verifica se o ID do produto foi passado na URL
if (!isset($_GET['id'])) {
    echo "ID do produto não fornecido.";
    exit;
}

$produto_id = $_GET['id'];

// Busca as categorias para o formulário
$query = "SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca os dados do produto
$query = "SELECT * FROM produtos WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$produto_id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo "Produto não encontrado.";
    exit;
}

// Busca os complementos do produto
$query = "SELECT c.*, co.id as opcao_id, co.nome as opcao_nome, co.preco as opcao_preco 
          FROM complementos c 
          INNER JOIN produto_complementos pc ON c.id = pc.complemento_id 
          LEFT JOIN complemento_opcoes co ON c.id = co.complemento_id 
          WHERE pc.produto_id = ?
          ORDER BY c.id, co.id";
$stmt = $db->prepare($query);
$stmt->execute([$produto_id]);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiza os complementos e suas opções
$complementos = [];
foreach ($resultados as $row) {
    $complemento_id = $row['id'];
    if (!isset($complementos[$complemento_id])) {
        $complementos[$complemento_id] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'descricao' => $row['descricao'],
            'min_escolhas' => $row['min_escolhas'],
            'max_escolhas' => $row['max_escolhas'],
            'obrigatorio' => $row['obrigatorio'],
            'opcoes' => []
        ];
    }
    if ($row['opcao_id']) {
        $complementos[$complemento_id]['opcoes'][] = [
            'id' => $row['opcao_id'],
            'nome' => $row['opcao_nome'],
            'preco' => $row['opcao_preco']
        ];
    }
}

$produto['complementos'] = array_values($complementos);

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $categoria_id = $_POST['categoria_id'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $tem_complementos = $_POST['tem_complementos'];
    $produto_id = $_GET['id'];

    try {
        $db->beginTransaction();

        // Processar upload da imagem
        $imagem = $produto['imagem']; // Mantém a imagem atual se não for enviada uma nova
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/produtos/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '_' . basename($_FILES['imagem']['name']);
            $uploadFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadFile)) {
                // Se houver uma imagem antiga, exclui
                if ($produto['imagem'] && file_exists('../' . $produto['imagem'])) {
                    unlink('../' . $produto['imagem']);
                }
                $imagem = 'uploads/produtos/' . $fileName;
            }
        }

        // Atualizar produto no banco de dados
        $query = "UPDATE produtos SET nome = ?, descricao = ?, preco = ?, categoria_id = ?, ativo = ?, tem_complementos = ?, imagem = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$nome, $descricao, $preco, $categoria_id, $ativo, $tem_complementos, $imagem, $produto_id]);

        if ($tem_complementos) {
            // Primeiro, pegar todos os complementos atuais do produto
            $query = "SELECT complemento_id FROM produto_complementos WHERE produto_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$produto_id]);
            $complementosAtuais = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Excluir complementos marcados para exclusão
            if (isset($_POST['complementos_excluidos']) && is_array($_POST['complementos_excluidos'])) {
                foreach ($_POST['complementos_excluidos'] as $complemento_id) {
                    // Excluir opções do complemento
                    $query = "DELETE FROM complemento_opcoes WHERE complemento_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$complemento_id]);

                    // Excluir relação produto-complemento
                    $query = "DELETE FROM produto_complementos WHERE produto_id = ? AND complemento_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$produto_id, $complemento_id]);

                    // Excluir o complemento
                    $query = "DELETE FROM complementos WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$complemento_id]);
                }
            }
            
            // Complementos do formulário
            $complementosForm = isset($_POST['complementos']) ? array_keys($_POST['complementos']) : [];
            $complementosForm = array_map(function($id) {
                return str_replace('complemento_', '', $id);
            }, $complementosForm);
            
            // Encontrar complementos que foram removidos
            $complementosRemovidos = array_diff($complementosAtuais, $complementosForm);
            
            // Remover complementos que não existem mais
            if (!empty($complementosRemovidos)) {
                // Primeiro remove as opções dos complementos
                $placeholders = str_repeat('?,', count($complementosRemovidos) - 1) . '?';
                $params = array_values($complementosRemovidos); // Garante que os índices são sequenciais

                $query = "DELETE FROM complemento_opcoes WHERE complemento_id IN ($placeholders)";
                $stmt = $db->prepare($query);
                $stmt->execute($params);

                // Depois remove as associações com o produto
                $query = "DELETE FROM produto_complementos WHERE complemento_id IN ($placeholders)";
                $stmt = $db->prepare($query);
                $stmt->execute($params);

                // Por último remove os complementos
                $query = "DELETE FROM complementos WHERE id IN ($placeholders)";
                $stmt = $db->prepare($query);
                $stmt->execute($params);
            }

            // Remove todas as associações existentes para recriar
            $query = "DELETE FROM produto_complementos WHERE produto_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$produto_id]);

            if (isset($_POST['complementos'])) {
                foreach ($_POST['complementos'] as $comp_id => $complemento) {
                    $comp_id = str_replace('complemento_', '', $comp_id);
                    
                    if (strpos($comp_id, 'new_') === 0) {
                        // Inserir novo complemento
                        $query = "INSERT INTO complementos (nome, descricao, min_escolhas, max_escolhas, obrigatorio) 
                                VALUES (?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            $complemento['nome'],
                            $complemento['descricao'],
                            $complemento['min_escolhas'],
                            $complemento['max_escolhas'],
                            isset($complemento['obrigatorio']) ? 1 : 0
                        ]);
                        
                        $comp_id = $db->lastInsertId();
                        
                        // Inserir opções do novo complemento
                        if (isset($complemento['opcoes'])) {
                            foreach ($complemento['opcoes'] as $opcao) {
                                $query = "INSERT INTO complemento_opcoes (complemento_id, nome, preco) VALUES (?, ?, ?)";
                                $stmt = $db->prepare($query);
                                $stmt->execute([$comp_id, $opcao['nome'], $opcao['preco']]);
                            }
                        }
                    } else {
                        // Atualizar complemento existente
                        $query = "UPDATE complementos SET nome = ?, descricao = ?, min_escolhas = ?, max_escolhas = ?, obrigatorio = ? 
                                WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            $complemento['nome'],
                            $complemento['descricao'],
                            $complemento['min_escolhas'],
                            $complemento['max_escolhas'],
                            isset($complemento['obrigatorio']) ? 1 : 0,
                            $comp_id
                        ]);
                        
                        // Remover todas as opções antigas
                        $query = "DELETE FROM complemento_opcoes WHERE complemento_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$comp_id]);
                        
                        // Inserir novas opções
                        if (isset($complemento['opcoes'])) {
                            foreach ($complemento['opcoes'] as $opcao) {
                                $query = "INSERT INTO complemento_opcoes (complemento_id, nome, preco) VALUES (?, ?, ?)";
                                $stmt = $db->prepare($query);
                                $stmt->execute([$comp_id, $opcao['nome'], $opcao['preco']]);
                            }
                        }
                    }
                    
                    // Associar complemento ao produto
                    $query = "INSERT INTO produto_complementos (produto_id, complemento_id) VALUES (?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$produto_id, $comp_id]);
                }
            }
        }

        $db->commit();
        header('Location: produtos.php');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        echo "Erro ao atualizar o produto: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Animação do acordeão */
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            visibility: hidden;
            display: none;
        }
        
        .accordion-content.active {
            max-height: none !important;
            transition: max-height 0.5s ease-in;
            visibility: visible;
            display: block;
        }

        /* Animação do ícone */
        .accordion-icon {
            transition: transform 0.3s ease;
        }

        /* Estilo quando arrastando */
        .sortable-ghost {
            opacity: 0.4;
        }

        .sortable-chosen {
            background-color: #fff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Área de drop */
        .sortable-drag {
            opacity: 0.9;
        }

        /* Container principal mais largo */
        .container {
            max-width: 1200px !important;
        }

        /* Botão excluir complemento */
        .btn-excluir-complemento {
            width: 100%;
            margin: 1rem 0;
            padding: 0.5rem;
            text-align: center;
            border: 1px solid #ef4444;
            color: #ef4444;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .btn-excluir-complemento:hover {
            background-color: #fef2f2;
            color: #dc2626;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-4">
                    <a href="produtos.php" class="text-gray-600 hover:text-gray-900 transition-colors">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <span class="text-2xl font-semibold">Editar Produto</span>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center">
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm">
            <form id="formProduto" class="p-6 space-y-4" method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nome do Produto -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="nome">
                            Nome do Produto
                        </label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <!-- Categoria -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="categoria_id">
                            Categoria
                        </label>
                        <select id="categoria_id" name="categoria_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" <?php echo $produto['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Preço -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="preco">
                            Preço
                        </label>
                        <input type="number" id="preco" name="preco" value="<?php echo htmlspecialchars($produto['preco']); ?>" step="0.01" required min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="ativo">
                            Status
                        </label>
                        <select id="ativo" name="ativo" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="1" <?php echo $produto['ativo'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                            <option value="0" <?php echo $produto['ativo'] == 0 ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>

                    <!-- Descrição -->
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="descricao">
                            Descrição
                        </label>
                        <textarea id="descricao" name="descricao" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
                    </div>

                    <!-- Imagem -->
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Imagem do Produto
                        </label>
                        <div class="flex items-center space-x-4">
                            <div class="w-24 h-24 bg-gray-100 rounded-lg overflow-hidden">
                                <img id="preview-imagem" src="<?php echo $produto['imagem'] ? '../' . $produto['imagem'] : '../assets/img/produto-padrao.svg'; ?>" 
                                     alt="Preview" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <input type="file" id="imagem" name="imagem" accept="image/*"
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                       onchange="previewImagem(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Complementos -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Produto tem complementos?
                    </label>
                    <div class="flex items-center mb-4">
                        <input type="radio" id="tem_complementos_nao" name="tem_complementos" value="0" <?php echo !$produto['tem_complementos'] ? 'checked' : ''; ?>
                               class="mr-2" onchange="toggleComplementos(this.value)">
                        <label for="tem_complementos_nao" class="mr-4">Não</label>
                        
                        <input type="radio" id="tem_complementos_sim" name="tem_complementos" value="1" <?php echo $produto['tem_complementos'] ? 'checked' : ''; ?>
                               class="mr-2" onchange="toggleComplementos(this.value)">
                        <label for="tem_complementos_sim">Sim</label>
                    </div>

                    <!-- Área de Complementos -->
                    <div id="area_complementos" class="space-y-4 bg-gray-50 rounded-lg p-4">
                        <?php foreach ($produto['complementos'] as $complemento): ?>
                        <div id="complemento_<?php echo $complemento['id']; ?>" class="bg-white rounded-lg shadow-sm">
                            <!-- Cabeçalho do Acordeão -->
                            <div class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 transition-colors" 
                                 onclick="toggleAccordion('complemento_<?php echo $complemento['id']; ?>', event)">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-grip-vertical text-gray-400 cursor-move handle"></i>
                                    <span class="font-medium"><?php echo htmlspecialchars($complemento['nome']); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-chevron-down text-gray-400 transition-transform accordion-icon"></i>
                                </div>
                            </div>
                            
                            <!-- Conteúdo do Acordeão -->
                            <div class="accordion-content border-t border-gray-100 p-4">
                                <div class="space-y-4">
                                    <div class="flex-1">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="nome_complemento">
                                            Nome do Complemento
                                        </label>
                                        <input type="text" name="complementos[complemento_<?php echo $complemento['id']; ?>][nome]" 
                                               value="<?php echo htmlspecialchars($complemento['nome']); ?>" 
                                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                                               onchange="atualizarBotaoExcluir('complemento_<?php echo $complemento['id']; ?>', this.value)">
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">
                                            Descrição
                                        </label>
                                        <input type="text" name="complementos[complemento_<?php echo $complemento['id']; ?>][descricao]" 
                                               value="<?php echo htmlspecialchars($complemento['descricao']); ?>" 
                                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                                Mínimo de Escolhas
                                            </label>
                                            <input type="number" name="complementos[complemento_<?php echo $complemento['id']; ?>][min_escolhas]" 
                                                   value="<?php echo $complemento['min_escolhas']; ?>" min="0" 
                                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                                Máximo de Escolhas
                                            </label>
                                            <input type="number" name="complementos[complemento_<?php echo $complemento['id']; ?>][max_escolhas]" 
                                                   value="<?php echo $complemento['max_escolhas']; ?>" min="0" 
                                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" name="complementos[complemento_<?php echo $complemento['id']; ?>][obrigatorio]" 
                                                   value="1" <?php echo $complemento['obrigatorio'] ? 'checked' : ''; ?> 
                                                   class="form-checkbox h-4 w-4 text-blue-600">
                                            <span class="text-gray-700 font-medium">Obrigatório</span>
                                        </label>
                                    </div>

                                    <button type="button" onclick="removerComplemento('<?php echo $complemento['id']; ?>', event)" 
                                            class="btn-excluir-complemento">
                                        <i class="fas fa-trash mr-2"></i>Excluir "<?php echo htmlspecialchars($complemento['nome']); ?>"
                                    </button>

                                    <!-- Área de Opções -->
                                    <div>
                                        <div class="flex justify-between items-center mb-4">
                                            <h3 class="text-lg font-medium">Opções</h3>
                                            <button type="button" onclick="adicionarOpcao('complemento_<?php echo $complemento['id']; ?>')" 
                                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                                                <i class="fas fa-plus mr-2"></i>Adicionar Opção
                                            </button>
                                        </div>
                                        
                                        <div id="opcoes_complemento_<?php echo $complemento['id']; ?>" class="space-y-2">
                                            <?php if (isset($complemento['opcoes'])): ?>
                                                <?php foreach ($complemento['opcoes'] as $opcao): ?>
                                                    <div class="opcao flex items-center space-x-2 bg-gray-50 p-3 rounded-lg" data-id="<?php echo $opcao['id']; ?>">
                                                        <i class="fas fa-grip-vertical text-gray-400 cursor-move handle-opcao"></i>
                                                        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4 w-full">
                                                            <div class="w-full sm:w-2/3">
                                                                <input type="text" name="complementos[complemento_<?php echo $complemento['id']; ?>][opcoes][<?php echo $opcao['id']; ?>][nome]" 
                                                                       value="<?php echo htmlspecialchars($opcao['nome']); ?>" 
                                                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500" 
                                                                       placeholder="Nome da opção">
                                                            </div>
                                                            <div class="w-full sm:w-1/3">
                                                                <div class="relative">
                                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                                        <span class="text-gray-500">R$</span>
                                                                    </div>
                                                                    <input type="number" step="0.01" min="0" 
                                                                           name="complementos[complemento_<?php echo $complemento['id']; ?>][opcoes][<?php echo $opcao['id']; ?>][preco]" 
                                                                           value="<?php echo number_format($opcao['preco'], 2, '.', ''); ?>" 
                                                                           class="w-full pl-10 pr-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500" 
                                                                           placeholder="0,00">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button type="button" onclick="removerOpcao('<?php echo $opcao['id']; ?>', event)" 
                                                                class="text-red-500 hover:text-red-700">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4" id="btn-adicionar-complemento">
                        <button type="button" onclick="adicionarComplemento()"
                                class="w-full bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600">
                            Adicionar outro complemento
                        </button>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" class="w-full bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Função para preview da imagem
        function previewImagem(input) {
            const preview = document.getElementById('preview-imagem');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Inicializa o Sortable para os complementos
        new Sortable(document.getElementById('area_complementos'), {
            animation: 150,
            handle: '.handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function() {
                // Aqui você pode adicionar lógica para salvar a nova ordem
                console.log('Nova ordem dos complementos salva');
            }
        });

        // Inicializa o Sortable para as opções de cada complemento
        function initializeSortable(element) {
            if (!element) return;
            
            new Sortable(element, {
                animation: 150,
                handle: '.handle-opcao',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: function() {
                    // Aqui você pode adicionar lógica para salvar a nova ordem das opções
                    console.log('Nova ordem das opções salva');
                }
            });
        }

        // Inicializa o Sortable para todas as áreas de opções existentes
        document.querySelectorAll('[id^="opcoes_complemento_"]').forEach(el => {
            initializeSortable(el);
        });

        function toggleComplementos(value) {
            const areaComplementos = document.getElementById('area_complementos');
            const btnAdicionarComplemento = document.getElementById('btn-adicionar-complemento');
            if (value === '1') {
                areaComplementos.classList.remove('hidden');
                btnAdicionarComplemento.classList.remove('hidden');
            } else {
                areaComplementos.classList.add('hidden');
                btnAdicionarComplemento.classList.add('hidden');
            }
        }

        function toggleAccordion(id, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            const complemento = document.getElementById(id);
            const content = complemento.querySelector('.accordion-content');
            const icon = complemento.querySelector('.accordion-icon');
            
            // Fecha todos os outros acordeões
            document.querySelectorAll('.accordion-content').forEach(el => {
                if (el !== content && el.classList.contains('active')) {
                    el.classList.remove('active');
                    el.style.maxHeight = '0';
                    el.closest('.bg-white').querySelector('.accordion-icon').style.transform = 'rotate(0deg)';
                }
            });
            
            // Abre/fecha o acordeão clicado
            content.classList.toggle('active');
            if (content.classList.contains('active')) {
                content.style.maxHeight = 'none';
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.style.maxHeight = '0';
                icon.style.transform = 'rotate(0deg)';
            }
        }

        function removerComplemento(id, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
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
                    const complemento = document.getElementById('complemento_' + id);
                    if (complemento) {
                        // Se for um complemento existente (não novo), adiciona um campo hidden para excluí-lo
                        if (!id.startsWith('new_')) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'complementos_excluidos[]';
                            input.value = id;
                            document.getElementById('formProduto').appendChild(input);
                        }
                        complemento.remove();
                    }
                }
            });
        }

        function removerOpcao(id, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            const opcao = document.querySelector(`.opcao[data-id="${id}"]`);
            if (opcao) {
                opcao.remove();
            }
        }

        function adicionarComplemento() {
            const areaComplementos = document.getElementById('area_complementos');
            const timestamp = Date.now();
            const complementoId = 'complemento_new_' + timestamp;
            
            const complementoHtml = `
                <div id="${complementoId}" class="bg-white rounded-lg shadow-sm">
                    <!-- Cabeçalho do Acordeão -->
                    <div class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 transition-colors" 
                         onclick="toggleAccordion('${complementoId}', event)">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-grip-vertical text-gray-400 cursor-move handle"></i>
                            <span class="font-medium">Novo Complemento</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-chevron-down text-gray-400 transition-transform accordion-icon"></i>
                        </div>
                    </div>
                    
                    <!-- Conteúdo do Acordeão -->
                    <div class="accordion-content border-t border-gray-100 p-4">
                        <div class="space-y-4">
                            <div class="flex-1">
                                <label class="block text-gray-700 text-sm font-bold mb-2">
                                    Nome do Complemento
                                </label>
                                <input type="text" name="complementos[${complementoId}][nome]" 
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500" 
                                       placeholder="Nome do complemento"
                                       onchange="atualizarBotaoExcluir('${complementoId}', this.value)">
                            </div>

                            <div class="flex-1">
                                <label class="block text-gray-700 text-sm font-bold mb-2">
                                    Descrição
                                </label>
                                <input type="text" name="complementos[${complementoId}][descricao]" 
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500" 
                                       placeholder="Descrição do complemento">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">
                                        Mínimo de Escolhas
                                    </label>
                                    <input type="number" name="complementos[${complementoId}][min_escolhas]" value="0" min="0" 
                                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">
                                        Máximo de Escolhas
                                    </label>
                                    <input type="number" name="complementos[${complementoId}][max_escolhas]" value="1" min="0" 
                                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                </div>
                            </div>

                            <div>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="complementos[${complementoId}][obrigatorio]" value="1" 
                                           class="form-checkbox h-4 w-4 text-blue-600">
                                    <span class="text-gray-700 font-medium">Obrigatório</span>
                                </label>
                            </div>

                            <button type="button" onclick="removerComplemento('${complementoId}', event)" 
                                    class="btn-excluir-complemento">
                                <i class="fas fa-trash mr-2"></i>Excluir "Novo Complemento"
                            </button>

                            <!-- Área de Opções -->
                            <div>
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium">Opções</h3>
                                    <button type="button" onclick="adicionarOpcao('${complementoId}')" 
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                                        <i class="fas fa-plus mr-2"></i>Adicionar Opção
                                    </button>
                                </div>
                                
                                <div id="opcoes_${complementoId}" class="space-y-2">
                                    <!-- As opções serão adicionadas aqui -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            areaComplementos.insertAdjacentHTML('beforeend', complementoHtml);
            
            // Inicializa o Sortable para as opções do novo complemento
            initializeSortable(document.getElementById(`opcoes_${complementoId}`));

            // Abre o novo complemento automaticamente
            setTimeout(() => toggleAccordion(complementoId), 100);
        }

        function adicionarOpcao(complementoId) {
            const areaOpcoes = document.getElementById(`opcoes_${complementoId}`);
            if (!areaOpcoes) {
                console.error('Área de opções não encontrada:', complementoId);
                return;
            }

            const opcaoId = 'new_' + Date.now();
            const complementoIdLimpo = complementoId.replace('complemento_', '');
            
            const opcaoHtml = `
                <div class="opcao flex items-center space-x-2 bg-gray-50 p-3 rounded-lg" data-id="${opcaoId}">
                    <i class="fas fa-grip-vertical text-gray-400 cursor-move handle-opcao"></i>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4 w-full">
                        <div class="w-full sm:w-2/3">
                            <input type="text" name="complementos[${complementoId}][opcoes][${opcaoId}][nome]" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500" 
                                   placeholder="Nome da opção">
                        </div>
                        <div class="w-full sm:w-1/3">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500">R$</span>
                                </div>
                                <input type="number" step="0.01" min="0" 
                                       name="complementos[${complementoId}][opcoes][${opcaoId}][preco]" 
                                       value="0.00" 
                                       class="w-full pl-10 pr-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500" 
                                       placeholder="0,00">
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="removerOpcao('${opcaoId}', event)" 
                            class="text-red-500 hover:text-red-700 ml-2">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            areaOpcoes.insertAdjacentHTML('beforeend', opcaoHtml);

            // Atualiza o height do acordeão para mostrar o novo conteúdo
            const complemento = document.getElementById(complementoId);
            if (complemento) {
                const content = complemento.querySelector('.accordion-content');
                if (content && content.classList.contains('active')) {
                    content.style.maxHeight = 'none';
                }
            }
        }

        function atualizarBotaoExcluir(complementoId, nome) {
            const complemento = document.getElementById(complementoId);
            if (complemento) {
                const botaoExcluir = complemento.querySelector('.btn-excluir-complemento');
                if (botaoExcluir) {
                    botaoExcluir.innerHTML = `<i class="fas fa-trash mr-2"></i>Excluir "${nome || 'Novo Complemento'}"`;
                }
            }
        }

        // Inicializa os complementos ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            toggleComplementos(document.querySelector('input[name="tem_complementos"]:checked').value);
        });
    </script>
</body>
</html>
