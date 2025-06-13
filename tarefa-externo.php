<?php
require 'config.php';

// Verify token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die('Token não fornecido');
}

$token = $_GET['token'];

// Get task details
$stmt = $conn->prepare("
    SELECT t.*, 
           u_criador.nome as criador_nome
    FROM tarefas t 
    LEFT JOIN usuarios u_criador ON t.id_usuario = u_criador.id_usuario
    WHERE t.token = ?
");

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();
$stmt->close();

if (!$task) {
    die('Tarefa não encontrada');
}

// Handle task completion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'complete') {
            // Update task status
            $stmt = $conn->prepare("UPDATE tarefas SET status = 'concluida' WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $stmt->close();

            // Notify task creator
            $notifyStmt = $conn->prepare("
                INSERT INTO notificacoes (id_usuario, tipo, mensagem, data_envio)
                VALUES (?, 'Email', ?, NOW())
            ");
            $message = "A tarefa '{$task['descricao']}' foi marcada como concluída pelo usuário externo.";
            $notifyStmt->bind_param("is", $task['id_usuario'], $message);
            $notifyStmt->execute();
            $notifyStmt->close();

            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
        else if ($_POST['action'] === 'add_iteration') {
            // Add new iteration
            if (!empty($_POST['descricao'])) {
                $stmt = $conn->prepare("
                    INSERT INTO tarefa_iteracoes (id_tarefa, descricao, id_usuario, created_at)
                    VALUES (?, ?, NULL, NOW())
                ");
                $stmt->bind_param("is", $task['id_tarefa'], $_POST['descricao']);
                $stmt->execute();
                $stmt->close();

                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        }
    }
}

// Get iterations
$stmt = $conn->prepare("
    SELECT i.*, u.nome as usuario_nome
    FROM tarefa_iteracoes i
    LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
    WHERE i.id_tarefa = ?
    ORDER BY i.created_at DESC
");

$stmt->bind_param("i", $task['id_tarefa']);
$stmt->execute();
$iterationsResult = $stmt->get_result();
$iterations = [];
while ($row = $iterationsResult->fetch_assoc()) {
    $iterations[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarefa Externa | GigaJus</title>
    <style>
        :root {
            --primary-color: #283593;
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #333333;
            --border-color: #e0e0e0;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--bg-primary);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .task-header {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .task-meta {
            color: #666;
            font-size: 0.9em;
            margin: 10px 0;
        }
        
        .task-description {
            margin: 20px 0;
            white-space: pre-wrap;
        }
        
        .iterations {
            margin-top: 30px;
        }
        
        .iteration-item {
            background: var(--bg-secondary);
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .iteration-meta {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }
        
        .form-group {
            margin: 20px 0;
        }
        
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            background: var(--primary-color);
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .status-pendente {
            background: #FFC107;
            color: black;
        }
        
        .status-concluida {
            background: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="task-header">
            <h1><?php echo htmlspecialchars($task['descricao']); ?></h1>
            <div class="task-meta">
                <p>Criado por: <?php echo htmlspecialchars($task['criador_nome']); ?></p>
                <p>Data de Criação: <?php echo date('d/m/Y H:i', strtotime($task['data_hora_criacao'])); ?></p>
                <p>Prazo: <?php echo date('d/m/Y H:i', strtotime($task['data_horario_final'])); ?></p>
                <p>Status: <span class="status status-<?php echo strtolower($task['status']); ?>">
                    <?php echo $task['status']; ?>
                </span></p>
            </div>
        </div>

        <?php if ($task['descricao_longa']): ?>
            <div class="task-description">
                <?php echo nl2br(htmlspecialchars($task['descricao_longa'])); ?>
            </div>
        <?php endif; ?>

        <?php if ($task['status'] !== 'concluida'): ?>
            <form method="post" style="margin: 20px 0;">
                <input type="hidden" name="action" value="complete">
                <button type="submit" class="btn">Marcar como Concluída</button>
            </form>
        <?php endif; ?>

        <div class="iterations">
            <h2>Iterações</h2>
            
            <form method="post" class="form-group">
                <input type="hidden" name="action" value="add_iteration">
                <textarea name="descricao" rows="4" placeholder="Adicione uma nova iteração..." required></textarea>
                <button type="submit" class="btn">Adicionar Iteração</button>
            </form>

            <?php foreach ($iterations as $iteration): ?>
                <div class="iteration-item">
                    <?php echo nl2br(htmlspecialchars($iteration['descricao'])); ?>
                    <div class="iteration-meta">
                        por <?php echo $iteration['usuario_nome'] ? htmlspecialchars($iteration['usuario_nome']) : 'Usuário Externo'; ?>
                        em <?php echo date('d/m/Y H:i', strtotime($iteration['created_at'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>