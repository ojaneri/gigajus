<?php
/**
 * make_cpf_nullable.php
 * Script para modificar a coluna cpf_cnpj da tabela clientes para permitir valores NULL.
 * Autor: Kilo Code
 * Data: 2025-04-29
 */

require 'config.php';

// A função logMessage já está definida em config.php

// Verificar se a coluna já é nullable
$checkSql = "SHOW COLUMNS FROM clientes WHERE Field = 'cpf_cnpj'";
$result = $conn->query($checkSql);

if ($result && $result->num_rows > 0) {
    $column = $result->fetch_assoc();
    $isNullable = $column['Null'] === 'YES';
    
    if ($isNullable) {
        logMessage("A coluna cpf_cnpj já permite valores NULL.", 'INFO');
        echo "A coluna cpf_cnpj já permite valores NULL. Nenhuma alteração necessária.";
    } else {
        // Executar a alteração da tabela
        $alterSql = "ALTER TABLE `clientes` MODIFY `cpf_cnpj` varchar(20) NULL";
        
        if ($conn->query($alterSql) === TRUE) {
            logMessage("Coluna cpf_cnpj modificada com sucesso para permitir valores NULL.", 'INFO');
            echo "Coluna cpf_cnpj modificada com sucesso para permitir valores NULL.";
        } else {
            logMessage("Erro ao modificar a coluna cpf_cnpj: " . $conn->error, 'ERROR');
            echo "Erro ao modificar a coluna cpf_cnpj: " . $conn->error;
        }
    }
} else {
    logMessage("Erro ao verificar a coluna cpf_cnpj: " . $conn->error, 'ERROR');
    echo "Erro ao verificar a coluna cpf_cnpj: " . $conn->error;
}

$conn->close();