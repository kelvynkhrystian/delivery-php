<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Busca os pedidos do usuário apenas se estiver logado
if (isset($_SESSION['usuario'])) {
    $query = "SELECT p.*, 
                COUNT(ip.id) as total_itens,
                GROUP_CONCAT(CONCAT(pr.nome, ' (', ip.quantidade, ')') SEPARATOR ', ') as itens,
                fp.nome as forma_pagamento
              FROM pedidos p
              LEFT JOIN itens_pedido ip ON p.id = ip.pedido_id
              LEFT JOIN produtos pr ON ip.produto_id = pr.id
              LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
              WHERE p.usuario_id = :usuario_id
              GROUP BY p.id
              ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":usuario_id", $_SESSION['usuario']['id']);
    $stmt->execute();
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStatusColor($status) {
    switch ($status) {
        case 'pendente':
            return 'bg-yellow-100 text-yellow-800';
        case 'confirmado':
            return 'bg-blue-100 text-blue-800';
        case 'em_preparo':
            return 'bg-purple-100 text-purple-800';
        case 'saiu_entrega':
            return 'bg-indigo-100 text-indigo-800';
        case 'entregue':
            return 'bg-green-100 text-green-800';
        case 'cancelado':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function formatStatus($status) {
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
            return $status;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos - Delivery</title>
    <?php include 'includes/header.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 pb-24">
        <h1 class="text-2xl font-bold mb-6">Meus Pedidos</h1>

        <?php if (!isset($_SESSION['usuario'])): ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <p class="text-gray-600 mb-4">Você precisa fazer login para ver seus pedidos</p>
                <button onclick="fazerLogin()" class="theme-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition">
                    Fazer Login
                </button>
            </div>
        <?php else: ?>
            <?php if (empty($pedidos)): ?>
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <p class="text-gray-600">Você ainda não fez nenhum pedido</p>
                    <a href="index.php" class="inline-block mt-4 bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
                        Fazer Pedido
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($pedidos as $pedido): ?>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h2 class="text-lg font-semibold mb-2">Pedido #<?php echo $pedido['id']; ?></h2>
                                    <p class="text-gray-600 text-sm">
                                        Realizado em: <?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?>
                                    </p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo getStatusColor($pedido['status']); ?>">
                                    <?php echo formatStatus($pedido['status']); ?>
                                </span>
                            </div>
                            
                            <div class="mb-4">
                                <p class="text-gray-600"><?php echo $pedido['itens']; ?></p>
                            </div>
                            
                            <div class="mb-4">
                                <span class="font-semibold">Forma de Pagamento:</span>
                                <span class="ml-2"><?php echo $pedido['forma_pagamento']; ?></span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="font-bold text-lg">
                                    R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?>
                                </span>
                                <a href="pedido.php?id=<?php echo $pedido['id']; ?>" 
                                   class="theme-primary text-white px-4 py-2 rounded hover:opacity-90">
                                    Ver Detalhes
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include 'includes/menu.php'; ?>

    <script>
    function fazerLogin() {
        Swal.fire({
            title: 'Atenção!',
            text: 'Você precisa fazer login para ver seus pedidos',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Fazer Login',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'conta.php';
            }
        });
    }
    </script>
    <script>
        const userId = <?php echo isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 'null' ?>;
        const currentOrderId = <?php echo isset($pedido_atual) ? $pedido_atual['id'] : 'null' ?>;
    </script>
    <script src="js/notifications.js"></script>
</body>
</html>
