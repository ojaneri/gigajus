<?php
session_start();
require 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'asc';
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : '';

// Build the query based on filters
$query = "
    SELECT t.*,
           c.nome as cliente_nome,
           u_criador.nome as criador_nome,
           u_responsavel.nome as responsavel_nome
    FROM tarefas t
    LEFT JOIN clientes c ON t.id_cliente = c.id_cliente
    LEFT JOIN usuarios u_criador ON t.id_usuario = u_criador.id_usuario
    LEFT JOIN usuarios u_responsavel ON t.id_cliente = u_responsavel.id_usuario
    WHERE (t.id_usuario = ? OR t.id_cliente = ?)
";

// Add status filter
if ($status_filter === 'pending') {
    $query .= " AND t.status != 'concluida'";
} elseif ($status_filter === 'completed') {
    $query .= " AND t.status = 'concluida'";
}

// Add date range filter
if (!empty($date_start)) {
    $query .= " AND DATE(t.data_horario_final) >= ?";
}
if (!empty($date_end)) {
    $query .= " AND DATE(t.data_horario_final) <= ?";
}

// Add sort order
$query .= " ORDER BY t.data_horario_final " . ($sort_order === 'desc' ? 'DESC' : 'ASC');

$stmt = $conn->prepare($query);

// Prepare the parameters for binding
$types = "ii"; // Start with the two user IDs
$params = [$_SESSION['user_id'], $_SESSION['user_id']];

// Add date parameters if they exist
if (!empty($date_start)) {
    $types .= "s";
    $params[] = $date_start;
}
if (!empty($date_end)) {
    $types .= "s";
    $params[] = $date_end;
}

