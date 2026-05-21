-- Adicionar coluna cupom_id na tabela pedidos se não existir
ALTER TABLE pedidos 
ADD COLUMN IF NOT EXISTS cupom_id INT NULL,
ADD COLUMN IF NOT EXISTS desconto_cupom DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP;

-- Adicionar chave estrangeira se não existir
SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_pedido_cupom'
);

SET @sql = IF(
    @fk_exists = 0,
    'ALTER TABLE pedidos ADD CONSTRAINT fk_pedido_cupom FOREIGN KEY (cupom_id) REFERENCES cupons(id)',
    'SELECT "Chave estrangeira já existe" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Atualiza pedidos existentes
UPDATE pedidos SET desconto_cupom = 0 WHERE desconto_cupom IS NULL;
