<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conectado ao MySQL com sucesso!\n\n";
    
    // Ler o arquivo SQL
    $sql = file_get_contents('database.sql');
    
    // Executar os comandos SQL
    $pdo->exec($sql);
    
    echo "✅ Banco de dados e tabelas criados com sucesso!\n";
    echo "✅ Administrador padrão criado:\n";
    echo "   Email: admin@admin.com\n";
    echo "   Senha: admin123\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
