<?php
/**
 * update_address_structure.php
 * Script to update the database structure for client addresses and migrate existing data
 * Autor: Osvaldo Janeri Filho
 * Data: 2025-06-13
 */

// Carregar configurações
$rootPath = dirname(__DIR__);
require_once $rootPath . '/config.php';

// Função para registrar mensagens de log
function logScriptMessage($message, $level = 'INFO') {
    echo date('Y-m-d H:i:s') . " [$level] $message\n";
    
    // Se a função logMessage existir no sistema, use-a também
    if (function_exists('logMessage')) {
        logMessage($message, $level);
    }
}

// Verificar se a tabela clientes existe
logScriptMessage("Verificando se a tabela clientes existe...");
$tableCheckQuery = "SHOW TABLES LIKE 'clientes'";
$tableResult = $conn->query($tableCheckQuery);

if ($tableResult->num_rows == 0) {
    logScriptMessage("A tabela clientes não existe. Abortando.", 'ERROR');
    exit(1);
}

// Verificar se as colunas já existem
logScriptMessage("Verificando se as novas colunas já existem...");
$columnCheckQuery = "SHOW COLUMNS FROM clientes LIKE 'cep'";
$columnResult = $conn->query($columnCheckQuery);

if ($columnResult->num_rows > 0) {
    logScriptMessage("As colunas de endereço já existem. Verificando se há dados para migrar.", 'WARNING');
} else {
    // Adicionar novas colunas
    logScriptMessage("Adicionando novas colunas de endereço...");
    
    $alterTableQuery = "
    ALTER TABLE clientes 
    ADD COLUMN cep VARCHAR(10) AFTER endereco,
    ADD COLUMN logradouro VARCHAR(255) AFTER cep,
    ADD COLUMN numero VARCHAR(20) AFTER logradouro,
    ADD COLUMN complemento VARCHAR(255) AFTER numero,
    ADD COLUMN bairro VARCHAR(100) AFTER complemento,
    ADD COLUMN cidade VARCHAR(100) AFTER bairro,
    ADD COLUMN estado VARCHAR(2) AFTER cidade";
    
    if ($conn->query($alterTableQuery) === TRUE) {
        logScriptMessage("Novas colunas adicionadas com sucesso.");
        
        // Criar índices para busca
        logScriptMessage("Criando índices para busca...");
        $createIndexQueries = [
            "CREATE INDEX idx_cliente_cidade ON clientes(cidade)",
            "CREATE INDEX idx_cliente_estado ON clientes(estado)",
            "CREATE INDEX idx_cliente_cep ON clientes(cep)"
        ];
        
        foreach ($createIndexQueries as $indexQuery) {
            if ($conn->query($indexQuery) === TRUE) {
                logScriptMessage("Índice criado: $indexQuery");
            } else {
                logScriptMessage("Erro ao criar índice: " . $conn->error, 'ERROR');
            }
        }
    } else {
        logScriptMessage("Erro ao adicionar novas colunas: " . $conn->error, 'ERROR');
        exit(1);
    }
}

// Migrar dados existentes
logScriptMessage("Iniciando migração de dados de endereço existentes...");

// Buscar clientes com endereço preenchido mas sem dados nas novas colunas
$clientesQuery = "SELECT id_cliente, endereco FROM clientes 
                 WHERE endereco IS NOT NULL AND endereco != '' 
                 AND (logradouro IS NULL OR logradouro = '')";
$clientesResult = $conn->query($clientesQuery);

if ($clientesResult->num_rows > 0) {
    logScriptMessage("Encontrados {$clientesResult->num_rows} clientes para migração de dados.");
    
    // Preparar statement para atualização
    $updateStmt = $conn->prepare("UPDATE clientes SET 
                                logradouro = ?, 
                                numero = ?, 
                                complemento = ?, 
                                bairro = ?, 
                                cidade = ?, 
                                estado = ? 
                                WHERE id_cliente = ?");
    
    $migrados = 0;
    $erros = 0;
    
    while ($cliente = $clientesResult->fetch_assoc()) {
        $endereco = $cliente['endereco'];
        $id_cliente = $cliente['id_cliente'];
        
        // Tentar extrair informações do endereço
        // Este é um processo heurístico simples, pode não funcionar para todos os formatos
        $logradouro = $numero = $complemento = $bairro = $cidade = $estado = '';
        
        // Padrões comuns de endereço brasileiro
        if (preg_match('/^(.*?),?\s*(?:n[º°]?\s*)?(\d+)(?:[,\s]+(.*))?$/i', $endereco, $matches)) {
            // Formato: Rua Nome da Rua, 123, Complemento
            $logradouro = trim($matches[1]);
            $numero = trim($matches[2]);
            $complemento = isset($matches[3]) ? trim($matches[3]) : '';
            
            // Tentar extrair bairro, cidade e estado do complemento
            if (!empty($complemento)) {
                // Procurar por padrões como "Bairro - Cidade/UF"
                if (preg_match('/^(.*?)(?:\s*[-,]\s*)(.*?)(?:\/|\s*[-,]\s*)([A-Z]{2})$/i', $complemento, $detalhes)) {
                    $bairro = trim($detalhes[1]);
                    $cidade = trim($detalhes[2]);
                    $estado = strtoupper(trim($detalhes[3]));
                    $complemento = ''; // Limpar complemento pois extraímos as informações
                }
                // Ou apenas "Cidade/UF"
                elseif (preg_match('/^(.*?)(?:\/|\s*[-,]\s*)([A-Z]{2})$/i', $complemento, $detalhes)) {
                    $cidade = trim($detalhes[1]);
                    $estado = strtoupper(trim($detalhes[2]));
                    $complemento = ''; // Limpar complemento pois extraímos as informações
                }
            }
        } else {
            // Se não conseguir extrair no formato padrão, coloca tudo no logradouro
            $logradouro = $endereco;
        }
        
        // Atualizar o cliente
        $updateStmt->bind_param("ssssssi", $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $id_cliente);
        
        if ($updateStmt->execute()) {
            $migrados++;
        } else {
            $erros++;
            logScriptMessage("Erro ao migrar cliente ID $id_cliente: " . $updateStmt->error, 'ERROR');
        }
    }
    
    logScriptMessage("Migração concluída. $migrados clientes migrados com sucesso. $erros erros encontrados.");
    $updateStmt->close();
} else {
    logScriptMessage("Nenhum cliente encontrado para migração de dados.");
}

logScriptMessage("Processo de atualização da estrutura de endereços concluído com sucesso!");