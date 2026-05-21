<?php
session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Busca as configurações do Maps
    $stmt = $db->prepare("SELECT maps_api_key, maps_latitude, maps_longitude, maps_endereco, maps_raio_entrega FROM configuracoes");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($config) {
        echo json_encode([
            'success' => true,
            'api_key' => $config['maps_api_key'],
            'latitude' => $config['maps_latitude'],
            'longitude' => $config['maps_longitude'],
            'endereco' => $config['maps_endereco'],
            'raio_entrega' => $config['maps_raio_entrega']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'api_key' => '',
            'latitude' => '',
            'longitude' => '',
            'endereco' => '',
            'raio_entrega' => ''
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar configurações: ' . $e->getMessage()
    ]);
}
