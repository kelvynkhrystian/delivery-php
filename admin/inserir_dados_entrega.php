<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verifica se as tabelas existem, se não, cria
    $db->exec("
        CREATE TABLE IF NOT EXISTS bairros_entrega (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_nome (nome)
        );

        CREATE TABLE IF NOT EXISTS faixas_distancia (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inicio DECIMAL(10,2) NOT NULL,
            fim DECIMAL(10,2) NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_faixa (inicio, fim)
        );
    ");
    
    // Lê e executa o arquivo SQL
    $sql = file_get_contents('sql/inserir_dados_entrega.sql');
    $db->exec($sql);
    
    echo "Dados inseridos com sucesso!";
} catch (PDOException $e) {
    echo "Erro ao inserir dados: " . $e->getMessage();
}
?>
