<?php
// Script to create the user_tokens table for "Remember Me" functionality

// Include database configuration
require 'config.php';

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "Acesso restrito. Você precisa estar logado como administrador.";
    //exit();
}

// Check if the user_tokens table already exists
$check_table = $conn->query("SHOW TABLES LIKE 'user_tokens'");

if ($check_table->num_rows > 0) {
    echo "A tabela 'user_tokens' já existe no banco de dados.<br>";
} else {
    // Create the user_tokens table
    $sql = "CREATE TABLE user_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES usuarios(id_usuario)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Tabela 'user_tokens' criada com sucesso.<br>";
        
        // Create index for faster token lookups
        $index_sql = "CREATE INDEX idx_user_tokens_token ON user_tokens(token)";
        if ($conn->query($index_sql) === TRUE) {
            echo "Índice para tokens criado com sucesso.<br>";
        } else {
            echo "Erro ao criar índice: " . $conn->error . "<br>";
        }
    } else {
        echo "Erro ao criar tabela 'user_tokens': " . $conn->error . "<br>";
    }
}

// Close the connection
$conn->close();

echo "<br>Processo concluído. <a href='login.php'>Voltar para Login</a>";
?>