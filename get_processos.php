<?php
require 'config.php';

$id_cliente = $_GET['id_cliente'] ?? '';

// Verificar se o ID do cliente foi passado
if ($id_cliente) {
    $stmtProcessos = $conn->prepare("SELECT id_processo, numero_processo FROM processos WHERE id_cliente = ?");
    $stmtProcessos->bind_param("i", $id_cliente);
    $stmtProcessos->execute();
    $result = $stmtProcessos->get_result();

    $processos = [];
    while ($row = $result->fetch_assoc()) {
        $processos[] = $row;
    }

    echo json_encode($processos);
}
?>
