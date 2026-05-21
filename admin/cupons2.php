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
        codigo, 
        tipo, 
        valor, 
        quantidade_maxima,
        quantidade_usada,
        data_inicio,
        data_fim,
        CONCAT(DATE_FORMAT(data_inicio, '%d/%m/%Y'), ' - ', 
            IFNULL(DATE_FORMAT(data_fim, '%d/%m/%Y'), 'Indeterminada')) as validade 
        FROM cupons 
        WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function formatarTipoCupom($tipo) {
    $tipos = [
        'valor_total' => "Valor<br>Total",
        'porcentagem_total' => "Porcentagem<br>Total",
        'valor_frete' => "Valor<br>Frete",
        'porcentagem_frete' => "Porcentagem<br>Frete"
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
            'quantidade_maxima' => !empty($_POST['quantidade_maxima']) ? $_POST['quantidade_maxima'] : null,
            'valor_minimo' => !empty($_POST['valor_minimo']) ? $_POST['valor_minimo'] : null
        ];

        if (isset($_POST['cupom_id'])) {
            $query = "UPDATE cupons SET codigo = :codigo, tipo = :tipo, valor = :valor, data_inicio = :data_inicio, data_fim = :data_fim, quantidade_maxima = :quantidade_maxima, valor_minimo_pedido = :valor_minimo WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['cupom_id']);
        } else {
            $query = "INSERT INTO cupons (codigo, tipo, valor, data_inicio, data_fim, quantidade_maxima, valor_minimo_pedido, ativo)
                      VALUES (:codigo, :tipo, :valor, :data_inicio, :data_fim, :quantidade_maxima, :valor_minimo, 1)";
            $stmt = $db->prepare($query);
        }

        $stmt->execute($dados);

        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Cupom cadastrado com sucesso',
                showConfirmButton: false,
                timer: 1500
            }).then(() => window.location.reload());
        </script>";

    } catch (PDOException $e) {
        $error = $e->getCode() == 23000 ? 'Código já existe' : 'Erro no servidor';
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
$query = "SELECT * FROM cupons ORDER BY created_at DESC";
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($cupons as $cupom): ?>
                            <tr class="cursor-pointer hover:bg-gray-50" 
                                ondblclick="abrirDetalhesCupom(<?= $cupom['id'] ?>)"
                                onclick="handleClick(<?= $cupom['id'] ?>)">
                                <td class="px-6 py-4"><?= $cupom['codigo'] ?></td>
                                <td class="px-6 py-4"><?= formatarTipoCupom($cupom['tipo']) ?></td>
                                <td class="px-6 py-4">
                                    <?= ($cupom['tipo'] === 'porcentagem_total' || $cupom['tipo'] === 'porcentagem_frete') 
                                        ? $cupom['valor'].'%' 
                                        : 'R$ '.number_format($cupom['valor'], 2, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?= date('d/m/Y', strtotime($cupom['data_inicio'])) ?> - 
                                    <?= $cupom['data_fim'] ? date('d/m/Y', strtotime($cupom['data_fim'])) : 'Indeterminada' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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

            <form method="POST" id="formCupom">
                <div class="space-y-4">
                    <div>
                        <label class="block text-base font-medium mb-1">Código *</label>
                        <input type="text" name="codigo" required 
                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-theme focus:border-theme">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-1">Tipo *</label>
                        <select name="tipo" required 
                                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-theme focus:border-theme">
                            <option value="valor_total">Valor Total (R$)</option>
                            <option value="porcentagem_total">Porcentagem Total (%)</option>
                            <option value="valor_frete">Valor Frete (R$)</option>
                            <option value="porcentagem_frete">Porcentagem Frete (%)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-1">Valor *</label>
                        <input type="number" step="0.01" name="valor" required 
                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-theme focus:border-theme">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-1">Data Início *</label>
                        <input type="date" name="data_inicio" required 
                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-theme focus:border-theme">
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-1">Data Fim</label>
                        <input type="date" name="data_fim" 
                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-theme focus:border-theme">
                        <p class="text-sm text-gray-500 mt-1">Deixe em branco para validade indeterminada</p>
                    </div>

                    <div>
                        <label class="block text-base font-medium mb-1">Usos Máximos</label>
                        <input type="number" name="quantidade_maxima" min="0" 
                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-theme focus:border-theme">
                        <p class="text-sm text-gray-500 mt-1">Digite 0 ou deixe em branco para usos ilimitados</p>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-theme text-white px-6 py-3 rounded-lg hover:bg-opacity-90">
                            Salvar Cupom
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detalhes do Cupom -->
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
                    Editar
                </button>
                <button type="button" onclick="excluirCupom()" 
                        class="w-full px-5 py-4 text-red-600 hover:bg-red-50 rounded-lg border border-red-600 text-lg">
                    Excluir
                </button>
            </div>
        </div>
    </div>

    <script>
    // Função para formatar o tipo do cupom
    function formatTipoCupom(tipo) {
        const tipos = {
            'valor_total': 'Valor Total',
            'porcentagem_total': 'Porcentagem Total',
            'valor_frete': 'Valor Frete',
            'porcentagem_frete': 'Porcentagem Frete'
        };
        return tipos[tipo] || tipo;
    }

    let cupomAtual = null;
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

    function mostrarModal(isEdit = false) {
        document.getElementById('modalTitle').textContent = isEdit ? 'Editar Cupom' : 'Novo Cupom';
        document.getElementById('cupomModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function fecharModal() {
        document.getElementById('cupomModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        // Limpar formulário
        document.getElementById('formCupom').reset();
        // Remover campo hidden de id se existir
        const hiddenId = document.querySelector('input[name="cupom_id"]');
        if (hiddenId) hiddenId.remove();
    }

    function fecharDetalhesModal() {
        document.getElementById('detalhesCupomModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function abrirDetalhesCupom(id) {
        cupomAtual = id;
        fetch(`?cupom_id=${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('detalhesCupomContent').innerHTML = `
                    <p class="mb-4 p-2 bg-gray-50 rounded"><strong class="font-semibold">Código:</strong><br>${data.codigo}</p>
                    <p class="mb-4 p-2 bg-gray-50 rounded"><strong class="font-semibold">Tipo:</strong><br>${formatTipoCupom(data.tipo)}</p>
                    <p class="mb-4 p-2 bg-gray-50 rounded"><strong class="font-semibold">Valor:</strong><br>${data.tipo.includes('porcentagem') ? data.valor + '%' : 'R$ ' + parseFloat(data.valor).toFixed(2)}</p>
                    <p class="mb-4 p-2 bg-gray-50 rounded"><strong class="font-semibold">Validade:</strong><br>${data.validade}</p>
                    <p class="mb-4 p-2 bg-gray-50 rounded">
                        <strong class="font-semibold">Usos:</strong><br>
                        ${data.quantidade_usada} de ${data.quantidade_maxima || '∞'} utilizações
                        ${data.quantidade_maxima ? 
                            `<div class="mt-2 bg-gray-200 rounded-full h-2">
                                <div class="bg-theme rounded-full h-2" style="width: ${(data.quantidade_usada / data.quantidade_maxima * 100)}%"></div>
                            </div>` 
                            : ''}
                    </p>
                `;
                document.getElementById('detalhesCupomModal').classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            });
    }

    function editarCupom() {
        const id = cupomAtual;
        fetch(`?cupom_id=${id}`)
            .then(response => response.json())
            .then(data => {
                // Preencher o modal de novo cupom com os dados existentes
                document.querySelector('input[name="codigo"]').value = data.codigo;
                document.querySelector('select[name="tipo"]').value = data.tipo;
                document.querySelector('input[name="valor"]').value = data.valor;
                
                // Preencher as datas diretamente dos campos do banco
                document.querySelector('input[name="data_inicio"]').value = data.data_inicio;
                document.querySelector('input[name="data_fim"]').value = data.data_fim || '';
                document.querySelector('input[name="quantidade_maxima"]').value = data.quantidade_maxima || '0';
                
                // Adicionar ID do cupom em um campo hidden
                let hiddenId = document.querySelector('input[name="cupom_id"]');
                if (!hiddenId) {
                    hiddenId = document.createElement('input');
                    hiddenId.type = 'hidden';
                    hiddenId.name = 'cupom_id';
                    document.querySelector('form').appendChild(hiddenId);
                }
                hiddenId.value = id;
                
                // Fechar modal de detalhes e abrir modal de edição
                fecharDetalhesModal();
                mostrarModal(true);
            });
    }

    function excluirCupom() {
        Swal.fire({
            title: 'Confirmar exclusão',
            text: 'Tem certeza que deseja excluir este cupom?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#661120',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('excluir_cupom.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${cupomAtual}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire(
                            'Excluído!',
                            'Cupom excluído com sucesso.',
                            'success'
                        ).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire(
                            'Erro!',
                            'Não foi possível excluir o cupom.',
                            'error'
                        );
                    }
                });
            }
        });
    }

    // Validação do valor do cupom
    document.querySelector('select[name="tipo"]').addEventListener('change', function() {
        const valor = document.querySelector('input[name="valor"]');
        if (this.value.includes('porcentagem') && parseFloat(valor.value) > 100) {
            Swal.fire('Erro', 'Porcentagem não pode ser maior que 100%', 'error');
        }
    });
    </script>
</body>
</html>
