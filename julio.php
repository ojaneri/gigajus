<?php
date_default_timezone_set('America/Sao_Paulo');

$url = "https://www.contatodiario.com.br:443/services/ws.php";
$username = "959905";
$password = "173878";

// Obtém a data e o filtro do nome do jornal do usuário
$dataUsuario = isset($_POST['data']) ? $_POST['data'] : date('Y-m-d');
$nomeJornalFiltro = isset($_POST['nome_jornal']) ? $_POST['nome_jornal'] : '';
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
$jornalNomes = [];
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
        $jornalNomes[] = $intimacao['nmJornal'];
    }
    if (preg_match('/<dsIntimacao xsi:type="xsd:string">(.*?)<\/dsIntimacao>/s', $match, $m)) {
        $intimacao['dsIntimacao'] = htmlspecialchars_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), ENT_QUOTES);
    }

    $intimacoes[] = $intimacao;
}

// Remove duplicatas e ordena os nomes dos jornais
$jornalNomes = array_unique($jornalNomes);
sort($jornalNomes);

// Filtra intimações pelo nome do jornal, se um filtro foi aplicado
if ($nomeJornalFiltro) {
    $intimacoes = array_filter($intimacoes, function($intimacao) use ($nomeJornalFiltro) {
        return $intimacao['nmJornal'] === $nomeJornalFiltro;
    });
}

$totalIntimacoes = count($intimacoes);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intimações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h2 class="text-center mb-4">Consulta de Intimações</h2>
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-6">
            <label for="data" class="form-label">Data das Intimações (YYYY-MM-DD):</label>
            <input type="date" id="data" name="data" value="<?php echo htmlspecialchars($dataUsuario); ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label for="nome_jornal" class="form-label">Nome do Jornal:</label>
            <select id="nome_jornal" name="nome_jornal" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($jornalNomes as $nomeJornal): ?>
                    <option value="<?php echo htmlspecialchars($nomeJornal); ?>" <?php echo $nomeJornalFiltro === $nomeJornal ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($nomeJornal); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 text-center">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Total de Intimações: <?php echo $totalIntimacoes; ?></h5>
            <?php if ($totalIntimacoes > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mt-3">
                        <thead class="table-light">
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
                </div>
            <?php else: ?>
                <p class="text-muted">Nenhuma intimação encontrada.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-5">
        <h5>Depuração</h5>
        <pre class="bg-light p-3 border"><?php echo htmlspecialchars($response); ?></pre>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
