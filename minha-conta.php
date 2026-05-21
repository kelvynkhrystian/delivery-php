<?php
require_once 'includes/conexao.php';
require_once 'includes/funcoes.php';
require_once 'includes/header.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Busca os dados do usuário
$usuario_id = $_SESSION['usuario_id'];
$query = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conexao->prepare($query);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

// Decodifica o endereço do usuário
$endereco = json_decode($usuario['endereco'], true) ?? [];

// Busca a chave da API do Google Maps
$query = "SELECT maps_api_key FROM configuracoes LIMIT 1";
$maps_api_key = $conexao->query($query)->fetch_assoc()['maps_api_key'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Minha Conta - <?php echo NOME_SITE; ?></title>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $maps_api_key; ?>&libraries=places&callback=Function.prototype"></script>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Seção de Dados Pessoais -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold mb-6">Dados Pessoais</h2>
                <form id="form-dados" class="space-y-4">
                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Telefone</label>
                            <input type="tel" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone']); ?>" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">E-mail</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nova Senha (deixe em branco para não alterar)</label>
                        <input type="password" name="senha" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Buscar Endereço</label>
                        <input type="text" id="endereco_busca" placeholder="Digite seu endereço" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CEP</label>
                            <input type="text" name="cep" id="endereco_cep" 
                                   value="<?php echo htmlspecialchars($endereco['cep'] ?? ''); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Número</label>
                            <input type="text" name="numero" id="endereco_numero" 
                                   value="<?php echo htmlspecialchars($endereco['numero'] ?? ''); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Logradouro</label>
                        <input type="text" name="logradouro" id="endereco_logradouro" 
                               value="<?php echo htmlspecialchars($endereco['logradouro'] ?? ''); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Complemento</label>
                        <input type="text" name="complemento" id="endereco_complemento" 
                               value="<?php echo htmlspecialchars($endereco['complemento'] ?? ''); ?>"
                               placeholder="Apartamento, bloco, etc." 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bairro</label>
                        <input type="text" name="bairro" id="endereco_bairro" 
                               value="<?php echo htmlspecialchars($endereco['bairro'] ?? ''); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    <input type="hidden" name="cidade" id="endereco_cidade" value="São Luís">
                    <input type="hidden" name="estado" id="endereco_estado" value="MA">
                    <input type="hidden" name="latitude" id="endereco_latitude" 
                           value="<?php echo htmlspecialchars($endereco['latitude'] ?? ''); ?>">
                    <input type="hidden" name="longitude" id="endereco_longitude" 
                           value="<?php echo htmlspecialchars($endereco['longitude'] ?? ''); ?>">

                    <div class="flex justify-end">
                        <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Inicialização do Autocomplete do Google Places
        function initAutocomplete() {
            const input = document.getElementById('endereco_busca');
            const options = {
                componentRestrictions: { country: 'br' },
                fields: ['address_components', 'geometry', 'name'],
                strictBounds: false,
                types: ['address'],
            };

            const autocomplete = new google.maps.places.Autocomplete(input, options);
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (!place.geometry) {
                    alert('Endereço não encontrado');
                    return;
                }

                // Preenche os campos com os dados do endereço
                for (const component of place.address_components) {
                    const type = component.types[0];
                    switch (type) {
                        case 'street_number':
                            document.getElementById('endereco_numero').value = component.long_name;
                            break;
                        case 'route':
                            document.getElementById('endereco_logradouro').value = component.long_name;
                            break;
                        case 'sublocality_level_1':
                        case 'sublocality':
                            document.getElementById('endereco_bairro').value = component.long_name;
                            break;
                        case 'postal_code':
                            document.getElementById('endereco_cep').value = component.long_name;
                            break;
                    }
                }

                // Salva as coordenadas
                document.getElementById('endereco_latitude').value = place.geometry.location.lat();
                document.getElementById('endereco_longitude').value = place.geometry.location.lng();
            });
        }

        // Inicializa o autocomplete quando a página carrega
        window.addEventListener('load', initAutocomplete);

        // Função AJAX para atualizar dados
        document.getElementById('form-dados').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('ajax/atualizar_usuario.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('Dados atualizados com sucesso!');
                } else {
                    alert(data.error || 'Erro ao atualizar dados');
                }
            } catch (error) {
                alert('Erro ao atualizar dados');
            }
        });
    </script>
</body>
</html>
