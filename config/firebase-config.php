<?php
// Configurações do Firebase
define('FIREBASE_SERVER_KEY', 'YOUR_FIREBASE_SERVER_KEY'); // Substitua pela sua chave do servidor Firebase
define('FIREBASE_API_URL', 'https://fcm.googleapis.com/fcm/send');

class FirebaseNotification {
    private $serverKey;
    private $apiUrl;

    public function __construct() {
        $this->serverKey = FIREBASE_SERVER_KEY;
        $this->apiUrl = FIREBASE_API_URL;
    }

    // Enviar notificação para cliente sobre status do pedido
    public function sendOrderStatusNotification($tokenCliente, $pedidoId, $status, $mensagem) {
        $data = [
            'to' => $tokenCliente,
            'notification' => [
                'title' => 'Atualização do Pedido #' . $pedidoId,
                'body' => $mensagem,
                'sound' => 'default',
                'click_action' => 'PEDIDO_STATUS_UPDATE'
            ],
            'data' => [
                'pedido_id' => $pedidoId,
                'status' => $status
            ]
        ];
        
        return $this->sendNotification($data);
    }

    // Enviar notificação para admin sobre novo pedido
    public function sendNewOrderNotification($tokenAdmin, $pedidoId, $clienteNome) {
        $data = [
            'to' => $tokenAdmin,
            'notification' => [
                'title' => 'Novo Pedido Recebido!',
                'body' => "Pedido #$pedidoId de $clienteNome",
                'sound' => 'default',
                'click_action' => 'NOVO_PEDIDO'
            ],
            'data' => [
                'pedido_id' => $pedidoId,
                'tipo' => 'novo_pedido'
            ]
        ];
        
        return $this->sendNotification($data);
    }

    private function sendNotification($data) {
        $headers = [
            'Authorization: key=' . $this->serverKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $result = curl_exec($ch);
        
        if ($result === false) {
            throw new Exception('Erro ao enviar notificação: ' . curl_error($ch));
        }
        
        curl_close($ch);
        return json_decode($result, true);
    }
}
?>
