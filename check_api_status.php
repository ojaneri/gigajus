#!/usr/bin/env php
<?php
/**
 * check_api_status.php
 * Script para verificar o status da API e do proxy SOCKS5
 * 
 * Este script pode ser executado manualmente ou via cron para monitorar
 * a disponibilidade da API e do proxy.
 * 
 * Uso:
 *   php check_api_status.php [--verbose] [--notify]
 * 
 * Opções:
 *   --verbose  Exibe informações detalhadas
 *   --notify   Envia notificação por e-mail em caso de falha
 */

// Define o timezone
date_default_timezone_set('America/Sao_Paulo');

// Caminho absoluto para o diretório do script
$scriptDir = dirname(__FILE__);
chdir($scriptDir);

// Processa argumentos da linha de comando
$options = getopt('', ['verbose::', 'notify::']);
$verbose = isset($options['verbose']);
$notify = isset($options['notify']);

// Inclui os arquivos necessários
require_once 'config.php';
require_once 'includes/notifications_helper.php';

// Função para registrar logs
function logStatus($message, $level = 'INFO') {
    global $verbose;
    
    $logFile = 'logs/api_status.log';
    
    // Cria o diretório de logs se não existir
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }
    
    $timestamp = date('[Y-m-d H:i:s]');
    $log_entry = "$timestamp [$level] $message";
    file_put_contents($logFile, $log_entry . PHP_EOL, FILE_APPEND);
    
    // Também exibe no console
    echo $log_entry . PHP_EOL;
}

// Função para verificar o proxy SOCKS5
function checkProxy() {
    global $verbose;
    
    logStatus("Verificando proxy SOCKS5: " . PROXY_SOCKS_HOST . ":" . PROXY_SOCKS_PORT);
    
    // Verifica se o host está acessível
    $ping_result = shell_exec("ping -c 1 " . PROXY_SOCKS_HOST);
    $ping_success = strpos($ping_result, "1 received") !== false;
    
    if ($ping_success) {
        logStatus("Proxy SOCKS5 está respondendo ao ping", "SUCCESS");
    } else {
        logStatus("Proxy SOCKS5 não está respondendo ao ping", "ERROR");
        return false;
    }
    
    // Tenta uma conexão simples via proxy
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://www.google.com",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_PROXY => PROXY_SOCKS_HOST,
        CURLOPT_PROXYPORT => PROXY_SOCKS_PORT,
        CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
        CURLOPT_PROXYUSERPWD => PROXY_SOCKS_AUTH,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);
    
    $result = curl_exec($ch);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($result !== false && $info['http_code'] == 200) {
        logStatus("Proxy SOCKS5 está funcionando corretamente", "SUCCESS");
        if ($verbose) {
            logStatus("Tempo de resposta: " . $info['total_time'] . "s", "DEBUG");
        }
        return true;
    } else {
        logStatus("Erro ao conectar via proxy SOCKS5: " . $error, "ERROR");
        if ($verbose) {
            logStatus("Informações da requisição: " . json_encode($info), "DEBUG");
        }
        return false;
    }
}

