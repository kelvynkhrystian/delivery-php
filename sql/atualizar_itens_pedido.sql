-- Adiciona a coluna preco_unitario se não existir
ALTER TABLE itens_pedido
ADD COLUMN IF NOT EXISTS preco_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00;

-- Atualiza os preços unitários existentes baseado nos produtos
UPDATE itens_pedido ip
JOIN produtos p ON ip.produto_id = p.id
SET ip.preco_unitario = p.preco
WHERE ip.preco_unitario = 0;
