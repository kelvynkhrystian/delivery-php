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
$nome_loja = $config['nome_loja'] ?? 'Minha Loja';

// Buscar pedidos com filtros
$query = "SELECT p.id, p.usuario_id, p.status, p.observacoes, p.total, p.created_at, p.updated_at,
                 u.nome, u.telefone, 
                 fp.nome as forma_pagamento
          FROM pedidos p 
          LEFT JOIN usuarios u ON p.usuario_id = u.id 
          LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
          WHERE 1=1";

// Aplicar filtro de data se fornecido
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $data_inicio = DateTime::createFromFormat('d/m/Y', $_GET['data_inicio'])->format('Y-m-d');
    $query .= " AND DATE(p.created_at) >= '$data_inicio'";
}
if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $data_fim = DateTime::createFromFormat('d/m/Y', $_GET['data_fim'])->format('Y-m-d');
    $query .= " AND DATE(p.created_at) <= '$data_fim'";
}

// Aplicar filtro de status se fornecido
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = $_GET['status'];
    $query .= " AND p.status = '$status'";
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $db->query($query);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status possíveis
$status_list = ['pendente', 'confirmado', 'em_preparo', 'saiu_entrega', 'entregue', 'cancelado'];

// Função para pegar primeiro e segundo nome
function formatarNome($nomeCompleto) {
    $partes = explode(' ', trim($nomeCompleto));
    if (count($partes) <= 2) {
        return $nomeCompleto;
    }
    return $partes[0] . ' ' . $partes[1];
}

// Função para formatar status
function formatarStatus($status) {
    switch ($status) {
        case 'pendente':
            return 'Pendente';
        case 'confirmado':
            return 'Confirmado';
        case 'em_preparo':
            return 'Em Preparo';
        case 'saiu_entrega':
            return 'Saiu para Entrega';
        case 'entregue':
            return 'Entregue';
        case 'cancelado':
            return 'Cancelado';
        default:
            return 'Desconhecido';
    }
}

// Contar novos pedidos
$total_novos_pedidos = 0;
foreach ($pedidos as $pedido) {
    if ($pedido['status'] == 'pendente') {
        $total_novos_pedidos++;
    }
}

