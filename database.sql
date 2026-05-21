-- Desativa verificação de chaves estrangeiras temporariamente
SET FOREIGN_KEY_CHECKS=0;

-- Remove tabelas existentes na ordem inversa das dependências
DROP TABLE IF EXISTS pedido_complementos;
DROP TABLE IF EXISTS itens_pedido;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS produto_complementos;
DROP TABLE IF EXISTS complemento_opcoes;
DROP TABLE IF EXISTS complementos;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS enderecos_usuario;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS formas_pagamento;
DROP TABLE IF EXISTS bairros_entrega;
DROP TABLE IF EXISTS faixas_distancia;
DROP TABLE IF EXISTS configuracoes_maps;
DROP TABLE IF EXISTS configuracoes;
DROP TABLE IF EXISTS administradores;
DROP TABLE IF EXISTS cupons_utilizados;
DROP TABLE IF EXISTS cupons;

-- Reativa verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS=1;

-- 1. Primeiro as tabelas sem dependências
CREATE TABLE IF NOT EXISTS administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    remember_token VARCHAR(64) NULL,
    ultimo_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_loja VARCHAR(255) NOT NULL,
    slogan VARCHAR(255),
    local VARCHAR(255),
    endereco_loja TEXT,
    telefone_loja VARCHAR(20),
    email_loja VARCHAR(255),
    instagram VARCHAR(255),
    pedido_minimo DECIMAL(10,2) DEFAULT 0.00,
    tempo_entrega INT DEFAULT 30,
    status_funcionamento ENUM('horario', 'aberto', 'fechado') DEFAULT 'horario',
    horarios_funcionamento JSON,
    tipo_entrega ENUM('bairro', 'distancia') DEFAULT 'bairro',
    configuracoes_entrega JSON,
    maps_api_key VARCHAR(255),
    maps_latitude DECIMAL(10,8),
    maps_longitude DECIMAL(11,8),
    maps_endereco TEXT,
    maps_raio_entrega DECIMAL(10,2) DEFAULT 5.00,
    tema_cor VARCHAR(20) DEFAULT 'azul',
    cor_personalizada VARCHAR(7),
    favicon VARCHAR(255),
    logo VARCHAR(255),
    banner VARCHAR(255),
    banner_pc VARCHAR(255),
    cor_tema VARCHAR(7) DEFAULT '#8B5CF6',
    smtp_host VARCHAR(100),
    smtp_porta VARCHAR(5),
    smtp_usuario VARCHAR(100),
    smtp_senha VARCHAR(255),
    smtp_seguranca ENUM('tls', 'ssl') DEFAULT 'tls',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes_maps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    endereco TEXT,
    raio_entrega DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faixas_distancia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inicio DECIMAL(10,3) NOT NULL,
    fim DECIMAL(10,3) NOT NULL,
    valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_faixa (inicio, fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bairros_entrega (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tempo VARCHAR(50),
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS formas_pagamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    senha_temporaria TINYINT(1) DEFAULT 0,
    remember_token VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    tipo ENUM('valor_total', 'porcentagem_total', 'frete_valor', 'frete_porcentagem') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
    valor_minimo_pedido DECIMAL(10,2) NULL,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Depois as tabelas com uma dependência
CREATE TABLE IF NOT EXISTS enderecos_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    cep VARCHAR(8) NOT NULL,
    logradouro VARCHAR(255) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(255) DEFAULT NULL,
    bairro VARCHAR(255) NOT NULL,
    cidade VARCHAR(255) NOT NULL,
    estado CHAR(2) NOT NULL,
    principal BOOLEAN DEFAULT FALSE,
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    categoria_id INT NOT NULL,
    imagem LONGTEXT,
    ativo BOOLEAN DEFAULT TRUE,
    tem_complementos BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS complementos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    min_escolhas INT DEFAULT 0,
    max_escolhas INT DEFAULT 1,
    obrigatorio BOOLEAN DEFAULT FALSE,
    ativo BOOLEAN DEFAULT TRUE,
    ordem INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Depois as tabelas com múltiplas dependências
CREATE TABLE IF NOT EXISTS complemento_opcoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complemento_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    preco DECIMAL(10,2) DEFAULT 0.00,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (complemento_id) REFERENCES complementos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produto_complementos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    complemento_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    FOREIGN KEY (complemento_id) REFERENCES complementos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    forma_pagamento_id INT,
    endereco_id INT,
    logradouro VARCHAR(255),
    numero VARCHAR(20),
    complemento VARCHAR(255),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado CHAR(2),
    cep VARCHAR(8),
    status ENUM('pendente', 'confirmado', 'em_preparo', 'saiu_entrega', 'entregue', 'cancelado') DEFAULT 'pendente',
    observacoes TEXT,
    total DECIMAL(10,2) NOT NULL,
    taxa_entrega DECIMAL(10,2) DEFAULT 0.00,
    precisa_troco BOOLEAN DEFAULT FALSE,
    troco_para DECIMAL(10,2),
    cupom_id INT NULL,
    desconto_cupom DECIMAL(10,2) DEFAULT 0.00,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (forma_pagamento_id) REFERENCES formas_pagamento(id),
    FOREIGN KEY (cupom_id) REFERENCES cupons(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itens_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT DEFAULT NULL,
    produto_nome VARCHAR(255) DEFAULT NULL,
    quantidade INT NOT NULL,
    valor_unitario DECIMAL(10,2) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    observacao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_complementos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_item_id INT NOT NULL,
    complemento_nome VARCHAR(100) NOT NULL,
    opcao_nome VARCHAR(100) NOT NULL,
    opcao_preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_item_id) REFERENCES itens_pedido(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cupons_utilizados (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cupom_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `valor_desconto` decimal(10,2) NOT NULL,
  `data_utilizacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cupom_id` (`cupom_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `pedido_id` (`pedido_id`),
  CONSTRAINT `cupons_utilizados_ibfk_1` FOREIGN KEY (`cupom_id`) REFERENCES `cupons` (`id`),
  CONSTRAINT `cupons_utilizados_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `cupons_utilizados_ibfk_3` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Inserir dados iniciais

-- Inserir administrador padrão (senha: admin123)
INSERT INTO administradores (nome, email, senha) VALUES 
('Administrador', 'admin@admin.com', '0192023a7bbd73250516f069df18b500');

-- Inserir categorias
INSERT INTO categorias (nome, descricao) VALUES 
('Lanches', 'Hambúrgueres e sanduíches'),
('Bebidas', 'Refrigerantes e sucos');

-- Inserir produtos
INSERT INTO produtos (nome, descricao, preco, categoria_id, ativo) VALUES 
('X-Burger', 'Hambúrguer com queijo', 15.90, 1, 1),
('X-Salada', 'Hambúrguer com queijo e salada', 17.90, 1, 1),
('Coca-Cola 350ml', 'Refrigerante Coca-Cola lata', 5.90, 2, 1),
('Guaraná 350ml', 'Refrigerante Guaraná lata', 5.90, 2, 1);

-- Inserir formas de pagamento
INSERT INTO formas_pagamento (nome) VALUES 
('PIX'),
('Dinheiro'),
('Cartão de Crédito'),
('Cartão de Débito');

-- Inserir configurações padrão
INSERT INTO configuracoes (
    nome_loja,
    slogan,
    local,
    endereco_loja,
    telefone_loja,
    email_loja,
    instagram,
    pedido_minimo,
    tempo_entrega,
    status_funcionamento,
    tipo_entrega,
    maps_api_key,
    maps_latitude,
    maps_longitude,
    maps_endereco,
    maps_raio_entrega,
    tema_cor,
    cor_personalizada,
    favicon,
    logo,
    banner,
    banner_pc,
    cor_tema,
    smtp_host,
    smtp_porta,
    smtp_usuario,
    smtp_senha,
    smtp_seguranca
) VALUES (
    'Nome da Loja',
    'Slogan da Loja',
    'Localização',
    'Endereço da Loja',
    '(00) 00000-0000',
    'contato@exemplo.com',
    '@exemplo',
    20.00,
    30,
    'aberto',
    'bairro',
    'AIzaSyDlGo9UjMBd7crhS5b-qqO9dGxR5oadp_U',
    NULL,
    NULL,
    NULL,
    9.00,
    '#FF6B00',
    '',
    'assets/images/favicon.svg',
    'assets/images/logo.svg',
    'assets/images/banner.svg',
    'assets/images/banner_pc.svg',
    '#8B5CF6',
    'smtp.gmail.com',
    '587',
    'seuemail@gmail.com',
    'suasenha',
    'tls'
);

-- Inserir bairros de exemplo
INSERT INTO bairros_entrega (nome, valor, tempo, ativo) VALUES 
('Centro', 5.00, '30-40 min', 1),
('Jardim América', 6.00, '40-50 min', 1),
('Vila Nova', 7.00, '40-50 min', 1),
('Santa Maria', 8.00, '50-60 min', 1),
('Parque das Flores', 9.00, '50-60 min', 1);

-- Inserir faixas de distância
INSERT INTO faixas_distancia (inicio, fim, valor) VALUES
(0.000, 1.000, 1.00),
(1.001, 2.000, 2.00),
(2.001, 3.000, 3.00),
(3.001, 4.000, 4.00),
(4.001, 5.000, 5.00),
(5.001, 6.000, 6.00),
(6.001, 7.000, 7.00),
(7.001, 8.000, 8.00),
(8.001, 9.000, 9.00),
(9.001, 10.000, 10.00);
