-- Adiciona as novas colunas se não existirem
ALTER TABLE configuracoes
ADD COLUMN IF NOT EXISTS email_sistema VARCHAR(100) AFTER telefone_loja,
ADD COLUMN IF NOT EXISTS senha_email VARCHAR(255) AFTER email_sistema;

-- Remove colunas antigas se existirem
ALTER TABLE configuracoes
DROP COLUMN IF EXISTS smtp_host,
DROP COLUMN IF EXISTS smtp_porta,
DROP COLUMN IF EXISTS smtp_usuario,
DROP COLUMN IF EXISTS smtp_senha,
DROP COLUMN IF EXISTS smtp_seguranca;
