-- Atualiza o tipo de entrega para distância em todas as configurações
UPDATE configuracoes SET tipo_entrega = 'distancia' WHERE 1;

-- Limpa a tabela de bairros (opcional - remova o comentário se quiser apagar os bairros)
-- TRUNCATE TABLE bairros;

-- Insere uma configuração de distância padrão se não existir nenhuma
INSERT INTO faixas_distancia (distancia_km, valor_entrega)
SELECT 5, 10.00
WHERE NOT EXISTS (SELECT 1 FROM faixas_distancia LIMIT 1);
