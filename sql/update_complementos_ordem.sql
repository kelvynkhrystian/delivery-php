-- Adiciona coluna ordem na tabela complementos se ela não existir
ALTER TABLE complementos ADD COLUMN IF NOT EXISTS ordem INT DEFAULT 0 AFTER ativo;

-- Atualiza a ordem inicial dos complementos baseado no ID
UPDATE complementos SET ordem = id WHERE ordem = 0;
