<?php
require 'config.php';

echo "<h1>Database Schema Inspector</h1>";

$tables_query = "SHOW TABLES";
$tables_result = $conn->query($tables_query);

if ($tables_result) {
    while ($table_row = $tables_result->fetch_row()) {
        $table_name = $table_row[0];
        echo "<h2>Table: " . htmlspecialchars($table_name) . "</h2>";

        $columns_query = "SHOW COLUMNS FROM " . $table_name;
        $columns_result = $conn->query($columns_query);

        if ($columns_result) {
            echo "<table border='1'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            while ($column_row = $columns_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($column_row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($column_row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($column_row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($column_row['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($column_row['Default']) . "</td>";
                echo "<td>" . htmlspecialchars($column_row['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            $columns_result->free();
        } else {
            echo "Error fetching columns for table " . htmlspecialchars($table_name) . ": " . $conn->error;
        }
    }
    $tables_result->free();
} else {
    echo "Error fetching tables: " . $conn->error;
}

$conn->close();
?>