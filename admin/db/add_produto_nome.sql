ALTER TABLE itens_pedido 
ADD COLUMN produto_nome VARCHAR(255) DEFAULT NULL,
MODIFY COLUMN produto_id INT DEFAULT NULL;

-- Preenche o nome dos produtos existentes
UPDATE itens_pedido ip 
JOIN produtos p ON ip.produto_id = p.id 
SET ip.produto_nome = p.nome 
WHERE ip.produto_id IS NOT NULL;
