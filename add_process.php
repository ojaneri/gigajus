<?php
require 'config.php';
include 'header.php';

// Obtendo a lista de clientes para o formulário
logMessage("[add_process.php] Passo 1: Obtendo a lista de clientes.");
$stmt = $conn->prepare("SELECT id_cliente, nome FROM clientes WHERE ativo = 1");
$stmt->execute();
$clientes = $stmt->get_result();
?>
<div class="content">
    <div class="form-container">
        <div class="form-header">
            <h2><i class="fas fa-gavel"></i> Adicionar Novo Processo</h2>
        </div>
        
        <form id="addProcessForm" action="add_process2.php" method="POST" class="improved-form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="id_cliente">
                        <i class="fas fa-user"></i> Cliente
                    </label>
                    <select id="id_cliente" name="id_cliente" required class="form-control">
                        <?php while ($cliente = $clientes->fetch_assoc()): ?>
                            <option value="<?php echo $cliente['id_cliente']; ?>">
                                <?php echo htmlspecialchars($cliente['nome']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="numero_processo">
                        <i class="fas fa-hashtag"></i> Número do Processo
                    </label>
                    <input type="text" id="numero_processo" name="numero_processo" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="tribunal">
                        <i class="fas fa-university"></i> Tribunal
                    </label>
                    <input type="text" id="tribunal" name="tribunal" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="status">
                        <i class="fas fa-info-circle"></i> Status
                    </label>
                    <input type="text" id="status" name="status" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="data_abertura">
                        <i class="fas fa-calendar-plus"></i> Data de Abertura
                    </label>
                    <input type="date" id="data_abertura" name="data_abertura" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="data_fechamento">
                        <i class="fas fa-calendar-check"></i> Data de Fechamento
                    </label>
                    <input type="date" id="data_fechamento" name="data_fechamento" class="form-control">
                </div>
                
                <div class="form-group full-width">
                    <label for="descricao">
                        <i class="fas fa-align-left"></i> Descrição
                    </label>
                    <textarea id="descricao" name="descricao" required class="form-control"></textarea>
                </div>
                
                <div class="form-group full-width">
                    <label for="status_externo">
                        <i class="fas fa-external-link-alt"></i> Status Externo
                    </label>
                    <textarea id="status_externo" name="status_externo" class="form-control"></textarea>
                </div>
                
                <div class="form-group full-width">
                    <label for="observacoes">
                        <i class="fas fa-sticky-note"></i> Observações
                    </label>
                    <textarea id="observacoes" name="observacoes" class="form-control"></textarea>
                </div>
                
                <div class="form-group full-width">
                    <label>
                        <i class="fas fa-bell"></i> Notificações
                    </label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="notificar_whatsapp" name="notificar[]" value="whatsapp">
                            <span><i class="fab fa-whatsapp"></i> WhatsApp</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="notificar_sms" name="notificar[]" value="sms">
                            <span><i class="fas fa-sms"></i> SMS</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="notificar_email" name="notificar[]" value="email">
                            <span><i class="fas fa-envelope"></i> E-mail</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="periodicidade_notificacao">
                        <i class="fas fa-clock"></i> Periodicidade (dias)
                    </label>
                    <input type="number" id="periodicidade_notificacao" name="periodicidade_notificacao" value="30" required class="form-control">
                </div>
            </div>
            
            <div class="form-actions">
                <a href="processes.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Adicionar Processo
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    document.getElementById('addProcessForm').addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);
        fetch('add_process2.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.notificationType === 'success') {
                showNotification(data.message, 'success');
                setTimeout(function() {
                    window.location.href = 'processes.php';
                }, 1500); // espera 1.5 segundos antes de redirecionar
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao adicionar o processo.', 'error');
        });
    });
</script>
