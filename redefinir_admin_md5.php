<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Senha padrão: admin123
    $senha = md5("admin123");
    
    $query = "UPDATE administradores SET senha = ? WHERE email = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$senha, 'admin@admin.com'])) {
        echo "Senha do administrador atualizada com sucesso!\n";
        echo "Email: admin@admin.com\n";
        echo "Senha: admin123\n";
        echo "Hash MD5: " . $senha . "\n";
    } else {
        echo "Erro ao atualizar senha do administrador.";
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
