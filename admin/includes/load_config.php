<?php
if (!function_exists('carregarConfiguracoes')) {
    function carregarConfiguracoes($db = null) {
        if (!$db) {
            require_once '../config/database.php';
            $database = new Database();
            $db = $database->getConnection();
        }

        // Define todos os campos possíveis com valores padrão
        $campos_padrao = [
            'nome_loja' => 'Erro - Verifique o BD',
            'email_loja' => 'email@empresa.com',
            'telefone_loja' => 'Erro - Verifique o BD',
            'horario_funcionamento' => 'Erro - Verifique o BD',
            'pedido_minimo' => '0.00',
            'tempo_entrega' => '30',
            'tipo_entrega' => 'bairro',
            'google_maps_key' => 'Erro - Verifique o BD',
            'endereco_loja' => 'Digite seu endereço',
            'latitude_loja' => '0',
            'longitude_loja' => '0',
            'instagram' => '@empresa',
            'logo' => '',
            'banner' => '',
            'banner_pc' => '',
            'slogan' => '',
            'whatsapp' => '',
            'semana_inicio' => '',
            'semana_fim' => '',
            'sabado_inicio' => '',
            'sabado_fim' => '',
            'domingo_inicio' => '',
            'domingo_fim' => '',
            'status_loja' => 'horario',
            'segunda_inicio' => '',
            'segunda_fim' => '',
            'terca_inicio' => '',
            'terca_fim' => '',
            'quarta_inicio' => '',
            'quarta_fim' => '',
            'quinta_inicio' => '',
            'quinta_fim' => '',
            'sexta_inicio' => '',
            'sexta_fim' => '',
            'favicon' => '',
            'local' => 'Digite seu bairro',
            'cor_tema' => '#8B5CF6',
        ];

        // Busca configurações atuais
        $stmt = $db->query("SELECT * FROM configuracoes ORDER BY id DESC LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se não existir configuração ou se existir, mescla com os valores padrão
        $config = $config ? array_merge($campos_padrao, $config) : $campos_padrao;

        return $config;
    }
}
