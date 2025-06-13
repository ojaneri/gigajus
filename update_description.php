<?php
session_start();
require 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Verificar se todos os parâmetros necessários foram fornecidos
if (!isset($_POST['id']) || !isset($_POST['file']) || !isset($_POST['description'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Parâmetros incompletos']);
    exit();
}

$id = intval($_POST['id']);
$file = $_POST['file'];
$description = trim($_POST['description']);

// Verificar se o cliente existe
$stmt = $conn->prepare("SELECT nome FROM clientes WHERE id_cliente = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
    exit();
}

$client = $result->fetch_assoc();
$nomeCliente = $client['nome'];

// Construir o caminho do diretório de upload
$folderName = $nomeCliente . " (ID " . $id . ")";
$uploadDir = "uploads/clientes/" . $folderName . "/";

// Verificar se o arquivo existe
$filePath = $uploadDir . $file;
if (!file_exists($filePath)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Arquivo não encontrado']);
    exit();
}

// Verificar se o arquivo de descrição existe
$descriptionFile = $filePath . ".descricao.txt";
if (file_exists($descriptionFile)) {
    // Ler o conteúdo atual
    $lines = file($descriptionFile, FILE_IGNORE_NEW_LINES);
    $usuario = $lines[0] ?? $_SESSION['username'];
    $dataEnvio = $lines[2] ?? date('Y-m-d H:i:s');
    
    // Atualizar o arquivo de descrição
    $content = $usuario . "\n" . $description . "\n" . $dataEnvio;
    if (file_put_contents($descriptionFile, $content)) {
        // Log da alteração
        logMessage("Descrição do arquivo $file atualizada por " . $_SESSION['username']);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Descrição atualizada com sucesso']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar descrição']);
    }
} else {
    // Criar um novo arquivo de descrição
    $usuario = $_SESSION['username'] ?? 'Sistema';
    $dataEnvio = date('Y-m-d H:i:s');
    $content = $usuario . "\n" . $description . "\n" . $dataEnvio;
    
    if (file_put_contents($descriptionFile, $content)) {
        // Log da criação
        logMessage("Descrição do arquivo $file criada por " . $_SESSION['username']);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Descrição criada com sucesso']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao criar descrição']);
    }
}