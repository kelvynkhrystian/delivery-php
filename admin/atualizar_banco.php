<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Iniciando atualização do banco de dados...</h2>";
    
    // Adiciona as colunas
    $sql = "ALTER TABLE pedidos 
            ADD COLUMN IF NOT EXISTS cupom_id INT NULL,
            ADD COLUMN IF NOT EXISTS desconto_cupom DECIMAL(10,2) DEFAULT 0.00,
            ADD COLUMN IF NOT EXISTS data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP";
    $db->exec($sql);
    echo "<p style='color: green;'>✓ Colunas adicionadas com sucesso!</p>";
    
    // Adiciona a chave estrangeira
    $sql = "ALTER TABLE pedidos
            ADD CONSTRAINT IF NOT EXISTS fk_pedidos_cupom 
            FOREIGN KEY (cupom_id) REFERENCES cupons(id)";
    $db->exec($sql);
    echo "<p style='color: green;'>✓ Chave estrangeira adicionada com sucesso!</p>";
    
    // Atualiza pedidos existentes
    $sql = "UPDATE pedidos SET desconto_cupom = 0 WHERE desconto_cupom IS NULL";
    $db->exec($sql);
    echo "<p style='color: green;'>✓ Pedidos atualizados com sucesso!</p>";
    
    echo "<h3 style='color: green;'>Atualização concluída com sucesso!</h3>";
    echo "<p><a href='pedidos.php' style='color: blue; text-decoration: underline;'>Voltar para Pedidos</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Erro durante a atualização:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p><a href='pedidos.php' style='color: blue; text-decoration: underline;'>Voltar para Pedidos</a></p>";
}
?>
