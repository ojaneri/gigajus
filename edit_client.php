<?php
session_start();
require 'config.php';
include 'header.php';

// Adicionar CSS específico para gerenciamento de clientes
echo '<link rel="stylesheet" href="assets/css/client-management.css">';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];
$client = null;

// Buscar dados do cliente para edição
$sql = "SELECT * FROM clientes WHERE id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

// Decodificar os outros dados JSON
$outros_dados = json_decode($client['outros_dados'], true) ?? [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $cpf_cnpj = $_POST['cpf_cnpj'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];
    $outros_dados = $_POST['outros_dados'] ?? [];
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    // Codificar os outros dados como JSON
    $outros_dados_json = json_encode($outros_dados);

    $sql = "UPDATE clientes SET nome = ?, cpf_cnpj = ?, email = ?, telefone = ?, endereco = ?, outros_dados = ?, ativo = ? WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssii", $nome, $cpf_cnpj, $email, $telefone, $endereco, $outros_dados_json, $ativo, $id);
    $stmt->execute();

    echo "<script>showNotification('Cliente atualizado com sucesso!', 'success');</script>";
    
    echo '<script>
            setTimeout(function() {
                window.location.href = "clients.php";
            }, 1500); // espera 1.5 segundos antes de redirecionar
          </script>';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deletar'])) {
    $sql = "UPDATE clientes SET ativo = 0 WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo "<script>showNotification('Cliente inativado com sucesso!', 'success');</script>";
    echo '<script>
            setTimeout(function() {
                window.location.href = "clients.php";
            }, 1500); // espera 1.5 segundos antes de redirecionar
          </script>';
    exit();
}
?>

    <div class="content">
        <div class="form-container">
            <div class="form-header">
                <h2 class="form-title"><i class="fas fa-user-edit"></i> Editar Cliente</h2>
                <div class="status-badge status-<?php echo $client['ativo'] ? 'ativo' : 'inativo'; ?>">
                    <?php echo $client['ativo'] ? 'Ativo' : 'Inativo'; ?>
                </div>
            </div>
            
            <form method="POST" action="edit_client.php?id=<?php echo $id; ?>" class="improved-form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nome" class="form-label"><i class="fas fa-user"></i> Nome Completo</label>
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($client['nome']); ?>"
                        placeholder="Ex: João da Silva" required class="form-control">
                </div>

                <div class="form-group">
                    <label for="cpf_cnpj" class="form-label"><i class="fas fa-id-card"></i> CPF/CNPJ</label>
                    <input type="text" name="cpf_cnpj" value="<?php echo htmlspecialchars($client['cpf_cnpj']); ?>"
                        placeholder="000.000.000-00" required class="form-control">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>"
                        placeholder="exemplo@dominio.com" required class="form-control">
                </div>

                <div class="form-group">
                    <label for="telefone" class="form-label"><i class="fas fa-phone"></i> Telefone</label>
                    <input type="text" name="telefone" value="<?php echo htmlspecialchars($client['telefone']); ?>"
                        placeholder="(00) 00000-0000" class="form-control">
                </div>

                <div class="form-group full-width">
                    <label for="endereco" class="form-label"><i class="fas fa-map-marker-alt"></i> Endereço</label>
                    <textarea name="endereco" rows="3" class="form-control"><?php echo htmlspecialchars($client['endereco']); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title"><i class="fas fa-plus-circle"></i> Outros Dados</h3>
                <div class="section-content">
                    <div id="outrosDadosContainer" class="outros-dados-container">
                        <?php foreach ($outros_dados as $index => $dado): ?>
                            <div class="outro-dado-item">
                                <div class="outro-dado-inputs">
                                    <input type="text" name="outros_dados[<?php echo $index; ?>][campo]"
                                        placeholder="Nome do campo"
                                        value="<?php echo htmlspecialchars($dado['campo']); ?>"
                                        class="form-control">
                                    <input type="text" name="outros_dados[<?php echo $index; ?>][valor]"
                                        placeholder="Valor correspondente"
                                        value="<?php echo htmlspecialchars($dado['valor']); ?>"
                                        class="form-control">
                                </div>
                                <button type="button" onclick="removerOutroDado(this)" class="btn btn-icon btn-remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="adicionarOutroDado()" class="btn btn-secondary btn-add-field">
                        <i class="fas fa-plus"></i> Adicionar Campo
                    </button>
                </div>
            </div>

            <div class="form-actions">
                <a href="clients.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </form>
        
        <div class="form-section danger-zone">
            <h3 class="section-title"><i class="fas fa-exclamation-triangle"></i> Zona de Risco</h3>
            <div class="section-content">
                <div class="danger-actions">
                    <form method="POST" action="edit_client.php?id=<?php echo $id; ?>">
                        <input type="hidden" name="deletar" value="1">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-user-slash"></i> Inativar Cliente
                        </button>
                    </form>
                    <a href="arquivos_client.php?id=<?php echo $id; ?>" class="btn btn-info">
                        <i class="fas fa-folder-open"></i> Gerenciar Arquivos
                    </a>
                    
                    <?php
                    // Verificar se o usuário é admin
                    $admin_query = "SELECT permissoes FROM usuarios WHERE id_usuario = ?";
                    $stmt = mysqli_prepare($conn, $admin_query);
                    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $user_data = mysqli_fetch_assoc($result);
                    
                    // Verificar se o usuário tem permissões de admin
                    $is_admin = false;
                    if ($user_data && isset($user_data['permissoes'])) {
                        $permissoes = json_decode($user_data['permissoes'], true);
                        $is_admin = isset($permissoes['admin']) && $permissoes['admin'] === true;
                    }
                    
                    // Mostrar botão de exclusão apenas para admins
                    if ($is_admin):
                    ?>
                        <button type="button" class="btn btn-danger" onclick="showDeleteConfirmation()">
                            <i class="fas fa-trash-alt"></i> Excluir Cliente Permanentemente
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function adicionarOutroDado() {
            const container = document.getElementById('outrosDadosContainer');
            const index = container.children.length;
            const div = document.createElement('div');
            div.classList.add('outro-dado-item');
            div.innerHTML = `
                <div class="outro-dado-inputs">
                    <input type="text" name="outros_dados[${index}][campo]"
                        placeholder="Nome do campo" required
                        class="form-control">
                    <input type="text" name="outros_dados[${index}][valor]"
                        placeholder="Valor correspondente" required
                        class="form-control">
                </div>
                <button type="button" onclick="removerOutroDado(this)"
                    class="btn btn-icon btn-remove">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(div);
        }
    
        function removerOutroDado(button) {
            const div = button.closest('.outro-dado-item');
            if (div) {
                div.remove();
            }
        }
        
        // Função para mostrar o modal de confirmação de exclusão
        function showDeleteConfirmation() {
            // Criar o modal de confirmação
            const modalOverlay = document.createElement('div');
            modalOverlay.className = 'modal-overlay';
            
            const modalContent = document.createElement('div');
            modalContent.className = 'modal-content';
            
            modalContent.innerHTML = `
                <div class="modal-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Confirmação de Exclusão</h3>
                    <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="warning-text">Atenção! Esta ação é irreversível e excluirá permanentemente:</p>
                    <ul class="warning-list">
                        <li>Todos os dados do cliente</li>
                        <li>Todos os arquivos associados ao cliente</li>
                        <li>Todas as pastas do cliente no sistema</li>
                    </ul>
                    <p>Para confirmar a exclusão, digite <strong>CONFIRMAR</strong> no campo abaixo:</p>
                    <input type="text" id="confirmText" class="form-control" placeholder="Digite CONFIRMAR">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                    <button class="btn btn-danger" onclick="executeDelete()">Excluir Permanentemente</button>
                </div>
            `;
            
            modalOverlay.appendChild(modalContent);
            document.body.appendChild(modalOverlay);
            
            // Adicionar estilo ao modal
            const style = document.createElement('style');
            style.textContent = `
                .modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 1000;
                }
                .modal-content {
                    background-color: white;
                    border-radius: 5px;
                    width: 500px;
                    max-width: 90%;
                    box-shadow: 0 3px 7px rgba(0, 0, 0, 0.3);
                }
                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px;
                    border-bottom: 1px solid #e0e0e0;
                }
                .modal-body {
                    padding: 20px;
                }
                .modal-footer {
                    padding: 15px;
                    border-top: 1px solid #e0e0e0;
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                }
                .modal-close {
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                }
                .warning-text {
                    color: #e74c3c;
                    font-weight: bold;
                }
                .warning-list {
                    margin-bottom: 20px;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Função para fechar o modal de confirmação
        function closeDeleteModal() {
            const modalOverlay = document.querySelector('.modal-overlay');
            if (modalOverlay) {
                modalOverlay.remove();
            }
        }
        
        // Função para executar a exclusão após confirmação
        function executeDelete() {
            const confirmText = document.getElementById('confirmText').value;
            if (confirmText === 'CONFIRMAR') {
                // Criar um formulário para enviar a solicitação de exclusão
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete_client.php';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = '<?php echo $id; ?>';
                
                const confirmInput = document.createElement('input');
                confirmInput.type = 'hidden';
                confirmInput.name = 'confirm';
                confirmInput.value = 'true';
                
                form.appendChild(idInput);
                form.appendChild(confirmInput);
                document.body.appendChild(form);
                form.submit();
            } else {
                alert('Por favor, digite CONFIRMAR corretamente para prosseguir com a exclusão.');
            }
        }
</script>
