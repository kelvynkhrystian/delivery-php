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

// Define todos os campos possíveis com valores padrão
$campos_padrao = [
    'nome_loja' => 'Erro - Verifique o BD',
    'email_loja' => 'email@empresa.com',
    'telefone_loja' => 'Erro - Verifique o BD',
    'horario_funcionamento' => 'Erro - Verifique o BD',
    'pedido_minimo' => '0.00',
    'tempo_entrega' => '30',
    'tipo_entrega' => 'distancia',
    'maps_api_key' => 'Erro - Verifique o BD',
    'maps_endereco' => 'Digite seu endereço',
    'maps_latitude' => '0',
    'maps_longitude' => '0',
    'maps_raio_entrega' => '3',
    'instagram' => '@empresa',
    'logo' => '',
    'banner' => '',
    'banner_pc' => '',
    'slogan' => '',
    'whatsapp' => '',
    'semana_inicio' => '',
    'semana_fim' => '',
    'sabado_inicio' => '',
    'sabado_fim' => '',
    'domingo_inicio' => '',
    'domingo_fim' => '',
    'status_loja' => 'horario',
    'segunda_inicio' => '',
    'segunda_fim' => '',
    'terca_inicio' => '',
    'terca_fim' => '',
    'quarta_inicio' => '',
    'quarta_fim' => '',
    'quinta_inicio' => '',
    'quinta_fim' => '',
    'sexta_inicio' => '',
    'sexta_fim' => '',
    'favicon' => '',
    'local' => 'Digite seu bairro',
    'cor_tema' => '#8B5CF6',
];

