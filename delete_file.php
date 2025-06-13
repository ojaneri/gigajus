<?php
require 'config.php';
include 'header.php';

// Verificar se o ID do cliente e o nome do arquivo foram passados
logMessage("Passo 1: Verificando se o ID do cliente e o nome do arquivo foram passados.");
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['file']) || empty($_GET['file'])) {
    logMessage("Passo 1.1: ID do cliente ou nome do arquivo não fornecidos.");
    echo "ID do cliente ou nome do arquivo não fornecidos.";
    exit();
}

$id = $_GET['id'];
$file = $_GET['file'];

// Obter o nome do cliente do banco de dados
$stmt = $conn->prepare("SELECT nome FROM clientes WHERE id_cliente = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $client = $result->fetch_assoc();
    $nomeCliente = $client['nome'];
    
    // Criar nome de pasta no formato "Nome do cliente (ID X)"
    $folderName = preg_replace('/[^a-zA-Z0-9\s]/', '', $nomeCliente); // Remove caracteres especiais
    $folderName = trim($folderName); // Remove espaços extras
    $folderName = "$folderName (ID $id)";
    $uploadDir = "uploads/clientes/$folderName/";
} else {
    // Se não encontrar o cliente, usa o ID como fallback
    $uploadDir = "uploads/clientes/$id/";
    logMessage("Cliente com ID $id não encontrado no banco de dados. Usando ID como nome da pasta.");
}

// Verificar possíveis localizações do arquivo
$oldUploadDir = "uploads/$id/";
$oldUploadDir2 = "uploads/$folderName/";
$oldFilePath = $oldUploadDir . $file;
$oldFilePath2 = $oldUploadDir2 . $file;
$oldDescricaoPath = $oldFilePath . ".descricao.txt";
$oldDescricaoPath2 = $oldFilePath2 . ".descricao.txt";

$filePath = $uploadDir . $file;
$descricaoPath = $filePath . ".descricao.txt";

logMessage("Passo 1.2: ID do cliente recebido: $id. Nome do arquivo recebido: $file.");
logMessage("Passo 1.3: Verificando arquivo em: $filePath");
if ($uploadDir != $oldUploadDir) {
    logMessage("Passo 1.4: Verificando também no caminho antigo: $oldFilePath");
}

// Verificar se o arquivo existe e tentar excluí-lo
$fileFound = false;

// Primeiro tenta no caminho novo (uploads/clientes/...)
if (file_exists($filePath)) {
    $fileFound = true;
    if (unlink($filePath)) {
        logMessage("Passo 2: Arquivo $filePath excluído com sucesso.");
        // Também excluir o arquivo de descrição, se existir
        if (file_exists($descricaoPath)) {
            unlink($descricaoPath);
            logMessage("Passo 2.1: Descrição $descricaoPath excluída com sucesso.");
        }
        echo "<script>showNotification('Arquivo excluído com sucesso!', 'success');</script>";
    } else {
        logMessage("Passo 2.2: Erro ao tentar excluir o arquivo $filePath.");
        echo "<script>showNotification('Erro ao tentar excluir o arquivo.', 'error');</script>";
    }
}
// Tenta no caminho antigo com ID (uploads/ID/...)
else if (file_exists($oldFilePath)) {
    $fileFound = true;
    if (unlink($oldFilePath)) {
        logMessage("Passo 2: Arquivo $oldFilePath excluído com sucesso.");
        // Também excluir o arquivo de descrição, se existir
        if (file_exists($oldDescricaoPath)) {
            unlink($oldDescricaoPath);
            logMessage("Passo 2.1: Descrição $oldDescricaoPath excluída com sucesso.");
        }
        echo "<script>showNotification('Arquivo excluído com sucesso!', 'success');</script>";
    } else {
        logMessage("Passo 2.2: Erro ao tentar excluir o arquivo $oldFilePath.");
        echo "<script>showNotification('Erro ao tentar excluir o arquivo.', 'error');</script>";
    }
}
// Tenta no caminho antigo com nome formatado (uploads/Nome (ID X)/...)
else if (file_exists($oldFilePath2)) {
    $fileFound = true;
    if (unlink($oldFilePath2)) {
        logMessage("Passo 2: Arquivo $oldFilePath2 excluído com sucesso.");
        // Também excluir o arquivo de descrição, se existir
        if (file_exists($oldDescricaoPath2)) {
            unlink($oldDescricaoPath2);
            logMessage("Passo 2.1: Descrição $oldDescricaoPath2 excluída com sucesso.");
        }
        echo "<script>showNotification('Arquivo excluído com sucesso!', 'success');</script>";
    } else {
        logMessage("Passo 2.2: Erro ao tentar excluir o arquivo $oldFilePath2.");
        echo "<script>showNotification('Erro ao tentar excluir o arquivo.', 'error');</script>";
    }
}
// Se não encontrou em nenhum dos caminhos
else {
    logMessage("Passo 2.3: Arquivo não encontrado em nenhum dos caminhos verificados.");
    echo "<script>showNotification('Arquivo não encontrado.', 'error');</script>";
}

// Redirecionar de volta para a página arquivos_client.php
header("Location: arquivos_client.php?id=$id");
exit();
?>
