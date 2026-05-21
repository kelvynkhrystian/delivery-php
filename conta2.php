<?php
session_start();
require_once 'config/database.php';

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
            $mensagem = 'Email ou senha incorretos';
        }
    } elseif (isset($_POST['cadastro'])) {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $telefone = $_POST['telefone'];

        $query = "INSERT INTO usuarios (nome, email, senha, telefone) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        try {
            if ($stmt->execute([$nome, $email, $senha, $telefone])) {
                // Pega o ID do usuário recém criado
                $usuario_id = $db->lastInsertId();
                
                // Busca os dados completos do usuário
                $query = "SELECT * FROM usuarios WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$usuario_id]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Faz login automático
                $_SESSION['usuario'] = $usuario;
                
                // Redireciona para a página anterior ou index
                $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            } else {
                $mensagem = 'Erro ao realizar cadastro';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensagem = 'Este email já está cadastrado';
            } else {
                $mensagem = 'Erro ao realizar cadastro';
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 pb-24 min-h-screen flex items-center justify-center">
        <?php if (isset($_SESSION['usuario'])): ?>
            <!-- Usuário Logado -->
            <div class="bg-white rounded-lg shadow-md p-6 w-[90%] max-w-md mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold">Minha Conta</h1>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-2"></i>Sair
                    </a>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nome Completo</label>
                        <p class="text-gray-600"><?php echo htmlspecialchars($_SESSION['usuario']['nome']); ?></p>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <p class="text-gray-600"><?php echo htmlspecialchars($_SESSION['usuario']['email']); ?></p>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Telefone</label>
                        <p class="text-gray-600"><?php echo htmlspecialchars($_SESSION['usuario']['telefone'] ?? 'Não informado'); ?></p>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Endereço</label>
                        <p class="text-gray-600"><?php echo htmlspecialchars($_SESSION['usuario']['endereco'] ?? 'Não informado'); ?></p>
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

                <!-- Mensagem de erro/sucesso -->
                <?php if ($mensagem): ?>
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" role="alert">
                        <p><?php echo $mensagem; ?></p>
                    </div>
                <?php endif; ?>

                <!-- Toggle Buttons -->
                <div class="flex rounded-lg overflow-hidden mb-6 bg-gray-100">
                    <button id="loginToggle" class="toggle-button flex-1 py-3 px-6 focus:outline-none bg-blue-600 text-white">
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
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="login-email">
                                    Email
                                </label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                       id="login-email" type="email" name="email" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="login-senha">
                                    Senha
                                </label>
                                <div class="relative">
                                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline pr-10"
                                           id="login-senha" type="password" name="senha" required>
                                    <button type="button" onclick="togglePassword('login-senha')" class="absolute inset-y-0 right-0 pr-3 flex items-center mb-3">
                                        <i class="fas fa-eye text-gray-500"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center mb-4">
                                <input id="manter_conectado" type="checkbox" name="manter_conectado" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <label for="manter_conectado" class="ml-2 text-sm text-gray-600">
                                    Manter conectado
                                </label>
                            </div>
                            <div>
                                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
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
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="cadastro-nome">
                                    Nome Completo
                                </label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                       id="cadastro-nome" type="text" name="nome" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="cadastro-email">
                                    Email
                                </label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                       id="cadastro-email" type="email" name="email" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="cadastro-senha">
                                    Senha
                                </label>
                                <div class="relative">
                                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline pr-10"
                                           id="cadastro-senha" type="password" name="senha" required>
                                    <button type="button" onclick="togglePassword('cadastro-senha')" class="absolute inset-y-0 right-0 pr-3 flex items-center mb-3">
                                        <i class="fas fa-eye text-gray-500"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="cadastro-telefone">
                                    Telefone
                                </label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                       id="cadastro-telefone" type="tel" name="telefone" required>
                            </div>
                            <div>
                                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                                        type="submit" name="cadastro">
                                    Cadastrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Link para Admin -->
                <div class="mt-8 text-center">
                    <a href="admin/login.php" class="text-blue-600 hover:text-blue-800">
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
            loginToggle.classList.add('bg-blue-600', 'text-white');
            cadastroToggle.classList.remove('bg-blue-600', 'text-white');
            cadastroToggle.classList.add('bg-gray-200', 'text-gray-600', 'hover:bg-gray-300');
            loginForm.classList.add('active');
            cadastroForm.classList.remove('active');
        });

        cadastroToggle.addEventListener('click', () => {
            cadastroToggle.classList.remove('bg-gray-200', 'text-gray-600', 'hover:bg-gray-300');
            cadastroToggle.classList.add('bg-blue-600', 'text-white');
            loginToggle.classList.remove('bg-blue-600', 'text-white');
            loginToggle.classList.add('bg-gray-200', 'text-gray-600', 'hover:bg-gray-300');
            cadastroForm.classList.add('active');
            loginForm.classList.remove('active');
        });

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
    </script>

    <script src="assets/js/main.js"></script>
</body>
</html>
