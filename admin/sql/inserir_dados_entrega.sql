-- Inserir dados na tabela bairros_entrega
INSERT INTO bairros_entrega (nome, valor) VALUES
('Centro', 5.00),
('Jardim América', 6.00),
('Vila Nova', 7.00),
('Santa Maria', 8.00),
('São José', 7.50);

-- Inserir dados na tabela faixas_distancia
INSERT INTO faixas_distancia (inicio, fim, valor) VALUES
(0, 3, 5.00),    -- Até 3km
(3, 5, 7.00),    -- De 3 a 5km
(5, 7, 9.00),    -- De 5 a 7km
(7, 9, 12.00);   -- De 7 a 9km
