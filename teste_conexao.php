<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    echo "Tentando conectar ao banco de dados...\n";
    
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "✅ Conexão bem sucedida!\n\n";
        
        // Testar se o banco existe
        $result = $conn->query("SHOW DATABASES LIKE 'delivery_app'");
        if ($result->fetch()) {
            echo "✅ Banco 'delivery_app' existe\n";
            
            // Testar se a tabela existe
            $result = $conn->query("SHOW TABLES FROM delivery_app LIKE 'administradores'");
            if ($result->fetch()) {
                echo "✅ Tabela 'administradores' existe\n";
                
                // Contar admins
                $result = $conn->query("SELECT COUNT(*) as total FROM administradores");
                $count = $result->fetch(PDO::FETCH_ASSOC);
                echo "Total de administradores: " . $count['total'] . "\n";
            } else {
                echo "❌ Tabela 'administradores' não existe\n";
            }
        } else {
            echo "❌ Banco 'delivery_app' não existe\n";
        }
    } else {
        echo "❌ Falha na conexão\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
