<?php
// Script to add the teor column to the notifications table (CLI version)

// Include database configuration
require 'config.php';

echo "Checking if 'teor' column exists in notifications table...\n";

// Check if the teor column already exists
$check_column = $conn->query("SHOW COLUMNS FROM notifications LIKE 'teor'");

if ($check_column->num_rows > 0) {
    echo "The 'teor' column already exists in the notifications table.\n";
} else {
    // Add the teor column
    $sql = "ALTER TABLE notifications ADD COLUMN teor TEXT AFTER data_publicacao";
    
    if ($conn->query($sql) === TRUE) {
        echo "Column 'teor' successfully added to the notifications table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
}

// Close the connection
$conn->close();
echo "Done.\n";
?>