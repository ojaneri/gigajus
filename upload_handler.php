<?php
require 'config.php';
session_start(); // Garantir que a sessão está iniciada

logMessage("Passo 1: Iniciando o upload_handler.php");

// Verificar se o ID do cliente foi passado
logMessage("Passo 2: Verificando se o ID do cliente foi passado.");
if (!isset($_GET['id']) || empty($_GET['id'])) {
    logMessage("Passo 2.1: ID do cliente não fornecido.");
    echo json_encode(["success" => false, "message" => "ID do cliente não fornecido."]);
    exit();
}

$id = $_GET['id'];
logMessage("Passo 2.2: ID do cliente recebido: $id");

// Recebendo arquivo para upload
logMessage("Passo 3: Verificando se um arquivo foi enviado.");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    logMessage("Passo 3.1: Arquivo encontrado no POST.");

    if (is_uploaded_file($_FILES['arquivo']['tmp_name'])) {
        logMessage("Passo 3.2: O arquivo foi enviado com sucesso.");

        $erro = $_FILES['arquivo']['error'];
        logMessage("Passo 3.3: Detalhes do arquivo: " . print_r($_FILES['arquivo'], true));

        if ($erro === UPLOAD_ERR_OK) {
            logMessage("Passo 3.4: Nenhum erro no upload do arquivo.");
            $descricao = $_POST['descricao'];
            $usuario = $_SESSION['user_name']; // Assumindo que o nome do usuário está armazenado na sessão
            $arquivo = $_FILES['arquivo'];
            $nomeOriginal = basename($arquivo['name']);
            
            // Obter o nome do cliente do banco de dados
            $stmt = $conn->prepare("SELECT nome FROM clientes WHERE id_cliente = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Criar diretório clientes se não existir
            if (!file_exists("uploads/clientes/")) {
                mkdir("uploads/clientes/", 0777, true);
                logMessage("Diretório uploads/clientes/ criado com sucesso.");
            }
            
            if ($result->num_rows > 0) {
                $client = $result->fetch_assoc();
                $nomeCliente = $client['nome'];
                
                // Criar nome de pasta no formato "Nome do cliente (ID X)"
                $folderName = preg_replace('/[^a-zA-Z0-9\s]/', '', $nomeCliente); // Remove caracteres especiais
                $folderName = trim($folderName); // Remove espaços extras
                $folderName = "$folderName (ID $id)";
                $uploadDir = "uploads/clientes/$folderName/";
                
                // Verificar se existem pastas antigas
                $oldUploadDir = "uploads/$id/";
                $oldUploadDir2 = "uploads/$folderName/";
                
                if (file_exists($oldUploadDir) && !file_exists($uploadDir)) {
                    // Renomear pasta antiga com ID para o novo formato em uploads/clientes/
                    if (rename($oldUploadDir, $uploadDir)) {
                        logMessage("Pasta $oldUploadDir renomeada para $uploadDir com sucesso.");
                    } else {
                        logMessage("Erro ao renomear a pasta $oldUploadDir para $uploadDir.");
                    }
                } else if (file_exists($oldUploadDir2) && !file_exists($uploadDir)) {
                    // Renomear pasta antiga com nome formatado para o novo diretório
                    if (rename($oldUploadDir2, $uploadDir)) {
                        logMessage("Pasta $oldUploadDir2 renomeada para $uploadDir com sucesso.");
                    } else {
                        logMessage("Erro ao renomear a pasta $oldUploadDir2 para $uploadDir.");
                    }
                }
            } else {
                // Se não encontrar o cliente, usa o ID como fallback
                $uploadDir = "uploads/clientes/$id/";
                logMessage("Cliente com ID $id não encontrado no banco de dados. Usando ID como nome da pasta.");
            }
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true); // Cria o diretório se não existir
            }
            $caminhoCompleto = $uploadDir . $nomeOriginal;
            $descricaoArquivo = $caminhoCompleto . ".descricao.txt";

            logMessage("Passo 3.5: Movendo arquivo para $caminhoCompleto.");
            if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                logMessage("Passo 3.6: Arquivo $nomeOriginal enviado com sucesso para $caminhoCompleto.");
                file_put_contents($descricaoArquivo, "$usuario\n$descricao");
                logMessage("Passo 3.7: Descrição do arquivo salva em $descricaoArquivo.");
                echo json_encode(["success" => true, "message" => "Arquivo e descrição enviados com sucesso."]);
            } else {
                logMessage("Passo 3.8: Erro ao mover o arquivo para $caminhoCompleto.");
                echo json_encode(["success" => false, "message" => "Erro ao enviar o arquivo."]);
            }
        } else {
            logMessage("Passo 3.9: Erro ao fazer upload do arquivo. Código de erro: $erro");
            echo json_encode(["success" => false, "message" => "Erro ao fazer upload do arquivo. Código de erro: $erro"]);
        }
    } else {
        logMessage("Passo 3.10: Nenhum arquivo foi enviado.");
        echo json_encode(["success" => false, "message" => "Nenhum arquivo foi enviado."]);
    }
} else {
    logMessage("Passo 3.11: Método de requisição não é POST ou campo de arquivo não encontrado no POST.");
    echo json_encode(["success" => false, "message" => "Método de requisição não é POST ou campo de arquivo não encontrado no POST."]);
}
?>
