<?php
$senha = 'admin123';
$hash = password_hash($senha, PASSWORD_DEFAULT);
echo "Hash gerado: " . $hash;

// Teste de verificação
if (password_verify($senha, $hash)) {
    echo "\nVerificação OK!";
} else {
    echo "\nVerificação falhou!";
}
