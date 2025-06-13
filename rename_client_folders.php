<?php
require 'config.php';

// Get all clients from the database
$query = "SELECT id_cliente, nome FROM clientes";
$result = $conn->query($query);

if (!$result) {
    logMessage("Erro ao buscar clientes: " . $conn->error);
    exit();
}

$renamed = 0;
$errors = 0;

echo "<h1>Renomeando pastas de clientes</h1>";
echo "<p>Iniciando processo de renomeação de pastas...</p>";

while ($client = $result->fetch_assoc()) {
    $id = $client['id_cliente'];
    $nome = $client['nome'];
    
    // Criar nome de pasta no formato "Nome do cliente (ID X)"
    $folderName = preg_replace('/[^a-zA-Z0-9\s]/', '', $nome); // Remove caracteres especiais
    $folderName = trim($folderName); // Remove espaços extras
    $folderName = "$folderName (ID $id)";
    
    // Criar diretório clientes se não existir
    if (!file_exists("uploads/clientes/")) {
        mkdir("uploads/clientes/", 0777, true);
        logMessage("Diretório uploads/clientes/ criado com sucesso!");
    }
    
    $oldPath = "uploads/$id/";
    $oldPath2 = "uploads/$folderName/";
    $newPath = "uploads/clientes/$folderName/";
    
    // Verificar se as pastas antigas existem e a nova não existe
    if (file_exists($oldPath) && !file_exists($newPath)) {
        logMessage("Renomeando pasta do cliente $nome (ID: $id)");
        logMessage("De: $oldPath");
        logMessage("Para: $newPath");
        
        if (rename($oldPath, $newPath)) {
            logMessage("✅ Pasta renomeada com sucesso!");
            $renamed++;
        } else {
            logMessage("❌ Erro ao renomear pasta!");
            $errors++;
        }
    } else if (file_exists($oldPath2) && !file_exists($newPath)) {
        logMessage("Renomeando pasta do cliente $nome (ID: $id)");
        logMessage("De: $oldPath2");
        logMessage("Para: $newPath");
        
        if (rename($oldPath2, $newPath)) {
            logMessage("✅ Pasta renomeada com sucesso!");
            $renamed++;
        } else {
            logMessage("❌ Erro ao renomear pasta!");
            $errors++;
        }
    } else if (file_exists($oldPath) && file_exists($oldPath2) && !file_exists($newPath)) {
        logMessage("⚠️ Múltiplas pastas antigas existem para o cliente $nome (ID: $id). Movendo arquivos...");
        
        // Criar a nova pasta
        mkdir($newPath, 0777, true);
        
        // Mover arquivos da pasta antiga com ID para a nova
        $files = scandir($oldPath);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                if (copy("$oldPath/$file", "$newPath/$file")) {
                    unlink("$oldPath/$file");
                    logMessage("  ✓ Arquivo $file movido de $oldPath com sucesso");
                } else {
                    logMessage("  ✗ Erro ao mover arquivo $file de $oldPath");
                }
            }
        }
        
        // Mover arquivos da pasta antiga com nome formatado para a nova
        $files = scandir($oldPath2);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                if (copy("$oldPath2/$file", "$newPath/$file")) {
                    unlink("$oldPath2/$file");
                    logMessage("  ✓ Arquivo $file movido de $oldPath2 com sucesso");
                } else {
                    logMessage("  ✗ Erro ao mover arquivo $file de $oldPath2");
                }
            }
        }
        
        // Remover pastas antigas
        if (rmdir($oldPath)) {
            logMessage("✅ Pasta antiga $oldPath removida com sucesso!");
        } else {
            logMessage("❌ Erro ao remover pasta antiga $oldPath!");
            $errors++;
        }
        
        if (rmdir($oldPath2)) {
            logMessage("✅ Pasta antiga $oldPath2 removida com sucesso!");
            $renamed++;
        } else {
            logMessage("❌ Erro ao remover pasta antiga $oldPath2!");
            $errors++;
        }
    } else if (!file_exists($oldPath) && !file_exists($oldPath2) && !file_exists($newPath)) {
        logMessage("ℹ️ Nenhuma pasta encontrada para o cliente $nome (ID: $id)");
    } else if (!file_exists($oldPath) && !file_exists($oldPath2) && file_exists($newPath)) {
        logMessage("✅ Pasta já está no novo formato para o cliente $nome (ID: $id)");
    }
    
    echo "<hr>";
}

echo "<h2>Resumo</h2>";
echo "<p>Total de pastas renomeadas: $renamed</p>";
echo "<p>Total de erros: $errors</p>";
echo "<p><a href='index.php'>Voltar para a página inicial</a></p>";
?>