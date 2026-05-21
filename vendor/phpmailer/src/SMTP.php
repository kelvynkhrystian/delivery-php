<?php
namespace PHPMailer\PHPMailer;

class SMTP {
    private $connection;
    private $error = '';
    private $debug = false;
    private $do_debug = 0;
    private $last_response = '';
    
    public function connect($host, $port = null) {
        // Conexão inicial sem SSL/TLS
        $this->connection = @fsockopen($host, $port, $errno, $errstr, 30);
        if (!$this->connection) {
            $this->error = "Conexão falhou: $errstr ($errno)";
            if ($this->do_debug >= 1) {
                echo "ERRO: " . $this->error . "\n";
            }
            return false;
        }
        
        if ($this->do_debug >= 1) {
            echo "Conectado a $host:$port\n";
        }
        
        // Lê resposta inicial
        $response = $this->getResponse();
        if ($response === false) {
            return false;
        }
        
        // Envia EHLO
        if (!$this->sendCommand("EHLO " . gethostname())) {
            return false;
        }
        
        // Inicia TLS
        if (!$this->startTLS()) {
            return false;
        }
        
        // Envia EHLO novamente após TLS
        if (!$this->sendCommand("EHLO " . gethostname())) {
            return false;
        }
        
        return true;
    }
    
    private function startTLS() {
        if (!$this->sendCommand("STARTTLS")) {
            return false;
        }
        
        // Ativa criptografia TLS
        if (!stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $this->error = "Falha ao ativar criptografia TLS";
            if ($this->do_debug >= 1) {
                echo "ERRO: " . $this->error . "\n";
            }
            return false;
        }
        
        if ($this->do_debug >= 1) {
            echo "TLS ativado com sucesso\n";
        }
        
        return true;
    }
    
    public function authenticate($username, $password) {
        // Inicia autenticação
        if (!$this->sendCommand("AUTH LOGIN")) {
            if ($this->do_debug >= 1) {
                echo "ERRO: Falha ao iniciar autenticação\n";
            }
            return false;
        }
        
        // Envia usuário em base64
        if (!$this->sendCommand(base64_encode($username))) {
            if ($this->do_debug >= 1) {
                echo "ERRO: Falha ao enviar usuário\n";
            }
            return false;
        }
        
        // Envia senha em base64
        if (!$this->sendCommand(base64_encode($password))) {
            if ($this->do_debug >= 1) {
                echo "ERRO: Falha ao enviar senha\n";
            }
            return false;
        }
        
        return true;
    }
    
    public function send($from, $to, $message) {
        // MAIL FROM
        if (!$this->sendCommand("MAIL FROM:<$from>")) {
            return false;
        }
        
        // RCPT TO
        if (!$this->sendCommand("RCPT TO:<$to>")) {
            return false;
        }
        
        // DATA
        if (!$this->sendCommand("DATA")) {
            return false;
        }
        
        // Envia a mensagem
        $message = str_replace("\r\n.", "\r\n..", $message);
        if (!$this->sendCommand($message . "\r\n.")) {
            return false;
        }
        
        return true;
    }
    
    public function close() {
        if ($this->connection) {
            $this->sendCommand("QUIT");
            fclose($this->connection);
        }
    }
    
    private function sendCommand($command) {
        if ($this->do_debug >= 2) {
            echo "CLIENT -> SERVER: $command\n";
        }
        
        fputs($this->connection, $command . "\r\n");
        return $this->getResponse();
    }
    
    private function getResponse() {
        $response = '';
        while ($line = fgets($this->connection, 515)) {
            if ($this->do_debug >= 1) {
                echo "SERVER -> CLIENT: $line";
            }
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        
        $this->last_response = $response;
        $code = substr($response, 0, 3);
        
        if (!$code || $code < 200 || $code >= 400) {
            $this->error = $response;
            if ($this->do_debug >= 1) {
                echo "ERRO: Código $code - $response\n";
            }
            return false;
        }
        
        return true;
    }
    
    public function setDebug($level) {
        $this->do_debug = $level;
    }
    
    public function getError() {
        return $this->error;
    }
    
    public function getLastResponse() {
        return $this->last_response;
    }
}
