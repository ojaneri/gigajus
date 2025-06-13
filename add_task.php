<?php
session_start();
require 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Combine date and time for deadline
        $data_horario_final = $_POST['data'] . ' ' . $_POST['hora'];
        
        // Handle assignee based on type
        $id_cliente = null;
        $email_externo = null;
        $token = null;
        
        if ($_POST['assignee_type'] === 'internal') {
            if (empty($_POST['id_responsavel'])) {
                throw new Exception("Responsável interno não selecionado");
            }
            $id_cliente = intval($_POST['id_responsavel']);
        } else {
            if (empty($_POST['email_externo'])) {
                throw new Exception("Email do responsável externo não fornecido");
            }
            $email_externo = $_POST['email_externo'];
            $token = bin2hex(random_bytes(32));
        }
        
        // Insert task
        $stmt = $conn->prepare("
            INSERT INTO tarefas (
                id_usuario,          -- quem criou
                id_cliente,          -- quem deve fazer (se for usuário interno)
                email_externo,       -- email do usuário externo
                token,               -- token para acesso externo
                descricao,           -- descrição curta
                descricao_longa,     -- descrição detalhada
                data_horario_final,  -- prazo
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente')
        ");
        
        $descricao_longa = isset($_POST['descricao_longa']) ? $_POST['descricao_longa'] : '';
        
        $stmt->bind_param(
            "iisssss",
            $_SESSION['user_id'],      // criador (int)
            $id_cliente,               // responsável interno (int)
            $email_externo,            // email externo (string)
            $token,                    // token (string)
            $_POST['descricao'],       // descrição curta (string)
            $descricao_longa,          // descrição longa (string)
            $data_horario_final        // prazo (string)
        );
        
        if ($stmt->execute()) {
            $taskId = $stmt->insert_id;
            
            // If it's an external user, send email
            if ($email_externo && $token) {
                $taskUrl = "https://janeri.com.br/gigajus/v2/tarefa-externo.php?token=" . $token;
                $emailBody = "Uma nova tarefa foi designada para você:\n\n";
                $emailBody .= "Descrição: " . $_POST['descricao'] . "\n";
                $emailBody .= "Prazo: " . date('d/m/Y H:i', strtotime($data_horario_final)) . "\n\n";
                $emailBody .= "Para visualizar a tarefa e adicionar iterações, acesse:\n" . $taskUrl;
                
                mail($email_externo, 
                     "Nova Tarefa - GigaJus", 
                     $emailBody,
                     "From: sistema@gigajus.com.br\r\n" .
                     "Content-Type: text/plain; charset=UTF-8\r\n"
                );
            }
            
            header("Location: calendar.php?success=1");
            exit();
        } else {
            throw new Exception("Erro ao criar tarefa: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<div class="content">
    <div class="form-container">
        <div class="form-header">
            <h2><i class="fas fa-tasks"></i> Nova Tarefa</h2>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="improved-form">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="descricao">
                        <i class="fas fa-heading"></i> Descrição Curta
                    </label>
                    <input type="text" id="descricao" name="descricao" class="form-control" required>
                </div>
                
                <div class="form-group full-width">
                    <label for="descricao_longa">
                        <i class="fas fa-align-left"></i> Descrição Longa
                    </label>
                    <textarea id="descricao_longa" name="descricao_longa" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="data">
                        <i class="fas fa-calendar-alt"></i> Data de Prazo
                    </label>
                    <input type="date" id="data" name="data" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="hora">
                        <i class="fas fa-clock"></i> Hora de Prazo
                    </label>
                    <input type="time" id="hora" name="hora" class="form-control" required>
                </div>
            </div>
            
            <div class="assignee-section">
                <h3><i class="fas fa-user-check"></i> Responsável pela Tarefa</h3>
                
                <div class="assignee-toggle">
                    <label class="radio-label">
                        <input type="radio" name="assignee_type" value="internal" checked onchange="toggleAssigneeFields()">
                        <span><i class="fas fa-user-tie"></i> Usuário Interno</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="assignee_type" value="external" onchange="toggleAssigneeFields()">
                        <span><i class="fas fa-user-friends"></i> Usuário Externo</span>
                    </label>
                </div>
                
                <div id="internal-assignee" class="assignee-fields active">
                    <div class="form-group">
                        <label for="id_responsavel">
                            <i class="fas fa-user-tie"></i> Selecione o Responsável
                        </label>
                        <select id="id_responsavel" name="id_responsavel" class="form-control">
                            <?php
                            $result = $conn->query("SELECT id_usuario, nome FROM usuarios ORDER BY nome");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['id_usuario']}'>{$row['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div id="external-assignee" class="assignee-fields">
                    <div class="form-group">
                        <label for="email_externo">
                            <i class="fas fa-envelope"></i> Email do Responsável
                        </label>
                        <input type="email" id="email_externo" name="email_externo" class="form-control" placeholder="email@exemplo.com">
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i> Um email com instruções será enviado para este endereço.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="calendar.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Criar Tarefa
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAssigneeFields() {
    const type = document.querySelector('input[name="assignee_type"]:checked').value;
    document.getElementById('internal-assignee').classList.toggle('active', type === 'internal');
    document.getElementById('external-assignee').classList.toggle('active', type === 'external');
    
    const internalSelect = document.querySelector('select[name="id_responsavel"]');
    const externalEmail = document.querySelector('input[name="email_externo"]');
    
    if (type === 'internal') {
        internalSelect.required = true;
        externalEmail.required = false;
    } else {
        internalSelect.required = false;
        externalEmail.required = true;
    }
}
</script>