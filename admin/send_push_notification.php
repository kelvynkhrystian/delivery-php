<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function sendPushNotification($pedidoId) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Configurações VAPID (você precisará gerar suas próprias chaves)
    $auth = array(
        'VAPID' => array(
            'subject' => 'mailto:seu-email@exemplo.com', // Seu email
            'publicKey' => 'SUA_CHAVE_PUBLICA_VAPID', // Sua chave pública VAPID
            'privateKey' => 'SUA_CHAVE_PRIVADA_VAPID', // Sua chave privada VAPID
        ),
    );
    
    $webPush = new WebPush($auth);
    
    // Buscar todas as subscriptions dos admins
    $query = "SELECT subscription FROM push_subscriptions WHERE active = 1";
    $stmt = $db->query($query);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $subscription = json_decode($row['subscription'], true);
        
        $notification = [
            'subscription' => Subscription::create($subscription),
            'payload' => json_encode([
                'title' => 'Novo Pedido',
                'body' => "Pedido #$pedidoId recebido!",
                'icon' => '/gestao/assets/images/icon.png',
                'pedidoId' => $pedidoId
            ])
        ];
        
        $webPush->queueNotification(
            $notification['subscription'],
            $notification['payload']
        );
    }
    
    // Enviar as notificações
    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();
        
        if (!$report->isSuccess()) {
            // Marcar subscription como inativa se houver erro
            $query = "UPDATE push_subscriptions SET active = 0 WHERE subscription LIKE ?";
            $stmt = $db->prepare($query);
            $stmt->execute(['%' . $endpoint . '%']);
        }
    }
}
