-- Verifica se o cupom existe
SELECT * FROM cupons WHERE codigo = '12';

-- Verifica todos os cupons ativos
SELECT * FROM cupons WHERE ativo = 1;
