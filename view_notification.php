<?php
session_start();
require 'config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verifica se o ID da notificação foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID de notificação inválido.";
    exit();
}

$notification_id = intval($_GET['id']);

// Busca os detalhes da notificação
$query = "SELECT n.* FROM notifications n WHERE n.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $notification_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Notificação não encontrada.";
    exit();
}

$notification = $result->fetch_assoc();

// Verifica se a coluna teor existe
$check_column = $conn->query("SHOW COLUMNS FROM notifications LIKE 'teor'");
if ($check_column->num_rows == 0) {
    // A coluna não existe, vamos tentar adicioná-la
    $add_column = $conn->query("ALTER TABLE notifications ADD COLUMN teor TEXT AFTER data_publicacao");
    
    if ($add_column) {
        // Coluna adicionada com sucesso
        error_log("Coluna 'teor' adicionada automaticamente à tabela notifications");
    } else {
        // Falha ao adicionar a coluna
        error_log("Erro ao adicionar coluna 'teor': " . $conn->error);
    }
    
    // Não temos teor para este registro ainda
    $notification['teor'] = '';
} else if (empty($notification['teor'])) {
    // A coluna existe mas está vazia
    $notification['teor'] = 'Conteúdo não disponível. Pode ser necessário atualizar os dados da API.';
}

// Obtém a lista de usuários para o formulário de criação de tarefas
$users_query = "SELECT id_usuario as id, nome as name FROM usuarios WHERE ativo = 1 ORDER BY nome";
$users_result = $conn->query($users_query);
$users = [];

if ($users_result) {
    while ($user = $users_result->fetch_assoc()) {
        $users[] = $user;
    }
}

$stmt->close();
include 'header.php';
?>

