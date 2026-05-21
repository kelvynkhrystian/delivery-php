<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Admin - <?php echo $titulo ?? 'Painel'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <!-- Navbar Brand-->
        <a class="navbar-brand ps-3" href="index.php">Painel Admin</a>
        <!-- Sidebar Toggle-->
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
            <i class="fas fa-bars"></i>
        </button>
        <!-- Navbar-->
        <ul class="navbar-nav ms-auto me-3">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="configuracoes.php">Configurações</a></li>
                    <li><hr class="dropdown-divider" /></li>
                    <li><a class="dropdown-item" href="logout.php">Sair</a></li>
                </ul>
            </li>
        </ul>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Principal</div>
                        <a class="nav-link" href="index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        
                        <div class="sb-sidenav-menu-heading">Gestão</div>
                        <a class="nav-link" href="pedidos.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-shopping-cart"></i></div>
                            Pedidos
                        </a>
                        <a class="nav-link" href="produtos.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
                            Produtos
                        </a>
                        <a class="nav-link" href="categorias.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tags"></i></div>
                            Categorias
                        </a>
                        <a class="nav-link" href="cupons.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-ticket-alt"></i></div>
                            Cupons
                        </a>
                        <a class="nav-link" href="usuarios.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                            Usuários
                        </a>
                        
                        <div class="sb-sidenav-menu-heading">Configurações</div>
                        <a class="nav-link" href="configuracoes.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-cog"></i></div>
                            Configurações
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Logado como:</div>
                    <?php echo $_SESSION['admin']['nome'] ?? 'Administrador'; ?>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
