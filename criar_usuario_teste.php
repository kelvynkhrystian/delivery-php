<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Remover usuário de teste se já existir
    $query = "DELETE FROM usuarios WHERE email = 'teste@teste.com'";
    $db->exec($query);
    
    // Criar novo usuário de teste
    $senha = 'teste123';
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO usuarios (nome_completo, email, senha, telefone) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute(['Usuário Teste', 'teste@teste.com', $hash, '11999999999'])) {
        echo "✅ Usuário de teste criado com sucesso!\n\n";
        echo "Use estes dados para login:\n";
        echo "Email: teste@teste.com\n";
        echo "Senha: teste123\n\n";
        
        // Verificar se a senha funciona
        $query = "SELECT * FROM usuarios WHERE email = 'teste@teste.com'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify('teste123', $usuario['senha'])) {
            echo "✅ Verificação da senha: OK\n";
        } else {
            echo "❌ Erro na verificação da senha\n";
        }
    } else {
        echo "❌ Erro ao criar usuário de teste\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
