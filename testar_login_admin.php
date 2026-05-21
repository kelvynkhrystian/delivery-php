<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Buscar usuário admin
$query = "SELECT * FROM usuarios WHERE email = 'admin@admin.com' AND tipo = 'admin'";
$stmt = $db->prepare($query);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== Teste de Login Admin ===\n\n";

if ($admin) {
    echo "✅ Usuário admin encontrado:\n";
    echo "ID: " . $admin['id'] . "\n";
    echo "Nome: " . $admin['nome_completo'] . "\n";
    echo "Email: " . $admin['email'] . "\n";
    echo "Tipo: " . $admin['tipo'] . "\n";
    
    // Testar senha
    $senha = 'admin123';
    $senha_correta = password_verify($senha, $admin['senha']);
    
    echo "\nTeste de senha 'admin123':\n";
    if ($senha_correta) {
        echo "✅ Senha está correta\n";
    } else {
        echo "❌ Senha está incorreta\n";
        echo "Hash atual: " . $admin['senha'] . "\n";
        echo "Hash esperado: \$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\n";
    }
} else {
    echo "❌ Usuário admin não encontrado no banco de dados\n";
    
    // Verificar se o banco existe
    $query = "SHOW DATABASES LIKE 'delivery_app'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $database_exists = $stmt->fetch();
    
    if ($database_exists) {
        echo "✅ Banco de dados 'delivery_app' existe\n";
        
        // Verificar se a tabela existe
        $query = "SHOW TABLES LIKE 'usuarios'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $table_exists = $stmt->fetch();
        
        if ($table_exists) {
            echo "✅ Tabela 'usuarios' existe\n";
            
            // Contar usuários
            $query = "SELECT COUNT(*) as total FROM usuarios";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            echo "Total de usuários: " . $result['total'] . "\n";
        } else {
            echo "❌ Tabela 'usuarios' não existe\n";
        }
    } else {
        echo "❌ Banco de dados 'delivery_app' não existe\n";
    }
}
