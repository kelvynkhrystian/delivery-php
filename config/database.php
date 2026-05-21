<?php
class Database {

    private $host = "localhost";
    private $db_name = "u190137270_joaodedeus";
    private $username = "u190137270_joaodedeus";
    private $password = "1?s0JjKqR?j";

    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Define fuso horário no PHP
            date_default_timezone_set('America/Sao_Paulo');

            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                )
            );

            // Define fuso horário na sessão do MySQL
            $this->conn->exec("SET time_zone = '-03:00'");

            return $this->conn;
        } catch(PDOException $e) {
            // Limpa qualquer saída anterior
            while (ob_get_level()) ob_end_clean();
            
            // Log do erro
            error_log("Erro de conexão com banco de dados: " . $e->getMessage());
            
            // Define o status code e cabeçalho JSON
            http_response_code(500);
            header('Content-Type: application/json');
            
            // Envia resposta de erro em JSON
            echo json_encode([
                'success' => false,
                'message' => 'Erro de conexão com o banco de dados'
            ]);
            exit;
        }
    }
}
?>