// Busca configurações atuais
$stmt = $db->query("SELECT * FROM configuracoes ORDER BY id DESC LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não existir configuração ou se existir, mescla com os valores padrão
$config = $config ? array_merge($campos_padrao, $config) : $campos_padrao;

// Debug
error_log("Configurações carregadas: " . print_r($config, true));

// Verifica cada campo e substitui por mensagem de erro se estiver vazio
foreach ($config as $key => $value) {
    if (empty($value) && !in_array($key, ['pedido_minimo', 'tempo_entrega', 'maps_latitude', 'maps_longitude', 'logo', 'banner', 'banner_pc', 'instagram', 'favicon', 'maps_endereco'])) {
        $config[$key] = 'Erro - Verifique o BD';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- SweetAlert2 CSS e JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui/material-ui.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Variáveis globais do mapa
        let map;
        let marker;
        let circle;
        let mapsLoaded = false;

        // Função para carregar o script do Google Maps
        function loadGoogleMaps(apiKey) {
            if (!apiKey) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Por favor, insira uma chave de API do Google Maps válida'
                });
                return;
            }
            
            console.log('Tentando carregar o Google Maps...');
            
            // Remove qualquer script anterior do Google Maps
            const existingScript = document.querySelector('script[src*="maps.googleapis.com"]');
            if (existingScript) {
                existingScript.remove();
            }

            // Adiciona handler para erros de carregamento
            window.gm_authFailure = function() {
                console.error('Erro de autenticação do Google Maps');
                Swal.fire({
                    icon: 'error',
                    title: 'Erro de API',
                    html: 'A chave da API do Google Maps não está ativa ou é inválida.<br><br>' +
                          'Por favor, verifique se:<br>' +
                          '1. A chave da API está correta<br>' +
                          '2. A API do Maps JavaScript está ativada no Console do Google Cloud<br>' +
                          '3. O domínio está autorizado a usar esta chave',
                    confirmButtonText: 'Entendi'
                });
            };
            
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=initMap`;
            script.async = true;
            script.defer = true;
            script.onerror = function() {
                console.error('Erro ao carregar o script do Google Maps');
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Não foi possível carregar o Google Maps. Verifique sua conexão com a internet.'
                });
            };
            document.head.appendChild(script);
        }

        // Função para alternar visibilidade da API Key
        function toggleApiKeyVisibility() {
            const input = document.getElementById('maps_api_key');
            if (input) {
                input.type = input.type === 'password' ? 'text' : 'password';
            }
        }

        // Função para validar e salvar API Key
        function validarApiKey() {
            const apiKey = document.getElementById('maps_api_key').value;
            if (!apiKey) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Por favor, insira uma API Key'
                });
                return;
            }

            // Mostra loading
            Swal.fire({
                title: 'Validando...',
                text: 'Aguarde enquanto validamos sua API Key',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Salva a API Key
            fetch('salvar_api_key.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ api_key: apiKey })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'API Key validada e salva com sucesso!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    // Carrega o mapa com a nova API key
                    loadGoogleMaps(apiKey);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message || 'Erro ao validar API Key'
                });
            });
        }

        // Função para buscar endereço
        function buscarEndereco() {
            if (typeof google === 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Por favor, configure primeiro a API Key do Google Maps'
                });
                return;
            }

            const endereco = document.getElementById('maps_endereco').value;
            if (!endereco) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Por favor, digite um endereço'
                });
                return;
            }

            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ address: endereco }, function(results, status) {
                if (status === 'OK') {
                    const location = results[0].geometry.location;
                    map.setCenter(location);
                    map.setZoom(16);
                    marker.setPosition(location);
                    
                    document.getElementById('maps_latitude').value = location.lat();
                    document.getElementById('maps_longitude').value = location.lng();
                    document.getElementById('maps_endereco').value = results[0].formatted_address;
                    atualizarCirculo();

                    // Salva as configurações após buscar o endereço
                    salvarConfiguracoesMaps();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Não foi possível encontrar o endereço'
                    });
                }
            });
        }

        // Função para atualizar o círculo do raio de entrega
        function atualizarCirculo() {
            const raio = parseFloat(document.getElementById('maps_raio_entrega').value) * 1000; // Converter km para metros
            if (raio > 0 && marker) {
                circle.setCenter(marker.getPosition());
                circle.setRadius(raio);
                circle.setVisible(true);
            } else if (circle) {
                circle.setVisible(false);
            }
        }

        // Função para inicializar o mapa
        function initMap() {
            if (typeof google === 'undefined') return;
            
            mapsLoaded = true;
            const lat = parseFloat(document.getElementById('maps_latitude').value) || -23.550520;
            const lng = parseFloat(document.getElementById('maps_longitude').value) || -46.633308;
            
            const mapOptions = {
                center: { lat, lng },
                zoom: 15
            };

            map = new google.maps.Map(document.getElementById('map'), mapOptions);
            
            // Adiciona o marcador
            marker = new google.maps.Marker({
                position: { lat, lng },
                map: map,
                draggable: true
            });

            // Adiciona o círculo
            const raio = parseFloat(document.getElementById('maps_raio_entrega').value) || 3;
            circle = new google.maps.Circle({
                map: map,
                radius: raio * 1000, // Converte para metros
                fillColor: '#FF0000',
                fillOpacity: 0.2,
                strokeColor: '#FF0000',
                strokeOpacity: 0.5,
                strokeWeight: 2
            });
            circle.bindTo('center', marker, 'position');

            // Evento quando o marcador é arrastado
            marker.addListener('dragend', function() {
                const pos = marker.getPosition();
                document.getElementById('maps_latitude').value = pos.lat();
                document.getElementById('maps_longitude').value = pos.lng();
                
                // Atualiza o endereço
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: pos }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        document.getElementById('maps_endereco').value = results[0].formatted_address;
                    }
                });
            });
        }

        // Função simples para mostrar seções
        function mostrarSecao(secaoId) {
            // Remove active de todos os botões
            const botoes = document.querySelectorAll('.secao-btn');
            botoes.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Esconde todas as seções
            const secoes = document.querySelectorAll('.secao');
            secoes.forEach(secao => secao.classList.add('hidden'));
            
            // Mostra a seção selecionada
            const secaoAtual = document.getElementById('secao-' + secaoId);
            if (secaoAtual) {
                secaoAtual.classList.remove('hidden');
                // Ativa o botão correspondente
                const botaoAtivo = document.querySelector(`button[onclick*="${secaoId}"]`);
                if (botaoAtivo) {
                    botaoAtivo.classList.add('active');
                }
            }
        }

        // Função para upload de imagens
        function uploadImagem(file, tipo) {
            console.log('Iniciando upload:', tipo);
            console.log('Arquivo:', file);
            console.log('Tipo do arquivo:', file.type);
            console.log('Tamanho:', file.size);

            const formData = new FormData();
            formData.append('imagem', file);
            formData.append('tipo', tipo);
            formData.append('secao', 'aparencia');

            // Mostra indicador de carregamento
            Swal.fire({
                title: 'Enviando...',
                text: 'Aguarde enquanto a imagem é processada',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('salvar_configuracoes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Resposta do servidor:', data);
                if (data.success) {
                    // Atualiza a visualização da imagem
                    const preview = document.getElementById(`${tipo.replace('-', '_')}_preview`);
                    if (preview) {
                        preview.src = data.path.startsWith('../') ? data.path : '../' + data.path;
                        console.log('Preview atualizado:', preview.src);
                    } else {
                        console.error('Elemento preview não encontrado:', `${tipo.replace('-', '_')}_preview`);
                    }

                    // Mostra mensagem de sucesso
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Imagem atualizada com sucesso',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    throw new Error(data.message || 'Erro desconhecido');
                }
            })
            .catch(error => {
                console.error('Erro no upload:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message || 'Erro ao fazer upload da imagem'
                });
            });
        }

        // Função para pré-visualizar imagem
        function previewImagem(input, tipo) {
            console.log('Preview iniciado:', tipo);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                const preview = document.getElementById(`${tipo.replace('-', '_')}_preview`);
                
                reader.onload = function(e) {
                    if (preview) {
                        preview.src = e.target.result;
                        console.log('Preview carregado com sucesso');
                    } else {
                        console.error('Elemento preview não encontrado');
                    }
                };
                
                reader.readAsDataURL(input.files[0]);
            }
            
            if (input.files && input.files[0]) {
                console.log('Iniciando upload após preview');
                uploadImagem(input.files[0], tipo);
            }
        }

        // Event listeners para os inputs de arquivo
        document.addEventListener('DOMContentLoaded', function() {
            const tipos = ['favicon', 'logo', 'banner', 'banner_pc'];
            
            tipos.forEach(tipo => {
                const input = document.getElementById(`input_${tipo}`);
                if (input) {
                    input.addEventListener('change', function() {
                        previewImagem(this, tipo);
                    });
                }
            });
        });

        // Função para salvar configurações
        function salvarConfiguracoes(secao) {
            // Mostra indicador de carregamento
            Swal.fire({
                title: 'Salvando...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('secao', secao);

            // Dados específicos de cada seção
            switch(secao) {
                case 'geral':
                    const campos = ['nome_loja', 'local', 'slogan', 'pedido_minimo', 'tempo_entrega'];
                    campos.forEach(campo => {
                        const elemento = document.getElementById(campo);
                        if (elemento) {
                            formData.append(campo, elemento.value);
                        }
                    });
                    break;

                case 'contato':
                    const camposContato = ['email_loja', 'telefone_loja', 'endereco_loja', 'instagram'];
                    camposContato.forEach(campo => {
                        const elemento = document.getElementById(campo);
                        if (elemento) {
                            formData.append(campo, elemento.value);
                        }
                    });
                    break;

                case 'horarios':
                    const statusFuncionamento = document.querySelector('input[name="status_funcionamento"]:checked');
                    if (statusFuncionamento) {
                        formData.append('status_funcionamento', statusFuncionamento.value);
                    }

                    const dias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
                    const horarios = {};
                    dias.forEach(dia => {
                        horarios[dia] = {
                            inicio: document.getElementById(`${dia}_inicio`).value,
                            fim: document.getElementById(`${dia}_fim`).value
                        };
                    });
                    formData.append('horarios_funcionamento', JSON.stringify(horarios));
                    break;

                case 'aparencia':
                    const faviconInput = document.getElementById('favicon_upload');
                    const logoInput = document.getElementById('logo_upload');
                    const bannerInput = document.getElementById('banner_upload');
                    const bannerPcInput = document.getElementById('banner_pc_upload');
                    
                    if (faviconInput && faviconInput.files.length > 0) {
                        formData.append('favicon', faviconInput.files[0]);
                    }
                    if (logoInput && logoInput.files.length > 0) {
                        formData.append('logo', logoInput.files[0]);
                    }
                    if (bannerInput && bannerInput.files.length > 0) {
                        formData.append('banner', bannerInput.files[0]);
                    }
                    if (bannerPcInput && bannerPcInput.files.length > 0) {
                        formData.append('banner_pc', bannerPcInput.files[0]);
                    }

                    const temaCorInput = document.querySelector('input[name="cor_tema"]:checked');
                    if (temaCorInput) {
                        formData.append('cor_tema', temaCorInput.value);
                    }

                    const corPersonalizadaInput = document.getElementById('cor_personalizada');
                    if (corPersonalizadaInput) {
                        formData.append('cor_tema', corPersonalizadaInput.value);
                    }
                    break;
            }

            // Salva as configurações
            fetch('salvar_configuracoes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Configurações salvas com sucesso!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Recarrega a página para mostrar as alterações
                        window.location.reload();
                    });
                } else {
                    console.error('Erro:', data);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro ao salvar configurações',
                        text: data.message || 'Ocorreu um erro ao salvar as configurações.'
                    });
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao salvar configurações',
                    text: 'Ocorreu um erro ao salvar as configurações.'
                });
            });
        }

        // Função para salvar email
        function salvarEmail() {
            const form = document.getElementById('form-email');
            const data = {
                tipo: 'email',
                novo_email: form.querySelector('#novo_email').value,
                senha_atual: form.querySelector('#senha_atual_email').value
            };

            // Validações básicas
            if (!data.novo_email || !data.senha_atual) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Todos os campos são obrigatórios'
                });
                return;
            }

            // Envia os dados para o servidor
            fetch('salvar_credenciais.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Atualiza o email atual mostrado e limpa os campos
                    document.querySelector('#form-email input[disabled]').value = data.novo_email;
                    form.reset();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: result.message
                    });
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Ocorreu um erro ao atualizar o email'
                });
            });
        }

        // Função para salvar senha
        function salvarSenha() {
            const form = document.getElementById('form-senha');
            const data = {
                tipo: 'senha',
                senha_atual: form.querySelector('#senha_atual_senha').value,
                nova_senha: form.querySelector('#nova_senha').value,
                confirmar_senha: form.querySelector('#confirmar_senha').value
            };

            // Validações básicas
            if (!data.senha_atual || !data.nova_senha || !data.confirmar_senha) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Todos os campos são obrigatórios'
                });
                return;
            }

            if (data.nova_senha !== data.confirmar_senha) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'As senhas não coincidem'
                });
                return;
            }

            // Envia os dados para o servidor
            fetch('salvar_credenciais.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        form.reset();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: result.message
                    });
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao salvar senha',
                    text: 'Ocorreu um erro ao salvar a senha.'
                });
            });
        }

        // Função para selecionar tipo de entrega
        function selecionarTipoEntrega(tipo) {
            alert('Função chamada com tipo: ' + tipo);
            console.log('Função chamada');
        }

        // Função para reverter seleção
        function reverterSelecao(tipo) {
            console.log('=== INÍCIO reverterSelecao ===');
            console.log('Revertendo seleção:', tipo);
            
            const botoes = document.querySelectorAll('.tipo-entrega-btn');
            botoes.forEach(btn => {
                btn.classList.remove('active');
            });
            const tipoAtual = '<?php echo $config["tipo_entrega"]; ?>';
            const botaoOriginal = document.querySelector(`button[onclick*="selecionarTipoEntrega('${tipoAtual}')"]`);
            if (botaoOriginal) {
                botaoOriginal.classList.add('active');
            }
            console.log('=== FIM reverterSelecao ===');
        }

        // Função para confirmar alteração do tipo de entrega
        function confirmarAlteracaoTipoEntrega(tipo) {
            console.log('Confirmado! Alterando tipo de entrega para:', tipo);
            
            document.getElementById('secao-entrega-bairro').classList.toggle('hidden', tipo !== 'bairro');
            document.getElementById('secao-entrega-distancia').classList.toggle('hidden', tipo !== 'distancia');

            // Limpa as listagens
            if (tipo === 'bairro') {
                document.getElementById('lista_distancias').innerHTML = '';
            } else {
                const listaBairros = document.getElementById('lista_bairros');
                if (listaBairros) listaBairros.innerHTML = '';
            }

            // Salva a configuração
            fetch('salvar_config.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tipo_entrega: tipo,
                    limpar_dados: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostra mensagem de sucesso
                    const modalSucesso = document.createElement('div');
                    modalSucesso.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                    modalSucesso.innerHTML = `
                        <div class="bg-white rounded-lg p-6 w-96">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-green-600">Sucesso!</h3>
                                <button class="text-gray-400 hover:text-gray-500" onclick="this.closest('.fixed').remove()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="mb-6">
                                <p>O tipo de entrega foi alterado com sucesso.</p>
                            </div>
                            <div class="flex justify-end">
                                <button class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" onclick="this.closest('.fixed').remove()">
                                    OK
                                </button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modalSucesso);
                } else {
                    throw new Error(data.message || 'Erro ao salvar configuração');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                // Mostra mensagem de erro
                const modalErro = document.createElement('div');
                modalErro.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                modalErro.innerHTML = `
                    <div class="bg-white rounded-lg p-6 w-96">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-red-600">Erro</h3>
                            <button class="text-gray-400 hover:text-gray-500" onclick="this.closest('.fixed').remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="mb-6">
                            <p>Ocorreu um erro ao salvar a configuração.</p>
                        </div>
                        <div class="flex justify-end">
                            <button class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700" onclick="this.closest('.fixed').remove()">
                                OK
                            </button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modalErro);
            });
        }

        // Função para adicionar bairro
        function adicionarBairro() {
            const bairro = document.getElementById('novo_bairro').value.trim();
            const valor = document.getElementById('novo_valor').value;
            
            if (!bairro || !valor) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Preencha o nome do bairro e o valor'
                });
                return;
            }

            // Envia para o banco
            fetch('salvar_bairro.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nome: bairro,
                    valor: valor
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Limpa os campos
                    document.getElementById('novo_bairro').value = '';
                    document.getElementById('novo_valor').value = '';
                    
                    // Recarrega a lista
                    carregarBairros();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Bairro adicionado!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    throw new Error(data.message || 'Erro ao adicionar bairro');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao adicionar bairro',
                    text: error.message
                });
            });
        }

        // Função para carregar bairros
        function carregarBairros() {
            fetch('bairro-listar.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const lista = document.getElementById('lista_bairros');
                    lista.innerHTML = '';
                    
                    data.bairros.forEach(bairro => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center justify-between p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow';
                        div.innerHTML = `
                            <div class="flex items-center gap-3">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <h4 class="font-medium">${bairro.nome}</h4>
                                    <p class="text-sm text-gray-500">R$ ${parseFloat(bairro.valor).toFixed(2)}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="abrirModal('bairro', { nome: '${bairro.nome}', valor: ${bairro.valor} })" class="text-gray-400 hover:text-theme">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="excluirBairro('${bairro.nome}')" class="text-gray-400 hover:text-red-500">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                        
                        lista.appendChild(div);
                    });
                }
            })
            .catch(error => console.error('Erro ao carregar bairros:', error));
        }

        // Função para excluir bairro
        function excluirBairro(nome) {
            Swal.fire({
                title: 'Confirmar exclusão',
                text: `Deseja realmente excluir o bairro "${nome}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'border border-theme rounded-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('bairro-excluir.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ nome: nome })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Bairro excluído!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                            carregarBairros();
                        } else {
                            throw new Error(data.message || 'Erro ao excluir bairro');
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro ao excluir bairro',
                            text: error.message
                        });
                    });
                }
            });
        }

        // Função para editar bairro
        function editarBairro(event) {
            event.preventDefault();
            const button = event.currentTarget;
            const bairroItem = button.closest('.bairro-item');
            const inputs = bairroItem.querySelectorAll('input');
            const isEditing = !inputs[0].readOnly;

            if (!isEditing) {
                // Habilita edição
                inputs.forEach(input => {
                    input.readOnly = false;
                    input.classList.add('border-blue-500');
                });
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.classList.remove('text-blue-500', 'hover:text-blue-700');
                button.classList.add('text-green-500', 'hover:text-green-700');
            } else {
                // Salva as alterações
                const novoNome = inputs[0].value.trim();
                const novoValor = inputs[1].value;

                fetch('bairro-editar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        nome_antigo: button.getAttribute('data-nome'),
                        nome: novoNome,
                        valor: novoValor
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        carregarBairros();
                        Swal.fire({
                            icon: 'success',
                            title: 'Bairro atualizado!',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        throw new Error(data.message || 'Erro ao atualizar bairro');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro ao atualizar bairro',
                        text: error.message
                    });
                });
            }
        }

        // Função para salvar configurações do Maps
        function salvarConfiguracoesMaps() {
            const data = {
                api_key: document.getElementById('maps_api_key').value,
                endereco: document.getElementById('maps_endereco').value,
                latitude: document.getElementById('maps_latitude').value,
                longitude: document.getElementById('maps_longitude').value,
                raio_entrega: document.getElementById('maps_raio_entrega').value
            };

            fetch('salvar_config_maps.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso',
                        text: 'Configurações salvas com sucesso',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    throw new Error(data.message || 'Erro ao salvar configurações');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message
                });
            });
        }

        // Função para adicionar faixa de distância
        function adicionarDistancia() {
            const inicio = document.getElementById('de_km').value;
            const fim = document.getElementById('ate_km').value;
            const valor = document.getElementById('valor_km').value;

            if (!inicio || !fim || !valor) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Preencha todos os campos'
                });
                return;
            }

            if (parseFloat(inicio) >= parseFloat(fim)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'O valor inicial deve ser menor que o valor final'
                });
                return;
            }

            fetch('dist-nova.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    inicio: parseFloat(inicio),
                    fim: parseFloat(fim),
                    valor: parseFloat(valor)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso',
                        text: data.message
                    });
                    document.getElementById('de_km').value = '';
                    document.getElementById('ate_km').value = '';
                    document.getElementById('valor_km').value = '';
                    carregarFaixasKm();
                } else {
                    throw new Error(data.message || 'Erro ao adicionar faixa de distância');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao adicionar faixa de distância'
                });
            });
        }

        // Função para carregar faixas de distância
        function carregarFaixasKm() {
            fetch('dist-listar.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const lista = document.getElementById('lista_distancias');
                    lista.innerHTML = '';
                    
                    data.faixas.forEach(faixa => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center justify-between p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow';
                        div.innerHTML = `
                            <div class="flex items-center gap-3">
                                <i class="fas fa-route theme-icon"></i>
                                <div>
                                    <h4 class="font-medium">De ${faixa.inicio}km até ${faixa.fim}km</h4>
                                    <p class="text-sm text-gray-500">R$ ${parseFloat(faixa.valor).toFixed(2)}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="abrirModal('distancia', { id: ${faixa.id}, inicio: ${faixa.inicio}, fim: ${faixa.fim}, valor: ${faixa.valor} })" class="text-gray-400 hover:text-theme">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="excluirFaixaKm(${faixa.id})" class="text-gray-400 hover:text-red-500">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                        
                        lista.appendChild(div);
                    });
                }
            })
            .catch(error => console.error('Erro ao carregar faixas:', error));
        }
        
        // Função para editar faixa de distância
        function editarFaixaKm(id, button) {
            const faixaItem = button.closest('.faixa-item');
            const inputs = faixaItem.querySelectorAll('input');
            const isEditing = !inputs[0].readOnly;

            if (!isEditing) {
                // Habilita edição
                inputs.forEach(input => {
                    input.readOnly = false;
                    input.classList.add('border-blue-500');
                });
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.classList.remove('text-blue-500', 'hover:text-blue-700');
                button.classList.add('text-green-500', 'hover:text-green-700');
            } else {
                // Salva alterações
                const distancia_km = parseFloat(inputs[0].value);
                const valor_entrega = parseFloat(inputs[1].value);

                fetch('dist-editar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        distancia_km: distancia_km,
                        valor_entrega: valor_entrega
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso',
                            text: data.message
                        });
                        carregarFaixasKm();
                    } else {
                        throw new Error(data.message || 'Erro ao atualizar faixa de distância');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro ao salvar alterações'
                    });
                });
            }
        }

        // Função para excluir faixa de distância
        function excluirFaixaKm(id) {
            Swal.fire({
                title: 'Confirmar exclusão',
                text: 'Deseja realmente excluir esta faixa de distância?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--theme-color'),
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'border border-theme rounded-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('dist-excluir.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: id })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso',
                                text: data.message
                            });
                            carregarFaixasKm();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro ao excluir faixa de distância'
                        });
                    });
                }
            });
        }

        // Função para selecionar tipo de entrega
        function selecionarTipoEntrega(tipo) {
            const botoes = document.querySelectorAll('.tipo-entrega-btn');
            botoes.forEach(btn => {
                btn.classList.remove('bg-blue-500', 'text-white');
                btn.classList.add('bg-white');
            });

            const botaoSelecionado = document.querySelector(`button[onclick="selecionarTipoEntrega('${tipo}')"]`);
            botaoSelecionado.classList.add('bg-purple-500', 'text-white');
            botaoSelecionado.classList.remove('bg-white');

            document.getElementById('secao-entrega-bairro').classList.toggle('hidden', tipo !== 'bairro');
            document.getElementById('secao-entrega-distancia').classList.toggle('hidden', tipo !== 'distancia');

            // Salva a configuração
            fetch('salvar_config.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tipo_entrega: tipo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: data.message || 'Erro ao salvar configuração'
                    });
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro ao salvar configuração'
                });
            });
        }
        
        // Função para selecionar tema
        function selecionarTema(tema) {
            // Mapeia os temas para seus valores hexadecimais
            const temasCores = {
                'amarelo': '#EAB308',
                'laranja': '#F97316',
                'vermelho': '#EF4444',
                'rosa': '#EC4899',
                'roxo': '#9333EA',
                'azul': '#3B82F6',
                'verde': '#22C55E',
                'cinza': '#6B7280'
            };

            // Pega a cor baseada no tema ou usa o valor personalizado
            const cor = temasCores[tema] || document.getElementById('cor_personalizada').value;
            
            console.log('Tema selecionado:', tema);
            console.log('Nova cor:', cor);

            // Atualiza o preview
            atualizarTema(cor);

            // Salva no banco de dados
            const formData = new FormData();
            formData.append('secao', 'aparencia');
            formData.append('cor_tema', cor);

            // Mostra o loading
            Swal.fire({
                title: 'Alterando tema...',
                text: 'Aguarde um momento',
                allowOutsideClick: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('salvar_configuracoes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove a seleção de todos os botões
                    document.querySelectorAll('.grid-cores button').forEach(btn => {
                        btn.classList.remove('ring-2', 'ring-offset-2');
                    });
                    
                    // Adiciona a seleção no botão correto
                    const botaoSelecionado = document.querySelector(`.grid-cores button[data-nome="${tema}"]`);
                    if (botaoSelecionado) {
                        const corBase = tema === 'roxo' ? 'purple' : 
                                    tema === 'amarelo' ? 'yellow' :
                                    tema === 'laranja' ? 'orange' :
                                    tema === 'vermelho' ? 'red' :
                                    tema === 'verde' ? 'green' :
                                    tema === 'azul' ? 'blue' :
                                    tema === 'preto' ? 'gray' : 'purple';
                                    
                        botaoSelecionado.classList.add('ring-2', 'ring-offset-2', `ring-${corBase}-500`);
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Tema atualizado com sucesso!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    throw new Error(data.message || 'Erro ao atualizar tema');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro ao atualizar tema. Tente novamente.'
                });
            });
        }

        // Funções do Modal de Edição
        function abrirModal(tipo, dados) {
            const modal = document.getElementById('modalEdicao');
            const formBairro = document.getElementById('formBairro');
            const formDistancia = document.getElementById('formDistancia');
            
            // Limpa formulários
            formBairro.classList.add('hidden');
            formDistancia.classList.add('hidden');
            
            if (tipo === 'bairro') {
                document.getElementById('modalTitle').textContent = 'Editar Bairro';
                formBairro.classList.remove('hidden');
                document.getElementById('editBairroNome').value = dados.nome;
                document.getElementById('editBairroValor').value = dados.valor;
                document.getElementById('editBairroNomeAntigo').value = dados.nome;
            } else {
                document.getElementById('modalTitle').textContent = 'Editar Faixa de Distância';
                formDistancia.classList.remove('hidden');
                document.getElementById('editDistanciaId').value = dados.id;
                document.getElementById('editDistanciaDe').value = dados.inicio;
                document.getElementById('editDistanciaAte').value = dados.fim;
                document.getElementById('editDistanciaValor').value = dados.valor;
            }
            
            modal.classList.remove('hidden');
        }

        function fecharModal() {
            document.getElementById('modalEdicao').classList.add('hidden');
        }

        function salvarEdicao() {
            const formBairro = document.getElementById('formBairro');
            if (!formBairro.classList.contains('hidden')) {
                // Editando bairro
                const nome = document.getElementById('editBairroNome').value.trim();
                const valor = document.getElementById('editBairroValor').value;
                const id = document.getElementById('editBairroId').value;
                
                if (!nome || !valor) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Preencha todos os campos'
                    });
                    return;
                }
                
                fetch('bairro-editar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        nome: nome,
                        valor: valor
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso',
                            text: data.message
                        });
                        carregarBairros();
                        fecharModal();
                    } else {
                        throw new Error(data.message || 'Erro ao editar bairro');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: error.message
                    });
                });
            } else {
                // Editando distância
                const id = document.getElementById('editDistanciaId').value;
                const inicio = document.getElementById('editDistanciaDe').value;
                const fim = document.getElementById('editDistanciaAte').value;
                const valor = document.getElementById('editDistanciaValor').value;
                
                if (!inicio || !fim || !valor) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Preencha todos os campos'
                    });
                    return;
                }

                if (parseFloat(inicio) >= parseFloat(fim)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'O valor inicial deve ser menor que o valor final'
                    });
                    return;
                }
                
                fetch('dist-editar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        inicio: inicio,
                        fim: fim,
                        valor: valor
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso',
                            text: data.message
                        });
                        carregarFaixasKm();
                        fecharModal();
                    } else {
                        throw new Error(data.message || 'Erro ao editar faixa de distância');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: error.message
                    });
                });
            }
        }

        // Função para carregar bairros
        function carregarBairros() {
            fetch('bairro-listar.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const lista = document.getElementById('lista_bairros');
                    lista.innerHTML = '';
                    
                    data.bairros.forEach(bairro => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center justify-between p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow';
                        div.innerHTML = `
                            <div class="flex items-center gap-3">
                                <i class="fas fa-map-marker-alt theme-icon"></i>
                                <div>
                                    <h4 class="font-medium">${bairro.nome}</h4>
                                    <p class="text-sm text-gray-500">R$ ${parseFloat(bairro.valor).toFixed(2)}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="abrirModal('bairro', { nome: '${bairro.nome}', valor: ${bairro.valor} })" class="text-gray-400 hover:text-theme">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="excluirBairro('${bairro.nome}')" class="text-gray-400 hover:text-red-500">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                        
                        lista.appendChild(div);
                    });
                }
            })
            .catch(error => console.error('Erro ao carregar bairros:', error));
        }

        // Função para carregar faixas de distância
        function carregarFaixasKm() {
            fetch('dist-listar.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const lista = document.getElementById('lista_distancias');
                    lista.innerHTML = '';
                    
                    data.faixas.forEach(faixa => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center justify-between p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow';
                        div.innerHTML = `
                            <div class="flex items-center gap-3">
                                <i class="fas fa-route theme-icon"></i>
                                <div>
                                    <h4 class="font-medium">De ${faixa.inicio}km até ${faixa.fim}km</h4>
                                    <p class="text-sm text-gray-500">R$ ${parseFloat(faixa.valor).toFixed(2)}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="abrirModal('distancia', { id: ${faixa.id}, inicio: ${faixa.inicio}, fim: ${faixa.fim}, valor: ${faixa.valor} })" class="text-gray-400 hover:text-theme">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="excluirFaixaKm(${faixa.id})" class="text-gray-400 hover:text-red-500">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                        
                        lista.appendChild(div);
                    });
                }
            })
            .catch(error => console.error('Erro ao carregar faixas:', error));
        }
        
        // Função para abrir modal de adição
        function abrirModalAdicao(tipo) {
            const modal = document.getElementById('modalAdicao');
            const formBairro = document.getElementById('formAdicaoBairro');
            const formDistancia = document.getElementById('formAdicaoDistancia');
            
            // Limpa formulários
            formBairro.classList.add('hidden');
            formDistancia.classList.add('hidden');
            
            if (tipo === 'bairro') {
                document.getElementById('modalAdicaoTitle').textContent = 'Adicionar Bairro';
                formBairro.classList.remove('hidden');
                document.getElementById('addBairroNome').value = '';
                document.getElementById('addBairroValor').value = '';
            } else {
                document.getElementById('modalAdicaoTitle').textContent = 'Adicionar Faixa de Distância';
                formDistancia.classList.remove('hidden');
                document.getElementById('addDistanciaDe').value = '';
                document.getElementById('addDistanciaAte').value = '';
                document.getElementById('addDistanciaValor').value = '';
            }
            
            modal.classList.remove('hidden');
        }

        function fecharModalAdicao() {
            document.getElementById('modalAdicao').classList.add('hidden');
        }

        function salvarAdicao() {
            const formBairro = document.getElementById('formAdicaoBairro');
            if (!formBairro.classList.contains('hidden')) {
                // Adicionando bairro
                const nome = document.getElementById('addBairroNome').value.trim();
                const valor = document.getElementById('addBairroValor').value;

                if (!nome || !valor) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Preencha todos os campos'
                    });
                    return;
                }

                fetch('bairro-adicionar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        nome: nome,
                        valor: valor
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Limpa os campos
                        document.getElementById('addBairroNome').value = '';
                        document.getElementById('addBairroValor').value = '';

                        // Recarrega a lista
                        carregarBairros();

                        Swal.fire({
                            icon: 'success',
                            title: 'Bairro adicionado!',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        throw new Error(data.message || 'Erro ao adicionar bairro');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro ao adicionar bairro',
                        text: error.message
                    });
                });
            } else {
                // Adicionando distância
                const inicio = document.getElementById('addDistanciaDe').value;
                const fim = document.getElementById('addDistanciaAte').value;
                const valor = document.getElementById('addDistanciaValor').value;

                if (!inicio || !fim || !valor) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Preencha todos os campos'
                    });
                    return;
                }

                if (parseFloat(inicio) >= parseFloat(fim)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'O valor inicial deve ser menor que o valor final'
                    });
                    return;
                }

                fetch('dist-adicionar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        inicio: inicio,
                        fim: fim,
                        valor: valor
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso',
                            text: 'Faixa de distância adicionada com sucesso!',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        carregarFaixasKm();
                    } else {
                        throw new Error(data.message || 'Erro ao adicionar faixa de distância');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro ao adicionar faixa de distância',
                        text: error.message
                    });
                });
            }

            fecharModalAdicao();
        }
    </script>
    <style>
        :root {
            --theme-color: <?php echo $config['cor_tema'] ?? '#8B5CF6'; ?>;
        }

        .theme-primary {
            background-color: var(--theme-color);
        }

        .theme-primary.text-white {
            color: white;
        }

        @media (max-width: 768px) {
            .menu-container {
                overflow-x: auto;
            }
            .menu-wrapper {
                display: inline-flex;
                padding: 0.5rem;
            }
        }

        .secao-btn {
            transition: all 0.3s ease;
        }

        .secao-btn.active {
            background-color: var(--theme-color) !important;
            color: white !important;
        }
        
        .theme-btn {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .theme-btn.selected::after {
            content: '';
            position: absolute;
            inset: -4px;
            border: 2px solid currentColor;
            border-radius: inherit;
        }
        
        .btn-primary {
            background-color: var(--theme-color) !important;
        }
        
        .text-primary {
            color: var(--theme-color) !important;
        }
        
        .border-primary {
            border-color: var(--theme-color) !important;
        }
        
        .ring-primary {
            --tw-ring-color: var(--theme-color) !important;
        }
        
        /* Substituindo cores do Tailwind */
        .bg-purple-600 {
            background-color: var(--theme-color) !important;
        }

        .bg-purple-500 {
            background-color: var(--theme-color) !important;
        }

        .hover\:bg-purple-700:hover {
            background-color: var(--theme-color) !important;
            filter: brightness(0.9);
        }

        .focus\:ring-purple-500:focus {
            --tw-ring-color: var(--theme-color) !important;
        }

        /* Estilo para botões de tipo de entrega */
        .tipo-entrega-btn {
            transition: all 0.3s ease;
            
            color: black !important;
        }

        .tipo-entrega-btn.active {
            background-color: var(--theme-color) !important;
            color: white !important;
            border-color: var(--theme-color) !important;
        }

        .tipo-entrega-btn.active i {
            color: white !important;
        }

        /* Estilo para ícones nas listagens */
        .text-purple-500 {
            color: var(--theme-color);
        }

        .theme-icon {
            color: var(--theme-color);
        }

        .hover\:text-theme:hover {
            color: var(--theme-color) !important;
        }
    </style>
</head>
<body class="bg-gray-100">

<!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-900 mr-4">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <span class="text-2xl font-semibold">Configurações</span>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Cabeçalho -->
        <?php include 'includes/menu.php'; ?>

        <!-- Menu de Navegação -->
        <div class="menu-container mb-8 -mx-4 px-4 md:mx-0 md:px-0">
            <nav class="menu-wrapper flex space-x-4 md:space-x-6">
                <button type="button" onclick="mostrarSecao('geral')" 
                        class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100 focus:outline-none secao-btn active">
                    <i class="fas fa-store"></i>
                    <span>Geral</span>
                </button>
                <button type="button" onclick="mostrarSecao('contato')" 
                        class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100 focus:outline-none secao-btn">
                    <i class="fas fa-phone"></i>
                    <span>Contato</span>
                </button>
                <button type="button" onclick="mostrarSecao('horarios')" 
                        class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100 focus:outline-none secao-btn">
                    <i class="fas fa-clock"></i>
                    <span>Horários</span>
                </button>
                <button type="button" onclick="mostrarSecao('entrega')" 
                        class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100 focus:outline-none secao-btn">
                    <i class="fas fa-motorcycle"></i>
                    <span>Entrega</span>
                </button>
                <button type="button" onclick="mostrarSecao('aparencia')" 
                        class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100 focus:outline-none secao-btn">
                    <i class="fas fa-paint-brush"></i>
                    <span>Aparência</span>
                </button>
                <button type="button" onclick="mostrarSecao('credenciais')" 
                        class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100 focus:outline-none secao-btn">
                    <i class="fas fa-lock"></i>
                    <span>Credenciais</span>
                </button>
                <button type="button" onclick="mostrarSecao('maps')" 
                        class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-100 focus:outline-none secao-btn">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Maps</span>
                </button>
            </nav>
        </div>

        <!-- Seções de Configuração -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <!-- Configurações Gerais -->
            <div id="secao-geral" class="secao">
                <h2 class="text-xl font-semibold mb-4">Configurações Gerais</h2>
                <div class="space-y-4">
                    <div class="pt-8">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Nome da Loja *
                        </label>
                        <input type="text" id="nome_loja" name="nome_loja" 
                               value="<?php echo htmlspecialchars($config['nome_loja'] ?? ''); ?>"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="local">
                            Local/Bairro
                        </label>
                        <input type="text" id="local" name="local" 
                            placeholder="Ex: Jardim América"
                            value="<?php echo htmlspecialchars($config['local']); ?>"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <!-- Slogan da Loja -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="slogan">
                            Slogan da Loja
                        </label>
                        <input type="text" id="slogan" name="slogan" 
                               value="<?php echo htmlspecialchars($config['slogan'] ?? 'O melhor delivery da região!'); ?>"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               placeholder="Ex: O melhor delivery da região!">
                        <p class="mt-1 text-sm text-gray-500">Este slogan aparecerá ao lado do nome da sua loja</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Pedido Mínimo (R$)
                        </label>
                        <input type="number" step="0.01" id="pedido_minimo" value="<?php echo htmlspecialchars($config['pedido_minimo']); ?>"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Tempo de Entrega (min)
                        </label>
                        <input type="number" id="tempo_entrega" value="<?php echo htmlspecialchars($config['tempo_entrega']); ?>"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button onclick="salvarConfiguracoes('geral')" 
                            class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                        <i class="fas fa-save mr-2"></i>
                        Salvar
                    </button>
                </div>
            </div>

            <!-- Configurações de Contato -->
            <div id="secao-contato" class="secao hidden">
                <h2 class="text-xl font-semibold mb-4">Informações de Contato</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email_loja">
                            E-mail da Loja
                        </label>
                        <input type="email" id="email_loja" name="email_loja" 
                               value="<?php echo htmlspecialchars($config['email_loja']); ?>"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="telefone_loja">
                            Telefone da Loja
                        </label>
                        <input type="tel" id="telefone_loja" name="telefone_loja" 
                               value="<?php echo htmlspecialchars($config['telefone_loja']); ?>"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="instagram">
                            Instagram
                        </label>
                        <input type="text" id="instagram" name="instagram" 
                               value="<?php echo htmlspecialchars($config['instagram']); ?>"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="endereco_loja">
                            Endereço
                        </label>
                        <textarea id="endereco_loja" name="endereco_loja" rows="2"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                               placeholder="Rua, número, bairro, cidade - Estado"><?php echo htmlspecialchars($config['endereco_loja']); ?></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button onclick="salvarConfiguracoes('contato')" 
                            class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                        <i class="fas fa-save mr-2"></i>
                        Salvar
                    </button>
                </div>
            </div>

            <!-- Configurações de Horários -->
            <div id="secao-horarios" class="secao hidden">
                <h2 class="text-xl font-semibold mb-4">Horários de Funcionamento</h2>
                
                <!-- Status de Funcionamento -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Status de Funcionamento</label>
                    <div class="flex space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="status_funcionamento" value="horario" 
                                   <?php echo ($config['status_funcionamento'] ?? 'horario') === 'horario' ? 'checked' : ''; ?>
                                   class="form-radio text-purple-600">
                            <span class="ml-2">Por Horário</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="status_funcionamento" value="aberto"
                                   <?php echo ($config['status_funcionamento'] ?? '') === 'aberto' ? 'checked' : ''; ?>
                                   class="form-radio text-green-600">
                            <span class="ml-2">Sempre Aberto</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="status_funcionamento" value="fechado"
                                   <?php echo ($config['status_funcionamento'] ?? '') === 'fechado' ? 'checked' : ''; ?>
                                   class="form-radio text-red-600">
                            <span class="ml-2">Sempre Fechado</span>
                        </label>
                    </div>
                </div>

                <!-- Grade de Horários -->
                <div id="grade-horarios" class="space-y-4">
                    <?php
                    $dias = [
                        'segunda' => 'Segunda-feira',
                        'terca' => 'Terça-feira',
                        'quarta' => 'Quarta-feira',
                        'quinta' => 'Quinta-feira',
                        'sexta' => 'Sexta-feira',
                        'sabado' => 'Sábado',
                        'domingo' => 'Domingo'
                    ];

                    $horarios = json_decode($config['horarios_funcionamento'] ?? '{}', true) ?: [];

                    foreach ($dias as $dia => $nome): ?>
                        <div class="grid grid-cols-12 gap-4 items-center" data-dia="<?php echo $dia; ?>">
                            <div class="col-span-3">
                                <label class="block text-gray-700 text-sm font-bold"><?php echo $nome; ?></label>
                            </div>
                            <div class="col-span-4">
                                <div class="flex items-center space-x-2">
                                    <input type="time" 
                                           id="<?php echo $dia; ?>_inicio" 
                                           value="<?php echo $horarios[$dia]['inicio'] ?? ''; ?>"
                                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                            </div>
                            <div class="col-span-1 text-center">até</div>
                            <div class="col-span-4">
                                <div class="flex items-center space-x-2">
                                    <input type="time" 
                                           id="<?php echo $dia; ?>_fim" 
                                           value="<?php echo $horarios[$dia]['fim'] ?? ''; ?>"
                                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 flex justify-end">
                    <button onclick="salvarConfiguracoes('horarios')" 
                            class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                        <i class="fas fa-save mr-2"></i>
                        Salvar
                    </button>
                </div>
            </div>

            <!-- Seção de Entrega -->
            <div id="secao-entrega" class="secao hidden">
                <div class="bg-white rounded-lg">
                    <h2 class="text-xl font-semibold mb-6">
                        <i class="fas fa-motorcycle"></i> Entrega
                    </h2>
                    
                    <!-- Botões de seleção do tipo de entrega -->
                    <div class="hidden">
                        <button type="button" id="btn-bairro"
                            class="tipo-entrega-btn flex items-center justify-center gap-2 p-4 bg-white hover:bg-purple-50 border border-gray-200 rounded-lg transition-all <?php echo $config['tipo_entrega'] === 'bairro' ? 'bg-purple-500 !text-white hover:bg-purple-600' : ''; ?>"
                            data-tipo="bairro">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Entrega por Bairro</span>
                        </button>
                        <button type="button" id="btn-distancia"
                            class="tipo-entrega-btn flex items-center justify-center gap-2 p-4 bg-white hover:bg-purple-50 border border-gray-200 rounded-lg transition-all <?php echo $config['tipo_entrega'] === 'distancia' ? 'bg-purple-500 !text-white hover:bg-purple-600' : ''; ?>"
                            data-tipo="distancia">
                            <i class="fas fa-route"></i>
                            <span>Entrega por Distância</span>
                        </button>
                    </div>

                    <!-- Seção de configuração por Bairro -->
                    <div id="secao-entrega-bairro" class="hidden">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium">Configuração por Bairro</h3>
                            <button onclick="abrirModalAdicao('bairro')" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                <i class="fas fa-plus mr-2"></i>
                                Adicionar Bairro
                            </button>
                        </div>
                        
                        <!-- Lista de bairros -->
                        <div id="lista_bairros" class="space-y-2">
                            <!-- Bairros serão adicionados aqui via JavaScript -->
                        </div>
                    </div>

                    <!-- Seção de configuração por Distância -->
                    <div id="secao-entrega-distancia" class="block">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
                            <h3 class="text-lg font-medium">Configuração por Distância</h3>
                            <button onclick="abrirModalAdicao('distancia')" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                <i class="fas fa-plus mr-2"></i>
                                Adicionar Faixa
                            </button>
                        </div>
                        
                        <!-- Lista de distâncias -->
                        <div id="lista_distancias" class="space-y-4">
                            <!-- Distâncias serão adicionadas aqui via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configurações de Design -->
            <div id="secao-aparencia" class="secao hidden">
                <div class="bg-white rounded-lg">
                    <h2 class="text-xl font-semibold mb-6">Aparência</h2>
                    
                    <!-- Seleção de Tema -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">Tema do Sistema</h3>
                        
                        <!-- Grid de cores -->
                        <div class="grid grid-cols-4 gap-4 mb-6 grid-cores">
                            <!-- Amarelo -->
                            <div class="flex flex-col items-center">
                                <button onclick="selecionarTema('amarelo')" data-cor="#EAB308" data-nome="amarelo" 
                                    class="w-16 h-16 rounded-xl bg-yellow-500 hover:scale-105 transition-transform shadow-md hover:shadow-lg active:scale-95<?php echo $config['cor_tema'] === '#EAB308' ? ' ring-2 ring-offset-2 ring-yellow-500' : ''; ?>" 
                                    title="Amarelo"></button>
                                <span class="text-sm mt-2">Amarelo</span>
                            </div>
                            
                            <!-- Laranja -->
                            <div class="flex flex-col items-center">
                                <button onclick="selecionarTema('laranja')" data-cor="#F97316" data-nome="laranja" 
                                    class="w-16 h-16 rounded-xl bg-orange-500 hover:scale-105 transition-transform shadow-md hover:shadow-lg active:scale-95<?php echo $config['cor_tema'] === '#F97316' ? ' ring-2 ring-offset-2 ring-orange-500' : ''; ?>" 
                                    title="Laranja"></button>
                                <span class="text-sm mt-2">Laranja</span>
                            </div>
                            
                            <!-- Vermelho -->
                            <div class="flex flex-col items-center">
                                <button onclick="selecionarTema('vermelho')" data-cor="#EF4444" data-nome="vermelho" 
                                    class="w-16 h-16 rounded-xl bg-red-500 hover:scale-105 transition-transform shadow-md hover:shadow-lg active:scale-95<?php echo $config['cor_tema'] === '#EF4444' ? ' ring-2 ring-offset-2 ring-red-500' : ''; ?>" 
                                    title="Vermelho"></button>
                                <span class="text-sm mt-2">Vermelho</span>
                            </div>

                            <!-- Rosa -->
                            <div class="flex flex-col items-center">
                                <button onclick="selecionarTema('rosa')" data-cor="#EC4899" data-nome="rosa" 
                                    class="w-16 h-16 rounded-xl bg-pink-500 hover:scale-105 transition-transform shadow-md hover:shadow-lg active:scale-95<?php echo $config['cor_tema'] === '#EC4899' ? ' ring-2 ring-offset-2 ring-pink-500' : ''; ?>" 
                                    title="Rosa"></button>
                                <span class="text-sm mt-2">Rosa</span>
                            </div>
                            
                            <!-- Verde -->
                            <div class="flex flex-col items-center">
                                <button onclick="selecionarTema('verde')" data-cor="#22C55E" data-nome="verde" 
                                    class="w-16 h-16 rounded-xl bg-green-500 hover:scale-105 transition-transform shadow-md hover:shadow-lg active:scale-95<?php echo $config['cor_tema'] === '#22C55E' ? ' ring-2 ring-offset-2 ring-green-500' : ''; ?>" 
                                    title="Verde"></button>
                                <span class="text-sm mt-2">Verde</span>
                            </div>
                            
                            <!-- Azul -->
                            <div class="flex flex-col items-center">
                                <button onclick="selecionarTema('azul')" data-cor="#3B82F6" data-nome="azul" 
                                    class="w-16 h-16 rounded-xl bg-blue-500 hover:scale-105 transition-transform shadow-md hover:shadow-lg active:scale-95<?php echo $config['cor_tema'] === '#3B82F6' ? ' ring-2 ring-offset-2 ring-blue-500' : ''; ?>" 
                                    title="Azul"></button>
                                <span class="text-sm mt-2">Azul</span>
                            </div>
                            
                            <!-- Roxo -->
                            <div class="flex flex-col items-center">
                                <button onclick="selecionarTema('roxo')" data-cor="#9333EA" data-nome="roxo" 
                                    class="w-16 h-16 rounded-xl bg-purple-500 hover:scale-105 transition-transform shadow-md hover:shadow-lg active:scale-95<?php echo $config['cor_tema'] === '#9333EA' ? ' ring-2 ring-offset-2 ring-purple-500' : ''; ?>" 
                                    title="Roxo"></button>
                                <span class="text-sm mt-2">Roxo</span>
                            </div>
                            
                            <!-- Cinza -->
                            <div class="flex flex-col items-center">
                                <button onclick="selecionarTema('cinza')" data-cor="#6B7280" data-nome="cinza" 
                                    class="w-16 h-16 rounded-xl bg-gray-500 hover:scale-105 transition-transform shadow-md hover:shadow-lg active:scale-95<?php echo $config['cor_tema'] === '#6B7280' ? ' ring-2 ring-offset-2 ring-gray-500' : ''; ?>" 
                                    title="Cinza"></button>
                                <span class="text-sm mt-2">Cinza</span>
                            </div>

                            <!-- Personalizado -->
                            <div class="flex flex-col items-center">
                                <div class="relative">
                                    <button class="w-16 h-16 rounded-xl bg-gradient-to-br from-pink-500 via-purple-500 to-blue-500 hover:scale-105 transition-transform" title="Personalizado">
                                        <i class="fas fa-palette text-white text-xl"></i>
                                    </button>
                                    <input type="color" id="cor_personalizada" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" title="Escolher cor personalizada">
                                </div>
                                <span class="text-sm mt-2">Personalizado</span>
                            </div>
                        </div>

                        <!-- Preview do Tema -->
                        <div class="bg-gray-100 rounded-lg p-4">
                            <h4 class="text-sm font-medium mb-3">Preview do Tema</h4>
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <div class="flex items-center space-x-4 mb-4">
                                    <div class="w-12 h-12 rounded-lg theme-primary" style="background-color: <?php echo $config['cor_tema'] ?? '#8B5CF6'; ?>;"></div>
                                    <div class="flex-1">
                                        <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                        <div class="h-3 bg-gray-100 rounded w-1/2"></div>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button class="px-4 py-2 rounded-lg theme-primary text-white text-sm" style="background-color: <?php echo $config['cor_tema'] ?? '#8B5CF6'; ?>;">Botão Principal</button>
                                    <button class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm">Botão Secundário</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Uploads (conteúdo existente) -->
                    <div class="border-t pt-8">
                        <h3 class="text-lg font-semibold mb-4">Imagens do Sistema</h3>
                        <!-- Upload do Favicon -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-medium mb-3">Favicon (32x32px)</h3>
                            <div class="mb-3">
                                <img id="favicon_preview" src="<?php echo !empty($config['favicon']) ? '../' . $config['favicon'] : '../assets/images/favicon.svg'; ?>" 
                                     alt="Favicon atual" class="w-8 h-8 mx-auto mb-2">
                            </div>
                            <div class="flex flex-col items-center">
                                <label for="favicon_upload" class="w-full cursor-pointer">
                                    <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-purple-500 transition-colors">
                                        <i class="fas fa-upload text-gray-400 mb-2"></i>
                                        <p class="text-sm text-gray-600">Clique para upload<br>ou arraste aqui</p>
                                        <p class="text-xs text-gray-500 mt-1">ICO, PNG, JPG (32x32px)</p>
                                    </div>
                                </label>
                                <input type="file" id="favicon_upload" class="hidden" accept=".ico,.png,.jpg,.jpeg" onchange="previewImagem(this, 'favicon')">
                            </div>
                        </div>

                        <!-- Upload da Logo -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-medium mb-3">Logo (200x80px)</h3>
                            <div class="mb-3">
                                <img id="logo_preview" src="<?php echo !empty($config['logo']) ? '../' . $config['logo'] : '../assets/images/logo.svg'; ?>" 
                                     alt="Logo atual" class="max-h-20 mx-auto mb-2">
                            </div>
                            <div class="flex flex-col items-center">
                                <label for="logo_upload" class="w-full cursor-pointer">
                                    <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-purple-500 transition-colors">
                                        <i class="fas fa-upload text-gray-400 mb-2"></i>
                                        <p class="text-sm text-gray-600">Clique para upload<br>ou arraste aqui</p>
                                        <p class="text-xs text-gray-500 mt-1">PNG com fundo transparente (200x80px)</p>
                                    </div>
                                </label>
                                <input type="file" id="logo_upload" class="hidden" accept=".png" onchange="previewImagem(this, 'logo')">
                            </div>
                        </div>

                        <!-- Upload do Banner Mobile -->
                        <div class="bg-gray-50 p-4 rounded-lg md:col-span-2 lg:col-span-1">
                            <h3 class="font-medium mb-3">Banner Mobile (420x140px)</h3>
                            <div class="mb-3">
                                <img id="banner_preview" src="<?php echo !empty($config['banner']) ? '../' . $config['banner'] : '../assets/images/banner.svg'; ?>" 
                                     alt="Banner atual" class="w-full h-32 object-cover rounded-lg mb-2">
                            </div>
                            <div class="flex flex-col items-center">
                                <label for="banner_upload" class="w-full cursor-pointer">
                                    <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-purple-500 transition-colors">
                                        <i class="fas fa-upload text-gray-400 mb-2"></i>
                                        <p class="text-sm text-gray-600">Clique para upload<br>ou arraste aqui</p>
                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG (420x140px)</p>
                                    </div>
                                </label>
                                <input type="file" id="banner_upload" class="hidden" accept=".jpg,.jpeg,.png" onchange="previewImagem(this, 'banner')">
                            </div>
                        </div>

                        <!-- Upload do Banner PC -->
                        <div class="bg-gray-50 p-4 rounded-lg md:col-span-2 lg:col-span-1">
                            <h3 class="font-medium mb-3">Banner PC (1400x140px)</h3>
                            <div class="mb-3">
                                <img id="banner_pc_preview" src="<?php echo !empty($config['banner_pc']) ? '../' . $config['banner_pc'] : '../assets/images/banner-pc-default.jpg'; ?>" 
                                     alt="Banner PC atual" class="w-full h-32 object-cover rounded-lg mb-2">
                            </div>
                            <div class="flex flex-col items-center">
                                <label for="banner_pc_upload" class="w-full cursor-pointer">
                                    <div class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-purple-500 transition-colors">
                                        <i class="fas fa-upload text-gray-400 mb-2"></i>
                                        <p class="text-sm text-gray-600">Clique para upload<br>ou arraste aqui</p>
                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG (1400x140px)</p>
                                    </div>
                                </label>
                                <input type="file" id="banner_pc_upload" class="hidden" accept=".jpg,.jpeg,.png" onchange="previewImagem(this, 'banner_pc')">
                            </div>
                        </div>
                    </div>

                    <!-- Informações e dicas -->
                    <div class="mt-6 bg-purple-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-purple-800 mb-2">Dicas para as imagens:</h4>
                        <ul class="text-sm text-purple-700 space-y-1">
                            <li><i class="fas fa-info-circle mr-1"></i> Favicon: Use imagens quadradas no tamanho exato de 32x32 pixels</li>
                            <li><i class="fas fa-info-circle mr-1"></i> Logo: Use PNG com fundo transparente no tamanho 200x80 pixels</li>
                            <li><i class="fas fa-info-circle mr-1"></i> Banner Mobile: Use imagens no tamanho exato de 420x140 pixels</li>
                            <li><i class="fas fa-info-circle mr-1"></i> Banner PC: Use imagens no tamanho exato de 1400x140 pixels</li>
                            <li><i class="fas fa-info-circle mr-1"></i> Tamanho máximo por arquivo: 5MB</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Configurações de Credenciais -->
            <div id="secao-credenciais" class="secao hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Seção de Email -->
                    <div class="bg-white rounded-lg">
                        <h2 class="text-xl font-semibold mb-6">Alterar Email</h2>
                        
                        <form id="form-email" class="space-y-6">
                            <!-- Email Atual -->
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">
                                    Email Atual
                                </label>
                                <input type="email" disabled
                                       class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg"
                                       value="<?php echo htmlspecialchars($_SESSION['admin']['email']); ?>">
                            </div>

                            <!-- Novo Email -->
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="novo_email">
                                    Novo Email
                                </label>
                                <input type="email" id="novo_email" name="novo_email" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                       placeholder="Digite o novo email">
                            </div>

                            <!-- Senha Atual -->
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="senha_atual_email">
                                    Senha Atual
                                </label>
                                <input type="password" id="senha_atual_email" name="senha_atual" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                       placeholder="Digite sua senha atual">
                                <p class="mt-1 text-sm text-gray-500">Necessária para confirmar a alteração</p>
                            </div>

                            <!-- Botão de Salvar -->
                            <div class="flex justify-end">
                                <button type="button" onclick="salvarEmail()"
                                        class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                                    <i class="fas fa-save mr-2"></i>
                                    Atualizar Email
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Seção de Senha -->
                    <div class="bg-white rounded-lg">
                        <h2 class="text-xl font-semibold mb-6">Alterar Senha</h2>
                        
                        <form id="form-senha" class="space-y-6">
                            <!-- Senha Atual -->
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="senha_atual_senha">
                                    Senha Atual
                                </label>
                                <input type="password" id="senha_atual_senha" name="senha_atual" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                       placeholder="Digite sua senha atual">
                            </div>

                            <!-- Nova Senha -->
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="nova_senha">
                                    Nova Senha
                                </label>
                                <input type="password" id="nova_senha" name="nova_senha" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                       placeholder="Digite a nova senha">
                                <p class="text-sm text-gray-500 mt-1">Mínimo de 6 caracteres</p>
                            </div>

                            <!-- Confirmar Nova Senha -->
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="confirmar_senha">
                                    Confirmar Nova Senha
                                </label>
                                <input type="password" id="confirmar_senha" name="confirmar_senha" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                       placeholder="Confirme a nova senha">
                            </div>

                            <!-- Botão de Salvar -->
                            <div class="flex justify-end">
                                <button type="button" onclick="salvarSenha()"
                                        class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                                    <i class="fas fa-key mr-2"></i>
                                    Atualizar Senha
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Configurações do Google Maps -->
            <div id="secao-maps" class="secao hidden">
                <div class="bg-white rounded-lg">
                    <h2 class="text-xl font-semibold mb-6">
                        <i class="fas fa-map-marked-alt"></i> Configurações do Maps
                    </h2>
                    
                    <!-- API Key -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="maps_api_key">
                            API Key do Google Maps
                        </label>
                        <div class="flex flex-col md:flex-row gap-2">
                            <div class="flex flex-1 gap-2 my-2 md:my-0">
                                <input type="password" id="maps_api_key" 
                                    class="flex-1 h-12 md:h-10 rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500"
                                    placeholder="Insira sua API Key do Google Maps">
                                <button onclick="toggleApiKeyVisibility()" class="bg-gray-100 px-3 rounded-md">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <button onclick="validarApiKey()" class="bg-purple-500 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                                <i class="fas fa-check mr-2"></i> Validar e Salvar
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i> 
                            Obtenha sua API Key no <a href="https://console.cloud.google.com" target="_blank" class="text-purple-600 hover:text-purple-800">Console do Google Cloud</a>
                        </p>
                    </div>

                    <!-- Mapa -->
                    <div class="mb-6">
                        <div id="map" class="w-full h-96 rounded-lg border-2 border-gray-200"></div>
                    </div>

                    <!-- Endereço -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="maps_endereco">
                            Endereço do Estabelecimento
                        </label>
                        <div class="flex flex-col md:flex-row gap-2 mb-4">
                            <input type="text" id="maps_endereco" 
                                class="flex-1 h-12 md:h-10 rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-2 focus:ring-purple-500"
                                placeholder="Digite o endereço do estabelecimento">
                            <button onclick="buscarEndereco()" class="bg-purple-500 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>

                    <!-- Coordenadas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="maps_latitude">
                                Latitude
                            </label>
                            <input type="text" id="maps_latitude" readonly
                                class="w-full px-3 py-2 border rounded-lg bg-gray-50"
                                placeholder="Latitude será preenchida automaticamente">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="maps_longitude">
                                Longitude
                            </label>
                            <input type="text" id="maps_longitude" readonly
                                class="w-full px-3 py-2 border rounded-lg bg-gray-50"
                                placeholder="Longitude será preenchida automaticamente">
                        </div>
                    </div>

                    <!-- Raio de Entrega -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="maps_raio_entrega">
                            Raio de Entrega (km)
                        </label>
                        <input type="number" id="maps_raio_entrega" min="0" step="0.1"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="Digite o raio máximo de entrega">
                    </div>

                    <!-- Botão Salvar -->
                    <div class="flex justify-end">
                        <button onclick="salvarConfiguracoesMaps()" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600">
                            <i class="fas fa-save mr-2"></i> Salvar Configurações
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div id="modalEdicao" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-xl font-semibold"></h3>
                <button onclick="fecharModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Formulário para Editar Bairros -->
            <form id="formBairro" class="hidden space-y-6">
                <input type="hidden" id="editBairroId">
                <div>
                    <label for="editBairroNome" class="block text-sm font-medium text-gray-700 mb-2">Nome do Bairro</label>
                    <input type="text" id="editBairroNome" class="w-full px-4 py-2 text-base border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Digite o nome do bairro">
                </div>
                <div>
                    <label for="editBairroValor" class="block text-sm font-medium text-gray-700 mb-2">Valor da Entrega</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500">R$</span>
                        </div>
                        <input type="number" id="editBairroValor" step="0.01" min="0" class="w-full pl-10 pr-4 py-2 text-base border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="0,00">
                    </div>
                </div>
            </form>

            <!-- Formulário para Editar Distâncias -->
            <form id="formDistancia" class="hidden space-y-6">
                <input type="hidden" id="editDistanciaId">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="editDistanciaDe" class="block text-sm font-medium text-gray-700 mb-2">De (km)</label>
                        <input type="number" id="editDistanciaDe" step="0.1" min="0" class="w-full px-4 py-2 text-base border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="0,0">
                    </div>
                    <div>
                        <label for="editDistanciaAte" class="block text-sm font-medium text-gray-700 mb-2">Até (km)</label>
                        <input type="number" id="editDistanciaAte" step="0.1" min="0" class="w-full px-4 py-2 text-base border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="0,0">
                    </div>
                </div>
                <div>
                    <label for="editDistanciaValor" class="block text-sm font-medium text-gray-700 mb-2">Valor da Entrega</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500">R$</span>
                        </div>
                        <input type="number" id="editDistanciaValor" step="0.01" min="0" class="w-full pl-10 pr-4 py-2 text-base border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="0,00">
                    </div>
                </div>
            </form>

            <div class="mt-6 flex justify-end gap-3">
                <button onclick="fecharModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancelar
                </button>
                <button onclick="salvarEdicao()" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                    Salvar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Adição -->
    <div id="modalAdicao" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalAdicaoTitle" class="text-xl font-semibold"></h3>
                <button onclick="fecharModalAdicao()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Formulário para Adicionar Bairros -->
            <div id="formAdicaoBairro" class="hidden">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Bairro</label>
                        <input type="text" id="addBairroNome" class="w-full px-4 py-2 text-base border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Digite o nome do bairro">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Valor da Entrega</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500">R$</span>
                            </div>
                            <input type="number" id="addBairroValor" step="0.01" min="0" class="w-full pl-10 pr-4 py-2 text-base border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="0,00">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulário para Adicionar Distâncias -->
            <div id="formAdicaoDistancia" class="hidden">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">De (km)</label>
                        <input type="number" id="addDistanciaDe" step="0.1" min="0" class="w-full px-4 py-2 text-base border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="0,0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Até (km)</label>
                        <input type="number" id="addDistanciaAte" step="0.1" min="0" class="w-full px-4 py-2 text-base border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="0,0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Valor da Entrega</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500">R$</span>
                            </div>
                            <input type="number" id="addDistanciaValor" step="0.01" min="0" class="w-full pl-10 pr-4 py-2 text-base border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="0,00">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button onclick="fecharModalAdicao()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancelar
                </button>
                <button onclick="salvarAdicao()" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                    Adicionar
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado');
            
            // Carrega as configurações do Maps
            fetch('carregar_config_maps.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Configurações do Maps carregadas:', data);
                    document.getElementById('maps_api_key').value = data.api_key || '';
                    document.getElementById('maps_endereco').value = data.endereco || '';
                    document.getElementById('maps_latitude').value = data.latitude || '';
                    document.getElementById('maps_longitude').value = data.longitude || '';
                    document.getElementById('maps_raio_entrega').value = data.raio_entrega || '';
                    
                    // Se tiver API key, carrega o mapa
                    if (data.api_key && !mapsLoaded) {
                        loadGoogleMaps(data.api_key);
                    }
                } else {
                    console.error('Erro ao carregar configurações do Maps:', data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao carregar configurações do Maps:', error);
            });
            
            // Carrega as listas inicialmente
            carregarBairros();
            carregarFaixasKm();
            console.log('Iniciando carregamento das listas');

            // Adiciona event listeners aos botões
            const btnBairro = document.getElementById('btn-bairro');
            const btnDistancia = document.getElementById('btn-distancia');
            
            if (btnBairro && btnDistancia) {
                btnBairro.addEventListener('click', () => selecionarTipoEntrega('bairro'));
                btnDistancia.addEventListener('click', () => selecionarTipoEntrega('distancia'));
                console.log('Event listeners adicionados com sucesso');
            } else {
                console.error('Botões não encontrados');
            }
        });

        function selecionarTipoEntrega(tipo) {
            console.log('Função chamada com tipo:', tipo);
            
            Swal.fire({
                title: 'Confirmar alteração',
                html: `Deseja realmente alterar para entrega por <strong>${tipo === 'bairro' ? 'Bairro' : 'Distância'}</strong>?<br><small class="text-red-600">Isso irá limpar as configurações anteriores.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#7C3AED',
                cancelButtonColor: '#9CA3AF',
                confirmButtonText: 'Sim, alterar',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'border border-theme rounded-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Alterna visibilidade das seções
                    const secaoBairro = document.getElementById('secao-entrega-bairro');
                    const secaoDistancia = document.getElementById('secao-entrega-distancia');
                    
                    if (secaoBairro && secaoDistancia) {
                        secaoBairro.classList.toggle('hidden', tipo !== 'bairro');
                        secaoDistancia.classList.toggle('hidden', tipo !== 'distancia');
                    }

                    // Atualiza os botões
                    const botoes = document.querySelectorAll('.tipo-entrega-btn');
                    botoes.forEach(btn => {
                        const isTipoAtual = btn.dataset.tipo === tipo;
                        btn.classList.remove('bg-purple-500', 'bg-white', '!text-white', 'hover:bg-purple-600');
                        if (isTipoAtual) {
                            btn.classList.add('bg-purple-500', '!text-white', 'hover:bg-purple-600');
                            btn.classList.remove('bg-white', 'hover:bg-purple-50');
                        } else {
                            btn.classList.add('bg-white', 'hover:bg-purple-50');
                            btn.classList.remove('bg-purple-500', '!text-white', 'hover:bg-purple-600');
                        }
                    });

                    // Salva a configuração
                    fetch('salvar_config.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            tipo_entrega: tipo,
                            limpar_dados: true
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mostra mensagem de sucesso
                            const modalSucesso = document.createElement('div');
                            modalSucesso.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                            modalSucesso.innerHTML = `
                                <div class="bg-white rounded-lg p-6 w-96">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-medium text-green-600">Sucesso!</h3>
                                        <button class="text-gray-400 hover:text-gray-500" onclick="this.closest('.fixed').remove()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="mb-6">
                                        <p>O tipo de entrega foi alterado com sucesso.</p>
                                    </div>
                                    <div class="flex justify-end">
                                        <button class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" onclick="this.closest('.fixed').remove()">
                                            OK
                                        </button>
                                    </div>
                                </div>
                            `;
                            document.body.appendChild(modalSucesso);
                        } else {
                            throw new Error(data.message || 'Erro ao salvar configuração');
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        // Mostra mensagem de erro
                        const modalErro = document.createElement('div');
                        modalErro.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                        modalErro.innerHTML = `
                            <div class="bg-white rounded-lg p-6 w-96">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-red-600">Erro</h3>
                                    <button class="text-gray-400 hover:text-gray-500" onclick="this.closest('.fixed').remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="mb-6">
                                    <p>Ocorreu um erro ao salvar a configuração.</p>
                                </div>
                                <div class="flex justify-end">
                                    <button class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700" onclick="this.closest('.fixed').remove()">
                                        OK
                                    </button>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modalErro);
                    });
                }
            });
        }

        function selecionarTema(tema) {
            // Mapeia os temas para seus valores hexadecimais
            const temasCores = {
                'amarelo': '#EAB308',
                'laranja': '#F97316',
                'vermelho': '#EF4444',
                'rosa': '#EC4899',
                'roxo': '#9333EA',
                'azul': '#3B82F6',
                'verde': '#22C55E',
                'cinza': '#6B7280'
            };
            
            // Pega a cor baseada no tema ou usa o valor personalizado
            const cor = temasCores[tema] || document.getElementById('cor_personalizada').value;
            
            console.log('Tema selecionado:', tema);
            console.log('Nova cor:', cor);

            // Atualiza o preview
            atualizarTema(cor);

            // Salva no banco de dados
            const formData = new FormData();
            formData.append('secao', 'aparencia');
            formData.append('cor_tema', cor);

            // Mostra o loading
            Swal.fire({
                title: 'Alterando tema...',
                text: 'Aguarde um momento',
                allowOutsideClick: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('salvar_configuracoes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove a seleção de todos os botões
                    document.querySelectorAll('.grid-cores button').forEach(btn => {
                        btn.classList.remove('ring-2', 'ring-offset-2');
                    });
                    
                    // Adiciona a seleção no botão correto
                    const botaoSelecionado = document.querySelector(`.grid-cores button[data-nome="${tema}"]`);
                    if (botaoSelecionado) {
                        const corBase = tema === 'roxo' ? 'purple' : 
                                    tema === 'amarelo' ? 'yellow' :
                                    tema === 'laranja' ? 'orange' :
                                    tema === 'vermelho' ? 'red' :
                                    tema === 'verde' ? 'green' :
                                    tema === 'azul' ? 'blue' :
                                    tema === 'preto' ? 'gray' : 'purple';
                                    
                        botaoSelecionado.classList.add('ring-2', 'ring-offset-2', `ring-${corBase}-500`);
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Tema atualizado com sucesso!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    throw new Error(data.message || 'Erro ao atualizar tema');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro ao atualizar tema. Tente novamente.'
                });
            });
        }

        // Função para atualizar a cor do tema
        function atualizarTema(cor) {
            document.documentElement.style.setProperty('--theme-color', cor);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Evento de mudança da cor personalizada
            document.getElementById('cor_personalizada').addEventListener('input', function(e) {
                const cor = e.target.value;
                atualizarTema(cor);
            });

            // Evento quando terminar de escolher a cor
            document.getElementById('cor_personalizada').addEventListener('change', function(e) {
                const cor = e.target.value;
                
                fetch('salvar_configuracoes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        secao: 'aparencia',
                        cor_tema: cor
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Tema atualizado com sucesso!',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        throw new Error(data.message || 'Erro ao atualizar tema');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Não foi possível salvar o tema'
                    });
                });
            });
        });
    </script>
</body>
</html>
