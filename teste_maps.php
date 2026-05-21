<?php
require_once 'config/database.php';

// Busca a chave da API
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT maps_api_key FROM configuracoes LIMIT 1");
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC);
$maps_api_key = $config['maps_api_key'];

if (empty($maps_api_key)) {
    die('Chave API não configurada. Configure primeiro nas configurações do sistema.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste da API do Google Maps</title>
    <style>
        #map {
            height: 400px;
            width: 100%;
            margin-bottom: 20px;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .status {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Teste da API do Google Maps</h1>
        <div id="status"></div>
        <div id="map"></div>
        <p><strong>Sua chave API:</strong> <?php echo htmlspecialchars($maps_api_key); ?></p>
    </div>

    <script>
        function initMap() {
            try {
                // Tenta criar um mapa (São Paulo como localização padrão)
                const map = new google.maps.Map(document.getElementById('map'), {
                    center: { lat: -23.5505, lng: -46.6333 },
                    zoom: 12
                });

                // Se chegou aqui, o mapa foi carregado com sucesso
                document.getElementById('status').innerHTML = 
                    '<div class="status success">' +
                    '<strong>Sucesso!</strong> A API do Google Maps está funcionando corretamente.' +
                    '</div>';

            } catch (error) {
                document.getElementById('status').innerHTML = 
                    '<div class="status error">' +
                    '<strong>Erro!</strong> ' + error.message +
                    '</div>';
            }
        }

        function handleError() {
            document.getElementById('status').innerHTML = 
                '<div class="status error">' +
                '<strong>Erro!</strong> Não foi possível carregar a API do Google Maps. ' +
                'Verifique se sua chave API está correta e se tem as APIs necessárias ativadas.' +
                '</div>';
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo urlencode($maps_api_key); ?>&callback=initMap" 
            async 
            defer
            onerror="handleError()"></script>
</body>
</html>
