<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Dados do admin
    $nome = "Admin";
    $email = "admin@admin.com";
    $senha = md5("admin"); // Senha: admin
    
    // Verifica se já existe um admin
    $query = "SELECT COUNT(*) FROM administradores WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        // Atualiza a senha do admin existente
        $query = "UPDATE administradores SET senha = ? WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$senha, $email]);
        echo "Senha do admin atualizada com sucesso!<br>";
        echo "Email: admin@admin.com<br>";
        echo "Senha: admin";
    } else {
        // Cria um novo admin
        $query = "INSERT INTO administradores (nome, email, senha) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$nome, $email, $senha]);
        echo "Admin criado com sucesso!<br>";
        echo "Email: admin@admin.com<br>";
        echo "Senha: admin";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
