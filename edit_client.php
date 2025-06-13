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
    $cep = $_POST['cep'];
    $logradouro = $_POST['logradouro'];
    $numero = $_POST['numero'];
    $complemento = $_POST['complemento'];
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $estado = $_POST['estado'];
    
    // Montar o endereço completo para manter compatibilidade
    $endereco = "$logradouro, $numero";
    if (!empty($complemento)) $endereco .= ", $complemento";
    if (!empty($bairro)) $endereco .= ", $bairro";
    $endereco .= " - $cidade/$estado";
    if (!empty($cep)) $endereco .= " - CEP: $cep";
    
    $outros_dados = $_POST['outros_dados'] ?? [];
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    // Codificar os outros dados como JSON
    $outros_dados_json = json_encode($outros_dados);

    $sql = "UPDATE clientes SET nome = ?, cpf_cnpj = ?, email = ?, telefone = ?,
            endereco = ?, cep = ?, logradouro = ?, numero = ?, complemento = ?,
            bairro = ?, cidade = ?, estado = ?, outros_dados = ?, ativo = ?
            WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssii", $nome, $cpf_cnpj, $email, $telefone,
                      $endereco, $cep, $logradouro, $numero, $complemento,
                      $bairro, $cidade, $estado, $outros_dados_json, $ativo, $id);
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
                <!-- Informações básicas do cliente -->
                <div class="form-section client-info-section">
                    <h3 class="section-title"><i class="fas fa-user"></i> Informações Básicas</h3>
                    <div class="section-content">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nome" class="form-label"><i class="fas fa-user"></i> Nome Completo</label>
                                <input type="text" name="nome" value="<?php echo htmlspecialchars($client['nome']); ?>"
                                    placeholder="Ex: João da Silva" required class="form-control">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="cpf_cnpj" class="form-label"><i class="fas fa-id-card"></i> CPF/CNPJ</label>
                                <input type="text" name="cpf_cnpj" value="<?php echo htmlspecialchars($client['cpf_cnpj']); ?>"
                                    placeholder="000.000.000-00" required class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>"
                                    placeholder="exemplo@dominio.com" required class="form-control">
                            </div>

                            <div class="form-group col-md-6">
                                <label for="telefone" class="form-label"><i class="fas fa-phone"></i> Telefone</label>
                                <input type="text" name="telefone" value="<?php echo htmlspecialchars($client['telefone']); ?>"
                                    placeholder="(00) 00000-0000" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Seção de endereço -->
                <div class="form-section endereco-section">
                    <h3 class="section-title"><i class="fas fa-map-marker-alt"></i> Endereço</h3>
                    <div class="section-content">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="cep" class="form-label">CEP</label>
                                <div class="input-group">
                                    <input type="text" name="cep" id="cep" value="<?php echo htmlspecialchars($client['cep'] ?? ''); ?>"
                                        placeholder="00000-000" class="form-control" maxlength="9">
                                    <div class="input-group-append">
                                        <button type="button" id="buscarCep" class="btn btn-secondary">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Digite o CEP e clique em Buscar para preencher o endereço automaticamente</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-9">
                                <label for="logradouro" class="form-label">Logradouro</label>
                                <input type="text" name="logradouro" id="logradouro" value="<?php echo htmlspecialchars($client['logradouro'] ?? ''); ?>"
                                    placeholder="Rua, Avenida, etc." class="form-control">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="numero" class="form-label">Número</label>
                                <input type="text" name="numero" id="numero" value="<?php echo htmlspecialchars($client['numero'] ?? ''); ?>"
                                    placeholder="Nº" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="complemento" class="form-label">Complemento</label>
                                <input type="text" name="complemento" id="complemento" value="<?php echo htmlspecialchars($client['complemento'] ?? ''); ?>"
                                    placeholder="Apto, Bloco, etc." class="form-control">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="bairro" class="form-label">Bairro</label>
                                <input type="text" name="bairro" id="bairro" value="<?php echo htmlspecialchars($client['bairro'] ?? ''); ?>"
                                    placeholder="Bairro" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-8">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" name="cidade" id="cidade" value="<?php echo htmlspecialchars($client['cidade'] ?? ''); ?>"
                                    placeholder="Cidade" class="form-control">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="estado" class="form-label">Estado</label>
                                <select name="estado" id="estado" class="form-control">
                                    <option value="">Selecione</option>
                                    <?php
                                    $estados = array(
                                        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
                                        'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
                                        'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
                                        'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
                                        'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
                                        'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
                                        'SE' => 'Sergipe', 'TO' => 'Tocantins'
                                    );
                                    
                                    $clienteEstado = $client['estado'] ?? '';
                                    
                                    foreach ($estados as $sigla => $nome) {
                                        $selected = ($clienteEstado == $sigla) ? 'selected' : '';
                                        echo "<option value=\"$sigla\" $selected>$sigla - $nome</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    /* Reset e ajustes globais */
                    .form-container {
                        width: 100% !important;
                        max-width: 100% !important;
                        padding: 0 !important;
                    }
                    
                    /* Estilos para o formulário */
                    .improved-form {
                        width: 100% !important;
                        max-width: 100% !important;
                        display: flex !important;
                        flex-direction: column !important;
                    }
                    
                    /* Estilos para todas as seções */
                    .form-section {
                        width: 100% !important;
                        max-width: 100% !important;
                        margin: 0 0 20px 0 !important;
                        padding: 0 !important;
                        box-sizing: border-box !important;
                        clear: both !important;
                    }
                    
                    .section-title {
                        margin-bottom: 15px !important;
                        padding-bottom: 10px !important;
                        border-bottom: 1px solid #e0e0e0 !important;
                        width: 100% !important;
                    }
                    
                    .section-content {
                        width: 100% !important;
                        max-width: 100% !important;
                        padding: 15px !important;
                        background-color: #f9f9f9 !important;
                        border-radius: 5px !important;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
                        box-sizing: border-box !important;
                        overflow: hidden !important;
                    }
                    
                    /* Estilos para as linhas e colunas */
                    .form-row {
                        display: flex !important;
                        flex-wrap: wrap !important;
                        margin-right: -10px !important;
                        margin-left: -10px !important;
                        width: 100% !important;
                        margin-bottom: 15px !important;
                        box-sizing: border-box !important;
                        clear: both !important;
                    }
                    
                    .form-group {
                        padding-right: 10px !important;
                        padding-left: 10px !important;
                        margin-bottom: 15px !important;
                        box-sizing: border-box !important;
                    }
                    
                    /* Definição das colunas */
                    .col-md-9 {
                        flex: 0 0 75% !important;
                        max-width: 75% !important;
                        box-sizing: border-box !important;
                    }
                    
                    .col-md-8 {
                        flex: 0 0 66.666667% !important;
                        max-width: 66.666667% !important;
                        box-sizing: border-box !important;
                    }
                    
                    .col-md-6 {
                        flex: 0 0 50% !important;
                        max-width: 50% !important;
                        box-sizing: border-box !important;
                    }
                    
                    .col-md-4 {
                        flex: 0 0 33.333333% !important;
                        max-width: 33.333333% !important;
                        box-sizing: border-box !important;
                    }
                    
                    .col-md-3 {
                        flex: 0 0 25% !important;
                        max-width: 25% !important;
                        box-sizing: border-box !important;
                    }
                    
                    /* Estilos específicos para a seção de informações básicas */
                    .client-info-section .section-content {
                        background-color: #f0f7ff !important;
                    }
                    
                    /* Estilos específicos para a seção de endereço */
                    .endereco-section .section-content {
                        background-color: #f9f9f9 !important;
                    }
                    
                    /* Responsividade para telas menores */
                    @media (max-width: 768px) {
                        .col-md-3,
                        .col-md-4,
                        .col-md-6,
                        .col-md-8,
                        .col-md-9 {
                            flex: 0 0 100% !important;
                            max-width: 100% !important;
                        }
                    }
                </style>
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
    // Função para formatar o CEP
    function formatarCEP(cep) {
        cep = cep.replace(/\D/g, ''); // Remove caracteres não numéricos
        if (cep.length > 5) {
            cep = cep.substring(0, 5) + '-' + cep.substring(5);
        }
        return cep;
    }
    
    // Função para buscar endereço pelo CEP
    function buscarEnderecoPorCEP(cep) {
        // Remove caracteres não numéricos
        cep = cep.replace(/\D/g, '');
        
        if (cep.length !== 8) {
            alert('CEP inválido. O CEP deve conter 8 dígitos.');
            return;
        }
        
        // Mostrar indicador de carregamento
        document.getElementById('buscarCep').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Fazer requisição para a API ViaCEP
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(response => response.json())
            .then(data => {
                if (data.erro) {
                    alert('CEP não encontrado.');
                } else {
                    // Preencher os campos com os dados retornados
                    document.getElementById('logradouro').value = data.logradouro;
                    document.getElementById('bairro').value = data.bairro;
                    document.getElementById('cidade').value = data.localidade;
                    document.getElementById('estado').value = data.uf;
                    
                    // Focar no campo número para facilitar o preenchimento
                    document.getElementById('numero').focus();
                }
                
                // Restaurar o botão de busca
                document.getElementById('buscarCep').innerHTML = '<i class="fas fa-search"></i> Buscar';
            })
            .catch(error => {
                console.error('Erro ao buscar CEP:', error);
                alert('Erro ao buscar CEP. Verifique sua conexão e tente novamente.');
                document.getElementById('buscarCep').innerHTML = '<i class="fas fa-search"></i> Buscar';
            });
    }
    
    // Inicializar eventos quando o documento estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        // Adicionar evento ao campo de CEP para formatação
        const cepInput = document.getElementById('cep');
        if (cepInput) {
            cepInput.addEventListener('input', function() {
                this.value = formatarCEP(this.value);
            });
            
            // Adicionar evento para buscar CEP ao pressionar Enter
            cepInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarEnderecoPorCEP(this.value);
                }
            });
        }
        
        // Adicionar evento ao botão de busca de CEP
        const buscarCepBtn = document.getElementById('buscarCep');
        if (buscarCepBtn) {
            buscarCepBtn.addEventListener('click', function() {
                const cep = document.getElementById('cep').value;
                buscarEnderecoPorCEP(cep);
            });
        }
    });

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
