<?php
session_start();
require_once 'includes/conexao.php';

$erro = '';
$return = isset($_GET['return']) ? $_GET['return'] : 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario'] = $usuario;
            header('Location: ' . $return);
            exit;
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestão</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-pattern min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-lg">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Fazer Login
                </h2>
            </div>
            <?php if ($erro): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($erro); ?></span>
                </div>
            <?php endif; ?>
            <form class="mt-8 space-y-6" method="POST">
                <input type="hidden" name="return" value="<?php echo htmlspecialchars($return); ?>">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">E-mail</label>
                        <input id="email" name="email" type="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-4 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 focus:z-10 sm:text-sm" 
                               placeholder="E-mail">
                    </div>
                    <div>
                        <label for="senha" class="sr-only">Senha</label>
                        <input id="senha" name="senha" type="password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-4 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-purple-500 focus:border-purple-500 focus:z-10 sm:text-sm" 
                               placeholder="Senha">
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt text-purple-500 group-hover:text-purple-400"></i>
                        </span>
                        Entrar
                    </button>
                </div>
                
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Não tem uma conta? 
                        <a href="cadastro.php" class="font-medium text-purple-600 hover:text-purple-500">
                            Cadastre-se
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/menu.php'; ?>
</body>
</html>
