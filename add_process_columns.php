<?php
// Script to add the polo_ativo and polo_passivo columns to the processes table

// Include database configuration
require 'config.php';

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "Acesso restrito. Você precisa estar logado como administrador.";
    exit();
}

// Check if the polo_ativo column already exists
$check_column = $conn->query("SHOW COLUMNS FROM processes LIKE 'polo_ativo'");

if ($check_column->num_rows > 0) {
    echo "A coluna 'polo_ativo' já existe na tabela processes.<br>";
} else {
    // Check if the old parte_ativa column exists
    $check_old_column = $conn->query("SHOW COLUMNS FROM processes LIKE 'parte_ativa'");
    
    if ($check_old_column->num_rows > 0) {
        // Rename the column
        $sql = "ALTER TABLE processes CHANGE COLUMN parte_ativa polo_ativo VARCHAR(255)";
        
        if ($conn->query($sql) === TRUE) {
            echo "Coluna 'parte_ativa' renomeada para 'polo_ativo' com sucesso.<br>";
        } else {
            echo "Erro ao renomear a coluna 'parte_ativa': " . $conn->error . "<br>";
        }
    } else {
        // Add the polo_ativo column
        $sql = "ALTER TABLE processes ADD COLUMN polo_ativo VARCHAR(255) AFTER tribunal";
        
        if ($conn->query($sql) === TRUE) {
            echo "Coluna 'polo_ativo' adicionada com sucesso à tabela processes.<br>";
        } else {
            echo "Erro ao adicionar a coluna 'polo_ativo': " . $conn->error . "<br>";
        }
    }
}

// Check if the polo_passivo column already exists
$check_column = $conn->query("SHOW COLUMNS FROM processes LIKE 'polo_passivo'");

if ($check_column->num_rows > 0) {
    echo "A coluna 'polo_passivo' já existe na tabela processes.<br>";
} else {
    // Check if the old parte_passiva column exists
    $check_old_column = $conn->query("SHOW COLUMNS FROM processes LIKE 'parte_passiva'");
    
    if ($check_old_column->num_rows > 0) {
        // Rename the column
        $sql = "ALTER TABLE processes CHANGE COLUMN parte_passiva polo_passivo VARCHAR(255)";
        
        if ($conn->query($sql) === TRUE) {
            echo "Coluna 'parte_passiva' renomeada para 'polo_passivo' com sucesso.<br>";
        } else {
            echo "Erro ao renomear a coluna 'parte_passiva': " . $conn->error . "<br>";
        }
    } else {
        // Add the polo_passivo column
        $sql = "ALTER TABLE processes ADD COLUMN polo_passivo VARCHAR(255) AFTER polo_ativo";
        
        if ($conn->query($sql) === TRUE) {
            echo "Coluna 'polo_passivo' adicionada com sucesso à tabela processes.<br>";
        } else {
            echo "Erro ao adicionar a coluna 'polo_passivo': " . $conn->error . "<br>";
        }
    }
}

// Close the connection
$conn->close();

echo "<br>Processo concluído. <a href='notifications.php'>Voltar para Notificações</a>";
?>