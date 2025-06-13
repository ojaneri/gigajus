<?php

date_default_timezone_set('America/Sao_Paulo');

$servername = "localhost";
$username = "gigajus";
$password = "gigajus";
$dbname = "gigajus";
define('API_NOTIFICATIONS_KEY', 'seu_token_jwt_aqui');

// Configurações de proxy para APIs externas
define('PROXY_HOST', '200.234.178.126');
define('PROXY_PORT', '59100');
define('PROXY_AUTH', 'janeri:aM9z7EhhbR');

// Configurações de proxy SOCKS5 para APIs externas
define('PROXY_SOCKS_HOST', '200.234.178.126');
define('PROXY_SOCKS_PORT', '59101');
define('PROXY_SOCKS_AUTH', 'janeri:aM9z7EhhbR');

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Função para registrar logs
function logMessage($message) {
    $logFile = 'gigajus.log';
    $backtrace = debug_backtrace();
    $caller = isset($backtrace[0]['file']) ? basename($backtrace[0]['file']) : 'unknown';

    $current = file_get_contents($logFile);
    $current .= date('Y-m-d H:i:s') . " - [$caller] " . $message . "\n";
    file_put_contents($logFile, $current);
}
?>
