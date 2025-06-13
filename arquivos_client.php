<?php
require 'config.php';
include 'header.php';

// Verificar se o ID do cliente foi passado
logMessage(__FILE__ . " Passo 1: Verificando se o ID do cliente foi passado.");
if (!isset($_GET['id']) || empty($_GET['id'])) {
    logMessage(__FILE__ . " Passo 1.1: ID do cliente não fornecido.");
    echo "ID do cliente não fornecido.";
    exit();
}

$id = $_GET['id'];
logMessage(__FILE__ . " Passo 1.2: ID do cliente recebido: $id");

// Recuperar nome do cliente do banco de dados
logMessage(__FILE__ . " Passo 1.3: Recuperando nome do cliente do banco de dados.");
$stmt = $conn->prepare("SELECT nome FROM clientes WHERE id_cliente = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();

if ($cliente) {
    $nomeCliente = $cliente['nome'];
    logMessage(__FILE__ . " Cliente encontrado: " . $nomeCliente);
} else {
    logMessage(__FILE__ . " Cliente não encontrado no banco de dados.");
    echo "Cliente não encontrado.";
    exit();
}

// Criar nome de pasta no formato "Nome do cliente (ID X)"
$folderName = preg_replace('/[^a-zA-Z0-9\s]/', '', $nomeCliente); // Remove caracteres especiais
$folderName = trim($folderName); // Remove espaços extras
$folderName = "$folderName (ID $id)";
$uploadDir = "uploads/clientes/$folderName/";

// Criar diretório clientes se não existir
if (!file_exists("uploads/clientes/")) {
    mkdir("uploads/clientes/", 0777, true);
    logMessage(__FILE__ . " Passo 2.0: Diretório uploads/clientes/ criado com sucesso.");
}

logMessage(__FILE__ . " Passo 2: Definindo o diretório de upload: $uploadDir");

// Verificar se existe pasta antiga com apenas o ID
$oldUploadDir = "uploads/$id/";
$oldUploadDir2 = "uploads/$folderName/";
// Verificar se existem pastas antigas
if (file_exists($oldUploadDir) && !file_exists($uploadDir)) {
    // Renomear pasta antiga com ID para o novo formato em uploads/clientes/
    if (rename($oldUploadDir, $uploadDir)) {
        logMessage(__FILE__ . " Passo 2.1: Pasta $oldUploadDir renomeada para $uploadDir com sucesso.");
    } else {
        logMessage(__FILE__ . " Passo 2.2: Erro ao renomear a pasta $oldUploadDir para $uploadDir.");
    }
} else if (file_exists($oldUploadDir2) && !file_exists($uploadDir)) {
    // Renomear pasta antiga com nome formatado para o novo diretório
    if (rename($oldUploadDir2, $uploadDir)) {
        logMessage(__FILE__ . " Passo 2.3: Pasta $oldUploadDir2 renomeada para $uploadDir com sucesso.");
    } else {
        logMessage(__FILE__ . " Passo 2.4: Erro ao renomear a pasta $oldUploadDir2 para $uploadDir.");
    }
}

// Criar pasta se não existir
if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0777, true)) {
        logMessage(__FILE__ . " Passo 2.3: Pasta $uploadDir criada com sucesso.");
        file_put_contents("$uploadDir/info", $nomeCliente);
        logMessage(__FILE__ . " Passo 2.3.1: Arquivo info criado com o nome do cliente.");
    } else {
        logMessage(__FILE__ . " Passo 2.4: Erro ao criar a pasta $uploadDir.");
    }
} else {
    logMessage(__FILE__ . " Passo 2.5: Pasta $uploadDir já existe.");
}

// Listar arquivos, excluindo arquivos de descrição
logMessage(__FILE__ . " Passo 3: Listando arquivos no diretório $uploadDir.");
$arquivos = is_dir($uploadDir) ? array_filter(array_diff(scandir($uploadDir), array('.', '..')), function($file) {
    return !preg_match('/\.descricao\.txt$/', $file);
}) : [];

