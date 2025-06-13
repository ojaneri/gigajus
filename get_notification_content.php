<?php
session_start();
require 'config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Verifica se o ID da notificação foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de notificação inválido']);
    exit();
}

$notification_id = intval($_GET['id']);

// Verifica se a coluna teor existe na tabela notifications
$check_column = $conn->query("SHOW COLUMNS FROM notifications LIKE 'teor'");
if ($check_column->num_rows == 0) {
    // A coluna não existe, vamos tentar adicioná-la
    $add_column = $conn->query("ALTER TABLE notifications ADD COLUMN teor TEXT AFTER data_publicacao");
    
    if ($add_column) {
        // Coluna adicionada com sucesso
        error_log("Coluna 'teor' adicionada automaticamente à tabela notifications");
    } else {
        // Falha ao adicionar a coluna
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Coluna teor não existe na tabela e não foi possível adicioná-la: ' . $conn->error]);
        exit();
    }
}

// Busca o teor da notificação
$query = "SELECT teor FROM notifications WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $notification_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $notification = $result->fetch_assoc();
    
    // Verifica se o teor está vazio
    if (empty($notification['teor'])) {
        // Tenta buscar o teor da API ou de outra fonte
        // Por enquanto, apenas retorna uma mensagem informativa
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'O teor desta notificação está vazio. Pode ser necessário atualizar os dados da API.']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'teor' => $notification['teor']]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Notificação não encontrada com ID: ' . $notification_id]);
}

$stmt->close();
$conn->close();
?>