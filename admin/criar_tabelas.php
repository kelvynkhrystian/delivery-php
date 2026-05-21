<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Lê o arquivo SQL
    $sql = file_get_contents('sql/criar_tabelas_entrega.sql');
    
    // Executa cada comando SQL separadamente
    $queries = explode(';', $sql);
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $db->exec($query);
        }
    }
    
    echo "Tabelas criadas com sucesso!";
} catch (PDOException $e) {
    echo "Erro ao criar tabelas: " . $e->getMessage();
}
?>
