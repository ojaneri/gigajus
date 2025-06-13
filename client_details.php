<?php
session_start();
require 'config.php';
include 'header.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar se o ID do cliente foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>showNotification('ID de cliente inválido.', 'error');</script>";
    echo "<script>setTimeout(function() { window.location.href = 'clients.php'; }, 2000);</script>";
    exit();
}

$client_id = intval($_GET['id']);

// Obter informações do cliente
$stmt = $conn->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>showNotification('Cliente não encontrado.', 'error');</script>";
    echo "<script>setTimeout(function() { window.location.href = 'clients.php'; }, 2000);</script>";
    exit();
}

$client = $result->fetch_assoc();

// Obter processos do cliente
$stmt = $conn->prepare("SELECT * FROM processos WHERE id_cliente = ? ORDER BY data_abertura DESC");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$processes_result = $stmt->get_result();
$processes = $processes_result->fetch_all(MYSQLI_ASSOC);

// Obter IDs dos processos para usar na consulta de atendimentos
$process_ids = [];
foreach ($processes as $process) {
    $process_ids[] = $process['id_processo'];
}

// Verificar se a tabela atendimentos tem a coluna id_processo
$check_column = $conn->query("SHOW COLUMNS FROM atendimentos LIKE 'id_processo'");
$has_id_processo = $check_column->num_rows > 0;

// Obter atendimentos vinculados a processos do cliente
$appointments_linked = [];
if (!empty($process_ids) && $has_id_processo) {
    $process_ids_str = implode(',', $process_ids);
    $sql = "SELECT a.*, p.numero_processo
            FROM atendimentos a
            JOIN processos p ON a.id_processo = p.id_processo
            WHERE a.id_processo IN ($process_ids_str)
            ORDER BY a.data DESC";
    $appointments_linked_result = $conn->query($sql);
    if ($appointments_linked_result) {
        $appointments_linked = $appointments_linked_result->fetch_all(MYSQLI_ASSOC);
    }
}

