<?php
session_start();
require 'config.php';
require 'includes/notifications_helper.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['taskId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit();
}

$taskId = intval($_POST['taskId']);
$action = isset($_POST['action']) ? $_POST['action'] : 'complete';
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

try {
    // Get task details first
    $stmt = $conn->prepare("
        SELECT t.*, u.nome as criador_nome
        FROM tarefas t
        LEFT JOIN usuarios u ON t.id_usuario = u.id_usuario
        WHERE t.id_tarefa = ?
    ");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    $stmt->close();

    if (!$task) {
        throw new Exception("Tarefa não encontrada");
    }
    
    // Handle different actions
    if ($action === 'delete') {
        // Delete the task
        $stmt = $conn->prepare("DELETE FROM tarefas WHERE id_tarefa = ?");
        $stmt->bind_param("i", $taskId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Tarefa excluída com sucesso'
            ]);
        } else {
            throw new Exception("Erro ao excluir tarefa: " . $stmt->error);
        }
        
        $stmt->close();
    } else {
        // Update task status to completed
        $stmt = $conn->prepare("UPDATE tarefas SET status = 'concluida' WHERE id_tarefa = ?");
        $stmt->bind_param("i", $taskId);
        
        if ($stmt->execute()) {
            // If there's a comment, add it as an iteration
            if (!empty($comment)) {
                // Check if iteracoes table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'iteracoes'");
                
                if ($table_check->num_rows == 0) {
                    // Create the iteracoes table if it doesn't exist
                    $create_table = $conn->query("
                        CREATE TABLE IF NOT EXISTS iteracoes (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            id_tarefa INT,
                            id_usuario INT,
                            descricao TEXT,
                            tipo VARCHAR(50) DEFAULT 'comentario',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (id_tarefa) REFERENCES tarefas(id_tarefa) ON DELETE CASCADE
                        )
                    ");
                    
                    if (!$create_table) {
                        logMessage("Erro ao criar tabela iteracoes: " . $conn->error);
                    }
                }
                
                try {
                    $comment_stmt = $conn->prepare("
                        INSERT INTO iteracoes (id_tarefa, id_usuario, descricao, tipo)
                        VALUES (?, ?, ?, 'conclusao')
                    ");
                    $comment_stmt->bind_param("iis", $taskId, $_SESSION['user_id'], $comment);
                    $comment_stmt->execute();
                    $comment_stmt->close();
                } catch (Exception $comment_ex) {
                    // Log the error but continue with task completion
                    logMessage("Erro ao adicionar comentário: " . $comment_ex->getMessage());
                }
            }
            // Send completion notification
            notifyTaskCompletion($taskId);
            
            echo "<script>showNotification('Tarefa marcada como concluída!', 'success');</script>";
            echo json_encode([
                'success' => true,
                'message' => 'Tarefa marcada como concluída'
            ]);
        } else {
            echo "<script>showNotification('Erro ao atualizar tarefa: " . $stmt->error . "', 'error');</script>";
            throw new Exception("Erro ao atualizar tarefa: " . $stmt->error);
        }
        
        $stmt->close();
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    logMessage("Erro ao completar tarefa: " . $e->getMessage());
}