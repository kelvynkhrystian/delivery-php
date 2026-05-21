<?php
session_start();
require_once 'config/database.php';
require_once 'classes/EmailSender.php';

// Mostrar erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verifica se o email existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        // Gera senha temporária
        $nova_senha = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        // Atualiza a senha no banco e marca como temporária
        $stmt = $db->prepare("UPDATE usuarios SET senha = ?, senha_temporaria = 1 WHERE id = ?");
        if ($stmt->execute([$senha_hash, $usuario['id']])) {
            try {
                // Envia email
                $emailSender = new EmailSender();
                $assunto = "Sua Nova Senha Temporária";
                $mensagem = "Sua senha temporária é: " . $nova_senha . "\n\n";
                $mensagem .= "Por favor, faça login e altere sua senha.\n\n";
                $mensagem .= "IMPORTANTE: Este é um email automático. Por favor, não responda esta mensagem.";

                if ($emailSender->enviarEmail($email, $assunto, $mensagem)) {
                    $_SESSION['mensagem'] = "Uma nova senha foi enviada para seu email.";
                    $_SESSION['tipo'] = "success";
                } else {
                    throw new Exception("Falha ao enviar email");
                }
            } catch (Exception $e) {
                $_SESSION['mensagem'] = "Erro ao enviar email: " . $e->getMessage();
                $_SESSION['tipo'] = "error";
            }
        } else {
            $_SESSION['mensagem'] = "Erro ao atualizar senha. Por favor, tente novamente.";
            $_SESSION['tipo'] = "error";
        }
    } else {
        $_SESSION['mensagem'] = "Email não encontrado no sistema.";
        $_SESSION['tipo'] = "error";
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Delivery</title>
    <?php include 'includes/header.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <?php if (isset($_SESSION['mensagem'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: '<?php echo $_SESSION['tipo'] === "success" ? "Sucesso!" : "Erro!"; ?>',
                text: '<?php echo $_SESSION['mensagem']; ?>',
                icon: '<?php echo $_SESSION['tipo']; ?>',
                confirmButtonText: 'OK',
                confirmButtonColor: 'var(--theme-color)'
            }).then(() => {
                if ('<?php echo $_SESSION['tipo']; ?>' === 'success') {
                    window.location.href = 'conta.php';
                }
            });
        });
    </script>
    <?php 
    unset($_SESSION['mensagem']);
    unset($_SESSION['tipo']);
    endif; ?>

    <div class="w-[90%] max-w-md">
        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-8">
                <div class="h-20 w-20 bg-theme/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lock-open text-theme text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Recuperar Senha</h1>
                <p class="text-gray-600 mt-2">Digite seu email para receber uma nova senha</p>
            </div>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" required
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-theme focus:border-theme"
                               placeholder="seu@email.com">
                    </div>
                </div>

                <button type="submit" 
                        class="w-full theme-primary text-white py-2 px-4 rounded-lg hover:opacity-90 transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-paper-plane"></i>
                    Enviar Nova Senha
                </button>

                <div class="text-center mt-4">
                    <a href="conta.php" class="text-theme hover:opacity-90">
                        Voltar para Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/menu.php'; ?>
</body>
</html>
