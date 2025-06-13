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

// Variáveis para armazenar os dados do cliente
$nome = $cpf_cnpj = $telefone = $email = "";
$cep = $logradouro = $numero = $complemento = $bairro = $cidade = $estado = "";
$errors = [];

// Variáveis para outros dados
$outros_dados = [];

// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'] ?? '';
    $cpf_cnpj = !empty($_POST['cpf_cnpj']) ? $_POST['cpf_cnpj'] : null;
    $telefone = $_POST['telefone'] ?? '';
    $email = !empty($_POST['email']) ? $_POST['email'] : null;
    
    // Dados de endereço
    $cep = $_POST['cep'] ?? '';
    $logradouro = $_POST['logradouro'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $complemento = $_POST['complemento'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    
    // Montar o endereço completo para manter compatibilidade
    $endereco = "$logradouro, $numero";
    if (!empty($complemento)) $endereco .= ", $complemento";
    if (!empty($bairro)) $endereco .= ", $bairro";
    $endereco .= " - $cidade/$estado";
    if (!empty($cep)) $endereco .= " - CEP: $cep";
    
    $outros_dados = $_POST['outros_dados'] ?? [];

    // Validar os dados (você pode adicionar mais validações conforme necessário)
    if (empty($nome)) {
        $errors[] = "O nome é obrigatório.";
    }
    // CPF/CNPJ é opcional, permitindo preenchimento posterior
    // Email é opcional, permitindo preenchimento posterior

    // Inserir no banco de dados se não houver erros
    if (empty($errors)) {
        $sql = "INSERT INTO clientes (nome, cpf_cnpj, endereco, telefone, email,
                cep, logradouro, numero, complemento, bairro, cidade, estado, outros_dados)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $outros_dados_json = json_encode($outros_dados);
        
        // Construir a query baseada nos campos que podem ser null
        if ($cpf_cnpj === null && $email === null) {
            $sql = "INSERT INTO clientes (nome, cpf_cnpj, endereco, telefone, email,
                    cep, logradouro, numero, complemento, bairro, cidade, estado, outros_dados)
                    VALUES (?, NULL, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssssss", $nome, $endereco, $telefone,
                              $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $outros_dados_json);
        } else if ($cpf_cnpj === null) {
            $sql = "INSERT INTO clientes (nome, cpf_cnpj, endereco, telefone, email,
                    cep, logradouro, numero, complemento, bairro, cidade, estado, outros_dados)
                    VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssssssss", $nome, $endereco, $telefone, $email,
                              $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $outros_dados_json);
        } else if ($email === null) {
            $sql = "INSERT INTO clientes (nome, cpf_cnpj, endereco, telefone, email,
                    cep, logradouro, numero, complemento, bairro, cidade, estado, outros_dados)
                    VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssssssss", $nome, $cpf_cnpj, $endereco, $telefone,
                              $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $outros_dados_json);
        } else {
            $stmt->bind_param("sssssssssssss", $nome, $cpf_cnpj, $endereco, $telefone, $email,
                              $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $outros_dados_json);
        }
        if ($stmt->execute()) {
            // Obter o ID do cliente recém-inserido
            $id_cliente = $conn->insert_id;
            
            // Criar pasta do cliente no formato "Nome do cliente (ID X)"
            $folderName = preg_replace('/[^a-zA-Z0-9\s]/', '', $nome); // Remove caracteres especiais
            $folderName = trim($folderName); // Remove espaços extras
            $folderName = "$folderName (ID $id_cliente)";
            $uploadDir = "uploads/clientes/$folderName/";
            
            // Criar diretório clientes se não existir
            if (!file_exists("uploads/clientes/")) {
                mkdir("uploads/clientes/", 0777, true);
                logMessage("Diretório uploads/clientes/ criado com sucesso.", 'INFO');
            }
            
            // Criar pasta do cliente se não existir
            if (!file_exists($uploadDir)) {
                if (mkdir($uploadDir, 0777, true)) {
                    logMessage("Pasta $uploadDir criada com sucesso.", 'INFO');
                    file_put_contents("$uploadDir/info", $nome);
                    logMessage("Arquivo info criado com o nome do cliente.", 'INFO');
                } else {
                    logMessage("Erro ao criar a pasta $uploadDir.", 'ERROR');
                }
            }
            
            echo "<script>showNotification('Cliente incluído com sucesso!', 'success');</script>";
            echo '<script>
                setTimeout(function() {
                    window.location.href = "clients.php";
                }, 1500); // espera 1.5 segundos antes de redirecionar
              </script>';
            exit();
        } else {
            echo "<script>showNotification('Erro ao incluir cliente: " . $stmt->error . "', 'error');</script>";
            $errors[] = "Erro ao inserir os dados do cliente: " . $stmt->error;
        }
    }
}
?>

