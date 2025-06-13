<?php
date_default_timezone_set('America/Sao_Paulo');

$url = "https://www.contatodiario.com.br:443/services/ws.php";
$username = "959805";
$password = "173378";

// Obtém a data do usuário ou usa a data atual
$dataUsuario = isset($_POST['data']) ? $_POST['data'] : date('Y-m-d');
$datetime = date('Ymd', strtotime($dataUsuario)) . '000000';

// XML request para SOAP
$requestXml = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsdl="www.contatodiario.com.br?wsdl">
   <soapenv:Header/>
   <soapenv:Body>
      <wsdl:getRecords>
         <username>{$username}</username>
         <password>{$password}</password>
         <datetime>{$datetime}</datetime>
      </wsdl:getRecords>
   </soapenv:Body>
</soapenv:Envelope>
XML;

// Inicializa cURL
$ch = curl_init($url);

// Configurações do cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $requestXml);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));

// Executa a requisição e obtém a resposta
$response = curl_exec($ch);
curl_close($ch);

// Converte a resposta de ISO-8859-1 para UTF-8
$response = mb_convert_encoding($response, 'UTF-8', 'ISO-8859-1');

// Processa a resposta procurando por cada intimação
$intimacoes = [];
$pattern = '/<item xsi:type="tns:Register">(.*?)<\/item>/s';
preg_match_all($pattern, $response, $matches);

foreach ($matches[1] as $match) {
    $intimacao = [];

    if (preg_match('/<cdLancamento xsi:type="xsd:int">(.*?)<\/cdLancamento>/', $match, $m)) {
        $intimacao['cdLancamento'] = $m[1];
    }
    if (preg_match('/<dtPublicacao xsi:type="xsd:string">(.*?)<\/dtPublicacao>/', $match, $m)) {
        $intimacao['dtPublicacao'] = $m[1];
    }
    if (preg_match('/<dtDivulgacao xsi:type="xsd:string">(.*?)<\/dtDivulgacao>/', $match, $m)) {
        $intimacao['dtDivulgacao'] = $m[1];
    }
    if (preg_match('/<nrProcesso xsi:type="xsd:string">(.*?)<\/nrProcesso>/', $match, $m)) {
        $intimacao['nrProcesso'] = $m[1];
    }
    if (preg_match('/<nmAdvogado xsi:type="xsd:string">(.*?)<\/nmAdvogado>/', $match, $m)) {
        $intimacao['nmAdvogado'] = $m[1];
    }
    if (preg_match('/<nmJornal xsi:type="xsd:string">(.*?)<\/nmJornal>/', $match, $m)) {
        $intimacao['nmJornal'] = $m[1];
    }
    if (preg_match('/<dsIntimacao xsi:type="xsd:string">(.*?)<\/dsIntimacao>/s', $match, $m)) {
        // Decodifica as entidades HTML
        $intimacao['dsIntimacao'] = htmlspecialchars_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), ENT_QUOTES);
    }

    $intimacoes[] = $intimacao;
}

$totalIntimacoes = count($intimacoes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intimações</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { display: flex; flex-direction: column; gap: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background-color: #f4f4f4; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
<div class="container">
    <form method="POST">
        <label for="data">Data das Intimações (YYYY-MM-DD):</label>
        <input type="date" id="data" name="data" value="<?php echo htmlspecialchars($dataUsuario); ?>">
        <button type="submit">Buscar</button>
    </form>
    <div>
        <h2>Total de Intimações: <?php echo $totalIntimacoes; ?></h2>
        <?php if ($totalIntimacoes > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Código de Lançamento</th>
                    <th>Data de Publicação</th>
                    <th>Data de Divulgação</th>
                    <th>Número do Processo</th>
                    <th>Nome do Advogado</th>
                    <th>Nome do Jornal</th>
                    <th>Descrição da Intimação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($intimacoes as $intimacao): ?>
                <tr>
                    <td><?php echo htmlspecialchars($intimacao['cdLancamento']); ?></td>
                    <td><?php echo htmlspecialchars($intimacao['dtPublicacao']); ?></td>
                    <td><?php echo htmlspecialchars($intimacao['dtDivulgacao']); ?></td>
                    <td><?php echo htmlspecialchars($intimacao['nrProcesso']); ?></td>
                    <td><?php echo htmlspecialchars($intimacao['nmAdvogado']); ?></td>
                    <td><?php echo htmlspecialchars($intimacao['nmJornal']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($intimacao['dsIntimacao'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>Nenhuma intimação encontrada.</p>
        <?php endif; ?>
    </div>
    <div>
        <h2>Depuração</h2>
        <pre><?php echo htmlspecialchars($response); ?></pre>
    </div>
</div>
</body>
</html>
