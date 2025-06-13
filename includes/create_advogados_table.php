<?php
require_once '/var/www/html/janeri.com.br/gigajus/v2/config.php';

// Check if the advogados table exists
$checkTableSql = "SHOW TABLES LIKE 'advogados'";
$result = $conn->query($checkTableSql);

if ($result->num_rows == 0) {
    // Create the advogados table
    $createTableSql = "CREATE TABLE advogados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome_advogado VARCHAR(255),
        oab_numero VARCHAR(50),
        oab_uf VARCHAR(2),
        id_empresa INT
    )";

    if ($conn->query($createTableSql) === TRUE) {
        echo "Table advogados created successfully";
    } else {
        echo "Error creating table: " . $conn->error;
    }
} else {
    // Check if the nome_advogado column exists
    $checkColumnSql = "SHOW COLUMNS FROM advogados LIKE 'nome_advogado'";
    $result = $conn->query($checkColumnSql);

    if ($result->num_rows == 0) {
        // Add the nome_advogado column
        $addColumnSql = "ALTER TABLE advogados ADD nome_advogado VARCHAR(255)";

        if ($conn->query($addColumnSql) === TRUE) {
            echo "Column nome_advogado added successfully";
        } else {
            echo "Error adding column: " . $conn->error;
        }
    } else {
        echo "Table advogados already exists and has the nome_advogado column";
    }
}

$conn->close();
?>