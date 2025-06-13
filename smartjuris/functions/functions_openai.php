<?php
function callOpenAIAPI($prompt) {
    $apiKey = 'YOUR_OPENAI_API_KEY';  // This should be stored securely in config.php
    $url = 'https://api.openai.com/v1/chat/completions';  // Assuming ChatGPT API
    $data = json_encode([
        'model' => 'gpt-4',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => 1000,
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);

    try {
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            throw new Exception('OpenAI API error: ' . $httpCode);
        }
        return json_decode($response, true);
    } catch (Exception $e) {
        registrar_log(__FILE__, 'ERROR', $e->getMessage());  // Log the error
        return false;
    } finally {
        curl_close($ch);
    }
}

// Example function to process a decision
function processDecision($text) {
    $prompt = "Analise a decisão judicial: $text. Extraia: numero_processo, tipo_documento, relator, ementa, inteiro_teor, principais_argumentos, procedente, dano_moral, valor_dano_moral, categoria.";
    $response = callOpenAIAPI($prompt);
    if ($response && isset($response['choices'][0]['message']['content'])) {
        return json_decode($response['choices'][0]['message']['content'], true);  // Assuming JSON response
    }
    return false;
}
?>