// Obter atendimentos não vinculados a processos mas pertencentes ao cliente
if ($has_id_processo) {
    $stmt = $conn->prepare("SELECT * FROM atendimentos WHERE id_cliente = ? AND (id_processo IS NULL OR id_processo = 0) ORDER BY data DESC");
} else {
    $stmt = $conn->prepare("SELECT * FROM atendimentos WHERE id_cliente = ? ORDER BY data DESC");
}
$stmt->bind_param("i", $client_id);
$stmt->execute();
$appointments_unlinked_result = $stmt->get_result();
$appointments_unlinked = $appointments_unlinked_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="content">
    <div class="client-details-container">
        <div class="client-details-header">
            <h2><i class="fas fa-user"></i> Detalhes do Cliente</h2>
            <div class="client-info">
                <h3><?php echo htmlspecialchars($client['nome']); ?></h3>
                <div class="client-meta">
                    <span><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($client['cpf_cnpj']); ?></span>
                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($client['email']); ?></span>
                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($client['telefone']); ?></span>
                    <?php if (isset($client['data_cadastro'])): ?>
                    <span><i class="fas fa-calendar"></i> Cadastrado em: <?php echo date('d/m/Y', strtotime($client['data_cadastro'])); ?></span>
                    <?php endif; ?>
                    <span class="status-badge status-<?php echo $client['ativo'] ? 'ativo' : 'inativo'; ?>">
                        <?php echo $client['ativo'] ? 'Ativo' : 'Inativo'; ?>
                    </span>
                </div>
            </div>
            <div class="client-actions">
                <a href="edit_client.php?id=<?php echo $client_id; ?>" class="btn-icon btn-edit" title="Editar Cliente">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="arquivos_client.php?id=<?php echo $client_id; ?>" class="btn-icon btn-files" title="Arquivos do Cliente">
                    <i class="fas fa-folder-open"></i>
                </a>
                <a href="clients.php" class="btn-icon btn-back" title="Voltar para Lista de Clientes">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </div>

        <!-- Processos do Cliente -->
        <div class="detail-section">
            <div class="section-header">
                <h3><i class="fas fa-gavel"></i> Processos do Cliente</h3>
                <a href="add_process.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Novo Processo
                </a>
            </div>
            
            <?php if (empty($processes)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>Nenhum processo encontrado para este cliente.</p>
                </div>
            <?php else: ?>
                <table class="improved-table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Tribunal</th>
                            <th>Status</th>
                            <th>Data de Abertura</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($processes as $process): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($process['numero_processo']); ?></td>
                                <td><?php echo htmlspecialchars($process['tribunal']); ?></td>
                                <td><?php echo htmlspecialchars($process['status']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($process['data_abertura'])); ?></td>
                                <td><?php echo htmlspecialchars(substr($process['descricao'], 0, 100)) . (strlen($process['descricao']) > 100 ? '...' : ''); ?></td>
                                <td class="actions-column">
                                    <a href="edit_process.php?id=<?php echo $process['id_processo']; ?>" class="btn-icon btn-edit" title="Editar Processo">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Atendimentos Vinculados a Processos -->
        <div class="detail-section">
            <div class="section-header">
                <h3><i class="fas fa-handshake"></i> Atendimentos Vinculados a Processos</h3>
                <a href="add_appointment.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Novo Atendimento
                </a>
            </div>
            
            <?php if (empty($appointments_linked)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-check"></i>
                    <p>Nenhum atendimento vinculado a processos encontrado.</p>
                </div>
            <?php else: ?>
                <table class="improved-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Processo</th>
                            <th>Responsável</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments_linked as $appointment): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($appointment['data'])); ?></td>
                                <td><?php echo htmlspecialchars($appointment['numero_processo']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['responsavel']); ?></td>
                                <td><?php echo htmlspecialchars(substr($appointment['descricao'], 0, 100)) . (strlen($appointment['descricao']) > 100 ? '...' : ''); ?></td>
                                <td class="actions-column">
                                    <a href="#" class="btn-icon btn-details" title="Ver Detalhes" onclick="showAppointmentDetails(<?php echo $appointment['id_atendimento']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Atendimentos Não Vinculados a Processos -->
        <div class="detail-section">
            <div class="section-header">
                <h3><i class="fas fa-calendar-alt"></i> Atendimentos Não Vinculados a Processos</h3>
            </div>
            
            <?php if (empty($appointments_unlinked)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar"></i>
                    <p>Nenhum atendimento não vinculado encontrado.</p>
                </div>
            <?php else: ?>
                <table class="improved-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Responsável</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments_unlinked as $appointment): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($appointment['data'])); ?></td>
                                <td><?php echo htmlspecialchars($appointment['responsavel']); ?></td>
                                <td><?php echo htmlspecialchars(substr($appointment['descricao'], 0, 100)) . (strlen($appointment['descricao']) > 100 ? '...' : ''); ?></td>
                                <td class="actions-column">
                                    <a href="#" class="btn-icon btn-details" title="Ver Detalhes" onclick="showAppointmentDetails(<?php echo $appointment['id_atendimento']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn-icon btn-delete" title="Excluir Atendimento" onclick="confirmDeleteAppointment(<?php echo $appointment['id_atendimento']; ?>)">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para exibir detalhes do atendimento -->
<div id="appointmentModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Detalhes do Atendimento</h3>
        <div id="appointmentDetails"></div>
    </div>
</div>

<!-- Modal de confirmação para excluir atendimento -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h3>Confirmar Exclusão</h3>
        <p>Tem certeza que deseja excluir este atendimento? Esta ação não pode ser desfeita.</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">Excluir</button>
        </div>
    </div>
</div>

<script>
// Modal para detalhes do atendimento
var modal = document.getElementById("appointmentModal");
var deleteModal = document.getElementById("deleteConfirmModal");
var span = document.getElementsByClassName("close")[0];
var currentAppointmentId = null;

function showAppointmentDetails(appointmentId) {
    // Aqui você pode fazer uma requisição AJAX para obter os detalhes do atendimento
    // Por enquanto, vamos apenas mostrar o modal com uma mensagem
    document.getElementById("appointmentDetails").innerHTML =
        "<p>Carregando detalhes do atendimento #" + appointmentId + "...</p>";
    
    // Fazer requisição AJAX
    fetch('get_appointment_details.php?id=' + appointmentId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                var html = `
                    <div class="appointment-detail">
                        <p><strong>Data:</strong> ${data.appointment.data}</p>
                        <p><strong>Responsável:</strong> ${data.appointment.responsavel}</p>
                        <p><strong>Descrição:</strong> ${data.appointment.descricao}</p>
                        <p><strong>Observações:</strong> ${data.appointment.observacoes || 'Nenhuma observação'}</p>
                    </div>
                `;
                document.getElementById("appointmentDetails").innerHTML = html;
            } else {
                document.getElementById("appointmentDetails").innerHTML =
                    "<p class='error'>Erro ao carregar detalhes: " + data.message + "</p>";
            }
        })
        .catch(error => {
            document.getElementById("appointmentDetails").innerHTML =
                "<p class='error'>Erro ao carregar detalhes. Por favor, tente novamente.</p>";
        });
    
    modal.style.display = "block";
}

// Função para confirmar exclusão de atendimento
function confirmDeleteAppointment(appointmentId) {
    currentAppointmentId = appointmentId;
    deleteModal.style.display = "block";
    
    // Configurar o botão de confirmação
    document.getElementById("confirmDeleteBtn").onclick = function() {
        deleteAppointment(currentAppointmentId);
    };
}

// Função para fechar o modal de confirmação
function closeDeleteModal() {
    deleteModal.style.display = "none";
    currentAppointmentId = null;
}

// Função para excluir o atendimento
function deleteAppointment(appointmentId) {
    fetch('delete_appointment.php?id=' + appointmentId, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Atendimento excluído com sucesso!', 'success');
            // Recarregar a página após 1 segundo
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        } else {
            showNotification('Erro ao excluir atendimento: ' + data.message, 'error');
        }
        closeDeleteModal();
    })
    .catch(error => {
        showNotification('Erro ao excluir atendimento. Por favor, tente novamente.', 'error');
        closeDeleteModal();
    });
}

// Fechar o modal quando clicar no X
span.onclick = function() {
    modal.style.display = "none";
}

// Fechar o modal quando clicar fora dele
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    } else if (event.target == deleteModal) {
        closeDeleteModal();
    }
}
</script>