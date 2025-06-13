<?php
/**
 * make_email_nullable.php
 * Script para modificar a coluna email da tabela clientes para permitir valores NULL.
 * Autor: Kilo Code
 * Data: 2025-04-29
 */

require 'config.php';

// A função logMessage já está definida em config.php

// Verificar se a coluna já é nullable
$checkSql = "SHOW COLUMNS FROM clientes WHERE Field = 'email'";
$result = $conn->query($checkSql);

if ($result && $result->num_rows > 0) {
    $column = $result->fetch_assoc();
    $isNullable = $column['Null'] === 'YES';
    
    if ($isNullable) {
        logMessage("A coluna email já permite valores NULL.", 'INFO');
        echo "A coluna email já permite valores NULL. Nenhuma alteração necessária.";
    } else {
        // Executar a alteração da tabela
        $alterSql = "ALTER TABLE `clientes` MODIFY `email` varchar(255) NULL";
        
        if ($conn->query($alterSql) === TRUE) {
            logMessage("Coluna email modificada com sucesso para permitir valores NULL.", 'INFO');
            echo "Coluna email modificada com sucesso para permitir valores NULL.";
        } else {
            logMessage("Erro ao modificar a coluna email: " . $conn->error, 'ERROR');
            echo "Erro ao modificar a coluna email: " . $conn->error;
        }
    }
} else {
    logMessage("Erro ao verificar a coluna email: " . $conn->error, 'ERROR');
    echo "Erro ao verificar a coluna email: " . $conn->error;
}

$conn->close();