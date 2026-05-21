<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, nome, descricao, preco, imagem, categoria, disponivel FROM produtos";
$stmt = $db->prepare($query);
$stmt->execute();

if($stmt->rowCount() > 0) {
    $produtos_arr = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $produto_item = array(
            "id" => $id,
            "nome" => $nome,
            "descricao" => $descricao,
            "preco" => $preco,
            "imagem" => $imagem,
            "categoria" => $categoria,
            "disponivel" => $disponivel
        );
        array_push($produtos_arr, $produto_item);
    }
    http_response_code(200);
    echo json_encode($produtos_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "Nenhum produto encontrado."));
}
?>