// Função para obter a descrição e dados do arquivo
function getFileData($filePath) {
    if (file_exists("$filePath.descricao.txt")) {
        $lines = file("$filePath.descricao.txt", FILE_IGNORE_NEW_LINES);
        $usuario = $lines[0] ?? "Desconhecido";
        $descricao = $lines[1] ?? "Sem descrição";
    } else {
        $usuario = "Desconhecido";
        $descricao = "Sem descrição";
    }
    $dataEnvio = date("d/m/Y H:i:s", filemtime($filePath));
    return [$usuario, $descricao, $dataEnvio];
}
?>
    <div class="content">
        <div class="client-files-header">
            <div class="client-title">
                <h2>Arquivos do Cliente: <?php echo htmlspecialchars($nomeCliente); ?></h2>
                <div class="client-folder-info">
                    <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($folderName); ?></span>
                </div>
            </div>
            
            <!-- Google Drive Link -->
            <a href="https://drive.google.com/drive/u/0/folders/11gjcfhYFdBygwCSwBfWNMTNF58EJc_KJ" target="_blank" class="btn-icon btn-drive" title="Acessar pasta no Google Drive">
                <i class="fab fa-google-drive"></i>
            </a>
        </div>
        
        <div class="upload-section">
        <h3><i class="fas fa-cloud-upload-alt"></i> Upload de Arquivo</h3>
        <form id="uploadForm" method="POST" enctype="multipart/form-data" class="improved-form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="descricao">
                        <i class="fas fa-file-alt"></i> Descrição do Arquivo
                    </label>
                    <input type="text" id="descricao" name="descricao" required class="form-control">
                </div>
                <div class="form-group">
                    <label for="arquivo">
                        <i class="fas fa-file-upload"></i> Selecionar Arquivo
                    </label>
                    <input type="file" id="arquivo" name="arquivo" required class="form-control file-input">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Enviar
                </button>
            </div>
        </form>
    </div>
    <div class="files-section">
        <h3><i class="fas fa-file-alt"></i> Arquivos Enviados</h3>
        <div class="file-list">
                <table class="improved-table">
                    <thead>
                        <tr>
                            <th>Nome do Arquivo</th>
                            <th>Data de Envio</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($arquivos as $arquivo): 
                            $filePath = $uploadDir . $arquivo;
                            list($usuario, $descricao, $dataEnvio) = getFileData($filePath);
                        ?>
                            <tr>
                                <td>
                                    <a href="<?php echo $filePath; ?>" target="_blank" title="Usuário: <?php echo htmlspecialchars($usuario); ?> | Data: <?php echo $dataEnvio; ?>"><?php echo $arquivo; ?></a>
                                </td>
                                <td title="Enviado por: <?php echo htmlspecialchars($usuario); ?>"><?php echo $dataEnvio; ?></td>
                                <td><?php echo htmlspecialchars($descricao); ?></td>
                                <td class="actions-column">
                                    <a href="<?php echo $filePath; ?>" download class="btn-icon btn-download" title="Baixar Arquivo">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <a href="#" class="btn-icon btn-edit" title="Editar Descrição" onclick="editDescription('<?php echo $arquivo; ?>', '<?php echo addslashes(htmlspecialchars($descricao)); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="btn-icon btn-delete" title="Excluir Arquivo" onclick="confirmDelete('<?php echo $arquivo; ?>')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    <div class="overlay" id="overlay">
    Enviando arquivo, aguarde... 
    </div>

    <script>
    // Adicionar o event listener ao formulário de upload
    document.getElementById('uploadForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevenir o comportamento padrão de submissão do formulário
        const button = document.querySelector('button[type="submit"]'); // Selecionar o botão de envio
        button.textContent = 'Enviando...'; // Alterar o texto do botão durante o envio
        button.disabled = true; // Desabilitar o botão para evitar envios múltiplos

        // Mostrar o overlay
        document.getElementById('overlay').style.display = 'flex';

        const formData = new FormData(this); // Usar o formulário como contexto para FormData
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload_handler.php?id=<?php echo $id; ?>', true);

        xhr.onload = function() {
            // Esconder o overlay
            document.getElementById('overlay').style.display = 'none';
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showNotification(response.message, 'success');
                    location.reload(); // Recarregar a página para atualizar a lista de arquivos
                } else {
                    showNotification(response.message, 'error');
                    button.textContent = 'Enviar'; // Restaurar o texto do botão se não for bem-sucedido
                    button.disabled = false; // Habilitar o botão novamente
                }
            } else {
                showNotification('Erro ao enviar o arquivo. Tente novamente.', 'error');
                button.textContent = 'Enviar'; // Restaurar o texto do botão se houver erro
                button.disabled = false; // Habilitar o botão novamente
            }
        };

        xhr.onerror = function() {
            alert('Erro ao enviar o arquivo. Verifique sua conexão e tente novamente.');
            document.getElementById('overlay').style.display = 'none';
            button.textContent = 'Enviar'; // Restaurar o texto do botão em caso de erro de rede
            button.disabled = false; // Habilitar o botão novamente
        };

        xhr.send(formData); // Enviar o formulário
    });

    // Função para confirmar a exclusão de um arquivo
    function confirmDelete(filename) {
        if (confirm("Tem certeza de que deseja apagar o arquivo " + filename + "?")) {
            window.location.href = "delete_file.php?id=<?php echo $id; ?>&file=" + filename;
        }
    }
    
    // Função para editar descrição
    function editDescription(arquivo, descricaoAtual) {
        const novaDescricao = prompt("Editar descrição do arquivo:", descricaoAtual);
        
        if (novaDescricao !== null && novaDescricao !== descricaoAtual) {
            // Mostrar o overlay
            document.getElementById('overlay').style.display = 'flex';
            
            // Enviar a nova descrição via AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_description.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                // Esconder o overlay
                document.getElementById('overlay').style.display = 'none';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            showNotification('Descrição atualizada com sucesso!', 'success');
                            // Recarregar a página para mostrar a descrição atualizada
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification('Erro ao atualizar descrição: ' + response.message, 'error');
                        }
                    } catch (e) {
                        showNotification('Erro ao processar resposta do servidor', 'error');
                    }
                } else {
                    showNotification('Erro ao atualizar descrição. Tente novamente.', 'error');
                }
            };
            
            xhr.onerror = function() {
                document.getElementById('overlay').style.display = 'none';
                showNotification('Erro de conexão. Verifique sua internet e tente novamente.', 'error');
            };
            
            xhr.send('id=<?php echo $id; ?>&file=' + encodeURIComponent(arquivo) + '&description=' + encodeURIComponent(novaDescricao));
        }
    }
</script>

