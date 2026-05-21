-- Adiciona novas colunas para temas
ALTER TABLE configuracoes
ADD COLUMN tema_cor VARCHAR(20) DEFAULT 'azul' AFTER cor_sistema,
ADD COLUMN cor_personalizada VARCHAR(7) DEFAULT '#3b82f6' AFTER tema_cor;