// Função para verificar a API
function checkAPI() {
    global $verbose;
    
    logStatus("Verificando API: https://comunicaapi.pje.jus.br/api/v1/comunicacao");
    
    // Verifica se o host está acessível
    $ping_result = shell_exec("ping -c 1 comunicaapi.pje.jus.br");
    $ping_success = strpos($ping_result, "1 received") !== false;
    
    if ($ping_success) {
        logStatus("Servidor da API está respondendo ao ping", "SUCCESS");
    } else {
        logStatus("Servidor da API não está respondendo ao ping", "WARNING");
        // Continua mesmo sem ping, pois pode ser que o servidor bloqueie ICMP
    }
    
    // Prepara os parâmetros para a API
    $params = [
        'meio' => 'D',
        'pagina' => 1,
        'tamanhoPagina' => 1,
        'dataDisponibilizacaoInicio' => date('Y-m-d', strtotime('-1 day')),
        'dataDisponibilizacaoFim' => date('Y-m-d')
    ];
    
    // Tenta acessar a API diretamente (sem proxy)
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://comunicaapi.pje.jus.br/api/v1/comunicacao?" . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);
    
    $result = curl_exec($ch);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($result !== false) {
        logStatus("API respondeu com status code: " . $info['http_code'], $info['http_code'] == 200 ? "SUCCESS" : "WARNING");
        if ($verbose) {
            logStatus("Tempo de resposta: " . $info['total_time'] . "s", "DEBUG");
            logStatus("Resposta: " . substr($result, 0, 200) . "...", "DEBUG");
        }
    } else {
        logStatus("Erro ao conectar diretamente à API: " . $error, "ERROR");
        if ($verbose) {
            logStatus("Informações da requisição: " . json_encode($info), "DEBUG");
        }
    }
    
    // Tenta acessar a API via proxy SOCKS5
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://comunicaapi.pje.jus.br/api/v1/comunicacao?" . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_PROXY => PROXY_SOCKS_HOST,
        CURLOPT_PROXYPORT => PROXY_SOCKS_PORT,
        CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
        CURLOPT_PROXYUSERPWD => PROXY_SOCKS_AUTH,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);
    
    $result = curl_exec($ch);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($result !== false) {
        logStatus("API via proxy respondeu com status code: " . $info['http_code'], $info['http_code'] == 200 ? "SUCCESS" : "WARNING");
        if ($verbose) {
            logStatus("Tempo de resposta: " . $info['total_time'] . "s", "DEBUG");
            logStatus("Resposta: " . substr($result, 0, 200) . "...", "DEBUG");
        }
        return $info['http_code'] == 200;
    } else {
        logStatus("Erro ao conectar à API via proxy: " . $error, "ERROR");
        if ($verbose) {
            logStatus("Informações da requisição: " . json_encode($info), "DEBUG");
        }
        return false;
    }
}

// Função para enviar notificação por e-mail
function sendStatusNotification($subject, $message) {
    global $notify;
    
    if (!$notify) {
        return;
    }
    
    // Endereço de e-mail do administrador
    $to = "admin@example.com";
    
    // Cabeçalhos do e-mail
    $headers = "From: sistema@gigajus.com.br\r\n";
    $headers .= "Reply-To: sistema@gigajus.com.br\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Envia o e-mail
    mail($to, $subject, $message, $headers);
    
    logStatus("Notificação enviada para $to", "INFO");
}

// Inicia a verificação
logStatus("Iniciando verificação de status da API e proxy");

// Verifica o proxy
$proxy_status = checkProxy();

// Verifica a API
$api_status = checkAPI();

// Resumo
logStatus("Resumo da verificação:");
logStatus("Proxy SOCKS5: " . ($proxy_status ? "OK" : "FALHA"));
logStatus("API: " . ($api_status ? "OK" : "FALHA"));

// Envia notificação se necessário
if (!$proxy_status || !$api_status) {
    $subject = "[ALERTA] Problema com API ou Proxy - " . date('Y-m-d H:i:s');
    $message = "<h2>Alerta de Monitoramento</h2>";
    $message .= "<p>Foi detectado um problema com a API ou o proxy SOCKS5:</p>";
    $message .= "<ul>";
    $message .= "<li>Proxy SOCKS5: " . ($proxy_status ? "OK" : "FALHA") . "</li>";
    $message .= "<li>API: " . ($api_status ? "OK" : "FALHA") . "</li>";
    $message .= "</ul>";
    $message .= "<p>Por favor, verifique o sistema e tome as medidas necessárias.</p>";
    $message .= "<p>Data e hora: " . date('Y-m-d H:i:s') . "</p>";
    
    sendStatusNotification($subject, $message);
}

logStatus("Verificação concluída");
exit($proxy_status && $api_status ? 0 : 1);
?>