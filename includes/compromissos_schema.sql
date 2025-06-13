-- Criação da tabela de compromissos (atendimentos)
CREATE TABLE IF NOT EXISTS `atendimentos` (
  `id_atendimento` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `data` datetime NOT NULL,
  `descricao` text NOT NULL,
  `responsavel` varchar(255) NOT NULL,
  `observacoes` text,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_atendimento`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `atendimentos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comentários sobre a tabela
-- Esta tabela armazena os atendimentos realizados para os clientes
-- id_atendimento: Identificador único do atendimento
-- id_cliente: Referência ao cliente atendido
-- data: Data e hora do atendimento
-- descricao: Descrição do atendimento
-- responsavel: Nome do responsável pelo atendimento
-- observacoes: Observações adicionais sobre o atendimento
-- data_cadastro: Data e hora de cadastro do atendimento