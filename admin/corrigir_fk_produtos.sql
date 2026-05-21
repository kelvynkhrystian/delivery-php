-- Primeiro, remover a constraint antiga
ALTER TABLE itens_pedido
DROP FOREIGN KEY itens_pedido_ibfk_2;

-- Adicionar a coluna para permitir NULL (caso ainda não permita)
ALTER TABLE itens_pedido
MODIFY COLUMN produto_id int(11) NULL;

-- Adicionar a nova constraint com ON DELETE SET NULL
ALTER TABLE itens_pedido
ADD CONSTRAINT fk_itens_pedido_produto
FOREIGN KEY (produto_id) REFERENCES produtos(id)
ON DELETE SET NULL;
