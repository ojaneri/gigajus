#!/usr/bin/env php
<?php
/**
 * fetch_daily_notifications.php
 * Script para buscar automaticamente intimações de todos os advogados cadastrados
 * para a data especificada (padrão: dia anterior).
 *
 * Este script é destinado a ser executado via crontab diariamente.
 * Exemplo de configuração no crontab:
 * 0 6 * * * /usr/bin/php /var/www/html/janeri.com.br/gigajus/v2/fetch_daily_notifications.php >> /var/www/html/janeri.com.br/gigajus/v2/logs/cron_notifications.log 2>&1
 *
 * Opções de linha de comando:
 * --date=YYYY-MM-DD : Especifica a data para buscar intimações (padrão: ontem)
 * --timeout=N       : Timeout em segundos para requisições API (padrão: 60)
 * --verbose         : Modo verboso com mais detalhes de log
 */

// Define o timezone
date_default_timezone_set('America/Sao_Paulo');

// Caminho absoluto para o diretório do script
$scriptDir = dirname(__FILE__);
chdir($scriptDir);

// Processa argumentos da linha de comando
$options = getopt('', ['date:', 'timeout::', 'verbose::']);
$target_date = isset($options['date']) ? $options['date'] : date('Y-m-d', strtotime('-1 day'));
$api_timeout = isset($options['timeout']) ? intval($options['timeout']) : 60;
$verbose = isset($options['verbose']);

// Valida a data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $target_date)) {
    echo "Erro: Formato de data inválido. Use YYYY-MM-DD.\n";
    exit(1);
}

// Inclui os arquivos necessários
require_once 'config.php';
require_once 'includes/notifications_helper.php';

// Aumenta o timeout para requisições cURL
ini_set('default_socket_timeout', $api_timeout);

// Função para registrar logs
function logToFile($message, $level = 'INFO') {
    global $verbose;
    
    $logFile = 'logs/daily_notifications.log';
    
    // Cria o diretório de logs se não existir
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }
    
    $timestamp = date('[Y-m-d H:i:s]');
    $log_entry = "$timestamp [$level] $message";
    file_put_contents($logFile, $log_entry . PHP_EOL, FILE_APPEND);
    
    // Também exibe no console para acompanhamento em tempo real
    // No modo não-verboso, só exibe INFO, WARNING e ERROR
    if ($verbose || in_array($level, ['INFO', 'WARNING', 'ERROR'])) {
        echo $log_entry . PHP_EOL;
    }
}

// Inicia o processo
logToFile("Iniciando busca de intimações para a data: $target_date");

// Registra informações sobre o ambiente
if ($verbose) {
    logToFile("Timeout da API configurado para $api_timeout segundos", "DEBUG");
    logToFile("Proxy configurado: " . PROXY_SOCKS_HOST . ":" . PROXY_SOCKS_PORT . " (SOCKS5)", "DEBUG");
}

// Verifica a conexão com o banco de dados
if ($conn->connect_error) {
    logToFile("ERRO: Falha na conexão com o banco de dados: " . $conn->connect_error);
    exit(1);
}

// Busca todas as empresas cadastradas
$empresas_query = "SELECT id_empresa, nome FROM empresas WHERE ativo = 1";
$empresas_result = $conn->query($empresas_query);

if (!$empresas_result) {
    logToFile("ERRO: Falha ao buscar empresas: " . $conn->error);
    exit(1);
}

$total_empresas = $empresas_result->num_rows;
logToFile("Encontradas $total_empresas empresas ativas");

$total_advogados = 0;
$total_notificacoes = 0;

// Para cada empresa, busca os advogados
while ($empresa = $empresas_result->fetch_assoc()) {
    $id_empresa = $empresa['id_empresa'];
    $nome_empresa = $empresa['nome'];
    
    logToFile("Processando empresa: $nome_empresa (ID: $id_empresa)");
    
    // Busca os advogados da empresa
    $advogados_query = "SELECT id_advogado, nome_advogado, oab_numero, oab_uf FROM advogados WHERE id_empresa = ?";
    $advogados_stmt = $conn->prepare($advogados_query);
    $advogados_stmt->bind_param("i", $id_empresa);
    $advogados_stmt->execute();
    $advogados_result = $advogados_stmt->get_result();
    
    $num_advogados = $advogados_result->num_rows;
    logToFile("Encontrados $num_advogados advogados ativos na empresa $nome_empresa");
    
    // Para cada advogado, busca as intimações
    while ($advogado = $advogados_result->fetch_assoc()) {
        $total_advogados++;
        
        $nome_advogado = $advogado['nome_advogado'];
        $oab_numero = $advogado['oab_numero'];
        $oab_uf = $advogado['oab_uf'];
        
        logToFile("Buscando intimações para: $nome_advogado (OAB $oab_numero/$oab_uf)");
        
        // Configura os filtros para a busca
        $filters = [
            'oab_numero' => $oab_numero,
            'oab_uf' => $oab_uf,
            'advogado_nome' => $nome_advogado,
            'data_inicio' => $target_date,
            'data_fim' => $target_date
        ];
        
        if ($verbose) {
            logToFile("Filtros para $nome_advogado: " . json_encode($filters), "DEBUG");
        }
        
        try {
            // Busca as intimações na API
            $api_notifications = fetchNotificationsFromAPI($filters);
            
            // Verifica se houve erro
            if (is_array($api_notifications) && isset($api_notifications['error'])) {
                logToFile("ERRO ao buscar intimações para $nome_advogado: " . print_r($api_notifications['error'], true), "ERROR");
                continue;
            }
            
            // Verifica se as notificações são um array válido
            if (!is_array($api_notifications)) {
                logToFile("ERRO: Formato de resposta inválido para $nome_advogado", "ERROR");
                continue;
            }
            
            $num_notificacoes = count($api_notifications);
            logToFile("Encontradas $num_notificacoes intimações para $nome_advogado");
            
            if ($verbose && $num_notificacoes > 0) {
                logToFile("Primeiras intimações: " . json_encode(array_slice($api_notifications, 0, 2)), "DEBUG");
            }
            
            // Salva as notificações no banco de dados
            $saved_count = saveNotificationsToDatabase($conn, $api_notifications);
            $total_notificacoes += $saved_count;
            
            logToFile("Salvas $saved_count novas intimações para $nome_advogado");
        } catch (Exception $e) {
            logToFile("Exceção ao processar advogado $nome_advogado: " . $e->getMessage(), "ERROR");
        }
        
        // Aguarda um pouco para não sobrecarregar a API
        if ($verbose) {
            logToFile("Aguardando 2 segundos antes da próxima requisição...", "DEBUG");
        }
        sleep(2);
    }
    
    $advogados_stmt->close();
}

// Finaliza o processo
logToFile("Processo concluído. Data: $target_date, Total: $total_advogados advogados processados, $total_notificacoes novas intimações salvas.");

// Registra informações de uso de memória se estiver em modo verboso
if ($verbose) {
    $memory_usage = memory_get_peak_usage(true) / 1024 / 1024;
    logToFile(sprintf("Uso máximo de memória: %.2f MB", $memory_usage), "DEBUG");
    
    $execution_time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
    logToFile(sprintf("Tempo de execução: %.2f segundos", $execution_time), "DEBUG");
}

// Fecha a conexão com o banco de dados
$conn->close();

exit(0); // Sai com código de sucesso
?>