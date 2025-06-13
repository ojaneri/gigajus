-- Estrutura do banco de dados `gigajus`
-- Gerado em: 2025-03-26 11:29:13

-- Estrutura da tabela `atendimentos`
SHOW CREATE TABLE `atendimentos`;

CREATE TABLE `atendimentos` (
  `id_atendimento` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `data` datetime NOT NULL,
  `descricao` text,
  `responsavel` varchar(255) DEFAULT NULL,
  `observacoes` text,
  PRIMARY KEY (`id_atendimento`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `atendimentos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `atendimentos`
-- id_atendimento - int - NOT NULL - auto_increment
-- id_cliente - int - NULL
-- data - datetime - NOT NULL
-- descricao - text - NULL
-- responsavel - varchar(255) - NULL
-- observacoes - text - NULL

-- Estrutura da tabela `clientes`
SHOW CREATE TABLE `clientes`;

CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cpf_cnpj` varchar(20) NOT NULL,
  `endereco` text,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `outros_dados` json DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `clientes`
-- id_cliente - int - NOT NULL - auto_increment
-- nome - varchar(255) - NOT NULL
-- cpf_cnpj - varchar(20) - NOT NULL
-- endereco - text - NULL
-- telefone - varchar(20) - NULL
-- email - varchar(255) - NULL
-- outros_dados - json - NULL
-- ativo - tinyint(1) - NULL - DEFAULT '1'

-- Estrutura da tabela `eventos`
SHOW CREATE TABLE `eventos`;

CREATE TABLE `eventos` (
  `id_evento` int NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `hora` time NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text,
  PRIMARY KEY (`id_evento`),
  KEY `idx_eventos_data` (`data`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `eventos`
-- id_evento - int - NOT NULL - auto_increment
-- data - date - NOT NULL
-- hora - time - NOT NULL
-- titulo - varchar(255) - NOT NULL
-- descricao - text - NULL

-- Estrutura da tabela `faturas`
SHOW CREATE TABLE `faturas`;

CREATE TABLE `faturas` (
  `id_fatura` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `data_emissao` date DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_fatura`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `faturas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `faturas`
-- id_fatura - int - NOT NULL - auto_increment
-- id_cliente - int - NULL
-- data_emissao - date - NULL
-- valor - decimal(10,2) - NULL
-- status - varchar(50) - NULL

-- Estrutura da tabela `feedback`
SHOW CREATE TABLE `feedback`;

CREATE TABLE `feedback` (
  `id_feedback` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `avaliacao` int DEFAULT NULL,
  `comentarios` text,
  PRIMARY KEY (`id_feedback`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `feedback`
-- id_feedback - int - NOT NULL - auto_increment
-- id_cliente - int - NULL
-- data - datetime - NULL
-- avaliacao - int - NULL
-- comentarios - text - NULL

-- Estrutura da tabela `notificacoes`
SHOW CREATE TABLE `notificacoes`;

CREATE TABLE `notificacoes` (
  `id_notificacao` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int DEFAULT NULL,
  `id_cliente` int DEFAULT NULL,
  `tipo` enum('SMS','WhatsApp','Email') DEFAULT NULL,
  `mensagem` text,
  `data_envio` datetime DEFAULT NULL,
  PRIMARY KEY (`id_notificacao`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `notificacoes_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `notificacoes`
-- id_notificacao - int - NOT NULL - auto_increment
-- id_usuario - int - NULL
-- id_cliente - int - NULL
-- tipo - enum('SMS','WhatsApp','Email') - NULL
-- mensagem - text - NULL
-- data_envio - datetime - NULL

-- Estrutura da tabela `password_resets`
SHOW CREATE TABLE `password_resets`;

CREATE TABLE `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `password_resets`
-- id - int - NOT NULL - auto_increment
-- email - varchar(255) - NOT NULL
-- token - varchar(255) - NOT NULL
-- expires_at - datetime - NOT NULL

-- Estrutura da tabela `pendencias`
SHOW CREATE TABLE `pendencias`;

CREATE TABLE `pendencias` (
  `id_pendencia` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `tipo` enum('escritorio','cliente') DEFAULT NULL,
  `descricao` text,
  `data_prazo` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_pendencia`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `pendencias_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `pendencias`
-- id_pendencia - int - NOT NULL - auto_increment
-- id_cliente - int - NULL
-- tipo - enum('escritorio','cliente') - NULL
-- descricao - text - NULL
-- data_prazo - date - NULL
-- status - varchar(50) - NULL

-- Estrutura da tabela `processos`
SHOW CREATE TABLE `processos`;

CREATE TABLE `processos` (
  `id_processo` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `numero_processo` varchar(255) NOT NULL,
  `descricao` text,
  `status` varchar(50) DEFAULT NULL,
  `tribunal` varchar(255) DEFAULT NULL,
  `data_abertura` date DEFAULT NULL,
  `data_fechamento` date DEFAULT NULL,
  `status_externo` text,
  `observacoes` text,
  `notificar` varchar(255) DEFAULT NULL,
  `periodicidade_notificacao` int DEFAULT NULL,
  PRIMARY KEY (`id_processo`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `processos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `processos`
-- id_processo - int - NOT NULL - auto_increment
-- id_cliente - int - NULL
-- numero_processo - varchar(255) - NOT NULL
-- descricao - text - NULL
-- status - varchar(50) - NULL
-- tribunal - varchar(255) - NULL
-- data_abertura - date - NULL
-- data_fechamento - date - NULL
-- status_externo - text - NULL
-- observacoes - text - NULL
-- notificar - varchar(255) - NULL
-- periodicidade_notificacao - int - NULL

-- Estrutura da tabela `tarefa_iteracoes`
SHOW CREATE TABLE `tarefa_iteracoes`;

CREATE TABLE `tarefa_iteracoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_tarefa` int NOT NULL,
  `descricao` text NOT NULL,
  `id_usuario` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_tarefa` (`id_tarefa`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `tarefa_iteracoes_ibfk_1` FOREIGN KEY (`id_tarefa`) REFERENCES `tarefas` (`id_tarefa`),
  CONSTRAINT `tarefa_iteracoes_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `tarefa_iteracoes`
-- id - int - NOT NULL - auto_increment
-- id_tarefa - int - NOT NULL
-- descricao - text - NOT NULL
-- id_usuario - int - NOT NULL
-- created_at - timestamp - NULL - DEFAULT 'CURRENT_TIMESTAMP' - DEFAULT_GENERATED

-- Estrutura da tabela `tarefas`
SHOW CREATE TABLE `tarefas`;

CREATE TABLE `tarefas` (
  `id_tarefa` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int DEFAULT NULL,
  `id_cliente` int DEFAULT NULL,
  `email_externo` varchar(255) DEFAULT NULL,
  `token` varchar(64) DEFAULT NULL,
  `descricao` text,
  `descricao_longa` text,
  `data_horario_final` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `data_hora_criacao` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tarefa`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `tarefas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `tarefas_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `tarefas`
-- id_tarefa - int - NOT NULL - auto_increment
-- id_usuario - int - NULL
-- id_cliente - int - NULL
-- email_externo - varchar(255) - NULL
-- token - varchar(64) - NULL
-- descricao - text - NULL
-- descricao_longa - text - NULL
-- data_horario_final - datetime - NULL
-- status - varchar(50) - NULL
-- data_hora_criacao - datetime - NULL - DEFAULT 'CURRENT_TIMESTAMP' - DEFAULT_GENERATED

-- Estrutura da tabela `tickets_suporte`
SHOW CREATE TABLE `tickets_suporte`;

CREATE TABLE `tickets_suporte` (
  `id_ticket` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `data_criacao` datetime NOT NULL,
  `descricao` text,
  `arquivos` text,
  `status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_ticket`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `tickets_suporte_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `tickets_suporte`
-- id_ticket - int - NOT NULL - auto_increment
-- id_cliente - int - NULL
-- data_criacao - datetime - NOT NULL
-- descricao - text - NULL
-- arquivos - text - NULL
-- status - varchar(50) - NULL

-- Estrutura da tabela `usuarios`
SHOW CREATE TABLE `usuarios`;

CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `theme` varchar(20) DEFAULT 'law',
  `permissoes` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Colunas da tabela `usuarios`
-- id_usuario - int - NOT NULL - auto_increment
-- nome - varchar(255) - NOT NULL
-- email - varchar(255) - NOT NULL
-- senha - varchar(255) - NOT NULL
-- telefone - varchar(20) - NULL
-- cargo - varchar(100) - NULL
-- theme - varchar(20) - NULL - DEFAULT 'law'
-- permissoes - json - NULL
-- created_at - timestamp - NULL - DEFAULT 'CURRENT_TIMESTAMP' - DEFAULT_GENERATED

