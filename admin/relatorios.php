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

// Define o período padrão (hoje)
$periodo = $_GET['periodo'] ?? 'hoje';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

// Configura as datas baseado no período selecionado
$hoje = date('Y-m-d');
$data_inicial = $hoje;
$data_final = $hoje;

switch($periodo) {
    case 'semana':
        $data_inicial = date('Y-m-d', strtotime('-7 days'));
        $data_final = $hoje;
        break;
    case 'mes':
        $data_inicial = date('Y-m-01');
        $data_final = date('Y-m-t');
        break;
    case 'ano':
        $data_inicial = date('Y-01-01');
        $data_final = date('Y-12-31');
        break;
    case 'data':
        if ($data_inicio && $data_fim) {
            $data_inicial = $data_inicio;
            $data_final = $data_fim;
        }
        break;
}

// Busca o faturamento do período
$query = "SELECT 
            COUNT(*) as total_pedidos,
            COALESCE(SUM(total), 0) as faturamento_total,
            COUNT(CASE WHEN status = 'entregue' THEN 1 END) as total_entregue,
            COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as total_cancelado
          FROM pedidos 
          WHERE DATE(created_at) BETWEEN ? AND ?";
$stmt = $db->prepare($query);
$stmt->execute([$data_inicial, $data_final]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Admin</title>
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        :root {
            --theme-color: <?php echo $config['cor_tema'] ?? '#8B5CF6'; ?>;
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
                    <span class="text-2xl font-semibold">Relatórios</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="max-w-7xl mx-auto px-4 py-6">
        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex items-center justify-between space-x-2">  
                <a href="?periodo=hoje" 
                   class="flex items-center justify-center px-2.5 py-1.5 md:px-6 md:py-3 rounded-lg md:text-lg md:min-w-[140px] <?php echo $periodo === 'hoje' ? 'bg-[var(--theme-color)] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Hoje
                </a>
                <a href="?periodo=semana" 
                   class="flex items-center justify-center px-2.5 py-1.5 md:px-6 md:py-3 rounded-lg md:text-lg md:min-w-[140px] <?php echo $periodo === 'semana' ? 'bg-[var(--theme-color)] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Semana
                </a>
                <a href="?periodo=mes" 
                   class="flex items-center justify-center px-2.5 py-1.5 md:px-6 md:py-3 rounded-lg md:text-lg md:min-w-[140px] <?php echo $periodo === 'mes' ? 'bg-[var(--theme-color)] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Mês
                </a>
                <a href="?periodo=ano" 
                   class="flex items-center justify-center px-2.5 py-1.5 md:px-6 md:py-3 rounded-lg md:text-lg md:min-w-[140px] <?php echo $periodo === 'ano' ? 'bg-[var(--theme-color)] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    Ano
                </a>
                <button onclick="toggleFiltroData()" 
                        class="flex items-center justify-center px-2.5 py-1.5 md:px-6 md:py-3 rounded-lg w-24 md:w-auto md:text-lg md:min-w-[140px] <?php echo $periodo === 'data' ? 'bg-[var(--theme-color)] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    <i class="far fa-calendar-alt mr-1.5"></i>Data
                </button>
            </div>

            <!-- Filtro por Data (inicialmente oculto) -->
            <div id="filtroData" class="mt-4 pt-4 border-t border-gray-200 <?php echo $periodo === 'data' ? '' : 'hidden'; ?>">
                <form action="" method="GET" class="flex flex-col gap-4">
                    <input type="hidden" name="periodo" value="data">
                    <div class="flex items-center justify-around">
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700">De:</label>
                            <input type="text" name="data_inicio" id="data_inicio" 
                                   value="<?php echo $data_inicio; ?>"
                                   class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 w-32 py-1.5 text-sm">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700">Até:</label>
                            <input type="text" name="data_fim" id="data_fim" 
                                   value="<?php echo $data_fim; ?>"
                                   class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 w-32 py-1.5 text-sm">
                        </div>
                    </div>
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-1.5 bg-[var(--theme-color)] text-white rounded-lg hover:brightness-90">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                </form>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-2 gap-4 mb-8">
            <!-- Total de Pedidos -->
            <div class="bg-white p-4 rounded-lg shadow-md flex flex-col h-32">
                <p class="text-base text-gray-500">Total de Pedidos</p>
                <div class="flex items-center justify-between flex-grow">
                    <p class="text-2xl font-semibold"><?php echo $result['total_pedidos']; ?></p>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-shopping-bag text-xl text-blue-500"></i>
                    </div>
                </div>
            </div>

            <!-- Total Faturado -->
            <div class="bg-white p-4 rounded-lg shadow-md flex flex-col h-32">
                <p class="text-base text-gray-500">Total Faturado</p>
                <div class="flex items-center justify-between flex-grow">
                    <p class="text-2xl font-semibold">R$ <?php echo number_format($result['faturamento_total'], 2, ',', '.'); ?></p>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-dollar-sign text-xl text-green-500"></i>
                    </div>
                </div>
            </div>

            <!-- Total Entregue -->
            <div class="bg-white p-4 rounded-lg shadow-md flex flex-col h-32">
                <p class="text-base text-gray-500">Total Entregue</p>
                <div class="flex items-center justify-between flex-grow">
                    <p class="text-2xl font-semibold"><?php echo $result['total_entregue']; ?></p>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-check-circle text-xl text-purple-500"></i>
                    </div>
                </div>
            </div>

            <!-- Total Cancelado -->
            <div class="bg-white p-4 rounded-lg shadow-md flex flex-col h-32">
                <p class="text-base text-gray-500">Total Cancelado</p>
                <div class="flex items-center justify-between flex-grow">
                    <p class="text-2xl font-semibold"><?php echo $result['total_cancelado']; ?></p>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-times-circle text-xl text-red-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Análise de Pedidos</h2>
                <div class="flex items-center space-x-4">
                    <select id="tipoGrafico" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="pedidos">Quantidade de Pedidos</option>
                        <option value="faturamento">Faturamento</option>
                    </select>
                </div>
            </div>
            <div id="grafico" class="w-full h-[400px]"></div>
        </div>
    </div>

    <?php include 'includes/menu.php'; ?>

    <script>
        // Inicializa o seletor de data
        flatpickr("#data_inicio, #data_fim", {
            locale: "pt",
            dateFormat: "d/m/Y",
            defaultDate: new Date(),
            maxDate: "today",
            disableMobile: true
        });

        // Função para mostrar/ocultar o filtro de data
        function toggleFiltroData() {
            const filtroData = document.getElementById('filtroData');
            filtroData.classList.toggle('hidden');
        }

        // Dados para o gráfico
        <?php
        // Busca dados para o gráfico baseado no período
        $query_grafico = "";
        $labels = [];
        $series_pedidos = [];
        $series_faturamento = [];

        switch($periodo) {
            case 'hoje':
                $query_grafico = "SELECT 
                    HOUR(created_at) as label,
                    COUNT(*) as total_pedidos,
                    COALESCE(SUM(total), 0) as faturamento
                FROM pedidos 
                WHERE DATE(created_at) = CURDATE()
                GROUP BY HOUR(created_at)
                ORDER BY HOUR(created_at)";
                break;
            case 'semana':
                $query_grafico = "SELECT 
                    DATE(created_at) as label,
                    DAYNAME(created_at) as dia_semana,
                    COUNT(*) as total_pedidos,
                    COALESCE(SUM(total), 0) as faturamento
                FROM pedidos 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                  AND created_at <= DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
                GROUP BY DATE(created_at), DAYNAME(created_at)
                ORDER BY DATE(created_at)";
                break;
            case 'mes':
                $query_grafico = "SELECT 
                    DAY(created_at) as label,
                    COUNT(*) as total_pedidos,
                    COALESCE(SUM(total), 0) as faturamento
                FROM pedidos 
                WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
                AND YEAR(created_at) = YEAR(CURRENT_DATE())
                GROUP BY DAY(created_at)
                ORDER BY label";
                break;
            case 'ano':
                $query_grafico = "SELECT 
                    MONTHNAME(created_at) as mes_nome,
                    MONTH(created_at) as label,
                    COUNT(*) as total_pedidos,
                    COALESCE(SUM(total), 0) as faturamento
                FROM pedidos 
                WHERE YEAR(created_at) = YEAR(CURRENT_DATE())
                GROUP BY MONTH(created_at), MONTHNAME(created_at)
                ORDER BY MONTH(created_at)";
                break;
            case 'data':
                if ($data_inicio && $data_fim) {
                    $query_grafico = "SELECT 
                        DATE(created_at) as label,
                        COUNT(*) as total_pedidos,
                        COALESCE(SUM(total), 0) as faturamento
                    FROM pedidos 
                    WHERE DATE(created_at) BETWEEN ? AND ?
                    GROUP BY DATE(created_at)
                    ORDER BY DATE(created_at)";
                }
                break;
        }

        if ($query_grafico) {
            $stmt = $db->prepare($query_grafico);
            if ($periodo === 'data') {
                $stmt->execute([$data_inicial, $data_final]);
            } else {
                $stmt->execute();
            }
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $label = $row['label'];
                if ($periodo === 'hoje') {
                    // Formato: 0h, 1h, 2h, ..., 23h
                    $label = $label . 'h';
                } else if ($periodo === 'semana') {
                    // Formato: Segunda, Terça, ..., Domingo
                    $dias = [
                        'Monday' => 'Segunda',
                        'Tuesday' => 'Terça',
                        'Wednesday' => 'Quarta',
                        'Thursday' => 'Quinta',
                        'Friday' => 'Sexta',
                        'Saturday' => 'Sábado',
                        'Sunday' => 'Domingo'
                    ];
                    $label = $dias[$row['dia_semana']];
                } else if ($periodo === 'mes') {
                    // Formato: Dia 1, Dia 2, ..., Dia 31
                    $label = "Dia " . $label;
                } else if ($periodo === 'ano') {
                    // Formato: Janeiro, Fevereiro, ..., Dezembro
                    $meses = [
                        1 => 'Janeiro',
                        2 => 'Fevereiro',
                        3 => 'Março',
                        4 => 'Abril',
                        5 => 'Maio',
                        6 => 'Junho',
                        7 => 'Julho',
                        8 => 'Agosto',
                        9 => 'Setembro',
                        10 => 'Outubro',
                        11 => 'Novembro',
                        12 => 'Dezembro'
                    ];
                    $label = $meses[(int)$label];
                }
                
                $labels[] = $label;
                $series_pedidos[] = (int)$row['total_pedidos'];
                $series_faturamento[] = (float)$row['faturamento'];
            }
        }
        ?>

        // Configuração do gráfico
        const options = {
            series: [{
                name: 'Pedidos',
                data: <?php echo json_encode($series_pedidos); ?>
            }],
            chart: {
                type: 'area',
                height: 400,
                toolbar: {
                    show: true,
                    tools: {
                        download: true
                    },
                    autoSelected: 'download',
                    icons: {
                        menu: '<i class="fas fa-download"></i>'
                    }
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            xaxis: {
                categories: <?php echo json_encode($labels); ?>,
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return Math.round(val);
                    }
                }
            },
            colors: ['#3B82F6'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 90, 100]
                }
            },
            grid: {
                borderColor: '#f1f1f1',
                row: {
                    colors: ['transparent', 'transparent'],
                    opacity: 0.5
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val;
                    }
                }
            }
        };

        const chart = new ApexCharts(document.querySelector("#grafico"), options);
        chart.render();

        // Alternar entre pedidos e faturamento
        document.getElementById('tipoGrafico').addEventListener('change', function(e) {
            const tipo = e.target.value;
            const dados = tipo === 'pedidos' 
                ? <?php echo json_encode($series_pedidos); ?>
                : <?php echo json_encode($series_faturamento); ?>;
            
            const novasOpcoes = {
                series: [{
                    name: tipo === 'pedidos' ? 'Pedidos' : 'Faturamento',
                    data: dados
                }],
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            if (tipo === 'faturamento') {
                                return 'R$ ' + val.toFixed(2);
                            }
                            return Math.round(val);
                        }
                    }
                },
                colors: [tipo === 'pedidos' ? '#3B82F6' : '#10B981']
            };
            
            chart.updateOptions(novasOpcoes);
        });
    </script>
</body>
</html>
