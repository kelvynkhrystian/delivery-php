-- Renomeia a tabela antiga para backup
RENAME TABLE faixas_distancia TO faixas_distancia_old;

-- Cria a nova tabela com a estrutura atualizada
CREATE TABLE faixas_distancia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    distancia_km DECIMAL(10,3) NOT NULL,
    valor_entrega DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_distancia (distancia_km)
);

-- Migra os dados da tabela antiga para a nova
INSERT INTO faixas_distancia (distancia_km, valor_entrega)
SELECT fim, valor FROM faixas_distancia_old;

-- Remove a tabela antiga
DROP TABLE faixas_distancia_old;
