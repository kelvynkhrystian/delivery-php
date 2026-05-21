<?php
session_start();

// Recebe os dados
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['redirect'])) {
    $_SESSION['redirect_after_login'] = $data['redirect'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'URL de redirecionamento não fornecida']);
}
