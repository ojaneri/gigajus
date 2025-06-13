<?php
// Script to add the teor column to the notifications table

// Include database configuration
require 'config.php';

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "Acesso restrito. Você precisa estar logado como administrador.";
    exit();
}

// Check if the teor column already exists
$check_column = $conn->query("SHOW COLUMNS FROM notifications LIKE 'teor'");

if ($check_column->num_rows > 0) {
    echo "A coluna 'teor' já existe na tabela notifications.";
} else {
    // Add the teor column
    $sql = "ALTER TABLE notifications ADD COLUMN teor TEXT AFTER data_publicacao";
    
    if ($conn->query($sql) === TRUE) {
        echo "Coluna 'teor' adicionada com sucesso à tabela notifications.";
    } else {
        echo "Erro ao adicionar a coluna: " . $conn->error;
    }
}

// Close the connection
$conn->close();
?>