<?php
session_start();
require_once '../config/database.php';

// Busca a cor do tema atual
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->query("SELECT cor_tema FROM configuracoes ORDER BY id DESC LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    $corTema = $config['cor_tema'] ?? '#8B5CF6'; // Roxo como cor padrão
} catch (PDOException $e) {
    $corTema = '#8B5CF6';
    error_log("Erro ao buscar cor do tema: " . $e->getMessage());
}

$mensagem = '';
if (!isset($_POST['email']) && isset($_COOKIE['remember_token'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $remember_token = $_COOKIE['remember_token'];
    
    $query = "SELECT * FROM administradores WHERE remember_token = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$remember_token]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        $_SESSION['admin'] = $admin;
        header('Location: dashboard.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $manter_conectado = isset($_POST['manter_conectado']);

    $query = "SELECT * FROM administradores WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        // Log: Email encontrado
        error_log("Email encontrado: " . $admin['email']);
        error_log("Hash da senha armazenado: " . $admin['senha']);
        error_log("Senha fornecida: " . $senha);

        if (md5($senha) === $admin['senha']) {
            $_SESSION['admin'] = $admin;
            
            if ($manter_conectado) {
                $remember_token = bin2hex(random_bytes(32));
                $query = "UPDATE administradores SET remember_token = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$remember_token, $admin['id']]);
                
                setcookie('remember_token', $remember_token, time() + (86400 * 30), '/');
            }
            
            header('Location: dashboard.php');
            exit;
        } else {
            error_log("Senha incorreta fornecida para o email: " . $admin['email']);
            $mensagem = 'Senha incorreta para o email: ' . $admin['email'];
            echo "<script>alert('$mensagem');</script>";
        }
    } else {
        error_log("Email não encontrado: " . $email);
        $mensagem = 'Email não encontrado';
        echo "<script>alert('$mensagem');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo - Delivery App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --theme-color: <?php echo $corTema; ?>;
            --theme-rgb: <?php 
                $hex = ltrim($corTema, '#');
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                echo "$r, $g, $b";
            ?>;
        }

        .theme-primary {
            background-color: var(--theme-color);
        }

        .theme-primary.text-white {
            color: white;
        }

        .text-theme {
            color: var(--theme-color);
        }

        /* Estilos para inputs */
        input:focus {
            outline: 2px solid transparent;
            outline-offset: 2px;
            border-color: var(--theme-color) !important;
            box-shadow: 0 0 0 1px var(--theme-color) !important;
        }

        input[type="checkbox"]:checked {
            background-color: var(--theme-color) !important;
            border-color: var(--theme-color) !important;
        }

        input[type="checkbox"]:focus {
            --tw-ring-color: rgba(var(--theme-rgb), 0.2) !important;
        }

        .btn-theme {
            background-color: var(--theme-color);
            transition: all 0.3s ease;
        }
        .btn-theme:hover {
            filter: brightness(0.9);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="container mx-auto px-4">
        <div class="w-[90%] max-w-md mx-auto">
            <?php if ($mensagem): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p><?php echo $mensagem; ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-center mb-8">
                    <i class="fas fa-user-shield text-4xl text-theme mb-4"></i>
                    <h1 class="text-2xl font-bold text-gray-800">Área Administrativa</h1>
                </div>

                <form method="POST" class="space-y-6">
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
                        <input id="manter_conectado" type="checkbox" name="manter_conectado" class="w-4 h-4 text-theme bg-gray-100 border-gray-300 rounded focus:ring-theme focus:border-theme">
                        <label for="manter_conectado" class="ml-2 text-sm text-gray-600">
                            Manter conectado
                        </label>
                    </div>
                    <div>
                        <button type="submit" class="btn-theme w-full text-white py-4 rounded-lg text-sm font-semibold">
                            Entrar
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <a href="../index.php" class="font-medium text-theme hover:opacity-90">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar para a loja
                    </a>
                </div>
            </div>
        </div>
    </div>

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
    </script>
</body>
</html>
