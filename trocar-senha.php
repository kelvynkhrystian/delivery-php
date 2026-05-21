<?php
session_start();
require_once 'config/database.php';

// Se não estiver logado, redireciona
if (!isset($_SESSION['usuario_id'])) {
    header('Location: conta.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    if ($nova_senha !== $confirmar_senha) {
        $_SESSION['mensagem'] = "As senhas não coincidem.";
        $_SESSION['tipo'] = "error";
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verifica senha atual
        $stmt = $db->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && password_verify($senha_atual, $usuario['senha'])) {
            // Atualiza a senha e remove flag de temporária
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET senha = ?, senha_temporaria = 0 WHERE id = ?");
            
            if ($stmt->execute([$senha_hash, $_SESSION['usuario_id']])) {
                $_SESSION['mensagem'] = "Senha alterada com sucesso!";
                $_SESSION['tipo'] = "success";
                header('Location: admin/index.php');
                exit;
            } else {
                $_SESSION['mensagem'] = "Erro ao atualizar senha.";
                $_SESSION['tipo'] = "error";
            }
        } else {
            $_SESSION['mensagem'] = "Senha atual incorreta.";
            $_SESSION['tipo'] = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha - Delivery App Online</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <?php if (isset($_SESSION['mensagem'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $_SESSION['tipo']; ?>',
                title: '<?php echo $_SESSION['mensagem']; ?>',
                showConfirmButton: true,
                timer: 3000
            });
        });
    </script>
    <?php 
    unset($_SESSION['mensagem']);
    unset($_SESSION['tipo']);
    endif; 
    ?>
    
    <div class="w-full max-w-lg mx-4">
        <div class="bg-white rounded-lg shadow-md">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800 text-center">Trocar Senha</h2>
                <p class="mt-2 text-gray-600 text-center">Digite sua nova senha</p>
            </div>

            <!-- Formulário -->
            <div class="p-8">
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-3" for="senha_atual">
                            <i class="fas fa-lock text-gray-400 mr-2"></i>Senha Atual
                        </label>
                        <input class="shadow-sm appearance-none border border-gray-300 rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               id="senha_atual" type="password" name="senha_atual" required 
                               placeholder="Digite sua senha atual">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-3" for="nova_senha">
                            <i class="fas fa-key text-gray-400 mr-2"></i>Nova Senha
                        </label>
                        <input class="shadow-sm appearance-none border border-gray-300 rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               id="nova_senha" type="password" name="nova_senha" required 
                               placeholder="Digite sua nova senha">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-3" for="confirmar_senha">
                            <i class="fas fa-check text-gray-400 mr-2"></i>Confirmar Nova Senha
                        </label>
                        <input class="shadow-sm appearance-none border border-gray-300 rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               id="confirmar_senha" type="password" name="confirmar_senha" required 
                               placeholder="Confirme sua nova senha">
                    </div>
                    <div class="flex items-center justify-between pt-2">
                        <button class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline w-full transition-colors duration-200"
                                type="submit">
                            <i class="fas fa-save mr-2"></i>Salvar Nova Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
