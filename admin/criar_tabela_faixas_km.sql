-- Apaga a tabela se existir
DROP TABLE IF EXISTS faixas_km;

-- Cria a tabela com a estrutura correta
CREATE TABLE faixas_km (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inicio DECIMAL(10,3) NOT NULL,
    fim DECIMAL(10,3) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    UNIQUE KEY unique_faixa (inicio, fim)
);

-- Insere as faixas de 1 a 10 km
INSERT INTO faixas_km (inicio, fim, valor) VALUES
(1.000, 2.000, 1.00),
(2.001, 3.000, 3.00),
(3.001, 4.000, 4.00),
(4.001, 5.000, 5.00),
(5.001, 6.000, 6.00),
(6.001, 7.000, 7.00),
(7.001, 8.000, 8.00),
(8.001, 9.000, 9.00),
(9.001, 10.000, 10.00);
