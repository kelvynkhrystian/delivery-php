<?php
// Previne qualquer output antes do header
ob_start();

// Previne a exibição de erros no output
ini_set('display_errors', 0);
error_reporting(0);

// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/verificar_admin.php';

try {
    // Limpa qualquer output anterior
    ob_clean();

    // Inicializa a conexão com o banco de dados
    $database = new Database();
    $db = $database->getConnection();

    // Obtém os dados da requisição
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data && $_POST) {
        $data = $_POST;
    }

    // Se for uma requisição de aparência
    if (isset($data['secao']) && $data['secao'] === 'aparencia') {
        // Validação da cor do tema
        if (isset($data['cor_tema'])) {
            $cor_tema = $data['cor_tema'];
            
            // Valida se é uma cor hexadecimal válida
            if (!preg_match('/^#[a-f0-9]{6}$/i', $cor_tema)) {
                throw new Exception('Cor inválida');
            }

            // Atualiza a cor do tema
            $query = "UPDATE configuracoes SET cor_tema = ? WHERE id = (SELECT id FROM (SELECT id FROM configuracoes ORDER BY id DESC LIMIT 1) as temp)";
            $stmt = $db->prepare($query);
            $stmt->execute([$cor_tema]);

            echo json_encode([
                'success' => true,
                'message' => 'Tema atualizado com sucesso'
            ]);
            exit;
        }
    }

    // Verifica se é um upload de imagem
    if (isset($_FILES['imagem']) && isset($_POST['tipo'])) {
        error_log('Recebendo upload de imagem: ' . print_r($_FILES['imagem'], true));
        error_log('Tipo: ' . $_POST['tipo']);
        
        $tipo = $_POST['tipo'] ?? '';
        $allowed_types = ['favicon', 'logo', 'banner', 'banner_pc'];
        
        if (!in_array($tipo, $allowed_types)) {
            throw new Exception('Tipo de imagem inválido');
        }

        try {
            $caminho = processarUploadImagem($_FILES['imagem'], $tipo);
            
            if ($caminho) {
                error_log('Upload bem sucedido. Caminho: ' . $caminho);
                // Atualiza o caminho da imagem no banco de dados
                $query = "UPDATE configuracoes SET {$tipo} = ? WHERE id = (SELECT id FROM (SELECT id FROM configuracoes ORDER BY id DESC LIMIT 1) as temp)";
                $stmt = $db->prepare($query);
                $stmt->execute([$caminho]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Imagem atualizada com sucesso',
                    'path' => $caminho
                ]);
                exit;
            } else {
                error_log('Erro no processamento do upload');
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao processar o upload da imagem'
                ]);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    // Se não for upload de imagem, verifica se tem a seção
    if (!isset($_POST['secao']) && !isset($data['secao'])) {
        throw new Exception('Seção não especificada');
    }

    // Validações básicas
    if (!isset($data['secao'])) {
        throw new Exception('Seção não especificada');
    }

    // Busca configurações atuais
    $stmt = $db->query("SELECT * FROM configuracoes ORDER BY id DESC LIMIT 1");
    $config_atual = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_config = $config_atual ? $config_atual['id'] : null;

    // Prepara os campos e valores baseado na seção
    $campos = [];
    
    switch($data['secao']) {
        case 'geral':
            if (empty($data['nome_loja'])) {
                throw new Exception('Nome da loja é obrigatório');
            }
            
            // Verifica cada campo se foi alterado
            if ($data['nome_loja'] !== ($config_atual['nome_loja'] ?? '')) {
                $campos['nome_loja'] = $data['nome_loja'];
            }
            if (($data['slogan'] ?? '') !== ($config_atual['slogan'] ?? '')) {
                $campos['slogan'] = $data['slogan'] ?? '';
            }
            if (($data['local'] ?? '') !== ($config_atual['local'] ?? '')) {
                $campos['local'] = $data['local'] ?? '';
            }
            if (floatval($data['pedido_minimo']) !== floatval($config_atual['pedido_minimo'] ?? 0)) {
                $campos['pedido_minimo'] = floatval($data['pedido_minimo']);
            }
            if (intval($data['tempo_entrega']) !== intval($config_atual['tempo_entrega'] ?? 30)) {
                $campos['tempo_entrega'] = intval($data['tempo_entrega']);
            }
            break;
            
        case 'contato':
            // Verifica cada campo se foi alterado
            if (($data['email_loja'] ?? '') !== ($config_atual['email_loja'] ?? '')) {
                $campos['email_loja'] = $data['email_loja'] ?: 'email@empresa.com';
            }
            if (($data['telefone_loja'] ?? '') !== ($config_atual['telefone_loja'] ?? '')) {
                $campos['telefone_loja'] = $data['telefone_loja'] ?? '';
            }
            if (($data['endereco_loja'] ?? '') !== ($config_atual['endereco_loja'] ?? '')) {
                $campos['endereco_loja'] = $data['endereco_loja'] ?? '';
            }
            if (($data['instagram'] ?? '') !== ($config_atual['instagram'] ?? '')) {
                $campos['instagram'] = $data['instagram'] ?? '@empresa';
            }
            break;
            
        case 'horarios':
            // Valida o status de funcionamento
            if (!in_array($data['status_funcionamento'], ['horario', 'aberto', 'fechado'])) {
                throw new Exception('Status de funcionamento inválido');
            }
            $campos['status_funcionamento'] = $data['status_funcionamento'];

            // Processa os horários
            $horarios = json_decode($data['horarios_funcionamento'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Formato de horários inválido');
            }
            $campos['horarios_funcionamento'] = json_encode($horarios);
            break;

        case 'aparencia':
            error_log('------- Alteração do Tema -------');
            error_log('Dados recebidos: ' . print_r($data, true));
            
            // Busca configuração atual do tema
            $stmt = $db->query("SELECT cor_tema FROM configuracoes ORDER BY id DESC LIMIT 1");
            $config_atual = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log('Tema atual: ' . ($config_atual['cor_tema'] ?? 'não definido'));
            
            $campos_permitidos = ['cor_tema'];
            $valores = [];
            $campos = [];

            foreach ($campos_permitidos as $campo) {
                if (isset($data[$campo])) {
                    $campos[] = $campo;
                    $valores[] = $data[$campo];
                    error_log("Campo {$campo} será atualizado para: " . $data[$campo]);
                }
            }

            if (!empty($campos)) {
                $sql_campos = implode(', ', array_map(function($campo) {
                    return "{$campo} = ?";
                }, $campos));

                $query = "UPDATE configuracoes SET {$sql_campos} WHERE id = (SELECT id FROM (SELECT id FROM configuracoes ORDER BY id DESC LIMIT 1) as temp)";
                $stmt = $db->prepare($query);
                $stmt->execute($valores);
                
                // Busca configuração atualizada para confirmar
                $stmt = $db->query("SELECT cor_tema FROM configuracoes ORDER BY id DESC LIMIT 1");
                $config_nova = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log('Nova cor do tema: ' . ($config_nova['cor_tema'] ?? 'não definido'));
                error_log('---------------------------');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Configurações de aparência atualizadas com sucesso'
            ]);
            exit;
            
        // Processa uploads de imagens
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $favicon = processarUploadImagem($_FILES['favicon'], 'favicon');
            if ($favicon) {
                $campos['favicon'] = $favicon;
            }
        }
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logo = processarUploadImagem($_FILES['logo'], 'logo');
            if ($logo) {
                $campos['logo'] = $logo;
            }
        }
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $banner = processarUploadImagem($_FILES['banner'], 'banner');
            if ($banner) {
                $campos['banner'] = $banner;
            }
        }
        if (isset($_FILES['banner_pc']) && $_FILES['banner_pc']['error'] === UPLOAD_ERR_OK) {
            $banner_pc = processarUploadImagem($_FILES['banner_pc'], 'banner_pc');
            if ($banner_pc) {
                $campos['banner_pc'] = $banner_pc;
            }
        }

        // Processa cores
        if (isset($data['cor_tema'])) {
            $campos['cor_tema'] = $data['cor_tema'];
        }
        break;
    }

    if ($_POST['secao'] === 'aparencia') {
        try {
            // Validação da cor
            $cor_tema = $_POST['cor_tema'] ?? '';
            if (empty($cor_tema)) {
                throw new Exception('Cor do tema é obrigatória');
            }

            // Verifica se já existe algum registro
            $stmt = $db->query("SELECT COUNT(*) as total FROM configuracoes");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] == 0) {
                // Se não existir nenhum registro, cria um
                $query = "INSERT INTO configuracoes (nome_loja, cor_tema) VALUES ('Minha Loja', :cor_tema)";
            } else {
                // Se existir, atualiza o último registro
                $query = "UPDATE configuracoes SET cor_tema = :cor_tema ORDER BY id DESC LIMIT 1";
            }

            $stmt = $db->prepare($query);
            $stmt->execute(['cor_tema' => $cor_tema]);

            echo json_encode([
                'success' => true,
                'message' => 'Cor do tema atualizada com sucesso'
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erro ao atualizar cor do tema: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao atualizar cor do tema: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Se não houver alterações, retorna sucesso sem fazer update
    if (empty($campos)) {
        echo json_encode([
            'success' => true,
            'message' => 'Nenhuma alteração necessária'
        ]);
        exit;
    }

    // Prepara e executa o SQL de update
    try {
        $sets = [];
        $valores = [];
        foreach ($campos as $campo => $valor) {
            $sets[] = "{$campo} = ?";
            $valores[] = $valor;
        }
        $valores[] = $id_config;

        $sql = "UPDATE configuracoes SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $db->prepare($sql);

        if ($stmt->execute($valores)) {
            // Limpa qualquer saída anterior
            while (ob_get_level()) ob_end_clean();
            
            // Define o cabeçalho JSON
            header('Content-Type: application/json');
            
            // Envia a resposta
            echo json_encode([
                'success' => true,
                'message' => 'Configurações atualizadas com sucesso'
            ]);
            exit;
        } else {
            error_log("Erro PDO: " . print_r($stmt->errorInfo(), true));
            throw new Exception('Erro ao salvar configurações');
        }
    } catch (Exception $e) {
        error_log("Erro ao salvar configurações: " . $e->getMessage());
        error_log("SQL: " . $sql);
        error_log("Valores: " . print_r($valores, true));
        throw $e;
    }
} catch (Exception $e) {
    error_log('Erro em salvar_configuracoes.php: ' . $e->getMessage());
    
    // Limpa qualquer saída anterior
    while (ob_get_level()) ob_end_clean();
    
    // Define o status code e cabeçalho JSON
    http_response_code(400);
    header('Content-Type: application/json');
    
    // Envia a resposta de erro
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

// Função para processar upload de imagens
function processarUploadImagem($file, $tipo) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro no upload do arquivo');
    }

    // Validar tipo de arquivo
    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'ico'];
    $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extensao, $extensoesPermitidas)) {
        throw new Exception('Tipo de arquivo não permitido. Use apenas: ' . implode(', ', $extensoesPermitidas));
    }

    // Validar tamanho (5MB máximo)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('O arquivo deve ter no máximo 5MB');
    }

    // Criar diretório se não existir
    $uploadDir = '../uploads/configuracoes/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Gerar nome único para o arquivo
    $nomeArquivo = uniqid($tipo . '_') . '.' . $extensao;
    $caminhoCompleto = $uploadDir . $nomeArquivo;
    $caminhoRelativo = 'uploads/configuracoes/' . $nomeArquivo;

    // Mover o arquivo
    if (!move_uploaded_file($file['tmp_name'], $caminhoCompleto)) {
        throw new Exception('Erro ao mover o arquivo');
    }

    // Retorna o caminho relativo para salvar no banco
    return $caminhoRelativo;
}
