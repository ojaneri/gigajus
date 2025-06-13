<?php
/**
 * Script para configuração e atualização do banco de dados
 * Este script verifica e cria as tabelas necessárias para o funcionamento do sistema
 */

require_once '/var/www/html/janeri.com.br/gigajus/v2/config.php';
logMessage("[setup_database.php] Iniciando verificação e atualização do banco de dados.");

// Função para executar um arquivo SQL
function executeSqlFile($conn, $filename) {
    logMessage("[setup_database.php] Executando arquivo SQL: $filename");
    
    // Ler o conteúdo do arquivo SQL
    $sql = file_get_contents($filename);
    
    // Dividir o conteúdo em consultas individuais
    $queries = explode(';', $sql);
    
    // Executar cada consulta
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                $result = $conn->query($query);
                if (!$result) {
                    logMessage("[setup_database.php] Erro ao executar consulta: " . $conn->error);
                }
            } catch (Exception $e) {
                logMessage("[setup_database.php] Exceção ao executar consulta: " . $e->getMessage());
            }
        }
    }
    
    logMessage("[setup_database.php] Arquivo SQL executado com sucesso: $filename");
}

// Verificar e criar tabela de atendimentos
$checkTableSql = "SHOW TABLES LIKE 'atendimentos'";
$result = $conn->query($checkTableSql);

if ($result->num_rows == 0) {
    logMessage("[setup_database.php] Tabela 'atendimentos' não encontrada. Criando tabela...");
    executeSqlFile($conn, __DIR__ . '/compromissos_schema.sql');
} else {
    logMessage("[setup_database.php] Tabela 'atendimentos' já existe.");
}

// Verificar e criar outras tabelas necessárias
// Aqui você pode adicionar verificações para outras tabelas

// Verificar se a coluna 'ativo' existe na tabela 'usuarios'
$checkAtivoColumnSql = "SHOW COLUMNS FROM usuarios LIKE 'ativo'";
$result = $conn->query($checkAtivoColumnSql);

if ($result->num_rows == 0) {
    logMessage("[setup_database.php] Coluna 'ativo' não encontrada na tabela 'usuarios'. Adicionando coluna...");
    executeSqlFile($conn, __DIR__ . '/add_ativo_usuarios.sql');
} else {
    logMessage("[setup_database.php] Coluna 'ativo' já existe na tabela 'usuarios'.");
}

logMessage("[setup_database.php] Verificação e atualização do banco de dados concluída.");
echo "Configuração do banco de dados concluída com sucesso!";
?>