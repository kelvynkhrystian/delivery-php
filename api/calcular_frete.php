<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

function calcularDistancia($lat1, $lon1, $lat2, $lon2) {
    $r = 6371; // Raio da Terra em km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLon/2) * sin($dLon/2);
         
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $d = $r * $c; // Distância em km
    
    return $d;
}

try {
    // Pega os dados do POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['endereco'])) {
        throw new Exception('Endereço não fornecido');
    }

    // Busca configurações da loja
    $query = "SELECT * FROM configuracoes WHERE id = 1";
    $stmt = $db->query($query);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config['google_maps_key']) {
        throw new Exception('Chave da API do Google Maps não configurada');
    }

    // Geocodifica o endereço do cliente
    $endereco = urlencode($data['endereco']);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$endereco}&key={$config['google_maps_key']}";
    
    $response = file_get_contents($url);
    $geocode = json_decode($response, true);

    if ($geocode['status'] !== 'OK') {
        throw new Exception('Não foi possível encontrar o endereço');
    }

    // Pega as coordenadas do endereço do cliente
    $lat_cliente = $geocode['results'][0]['geometry']['location']['lat'];
    $lng_cliente = $geocode['results'][0]['geometry']['location']['lng'];
    
    // Calcula a distância
    $distancia = calcularDistancia(
        $config['latitude_loja'],
        $config['longitude_loja'],
        $lat_cliente,
        $lng_cliente
    );

    // Busca a taxa de entrega baseada na distância
    $query = "SELECT valor FROM taxas_entrega 
              WHERE tipo = 'km' 
              AND km_inicial <= :distancia 
              AND km_final >= :distancia
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['distancia' => $distancia]);
    $taxa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$taxa) {
        throw new Exception('Não entregamos nesta distância');
    }

    // Retorna os dados
    echo json_encode([
        'success' => true,
        'distancia' => round($distancia, 2),
        'valor_frete' => floatval($taxa['valor']),
        'endereco_formatado' => $geocode['results'][0]['formatted_address']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
