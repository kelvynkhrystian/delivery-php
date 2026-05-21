<?php
session_start();
require_once '../config/database.php';
require_once 'includes/load_config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$config = carregarConfiguracoes($db);

function getCupomDetails($id, $db) {
    $query = "SELECT 
        id,
        codigo, 
        tipo, 
        valor, 
        ativo,
        DATE_FORMAT(data_inicio, '%Y-%m-%d') as data_inicio,
        DATE_FORMAT(data_fim, '%Y-%m-%d') as data_fim,
        valor_minimo_pedido,
        CONCAT(DATE_FORMAT(data_inicio, '%d/%m/%Y'), ' - ', 
            IFNULL(DATE_FORMAT(data_fim, '%d/%m/%Y'), 'Indeterminada')) as validade
        FROM cupons 
        WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function formatarTipoCupom($tipo) {
    $tipos = [
        'valor_total' => "Valor Total",
        'porcentagem_total' => "Porcentagem Total",
        'valor_frete' => "Valor Frete",
        'porcentagem_frete' => "Porcentagem Frete"
    ];
    return $tipos[$tipo] ?? $tipo;
}

if (isset($_GET['cupom_id'])) {
    $cupomDetails = getCupomDetails($_GET['cupom_id'], $db);
    echo json_encode($cupomDetails);
    exit;
}

// Processar formulário de cupom
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dados = [
            'codigo' => strtoupper($_POST['codigo']),
            'tipo' => $_POST['tipo'],
            'valor' => $_POST['valor'],
            'data_inicio' => $_POST['data_inicio'],
            'data_fim' => !empty($_POST['data_fim']) ? $_POST['data_fim'] : null,
            'valor_minimo_pedido' => !empty($_POST['valor_minimo']) ? $_POST['valor_minimo'] : null
        ];

        if (isset($_POST['cupom_id'])) {
            // Edição
            $query = "UPDATE cupons SET 
                        codigo = :codigo, 
                        tipo = :tipo, 
                        valor = :valor, 
                        data_inicio = :data_inicio, 
                        data_fim = :data_fim, 
                        valor_minimo_pedido = :valor_minimo_pedido 
                     WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['cupom_id']);
        } else {
            // Novo cupom
            $query = "INSERT INTO cupons (
                        codigo, tipo, valor, data_inicio, data_fim, 
                        valor_minimo_pedido, ativo
                    ) VALUES (
                        :codigo, :tipo, :valor, :data_inicio, :data_fim,
                        :valor_minimo_pedido, 1
                    )";
            $stmt = $db->prepare($query);
        }

        foreach ($dados as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();

        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Cupom " . (isset($_POST['cupom_id']) ? 'atualizado' : 'cadastrado') . " com sucesso',
                showConfirmButton: false,
                timer: 1500
            }).then(() => window.location.reload());
        </script>";

    } catch (PDOException $e) {
        $error = $e->getCode() == 23000 ? 'Código já existe' : 'Erro no servidor: ' . $e->getMessage();
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: '$error'
            });
        </script>";
    }
}

