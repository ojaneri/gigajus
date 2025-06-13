USE smartjuris;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE escritorios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    endereco VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE decisoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_processo VARCHAR(255) NOT NULL,
    tipo_documento VARCHAR(255),
    relator VARCHAR(255),
    ementa TEXT,
    inteiro_teor TEXT,
    principais_argumentos TEXT,
    procedente BOOLEAN,
    dano_moral BOOLEAN,
    valor_dano_moral DECIMAL(10, 2),
    categoria VARCHAR(255),
    resultado_json JSON,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    arquivo VARCHAR(255) NOT NULL,
    tipo ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR') NOT NULL,
    mensagem TEXT NOT NULL,
    datahora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);