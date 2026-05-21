-- Adiciona as colunas faltantes se não existirem
ALTER TABLE configuracoes
ADD COLUMN IF NOT EXISTS favicon VARCHAR(255) AFTER banner,
ADD COLUMN IF NOT EXISTS status_funcionamento ENUM('horario', 'aberto', 'fechado') DEFAULT 'horario' AFTER cor_personalizada,
ADD COLUMN IF NOT EXISTS horarios_funcionamento JSON AFTER status_funcionamento;

-- Atualiza os registros existentes com valores padrão
UPDATE configuracoes 
SET favicon = 'assets/images/favicon.svg' WHERE favicon IS NULL,
    status_funcionamento = 'horario' WHERE status_funcionamento IS NULL,
    horarios_funcionamento = '{"segunda":{"inicio":"08:00","fim":"18:00"},"terca":{"inicio":"08:00","fim":"18:00"},"quarta":{"inicio":"08:00","fim":"18:00"},"quinta":{"inicio":"08:00","fim":"18:00"},"sexta":{"inicio":"08:00","fim":"18:00"},"sabado":{"inicio":"08:00","fim":"12:00"},"domingo":{"inicio":"","fim":""}}' 
WHERE horarios_funcionamento IS NULL;
