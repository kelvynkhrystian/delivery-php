<?php
session_start();

// Remove o cookie remember_token se existir
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

session_destroy();
header('Location: ../index.php');
exit;
?>
