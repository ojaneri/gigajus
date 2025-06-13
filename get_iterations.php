<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['task_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit();
}

$taskId = intval($_GET['task_id']);

// Get iterations with user information
$stmt = $conn->prepare("
    SELECT i.*, u.nome as usuario_nome
    FROM tarefa_iteracoes i
    LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
    WHERE i.id_tarefa = ?
    ORDER BY i.created_at DESC
");

$stmt->bind_param("i", $taskId);
$stmt->execute();
$result = $stmt->get_result();

$iterations = [];
while ($row = $result->fetch_assoc()) {
    $iterations[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'iterations' => $iterations
]);