<?php
require 'config.php';

// Consulta para verificar se há processos no banco de dados
$sql = "SELECT * FROM processos";
$result = $conn->query($sql);

// Exibir resultados
echo "<h1>Processos no Banco de Dados</h1>";
echo "<pre>";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Não há processos no banco de dados.";
}
echo "</pre>";
?>