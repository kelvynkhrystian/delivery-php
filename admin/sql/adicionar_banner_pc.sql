-- Adiciona a coluna banner_pc na tabela configuracoes
ALTER TABLE configuracoes ADD COLUMN banner_pc VARCHAR(255) AFTER banner;

-- Atualiza registros existentes com valor padrão (opcional)
UPDATE configuracoes SET banner_pc = 'assets/images/banner-pc.svg' WHERE banner_pc IS NULL;
