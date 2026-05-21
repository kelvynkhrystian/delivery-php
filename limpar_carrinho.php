<?php
session_start();

// Limpa o carrinho na sessão
$_SESSION['carrinho'] = array();

// Retorna um script para limpar o localStorage também
header('Content-Type: application/javascript');
echo "
    localStorage.removeItem('carrinho');
    if (typeof atualizarBadgeCarrinho === 'function') {
        atualizarBadgeCarrinho();
    }
    window.location.reload();
";
?>
