<?php
session_start();

// Verifica se o usuário está logado como administrador
if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['id'])) {
    header('Location: login.php');
    exit;
}
?>
