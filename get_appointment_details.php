<?php
session_start();
require 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Verificar se o ID do atendimento foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de atendimento inválido']);
    exit();
}

$appointment_id = intval($_GET['id']);

// Verificar se a tabela atendimentos tem a coluna id_processo
$check_column = $conn->query("SHOW COLUMNS FROM atendimentos LIKE 'id_processo'");
$has_id_processo = $check_column->num_rows > 0;

// Obter detalhes do atendimento
if ($has_id_processo) {
    $stmt = $conn->prepare("
        SELECT a.*, p.numero_processo
        FROM atendimentos a
        LEFT JOIN processos p ON a.id_processo = p.id_processo
        WHERE a.id_atendimento = ?
    ");
} else {
    $stmt = $conn->prepare("
        SELECT a.*
        FROM atendimentos a
        WHERE a.id_atendimento = ?
    ");
}
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Atendimento não encontrado']);
    exit();
}

$appointment = $result->fetch_assoc();

// Formatar a data para exibição
$appointment['data'] = date('d/m/Y H:i', strtotime($appointment['data']));

// Retornar os dados como JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'appointment' => $appointment
]);