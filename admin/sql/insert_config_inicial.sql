-- Insere configuração inicial se não existir
INSERT INTO configuracoes (id, nome_loja, cor_tema)
SELECT 1, 'Minha Loja', '#8B5CF6'
WHERE NOT EXISTS (SELECT 1 FROM configuracoes WHERE id = 1);
