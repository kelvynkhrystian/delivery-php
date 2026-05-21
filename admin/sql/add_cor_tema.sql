-- Adiciona a coluna cor_tema se não existir
ALTER TABLE configuracoes
ADD COLUMN IF NOT EXISTS cor_tema VARCHAR(7) DEFAULT '#8B5CF6';

-- Atualiza registros existentes que não têm cor definida
UPDATE configuracoes 
SET cor_tema = '#8B5CF6' 
WHERE cor_tema IS NULL;
