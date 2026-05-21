<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Gerar nova senha
$nova_senha = 'admin123';
$hash = password_hash($nova_senha, PASSWORD_DEFAULT);

// Atualizar senha do admin
$query = "UPDATE usuarios SET senha = ? WHERE email = 'admin@admin.com' AND tipo = 'admin'";
$stmt = $db->prepare($query);

if ($stmt->execute([$hash])) {
    echo "Senha do admin redefinida com sucesso!\n";
    echo "Email: admin@admin.com\n";
    echo "Nova senha: admin123\n";
    echo "Hash gerado: " . $hash . "\n";
    
    // Testar a nova senha
    if (password_verify($nova_senha, $hash)) {
        echo "\n✅ Verificação da nova senha: OK";
    } else {
        echo "\n❌ Erro na verificação da nova senha";
    }
} else {
    echo "Erro ao redefinir senha do admin";
}
