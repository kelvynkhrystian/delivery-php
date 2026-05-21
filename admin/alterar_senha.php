<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    if ($nova_senha !== $confirmar_senha) {
        $mensagem = 'As senhas não coincidem.';
        echo "<script>alert('$mensagem');</script>";
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar senha atual
    $query = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['admin']['id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!password_verify($senha_atual, $admin['senha'])) {
        $_SESSION['mensagem'] = 'Senha atual incorreta';
        header('Location: index.php');
        exit;
    }
    
    // Atualizar senha
    $nova_senha_hash = md5($nova_senha);
    $query = "UPDATE usuarios SET senha = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$nova_senha_hash, $_SESSION['admin']['id']])) {
        $_SESSION['mensagem'] = 'Senha alterada com sucesso';
        
        // Remover token de "manter conectado" em todos os dispositivos
        $query = "UPDATE usuarios SET remember_token = NULL WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['admin']['id']]);
        
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    } else {
        $_SESSION['mensagem'] = 'Erro ao alterar a senha';
    }
}

header('Location: index.php');
exit;
