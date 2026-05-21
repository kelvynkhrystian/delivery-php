<?php
// Definir timezone para Brasília
date_default_timezone_set('America/Sao_Paulo');

// Se for uma requisição AJAX, não inclui o arquivo database.php
if (!defined('STDIN') && !isset($db)) {
    require_once __DIR__ . '/../config/database.php';
}

function verificarHorarioFuncionamento() {
    try {
        $database = new Database();
        $db = $database->getConnection();

        // Busca configurações
        $stmt = $db->query("SELECT * FROM configuracoes ORDER BY id DESC LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            return [
                'aberto' => false,
                'status' => 'error',
                'mensagem' => 'Erro ao carregar configurações'
            ];
        }

        // Se não tiver status_funcionamento definido, assume horário
        $status = $config['status_funcionamento'] ?? 'horario';

        // Se estiver configurado como sempre aberto
        if ($status === 'aberto') {
            return [
                'aberto' => true,
                'status' => 'open',
                'mensagem' => 'ABERTO'
            ];
        }

        // Se estiver configurado como sempre fechado
        if ($status === 'fechado') {
            return [
                'aberto' => false,
                'status' => 'closed',
                'mensagem' => 'FECHADO'
            ];
        }

        // Se estiver configurado para seguir horários
        // Pega o horário de funcionamento
        $horarios = json_decode($config['horarios_funcionamento'] ?? '{}', true);
        
        if (empty($horarios)) {
            // Se não houver horários definidos, assume fechado
            return [
                'aberto' => false,
                'status' => 'closed',
                'mensagem' => 'FECHADO'
            ];
        }

        // Pega o dia atual usando date()
        $dia_semana = date('N'); // 1 (Segunda) até 7 (Domingo)
        
        // Mapeia o número do dia para o nome em português
        $dias = [
            1 => 'segunda',
            2 => 'terca',
            3 => 'quarta',
            4 => 'quinta',
            5 => 'sexta',
            6 => 'sabado',
            7 => 'domingo'
        ];
        
        $dia = $dias[$dia_semana] ?? '';
        
        // Se não tiver horário para hoje ou dia não encontrado
        if (empty($dia) || empty($horarios[$dia]) || empty($horarios[$dia]['inicio']) || empty($horarios[$dia]['fim'])) {
            return [
                'aberto' => false,
                'status' => 'closed',
                'mensagem' => 'FECHADO'
            ];
        }

        // Pega hora atual
        $agora = new DateTime();
        $hora_atual = $agora->format('H:i');
        
        // Converte horários para DateTime
        $hora_inicio = DateTime::createFromFormat('H:i', $horarios[$dia]['inicio']);
        $hora_fim = DateTime::createFromFormat('H:i', $horarios[$dia]['fim']);
        $hora_agora = DateTime::createFromFormat('H:i', $hora_atual);
        
        // Verifica se está dentro do horário
        if ($hora_agora >= $hora_inicio && $hora_agora <= $hora_fim) {
            return [
                'aberto' => true,
                'status' => 'open',
                'mensagem' => 'ABERTO'
            ];
        }

        return [
            'aberto' => false,
            'status' => 'closed',
            'mensagem' => 'FECHADO'
        ];
    } catch (Exception $e) {
        error_log("Erro ao verificar horário: " . $e->getMessage());
        return [
            'aberto' => false,
            'status' => 'error',
            'mensagem' => 'Erro ao verificar horário'
        ];
    }
}

// Função para retornar apenas o status atual
function getStatusLoja() {
    header('Content-Type: application/json');
    echo json_encode(verificarHorarioFuncionamento());
    exit;
}

// Se for uma requisição AJAX direta ao arquivo
if (!defined('STDIN') && basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('Content-Type: application/json');
    echo json_encode(verificarHorarioFuncionamento());
    exit;
}
