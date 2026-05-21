<?php
session_start();
require_once '../config/database.php';

// Simular que é admin
$_SESSION['admin'] = true;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste de Notificações</title>
    <script>
        const isAdmin = true;
    </script>
    <script src="../js/notifications.js"></script>
</head>
<body>
    <h1>Teste de Notificações</h1>
    <button onclick="testarNotificacao()">Testar Notificação</button>

    <script>
        async function testarNotificacao() {
            // Primeiro pede permissão se necessário
            await requestNotificationPermission();
            
            // Simula uma notificação de novo pedido
            showNotification(
                "Novo Pedido!", 
                "Pedido #123 recebido! (Teste de notificação)"
            );
        }
    </script>
</body>
</html>
