-- SQL script to modify the clientes table to make cpf_cnpj column nullable
ALTER TABLE `clientes` MODIFY `cpf_cnpj` varchar(20) NULL;