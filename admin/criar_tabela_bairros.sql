-- Apaga a tabela se existir
DROP TABLE IF EXISTS bairros_entrega;

-- Cria a tabela com a estrutura correta
CREATE TABLE bairros_entrega (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    ativo BOOLEAN DEFAULT true,
    UNIQUE KEY unique_bairro (nome)
);

-- Insere alguns bairros de exemplo com valores variados
INSERT INTO bairros_entrega (nome, valor) VALUES
('Centro', 3.50),
('Jardim América', 4.00),
('Vila Nova', 5.50),
('Santa Rita', 4.50),
('São José', 6.00);
