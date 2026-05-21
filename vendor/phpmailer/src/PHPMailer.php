<?php
namespace PHPMailer\PHPMailer;

class PHPMailer {
    public $Host = '';
    public $Port = 25;
    public $Username = '';
    public $Password = '';
    public $CharSet = 'utf-8';
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $SMTPAuth = false;
    public $SMTPSecure = '';
    public $SMTPDebug = 0;
    public $Debugoutput = 'echo';
    private $to = [];
    private $smtp = null;
    private $error_info = '';
    
    public function isSMTP() {
        require_once __DIR__ . '/SMTP.php';
        $this->smtp = new SMTP();
    }
    
    public function setFrom($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
    }
    
    public function addAddress($address) {
        $this->to[] = $address;
    }
    
    public function clearAddresses() {
        $this->to = [];
    }
    
    public function isHTML($isHtml) {
        // Não precisamos implementar isso para texto simples
    }
    
    public function send() {
        if ($this->smtp) {
            try {
                if (!$this->smtp->connect($this->Host, $this->Port)) {
                    throw new Exception('Não foi possível conectar ao servidor SMTP');
                }
                
                if ($this->SMTPAuth) {
                    if (!$this->smtp->authenticate($this->Username, $this->Password)) {
                        throw new Exception('Autenticação SMTP falhou');
                    }
                }
                
                foreach ($this->to as $to) {
                    if (!$this->smtp->send($this->From, $to, $this->formatMessage())) {
                        throw new Exception('Erro ao enviar email');
                    }
                }
                
                $this->smtp->close();
                return true;
            } catch (Exception $e) {
                $this->error_info = $e->getMessage();
                return false;
            }
        } else {
            $headers = $this->formatHeaders();
            foreach ($this->to as $to) {
                if (!mail($to, $this->Subject, $this->Body, $headers)) {
                    return false;
                }
            }
            return true;
        }
    }
    
    private function formatHeaders() {
        $headers = [];
        $headers[] = "From: {$this->FromName} <{$this->From}>";
        $headers[] = "Reply-To: {$this->From}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/plain; charset={$this->CharSet}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        return implode("\r\n", $headers);
    }
    
    private function formatMessage() {
        $message = [];
        $message[] = "From: {$this->FromName} <{$this->From}>";
        $message[] = "To: %s"; // SMTP class vai substituir
        $message[] = "Subject: {$this->Subject}";
        $message[] = "MIME-Version: 1.0";
        $message[] = "Content-Type: text/plain; charset={$this->CharSet}";
        $message[] = "";
        $message[] = $this->Body;
        return implode("\r\n", $message);
    }
    
    public function __get($name) {
        if ($name === 'ErrorInfo') {
            return $this->error_info;
        }
    }
}
