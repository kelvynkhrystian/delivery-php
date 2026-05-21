<?php
header('Content-Type: application/json');

require_once 'conexao.php';

function getDiaAtual() {
    $dias = [
        'Sunday' => 'domingo',
        'Monday' => 'segunda',
        'Tuesday' => 'terca',
        'Wednesday' => 'quarta',
        'Thursday' => 'quinta',
        'Friday' => 'sexta',
        'Saturday' => 'sabado'
    ];
    return $dias[date('l')];
}

function verificarHorario($config) {
    $diaAtual = getDiaAtual();
    $horaAtual = date('H:i');
    
    $horarioInicio = $config[$diaAtual . '_inicio'];
    $horarioFim = $config[$diaAtual . '_fim'];
    
    if (empty($horarioInicio) || empty($horarioFim)) {
        return false;
    }
    
    return ($horaAtual >= $horarioInicio && $horaAtual <= $horarioFim);
}

try {
    $sql = "SELECT * FROM configuracoes LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    $response = [
        'status' => 'fechado',
        'mensagem' => '',
        'horarios' => []
    ];

    // Verifica o status da loja
    switch ($config['status_loja']) {
        case 'aberto':
            $response['status'] = 'aberto';
            break;
            
        case 'fechado':
            $response['status'] = 'fechado';
            $response['mensagem'] = 'Estamos fechados por hoje.';
            break;
            
        case 'horario':
            if (verificarHorario($config)) {
                $response['status'] = 'aberto';
            } else {
                $response['status'] = 'fechado';
                $response['mensagem'] = 'Estamos fechados no momento.';
                
                // Adiciona horários de funcionamento
                $dias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
                foreach ($dias as $dia) {
                    if (!empty($config[$dia . '_inicio']) && !empty($config[$dia . '_fim'])) {
                        $response['horarios'][] = [
                            'dia' => ucfirst($dia) . '-feira',
                            'horario' => $config[$dia . '_inicio'] . ' às ' . $config[$dia . '_fim']
                        ];
                    }
                }
            }
            break;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao verificar status da loja: ' . $e->getMessage()]);
}
