<?php
require 'config.php';
include 'header.php';

// Obtendo a lista de clientes
logMessage("[add_appointment.php] Iniciando a recuperação de dados para o formulário.");
$stmtClientes = $conn->prepare("SELECT id_cliente, nome FROM clientes WHERE ativo = 1");
$stmtClientes->execute();
$clientes = $stmtClientes->get_result();
?>
<div class="content">
    <div class="form-container">
        <div class="form-header">
            <h2><i class="fas fa-handshake"></i> Adicionar Novo Atendimento</h2>
        </div>
        
        <form action="add_appointment2.php" method="POST" class="improved-form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="id_cliente">
                        <i class="fas fa-user"></i> Cliente
                    </label>
                    <select id="id_cliente" name="id_cliente" required onchange="loadProcessos()" class="form-control">
                        <option value="">Selecione um cliente</option>
                        <?php while ($cliente = $clientes->fetch_assoc()): ?>
                            <option value="<?php echo $cliente['id_cliente']; ?>"><?php echo htmlspecialchars($cliente['nome']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="id_processo">
                        <i class="fas fa-gavel"></i> Vincular a Processo (opcional)
                    </label>
                    <select id="id_processo" name="id_processo" class="form-control">
                        <option value="">Nenhum</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="data">
                        <i class="fas fa-calendar-alt"></i> Data do Atendimento
                    </label>
                    <input type="datetime-local" id="data" name="data" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="responsavel">
                        <i class="fas fa-user-tie"></i> Responsável
                    </label>
                    <input type="text" id="responsavel" name="responsavel" required class="form-control">
                </div>
                
                <div class="form-group full-width">
                    <label for="descricao">
                        <i class="fas fa-align-left"></i> Descrição
                    </label>
                    <textarea id="descricao" name="descricao" required class="form-control"></textarea>
                </div>
                
                <div class="form-group full-width">
                    <label for="observacoes">
                        <i class="fas fa-sticky-note"></i> Observações (opcional)
                    </label>
                    <textarea id="observacoes" name="observacoes" class="form-control"></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="appointments.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Adicionar Atendimento
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    function loadProcessos() {
        var idCliente = document.getElementById('id_cliente').value;
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'get_processos.php?id_cliente=' + idCliente, true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                var processos = JSON.parse(xhr.responseText);
                var processoSelect = document.getElementById('id_processo');
                processoSelect.innerHTML = '<option value="">Nenhum</option>';
                processos.forEach(function(processo) {
                    processoSelect.innerHTML += '<option value="' + processo.id_processo + '">' + processo.numero_processo + '</option>';
                });
            }
        };
        xhr.send();
    }
</script>
