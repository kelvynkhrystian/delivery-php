<?php
session_start();
require_once '../../config/database.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Processa o upload da logo se houver
    $logo_url = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/logos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Tipo de arquivo não permitido. Use apenas jpg, jpeg, png ou gif.');
        }

        $filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
            $logo_url = 'uploads/logos/' . $filename;
        } else {
            throw new Exception('Erro ao fazer upload da logo');
        }
    }

    // Prepara os dados para atualização
    $config = [
        'nome_loja' => $_POST['nome_loja'] ?? '',
        'slogan' => $_POST['slogan'] ?? '',
        'descricao' => $_POST['descricao'] ?? '',
        'endereco' => $_POST['endereco'] ?? '',
        'telefone' => $_POST['telefone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'horario_funcionamento' => $_POST['horario_funcionamento'] ?? '',
        'tipo_entrega' => $_POST['tipo_entrega'] ?? '',
        'google_maps_key' => $_POST['google_maps_key'] ?? '',
        'endereco_loja' => $_POST['endereco_loja'] ?? '',
        'latitude_loja' => $_POST['latitude_loja'] ?? '',
        'longitude_loja' => $_POST['longitude_loja'] ?? '',
        'taxa_entrega_km' => $_POST['taxa_entrega_km'] ?? 0.00,
        'cor_primaria' => $_POST['cor_primaria'] ?? '#3B82F6',
        'cor_secundaria' => $_POST['cor_secundaria'] ?? '#1E40AF'
    ];

    // Adiciona a logo_url se foi feito upload
    if ($logo_url) {
        $config['logo_url'] = $logo_url;
    }

    // Atualiza as configurações
    $sql = "UPDATE configuracoes SET 
            nome_loja = :nome_loja,
            slogan = :slogan,
            descricao = :descricao,
            endereco = :endereco,
            telefone = :telefone,
            email = :email,
            horario_funcionamento = :horario_funcionamento,
            tipo_entrega = :tipo_entrega,
            google_maps_key = :google_maps_key,
            endereco_loja = :endereco_loja,
            latitude_loja = :latitude_loja,
            longitude_loja = :longitude_loja,
            taxa_entrega_km = :taxa_entrega_km,
            cor_primaria = :cor_primaria,
            cor_secundaria = :cor_secundaria
            WHERE id = 1";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':nome_loja', $config['nome_loja']);
    $stmt->bindParam(':slogan', $config['slogan']);
    $stmt->bindParam(':descricao', $config['descricao']);
    $stmt->bindParam(':endereco', $config['endereco']);
    $stmt->bindParam(':telefone', $config['telefone']);
    $stmt->bindParam(':email', $config['email']);
    $stmt->bindParam(':horario_funcionamento', $config['horario_funcionamento']);
    $stmt->bindParam(':tipo_entrega', $config['tipo_entrega']);
    $stmt->bindParam(':google_maps_key', $config['google_maps_key']);
    $stmt->bindParam(':endereco_loja', $config['endereco_loja']);
    $stmt->bindParam(':latitude_loja', $config['latitude_loja']);
    $stmt->bindParam(':longitude_loja', $config['longitude_loja']);
    $stmt->bindParam(':taxa_entrega_km', $config['taxa_entrega_km']);
    $stmt->bindParam(':cor_primaria', $config['cor_primaria']);
    $stmt->bindParam(':cor_secundaria', $config['cor_secundaria']);

    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Configurações salvas com sucesso'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar configurações: ' . $e->getMessage()
    ]);
}
