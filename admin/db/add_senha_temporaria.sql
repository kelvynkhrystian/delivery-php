-- Adiciona a coluna senha_temporaria se não existir
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS senha_temporaria TINYINT(1) DEFAULT 0 AFTER senha;
