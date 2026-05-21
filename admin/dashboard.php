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

// Busca pedidos pendentes
$query = "SELECT COUNT(*) as total FROM pedidos WHERE status = 'pendente'";
$stmt = $db->query($query);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$pedidos_pendentes = $result['total'];

// Busca faturamento do dia
$hoje = date('Y-m-d');
$query = "SELECT COALESCE(SUM(total), 0) as total FROM pedidos 
          WHERE DATE(created_at) = ? AND status != 'cancelado'";
$stmt = $db->prepare($query);
$stmt->execute([$hoje]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$faturamento_hoje = $result['total'];

// Busca total de pedidos do dia
$query = "SELECT COUNT(*) as total FROM pedidos WHERE DATE(created_at) = ?";
$stmt = $db->prepare($query);
$stmt->execute([$hoje]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$pedidos_hoje = $result['total'];

// Busca faturamento do mês
$primeiro_dia_mes = date('Y-m-01');
$ultimo_dia_mes = date('Y-m-t');
$query = "SELECT COALESCE(SUM(total), 0) as total FROM pedidos 
          WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelado'";
$stmt = $db->prepare($query);
$stmt->execute([$primeiro_dia_mes, $ultimo_dia_mes]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$faturamento_mes = $result['total'];

// Busca últimos 5 pedidos
$query = "SELECT p.* 
          FROM pedidos p 
          ORDER BY p.created_at DESC 
          LIMIT 5";
$stmt = $db->query($query);
$ultimos_pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Admin</title>
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-2xl font-semibold">Painel</span>
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
                <!-- Cards de Estatísticas -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                    <!-- Pedidos Pendentes -->
                    <div class="bg-white p-4 rounded-lg shadow-md flex flex-col h-40">
                        <p class="text-sm text-gray-500">Pedidos Pendentes</p>
                        <div class="flex items-center justify-between flex-grow">
                            <p class="text-2xl font-semibold"><?php echo $pedidos_pendentes; ?></p>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-clock text-xl text-yellow-500"></i>
                            </div>
                        </div>
                        <div class="text-left">
                            <a href="pedidos.php?status=pendente" class="text-yellow-500 hover:text-yellow-600 text-sm">
                                Ver pedidos <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Pedidos Hoje -->
                    <div class="bg-white p-4 rounded-lg shadow-md flex flex-col h-40">
                        <p class="text-sm text-gray-500">Pedidos Hoje</p>
                        <div class="flex items-center justify-between flex-grow">
                            <p class="text-2xl font-semibold"><?php echo $pedidos_hoje; ?></p>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-shopping-bag text-xl text-blue-500"></i>
                            </div>
                        </div>
                        <div class="text-left">
                            <a href="pedidos.php" class="text-blue-500 hover:text-blue-600 text-sm">
                                Ver todos <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Faturamento Hoje -->
                    <div class="bg-white p-4 rounded-lg shadow-md flex flex-col h-40">
                        <p class="text-sm text-gray-500">Faturamento Hoje</p>
                        <div class="flex items-center justify-between flex-grow">
                            <p class="text-2xl font-semibold">R$ <?php echo number_format($faturamento_hoje, 2, ',', '.'); ?></p>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-dollar-sign text-xl text-green-500"></i>
                            </div>
                        </div>
                        <div class="text-left">
                            <a href="relatorios.php" class="text-green-500 hover:text-green-600 text-sm">
                                Ver relatório <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Faturamento Mensal -->
                    <div class="bg-white p-4 rounded-lg shadow-md flex flex-col h-40">
                        <p class="text-sm text-gray-500">Faturamento Mensal</p>
                        <div class="flex items-center justify-between flex-grow">
                            <p class="text-2xl font-semibold">R$ <?php echo number_format($faturamento_mes, 2, ',', '.'); ?></p>
                            <div class="bg-purple-100 p-3 rounded-full">
                                <i class="fas fa-chart-line text-xl text-purple-500"></i>
                            </div>
                        </div>
                        <div class="text-left">
                            <a href="relatorios.php" class="text-purple-500 hover:text-purple-600 text-sm">
                                Ver relatório <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Últimos Pedidos -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 border-b">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-semibold">Últimos Pedidos</h2>
                            <a href="pedidos.php" class="text-blue-500 hover:text-blue-600">
                                Ver todos <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pedido</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($ultimos_pedidos as $pedido): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900">#<?php echo $pedido['id']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
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
                                                case 'em_entrega':
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
                                            <?php echo ucfirst(str_replace('_', ' ', $pedido['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="pedidos.php?id=<?php echo $pedido['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                            Ver detalhes <i class="fas fa-eye ml-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<script>
    function toggleMenu() {
        const menu = document.querySelector('.menu-lateral');
        menu.classList.toggle('hidden');
    }
</script>
