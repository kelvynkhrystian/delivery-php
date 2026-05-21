<?php
function sendNotification($pedidoId) {
    $fields = array(
        'app_id' => "SEU_APP_ID",
        'included_segments' => array('All'),
        'contents' => array(
            "en" => "Novo pedido #" . $pedidoId . " recebido!"
        ),
        'url' => '/gestao/admin/gerenciar_pedidos.php'
    );

    $fields = json_encode($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json; charset=utf-8",
        "Authorization: Basic SEU_REST_API_KEY" // Você vai pegar isso no OneSignal
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
