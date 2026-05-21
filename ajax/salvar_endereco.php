<?php
session_start();
require_once '../config/database.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Define JSON como padrão de retorno para AJAX
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

// Verifica autenticação
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id'])) {
    if ($isAjax) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuário não autenticado']);
    } else {
        $_SESSION['mensagem'] = 'Usuário não autenticado';
        $_SESSION['mensagem_tipo'] = 'erro';
        header('Location: ../conta.php');
    }
    exit;
}

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
    } else {
        $_SESSION['mensagem'] = 'Método não permitido';
        $_SESSION['mensagem_tipo'] = 'erro';
        header('Location: ../conta.php');
    }
    exit;
}

// Captura POST e também JSON cru (se for enviado assim)
$raw = file_get_contents('php://input');
$json = json_decode($raw, true);

$dados = $_POST ?: [];
if (is_array($json)) {
    $dados = array_merge($dados, $json);
}

// Normaliza e remove espaços extras
$dados['logradouro']  = trim($dados['logradouro']  ?? ($dados['endereco']['logradouro'] ?? ''));
$dados['numero']      = trim($dados['numero']      ?? ($dados['endereco']['numero'] ?? ''));
$dados['complemento'] = trim($dados['complemento'] ?? ($dados['endereco']['complemento'] ?? ''));
$dados['bairro']      = trim($dados['bairro']      ?? ($dados['bairro_endereco'] ?? ($dados['endereco']['bairro'] ?? '')));
$dados['cidade']      = trim($dados['cidade']      ?? ($dados['endereco']['cidade'] ?? ''));
$dados['estado']      = trim($dados['estado']      ?? ($dados['endereco']['estado'] ?? ''));
$dados['cep']         = trim($dados['cep']         ?? ($dados['endereco']['cep'] ?? ''));
$dados['latitude']    = trim($dados['latitude']    ?? ($dados['endereco']['latitude'] ?? ''));
$dados['longitude']   = trim($dados['longitude']   ?? ($dados['endereco']['longitude'] ?? ''));

// Campos obrigatórios
$required_fields = ['logradouro', 'numero', 'bairro', 'cidade', 'estado', 'cep', 'latitude', 'longitude'];
foreach ($required_fields as $field) {
    if ($dados[$field] === '') {
        if ($isAjax) {
            http_response_code(400);
            echo json_encode(['error' => "Campo {$field} é obrigatório"]);
        } else {
            $_SESSION['mensagem'] = "Campo {$field} é obrigatório";
            $_SESSION['mensagem_tipo'] = 'erro';
            header('Location: ../conta.php');
        }
        exit;
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $db->beginTransaction();
    
    if (!empty($dados['endereco_id'])) {
        // Atualiza endereço existente
        $stmt = $db->prepare("
            UPDATE enderecos_usuario 
            SET logradouro = ?, 
                numero = ?, 
                complemento = ?, 
                bairro = ?, 
                cidade = ?, 
                estado = ?, 
                cep = ?,
                latitude = ?,
                longitude = ?
            WHERE id = ? AND usuario_id = ?
        ");
        
        $stmt->execute([
            $dados['logradouro'],
            $dados['numero'],
            $dados['complemento'] ?? '',
            $dados['bairro'],
            $dados['cidade'],
            $dados['estado'],
            $dados['cep'],
            $dados['latitude'],
            $dados['longitude'],
            $dados['endereco_id'],
            $_SESSION['usuario']['id']
        ]);
        
        $_SESSION['mensagem'] = 'Endereço atualizado com sucesso!';
    } else {
        // Insere novo endereço
        $stmt = $db->prepare("
            INSERT INTO enderecos_usuario 
            (usuario_id, logradouro, numero, complemento, bairro, cidade, estado, cep, latitude, longitude, principal) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Se for o primeiro, marca como principal
        $stmt_count = $db->prepare("SELECT COUNT(*) FROM enderecos_usuario WHERE usuario_id = ?");
        $stmt_count->execute([$_SESSION['usuario']['id']]);
        $is_first = $stmt_count->fetchColumn() == 0;
        
        $stmt->execute([
            $_SESSION['usuario']['id'],
            $dados['logradouro'],
            $dados['numero'],
            $dados['complemento'] ?? '',
            $dados['bairro'],
            $dados['cidade'],
            $dados['estado'],
            $dados['cep'],
            $dados['latitude'],
            $dados['longitude'],
            $is_first ? 1 : 0
        ]);
        
        $_SESSION['mensagem'] = 'Endereço adicionado com sucesso!';
    }
    
    $db->commit();
    $_SESSION['mensagem_tipo'] = 'sucesso';
    
    if ($isAjax) {
        echo json_encode(['ok' => true, 'message' => $_SESSION['mensagem']]);
    } else {
        header('Location: ../conta.php');
    }
    exit;
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['mensagem'] = 'Erro ao salvar endereço';
    $_SESSION['mensagem_tipo'] = 'erro';
    
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['error' => $_SESSION['mensagem']]);
    } else {
        header('Location: ../conta.php');
    }
    exit;
}
