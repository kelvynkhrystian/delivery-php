<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

session_start();

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    // Verifica se recebeu um arquivo
    if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Nenhuma imagem enviada ou erro no upload');
    }

    $file = $_FILES['imagem'];
    $fileName = $file['name'];
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Verifica o tipo do arquivo
    $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Tipo de arquivo não permitido. Use apenas jpg, jpeg, png ou webp');
    }

    // Verifica o tamanho (máximo 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Arquivo muito grande. Tamanho máximo: 5MB');
    }

    // Lê o arquivo e converte para base64
    $imageData = file_get_contents($file['tmp_name']);
    $base64Image = 'data:image/' . $fileType . ';base64,' . base64_encode($imageData);

    // Se recebeu um produto_id, atualiza no banco
    if (isset($_POST['produto_id']) && !empty($_POST['produto_id'])) {
        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("UPDATE produtos SET imagem = ? WHERE id = ?");
        $stmt->execute([$base64Image, $_POST['produto_id']]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Imagem enviada com sucesso',
        'image_data' => $base64Image
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