$ultimo_pedido_id = $pedidos[0]['id'] ?? 0;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <!-- jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="../admin/js/notifications.js"></script>
    <style>
        .ui-datepicker {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            font-family: inherit;
            padding: 0;
            width: 280px;
        }
        .ui-datepicker .ui-datepicker-header {
            background: white;
            border: none;
            border-bottom: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
            padding: 10px;
        }
        .ui-datepicker th {
            background: #f8f9fa;
            border: none;
            color: #374151;
            font-weight: 500;
            padding: 7px;
            text-transform: uppercase;
            font-size: 11px;
        }
        .ui-datepicker td {
            border: none;
            padding: 1px;
        }
        .ui-datepicker td a {
            background: none !important;
            border: none !important;
            text-align: center;
            padding: 7px;
            font-size: 13px;
        }
        .ui-datepicker td a.ui-state-active {
            background: #3b82f6 !important;
            color: white;
            border-radius: 4px;
        }
        .ui-datepicker td a.ui-state-highlight {
            background: #e5e7eb !important;
            color: black;
            border-radius: 4px;
        }
        .ui-datepicker td a:hover {
            background: #f3f4f6 !important;
            border-radius: 4px;
        }
        .ui-datepicker .ui-datepicker-prev,
        .ui-datepicker .ui-datepicker-next {
            top: 8px;
            border: none;
            background: none;
            cursor: pointer;
            color: #666;
            font-size: 20px;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        .ui-datepicker .ui-datepicker-prev {
            left: 5px;
        }
        .ui-datepicker .ui-datepicker-next {
            right: 5px;
        }
        .ui-datepicker .ui-icon {
            display: none;
        }
        .ui-datepicker .ui-datepicker-prev:after {
            content: "←";
            position: absolute;
        }
        .ui-datepicker .ui-datepicker-next:after {
            content: "→";
            position: absolute;
        }
        .ui-datepicker .ui-datepicker-title {
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }
        /* Posicionamento do calendário */
        #ui-datepicker-div {
            left: 50% !important;
            transform: translateX(-50%) !important;
        }
        :root {
            --vanilla-calendar-selected-bg: #3b82f6;
            --vanilla-calendar-today-bg: #e5e7eb;
            --vanilla-calendar-today-color: #000;
            --vanilla-calendar-selected-color: #fff;
        }
        .vanilla-calendar {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            font-family: inherit;
            max-width: 300px;
        }
        .vanilla-calendar-header {
            padding: 15px;
            background: #3b82f6;
            color: white;
            border-radius: 8px 8px 0 0;
        }
        .vanilla-calendar-week {
            padding: 5px;
            background: #f3f4f6;
        }
        .vanilla-calendar-day {
            border-radius: 4px;
        }
        .vanilla-calendar-day:hover {
            background: #93c5fd;
            color: #1e40af;
        }
    </style>
    <style>
        :root {
            --theme-color: <?php echo $config['cor_tema'] ?? '#8B5CF6'; ?>;
        }
        .btn-theme {
            --tw-bg-opacity: 1;
            background-color: var(--theme-color);
        }
        .btn-theme:hover {
            opacity: 0.9;
        }
        .btn-theme-outline {
            background-color: white;
            border: 1px solid var(--theme-color);
            color: var(--theme-color);
        }
        .btn-theme-outline:hover {
            background-color: rgba(var(--theme-color), 0.1);
        }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-900 mr-4">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <span class="text-2xl font-semibold">Pedidos</span>
                </div>
                <div class="flex-1 flex items-center justify-center">
                    <?php if ($total_novos_pedidos > 0): ?>
                        <div class="relative">
                            <button onclick="(() => {
                                notificacoesAtivas = !notificacoesAtivas;
                                localStorage.setItem('notificacoesAtivas', notificacoesAtivas);
                                
                                const icon = this.querySelector('i');
                                if (icon) {
                                    icon.className = notificacoesAtivas ? 'fas fa-bell' : 'fas fa-bell-slash';
                                }

                                // Se ativou as notificações, toca o som
                                if (notificacoesAtivas) {
                                    const audio = new Audio('../assets/sounds/notification.mp3');
                                    audio.play();
                                }
                                
                                // Mostrar alerta
                                Swal.fire({
                                    title: notificacoesAtivas ? 'Notificações Ativadas!' : 'Notificações Desativadas',
                                    text: notificacoesAtivas ? 'Você receberá alertas sonoros de novos pedidos.' : 'Alertas sonoros desativados.',
                                    icon: notificacoesAtivas ? 'success' : 'info',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                            })()" class="bg-white p-2 rounded-full hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 focus:border-indigo-500 relative">
                                <i class="fas fa-bell"></i>
                            </button>
                            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"><?php echo $total_novos_pedidos; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <?php include 'includes/menu.php'; ?>

    <!-- Conteúdo Principal -->
    <div class="max-w-7xl mx-auto px-4 pt-8 pb-6">
        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex items-center justify-between space-x-2">
                <div class="flex items-center ml-auto gap-4">
                    <div class="flex items-center">
                        <label for="filtro-status" class="mr-2 text-sm font-medium text-gray-700">Status:</label>
                        <select id="filtro-status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Todos</option>
                            <?php foreach ($status_list as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo (isset($_GET['status']) && $status == $_GET['status']) ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $status)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button id="toggle-dates" onclick="toggleFiltroData()" class="btn-theme text-white flex items-center justify-center px-2.5 py-1.5 rounded-lg w-24">
                        <i class="fas fa-calendar-alt mr-1.5"></i>Data
                    </button>
                </div>
            </div>
            <div id="date-section" class="hidden mt-4 pt-4 border-t border-gray-200">
                <form class="flex flex-col gap-4">
                    <div class="flex items-center justify-end gap-4">
                        <div class="flex items-center gap-2">
                            <label for="filtro-data-inicial" class="text-sm font-medium text-gray-700">De:</label>
                            <div class="date-input-wrapper">
                                <input type="text" id="filtro-data-inicial" readonly
                                       class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 w-32 py-1.5 text-sm cursor-pointer"
                                       placeholder="dd/mm/aaaa">
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <label for="filtro-data-final" class="text-sm font-medium text-gray-700">Até:</label>
                            <div class="date-input-wrapper">
                                <input type="text" id="filtro-data-final" readonly
                                       class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 w-32 py-1.5 text-sm cursor-pointer"
                                       placeholder="dd/mm/aaaa">
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 w-full">
                        <button type="button" onclick="limparFiltroPorData()" class="flex-1 px-4 py-1.5 btn-theme-outline rounded-lg">
                            Limpar
                        </button>
                        <button type="button" onclick="aplicarFiltroPorData()" class="flex-1 flex items-center justify-center gap-2 px-4 py-1.5 btn-theme text-white rounded-lg">
                            <i class="fas fa-filter"></i>
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Pedidos -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pedido</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($pedidos as $pedido): ?>
                        <tr class="pedido-row" data-pedido-id="<?php echo $pedido['id']; ?>" data-status="<?php echo $pedido['status']; ?>" data-data="<?php echo date('Y-m-d', strtotime($pedido['created_at'])); ?>">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">#<?php echo $pedido['id']; ?></span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm text-gray-900"><?php echo formatarNome($pedido['nome']); ?><br>
                                    <span class="text-gray-500"><?php echo $pedido['telefone']; ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900">R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <select onchange="atualizarStatus(<?php echo $pedido['id']; ?>, this.value); atualizarCorStatus(this);" 
                                        data-pedido-id="<?php echo $pedido['id']; ?>"
                                        class="text-sm rounded-full px-3 py-1 <?php
                                        switch($pedido['status']) {
                                            case 'pendente':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'confirmado':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                            case 'em_preparo':
                                                echo 'bg-purple-100 text-purple-800';
                                                break;
                                            case 'saiu_entrega':
                                                echo 'bg-indigo-100 text-indigo-800';
                                                break;
                                            case 'entregue':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'cancelado':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                        }
                                        ?>">
                                    <option value="pendente" <?php echo $pedido['status'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="confirmado" <?php echo $pedido['status'] == 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                                    <option value="em_preparo" <?php echo $pedido['status'] == 'em_preparo' ? 'selected' : ''; ?>>Em Preparo</option>
                                    <option value="saiu_entrega" <?php echo $pedido['status'] == 'saiu_entrega' ? 'selected' : ''; ?>>Saiu para Entrega</option>
                                    <option value="entregue" <?php echo $pedido['status'] == 'entregue' ? 'selected' : ''; ?>>Entregue</option>
                                    <option value="cancelado" <?php echo $pedido['status'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <?php
                                    $pedidoData = array(
                                        'id' => $pedido['id'],
                                        'nome' => $pedido['nome'],
                                        'telefone' => $pedido['telefone'],
                                        'created_at' => $pedido['created_at'],
                                        'status' => $pedido['status'],
                                        'forma_pagamento' => $pedido['forma_pagamento'] ?? null,
                                        'valor_total' => $pedido['total']
                                    );
                                    ?>
                                    <button 
                                        onclick='verDetalhes(<?php echo $pedido['id']; ?>)'
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-white btn-theme rounded-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-opacity-50 transition-colors duration-200"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Ver
                                    </button>
                                    <button 
                                        onclick="window.open('imprimir_pedido.php?id=<?php echo $pedido['id']; ?>', '_blank')" 
                                        class="inline-flex items-center px-3 py-2 btn-theme-outline rounded-md hover:bg-opacity-10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-opacity-50 transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                        </svg>
                                        Imprimir
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal de Impressão -->
    <div id="modal-impressao" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 max-h-[90vh] overflow-y-auto">
            <div id="modal-impressao-content">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Variáveis globais
        let pedidoAtual = null;
        let audioContext = null;

        // Função para alternar notificações
        function toggleNotifications() {
            notificacoesAtivas = !notificacoesAtivas;
            localStorage.setItem('notificacoesAtivas', notificacoesAtivas);
            
            // Atualizar o ícone
            const notificationIcon = document.querySelector('button[onclick="toggleNotifications()"] i');
            if (notificationIcon) {
                notificationIcon.className = notificacoesAtivas ? 'fas fa-bell' : 'fas fa-bell-slash';
            }
            
            // Mostrar alerta
            Swal.fire({
                title: notificacoesAtivas ? 'Notificações Ativadas!' : 'Notificações Desativadas',
                text: notificacoesAtivas ? 'Você receberá alertas sonoros de novos pedidos.' : 'Alertas sonoros desativados.',
                icon: notificacoesAtivas ? 'success' : 'info',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            }).then(() => {
                // Recarrega a página para atualizar a lista
                window.location.reload();
            });
        }

        // Função para imprimir pedido
        function imprimirPedido(pedidoId) {
            window.open(`imprimir_pedido.php?id=${pedidoId}`, '_blank');
        }

        // Função para ver detalhes do pedido
        function verDetalhes(pedidoId) {
            // Primeiro buscar os dados do pedido
            fetch(`get_pedido.php?id=${pedidoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    pedidoAtual = data;
                    
                    // Buscar itens do pedido
                    return fetch(`get_itens_pedido.php?pedido_id=${pedidoId}`);
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    // Calcular subtotal e taxa de entrega
                    const subtotal = pedidoAtual.subtotal || 0;
                    const taxa_entrega = pedidoAtual.taxa_entrega || 0;
                    const desconto = pedidoAtual.desconto_cupom || 0;
                    const total = subtotal + taxa_entrega - desconto;

                    // Define a classe CSS baseada no status
                    let statusClass = '';
                    switch (pedidoAtual.status) {
                        case 'pendente':
                            statusClass = 'bg-yellow-100 text-yellow-800';
                            break;
                        case 'confirmado':
                            statusClass = 'bg-blue-100 text-blue-800';
                            break;
                        case 'em_preparo':
                            statusClass = 'bg-indigo-100 text-indigo-800';
                            break;
                        case 'saiu_entrega':
                            statusClass = 'bg-purple-100 text-purple-800';
                            break;
                        case 'entregue':
                            statusClass = 'bg-green-100 text-green-800';
                            break;
                        case 'cancelado':
                            statusClass = 'bg-red-100 text-red-800';
                            break;
                        default:
                            statusClass = 'bg-gray-100 text-gray-800';
                    }

                    const modalContent = document.getElementById('modal-impressao-content');
                    if (!modalContent) {
                        console.error('Elemento modal-impressao-content não encontrado');
                        return;
                    }
                    modalContent.innerHTML = `
                        <div class="flex justify-between items-start mb-4">
                            <h2 class="text-xl font-bold">Pedido #${pedidoAtual.id}</h2>
                            <button onclick="fecharModalImpressao()" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Status:</span>
                                <span class="px-2 py-1 rounded-full text-sm ${statusClass}">${formatarStatusJS(pedidoAtual.status)}</span>
                            </div>

                            <div>
                                <h3 class="font-medium mb-2">Cliente</h3>
                                <div class="text-gray-600">
                                    <p>${pedidoAtual.nome || 'N/A'}</p>
                                    <p>${pedidoAtual.telefone || 'N/A'}</p>
                                </div>
                            </div>

                            <div>
                                <h3 class="font-medium mb-2">Endereço</h3>
                                <p class="text-gray-600">${pedidoAtual.endereco || 'N/A'}</p>
                            </div>

                            <div>
                                <h3 class="font-medium mb-2">Forma de Pagamento</h3>
                                <p class="text-gray-600">${pedidoAtual.forma_pagamento || 'N/A'}</p>
                                ${pedidoAtual.troco_para ? `
                                <div class="mt-2">
                                    <p class="text-sm text-gray-600">Troco para: R$ ${Number(pedidoAtual.troco_para).toFixed(2)}</p>
                                    <p class="text-sm text-gray-600">Valor do troco: R$ ${(Number(pedidoAtual.troco_para) - Number(pedidoAtual.total)).toFixed(2)}</p>
                                </div>
                                ` : ''}
                            </div>

                            <div>
                                <h3 class="font-medium mb-2">Itens do Pedido</h3>
                                <div class="space-y-2">
                                    ${data.itens.map(item => `
                                        <div class="flex justify-between items-center">
                                            <span>${item.quantidade}x ${item.nome}</span>
                                            <span>R$ ${Number(item.preco_unitario * item.quantidade).toFixed(2)}</span>
                                        </div>
                                        ${item.complementos ? item.complementos.map(comp => `
                                            <div class="flex justify-between items-center ml-4 text-sm text-gray-600">
                                                <span>+ ${comp.complemento_nome}: ${comp.opcao_nome}</span>
                                                <span>R$ ${Number(comp.opcao_preco * item.quantidade).toFixed(2)}</span>
                                            </div>
                                        `).join('') : ''}
                                        ${item.observacoes ? `
                                            <div class="ml-4 text-sm text-gray-600">
                                                <span>Obs: ${item.observacoes}</span>
                                            </div>
                                        ` : ''}
                                    `).join('')}
                                </div>
                            </div>

                            <!-- Totais -->
                            <div class="mt-4 space-y-2 border-t pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span>R$ ${Number(pedidoAtual.subtotal || 0).toFixed(2)}</span>
                                </div>

                                <!-- Frete -->
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Frete:</span> 
                                    <span>R$ ${Number(pedidoAtual.taxa_entrega || 0).toFixed(2)}</span>
                                </div>

                                ${pedidoAtual.cupom_codigo ? `
                                <div class="flex justify-between items-start">
                                    <div class="text-gray-600">
                                        <div>Cupom ${pedidoAtual.cupom_codigo}</div>
                                        <div class="text-sm">
                                            (${pedidoAtual.cupom_tipo.includes('frete') ? 
                                                'Desconto no Frete ' + pedidoAtual.cupom_valor + '%' : 
                                                'Desconto no Total ' + pedidoAtual.cupom_valor + 
                                                (pedidoAtual.cupom_tipo.includes('porcentagem') ? '%' : ' reais')})
                                        </div>
                                    </div>
                                    <span class="text-green-600">- R$ ${Number(pedidoAtual.desconto_cupom || 0).toFixed(2)}</span>
                                </div>
                                ` : ''}
                                
                                <div class="flex justify-between items-center font-bold text-lg border-t pt-2">
                                    <span>Total:</span>
                                    <span>R$ ${Number(pedidoAtual.total || 0).toFixed(2)}</span>
                                </div>
                            </div>

                            <!-- Observações do Pedido -->
                            ${pedidoAtual.observacoes ? `
                            <div class="mt-4 pt-4 border-t">
                                <h3 class="font-medium mb-2">Observações do Pedido</h3>
                                <p class="text-gray-600">${pedidoAtual.observacoes}</p>
                            </div>
                            ` : ''}

                            <!-- Botões -->
                            <div class="flex justify-end space-x-4 mt-6">
                                <button onclick="fecharModalImpressao()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                                    Fechar
                                </button>
                                <button onclick="imprimirPedido(${pedidoAtual.id})" class="inline-flex items-center px-4 py-2 btn-theme text-white rounded hover:opacity-90">
                                    <i class="fas fa-print mr-2"></i>
                                    Imprimir
                                </button>
                            </div>
                        </div>
                    `;
                })
                .catch(error => {
                    console.error('Erro ao buscar detalhes:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: error.message || 'Erro ao carregar detalhes do pedido'
                    });
                });

            // Mostrar modal
            const modal = document.getElementById('modal-impressao');
            modal.classList.remove('hidden');
        }

        // Função para fechar modal
        function fecharModalImpressao() {
            const modal = document.getElementById('modal-impressao');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Função para imprimir pedido
        function imprimirPedido() {
            if (!pedidoAtual) return;
            let url = `imprimir_pedido.php?id=${pedidoAtual.id}`;
            window.open(url, '_blank', 'width=800,height=600');
        }

        // Função para atualizar o status do pedido
        function atualizarStatus(pedidoId, novoStatus) {
            fetch('atualizar_status_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    pedido_id: pedidoId,
                    status: novoStatus
                })
            })
            .then(response => {
                return response.json();
            })
            .then(data => {
                
                if (data.success) {
                    // Atualiza a linha da tabela com a nova cor de status
                    const row = document.querySelector(`tr[data-pedido-id="${pedidoId}"]`);
                    if (row) {
                        row.setAttribute('data-status', novoStatus);
                    }
                    
                    // Mostra mensagem de sucesso
                    Swal.fire({
                        title: 'Sucesso!',
                        text: 'Status atualizado com sucesso',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Recarrega a página para atualizar a lista
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Erro ao atualizar status');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    title: 'Erro!',
                    text: error.message || 'Ocorreu um erro ao atualizar o status',
                    icon: 'error'
                });
            });
        }

        // Função para atualizar a cor do status
        function atualizarCorStatus(selectElement) {
            const tr = selectElement.closest('tr');
            if (tr) {
                tr.setAttribute('data-status', selectElement.value);
            }
        }

        // Função para formatar status
        function formatarStatusJS(status) {
            switch (status) {
                case 'pendente':
                    return 'Pendente';
                case 'confirmado':
                    return 'Confirmado';
                case 'em_preparo':
                    return 'Em Preparo';
                case 'saiu_entrega':
                    return 'Saiu para Entrega';
                case 'entregue':
                    return 'Entregue';
                case 'cancelado':
                    return 'Cancelado';
                default:
                    return 'Desconhecido';
            }
        }

        // Funções de filtro
        document.addEventListener('DOMContentLoaded', function() {
            // Configuração do datepicker
            $.datepicker.regional['pt-BR'] = {
                closeText: 'Fechar',
                prevText: '←',
                nextText: '→',
                currentText: 'Hoje',
                monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
                monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun',
                'Jul','Ago','Set','Out','Nov','Dez'],
                dayNames: ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'],
                dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'],
                dayNamesMin: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'],
                weekHeader: 'Sm',
                dateFormat: 'dd/mm/yy',
                firstDay: 0,
                isRTL: false,
                showMonthAfterYear: false,
                yearSuffix: ''
            };
            $.datepicker.setDefaults($.datepicker.regional['pt-BR']);
            
            const configData = {
                dateFormat: 'dd/mm/yy',
                maxDate: new Date(),
                showOtherMonths: true,
                selectOtherMonths: true,
                changeMonth: true,
                changeYear: true
            };

            $('#filtro-data-inicial').datepicker(configData);
            $('#filtro-data-final').datepicker(configData);

            // Filtro de status
            const filtroStatus = document.getElementById('filtro-status');
            if (filtroStatus) {
                filtroStatus.addEventListener('change', function() {
                    const status = this.value;
                    const rows = document.querySelectorAll('.pedido-row');
                    
                    rows.forEach(row => {
                        if (status === '' || row.getAttribute('data-status') === status) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });

                // Aplicar filtro inicial se houver
                if (filtroStatus.value) {
                    filtroStatus.dispatchEvent(new Event('change'));
                }
            }
        });

        // Função para toggle filtro de data
        function toggleFiltroData() {
            const filtroData = document.getElementById('date-section');
            if (filtroData) {
                filtroData.classList.toggle('hidden');
            }
        }

        // Função para filtrar por data
        function aplicarFiltroPorData() {
            const dataInicial = document.getElementById('filtro-data-inicial').value;
            const dataFinal = document.getElementById('filtro-data-final').value;

            if (!dataInicial || !dataFinal) {
                alert('Por favor, preencha ambas as datas');
                return;
            }

            // Converter data do formato dd/mm/yyyy para yyyy-mm-dd
            const [diaI, mesI, anoI] = dataInicial.split('/');
            const [diaF, mesF, anoF] = dataFinal.split('/');
            
            const inicio = new Date(anoI, mesI - 1, diaI);
            const fim = new Date(anoF, mesF - 1, diaF);
            fim.setHours(23, 59, 59);

            const rows = document.querySelectorAll('.pedido-row');
            rows.forEach(row => {
                const dataPedido = new Date(row.getAttribute('data-data'));
                if (dataPedido >= inicio && dataPedido <= fim) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Função para limpar filtro de data
        function limparFiltroPorData() {
            document.getElementById('filtro-data-inicial').value = '';
            document.getElementById('filtro-data-final').value = '';
            const rows = document.querySelectorAll('.pedido-row');
            rows.forEach(row => row.style.display = '');
        }
        
        // Som de notificação
        const audio = new Audio('../assets/sounds/notification.mp3');
    </script>
</body>
</html>
