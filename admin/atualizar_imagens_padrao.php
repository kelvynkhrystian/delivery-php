<?php
require_once '../conexao.php';

try {
    // Define os caminhos das imagens existentes
    $imagens = [
        'logo' => 'assets/img/logo.png',
        'banner' => 'assets/img/banner.png',
        'favicon' => 'assets/img/favicon.svg'
    ];

    // Verifica se as imagens existem
    foreach ($imagens as $tipo => $caminho) {
        if (!file_exists($caminho)) {
            throw new Exception("Arquivo $caminho não encontrado");
        }
    }

    // Atualiza o banco de dados
    $sql = "UPDATE configuracoes SET logo = :logo, banner = :banner";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':logo' => $imagens['logo'],
        ':banner' => $imagens['banner']
    ]);

    echo "Imagens atualizadas com sucesso no banco de dados!";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
