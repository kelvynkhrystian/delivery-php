<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Primeiro, vamos verificar se o admin existe
$query = "SELECT * FROM administradores WHERE email = 'admin@admin.com'";
$stmt = $db->prepare($query);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    // Se não existe, criar o admin
    $senha = 'admin123';
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO administradores (nome, email, senha) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    if ($stmt->execute(['Administrador', 'admin@admin.com', $hash])) {
        echo "✅ Administrador criado com sucesso!\n";
    } else {
        echo "❌ Erro ao criar administrador\n";
        exit;
    }
} else {
    // Se existe, atualizar a senha
    $senha = 'admin123';
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    
    $query = "UPDATE administradores SET senha = ? WHERE email = 'admin@admin.com'";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$hash])) {
        echo "✅ Senha do administrador atualizada com sucesso!\n";
    } else {
        echo "❌ Erro ao atualizar senha\n";
        exit;
    }
}

echo "\nDados para login:\n";
echo "Email: admin@admin.com\n";
echo "Senha: admin123\n";

// Testar a senha
$query = "SELECT * FROM administradores WHERE email = 'admin@admin.com'";
$stmt = $db->prepare($query);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (password_verify('admin123', $admin['senha'])) {
    echo "\n✅ Verificação da senha: OK";
} else {
    echo "\n❌ Erro na verificação da senha";
}
