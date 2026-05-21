<?php
error_reporting(0); // Desativa a exibição de erros
ini_set('display_errors', 0); // Garante que os erros não serão mostrados

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Verifica se recebeu dados JSON válidos
    $json = file_get_contents('php://input');
    if (!$json) {
        throw new Exception('Nenhum dado recebido');
    }
    
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Dados JSON inválidos');
    }
    
    if (!isset($data['latitude']) || !isset($data['longitude'])) {
        throw new Exception('Coordenadas não fornecidas');
    }
    
    // Validar se as coordenadas são números válidos
    if (!is_numeric($data['latitude']) || !is_numeric($data['longitude'])) {
        throw new Exception('Coordenadas inválidas');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar coordenadas da loja
    $query = "SELECT maps_latitude, maps_longitude, maps_raio_entrega FROM configuracoes LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config || !$config['maps_latitude'] || !$config['maps_longitude']) {
        throw new Exception('Coordenadas da loja não configuradas');
    }
    
    // Calcula a distância usando a fórmula de Haversine
    $lat1 = deg2rad((float)$config['maps_latitude']);
    $lon1 = deg2rad((float)$config['maps_longitude']);
    $lat2 = deg2rad((float)$data['latitude']);
    $lon2 = deg2rad((float)$data['longitude']);
    
    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;
    
    $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distancia = 6371 * $c; // Raio da Terra em km
    
    // Verificar se está dentro do raio de entrega
    if ($config['maps_raio_entrega'] > 0 && $distancia > $config['maps_raio_entrega']) {
        echo json_encode([
            'success' => false,
            'message' => 'Endereço fora do raio de entrega',
            'distancia' => round($distancia, 1),
            'raio_entrega' => $config['maps_raio_entrega']
        ]);
        exit;
    }
    
    // Buscar faixa de distância apropriada
    $query = "SELECT * FROM faixas_distancia WHERE ? BETWEEN inicio AND fim AND ativo = 1 ORDER BY valor ASC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$distancia]);
    $faixa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$faixa) {
        echo json_encode([
            'success' => false,
            'message' => 'Não há faixa de entrega configurada para esta distância',
            'distancia' => round($distancia, 1)
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'valor_frete' => $faixa['valor'],
        'distancia' => round($distancia, 1)
    ]);
    
} catch (Exception $e) {
    error_log("Erro no cálculo de frete: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
