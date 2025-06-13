<?php
/**
 * test_socks5_proxy.php
 * Script para testar a conexão com a API usando proxy SOCKS5
 * Autor: Osvaldo Janeri Filho
 * Data: 2025-05-15
 */

// Set headers for proper display in browser
header('Content-Type: text/html; charset=utf-8');

// Include config file to get proxy constants
require_once 'config.php';

echo "<h1>Teste de Conexão SOCKS5</h1>";
echo "<p>Este script testa a conexão com a API usando o proxy SOCKS5 configurado.</p>";

// Display proxy configuration
echo "<h2>Configuração do Proxy SOCKS5:</h2>";
echo "<pre>";
echo "Host: " . PROXY_SOCKS_HOST . "\n";
echo "Port: " . PROXY_SOCKS_PORT . "\n";
echo "Auth: " . PROXY_SOCKS_AUTH . "\n";
echo "</pre>";

// The curl command with SOCKS5 proxy
$curl_command = 'curl -v --socks5 ' . PROXY_SOCKS_HOST . ':' . PROXY_SOCKS_PORT . ' --proxy-user ' . PROXY_SOCKS_AUTH . ' "https://comunicaapi.pje.jus.br/api/v1/comunicacao?dataDisponibilizacaoInicio=' . date('Y-m-d', strtotime('-7 days')) . '&dataDisponibilizacaoFim=' . date('Y-m-d') . '&pagina=1&tamanhoPagina=10&meio=D" -H "Accept: application/json"';

echo "<h2>Comando curl:</h2>";
echo "<pre>" . htmlspecialchars($curl_command) . "</pre>";

// Execute the command
echo "<h2>Executando comando...</h2>";
echo "<pre>";
$start_time = microtime(true);
$result = shell_exec($curl_command . " 2>&1");
$end_time = microtime(true);
$execution_time = $end_time - $start_time;

echo "Tempo de execução: " . round($execution_time, 2) . " segundos\n\n";

// Check if the result is valid JSON
$json_data = json_decode($result, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "Resposta JSON válida recebida.\n\n";
    
    // Check if it's a paginated response
    if (isset($json_data['content']) && is_array($json_data['content'])) {
        echo "Resposta paginada detectada.\n";
        echo "Total de elementos: " . ($json_data['totalElements'] ?? 'Desconhecido') . "\n";
        echo "Total de páginas: " . ($json_data['totalPages'] ?? 'Desconhecido') . "\n";
        echo "Página atual: " . ($json_data['number'] ?? 'Desconhecido') . "\n";
        echo "Tamanho da página: " . ($json_data['size'] ?? 'Desconhecido') . "\n\n";
        
        echo "Primeiros 3 itens (se disponíveis):\n";
        $items = array_slice($json_data['content'], 0, 3);
        echo json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "Resposta não paginada.\n";
        echo "Número de itens: " . count($json_data) . "\n\n";
        
        echo "Primeiros 3 itens (se disponíveis):\n";
        $items = array_slice($json_data, 0, 3);
        echo json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} else {
    echo "Erro ao analisar JSON: " . json_last_error_msg() . "\n\n";
    echo "Resposta bruta:\n" . htmlspecialchars($result);
}
echo "</pre>";

// Also save the result to a file for further analysis
$log_file = 'socks5_test_result.json';
file_put_contents($log_file, $result);
echo "<p>Resposta completa salva em: <code>$log_file</code></p>";

// Add a link to go back to the notifications page
echo '<p><a href="notifications.php" class="btn btn-primary">Voltar para a Página de Notificações</a></p>';
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    line-height: 1.6;
}
h1, h2 {
    color: #333;
}
pre {
    background-color: #f5f5f5;
    padding: 10px;
    border-radius: 5px;
    overflow: auto;
    max-height: 400px;
}
.btn {
    display: inline-block;
    padding: 8px 16px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 4px;
}
.btn:hover {
    background-color: #0056b3;
}
</style>