// Bind parameters dynamically
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['data_horario_final']));
    if (!isset($tasks[$date])) {
        $tasks[$date] = [];
    }
    $tasks[$date][] = $row;
}
$stmt->close();
?>

    <div class="task-container">
        <div class="task-header">
            <h2>Minhas Tarefas</h2>
            <div class="task-actions">
                <a href="add_task.php" class="btn-new-task">
                    <i class="fas fa-plus"></i> Nova Tarefa
                </a>
            </div>
        </div>
        
        <div class="filter-container">
            <form method="GET" action="" class="filter-form">
                <!-- Preserve sidebar parameter if present -->
                <?php if (isset($_GET['sidebar']) && $_GET['sidebar'] === 'collapsed'): ?>
                    <input type="hidden" name="sidebar" value="collapsed">
                <?php endif; ?>
                
                <div class="filter-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Todas</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pendentes</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Concluídas</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort">Ordenar por data:</label>
                    <select name="sort" id="sort">
                        <option value="asc" <?php echo $sort_order === 'asc' ? 'selected' : ''; ?>>Crescente</option>
                        <option value="desc" <?php echo $sort_order === 'desc' ? 'selected' : ''; ?>>Decrescente</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_start">Data inicial:</label>
                    <input type="date" id="date_start" name="date_start" value="<?php echo htmlspecialchars($date_start); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="date_end">Data final:</label>
                    <input type="date" id="date_end" name="date_end" value="<?php echo htmlspecialchars($date_end); ?>">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary btn-sm">Aplicar Filtros</button>
                    <a href="calendar.php<?php echo isset($_GET['sidebar']) ? '?sidebar=' . htmlspecialchars($_GET['sidebar']) : ''; ?>" class="btn btn-secondary btn-sm">Limpar</a>
                </div>
            </form>
        </div>

        <?php if (empty($tasks)): ?>
            <p class="empty-state">
                Nenhuma tarefa encontrada.
            </p>
        <?php else: ?>
            <?php foreach ($tasks as $date => $dayTasks): ?>
                <div class="date-header">
                    <span><?php echo date('d/m/Y', strtotime($date)); ?></span>
                    <span class="task-count"><?php echo count($dayTasks); ?> tarefas</span>
                </div>
                <div class="table-responsive">
                    <table class="task-list">
                        <thead>
                            <tr>
                                <th class="col-checkbox"></th>
                                <th>Descrição</th>
                                <th>Responsável</th>
                                <th>Prazo</th>
                                <th>Status</th>
                                <th class="col-actions">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dayTasks as $task): ?>
                                <tr>
                                    <td>
                                        <?php if ($task['status'] !== 'concluida'): ?>
                                            <input type="checkbox" 
                                                   class="task-checkbox"
                                                   onclick="completeTask(<?php echo $task['id_tarefa']; ?>)">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['descricao']); ?></td>
                                    <td>
                                        <?php 
                                        if ($task['email_externo']) {
                                            echo '<i class="fas fa-external-link-alt"></i> ' . htmlspecialchars($task['email_externo']);
                                        } else {
                                            echo htmlspecialchars($task['responsavel_nome']);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('H:i', strtotime($task['data_horario_final'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($task['status']); ?>">
                                            <?php echo $task['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="task-actions">
                                            <button class="btn btn-icon" onclick="toggleTask(<?php echo $task['id_tarefa']; ?>)" title="Detalhes">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($task['status'] !== 'concluida'): ?>
                                                <button class="btn btn-icon btn-success" onclick="showCompleteModal(<?php echo $task['id_tarefa']; ?>)" title="Concluir">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-icon btn-danger" onclick="deleteTask(<?php echo $task['id_tarefa']; ?>)" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="details-row">
                                    <td colspan="6" style="padding: 0;">
                                        <div id="task-<?php echo $task['id_tarefa']; ?>" class="task-details">
                                            <div class="task-meta">
                                                <p><strong>Criado por:</strong> <?php echo htmlspecialchars($task['criador_nome']); ?></p>
                                                <p><strong>Data de Criação:</strong> <?php echo date('d/m/Y H:i', strtotime($task['data_hora_criacao'])); ?></p>
                                            </div>
                                            
                                            <?php if ($task['descricao_longa']): ?>
                                                <div class="task-description">
                                                    <?php echo nl2br(htmlspecialchars($task['descricao_longa'])); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="iterations-list" id="iterations-<?php echo $task['id_tarefa']; ?>">
                                                <h4>Iterações</h4>
                                                <!-- Iterações serão carregadas via AJAX -->
                                            </div>
                                            
                                            <div class="new-iteration">
                                                <textarea class="form-control" placeholder="Nova iteração..." id="new-iteration-<?php echo $task['id_tarefa']; ?>"></textarea>
                                                <button class="btn btn-primary" onclick="addIteration(<?php echo $task['id_tarefa']; ?>)">
                                                    Adicionar Iteração
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<style>
    .filter-container {
        background-color: #f8f9fa;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        border: 1px solid #dee2e6;
    }
    
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .filter-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .filter-group label {
        font-weight: bold;
        margin-bottom: 0;
    }
    
    .filter-group select {
        padding: 6px 10px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        background-color: white;
    }
    
    .task-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .task-actions {
        display: flex;
        gap: 10px;
    }
</style>

<script>
function toggleTask(taskId) {
    const content = document.getElementById(`task-${taskId}`);
    if (!content.classList.contains('active')) {
        loadIterations(taskId);
    }
    content.classList.toggle('active');
}

function loadIterations(taskId) {
    const container = document.getElementById(`iterations-${taskId}`);
    
    fetch(`get_iterations.php?task_id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                container.innerHTML = '<h4>Iterações</h4>' + data.iterations.map(iteration => `
                    <div class="iteration-item">
                        <small>por ${iteration.usuario_nome || 'Usuário Externo'} em ${new Date(iteration.created_at).toLocaleString()}</small>
                        ${iteration.descricao}
                    </div>
                `).join('');
            }
        });
}

function addIteration(taskId) {
    const textarea = document.getElementById(`new-iteration-${taskId}`);
    const description = textarea.value.trim();
    
    if (!description) return;
    
    fetch('add_iteration.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            taskId: taskId,
            description: description
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            textarea.value = '';
            loadIterations(taskId);
        } else {
            alert('Erro ao adicionar iteração: ' + data.message);
        }
    });
}

function completeTask(taskId) {
    if (!confirm('Deseja marcar esta tarefa como concluída?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('taskId', taskId);
    
    fetch('complete_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao completar tarefa: ' + data.message);
        }
    });
}
// Delete task function
function deleteTask(taskId) {
    if (!confirm('Tem certeza que deseja excluir esta tarefa?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('taskId', taskId);
    formData.append('action', 'delete');
    
    fetch('complete_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao excluir tarefa: ' + data.message);
        }
    });
}

// Show complete task modal
function showCompleteModal(taskId) {
    document.getElementById('complete-task-id').value = taskId;
    document.getElementById('complete-task-modal').style.display = 'block';
}

// Close modal
function closeModal() {
    document.getElementById('complete-task-modal').style.display = 'none';
    document.getElementById('complete-comment').value = '';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('complete-task-modal');
    if (event.target === modal) {
        closeModal();
    }
}

// Complete task with comment
function completeTaskWithComment() {
    const taskId = document.getElementById('complete-task-id').value;
    const comment = document.getElementById('complete-comment').value;
    
    const formData = new FormData();
    formData.append('taskId', taskId);
    formData.append('comment', comment);
    
    fetch('complete_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao completar tarefa: ' + data.message);
        }
    });
    
    closeModal();
}
</script>

<!-- Complete Task Modal -->
<div id="complete-task-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Concluir Tarefa</h3>
        <p>Adicione um comentário sobre a conclusão desta tarefa:</p>
        <input type="hidden" id="complete-task-id">
        <textarea id="complete-comment" class="form-control" rows="4" placeholder="Comentário de conclusão..."></textarea>
        <div class="modal-actions">
            <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancelar</button>
            <button type="button" onclick="completeTaskWithComment()" class="btn btn-success">Concluir Tarefa</button>
        </div>
    </div>
</div>

<style>
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.4);
    }
    
    .modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 50%;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close:hover {
        color: black;
    }
    
    .modal-actions {
        margin-top: 20px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    
    /* Button styles */
    .btn-icon {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        margin-right: 5px;
        background-color: #f8f9fa;
        color: #495057;
    }
    
    .btn-icon:hover {
        background-color: #e9ecef;
    }
    
    .btn-success {
        background-color: #28a745;
        color: white;
    }
    
    .btn-success:hover {
        background-color: #218838;
    }
    
    .btn-danger {
        background-color: #dc3545;
        color: white;
    }
    
    .btn-danger:hover {
        background-color: #c82333;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
    }
</style>
