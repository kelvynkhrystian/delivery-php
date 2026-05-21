<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Recebe os dados do formulário
$data = json_decode(file_get_contents('php://input'), true);
$tipo = $data['tipo'] ?? ''; // 'email' ou 'senha'

try {
    // Verifica a senha atual
    $stmt = $db->prepare("SELECT senha FROM administradores WHERE id = ?");
    $stmt->execute([1]); // Assumindo que o ID do admin é 1
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || md5($data['senha_atual']) !== $admin['senha']) {
        echo json_encode(['success' => false, 'message' => 'Senha atual incorreta']);
        exit;
    }

    if ($tipo === 'email') {
        $novo_email = $data['novo_email'] ?? '';
        
        if (empty($novo_email)) {
            echo json_encode(['success' => false, 'message' => 'O novo email é obrigatório']);
            exit;
        }

        // Atualiza apenas o email
        $stmt = $db->prepare("UPDATE administradores SET email = ? WHERE id = ?");
        $stmt->execute([$novo_email, 1]);
        
        // Atualiza a sessão com o novo email
        $_SESSION['admin']['email'] = $novo_email;
        
        echo json_encode(['success' => true, 'message' => 'Email atualizado com sucesso']);
    } 
    else if ($tipo === 'senha') {
        $nova_senha = $data['nova_senha'] ?? '';
        $confirmar_senha = $data['confirmar_senha'] ?? '';

        if (empty($nova_senha)) {
            echo json_encode(['success' => false, 'message' => 'A nova senha é obrigatória']);
            exit;
        }

        if ($nova_senha !== $confirmar_senha) {
            echo json_encode(['success' => false, 'message' => 'As senhas não coincidem']);
            exit;
        }

        if (strlen($nova_senha) < 6) {
            echo json_encode(['success' => false, 'message' => 'A nova senha deve ter pelo menos 6 caracteres']);
            exit;
        }
        
        // Hash MD5 da nova senha
        $senha_hash = md5($nova_senha);
        
        // Atualiza a senha
        $stmt = $db->prepare("UPDATE administradores SET senha = ? WHERE id = ?");
        $stmt->execute([$senha_hash, 1]);
        
        // Atualiza a sessão com a nova senha
        $_SESSION['admin']['senha'] = $senha_hash;
        
        echo json_encode(['success' => true, 'message' => 'Senha atualizada com sucesso']);
    } 
    else {
        echo json_encode(['success' => false, 'message' => 'Operação inválida']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar credenciais: ' . $e->getMessage()]);
}
