-- Criar tabela de cupons
CREATE TABLE IF NOT EXISTS `cupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `tipo` enum('valor_total', 'porcentagem_total', 'valor_frete', 'porcentagem_frete') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `quantidade_maxima` int(11) DEFAULT NULL,
  `quantidade_usada` int(11) DEFAULT 0,
  `valor_minimo_pedido` decimal(10,2) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar tabela de cupons utilizados
CREATE TABLE IF NOT EXISTS `cupons_utilizados` (
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
