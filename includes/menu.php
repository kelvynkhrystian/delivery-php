<?php
// Pega a página atual para destacar o item correto no menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Menu de Navegação -->
<nav class="bg-white shadow-lg fixed bottom-0 w-full z-30">
    <div class="max-w-7xl mx-auto px-2">
        <div class="flex justify-around items-center py-3">
            <a href="index.php" class="flex flex-col items-center <?php echo $current_page === 'index.php' ? 'text-theme' : 'text-gray-600'; ?>">
                <i class="fas fa-home text-xl"></i>
                <span class="text-sm">Início</span>
            </a>
            <a href="pedidos.php" class="flex flex-col items-center <?php echo $current_page === 'pedidos.php' ? 'text-theme' : 'text-gray-600'; ?>">
                <i class="fas fa-clipboard-list text-xl"></i>
                <span class="text-sm">Pedidos</span>
            </a>
            <a href="carrinho.php" class="flex flex-col items-center <?php echo $current_page === 'carrinho.php' ? 'text-theme' : 'text-gray-600'; ?> relative">
                <i class="fas fa-shopping-cart text-xl"></i>
                <span class="text-sm">Carrinho</span>
                <?php if (isset($_SESSION['carrinho']) && count($_SESSION['carrinho']) > 0): ?>
                <span id="carrinho-contador" class="absolute -top-1 -right-1 theme-primary text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                    <?php echo count($_SESSION['carrinho']); ?>
                </span>
                <?php endif; ?>
            </a>
            <a href="conta.php" class="flex flex-col items-center <?php echo $current_page === 'conta.php' ? 'text-theme' : 'text-gray-600'; ?>">
                <i class="fas fa-user text-xl"></i>
                <span class="text-sm">Minha Conta</span>
            </a>
        </div>
    </div>
</nav>
