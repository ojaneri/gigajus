<?php
require 'config.php';
require 'header.php';

// Verificar token
if (!isset($_GET['token']) || !validateToken($_GET['token'], $_GET['id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit('Token inválido ou expirado');
}

$task_id = intval($_GET['id']);
$conn = new mysqli($servername, $username, $password, $dbname);

// Buscar dados da tarefa
$task = $conn->query("
    SELECT t.*, 
           c.nome as cliente_nome,
           u.nome as responsavel_nome
    FROM tarefas t
    LEFT JOIN clientes c ON t.id_cliente = c.id_cliente
    LEFT JOIN usuarios u ON t.id_usuario = u.id_usuario
    WHERE t.id_tarefa = $task_id
")->fetch_assoc();

// Buscar iterações
$iteracoes = $conn->query("
    SELECT * FROM tarefa_iteracoes 
    WHERE id_tarefa = $task_id 
    ORDER BY created_at DESC
");

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($task['descricao']) ?> | Gigajus</title>
    <link rel="stylesheet" href="assets/css/unified.css">
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($task['descricao']) ?></h1>
        
        <div class="task-info">
            <p>Cliente: <?= htmlspecialchars($task['cliente_nome']) ?></p>
            <p>Responsável: <?= htmlspecialchars($task['responsavel_nome']) ?></p>
            <p>Prazo: <?= date('d/m/Y H:i', strtotime($task['data_horario_final'])) ?></p>
        </div>

        <h2>Iterações</h2>
        <div class="iterations">
            <?php while($iter = $iteracoes->fetch_assoc()): ?>
                <div class="iteration">
                    <p><?= nl2br(htmlspecialchars($iter['descricao'])) ?></p>
                    <small><?= date('d/m/Y H:i', strtotime($iter['created_at'])) ?></small>
                </div>
            <?php endwhile; ?>
        </div>

        <h2>Nova Iteração</h2>
        <form method="POST" action="add_iteration.php">
            <input type="hidden" name="task_id" value="<?= $task_id ?>">
            <input type="hidden" name="token" value="<?= $_GET['token'] ?>">
            
            <textarea name="descricao" required></textarea>
            <button type="submit">Adicionar Iteração</button>
        </form>

        <?php if($task['status'] != 'concluido'): ?>
            <form method="POST" action="complete_task.php">
                <input type="hidden" name="task_id" value="<?= $task_id ?>">
                <input type="hidden" name="token" value="<?= $_GET['token'] ?>">
                <button type="submit" class="btn-complete">Marcar como Concluído</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>