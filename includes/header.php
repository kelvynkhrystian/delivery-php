<?php
// Busca a cor do tema atual
require_once __DIR__ . '/../config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->query("SELECT cor_tema FROM configuracoes ORDER BY id DESC LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    $corTema = $config['cor_tema'] ?? '#8B5CF6'; // Roxo como cor padrão
} catch (PDOException $e) {
    $corTema = '#8B5CF6';
    error_log("Erro ao buscar cor do tema: " . $e->getMessage());
}
?>
<style>
    :root {
        --theme-color: <?php echo $corTema; ?>;
    }

    .theme-primary {
        background-color: var(--theme-color) !important;
    }

    .text-theme {
        color: var(--theme-color) !important;
    }

    .bg-theme\/10 {
        background-color: <?php echo $corTema; ?>1A !important;
    }
</style>
