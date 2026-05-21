<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Carrega as configurações se ainda não foram carregadas
if (!isset($config)) {
    require_once 'includes/load_config.php';
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $config = carregarConfiguracoes($db);
}

$corTema = $config['cor_tema'] ?? '#8B5CF6';
?>
<!-- Menu Hamburguer -->
<div id="menuOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity"></div>

<button id="menuButton" class="fixed top-4 right-4 z-50 bg-white p-2 rounded-lg shadow-lg hover:bg-gray-100 focus:outline-none">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path id="menuIcon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        <path id="closeIcon" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
    </svg>
</button>

<style>
    .menu-active {
        background-color: <?php echo $corTema; ?>;
        color: white;
    }
</style>

<div id="sideMenu" class="fixed top-0 right-0 w-64 h-full bg-white shadow-lg transform translate-x-full transition-transform duration-300 ease-in-out z-50">
    <div class="p-6">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-xl font-bold text-gray-800">Menu Admin</h2>
        </div>

        <nav class="space-y-4">
            <a href="dashboard.php" class="flex items-center py-2 px-4 rounded-lg transition-colors <?php echo $current_page === 'dashboard.php' ? 'menu-active' : 'text-gray-600 hover:bg-gray-50'; ?>">
                <i class="fas fa-chart-line w-5 h-5 mr-3"></i>
                <span>Painel</span>
            </a>

            <a href="pedidos.php" class="flex items-center py-2 px-4 rounded-lg transition-colors <?php echo $current_page === 'pedidos.php' ? 'menu-active' : 'text-gray-600 hover:bg-gray-50'; ?>">
                <i class="fas fa-shopping-bag w-5 h-5 mr-3"></i>
                <span>Pedidos</span>
            </a>

            <a href="produtos.php" class="flex items-center py-2 px-4 rounded-lg transition-colors <?php echo $current_page === 'produtos.php' ? 'menu-active' : 'text-gray-600 hover:bg-gray-50'; ?>">
                <i class="fas fa-box w-5 h-5 mr-3"></i>
                <span>Produtos</span>
            </a>

            <a href="categorias.php" class="flex items-center py-2 px-4 rounded-lg transition-colors <?php echo $current_page === 'categorias.php' ? 'menu-active' : 'text-gray-600 hover:bg-gray-50'; ?>">
                <i class="fas fa-tags w-5 h-5 mr-3"></i>
                <span>Categorias</span>
            </a>

            <a href="cupons.php" class="flex items-center py-2 px-4 rounded-lg transition-colors <?php echo $current_page === 'cupons.php' ? 'menu-active' : 'text-gray-600 hover:bg-gray-50'; ?>">
                <i class="fas fa-ticket-alt w-5 h-5 mr-3"></i>
                <span>Cupons</span>
            </a>

            <a href="relatorios.php" class="flex items-center py-2 px-4 rounded-lg transition-colors <?php echo $current_page === 'relatorios.php' ? 'menu-active' : 'text-gray-600 hover:bg-gray-50'; ?>">
                <i class="fas fa-chart-bar w-5 h-5 mr-3"></i>
                <span>Relatórios</span>
            </a>

            <a href="configuracoes.php" class="flex items-center py-2 px-4 rounded-lg transition-colors <?php echo $current_page === 'configuracoes.php' ? 'menu-active' : 'text-gray-600 hover:bg-gray-50'; ?>">
                <i class="fas fa-cog w-5 h-5 mr-3"></i>
                <span>Configurações</span>
            </a>

            <div class="pt-4 mt-4 border-t border-gray-200">
                <a href="logout.php" class="flex items-center py-2 px-4 rounded-lg text-red-600 hover:bg-red-50 transition-colors">
                    <i class="fas fa-sign-out-alt w-5 h-5 mr-3"></i>
                    <span>Sair</span>
                </a>
            </div>
        </nav>
    </div>
</div>

<script>
const menuButton = document.getElementById('menuButton');
const menuIcon = document.getElementById('menuIcon');
const closeIcon = document.getElementById('closeIcon');
const sideMenu = document.getElementById('sideMenu');
const menuOverlay = document.getElementById('menuOverlay');
let isMenuOpen = false;

function toggleMenu() {
    isMenuOpen = !isMenuOpen;
    
    // Atualiza ícones
    menuIcon.classList.toggle('hidden');
    closeIcon.classList.toggle('hidden');
    
    // Atualiza menu e overlay
    if (isMenuOpen) {
        sideMenu.classList.remove('translate-x-full');
        menuOverlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } else {
        sideMenu.classList.add('translate-x-full');
        menuOverlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

menuButton.addEventListener('click', toggleMenu);
menuOverlay.addEventListener('click', toggleMenu);

// Fecha o menu ao pressionar ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isMenuOpen) {
        toggleMenu();
    }
});
</script>