<style>
    .container {
        max-width: 1000px;
        margin: 20px auto;
        padding: 20px;
        background-color: #fff;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    h1 {
        margin-top: 0;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .notification-details {
        margin-bottom: 20px;
    }
    
    .notification-details dl {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 10px;
    }
    
    .notification-details dt {
        font-weight: bold;
    }
    
    .notification-content {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 4px;
        border-left: 3px solid #007bff;
        white-space: pre-wrap;
        margin-bottom: 20px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .status-pending {
        background-color: #ffecb3;
        color: #856404;
    }
    
    .status-processed {
        background-color: #d4edda;
        color: #155724;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .task-form {
        margin-top: 20px;
        border-top: 1px solid #ddd;
        padding-top: 15px;
    }
    
    .task-form h3 {
        margin-top: 0;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .form-group textarea {
        min-height: 100px;
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 15px;
    }
    
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }
    
    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }
    
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }
</style>
    
    <div class="container">
        <h1>Detalhes da Intimação</h1>
        
        <?php if (!empty($_GET['status'])): ?>
            <div class="alert <?php echo ($_GET['status'] == 'processed' || $_GET['status'] == 'task_created') ? 'alert-success' : 'alert-danger'; ?>">
                <?php
                    switch ($_GET['status']) {
                        case 'processed':
                            echo "Intimação marcada como processada com sucesso.";
                            break;
                        case 'task_created':
                            echo "Tarefa criada com sucesso.";
                            break;
                        case 'already_processed':
                            echo "Esta intimação já foi processada anteriormente.";
                            break;
                        case 'error':
                            echo "Erro ao processar a solicitação.";
                            if (!empty($_GET['message'])) {
                                echo " " . htmlspecialchars($_GET['message']);
                            }
                            break;
                        case 'invalid_id':
                            echo "ID de intimação inválido.";
                            break;
                        case 'not_found':
                            echo "Intimação não encontrada.";
                            break;
                        case 'invalid_request':
                            echo "Requisição inválida.";
                            break;
                        default:
                            echo "Status desconhecido.";
                    }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="notifications.php" class="btn btn-secondary">Voltar para Lista</a>
            
            <?php if (!$notification['processada']): ?>
                <form action="process_notification.php" method="POST" style="display: inline;">
                    <input type="hidden" name="notification_id" value="<?php echo $notification_id; ?>">
                    <input type="hidden" name="return_url" value="view_notification.php?id=<?php echo $notification_id; ?>">
                    <button type="submit" name="mark_processed" class="btn btn-primary">Marcar como Processada</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="notification-details">
            <dl>
                <dt>Processo:</dt>
                <dd><?php echo htmlspecialchars($notification['numero_processo']); ?></dd>
                
                <dt>Classe:</dt>
                <dd><?php echo htmlspecialchars($notification['classe']); ?></dd>
                
                <dt>Tribunal:</dt>
                <dd><?php echo htmlspecialchars($notification['tribunal']); ?></dd>
                
                <dt>Advogados:</dt>
                <dd><?php echo htmlspecialchars($notification['advogados'] ?? 'Não informado'); ?></dd>
                
                <dt>Parte Ativa:</dt>
                <dd><?php echo htmlspecialchars($notification['polo_ativo'] ?? 'Não informado'); ?></dd>
                
                <dt>Parte Passiva:</dt>
                <dd><?php echo htmlspecialchars($notification['polo_passivo'] ?? 'Não informado'); ?></dd>
                
                <dt>Data de Publicação:</dt>
                <dd>
                    <?php
                        echo !empty($notification['data_publicacao'])
                            ? date('d/m/Y', strtotime($notification['data_publicacao']))
                            : 'Não informada';
                    ?>
                </dd>
                
                <dt>Status:</dt>
                <dd>
                    <span class="status-badge <?php echo $notification['processada'] ? 'status-processed' : 'status-pending'; ?>">
                        <?php echo $notification['processada'] ? 'Processada' : 'Pendente'; ?>
                    </span>
                </dd>
            </dl>
            
            <h3>Teor da Intimação:</h3>
            <div class="notification-content">
                <?php echo htmlspecialchars($notification['teor'] ?? 'Conteúdo não disponível'); ?>
            </div>
        </div>
        
        <div class="task-form">
            <h3>Adicionar Tarefa</h3>
            <form action="create_task.php" method="POST">
                <input type="hidden" name="notification_id" value="<?php echo $notification_id; ?>">
                <input type="hidden" name="return_url" value="view_notification.php?id=<?php echo $notification_id; ?>">
                
                <div class="form-group">
                    <label for="task_title">Título da Tarefa:</label>
                    <input type="text" id="task_title" name="task_title" required 
                           value="Tarefa referente ao processo <?php echo htmlspecialchars($notification['numero_processo']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="task_description">Descrição:</label>
                    <textarea id="task_description" name="task_description" required>Tarefa relacionada à intimação do processo <?php echo htmlspecialchars($notification['numero_processo']); ?> (<?php echo htmlspecialchars($notification['classe']); ?>) do tribunal <?php echo htmlspecialchars($notification['tribunal']); ?>.</textarea>
                </div>
                
                <div class="form-group">
                    <label for="task_user">Responsável:</label>
                    <select id="task_user" name="task_user" required>
                        <option value="">Selecione um usuário</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="task_datetime">Data de Vencimento:</label>
                    <input type="datetime-local" id="task_datetime" name="task_datetime" required
                           value="<?php
                               // Define a data de vencimento para 3 dias úteis a partir de hoje
                               $today = new DateTime();
                               $dueDate = clone $today;
                               $addedDays = 0;
                               
                               while ($addedDays < 3) {
                                   $dueDate->modify('+1 day');
                                   // Verifica se não é fim de semana (0 = Domingo, 6 = Sábado)
                                   $dayOfWeek = (int)$dueDate->format('w');
                                   if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
                                       $addedDays++;
                                   }
                               }
                               
                               // Formata a data para o formato esperado pelo input datetime-local
                               echo $dueDate->format('Y-m-d') . 'T17:00';
                           ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Criar Tarefa</button>
                </div>
            </form>
        </div>
    </div>