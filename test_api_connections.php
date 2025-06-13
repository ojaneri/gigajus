#!/usr/bin/env php
<?php
/**
 * test_api_connections.php
 * Script para testar diferentes métodos de conexão com a API de intimações
 * 
 * Este script testa o acesso à API usando:
 * 1. Conexão direta
 * 2. Proxy HTTP
 * 3. Proxy SOCKS5
 * 
 * Uso:
 *   php test_api_connections.php [--verbose]
 * 
 * Opções:
 *   --verbose  Exibe informações detalhadas
 */

// Define o timezone
date_default_timezone_set('America/Sao_Paulo');

// Processa argumentos da linha de comando
$options = getopt('', ['verbose::']);
$verbose = isset($options['verbose']);

// Inclui os arquivos necessários
require_once 'config.php';

// URL da API
$api_url = 'https://comunicaapi.pje.jus.br/api/v1/comunicacao';

// Parâmetros da requisição
$params = [
    'meio' => 'D',
    'pagina' => 1,
    'tamanhoPagina' => 10,
    'dataDisponibilizacaoInicio' => date('Y-m-d', strtotime('-7 days')),
    'dataDisponibilizacaoFim' => date('Y-m-d')
];

// Cabeçalhos da requisição
$headers = [
    'Content-Type: application/json',
    'Accept: application/json'
];

// Função para exibir resultados
function displayResult($title, $success, $status_code, $time, $error = '', $response = '') {
    global $verbose;
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "$title\n";
    echo str_repeat("=", 80) . "\n";
    
    echo "Status: " . ($success ? "SUCESSO" : "FALHA") . "\n";
    echo "Código HTTP: $status_code\n";
    echo "Tempo de execução: " . round($time, 2) . " segundos\n";
    
    if (!$success && $error) {
        echo "Erro: $error\n";
    }
    
    if ($verbose && $response) {
        echo "\nResposta (primeiros 500 caracteres):\n";
        echo substr($response, 0, 500) . (strlen($response) > 500 ? "..." : "") . "\n";
    }
}

// Função para salvar resultados em arquivo
function saveResult($filename, $data) {
    file_put_contents($filename, $data);
    echo "Resposta completa salva em: $filename\n";
}

echo "\n";
echo "TESTE DE CONEXÕES COM A API DE INTIMAÇÕES\n";
echo "Data e hora: " . date('Y-m-d H:i:s') . "\n";
echo "URL da API: $api_url\n";
echo "Parâmetros: " . json_encode($params) . "\n\n";

// 1. Teste de conexão direta
echo "Testando conexão direta...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api_url . '?' . http_build_query($params),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_VERBOSE => $verbose
]);

$start_time = microtime(true);
$response = curl_exec($ch);
$end_time = microtime(true);
$execution_time = $end_time - $start_time;

$success = ($response !== false);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

displayResult("1. CONEXÃO DIRETA", $success, $status_code, $execution_time, $error, $response);
if ($response) {
    saveResult('api_direct_response.json', $response);
}

// 2. Teste de conexão via proxy HTTP
echo "\nTestando conexão via proxy HTTP...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api_url . '?' . http_build_query($params),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_VERBOSE => $verbose,
    // Configuração do proxy HTTP
    CURLOPT_PROXY => PROXY_HOST,
    CURLOPT_PROXYPORT => PROXY_PORT,
    CURLOPT_PROXYUSERPWD => PROXY_AUTH
]);

$start_time = microtime(true);
$response = curl_exec($ch);
$end_time = microtime(true);
$execution_time = $end_time - $start_time;

$success = ($response !== false);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

displayResult("2. CONEXÃO VIA PROXY HTTP", $success, $status_code, $execution_time, $error, $response);
if ($response) {
    saveResult('api_http_proxy_response.json', $response);
}

// 3. Teste de conexão via proxy SOCKS5
echo "\nTestando conexão via proxy SOCKS5...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api_url . '?' . http_build_query($params),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_VERBOSE => $verbose,
    // Configuração do proxy SOCKS5
    CURLOPT_PROXY => PROXY_SOCKS_HOST,
    CURLOPT_PROXYPORT => PROXY_SOCKS_PORT,
    CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
    CURLOPT_PROXYUSERPWD => PROXY_SOCKS_AUTH
]);

$start_time = microtime(true);
$response = curl_exec($ch);
$end_time = microtime(true);
$execution_time = $end_time - $start_time;