// Buscar cupons existentes
$query = "SELECT 
            c.*,
            CONCAT(DATE_FORMAT(data_inicio, '%d/%m/%Y'), ' - ', 
                IFNULL(DATE_FORMAT(data_fim, '%d/%m/%Y'), 'Indeterminada')) as validade
          FROM cupons c 
          ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$cupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cupons - Admin</title>
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
    <script>
    let cupomAtual = null;
    let cupomAtualAtivo = null;
    let clickTimer = null;
    const DOUBLE_CLICK_DELAY = 300;

    function handleClick(id) {
        // Em dispositivos móveis, abre imediatamente
        if ('ontouchstart' in window) {
            abrirDetalhesCupom(id);
            return;
        }

        // Em desktop, mantém o comportamento de duplo clique
        if (clickTimer === null) {
            clickTimer = setTimeout(() => {
                clickTimer = null;
            }, DOUBLE_CLICK_DELAY);
        } else {
            clearTimeout(clickTimer);
            clickTimer = null;
            abrirDetalhesCupom(id);
        }
    }

    function mostrarModal() {
        document.getElementById('modalTitle').textContent = 'Novo Cupom';
        document.getElementById('cupomModal').classList.remove('hidden');
        // Limpa os campos
        document.getElementById('cupom_id').value = '';
        document.getElementById('codigo').value = '';
        document.getElementById('tipo').value = 'valor_total';
        document.getElementById('valor').value = '';
        document.getElementById('data_inicio').value = '';
        document.getElementById('data_fim').value = '';
        document.getElementById('valor_minimo_pedido').value = '';
        document.getElementById('ativo').checked = true;
    }

    function fecharModal() {
        document.getElementById('cupomModal').classList.add('hidden');
    }

    function fecharDetalhesModal() {
        document.getElementById('detalhesCupomModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function formatTipoCupom(tipo) {
        const tipos = {
            'valor_total': 'Valor Total',
            'porcentagem_total': 'Porcentagem Total',
            'valor_frete': 'Valor Frete',
            'porcentagem_frete': 'Porcentagem Frete'
        };
        return tipos[tipo] || tipo;
    }

    function abrirDetalhesCupom(id) {
        cupomAtual = id;
        
        fetch(`?cupom_id=${id}`)
            .then(response => response.json())
            .then(cupom => {
                const tipos = {
                    'valor_total': 'Valor Total (R$)',
                    'porcentagem_total': 'Porcentagem Total (%)',
                    'valor_frete': 'Valor Frete (R$)',
                    'porcentagem_frete': 'Porcentagem Frete (%)'
                };

                const valor = cupom.tipo.includes('porcentagem') ? 
                    `${cupom.valor}%` : 
                    `R$ ${parseFloat(cupom.valor).toFixed(2).replace('.', ',')}`;

                const html = `
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Código</p>
                            <p class="font-semibold">${cupom.codigo}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Tipo</p>
                            <p class="font-semibold">${tipos[cupom.tipo] || cupom.tipo}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Valor</p>
                            <p class="font-semibold">${valor}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            <p class="font-semibold">
                                <span class="px-2 py-1 rounded-full text-sm
                                    ${cupom.ativo == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${cupom.ativo == 1 ? 'Ativo' : 'Inativo'}
                                </span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Validade</p>
                            <p class="font-semibold">${cupom.validade}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Valor Mínimo</p>
                            <p class="font-semibold">${cupom.valor_minimo_pedido ? `R$ ${parseFloat(cupom.valor_minimo_pedido).toFixed(2).replace('.', ',')}` : 'Não definido'}</p>
                        </div>
                    </div>
                `;

                document.getElementById('detalhesCupomContent').innerHTML = html;
                document.getElementById('detalhesCupomModal').classList.remove('hidden');
                document.getElementById('btn_status_text').textContent = cupom.ativo == 1 ? 'Desativar' : 'Ativar';
                cupomAtualAtivo = cupom.ativo == 1;
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao carregar detalhes do cupom',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--cor-tema')
                });
            });
    }

    function alternarStatusCupom() {
        if (!cupomAtual) return;

        const novoStatus = !cupomAtualAtivo;
        
        fetch('ajax/alterar_status_cupom.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: cupomAtual,
                ativo: novoStatus ? 1 : 0
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: `Cupom ${novoStatus ? 'ativado' : 'desativado'} com sucesso!`,
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--cor-tema')
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.message || 'Erro ao alterar status do cupom');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: error.message,
                confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--cor-tema')
            });
        });
    }

    function excluirCupom() {
        if (!cupomAtual) return;

        Swal.fire({
            title: 'Confirmar exclusão?',
            text: "Esta ação não pode ser desfeita!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--cor-tema'),
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ajax/excluir_cupom.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: cupomAtual
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: 'Cupom excluído com sucesso!',
                            confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--cor-tema')
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        throw new Error(data.message || 'Erro ao excluir cupom');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: error.message,
                        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--cor-tema')
                    });
                });
            }
        });
    }

    function editarCupom() {
        if (!cupomAtual) return;
        
        console.log('Editando cupom:', cupomAtual);
        
        fetch(`?cupom_id=${cupomAtual}`)
            .then(response => response.json())
            .then(data => {
                console.log('Dados do cupom para edição:', data);
                
                // Preencher o modal de novo cupom com os dados existentes
                document.querySelector('input[name="codigo"]').value = data.codigo;
                document.querySelector('select[name="tipo"]').value = data.tipo;
                document.querySelector('input[name="valor"]').value = data.valor;
                document.querySelector('input[name="data_inicio"]').value = data.data_inicio;
                document.querySelector('input[name="data_fim"]').value = data.data_fim || '';
                document.querySelector('input[name="valor_minimo_pedido"]').value = data.valor_minimo_pedido || '';
                document.querySelector('input[name="ativo"]').checked = data.ativo == 1;
                
                // Adicionar ID do cupom em um campo hidden
                let hiddenId = document.querySelector('input[name="cupom_id"]');
                if (!hiddenId) {
                    hiddenId = document.createElement('input');
                    hiddenId.type = 'hidden';
                    hiddenId.name = 'cupom_id';
                    document.getElementById('formCupom').appendChild(hiddenId);
                }
                hiddenId.value = cupomAtual;
                
                console.log('Campos preenchidos com sucesso');
                
                // Fechar modal de detalhes e abrir modal de edição
                fecharDetalhesModal();
                document.getElementById('modalTitle').textContent = 'Editar Cupom';
                document.getElementById('cupomModal').classList.remove('hidden');
            })
            .catch(error => {
                console.error('Erro ao editar:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao carregar dados do cupom',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--cor-tema')
                });
            });
    }

    function salvarCupom() {
        const form = document.getElementById('formCupom');
        const formData = {
            id: form.querySelector('input[name="cupom_id"]')?.value,
            codigo: form.querySelector('input[name="codigo"]').value,
            tipo: form.querySelector('select[name="tipo"]').value,
            valor: form.querySelector('input[name="valor"]').value,
            data_inicio: form.querySelector('input[name="data_inicio"]').value,
            data_fim: form.querySelector('input[name="data_fim"]').value,
            valor_minimo_pedido: form.querySelector('input[name="valor_minimo_pedido"]').value || 0,
            ativo: form.querySelector('input[name="ativo"]').checked ? 1 : 0
        };

        console.log('Dados do formulário:', formData);

        fetch('ajax/salvar_cupom.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            console.log('Status da resposta:', response.status);
            return response.text().then(text => {
                console.log('Resposta bruta:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Erro ao fazer parse da resposta:', e);
                    throw new Error('Resposta inválida do servidor');
                }
            });
        })
        .then(data => {
            console.log('Dados processados:', data);
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: 'Cupom salvo com sucesso!',
                    confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--cor-tema')
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.message || 'Erro ao salvar cupom');
            }
        })
        .catch(error => {
            console.error('Erro completo:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: error.message,
                confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--cor-tema')
            });
        });

        // Previne o envio normal do formulário
        return false;
    }
    </script>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-2xl font-semibold">Cupons</span>
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
                <!-- Cabeçalho com Botão -->
                <div class="mb-6">
                    <button onclick="mostrarModal()" class="bg-theme text-white px-6 py-3 rounded-lg hover:bg-opacity-90">
                        <i class="fas fa-plus mr-2"></i>Novo Cupom
                    </button>
                </div>

                <!-- Listagem de Cupons -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-4 bg-blue-50 text-blue-700 flex items-center justify-center text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        Dê um toque (mobile) ou clique duplo (desktop) em um cupom para ver mais detalhes
                    </div>
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Validade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($cupons as $cupom): ?>
                            <tr class="hover:bg-gray-50 cursor-pointer" ondblclick="abrirDetalhesCupom(<?= $cupom['id'] ?>)" onclick="handleClick(<?= $cupom['id'] ?>)">
                                <td class="px-6 py-4"><?= htmlspecialchars($cupom['codigo']) ?></td>
                                <td class="px-4 py-3">
                                    <?php 
                                    if ($cupom['tipo'] === 'valor_total' || $cupom['tipo'] === 'valor_frete') {
                                        echo 'R$ ' . number_format($cupom['valor'], 2, ',', '.');
                                    } else {
                                        echo number_format($cupom['valor'], 2, ',', '.') . '%';
                                    }
                                    ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php 
                                    $tipos = [
                                        'valor_total' => 'Valor Total (R$)',
                                        'porcentagem_total' => 'Porcentagem Total (%)',
                                        'valor_frete' => 'Valor Frete (R$)',
                                        'porcentagem_frete' => 'Porcentagem Frete (%)'
                                    ];
                                    echo $tipos[$cupom['tipo']] ?? $cupom['tipo'];
                                    ?>
                                </td>
                                <td class="px-6 py-4"><?= htmlspecialchars($cupom['validade']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?= $cupom['ativo'] == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $cupom['ativo'] == 1 ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modal de Detalhes do Cupom -->
                <div id="detalhesCupomModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 p-4 md:p-0">
                    <div class="relative bg-white rounded-lg shadow-xl mx-auto mt-4 md:mt-20 p-6 w-full md:w-[95%] max-w-[500px]">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-semibold">Detalhes do Cupom</h3>
                            <button onclick="fecharDetalhesModal()" class="text-gray-500 hover:text-gray-700 p-2">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        <div id="detalhesCupomContent" class="space-y-6 text-lg">
                            <!-- Conteúdo dinâmico será carregado aqui -->
                        </div>

                        <div class="mt-8 flex flex-col md:flex-row justify-center space-y-3 md:space-y-0 md:space-x-3">
                            <button type="button" onclick="editarCupom()" 
                                    class="w-full px-5 py-4 bg-theme text-white rounded-lg hover:bg-opacity-90 text-lg">
                                <i class="fas fa-edit mr-2"></i>Editar
                            </button>
                            <button type="button" onclick="alternarStatusCupom()" 
                                    class="w-full px-5 py-4 bg-blue-600 text-white rounded-lg hover:bg-opacity-90 text-lg">
                                <i class="fas fa-power-off mr-2"></i>
                                <span id="btn_status_text">Ativar/Desativar</span>
                            </button>
                            <button type="button" onclick="excluirCupom()" 
                                    class="w-full px-5 py-4 text-red-600 hover:bg-red-50 rounded-lg border border-red-600 text-lg">
                                <i class="fas fa-trash-alt mr-2"></i>Excluir
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal de Novo/Editar Cupom -->
                <div id="cupomModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
                    <div class="relative bg-white rounded-lg shadow-xl mx-auto mt-20 p-6" style="width: 95%; max-width: 500px;">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold" id="modalTitle">Novo Cupom</h3>
                            <button onclick="fecharModal()" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <form id="formCupom" class="space-y-4">
                            <!-- Campo hidden para ID -->
                            <input type="hidden" id="cupom_id" name="cupom_id">

                            <!-- Código -->
                            <div class="mb-4">
                                <label for="codigo" class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                                <input type="text" id="codigo" name="codigo" required 
                                    class="w-full p-2 border rounded-lg" placeholder="Ex: DESCONTO20">
                            </div>

                            <!-- Tipo -->
                            <div class="mb-4">
                                <label for="tipo" class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                                <select id="tipo" name="tipo" required class="w-full p-2 border rounded-lg">
                                    <option value="valor_total">Valor Total (R$)</option>
                                    <option value="porcentagem_total">Porcentagem Total (%)</option>
                                    <option value="valor_frete">Valor Frete (R$)</option>
                                    <option value="porcentagem_frete">Porcentagem Frete (%)</option>
                                </select>
                            </div>

                            <!-- Valor -->
                            <div class="mb-4">
                                <label for="valor" class="block text-sm font-medium text-gray-700 mb-2">Valor *</label>
                                <input type="number" id="valor" name="valor" required step="0.01" min="0"
                                    class="w-full p-2 border rounded-lg" placeholder="Valor do desconto">
                            </div>

                            <!-- Data Início -->
                            <div class="mb-4">
                                <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-2">Data Início *</label>
                                <input type="date" id="data_inicio" name="data_inicio" required 
                                    class="w-full p-2 border rounded-lg">
                            </div>

                            <!-- Data Fim -->
                            <div class="mb-4">
                                <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                                <input type="date" id="data_fim" name="data_fim" 
                                    class="w-full p-2 border rounded-lg">
                                <p class="text-sm text-gray-500 mt-1">Deixe em branco para validade indeterminada</p>
                            </div>

                            <!-- Valor Mínimo -->
                            <div class="mb-4">
                                <label for="valor_minimo_pedido" class="block text-sm font-medium text-gray-700 mb-2">Valor Mínimo do Pedido</label>
                                <input type="number" id="valor_minimo_pedido" name="valor_minimo_pedido" step="0.01" min="0"
                                    class="w-full p-2 border rounded-lg" placeholder="Valor mínimo para usar o cupom">
                                <p class="text-sm text-gray-500 mt-1">Opcional</p>
                            </div>

                            <!-- Ativo -->
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" id="ativo" name="ativo" class="form-checkbox h-5 w-5 text-theme" checked>
                                    <span class="ml-2 text-gray-700">Cupom Ativo</span>
                                </label>
                            </div>

                            <!-- Botão Salvar -->
                            <div class="pt-4">
                                <button type="button" onclick="salvarCupom()" 
                                    class="w-full bg-theme text-white px-6 py-3 rounded-lg hover:bg-opacity-90">
                                    Salvar Cupom
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
