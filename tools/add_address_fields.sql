-- SQL script to add separate address fields to the clientes table

-- Add new address fields
ALTER TABLE clientes 
ADD COLUMN cep VARCHAR(10) AFTER endereco,
ADD COLUMN logradouro VARCHAR(255) AFTER cep,
ADD COLUMN numero VARCHAR(20) AFTER logradouro,
ADD COLUMN complemento VARCHAR(255) AFTER numero,
ADD COLUMN bairro VARCHAR(100) AFTER complemento,
ADD COLUMN cidade VARCHAR(100) AFTER bairro,
ADD COLUMN estado VARCHAR(2) AFTER cidade;

-- Create index on cidade and estado for faster searches
CREATE INDEX idx_cliente_cidade ON clientes(cidade);
CREATE INDEX idx_cliente_estado ON clientes(estado);
CREATE INDEX idx_cliente_cep ON clientes(cep);