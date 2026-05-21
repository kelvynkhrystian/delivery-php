<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['admin'])) {
        throw new Exception('Acesso não autorizado');
    }

    $database = new Database();
    $db = $database->getConnection();
    
    // Inicia uma transação
    $db->beginTransaction();

    // Dados básicos do produto
    $id = $_POST['id'] ?? null;
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = str_replace(',', '.', $_POST['preco']);
    $categoria_id = $_POST['categoria_id'];
    $ativo = $_POST['ativo'];
    $tem_complementos = $_POST['tem_complementos'] ?? 0;

    // Validações básicas
    if (empty($nome)) {
        throw new Exception('Nome do produto é obrigatório');
    }

    if (!is_numeric($preco) || $preco < 0) {
        throw new Exception('Preço inválido');
    }

    // Processa a imagem
    $imagem = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['imagem']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            throw new Exception('Formato de imagem não permitido');
        }

        $upload_dir = '../uploads/produtos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_filename = uniqid() . '.' . $ext;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $upload_path)) {
            $imagem = 'uploads/produtos/' . $new_filename;

            // Se estiver editando, remove a imagem antiga
            if ($id) {
                $query = "SELECT imagem FROM produtos WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                $produto = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($produto && $produto['imagem']) {
                    $old_image = '../' . $produto['imagem'];
                    if (file_exists($old_image)) {
                        unlink($old_image);
                    }
                }
            }
        }
    }

    // Salva ou atualiza o produto
    if ($id) {
        $query = "UPDATE produtos SET nome = ?, descricao = ?, preco = ?, categoria_id = ?, ativo = ?, tem_complementos = ? ";
        $params = [$nome, $descricao, $preco, $categoria_id, $ativo, $tem_complementos];
        
        if ($imagem) {
            $query .= ", imagem = ? ";
            $params[] = $imagem;
        }
        
        $query .= "WHERE id = ?";
        $params[] = $id;

        $stmt = $db->prepare($query);
        $stmt->execute($params);
    } else {
        $query = "INSERT INTO produtos (nome, descricao, preco, categoria_id, ativo, tem_complementos, imagem) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$nome, $descricao, $preco, $categoria_id, $ativo, $tem_complementos, $imagem]);
        $id = $db->lastInsertId();
    }

    // Se tem complementos, processa eles
    if ($tem_complementos) {
        // Remove complementos antigos se estiver editando
        if ($id) {
            $stmt = $db->prepare("DELETE FROM produto_complementos WHERE produto_id = ?");
            $stmt->execute([$id]);
        }

        // Processa os novos complementos
        if (isset($_POST['complementos']) && is_array($_POST['complementos'])) {
            foreach ($_POST['complementos'] as $comp) {
                // Insere o complemento
                $stmt = $db->prepare("INSERT INTO complementos (nome, descricao, min_escolhas, max_escolhas, obrigatorio) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $comp['nome'],
                    $comp['descricao'],
                    $comp['min_escolhas'],
                    $comp['max_escolhas'],
                    isset($comp['obrigatorio']) ? 1 : 0
                ]);
                $complemento_id = $db->lastInsertId();

                // Relaciona com o produto
                $stmt = $db->prepare("INSERT INTO produto_complementos (produto_id, complemento_id) VALUES (?, ?)");
                $stmt->execute([$id, $complemento_id]);

                // Insere as opções do complemento
                if (isset($comp['opcoes']) && is_array($comp['opcoes'])) {
                    foreach ($comp['opcoes'] as $opcao) {
                        $stmt = $db->prepare("INSERT INTO complemento_opcoes (complemento_id, nome, preco) VALUES (?, ?, ?)");
                        $stmt->execute([
                            $complemento_id,
                            $opcao['nome'],
                            $opcao['preco']
                        ]);
                    }
                }
            }
        }
    }

    // Confirma todas as alterações
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => $id ? 'Produto atualizado com sucesso!' : 'Produto cadastrado com sucesso!'
    ]);

} catch (Exception $e) {
    // Reverte todas as alterações em caso de erro
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
