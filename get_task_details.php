<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Task ID is required']);
    exit();
}

$taskId = intval($_GET['id']);

// Get task details with related information
$stmt = $conn->prepare("
    SELECT t.*, 
           c.nome as cliente_nome,
           u_criador.nome as criador_nome,
           u_responsavel.nome as responsavel_nome
    FROM tarefas t 
    LEFT JOIN clientes c ON t.id_cliente = c.id_cliente
    LEFT JOIN usuarios u_criador ON t.id_usuario = u_criador.id_usuario
    LEFT JOIN usuarios u_responsavel ON t.id_cliente = u_responsavel.id_usuario
    WHERE t.id_tarefa = ?
");
$stmt->bind_param("i", $taskId);
$stmt->execute();
$taskResult = $stmt->get_result();
$task = $taskResult->fetch_assoc();
$stmt->close();

if (!$task) {
    http_response_code(404);
    echo json_encode(['error' => 'Task not found']);
    exit();
}

// Get iterations
$stmt = $conn->prepare("
    SELECT i.*, u.nome as usuario_nome
    FROM tarefa_iteracoes i
    LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
    WHERE i.id_tarefa = ?
    ORDER BY i.created_at DESC
");
$stmt->bind_param("i", $taskId);
$stmt->execute();
$iterationsResult = $stmt->get_result();
$iterations = [];
while ($row = $iterationsResult->fetch_assoc()) {
    $iterations[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'task' => $task,
    'iterations' => $iterations
]);