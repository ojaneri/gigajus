<?php
require 'config.php';
date_default_timezone_set('America/Sao_Paulo');

// Credenciais do serviço
$username = "959905";
$password = "173878";
$url = "https://www.contatodiario.com.br:443/services/ws.php";

// Processamento dos dados
$dataInicio = $_POST['data_inicio'] ?? date('Y-m-d');
$dataFim = $_POST['data_fim'] ?? $dataInicio;
$nrProcesso = trim($_POST['nr_processo'] ?? '');
$palavraChave = trim($_POST['palavra_chave'] ?? '');
$jornal = trim($_POST['jornal'] ?? '');

// Validação das datas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim)) {
    die("Formato de data inválido");
}
?>
=======
<?php
require 'config.php';
date_default_timezone_set('America/Sao_Paulo');
?>

// Credenciais do serviço
$username = "959905";
$password = "173878";
$url = "https://www.contatodiario.com.br:443/services/ws.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Pública de Intimações</title>
    <link rel="stylesheet" href="assets/css/unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .main-content {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<?php
$username = "959905";
$password = "173878";
$url = "https://www.contatodiario.com.br:443/services/ws.php";

// Inicia buffer de saída
ob_start();

// Processamento PHP...

// Coleta todo o HTML em uma variável
$html = <<<HTML
    <div class="main-content">

// ... todo o código de processamento PHP ...

// Termina o bloco PHP ANTES do HTML
?>























$totalIntimacoes = count($intimacoes);




// Exportar para CSV
if (isset($_POST['exportar_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="intimacoes_' . date('Ymd-His') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
    
    fputcsv($output, [
        'Código', 'Processo', 'Advogado', 'Jornal',
        'Publicação', 'Divulgação', 'Descrição'
    ], ';');
    
    foreach ($intimacoes as $intimacao) {
        fputcsv($output, [
            $intimacao['cdLancamento'] ?? '',
            $intimacao['nrProcesso'] ?? '',
            $intimacao['nmAdvogado'] ?? '',
            $intimacao['nmJornal'] ?? '',
            $intimacao['dtPublicacao'] ?? '',
            $intimacao['dtDivulgacao'] ?? '',
            utf8_encode($intimacao['dsIntimacao'] ?? '')
        ], ';');
    }
    exit;
}
?>

   <div class="content">
        <div class="form-header">
            <h2><i class="fas fa-bell"></i> Notificações</h2>
        </div>
        <form method="POST" class="unified-form filter-grid">
            <div class="input-group">
                <label for="data_inicio"><i class="fas fa-calendar-day"></i> Data Inicial:</label>
                <input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($_POST['data_inicio'] ?? ''); ?>" class="form-control">
            </div>
            
            <div class="input-group">
                <label for="data_fim"><i class="fas fa-calendar-day"></i> Data Final:</label>
                <input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($_POST['data_fim'] ?? ''); ?>" class="form-control">
            </div>

            <div class="input-group">
                <label for="nr_processo"><i class="fas fa-file-alt"></i> Número do Processo:</label>
                <input type="text" id="nr_processo" name="nr_processo" value="<?php echo htmlspecialchars($_POST['nr_processo'] ?? ''); ?>" class="form-control">
            </div>

            <div class="input-group">
                <label for="palavra_chave"><i class="fas fa-key"></i> Palavra-chave:</label>
                <input type="text" id="palavra_chave" name="palavra_chave" value="<?php echo htmlspecialchars($_POST['palavra_chave'] ?? ''); ?>" class="form-control">
            </div>

            <div class="input-group">
                <label for="jornal"><i class="fas fa-newspaper"></i> Tribunal/Jornal:</label>
                <input type="text" id="jornal" name="jornal" value="<?php echo htmlspecialchars($_POST['jornal'] ?? ''); ?>" class="form-control">
            </div>

            <div class="form-buttons">
                <button type="submit" name="buscar" class="btn btn-primary">
                    <i class="fas fa-search"></i> Aplicar Filtros
                </button>
                
                <button type="submit" name="exportar_csv" class="btn btn-export">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </button>
            </div>
        </form>

        <div class="process-list"> <!-- Added process-list div here -->
            <h3>Total de Notificações: <?php echo $totalIntimacoes; ?></h3>
            <?php if ($totalIntimacoes > 0): ?>
            <table class="striped-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Publicação</th>
                        <th>Divulgação</th>
                        <th>Processo</th>
                        <th>Advogado</th>
                        <th>Jornal</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($intimacoes as $intimacao): ?>
                    <tr class="intimacao-row" onclick="toggleIntimacao(this)">
                        <td><?php echo htmlspecialchars($intimacao['cdLancamento']); ?></td>
                        <td><?php echo htmlspecialchars($intimacao['dtPublicacao']); ?></td>
                        <td>
                            <div class="descricao-truncada"><?php
                                $desc = htmlspecialchars($intimacao['dtDivulgacao']);
                                echo strlen($desc) > 100 ? substr($desc, 0, 100).'...' : $desc;
                            ?></div>
                            <div class="descricao-completa"><?php echo $desc; ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($intimacao['nrProcesso']); ?></td>
                        <td><?php echo htmlspecialchars($intimacao['nmAdvogado']); ?></td>
                        <td><?php echo htmlspecialchars($intimacao['nmJornal']); ?></td>
                        <td class="toggle-cell" onclick="toggleDescricao(this)">
                            <div class="descricao-truncada">
                                <?php
                                    $desc = nl2br(htmlspecialchars($intimacao['dsIntimacao']));
                                    echo strlen($desc) > 100 ? substr($desc, 0, 100).'...' : $desc;
                                ?>
                            </div>
                            <div class="descricao-completa" style="display: none;">
                                <?php echo $desc; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>Nenhuma notificação encontrada para a data selecionada.</p>
            <?php endif; ?>
        </div> <!-- Closing process-list div -->
        <div>
            <h2>Debug XML Response</h2>
            <pre><?php echo htmlspecialchars($response); ?></pre>
        </div>
    </div>

</body>
</html>
