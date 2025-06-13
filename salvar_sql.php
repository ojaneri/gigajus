<?php
require_once 'config.php';

// Função para obter a estrutura de uma tabela
function getTableStructure($conn, $tableName) {
    $structure = "-- Estrutura da tabela `$tableName`\n";
    $structure .= "SHOW CREATE TABLE `$tableName`;\n\n";
    
    // Obter a estrutura da tabela
    $result = $conn->query("SHOW CREATE TABLE `$tableName`");
    if ($result && $row = $result->fetch_assoc()) {
        $structure .= $row['Create Table'] . ";\n\n";
    }
    
    // Obter as colunas da tabela
    $structure .= "-- Colunas da tabela `$tableName`\n";
    $result = $conn->query("DESCRIBE `$tableName`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $structure .= "-- " . $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . 
                          ($row['Default'] !== null ? " - DEFAULT '" . $row['Default'] . "'" : "") . 
                          ($row['Extra'] ? " - " . $row['Extra'] : "") . "\n";
        }
        $structure .= "\n";
    }
    
    return $structure;
}

// Iniciar o arquivo SQL
$sqlContent = "-- Estrutura do banco de dados `$dbname`\n";
$sqlContent .= "-- Gerado em: " . date('Y-m-d H:i:s') . "\n\n";

// Obter todas as tabelas do banco de dados
$tables = [];
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
}

// Obter a estrutura de cada tabela
foreach ($tables as $table) {
    $sqlContent .= getTableStructure($conn, $table);
}

// Salvar o conteúdo no arquivo
file_put_contents('banco.sql', $sqlContent);

// Usar mysqldump para obter uma exportação completa (alternativa)
$command = "mysqldump -u$username -p$password --no-data --skip-comments $dbname > banco_completo.sql";
shell_exec($command);

echo "Estrutura do banco de dados salva com sucesso em banco.sql e banco_completo.sql!";

// Verificar erros específicos no index.php
$indexContent = file_get_contents('index.php');
$lines = explode("\n", $indexContent);

echo "<h2>Análise do arquivo index.php</h2>";
echo "<pre>";

// Verificar a consulta SQL problemática
$sqlAtividadesLine = 0;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], '$sqlAtividades') !== false) {
        $sqlAtividadesLine = $i;
        break;
    }
}

if ($sqlAtividadesLine > 0) {
    echo "Consulta SQL encontrada na linha $sqlAtividadesLine:\n";
    
    // Mostrar a consulta SQL
    for ($i = $sqlAtividadesLine; $i < $sqlAtividadesLine + 15; $i++) {
        if ($i < count($lines)) {
            echo $lines[$i] . "\n";
        }
    }
    
    // Verificar se as colunas existem nas tabelas
    echo "\nVerificando colunas nas tabelas:\n";
    
    // Verificar tabela tarefas
    $result = $conn->query("DESCRIBE tarefas");
    if ($result) {
        echo "Colunas da tabela 'tarefas':\n";
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
            echo "- " . $row['Field'] . "\n";
        }
        
        // Verificar se data_horario_final existe
        if (in_array('data_horario_final', $columns)) {
            echo "✓ Coluna 'data_horario_final' existe na tabela 'tarefas'\n";
        } else {
            echo "✗ Coluna 'data_horario_final' NÃO existe na tabela 'tarefas'\n";
        }
    }
    
    // Verificar tabela processos
    $result = $conn->query("DESCRIBE processos");
    if ($result) {
        echo "\nColunas da tabela 'processos':\n";
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
            echo "- " . $row['Field'] . "\n";
        }
        
        // Verificar se data_abertura existe
        if (in_array('data_abertura', $columns)) {
            echo "✓ Coluna 'data_abertura' existe na tabela 'processos'\n";
        } else {
            echo "✗ Coluna 'data_abertura' NÃO existe na tabela 'processos'\n";
        }
        
        // Verificar se numero_processo existe
        if (in_array('numero_processo', $columns)) {
            echo "✓ Coluna 'numero_processo' existe na tabela 'processos'\n";
        } else {
            echo "✗ Coluna 'numero_processo' NÃO existe na tabela 'processos'\n";
        }
    }
}

// Verificar logs de erro
if (file_exists('gigajus.log')) {
    echo "\n\nÚltimas 20 linhas do arquivo de log:\n";
    $logContent = file_get_contents('gigajus.log');
    $logLines = explode("\n", $logContent);
    $logLines = array_slice($logLines, -20);
    foreach ($logLines as $line) {
        echo $line . "\n";
    }
}

echo "</pre>";
?>