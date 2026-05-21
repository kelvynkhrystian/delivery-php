<?php
session_start();

// Função para verificar se o usuário está logado como admin
function verificarAdmin() {
    if (!isset($_SESSION['admin'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Acesso não autorizado'
        ]);
        exit;
    }
    return true;
}

// Executa a verificação
verificarAdmin();
