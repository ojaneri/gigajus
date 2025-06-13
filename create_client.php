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
$nome = $cpf_cnpj = $endereco = $telefone = $email = "";
$errors = [];

// Variáveis para outros dados
$outros_dados = [];

// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'] ?? '';
    $cpf_cnpj = !empty($_POST['cpf_cnpj']) ? $_POST['cpf_cnpj'] : null;
    $endereco = $_POST['endereco'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = !empty($_POST['email']) ? $_POST['email'] : null;
    $outros_dados = $_POST['outros_dados'] ?? [];

    // Validar os dados (você pode adicionar mais validações conforme necessário)
    if (empty($nome)) {
        $errors[] = "O nome é obrigatório.";
    }
    // CPF/CNPJ é opcional, permitindo preenchimento posterior
    // Email é opcional, permitindo preenchimento posterior

    // Inserir no banco de dados se não houver erros
    if (empty($errors)) {
        $sql = "INSERT INTO clientes (nome, cpf_cnpj, endereco, telefone, email, outros_dados) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $outros_dados_json = json_encode($outros_dados);
        
        // Construir a query baseada nos campos que podem ser null
        if ($cpf_cnpj === null && $email === null) {
            $sql = "INSERT INTO clientes (nome, cpf_cnpj, endereco, telefone, email, outros_dados) VALUES (?, NULL, ?, ?, NULL, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nome, $endereco, $telefone, $outros_dados_json);
        } else if ($cpf_cnpj === null) {
            $sql = "INSERT INTO clientes (nome, cpf_cnpj, endereco, telefone, email, outros_dados) VALUES (?, NULL, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $nome, $endereco, $telefone, $email, $outros_dados_json);
        } else if ($email === null) {
            $sql = "INSERT INTO clientes (nome, cpf_cnpj, endereco, telefone, email, outros_dados) VALUES (?, ?, ?, ?, NULL, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $nome, $cpf_cnpj, $endereco, $telefone, $outros_dados_json);
        } else {
            $stmt->bind_param("ssssss", $nome, $cpf_cnpj, $endereco, $telefone, $email, $outros_dados_json);
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
            <div class="form-grid">
                <div class="form-group">
                    <label for="nome" title="Insira o nome completo do cliente">
                        <i class="fas fa-user"></i> Nome
                    </label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="cpf_cnpj" title="Insira o CPF ou CNPJ do cliente">
                        <i class="fas fa-id-card"></i> CPF/CNPJ
                    </label>
                    <input type="text" id="cpf_cnpj" name="cpf_cnpj" value="<?php echo htmlspecialchars($cpf_cnpj); ?>" oninput="formatCPF(this)" class="form-control">
                    <span id="cpf_error" class="error-message"></span>
                </div>
                
                <div class="form-group">
                    <label for="telefone" title="Insira o telefone do cliente">
                        <i class="fas fa-phone"></i> Telefone
                    </label>
                    <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone); ?>" oninput="formatPhone(this)" class="form-control">
                    <span id="phone_error" class="error-message"></span>
                </div>
                
                <div class="form-group">
                    <label for="email" title="Insira o email do cliente">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" oninput="validateEmail(this)" class="form-control">
                    <span id="email_error" class="error-message"></span>
                </div>
                
                <div class="form-group full-width">
                    <label for="endereco" title="Insira o endereço completo do cliente">
                        <i class="fas fa-map-marker-alt"></i> Endereço
                    </label>
                    <textarea id="endereco" name="endereco" class="form-control"><?php echo htmlspecialchars($endereco); ?></textarea>
                </div>
                
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
