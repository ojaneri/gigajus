<?php
session_start();
require 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}
require 'includes/notifications_helper.php';

header('Content-Type: application/json');

try {
    // Verificar dados obrigatórios
    if (!isset($_POST['task_id'], $_POST['descricao'], $_POST['token'])) {
        throw new Exception('Dados incompletos');
    }

    // Validar token
    if (!validateToken($_POST['token'], $_POST['task_id'])) {
        throw new Exception('Token inválido ou expirado');
    }

    $task_id = intval($_POST['task_id']);
    $descricao = trim($_POST['descricao']);
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

    if (empty($descricao)) {
        throw new Exception('Descrição não pode estar vazia');
    }

    // Inserir iteração com prepared statement
    $stmt = $conn->prepare("INSERT INTO tarefa_iteracoes (id_tarefa, id_usuario, descricao) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $task_id, $user_id, $descricao);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao salvar iteração: ' . $stmt->error);
    }

    // Atualizar tarefa com prepared statement
    $update_stmt = $conn->prepare("UPDATE tarefas SET ultima_atualizacao = NOW() WHERE id_tarefa = ?");
    $update_stmt->bind_param("i", $task_id);
    if (!$update_stmt->execute()) {
        throw new Exception('Erro ao atualizar tarefa: ' . $update_stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Iteração adicionada com sucesso!',
        'iteration' => [
            'descricao' => htmlspecialchars($descricao),
            'data' => date('d/m/Y H:i')
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    logMessage("ERRO em add_iteration: " . $e->getMessage());
}