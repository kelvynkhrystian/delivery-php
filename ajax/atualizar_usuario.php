<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

$database = new Database();
$conexao = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'];
$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$telefone = $_POST['telefone'] ?? '';
$senha = $_POST['senha'] ?? '';

// Dados do endereço
$cep = $_POST['cep'] ?? '';
$logradouro = $_POST['logradouro'] ?? '';
$numero = $_POST['numero'] ?? '';
$complemento = $_POST['complemento'] ?? '';
$bairro = $_POST['bairro'] ?? '';
$cidade = $_POST['cidade'] ?? 'São Luís';
$estado = $_POST['estado'] ?? 'MA';
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;

// Monta o endereço completo em JSON
$endereco = json_encode([
    'cep' => $cep,
    'logradouro' => $logradouro,
    'numero' => $numero,
    'complemento' => $complemento,
    'bairro' => $bairro,
    'cidade' => $cidade,
    'estado' => $estado,
    'latitude' => $latitude,
    'longitude' => $longitude
]);

if (empty($nome) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Nome e e-mail são obrigatórios']);
    exit;
}

try {
    // Verifica se o e-mail já existe para outro usuário
    $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->execute([$email, $usuario_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'error' => 'Este e-mail já está em uso']);
        exit;
    }

    // Atualiza os dados do usuário
    if (!empty($senha)) {
        $senha_hash = md5($senha);
        $stmt = $conexao->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, senha = ?, endereco = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $telefone, $senha_hash, $endereco, $usuario_id]);
    } else {
        $stmt = $conexao->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, endereco = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $telefone, $endereco, $usuario_id]);
    }

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        // Se nenhuma linha foi afetada, pode ser porque os dados são os mesmos
        echo json_encode(['success' => true, 'message' => 'Nenhuma alteração necessária']);
    }

} catch (Exception $e) {
    error_log("Erro ao atualizar usuário: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar dados: ' . $e->getMessage()]);
}
