<?php
require 'config.php';

// Consulta para obter as tabelas
$sqlTables = "SHOW TABLES";
$resultTables = $conn->query($sqlTables);

if ($resultTables->num_rows > 0) {
    while ($row = $resultTables->fetch_assoc()) {
        $tableName = $row["Tables_in_gigajus"];
        echo "<h2>Tabela: $tableName</h2>";

        // Consulta para obter a estrutura da tabela
        $sqlColumns = "SHOW COLUMNS FROM $tableName";
        $resultColumns = $conn->query($sqlColumns);

        if ($resultColumns->num_rows > 0) {
            echo "<table border='1'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            while ($column = $resultColumns->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "<td>" . $column['Extra'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "Nenhuma coluna encontrada na tabela $tableName.<br>";
        }
    }
} else {
    echo "Nenhuma tabela encontrada.";
}

$conn->close();
?>