<div class="content">
    <div class="form-container">
        <div class="form-header">
            <h2><i class="fas fa-user-plus"></i> Novo Cliente</h2>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="create_client.php" onsubmit="return validateForm()" class="improved-form">
            <!-- Informações básicas do cliente -->
            <div class="form-section client-info-section">
                <h3 class="section-title"><i class="fas fa-user"></i> Informações Básicas</h3>
                <div class="section-content">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nome" title="Insira o nome completo do cliente">
                                <i class="fas fa-user"></i> Nome
                            </label>
                            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required class="form-control">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="cpf_cnpj" title="Insira o CPF ou CNPJ do cliente">
                                <i class="fas fa-id-card"></i> CPF/CNPJ
                            </label>
                            <input type="text" id="cpf_cnpj" name="cpf_cnpj" value="<?php echo htmlspecialchars($cpf_cnpj); ?>" oninput="formatCPF(this)" class="form-control">
                            <span id="cpf_error" class="error-message"></span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="telefone" title="Insira o telefone do cliente">
                                <i class="fas fa-phone"></i> Telefone
                            </label>
                            <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone); ?>" oninput="formatPhone(this)" class="form-control">
                            <span id="phone_error" class="error-message"></span>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="email" title="Insira o email do cliente">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" oninput="validateEmail(this)" class="form-control">
                            <span id="email_error" class="error-message"></span>
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
                                <label for="cep" title="Insira o CEP para busca automática">
                                    <i class="fas fa-search"></i> CEP
                                </label>
                                <div class="input-group">
                                    <input type="text" id="cep" name="cep" value="<?php echo htmlspecialchars($cep); ?>"
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
                                <label for="logradouro" title="Nome da rua, avenida, etc.">
                                    <i class="fas fa-road"></i> Logradouro
                                </label>
                                <input type="text" id="logradouro" name="logradouro" value="<?php echo htmlspecialchars($logradouro); ?>"
                                    placeholder="Rua, Avenida, etc." class="form-control">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="numero" title="Número do endereço">
                                    <i class="fas fa-hashtag"></i> Número
                                </label>
                                <input type="text" id="numero" name="numero" value="<?php echo htmlspecialchars($numero); ?>"
                                    placeholder="Nº" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="complemento" title="Complemento do endereço (apto, bloco, etc.)">
                                    <i class="fas fa-info-circle"></i> Complemento
                                </label>
                                <input type="text" id="complemento" name="complemento" value="<?php echo htmlspecialchars($complemento); ?>"
                                    placeholder="Apto, Bloco, etc." class="form-control">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="bairro" title="Bairro do endereço">
                                    <i class="fas fa-map"></i> Bairro
                                </label>
                                <input type="text" id="bairro" name="bairro" value="<?php echo htmlspecialchars($bairro); ?>"
                                    placeholder="Bairro" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-8">
                                <label for="cidade" title="Cidade do endereço">
                                    <i class="fas fa-city"></i> Cidade
                                </label>
                                <input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($cidade); ?>"
                                    placeholder="Cidade" class="form-control">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="estado" title="Estado (UF)">
                                    <i class="fas fa-map-marked-alt"></i> Estado
                                </label>
                                <select id="estado" name="estado" class="form-control">
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
                                    
                                    foreach ($estados as $sigla => $nome) {
                                        $selected = ($estado == $sigla) ? 'selected' : '';
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
                
                <div class="form-group full-width">
                    <label title="Insira outros dados relevantes do cliente">
                        <i class="fas fa-info-circle"></i> Outros Dados
                    </label>
                    <div id="outrosDadosContainer" class="outros-dados-container"></div>
                    <button type="button" onclick="adicionarOutroDado()" class="btn btn-secondary btn-add-field">
                        <i class="fas fa-plus"></i> Adicionar Campo
                    </button>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="clients.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Cliente
                </button>
            </div>
        </form>
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

    function validateForm() {
        var cpf_cnpj = document.getElementById('cpf_cnpj').value;
        var telefone = document.getElementById('telefone').value;
        var email = document.getElementById('email').value;
        var cpfCnpjPattern = /^\d{3}\.\d{3}\.\d{3}\-\d{2}$/;
        var telefonePattern = /^\(\d{2}\) \d{5}\-\d{4}$/;
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        // Verifica o formato do CPF apenas se o campo não estiver vazio
        if (cpf_cnpj && !cpfCnpjPattern.test(cpf_cnpj)) {
            alert('O CPF deve estar no formato xxx.xxx.xxx-xx.');
            return false;
        }

        if (telefone && !telefonePattern.test(telefone)) {
            alert('O telefone deve estar no formato (DD) XXXXX-XXXX.');
            return false;
        }

        // Verifica o formato do email apenas se o campo não estiver vazio
        if (email && !emailPattern.test(email)) {
            alert('Por favor, insira um endereço de email válido.');
            return false;
        }

        return true;
    }

    function formatCPF(input) {
        input.value = input.value.replace(/\D/g, '') // Remove caracteres não numéricos
            .replace(/(\d{3})(\d)/, '$1.$2') // Coloca o ponto após o terceiro número
            .replace(/(\d{3})(\d)/, '$1.$2') // Coloca o ponto após o sexto número
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2'); // Coloca o hífen entre o nono e o décimo número

        // Só valida se o campo não estiver vazio
        if (input.value) {
            if (!/^\d{3}\.\d{3}\.\d{3}-\d{2}$/.test(input.value)) {
                document.getElementById('cpf_error').innerText = 'CPF inválido.';
            } else {
                document.getElementById('cpf_error').innerText = '';
            }
        } else {
            document.getElementById('cpf_error').innerText = '';
        }
    }

    function formatPhone(input) {
        input.value = input.value.replace(/\D/g, '') // Remove caracteres não numéricos
            .replace(/(\d{2})(\d)/, '($1) $2') // Coloca parênteses no DDD
            .replace(/(\d{5})(\d{4})/, '$1-$2'); // Coloca o hífen no número

        if (!/^\(\d{2}\) \d{5}-\d{4}$/.test(input.value)) {
            document.getElementById('phone_error').innerText = 'Telefone inválido.';
        } else {
            document.getElementById('phone_error').innerText = '';
        }
    }

    function validateEmail(input) {
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        // Só valida se o campo não estiver vazio
        if (input.value) {
            if (!emailPattern.test(input.value)) {
                document.getElementById('email_error').innerText = 'Email inválido.';
            } else {
                document.getElementById('email_error').innerText = '';
            }
        } else {
            document.getElementById('email_error').innerText = '';
        }
    }

    function adicionarOutroDado() {
        const container = document.getElementById('outrosDadosContainer');
        const index = container.children.length;
        const div = document.createElement('div');
        div.classList.add('outro-dado-item');
        div.innerHTML = `
            <div class="outro-dado-inputs">
                <input type="text" name="outros_dados[${index}][campo]" placeholder="Nome do campo" required class="form-control">
                <input type="text" name="outros_dados[${index}][valor]" placeholder="Valor correspondente" required class="form-control">
            </div>
            <button type="button" onclick="removerOutroDado(this)" class="btn btn-icon btn-remove">
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
</script>
