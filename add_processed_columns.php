<?php
// Script to add the processed_by and processed_at columns to the notifications table

// Include database configuration
require 'config.php';

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "Acesso restrito. Você precisa estar logado como administrador.";
    exit();
}

// Check if the processed_by column already exists
$check_column = $conn->query("SHOW COLUMNS FROM notifications LIKE 'processed_by'");

if ($check_column->num_rows > 0) {
    echo "A coluna 'processed_by' já existe na tabela notifications.<br>";
} else {
    // Add the processed_by column
    $sql = "ALTER TABLE notifications ADD COLUMN processed_by INT AFTER processada";
    
    if ($conn->query($sql) === TRUE) {
        echo "Coluna 'processed_by' adicionada com sucesso à tabela notifications.<br>";
    } else {
        echo "Erro ao adicionar a coluna 'processed_by': " . $conn->error . "<br>";
    }
}

// Check if the processed_at column already exists
$check_column = $conn->query("SHOW COLUMNS FROM notifications LIKE 'processed_at'");

if ($check_column->num_rows > 0) {
    echo "A coluna 'processed_at' já existe na tabela notifications.<br>";
} else {
    // Add the processed_at column
    $sql = "ALTER TABLE notifications ADD COLUMN processed_at DATETIME AFTER processed_by";
    
    if ($conn->query($sql) === TRUE) {
        echo "Coluna 'processed_at' adicionada com sucesso à tabela notifications.<br>";
    } else {
        echo "Erro ao adicionar a coluna 'processed_at': " . $conn->error . "<br>";
    }
}

// Check if the foreign key constraint exists
$check_constraint = $conn->query("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                                 WHERE TABLE_NAME = 'notifications' 
                                 AND COLUMN_NAME = 'processed_by' 
                                 AND REFERENCED_TABLE_NAME = 'users'");

if ($check_constraint->num_rows > 0) {
    echo "A constraint de chave estrangeira para 'processed_by' já existe.<br>";
} else {
    // Try to add the foreign key constraint
    $sql = "ALTER TABLE notifications 
            ADD CONSTRAINT fk_notifications_processed_by
            FOREIGN KEY (processed_by) REFERENCES users(id)";
    
    if ($conn->query($sql) === TRUE) {
        echo "Constraint de chave estrangeira adicionada com sucesso.<br>";
    } else {
        echo "Aviso: Não foi possível adicionar a constraint de chave estrangeira: " . $conn->error . "<br>";
        echo "Isso pode ser ignorado se você não precisar da integridade referencial.<br>";
    }
}

// Close the connection
$conn->close();

echo "<br>Processo concluído. <a href='notifications.php'>Voltar para Notificações</a>";
?>