<?php
session_start();
require_once '../config/database.php';

// Garante que a saída será sempre JSON
header('Content-Type: application/json');

// Ativa o relatório de erros
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    if (!isset($_SESSION['admin'])) {
        throw new Exception('Acesso não autorizado');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    $database = new Database();
    $db = $database->getConnection();

    $tipo = $_POST['tipo'] ?? '';
    $allowed_types = [
        'favicon' => ['image/x-icon', 'image/vnd.microsoft.icon', 'image/ico', 'image/png', 'image/svg+xml'],
        'logo' => ['image/jpeg', 'image/png', 'image/svg+xml'],
        'banner' => ['image/jpeg', 'image/png']
    ];

    if (!isset($allowed_types[$tipo])) {
        throw new Exception('Tipo de imagem inválido');
    }

    if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro no upload do arquivo: ' . ($_FILES['imagem']['error'] ?? 'Arquivo não enviado'));
    }

    $file = $_FILES['imagem'];
    $file_type = mime_content_type($file['tmp_name']);

    if (!in_array($file_type, $allowed_types[$tipo])) {
        throw new Exception('Tipo de arquivo não permitido. Tipo detectado: ' . $file_type);
    }

    // Criar diretório se não existir
    $upload_dir = '../assets/img/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Não foi possível criar o diretório de upload');
        }
    }

    // Gerar nome único para o arquivo
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = $tipo . '_' . uniqid() . '.' . $extension;
    $target_path = $upload_dir . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception('Erro ao mover o arquivo');
    }

    // Atualizar o caminho no banco de dados
    $relative_path = 'assets/img/' . $new_filename;
    $stmt = $db->prepare("UPDATE configuracoes SET {$tipo} = ? WHERE id = 1");
    
    if (!$stmt->execute([$relative_path])) {
        // Se falhar o update, remove o arquivo que foi enviado
        @unlink($target_path);
        throw new Exception('Erro ao salvar no banco de dados');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Imagem atualizada com sucesso',
        'path' => $relative_path
    ]);

} catch (Exception $e) {
    error_log("Erro em salvar_imagem.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
