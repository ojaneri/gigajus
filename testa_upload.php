<?php

// URL do upload_handler.php
$url = 'https://janeri.com.br/gigajus/v2/upload_handler.php?id=5';

// Arquivo de teste para upload
$filePath = '/etc/passwd'; // Substitua pelo caminho real do arquivo
$fileDescription = 'Descrição do arquivo de teste';

// Iniciar cURL
$ch = curl_init();

// Configurar opções do cURL
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore SSL verification for testing (not recommended for production)

// Arquivo e descrição para enviar
$data = [
    'arquivo' => new CURLFile($filePath),
    'descricao' => $fileDescription,
];

// Setar os dados do POST
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

// Executar o cURL
$response = curl_exec($ch);

// Verificar se houve algum erro
if (curl_errno($ch)) {
    echo 'Erro no cURL: ' . curl_error($ch);
} else {
    // Imprimir resposta
    echo 'Resposta do servidor: ' . $response;
}

// Fechar cURL
curl_close($ch);
