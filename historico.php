<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Se o usuário não estiver logado, não mostra o histórico
$pedidos = [];
if (isset($_SESSION['usuario'])) {
    $query = "SELECT p.*, COUNT(pi.id) as total_items 
              FROM pedidos p 
              LEFT JOIN pedido_items pi ON p.id = pi.pedido_id 
              WHERE p.usuario_id = ? 
              GROUP BY p.id 
              ORDER BY p.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario']['id']]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Pedidos - Delivery App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Menu de Navegação -->
    <nav class="bg-white shadow-lg fixed bottom-0 w-full md:top-0 md:bottom-auto z-30">
        <div class="container mx-auto">
            <div class="flex justify-around items-center py-3">
                <a href="index.php" class="flex flex-col items-center text-gray-600">
                    <i class="fas fa-home text-xl"></i>
                    <span class="text-sm">Início</span>
                </a>
                <a href="historico.php" class="flex flex-col items-center text-blue-600">
                    <i class="fas fa-history text-xl"></i>
                    <span class="text-sm">Histórico</span>
                </a>
                <a href="carrinho.php" class="flex flex-col items-center text-gray-600 relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="text-sm">Carrinho</span>
                    <span id="carrinho-contador" 
                          class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center hidden">
                        0
                    </span>
                </a>
                <a href="conta.php" class="flex flex-col items-center text-gray-600">
                    <i class="fas fa-user text-xl"></i>
                    <span class="text-sm">Minha Conta</span>
                </a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 pt-20 pb-32">
        <h1 class="text-2xl font-bold mb-6">Histórico de Pedidos</h1>

        <?php if (!isset($_SESSION['usuario'])): ?>
            <!-- Mensagem para usuários não logados -->
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-user-lock text-gray-400 text-4xl mb-4"></i>
                <h2 class="text-xl font-semibold mb-2">Faça login para ver seu histórico</h2>
                <p class="text-gray-600 mb-4">Para acessar seu histórico de pedidos, você precisa estar logado.</p>
                <a href="conta.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                    Fazer Login
                </a>
            </div>
        <?php elseif (empty($pedidos)): ?>
            <!-- Mensagem para usuários sem pedidos -->
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-shopping-bag text-gray-400 text-4xl mb-4"></i>
                <h2 class="text-xl font-semibold mb-2">Nenhum pedido encontrado</h2>
                <p class="text-gray-600 mb-4">Você ainda não fez nenhum pedido.</p>
                <a href="index.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                    Fazer Pedido
                </a>
            </div>
        <?php else: ?>
            <!-- Lista de Pedidos -->
            <div class="space-y-4">
                <?php foreach ($pedidos as $pedido): ?>
                    <a href="pedido.php?id=<?php echo $pedido['id']; ?>" 
                       class="block bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-semibold">Pedido #<?php echo $pedido['id']; ?></h3>
                                <p class="text-sm text-gray-600">
                                    <?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm 
                                <?php
                                    switch ($pedido['status']) {
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
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $pedido['status'])); ?>
                            </span>
                        </div>
                        <div class="border-t pt-4">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span><?php echo $pedido['total_items']; ?> items</span>
                                <span class="font-semibold">
                                    Total: R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="assets/js/main.js"></script>
    <script>
        // Inicializar o carrinho quando a página carregar
        document.addEventListener('DOMContentLoaded', () => {
            carrinho.carregarCarrinho();
        });
    </script>
</body>
</html>