$success = ($response !== false);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

displayResult("3. CONEXÃO VIA PROXY SOCKS5", $success, $status_code, $execution_time, $error, $response);
if ($response) {
    saveResult('api_socks5_proxy_response.json', $response);
}

// 4. Teste de conexão via proxy SOCKS5 com parâmetros específicos
echo "\nTestando conexão via proxy SOCKS5 com parâmetros específicos...\n";

// Parâmetros específicos para teste
$specific_params = [
    'meio' => 'D',
    'pagina' => 1,
    'tamanhoPagina' => 10,
    'numeroOab' => '25695',
    'ufOab' => 'CE',
    'dataDisponibilizacaoInicio' => date('Y-m-d', strtotime('-1 day')),
    'dataDisponibilizacaoFim' => date('Y-m-d')
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api_url . '?' . http_build_query($specific_params),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_VERBOSE => $verbose,
    // Configuração do proxy SOCKS5
    CURLOPT_PROXY => PROXY_SOCKS_HOST,
    CURLOPT_PROXYPORT => PROXY_SOCKS_PORT,
    CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
    CURLOPT_PROXYUSERPWD => PROXY_SOCKS_AUTH
]);

$start_time = microtime(true);
$response = curl_exec($ch);
$end_time = microtime(true);
$execution_time = $end_time - $start_time;

$success = ($response !== false);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

displayResult("4. CONEXÃO VIA PROXY SOCKS5 COM PARÂMETROS ESPECÍFICOS", $success, $status_code, $execution_time, $error, $response);
if ($response) {
    saveResult('api_socks5_specific_response.json', $response);
}

// 5. Teste de conexão via proxy SOCKS5 com Python (usando shell_exec)
echo "\nTestando conexão via proxy SOCKS5 com Python...\n";

$python_command = "python3 -c \"
import requests
import json
import time

proxies = {
    'http': 'socks5://" . PROXY_SOCKS_AUTH . "@" . PROXY_SOCKS_HOST . ":" . PROXY_SOCKS_PORT . "',
    'https': 'socks5://" . PROXY_SOCKS_AUTH . "@" . PROXY_SOCKS_HOST . ":" . PROXY_SOCKS_PORT . "'
}

params = {
    'meio': 'D',
    'pagina': 1,
    'tamanhoPagina': 10,
    'dataDisponibilizacaoInicio': '" . date('Y-m-d', strtotime('-7 days')) . "',
    'dataDisponibilizacaoFim': '" . date('Y-m-d') . "'
}

start_time = time.time()
try:
    response = requests.get('" . $api_url . "', params=params, proxies=proxies, timeout=30)
    end_time = time.time()
    print('Status Code: ' + str(response.status_code))
    print('Execution Time: ' + str(round(end_time - start_time, 2)) + ' seconds')
    print('Response: ' + response.text[:500])
    with open('api_python_response.json', 'w') as f:
        f.write(response.text)
except Exception as e:
    end_time = time.time()
    print('Error: ' + str(e))
    print('Execution Time: ' + str(round(end_time - start_time, 2)) + ' seconds')
\"";

$start_time = microtime(true);
$python_output = shell_exec($python_command);
$end_time = microtime(true);
$execution_time = $end_time - $start_time;

echo "\n" . str_repeat("=", 80) . "\n";
echo "5. CONEXÃO VIA PROXY SOCKS5 COM PYTHON\n";
echo str_repeat("=", 80) . "\n";
echo "Saída do Python:\n";
echo $python_output . "\n";

echo "\nRESUMO DOS TESTES\n";
echo str_repeat("=", 80) . "\n";
echo "1. Conexão direta: " . ($status_code == 200 ? "SUCESSO" : "FALHA") . "\n";
echo "2. Conexão via proxy HTTP: " . ($status_code == 200 ? "SUCESSO" : "FALHA") . "\n";
echo "3. Conexão via proxy SOCKS5: " . ($status_code == 200 ? "SUCESSO" : "FALHA") . "\n";
echo "4. Conexão via proxy SOCKS5 com parâmetros específicos: " . ($status_code == 200 ? "SUCESSO" : "FALHA") . "\n";
echo "5. Conexão via proxy SOCKS5 com Python: " . (strpos($python_output, 'Status Code: 200') !== false ? "SUCESSO" : "FALHA") . "\n";

echo "\nOs resultados completos foram salvos nos arquivos .json correspondentes.\n";
?>