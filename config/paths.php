<?php
// Detecta se está em produção ou desenvolvimento
function getBasePath() {
    $server_name = $_SERVER['SERVER_NAME'];
    if ($server_name === 'localhost' || $server_name === '127.0.0.1') {
        return '/gestao';
    }
    return '';
}

// Define a constante BASE_PATH
define('BASE_PATH', getBasePath());
