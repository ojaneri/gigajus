-- SQL script to modify the clientes table to make email column nullable
ALTER TABLE `clientes` MODIFY `email` varchar(255) NULL;