<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Remover admin existente
    $query = "DELETE FROM administradores WHERE email = 'admin@admin.com'";
    $db->exec($query);
    
    // Criar novo admin
    $senha = 'admin321';
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO administradores (nome, email, senha) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute(['Administrador', 'admin@admin.com', $hash])) {
        echo "✅ Administrador criado com sucesso!\n\n";
        echo "Use estes dados para login:\n";
        echo "Email: admin@admin.com\n";
        echo "Senha: admin321\n\n";
        
        // Verificar se a senha funciona
        $query = "SELECT * FROM administradores WHERE email = 'admin@admin.com'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify('admin321', $admin['senha'])) {
            echo "✅ Verificação da senha: OK\n";
            echo "\nHash gerado: " . $admin['senha'] . "\n";
        } else {
            echo "❌ Erro na verificação da senha\n";
        }
    } else {
        echo "❌ Erro ao criar administrador\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
