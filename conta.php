<?php
session_start();
require_once 'config/database.php';

// Busca a chave da API do Google Maps das configurações
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT maps_api_key FROM configuracoes LIMIT 1");
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC);
$maps_api_key = $config['maps_api_key'];

// Processar ação de tornar endereço principal
if (isset($_POST['tornar_principal']) && isset($_POST['endereco_id'])) {
    try {
        $db->beginTransaction();
        
        // Primeiro, remove o status principal de todos os endereços do usuário
        $stmt = $db->prepare("UPDATE enderecos_usuario SET principal = 0 WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['usuario']['id']]);
        
        // Depois, define o endereço selecionado como principal
        $stmt = $db->prepare("UPDATE enderecos_usuario SET principal = 1 WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$_POST['endereco_id'], $_SESSION['usuario']['id']]);
        
        $db->commit();
        $_SESSION['mensagem'] = 'Endereço definido como principal com sucesso!';
        $_SESSION['mensagem_tipo'] = 'sucesso';
        header('Location: conta.php');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensagem'] = 'Erro ao definir endereço como principal.';
        $_SESSION['mensagem_tipo'] = 'erro';
    }
}

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $senha = $_POST['senha'];
        $manter_conectado = isset($_POST['manter_conectado']);

        $query = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Verifica se precisa trocar a senha
            $stmt = $db->prepare("SELECT senha_temporaria FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario['id']]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($info && $info['senha_temporaria'] == 1) {
                $_SESSION['usuario'] = $usuario;
                header('Location: trocar-senha.php');
                exit;
            }

            $_SESSION['usuario'] = $usuario;
            
            // Se marcou "manter conectado"
            if ($manter_conectado) {
                $remember_token = bin2hex(random_bytes(32));
                $query = "UPDATE usuarios SET remember_token = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$remember_token, $usuario['id']]);
                
                // Cookie expira em 30 dias
                setcookie('remember_token', $remember_token, time() + (86400 * 30), '/');
            }
            
            header('Location: index.php');
            exit;
        } else {
            $mensagem = 'Email ou senha incorretos. Por favor, verifique suas credenciais ou crie uma nova conta.';
        }
    } elseif (isset($_POST['nome']) && isset($_POST['email']) && !isset($_POST['senha_atual'])) {
        // Processamento do cadastro
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $telefone = $_POST['telefone'];
        $senha = $_POST['senha'];
        $confirmar_senha = $_POST['confirmar_senha'];

        if ($senha !== $confirmar_senha) {
            $mensagem = 'As senhas não coincidem. Por favor, tente novamente.';
        } else {
            // Verifica se o email já existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $mensagem = 'Este email já está cadastrado. Por favor, use outro email ou faça login.';
            } else {
                // Insere o novo usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO usuarios (nome, email, telefone, senha) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$nome, $email, $telefone, $senha_hash])) {
                    $mensagem = 'Cadastro realizado com sucesso! Você já pode fazer login.';
                } else {
                    $mensagem = 'Erro ao realizar o cadastro. Por favor, tente novamente.';
                }
            }
        }
    } elseif (isset($_POST['senha_atual']) && isset($_POST['senha_nova']) && isset($_POST['confirmar_senha_nova'])) {
        // Processamento da alteração de senha
        $senha_atual = $_POST['senha_atual'];
        $senha_nova = $_POST['senha_nova'];
        $confirmar_senha_nova = $_POST['confirmar_senha_nova'];
        
        // Verifica se a senha atual está correta
        $stmt = $db->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario']['id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario || !password_verify($senha_atual, $usuario['senha'])) {
            $mensagem = 'Senha atual incorreta. Por favor, verifique e tente novamente.';
        } elseif ($senha_nova !== $confirmar_senha_nova) {
            $mensagem = 'As novas senhas não coincidem. Por favor, tente novamente.';
        } elseif (strlen($senha_nova) < 6) {
            $mensagem = 'A nova senha deve ter pelo menos 6 caracteres.';
        } else {
            // Atualiza a senha
            $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            if ($stmt->execute([$senha_hash, $_SESSION['usuario']['id']])) {
                $mensagem = 'Senha alterada com sucesso!';
            } else {
                $mensagem = 'Erro ao alterar a senha. Por favor, tente novamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - Delivery App</title>
    <?php include 'includes/header.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <style>
        .form-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-in-out;
        }
        .form-container.active {
            max-height: 1000px;
        }
        .toggle-button {
            transition: all 0.3s ease;
        }
        .toggle-button.active {
            background-color: var(--theme-color) !important;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php if (!empty($mensagem)): ?>
    <script>
        function showMessage() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Atenção',
                    text: '<?php echo $mensagem; ?>',
                    icon: 'warning',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6'
                });
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', showMessage);
        } else {
            showMessage();
        }
    </script>
    <?php endif; ?>
    <div class="container mx-auto px-4 py-8 pb-24 min-h-screen flex items-center justify-center">
        <?php if (isset($_SESSION['usuario'])): ?>
            <!-- Usuário Logado -->
            <div class="container mx-auto px-0 pt-6 pb-[150px]">
                <div class="max-w-4xl mx-auto space-y-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800">Minha Conta</h1>
                            <p class="text-gray-500 mt-1">Gerencie suas informações pessoais e endereços</p>
                        </div>
                        <div class="h-12 w-12 theme-primary rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white text-xl"></i>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <?php if (isset($_SESSION['mensagem'])): ?>
                        <script>
                            function showMessage() {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        title: 'Atenção',
                                        text: '<?php echo $_SESSION['mensagem']; ?>',
                                        icon: '<?php echo $_SESSION['mensagem_tipo'] === 'erro' ? 'error' : 'success'; ?>',
                                        confirmButtonText: 'OK',
                                        confirmButtonColor: 'var(--theme-color)'
                                    });
                                }
                            }
                            
                            if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', showMessage);
                            } else {
                                showMessage();
                            }
                        </script>
                        <?php unset($_SESSION['mensagem']); unset($_SESSION['mensagem_tipo']); endif; ?>

                        <div class="flex flex-col space-y-6">
                            <!-- Seção de Informações Pessoais -->
                            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100 w-full">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-theme/10 rounded-lg">
                                            <i class="fas fa-user-edit text-theme text-xl"></i>
                                        </div>
                                        <h2 class="text-xl font-semibold text-gray-800">Informações</h2>
                                    </div>
                                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors flex items-center gap-2">
                                        <i class="fas fa-sign-out-alt"></i>
                                        Sair
                                    </a>
                                </div>
                                <form method="POST" class="space-y-4" onsubmit="return validarSenhas(this)">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-[25px]">
                                        <div>
                                            <input type="text" name="nome" value="<?php echo htmlspecialchars($_SESSION['usuario']['nome']); ?>" 
                                                   class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                                   required placeholder="Digite seu nome completo">
                                        </div>
                                        <div>
                                            <input type="tel" name="telefone" value="<?php echo htmlspecialchars($_SESSION['usuario']['telefone'] ?? ''); ?>" 
                                                   class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                                   required placeholder="Digite seu telefone">
                                        </div>
                                    </div>
                                    <div>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['usuario']['email']); ?>" 
                                               class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                               required placeholder="Digite seu email">
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <div class="relative">
                                                <input type="password" name="senha_atual" id="senha_atual"
                                                       class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                                       placeholder="Digite sua senha atual">
                                                <button type="button" onclick="togglePassword('senha_atual')" 
                                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <i class="fas fa-eye text-gray-500"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <div class="relative">
                                                <input type="password" name="senha_nova" id="senha_nova"
                                                       class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                                       minlength="6"
                                                       placeholder="Digite a nova senha">
                                                <button type="button" onclick="togglePassword('senha_nova')" 
                                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <i class="fas fa-eye text-gray-500"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="relative">
                                                <input type="password" name="confirmar_senha_nova" id="confirmar_senha_nova"
                                                       class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                                       minlength="6"
                                                       placeholder="Confirme a nova senha">
                                                <button type="button" onclick="togglePassword('confirmar_senha_nova')" 
                                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <i class="fas fa-eye text-gray-500"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="height: 25px;"></div>
                                    <div>
                                        <button type="submit" name="atualizar_info" 
                                                class="theme-primary text-white px-4 py-2 rounded-lg hover:opacity-90 transition-colors flex items-center gap-2">
                                            <i class="fas fa-save"></i>
                                            Salvar Alterações
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div style="height: 25px;"></div>

                            <!-- Seção de Endereços -->
                            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100 w-full">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-theme/10 rounded-lg">
                                            <i class="fas fa-map-marker-alt text-theme text-xl"></i>
                                        </div>
                                        <h2 class="text-2xl font-semibold text-gray-700">Endereços</h2>
                                    </div>
                                    <button onclick="novoEndereco()" 
                                            class="theme-primary text-white px-4 py-2 rounded-lg hover:opacity-90 transition-colors flex items-center gap-2">
                                        <i class="fas fa-plus"></i>
                                        Adicionar
                                    </button>
                                </div>

                                <!-- Lista de Endereços -->
                                <div class="space-y-4">
                                    <?php
                                    // Buscar endereços do usuário
                                    $database = new Database();
                                    $db = $database->getConnection();
                                    $stmt = $db->prepare("SELECT * FROM enderecos_usuario WHERE usuario_id = ? ORDER BY principal DESC");
                                    $stmt->execute([$_SESSION['usuario']['id']]);
                                    $enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($enderecos as $endereco): ?>
                                    <div class="bg-white rounded-lg shadow p-4 flex items-center justify-between">
                                        <div class="flex-1">
                                            <p class="text-gray-800">
                                                <?= htmlspecialchars($endereco['logradouro']) ?>, 
                                                <?= htmlspecialchars($endereco['numero']) ?>
                                                <?= $endereco['complemento'] ? ' - ' . htmlspecialchars($endereco['complemento']) : '' ?>
                                            </p>
                                            <p class="text-gray-600 text-sm">
                                                <?= htmlspecialchars($endereco['bairro']) ?>, 
                                                <?= htmlspecialchars($endereco['cidade']) ?> - 
                                                <?= htmlspecialchars($endereco['estado']) ?>
                                            </p>
                                            <p class="text-gray-500 text-sm">
                                                CEP: <?= htmlspecialchars($endereco['cep']) ?>
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <?php if (!$endereco['principal']): ?>
                                                <button onclick="tornarPrincipal(<?= $endereco['id'] ?>)" 
                                                        class="text-gray-400 hover:text-theme transition-colors">
                                                    <i class="far fa-star text-xl"></i>
                                                </button>
                                            <?php else: ?>
                                                <i class="fas fa-star text-xl text-theme"></i>
                                            <?php endif; ?>
                                            <button onclick="editarEndereco(<?= $endereco['id'] ?>)" 
                                                    class="text-gray-400 hover:text-theme transition-colors">
                                                <i class="fas fa-edit text-xl"></i>
                                            </button>
                                            <button onclick="excluirEndereco(<?= $endereco['id'] ?>)"
                                                    class="text-gray-400 hover:text-red-500 transition-colors">
                                                <i class="fas fa-trash text-xl"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if (empty($enderecos)): ?>
                                <div class="text-center py-8">
                                    <div class="text-gray-400 mb-3">
                                        <i class="fas fa-map-marker-alt text-4xl"></i>
                                    </div>
                                    <p class="text-gray-500">Você ainda não possui endereços cadastrados</p>
                                    <button onclick="novoEndereco()" class="theme-primary text-white px-4 py-2 rounded-lg mt-4 hover:opacity-90 transition-colors">
                                        Adicionar Endereço
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="w-[90%] max-w-md mx-auto">
                    <!-- Mensagem de Boas-vindas -->
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Seja bem-vindo!</h1>
                        <p class="text-gray-600">Faça login ou crie sua conta para continuar</p>
                    </div>

                    <!-- Toggle Buttons -->
                    <div class="flex rounded-lg overflow-hidden mb-6 bg-gray-100">
                        <button id="loginToggle" class="toggle-button flex-1 py-3 px-6 focus:outline-none theme-primary text-white active">
                            Entrar
                        </button>
                        <button id="cadastroToggle" class="toggle-button flex-1 py-3 px-6 focus:outline-none bg-gray-200 text-gray-600 hover:bg-gray-300">
                            Cadastrar
                        </button>
                    </div>

                    <!-- Login Form -->
                    <div id="loginForm" class="form-container active bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <form method="POST" class="space-y-4">
                                <div>
                                    <input class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                           id="email" type="email" name="email" required
                                           placeholder="Digite seu email"
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                                <div>
                                    <div class="relative">
                                        <input class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                               id="senha" type="password" name="senha" required
                                               placeholder="Digite sua senha">
                                        <button type="button" onclick="togglePassword('senha')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <i class="fas fa-eye text-gray-500"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center mb-4">
                                    <div class="flex items-center">
                                        <input id="manter_conectado" type="checkbox" name="manter_conectado" class="w-4 h-4 text-theme bg-gray-100 border-gray-300 rounded focus:ring-theme focus:border-theme">
                                        <label for="manter_conectado" class="ml-2 text-sm text-gray-600">
                                            Manter conectado
                                        </label>
                                    </div>
                                    <a href="recuperar-senha.php" class="text-sm text-theme hover:opacity-90 ml-4">
                                        Esqueci minha senha
                                    </a>
                                </div>
                                <div>
                                    <button class="theme-primary text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full hover:opacity-90"
                                            type="submit" name="login">
                                        Entrar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Cadastro Form -->
                    <div id="cadastroForm" class="form-container bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <form method="POST" class="space-y-4" onsubmit="return validarSenhas(this)">
                                <div>
                                    <input class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                           id="cadastro-nome" type="text" name="nome" required
                                           placeholder="Digite seu nome completo">
                                </div>
                                <div>
                                    <input class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                           id="cadastro-email" type="email" name="email" required
                                           placeholder="Digite seu email">
                                </div>
                                <div>
                                    <input class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                           id="cadastro-telefone" type="tel" name="telefone" required
                                           placeholder="Digite seu telefone">
                                </div>
                                <div>
                                    <div class="relative">
                                        <input class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                               id="cadastro-senha" type="password" name="senha" required
                                               placeholder="Digite sua senha">
                                        <button type="button" onclick="togglePassword('cadastro-senha')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <i class="fas fa-eye text-gray-500"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <div class="relative">
                                        <input class="appearance-none rounded-lg relative block w-full px-3 py-4 border border-gray-300 text-gray-900 focus:outline-none focus:z-10 sm:text-sm"
                                               id="cadastro-confirmar-senha" type="password" name="confirmar_senha" required
                                               placeholder="Confirme sua senha">
                                        <button type="button" onclick="togglePassword('cadastro-confirmar-senha')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <i class="fas fa-eye text-gray-500"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <button class="theme-primary text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full hover:opacity-90"
                                            type="submit" name="cadastro">
                                        Cadastrar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Link para Admin -->
                    <div class="mt-8 text-center">
                        <a href="admin/login.php" class="text-theme hover:opacity-90">
                            <i class="fas fa-user-shield mr-2"></i>Área Administrativa
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php include 'includes/menu.php'; ?>

        <script>
            const loginToggle = document.getElementById('loginToggle');
            const cadastroToggle = document.getElementById('cadastroToggle');
            const loginForm = document.getElementById('loginForm');
            const cadastroForm = document.getElementById('cadastroForm');

            loginToggle.addEventListener('click', () => {
                loginToggle.classList.remove('bg-gray-200', 'text-gray-600', 'hover:bg-gray-300');
                loginToggle.classList.add('theme-primary', 'text-white', 'active');
                cadastroToggle.classList.remove('theme-primary', 'text-white', 'active');
                cadastroToggle.classList.add('bg-gray-200', 'text-gray-600', 'hover:bg-gray-300');
                loginForm.classList.add('active');
                cadastroForm.classList.remove('active');
            });

            cadastroToggle.addEventListener('click', () => {
                cadastroToggle.classList.remove('bg-gray-200', 'text-gray-600', 'hover:bg-gray-300');
                cadastroToggle.classList.add('theme-primary', 'text-white', 'active');
                loginToggle.classList.remove('theme-primary', 'text-white', 'active');
                loginToggle.classList.add('bg-gray-200', 'text-gray-600', 'hover:bg-gray-300');
                cadastroForm.classList.add('active');
                loginForm.classList.remove('active');
            });
        </script>

        <script>
            let autocomplete;
            let componentForm = {
                street_number: 'short_name',
                route: 'long_name',
                sublocality_level_1: 'long_name',
                administrative_area_level_2: 'long_name',
                administrative_area_level_1: 'short_name',
                postal_code: 'short_name'
            };

            function initAutocomplete() {
              const maranhaoBounds = new google.maps.LatLngBounds(
                new google.maps.LatLng(-7.5, -48.5), // sudoeste
                new google.maps.LatLng(-1.0, -41.0)  // nordeste
              );
            
              autocomplete = new google.maps.places.Autocomplete(
                document.getElementById('searchInput'),
                {
                  types: ['geocode'],
                  componentRestrictions: { country: 'br' },
                  bounds: maranhaoBounds,
                  strictBounds: true,
                  fields: ['address_components', 'geometry', 'formatted_address']
                }
              );
            
              // << O QUE FALTAVA
              autocomplete.addListener('place_changed', fillInAddress);
            }


            function fillInAddress() {
                const place = autocomplete.getPlace();
                let numero = '';
                let logradouro = '';
                let bairro = '';
                let cidade = '';
                let estado = '';
                let cep = '';

                // Extrair componentes do endereço

                
                for (const component of place.address_components) {
                  // Alguns componentes têm múltiplos types, então checamos includes(...)
                  const types = component.types;
                
                  if (types.includes('street_number')) {
                    numero = component.long_name;
                    continue;
                  }
                  if (types.includes('route')) {
                    logradouro = component.long_name;
                    continue;
                  }
                  // BAIRRO: considerar várias possibilidades
                  if (
                    types.includes('neighborhood') ||
                    types.includes('sublocality_level_1') ||
                    types.includes('sublocality')
                  ) {
                    bairro = bairro || component.long_name; // não sobrescreve se já achou
                    continue;
                  }
                  // CIDADE: locality é o ideal; se não vier, use a_l_2 como fallback
                  if (types.includes('locality')) {
                    cidade = component.long_name;
                    continue;
                  }
                  if (types.includes('administrative_area_level_2')) {
                    // usa a_l_2 só se não tiver locality
                    if (!cidade) cidade = component.long_name;
                    continue;
                  }
                  if (types.includes('administrative_area_level_1')) {
                    estado = component.short_name;
                    continue;
                  }
                  if (types.includes('postal_code')) {
                    cep = component.long_name.replace('-', '');
                    continue;
                  }
                }
            
            // fallback extremo de bairro (algumas respostas usam apenas "political")
            if (!bairro) {
              const poli = (place.address_components || []).find(c => c.types.includes('political'));
              if (poli) bairro = poli.long_name;
            }


                // Se não encontrou o número na estrutura, tenta extrair do endereço digitado
                if (!numero) {
                    const searchInput = document.getElementById('searchInput').value;
                    const matches = searchInput.match(/,?\s*(\d+)\s*,?/);
                    if (matches && matches[1]) {
                        numero = matches[1];
                    } else {
                        // Se não encontrou no input, tenta extrair do endereço formatado
                        const fullAddress = place.formatted_address;
                        const addressMatches = fullAddress.match(/,?\s*(\d+)\s*,?/);
                        if (addressMatches && addressMatches[1]) {
                            numero = addressMatches[1];
                        } else {
                            numero = ''; // Deixa vazio para o usuário digitar
                        }
                    }
                }

                // Preencher campos ocultos
                document.getElementById('logradouro').value = logradouro;
                document.getElementById('numero').value = numero;
                document.getElementById('bairro').value = bairro;
                document.getElementById('cidade').value = cidade;
                document.getElementById('estado').value = estado;
                document.getElementById('cep').value = cep;
                document.getElementById('latitude').value = place.geometry.location.lat();
                document.getElementById('longitude').value = place.geometry.location.lng();

                // Atualizar preview
                const preview = document.getElementById('enderecoPreview');
                const previewText = document.getElementById('previewText');
                preview.classList.remove('hidden');
                
                const enderecoFormatado = `${logradouro}${numero ? ', ' + numero : ''}
                    ${bairro ? `\n${bairro},` : ''} ${cidade} - ${estado}
                    ${cep ? `\nCEP: ${cep}` : ''}`;
                
                previewText.innerHTML = enderecoFormatado.replace(/\n/g, '<br>');

                // Se não encontrou o número, mostra campo para digitar
                if (!numero) {
                    Swal.fire({
                        title: 'Número do endereço',
                        text: 'Por favor, digite o número do endereço:',
                        input: 'text',
                        inputAttributes: {
                            autocapitalize: 'off'
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Confirmar',
                        cancelButtonText: 'Cancelar',
                        showLoaderOnConfirm: true,
                        preConfirm: (number) => {
                            document.getElementById('numero').value = number;
                            const currentText = previewText.innerHTML;
                            previewText.innerHTML = currentText.replace(logradouro, `${logradouro}, ${number}`);
                        }
                    });
                }
            }

            function editarEndereco(id) {
                // Buscar dados do endereço via AJAX
                fetch(`ajax/buscar_endereco.php?id=${id}`)
                    .then(response => response.json())
                    .then(endereco => {
                        // Preencher o formulário com os dados do endereço
                        document.getElementById('endereco_id').value = endereco.id;
                        document.getElementById('logradouro').value = endereco.logradouro;
                        document.getElementById('numero').value = endereco.numero;
                        document.getElementById('complemento').value = endereco.complemento || '';
                        document.getElementById('bairro').value = endereco.bairro;
                        document.getElementById('cidade').value = endereco.cidade;
                        document.getElementById('estado').value = endereco.estado;
                        document.getElementById('cep').value = endereco.cep;
                        document.getElementById('latitude').value = endereco.latitude;
                        document.getElementById('longitude').value = endereco.longitude;

                        // Atualizar o campo de busca
                        document.getElementById('searchInput').value = 
                            `${endereco.logradouro}, ${endereco.numero} - ${endereco.bairro}, ${endereco.cidade} - ${endereco.estado}`;

                        // Atualizar preview
                        const preview = document.getElementById('enderecoPreview');
                        const previewText = document.getElementById('previewText');
                        preview.classList.remove('hidden');
                        previewText.textContent = `${endereco.logradouro}, ${endereco.numero} - ${endereco.bairro}
                            ${endereco.cidade} - ${endereco.estado}
                            CEP: ${endereco.cep}`;
                        
                        // Atualizar o título do modal
                        document.querySelector('#formEndereco h3').textContent = 'Editar Endereço';
                        
                        // Mostrar o modal
                        document.getElementById('formEndereco').classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error('Erro ao buscar endereço:', error);
                        Swal.fire({
                            title: 'Erro',
                            text: 'Não foi possível carregar os dados do endereço',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            }

            function cancelarNovoEndereco() {
                document.getElementById('formEndereco').classList.add('hidden');
                document.getElementById('endereco_id').value = '';
                document.getElementById('formNovoEndereco').reset();
                document.getElementById('enderecoPreview').classList.add('hidden');
                document.querySelector('#formEndereco h3').textContent = 'Adicionar Novo Endereço';
            }

            function novoEndereco() {
                document.getElementById('formEndereco').classList.remove('hidden');
            }

            // Inicializar tooltips do Bootstrap
            document.addEventListener('DOMContentLoaded', function() {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>

        <script>
            function tornarPrincipal(id) {
                fetch(`ajax/tornar_principal.php?id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        Swal.fire({
                            title: 'Erro',
                            text: data.error || 'Não foi possível definir o endereço como principal',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    Swal.fire({
                        title: 'Erro',
                        text: 'Não foi possível definir o endereço como principal',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
            }

            function excluirEndereco(id) {
                Swal.fire({
                    title: 'Confirmar exclusão',
                    text: 'Tem certeza que deseja excluir este endereço?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, excluir',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`ajax/excluir_endereco.php?id=${id}`, {
                            method: 'POST'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                Swal.fire({
                                    title: 'Erro',
                                    text: data.error || 'Não foi possível excluir o endereço',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Erro:', error);
                            Swal.fire({
                                title: 'Erro',
                                text: 'Não foi possível excluir o endereço',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        });
                    }
                });
            }

            function editarEndereco(id) {
                fetch(`ajax/buscar_endereco.php?id=${id}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erro ao buscar endereço');
                        }
                        return response.json();
                    })
                    .then(endereco => {
                        // Preencher o formulário com os dados do endereço
                        document.getElementById('endereco_id').value = endereco.id;
                        document.getElementById('logradouro').value = endereco.logradouro;
                        document.getElementById('numero').value = endereco.numero;
                        document.getElementById('complemento').value = endereco.complemento || '';
                        document.getElementById('bairro').value = endereco.bairro;
                        document.getElementById('cidade').value = endereco.cidade;
                        document.getElementById('estado').value = endereco.estado;
                        document.getElementById('cep').value = endereco.cep;
                        document.getElementById('latitude').value = endereco.latitude;
                        document.getElementById('longitude').value = endereco.longitude;

                        // Atualizar o campo de busca
                        document.getElementById('searchInput').value = 
                            `${endereco.logradouro}, ${endereco.numero} - ${endereco.bairro}, ${endereco.cidade} - ${endereco.estado}`;
                        
                        // Atualizar o título do modal
                        document.querySelector('#formEndereco h3').textContent = 'Editar Endereço';
                        
                        // Mostrar o modal
                        document.getElementById('formEndereco').classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        Swal.fire({
                            title: 'Erro',
                            text: 'Não foi possível carregar os dados do endereço',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            }
        </script>

        <!-- Modal de Novo/Editar Endereço -->
        <div id="formEndereco" class="hidden">
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold">Adicionar Novo Endereço</h3>
                        <button type="button" onclick="cancelarNovoEndereco()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form id="formNovoEndereco" method="POST" action="ajax/salvar_endereco.php" class="space-y-4">
                        <input type="hidden" id="endereco_id" name="endereco_id">
                        <input type="hidden" id="logradouro" name="logradouro">
                        <input type="hidden" id="numero" name="numero">
                        <input type="hidden" id="complemento" name="complemento">
                        <input type="hidden" id="bairro" name="bairro">
                        <input type="hidden" id="cidade" name="cidade">
                        <input type="hidden" id="estado" name="estado">
                        <input type="hidden" id="cep" name="cep">
                        <input type="hidden" id="latitude" name="latitude">
                        <input type="hidden" id="longitude" name="longitude">
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Buscar Endereço
                            </label>
                            <div class="bg-blue-50 p-3 rounded-lg text-sm text-blue-700">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-info-circle mt-0.5"></i>
                                    <div>
                                        <p class="font-medium mb-1">Como preencher:</p>
                                        <p class="text-blue-600">Digite na ordem:</p>
                                        <p class="font-medium mt-1 text-blue-800">
                                            <span class="bg-white px-2 py-0.5 rounded mr-1">Rua/Av.</span> + 
                                            <span class="bg-white px-2 py-0.5 rounded mr-1">Número</span> + 
                                            <span class="bg-white px-2 py-0.5 rounded mr-1">Bairro</span> + 
                                            <span class="bg-white px-2 py-0.5 rounded">Cidade</span>
                                        </p>
                                        <p class="mt-2 text-blue-600">Exemplo:</p>
                                        <p class="italic bg-white px-3 py-1.5 rounded text-blue-800 mt-1">
                                            Rua das Flores, 123, Centro, São Luís
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <input type="text" id="searchInput" 
                                   placeholder="Digite o endereço completo" 
                                   class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3 px-4">
                        </div>

                        <!-- Preview do endereço -->
                        <div id="enderecoPreview" class="hidden bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Endereço selecionado:</p>
                            <p id="previewText" class="text-gray-800"></p>
                        </div>

                        <div class="flex justify-end gap-4 mt-6">
                            <button type="button" onclick="cancelarNovoEndereco()"
                                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="theme-primary text-white rounded-lg hover:opacity-90 transition-colors flex items-center gap-2 px-4 py-2">
                                <i class="fas fa-save"></i>
                                Salvar Endereço
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($maps_api_key); ?>&libraries=places&callback=initAutocomplete" async defer></script>

        <script>
            function togglePassword(inputId) {
                const input = document.getElementById(inputId);
                const icon = input.nextElementSibling.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }

            function validarSenhas(form) {
                const senhaAtual = form.querySelector('input[name="senha_atual"]');
                const senhaNova = form.querySelector('input[name="senha_nova"]');
                const confirmarSenhaNova = form.querySelector('input[name="confirmar_senha_nova"]');
                
                // Se estiver alterando a senha (formulário de perfil)
                if (senhaAtual && senhaNova && confirmarSenhaNova) {
                    if (!senhaAtual.value) {
                        Swal.fire({
                            title: 'Erro',
                            text: 'Por favor, digite sua senha atual.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: 'var(--theme-color)'
                        });
                        return false;
                    }
                    
                    // Se algum campo de nova senha estiver preenchido, todos devem estar
                    if (senhaNova.value || confirmarSenhaNova.value) {
                        if (!senhaNova.value || !confirmarSenhaNova.value) {
                            Swal.fire({
                                title: 'Erro',
                                text: 'Por favor, preencha ambos os campos de nova senha.',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                confirmButtonColor: 'var(--theme-color)'
                            });
                            return false;
                        }
                        
                        if (senhaNova.value !== confirmarSenhaNova.value) {
                            Swal.fire({
                                title: 'Erro',
                                text: 'As novas senhas não coincidem. Por favor, verifique e tente novamente.',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                confirmButtonColor: 'var(--theme-color)'
                            });
                            return false;
                        }
                        
                        if (senhaNova.value.length < 6) {
                            Swal.fire({
                                title: 'Erro',
                                text: 'A nova senha deve ter pelo menos 6 caracteres.',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                confirmButtonColor: 'var(--theme-color)'
                            });
                            return false;
                        }
                    }
                }
                
                return true;
            }
        </script>
    </body>
</html>