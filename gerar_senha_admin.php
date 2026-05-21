<?php
require_once 'config/database.php';

$senha = 'admin123';
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

$database = new Database();
$db = $database->getConnection();

$query = "UPDATE usuarios SET senha = ? WHERE email = 'admin@admin.com' AND tipo = 'admin'";
$stmt = $db->prepare($query);

if ($stmt->execute([$senha_hash])) {
    echo "Senha do admin atualizada com sucesso!\n";
    echo "Email: admin@admin.com\n";
    echo "Senha: admin123\n";
} else {
    echo "Erro ao atualizar senha do admin";
}
