<?php
session_start();
require_once 'includes/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Busca os dados do usuário
$mensagem = '';
$usuario = $_SESSION['usuario'];

// Busca a chave da API do Google Maps das configurações
$stmt = $conexao->prepare("SELECT maps_api_key FROM configuracoes LIMIT 1");
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC);
$maps_api_key = $config['maps_api_key'];

// Busca os endereços do usuário
$stmt = $conexao->prepare("SELECT * FROM enderecos_usuario WHERE usuario_id = ? ORDER BY principal DESC");
$stmt->execute([$usuario['id']]);
$enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processa atualização de informações pessoais
if (isset($_POST['atualizar_info'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if ($senha && $confirmar_senha) {
        if ($senha !== $confirmar_senha) {
            $mensagem = 'Senhas não conferem';
        } else {
            $senha = password_hash($senha, PASSWORD_DEFAULT);
            $query = "UPDATE usuarios SET nome = ?, email = ?, telefone = ?, senha = ? WHERE id = ?";
            $stmt = $conexao->prepare($query);
            try {
                if ($stmt->execute([$nome, $email, $telefone, $senha, $usuario['id']])) {
                    // Atualiza a sessão
                    $_SESSION['usuario']['nome'] = $nome;
                    $_SESSION['usuario']['email'] = $email;
                    $_SESSION['usuario']['telefone'] = $telefone;
                    $mensagem = 'Informações atualizadas com sucesso!';
                }
            } catch (PDOException $e) {
                $mensagem = 'Erro ao atualizar informações';
            }
        }
    } else {
        $query = "UPDATE usuarios SET nome = ?, email = ?, telefone = ? WHERE id = ?";
        $stmt = $conexao->prepare($query);
        try {
            if ($stmt->execute([$nome, $email, $telefone, $usuario['id']])) {
                // Atualiza a sessão
                $_SESSION['usuario']['nome'] = $nome;
                $_SESSION['usuario']['email'] = $email;
                $_SESSION['usuario']['telefone'] = $telefone;
                $mensagem = 'Informações atualizadas com sucesso!';
            }
        } catch (PDOException $e) {
            $mensagem = 'Erro ao atualizar informações';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .endereco-card {
            transition: all 0.3s ease;
        }
        .endereco-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .bg-pattern {
            background-color: #f8fafc;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239BA3AF' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="bg-pattern min-h-screen">
    <div class="container mx-auto px-0 pt-6 pb-[150px]">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Minha Conta</h1>
                    <p class="text-gray-500 mt-1">Gerencie suas informações pessoais e endereços</p>
                </div>
                <div class="h-12 w-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-white text-xl"></i>
                </div>
            </div>

            <?php if ($mensagem): ?>
            <div class="mb-4 p-4 rounded-lg bg-green-100 border border-green-200 text-green-700 flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
            <?php endif; ?>

            <!-- Seção de Informações Pessoais -->
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-user-edit text-blue-500 text-xl"></i>
                    </div>
                    <h2 class="text-2xl font-semibold text-gray-700">Informações Pessoais</h2>
                </div>
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-user text-gray-400 mr-1"></i> Nome
                            </label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" 
                                   class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 py-3 px-4"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-phone text-gray-400 mr-1"></i> Telefone
                            </label>
                            <input type="tel" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone']); ?>" 
                                   class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 py-3 px-4"
                                   required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-envelope text-gray-400 mr-1"></i> Email
                        </label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" 
                               class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 py-3 px-4"
                               required>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-lock text-gray-400 mr-1"></i> Nova Senha
                            </label>
                            <input type="password" name="senha" 
                                   class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 py-3 px-4"
                                   minlength="6"
                                   placeholder="Digite a nova senha">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-lock text-gray-400 mr-1"></i> Confirmar Senha
                            </label>
                            <input type="password" name="confirmar_senha" 
                                   class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 py-3 px-4"
                                   minlength="6"
                                   placeholder="Confirme a nova senha">
                        </div>
                    </div>
                    <div>
                        <button type="submit" name="atualizar_info" 
                                class="w-full bg-blue-500 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-600 transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            Atualizar Informações
                        </button>
                    </div>
                </form>
            </div>

            <!-- Seção de Endereços -->
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-map-marker-alt text-blue-500 text-xl"></i>
                        </div>
                        <h2 class="text-2xl font-semibold text-gray-700">Endereços</h2>
                    </div>
                    <?php if (count($enderecos) < 2): ?>
                    <button onclick="mostrarFormEndereco()" 
                            class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        Adicionar
                    </button>
                    <?php else: ?>
                    <span class="text-sm text-gray-500 flex items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        Limite máximo de 2 endereços atingido
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Lista de Endereços -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($enderecos as $endereco): ?>
                    <div class="endereco-card bg-gray-50 rounded-lg p-4 border border-gray-200 relative">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center">
                                <?php if ($endereco['principal']): ?>
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full flex items-center gap-1">
                                    <i class="fas fa-check-circle"></i>
                                    Principal
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex gap-2">
                                <?php if (!$endereco['principal']): ?>
                                <button onclick="definirPrincipal(<?php echo $endereco['id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-800 transition-colors" title="Definir como Principal">
                                    <i class="fas fa-star"></i>
                                </button>
                                <?php endif; ?>
                                <button onclick="excluirEndereco(<?php echo $endereco['id']; ?>)" 
                                        class="text-red-600 hover:text-red-800 transition-colors" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="mt-1">
                                <i class="fas fa-home text-gray-400"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 mb-1">
                                    <?php echo htmlspecialchars($endereco['logradouro']); ?>, 
                                    <?php echo htmlspecialchars($endereco['numero']); ?>
                                    <?php if ($endereco['complemento']): ?>
                                        - <?php echo htmlspecialchars($endereco['complemento']); ?>
                                    <?php endif; ?>
                                </p>
                                <p class="text-gray-600 mb-1">
                                    <?php echo htmlspecialchars($endereco['bairro']); ?>
                                </p>
                                <p class="text-gray-600">
                                    <?php echo htmlspecialchars($endereco['cidade']); ?> - 
                                    <?php echo htmlspecialchars($endereco['estado']); ?>, 
                                    <?php echo htmlspecialchars($endereco['cep']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($enderecos)): ?>
                <div class="text-center py-8">
                    <div class="text-gray-400 mb-3">
                        <i class="fas fa-map-marker-alt text-4xl"></i>
                    </div>
                    <p class="text-gray-500">Você ainda não possui endereços cadastrados</p>
                    <p class="text-gray-400 text-sm">Clique em "Adicionar" para começar</p>
                </div>
                <?php endif; ?>

                <!-- Formulário de Novo Endereço -->
                <div id="formEndereco" class="hidden">
                    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-semibold text-gray-800">Adicionar Novo Endereço</h3>
                                <button onclick="cancelarNovoEndereco()" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <form id="formNovoEndereco" class="space-y-4">
                                <!-- Campo de busca com Places API -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Buscar Endereço
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-search text-gray-400"></i>
                                        </div>
                                        <input type="text" id="searchInput" 
                                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="Digite o endereço para buscar...">
                                    </div>
                                </div>

                                <!-- Campos ocultos para dados do endereço -->
                                <input type="hidden" name="cep" id="cep">
                                <input type="hidden" name="logradouro" id="logradouro">
                                <input type="hidden" name="numero" id="numero">
                                <input type="hidden" name="complemento" id="complemento">
                                <input type="hidden" name="bairro" id="bairro">
                                <input type="hidden" name="cidade" id="cidade">
                                <input type="hidden" name="estado" id="estado">
                                <input type="hidden" name="latitude" id="latitude">
                                <input type="hidden" name="longitude" id="longitude">

                                <!-- Preview do endereço -->
                                <div id="enderecoPreview" class="hidden bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600 mb-1">
                                        Endereço selecionado:
                                    </p>
                                    <p id="previewText" class="text-gray-800"></p>
                                </div>

                                <div class="flex justify-end gap-4 mt-6">
                                    <button type="button" onclick="cancelarNovoEndereco()"
                                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                        Cancelar
                                    </button>
                                    <button type="submit"
                                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-2">
                                        <i class="fas fa-save"></i>
                                        Salvar Endereço
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/menu.php'; ?>

    <script>
        let autocomplete;
        let componentForm = {
            street_number: { type: 'short_name', field: 'numero' },
            route: { type: 'long_name', field: 'logradouro' },
            sublocality_level_1: { type: 'long_name', field: 'bairro' },
            administrative_area_level_2: { type: 'long_name', field: 'cidade' },
            administrative_area_level_1: { type: 'short_name', field: 'estado' },
            postal_code: { type: 'short_name', field: 'cep' }
        };

        function initAutocomplete() {
            const input = document.getElementById('searchInput');
            const options = {
                componentRestrictions: { country: 'br' },
                fields: ['address_components', 'geometry', 'formatted_address'],
                types: ['address']
            };

            autocomplete = new google.maps.places.Autocomplete(input, options);
            autocomplete.addListener('place_changed', fillInAddress);
        }

        function fillInAddress() {
            const place = autocomplete.getPlace();
            if (!place.address_components) return;

            // Limpa os campos
            for (const component in componentForm) {
                document.getElementById(componentForm[component].field).value = '';
            }

            // Preenche os campos com os valores do endereço
            for (const component of place.address_components) {
                const addressType = component.types[0];
                if (componentForm[addressType]) {
                    const val = component[componentForm[addressType].type];
                    document.getElementById(componentForm[addressType].field).value = val;
                }
            }

            // Salva latitude e longitude
            if (place.geometry && place.geometry.location) {
                document.getElementById('latitude').value = place.geometry.location.lat();
                document.getElementById('longitude').value = place.geometry.location.lng();
            }

            // Mostra preview do endereço
            const previewDiv = document.getElementById('enderecoPreview');
            const previewText = document.getElementById('previewText');
            previewText.textContent = place.formatted_address;
            previewDiv.classList.remove('hidden');
        }

        function mostrarFormEndereco() {
            document.getElementById('formEndereco').classList.remove('hidden');
        }

        function cancelarNovoEndereco() {
            document.getElementById('formEndereco').classList.add('hidden');
            document.getElementById('formNovoEndereco').reset();
            document.getElementById('enderecoPreview').classList.add('hidden');
            document.getElementById('searchInput').value = '';
        }

        function excluirEndereco(id) {
            if (confirm('Tem certeza que deseja excluir este endereço?')) {
                fetch('ajax/excluir_endereco.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ endereco_id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Erro ao excluir endereço');
                    }
                });
            }
        }

        function definirPrincipal(id) {
            fetch('ajax/definir_endereco_principal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ endereco_id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Erro ao definir endereço principal');
                }
            });
        }

        // Salvar novo endereço
        document.getElementById('formNovoEndereco').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);

            fetch('ajax/salvar_endereco.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Erro ao salvar endereço');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar endereço. Por favor, tente novamente.');
            });
        });
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($maps_api_key); ?>&libraries=places&callback=initAutocomplete" async defer></script>
</body>
</html>