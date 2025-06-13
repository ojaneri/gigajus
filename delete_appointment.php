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

// Verificar se o atendimento existe
$stmt = $conn->prepare("SELECT id_atendimento, id_cliente FROM atendimentos WHERE id_atendimento = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Atendimento não encontrado']);
    exit();
}

$appointment = $result->fetch_assoc();
$client_id = $appointment['id_cliente'];

// Excluir o atendimento
$stmt = $conn->prepare("DELETE FROM atendimentos WHERE id_atendimento = ?");
$stmt->bind_param("i", $appointment_id);

if ($stmt->execute()) {
    // Log da exclusão
    $user_id = $_SESSION['user_id'];
    $log_stmt = $conn->prepare("INSERT INTO logs (user_id, action, entity_type, entity_id, details) VALUES (?, 'delete', 'appointment', ?, ?)");
    $details = json_encode(['client_id' => $client_id]);
    $log_stmt->bind_param("iis", $user_id, $appointment_id, $details);
    $log_stmt->execute();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Atendimento excluído com sucesso']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir atendimento: ' . $stmt->error]);
}