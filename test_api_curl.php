<?php
/**
 * Test script to directly call the API using the curl command provided by the user
 * This script can be run from the command line: php test_api_curl.php
 * Or accessed via the web browser
 */

// Set headers for proper display in browser
header('Content-Type: text/html; charset=utf-8');

echo "<h1>API Test Script</h1>";
echo "<p>Testing direct curl command to the API</p>";

// The curl command provided by the user
$curl_command = 'curl -X GET "https://comunicaapi.pje.jus.br/api/v1/comunicacao?dataDisponibilizacaoInicio=2025-04-08&dataDisponibilizacaoFim=2025-04-08&siglaTribunal=&pagina=1&tamanhoPagina=100&meio=D" -H "Accept: application/json"';

// Include config file to get proxy constants
require_once 'config.php';

// Add SOCKS5 proxy settings to the curl command
$curl_command_with_proxy = 'curl -X GET "https://comunicaapi.pje.jus.br/api/v1/comunicacao?dataDisponibilizacaoInicio=2025-04-08&dataDisponibilizacaoFim=2025-04-08&siglaTribunal=&pagina=1&tamanhoPagina=100&meio=D" -H "Accept: application/json" --socks5 ' . PROXY_SOCKS_HOST . ':' . PROXY_SOCKS_PORT . ' --proxy-user ' . PROXY_SOCKS_AUTH;

echo "<h2>Original Curl Command:</h2>";
echo "<pre>" . htmlspecialchars($curl_command) . "</pre>";

echo "<h2>Curl Command with Proxy:</h2>";
echo "<pre>" . htmlspecialchars($curl_command_with_proxy) . "</pre>";

// Execute the command
echo "<h2>Executing Command...</h2>";
echo "<pre>";
$start_time = microtime(true);
$result = shell_exec($curl_command_with_proxy . " 2>&1");
$end_time = microtime(true);
$execution_time = $end_time - $start_time;

echo "Execution time: " . round($execution_time, 2) . " seconds\n\n";

// Check if the result is valid JSON
$json_data = json_decode($result, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "Valid JSON response received.\n\n";
    
    // Check if it's a paginated response
    if (isset($json_data['content']) && is_array($json_data['content'])) {
        echo "Paginated response detected.\n";
        echo "Total elements: " . ($json_data['totalElements'] ?? 'Unknown') . "\n";
        echo "Total pages: " . ($json_data['totalPages'] ?? 'Unknown') . "\n";
        echo "Current page: " . ($json_data['number'] ?? 'Unknown') . "\n";
        echo "Page size: " . ($json_data['size'] ?? 'Unknown') . "\n\n";
        
        echo "First 3 items (if available):\n";
        $items = array_slice($json_data['content'], 0, 3);
        echo json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "Non-paginated response.\n";
        echo "Number of items: " . count($json_data) . "\n\n";
        
        echo "First 3 items (if available):\n";
        $items = array_slice($json_data, 0, 3);
        echo json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} else {
    echo "Error parsing JSON: " . json_last_error_msg() . "\n\n";
    echo "Raw response:\n" . htmlspecialchars($result);
}
echo "</pre>";

// Also save the result to a file for further analysis
$log_file = 'api_test_result.json';
file_put_contents($log_file, $result);
echo "<p>Full response saved to: <code>$log_file</code></p>";

// Add a link to go back to the notifications page
echo '<p><a href="notifications.php" class="btn btn-primary">Return to Notifications Page</a></p>';
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