<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Dados do usuário
    $nome = "Usuário Teste";
    $email = "teste@teste.com";
    $senha = md5("123456"); // Senha: 123456
    $telefone = "(11) 99999-9999";
    
    // Verifica se já existe um usuário
    $query = "SELECT COUNT(*) FROM usuarios WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        // Atualiza a senha do usuário existente
        $query = "UPDATE usuarios SET nome = ?, senha = ?, telefone = ? WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$nome, $senha, $telefone, $email]);
        echo "Usuário atualizado com sucesso!<br>";
        echo "Email: teste@teste.com<br>";
        echo "Senha: 123456";
    } else {
        // Cria um novo usuário
        $query = "INSERT INTO usuarios (nome, email, senha, telefone) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$nome, $email, $senha, $telefone]);
        echo "Usuário criado com sucesso!<br>";
        echo "Email: teste@teste.com<br>";
        echo "Senha: 123456";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
