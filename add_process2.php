<?php
require 'config.php';
session_start();
require_once 'includes/notifications_helper.php';

// Recebe parâmetros via GET
$numero_processo = isset($_GET['numero_processo']) ? trim($_GET['numero_processo']) : '';
$nome_parte = isset($_GET['nome_parte']) ? trim($_GET['nome_parte']) : '';
$tribunal_get = isset($_GET['tribunal']) ? trim($_GET['tribunal']) : '';

// Função para extrair nomes das partes do texto da intimação
function extrairNomesPartes($texto) {
    $padrao = '/\b(?:autor|réu|reu|requerente(?:\(s\))?|requerido(?:\(a\)\(s\))?|apelante|apelado|promovente|promovido|agravante|agravado)\b\s*:\s*([A-Z\sÇÃÕÁÉÍÓÚÂÊÔÀ\.]+(?: e outros)?)/iu';

    preg_match_all($padrao, $texto, $matches, PREG_SET_ORDER);

    $partes = [];
    foreach ($matches as $match) {
        $tipo = mb_strtoupper(trim($match[0]));
        $nome = trim($match[1]);
        
        // Determina o tipo da parte
        if (stripos($tipo, 'AUTOR') !== false) {
            $partes['AUTOR'] = $nome;
        } elseif (stripos($tipo, 'RÉU') !== false || stripos($tipo, 'REU') !== false) {
            $partes['REU'] = $nome;
        } elseif (stripos($tipo, 'REQUERENTE') !== false) {
            $partes['REQUERENTE'] = $nome;
        } elseif (stripos($tipo, 'REQUERIDO') !== false) {
            $partes['REQUERIDO'] = $nome;
        } elseif (stripos($tipo, 'APELANTE') !== false) {
            $partes['APELANTE'] = $nome;
        } elseif (stripos($tipo, 'APELADO') !== false) {
            $partes['APELADO'] = $nome;
        } elseif (stripos($tipo, 'PROMOVENTE') !== false) {
            $partes['PROMOVENTE'] = $nome;
        } elseif (stripos($tipo, 'PROMOVIDO') !== false) {
            $partes['PROMOVIDO'] = $nome;
        } elseif (stripos($tipo, 'AGRAVANTE') !== false) {
            $partes['AGRAVANTE'] = $nome;
        } elseif (stripos($tipo, 'AGRAVADO') !== false) {
            $partes['AGRAVADO'] = $nome;
        }
    }
    
    // Procura por padrões específicos para os exemplos fornecidos
    if (strpos($texto, 'PROMOVENTE(S):') !== false && strpos($texto, 'Endereço: Nome:') !== false) {
        // Exemplo 1: PROMOVENTE(S): OSVALDO JANERI FILHO Endereço: Nome: OSVALDO JANERI FILHO
        preg_match('/PROMOVENTE\(S\):\s*([^\n\r]+?)(?=\s*Endereço:)/is', $texto, $matches_promovente);
        if (!empty($matches_promovente[1])) {
            $partes['PROMOVENTE'] = trim($matches_promovente[1]);
        } else {
            // Tenta extrair do formato "Endereço: Nome: NOME"
            preg_match('/PROMOVENTE\(S\):.*?Endereço:\s*Nome:\s*([^\n\r]+?)(?=Endereço:|$)/is', $texto, $matches_nome);
            if (!empty($matches_nome[1])) {
                $partes['PROMOVENTE'] = trim($matches_nome[1]);
            }
        }
        
        // Extrai PROMOVIDO do mesmo formato
        preg_match('/PROMOVIDO\(S\):\s*([^\n\r]+?)(?=\s*Endereço:)/is', $texto, $matches_promovido);
        if (!empty($matches_promovido[1])) {
            $partes['PROMOVIDO'] = trim($matches_promovido[1]);
        } else {
            // Tenta extrair do formato "Endereço: Nome: NOME"
            preg_match('/PROMOVIDO\(S\):.*?Endereço:\s*Nome:\s*([^\n\r]+?)(?=Endereço:|$)/is', $texto, $matches_nome);
            if (!empty($matches_nome[1])) {
                $partes['PROMOVIDO'] = trim($matches_nome[1]);
            }
        }
    }
    
    // Procura por padrões como "ajuizou a presente demanda contra"
    if (preg_match('/([^\s]+(?:\s+[^\s]+){1,5})\s+ajuizou\s+a\s+presente\s+demanda\s+contra\s+([^\s]+(?:\s+[^\s]+){1,10})/is', $texto, $matches)) {
        if (!isset($partes['AUTOR']) && !isset($partes['REQUERENTE']) && !isset($partes['PROMOVENTE'])) {
            $partes['AUTOR'] = trim($matches[1]);
        }
        if (!isset($partes['REU']) && !isset($partes['REQUERIDO']) && !isset($partes['PROMOVIDO'])) {
            $partes['REU'] = trim($matches[2]);
        }
    }
    
    // Limpa e formata os nomes encontrados
    foreach ($partes as $tipo => $nome) {
        // Remove múltiplos espaços
        $nome = preg_replace('/\s+/', ' ', $nome);
        
        // Remove caracteres especiais e pontuação no final
        $nome = rtrim($nome, ".,;:()[]{}'\"\t\n\r ");
        
        // Remove prefixos comuns
        $nome = preg_replace('/^(Dr\.|Dr\(a\)|Dra\.|Exmo\.|Exma\.|Ilmo\.|Ilma\.|Sr\.|Sra\.|MM\.|M\.M\.)?\s*/i', '', $nome);
        
        // Atualiza o nome limpo
        $partes[$tipo] = trim($nome);
    }
    
    return $partes;
}

// Função para verificar se um cliente existe no sistema
function clienteExisteNoSistema($conn, $nome) {
    $stmt = $conn->prepare("SELECT id_cliente FROM clientes WHERE nome LIKE ? AND ativo = 1");
    $param = "%" . trim($nome) . "%";
    $stmt->bind_param("s", $param);
    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();
    return $existe;
}

// Se nome_parte está vazio, tenta extrair do texto da intimação
if (empty($nome_parte) && isset($_GET['teor'])) {
    $teor_text = $_GET['teor'];
    
    // Extrai os nomes das partes do texto
    $partes_encontradas = extrairNomesPartes($teor_text);
    
    // Se encontrou alguma parte, usa a primeira como nome_parte para busca inicial
    if (!empty($partes_encontradas)) {
        // Prioridade: REQUERENTE, AUTOR, PROMOVENTE, AGRAVANTE, APELANTE
        if (isset($partes_encontradas['REQUERENTE'])) {
            $nome_parte = $partes_encontradas['REQUERENTE'];
        } elseif (isset($partes_encontradas['AUTOR'])) {
            $nome_parte = $partes_encontradas['AUTOR'];
        } elseif (isset($partes_encontradas['PROMOVENTE'])) {
            $nome_parte = $partes_encontradas['PROMOVENTE'];
        } elseif (isset($partes_encontradas['AGRAVANTE'])) {
            $nome_parte = $partes_encontradas['AGRAVANTE'];
        } elseif (isset($partes_encontradas['APELANTE'])) {
            $nome_parte = $partes_encontradas['APELANTE'];
        } elseif (isset($partes_encontradas['CABEÇALHO'])) {
            $nome_parte = $partes_encontradas['CABEÇALHO'];
        }
    } else {
        // Tenta o padrão antigo como fallback
        if (preg_match('/REQUERENTE\(S\):\s*([^\n\r]+?)\s*REQUERIDO\(A\)\(S\):\s*([^\n\r]+)/i', $teor_text, $matches)) {
            $nome_parte = trim($matches[1]);
        }
    }
}

// Handle process registration via normal POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_cliente']) && isset($_POST['numero_processo'])) {
    $id_cliente = $_POST['id_cliente'];
    $numero_processo = $_POST['numero_processo'];
    $tribunal = $_POST['tribunal'] ?? '';
    $status = $_POST['status'] ?? '';
    $data_abertura = $_POST['data_abertura'] ?? null;
    $data_fechamento = $_POST['data_fechamento'] ?? null;
    // Corrige datas vazias para NULL
    if ($data_abertura === '' || is_null($data_abertura)) {
        $data_abertura = null;
    }
    if ($data_fechamento === '' || is_null($data_fechamento)) {
        $data_fechamento = null;
    }
    $descricao = $_POST['descricao'] ?? '';
    $status_externo = $_POST['status_externo'] ?? '';

    $stmt = $conn->prepare("INSERT INTO processos (id_cliente, numero_processo, tribunal, status, data_abertura, data_fechamento, descricao, status_externo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "isssssss",
        $id_cliente,
        $numero_processo,
        $tribunal,
        $status,
        $data_abertura,
        $data_fechamento,
        $descricao,
        $status_externo
    );
    if ($stmt->execute()) {
        $stmt->close();
        echo '<script>
            alert("Processo adicionado com sucesso!");
            window.location.href = "notifications.php";
        </script>';
        exit();
    } else {
        $error_msg = 'Erro ao cadastrar processo: ' . $stmt->error;
        $stmt->close();
    }
}

// Lógica de busca de clientes
$busca_nome = '';
$is_ajax_search = isset($_POST['ajax_search']) && $_POST['ajax_search'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['busca_nome'])) {
        $busca_nome = trim($_POST['busca_nome']);
    }
    
    if (isset($_POST['create_new_client'])) {
        $create_new_client = true;
        $new_client_id = null;

        // Se o usuário pediu para criar um novo cliente
        if ($create_new_client && !empty($busca_nome)) {
        // Verifica se já existe cliente igual
        $stmt = $conn->prepare("SELECT id_cliente FROM clientes WHERE nome = ? AND ativo = 1");
        $stmt->bind_param("s", $busca_nome);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0) {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO clientes (nome, ativo) VALUES (?, 1)");
            $stmt->bind_param("s", $busca_nome);
            $stmt->execute();
            $new_client_id = $stmt->insert_id;
            $stmt->close();
        } else {
            $stmt->bind_result($existing_id);
            $stmt->fetch();
            $new_client_id = $existing_id;
            $stmt->close();
        }
            // Após criar, redefine busca_nome para vazio para não mostrar o botão novamente
            $busca_nome = '';
            
            // Se for uma requisição AJAX, inclui o ID do cliente na resposta
            if ($is_ajax_search) {
                echo "<div id='new_client_id_container' data-id='$new_client_id' style='display:none;'>new_client_id=$new_client_id</div>";
            }
        }
    }
} elseif (!empty($nome_parte)) {
    $busca_nome = $nome_parte;
}

$clientes = [];
$similar_clients = [];
$selected_client_id = null;

// Busca todos os clientes ativos para comparação de similaridade
$stmt = $conn->prepare("SELECT id_cliente, nome FROM clientes WHERE ativo = 1");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}
$stmt->close();

// Se houve busca, calcula similaridade
if (!empty($busca_nome)) {
    foreach ($clientes as $cliente) {
        similar_text(mb_strtolower($busca_nome), mb_strtolower($cliente['nome']), $percent);
        if ($percent >= 90) {
            $similar_clients[] = $cliente;
        }
    }
    // Se houver similar e nenhum cliente já selecionado, seleciona o primeiro similar
    if (count($similar_clients) > 0 && empty($selected_client_id)) {
        $selected_client_id = $similar_clients[0]['id_cliente'];
    }
}

// Se acabou de criar um novo cliente, seleciona ele
if (isset($new_client_id) && $new_client_id) {
    $selected_client_id = $new_client_id;
} elseif (isset($_POST['id_cliente'])) {
    $selected_client_id = $_POST['id_cliente'];
}


// Get notification details if ID is passed or by process number
$notification_content = '';
if (isset($_GET['id_notificacao'])) {
    $stmt = $conn->prepare("SELECT teor FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $_GET['id_notificacao']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $notification_content = $row['teor'];
    }
    $stmt->close();
} elseif (!empty($numero_processo)) {
    // Try to get notification content by process number
    $stmt = $conn->prepare("SELECT teor FROM notifications WHERE numero_processo = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("s", $numero_processo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $notification_content = $row['teor'];
    }
    $stmt->close();
}
// If this is an AJAX search request, only return the search results section
if ($is_ajax_search) {
    // Skip the header and other parts of the page
    ob_start();
?>
    <style>
    /* Ensure proper styling for AJAX results */
    .form-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f1f1f1;
    }
    .form-label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #495057;
    }
    .list-group {
        list-style: none;
        padding: 0;
        margin-bottom: 1rem;
    }
    .list-group-item {
        position: relative;
        display: block;
        padding: 0.75rem 1.25rem;
        margin-bottom: -1px;
        background-color: #fff;
        border: 1px solid rgba(0,0,0,.125);
        color: #212529;
    }
    .client-item {
        cursor: pointer;
    }
    .alert {
        position: relative;
        padding: 0.75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: 0.25rem;
    }
    .alert-info {
        color: #0c5460;
        background-color: #d1ecf1;
        border-color: #bee5eb;
    }
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }
    .btn {
        display: inline-block;
        font-weight: 400;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        user-select: none;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    }
    .btn-success {
        color: #fff;
        background-color: #28a745;
        border-color: #28a745;
    }
    .w-100 {
        width: 100%;
    }
    .mb-3 {
        margin-bottom: 1rem;
    }
    .mb-4 {
        margin-bottom: 1.5rem;
    }
    </style>
    <div class="form-section">
        <h3 style="color: #495057; margin-bottom: 1rem;"><i class="fas fa-users"></i> Resultados da Busca</h3>
        <div class="form-group mb-4">
                <label class="form-label">Resultados similares (≥90%):</label>
                <?php if (count($similar_clients) > 0): ?>
                    <ul class="list-group mb-3">
                        <?php foreach ($similar_clients as $cliente): ?>
                            <li class="list-group-item client-item"
                                data-id="<?php echo $cliente['id_cliente']; ?>"
                                style="cursor: pointer; transition: background-color 0.2s; color: #212529; background-color: #fff;"
                                onmouseover="this.style.backgroundColor='#f8f9fa'"
                                onmouseout="this.style.backgroundColor='#fff'"
                                onclick="selectClient(<?php echo $cliente['id_cliente']; ?>, '<?php echo addslashes(htmlspecialchars($cliente['nome'])); ?>')">
                                <i class="fas fa-user mr-2"></i> <?php echo htmlspecialchars($cliente['nome']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Clique em um cliente para selecioná-lo automaticamente.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger mb-3">
                        Nenhum cliente similar encontrado.
                    </div>
                    <button type="button" class="btn btn-success w-100" onclick="createNewClient('<?php echo htmlspecialchars(addslashes($busca_nome)); ?>')">
                        <i class="fas fa-user-plus"></i> Criar novo cliente: <?php echo htmlspecialchars($busca_nome); ?>
                    </button>
                <?php endif; ?>
        </div>
    </div>
<?php
    $search_results = ob_get_clean();
    echo $search_results;
    exit;
}

// Regular page load
require 'header.php';
?>
<style>
    .content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .notification-preview {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 30px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .preview-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .preview-header h4 {
        margin: 0;
        color: #495057;
    }
    
    .preview-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .toggle-preview {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }
    
    .toggle-preview:hover {
        background-color: #0069d9;
    }
    
    .preview-content, .full-content {
        background-color: white;
        padding: 15px;
        border-radius: 4px;
        border: 1px solid #e9ecef;
        white-space: pre-wrap;
        font-family: monospace;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .notification-actions {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #dee2e6;
    }
    
    .notification-status {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #dee2e6;
        text-align: center;
    }
    
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: bold;
    }
    
    .status-processed {
        background-color: #d4edda;
        color: #155724;
    }
    
    .form-container {
        background-color: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .form-header {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .form-header h2 {
        margin: 0;
        color: #495057;
    }
    
    .form-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f1f1f1;
    }
    
    .form-section:last-child {
        border-bottom: none;
    }
    
    .action-button {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        margin-top: 15px;
    }
    
    .action-button:hover {
        background-color: #218838;
    }
</style>

<div class="content">
    <div class="notification-preview">
        <div class="preview-header">
            <h4><i class="fas fa-file-alt"></i> Texto da Intimação</h4>
            <div class="preview-actions">
                <?php if (isset($_GET['id_notificacao']) && is_numeric($_GET['id_notificacao'])): ?>
                    <a href="view_notification.php?id=<?php echo intval($_GET['id_notificacao']); ?>" class="btn btn-sm btn-info" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Ver Notificação Completa
                    </a>
                <?php endif; ?>
                <button class="toggle-preview" onclick="togglePreview()">Mostrar Mais</button>
            </div>
        </div>
        <?php
        // Only show success message when a process is actually being added
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_cliente']) && isset($_POST['numero_processo']) && empty($error)) {
            echo "<script>
                alert('Processo cadastrado com sucesso!');
                window.location.href = 'notifications.php';
            </script>";
            exit();
        }
        ?>
        <script>
        // Garante que o script seja executado após o carregamento da página
        window.onload = function() {
            // Define o estado inicial explicitamente
            document.querySelector('.preview-content').style.display = 'block';
            document.querySelector('.full-content').style.display = 'none';
            document.querySelector('.toggle-preview').textContent = 'Mostrar Mais';
        };
        
        function togglePreview() {
            const preview = document.querySelector('.preview-content');
            const full = document.querySelector('.full-content');
            const button = document.querySelector('.toggle-preview');
            
            // Verifica o estado atual de forma mais robusta
            const isFullVisible = window.getComputedStyle(full).display !== 'none';
            
            if (!isFullVisible) {
                // Mostrar conteúdo completo
                full.style.display = 'block';
                preview.style.display = 'none';
                button.textContent = 'Mostrar Menos';
            } else {
                // Mostrar preview
                full.style.display = 'none';
                preview.style.display = 'block';
                button.textContent = 'Mostrar Mais';
            }
        }
        </script>
        <div class="preview-content" style="display: block;">
            <?php
            // Mostrar apenas os primeiros 500 caracteres
            $preview_text = mb_substr($notification_content, 0, 500);
            echo nl2br(htmlspecialchars($preview_text));
            
            // Adicionar reticências se o texto for maior que 500 caracteres
            if (mb_strlen($notification_content) > 500) {
                echo '<span style="color: #6c757d;">...</span>';
            }
            ?>
        </div>
        <div class="full-content" style="display: none !important;">
            <?php echo nl2br(htmlspecialchars($notification_content)); ?>
        </div>
        
        <?php if (isset($_GET['id_notificacao']) && is_numeric($_GET['id_notificacao'])):
            $notif_id = intval($_GET['id_notificacao']);
            // Verifica se a notificação está processada
            $check_processed = $conn->prepare("SELECT processada FROM notifications WHERE id = ?");
            $check_processed->bind_param("i", $notif_id);
            $check_processed->execute();
            $processed_result = $check_processed->get_result();
            
            if ($processed_result->num_rows > 0) {
                $processed_row = $processed_result->fetch_assoc();
                $is_processed = $processed_row['processada'] == 1;
                
                if (!$is_processed):
        ?>
                <div class="notification-actions">
                    <form action="process_notification.php" method="POST" style="display: inline;">
                        <input type="hidden" name="notification_id" value="<?php echo $notif_id; ?>">
                        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                        <button type="submit" name="mark_processed" class="btn btn-success">
                            <i class="fas fa-check-circle"></i> Marcar Notificação como Processada
                        </button>
                    </form>
                </div>
        <?php
                else:
        ?>
                <div class="notification-status">
                    <span class="status-badge status-processed">
                        <i class="fas fa-check-circle"></i> Notificação Processada
                    </span>
                </div>
        <?php
                endif;
            }
            $check_processed->close();
        endif;
        ?>
    </div>
    </div>
    
        <div class="form-header">
            <h2><i class="fas fa-gavel"></i> Adicionar Novo Processo</h2>
        </div>
        
        <?php if (!empty($notification_content)): ?>
        <div class="form-section">
            <h3><i class="fas fa-users"></i> Partes Identificadas na Intimação</h3>
            <?php
            // Extrai os nomes das partes do texto da intimação
            $partes_encontradas = extrairNomesPartes($notification_content);
            
            if (!empty($partes_encontradas)):
            ?>
            <div class="table-responsive mb-4">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Tipo</th>
                            <th>Nome</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partes_encontradas as $tipo => $nome): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($tipo); ?></strong></td>
                            <td><?php
                                // Limita o nome a 50 caracteres para exibição
                                $nome_exibicao = mb_strlen($nome) > 50 ? mb_substr($nome, 0, 50) . '...' : $nome;
                                echo htmlspecialchars($nome_exibicao);
                            ?></td>
                            <td>
                                <?php
                                $existe = clienteExisteNoSistema($conn, $nome);
                                if ($existe):
                                ?>
                                <span class="badge bg-success">Cliente Existente</span>
                                <?php else: ?>
                                <span class="badge bg-warning text-dark">Não Cadastrado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" onclick="selecionarParte('<?php echo addslashes(htmlspecialchars($nome)); ?>')">
                                    <i class="fas fa-user-check"></i> Selecionar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-user-plus"></i> Adicionar Novo Cliente</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="novo_cliente_nome" class="form-label">Nome do novo cliente:</label>
                                <input type="text" id="novo_cliente_nome" class="form-control mb-3" placeholder="Digite o nome completo">
                                <button type="button" class="btn btn-success w-100" onclick="criarNovoCliente()">
                                    <i class="fas fa-plus-circle"></i> Adicionar Cliente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Escolher Cliente Cadastrado</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="busca_cliente" class="form-label">Buscar cliente:</label>
                                <input type="text" id="busca_cliente" class="form-control mb-3" placeholder="Digite o nome do cliente" oninput="filtrarClientes(this.value)">
                                
                                <div id="resultados_busca" class="mb-3" style="max-height: 200px; overflow-y: auto; display: none;">
                                    <ul class="list-group" id="lista_clientes_filtrados">
                                        <!-- Resultados da busca serão inseridos aqui via JavaScript -->
                                    </ul>
                                </div>
                                
                                <select id="cliente_existente" class="form-select mb-3" onchange="atualizarClienteSelecionado(this.value)">
                                    <option value="">-- Selecione um cliente --</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id_cliente']; ?>"><?php echo htmlspecialchars($cliente['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <button type="button" class="btn btn-success w-100" onclick="confirmarClienteSelecionado()">
                                    <i class="fas fa-check-circle"></i> Confirmar Cliente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Não foi possível identificar as partes no texto da intimação.
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div id="search-results-container" style="display: none;"></div>
        </div>
<!-- Seção de Resultados da Busca removida -->

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger mb-4">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <h3><i class="fas fa-file-alt"></i> Dados do Processo</h3>
            <form id="addProcessForm" action="add_process2.php<?php
                // Preserve all GET parameters
                $get_params = $_GET;
                echo !empty($get_params) ? '?' . http_build_query($get_params) : '';
            ?>" method="POST" class="improved-form">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="id_cliente" class="form-label">
                                <i class="fas fa-user"></i> Cliente
                            </label>
                            <select id="id_cliente" name="id_cliente" required class="form-select">
                                <option value="">Selecione o cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id_cliente']; ?>" <?php echo ($selected_client_id == $cliente['id_cliente']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cliente['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="numero_processo" class="form-label">
                                <i class="fas fa-hashtag"></i> Número do Processo
                            </label>
                            <input type="text" id="numero_processo" name="numero_processo" required class="form-control" value="<?php echo htmlspecialchars($numero_processo); ?>">
                        </div>

                        <div class="form-group mb-3">
                            <label for="tribunal" class="form-label">
                                <i class="fas fa-university"></i> Tribunal
                            </label>
                            <input type="text" id="tribunal" name="tribunal" class="form-control" value="<?php echo htmlspecialchars($tribunal_get); ?>">
                        </div>

                        <div class="form-group mb-3">
                            <label for="status" class="form-label">
                                <i class="fas fa-info-circle"></i> Status
                            </label>
                            <input type="text" id="status" name="status" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="data_abertura" class="form-label">
                                <i class="fas fa-calendar-plus"></i> Data de Abertura
                            </label>
                            <input type="date" id="data_abertura" name="data_abertura" class="form-control">
                        </div>

                        <div class="form-group mb-3">
                            <label for="data_fechamento" class="form-label">
                                <i class="fas fa-calendar-check"></i> Data de Fechamento
                            </label>
                            <input type="date" id="data_fechamento" name="data_fechamento" class="form-control">
                        </div>

                        <div class="form-group mb-3">
                            <label for="descricao" class="form-label">
                                <i class="fas fa-align-left"></i> Descrição
                            </label>
                            <textarea id="descricao" name="descricao" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="status_externo" class="form-label">
                                <i class="fas fa-external-link-alt"></i> Status Externo
                            </label>
                            <textarea id="status_externo" name="status_externo" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="action-button">
                        <i class="fas fa-plus-circle"></i> Adicionar Processo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>

// Function to select a client from the search results
function selectClient(clientId, clientName) {
    // Find the client dropdown
    const clientSelect = document.getElementById('id_cliente');
    
    // Set the selected value
    if (clientSelect) {
        clientSelect.value = clientId;
        
        // Highlight the selected client in the list
        const clientItems = document.querySelectorAll('.client-item');
        clientItems.forEach(item => {
            if (parseInt(item.dataset.id) === clientId) {
                item.style.backgroundColor = '#d4edda';
                item.style.borderColor = '#c3e6cb';
                item.style.color = '#155724';
            } else {
                item.style.backgroundColor = '';
                item.style.borderColor = '';
                item.style.color = '';
            }
        });
        
        // Scroll to the form
        document.getElementById('addProcessForm').scrollIntoView({ behavior: 'smooth' });
        
        // Flash the select element to draw attention to it
        clientSelect.style.backgroundColor = '#d4edda';
        clientSelect.style.borderColor = '#c3e6cb';
        
        setTimeout(() => {
            clientSelect.style.backgroundColor = '';
            clientSelect.style.borderColor = '';
        }, 1500);
        
        // Show a success message
        const successMessage = document.createElement('div');
        successMessage.className = 'alert alert-success mt-2';
        successMessage.innerHTML = `<i class="fas fa-check-circle"></i> Cliente "${clientName}" selecionado com sucesso!`;
        
        // Insert the message after the select element
        clientSelect.parentNode.appendChild(successMessage);
        
        // Remove the message after 3 seconds
        setTimeout(() => {
            successMessage.remove();
        }, 3000);
    }
}

// Function to create a new client via AJAX
function createNewClient(clientName) {
    if (!clientName) return;
    
    // Show loading indicator
    const resultsContainer = document.getElementById('search-results-container');
    resultsContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Criando cliente...</div>';
    
    // Create form data
    const formData = new FormData();
    formData.append('busca_nome', clientName);
    formData.append('create_new_client', '1');
    
    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Add all current GET parameters to the request
    for (const [key, value] of urlParams.entries()) {
        if (key !== 'busca_nome') {
            formData.append(key, value);
        }
    }
    
    // Send AJAX request
    fetch('add_process2.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Reload the page to show the newly created client
        window.location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        resultsContainer.innerHTML = '<div class="alert alert-danger">Erro ao criar cliente. Tente novamente.</div>';
    });
}

// AJAX search for clients
document.addEventListener('DOMContentLoaded', function() {
    // Verifica se há um novo cliente para selecionar
    const urlParams = new URLSearchParams(window.location.search);
    const newClientId = urlParams.get('new_client_id');
    const newClientName = urlParams.get('new_client_name');
    
    if (newClientId) {
        // Seleciona o cliente no dropdown principal
        const mainSelect = document.getElementById('id_cliente');
        if (mainSelect) {
            mainSelect.value = newClientId;
            
            // Destaca o select para chamar atenção
            mainSelect.style.backgroundColor = '#d4edda';
            mainSelect.style.borderColor = '#c3e6cb';
            
            setTimeout(() => {
                mainSelect.style.backgroundColor = '';
                mainSelect.style.borderColor = '';
            }, 1500);
            
            // Mostra mensagem de sucesso
            const formGroup = mainSelect.closest('.form-group');
            if (formGroup) {
                const successMessage = document.createElement('div');
                successMessage.className = 'alert alert-success mt-2';
                successMessage.innerHTML = `<i class="fas fa-check-circle"></i> Cliente "${decodeURIComponent(newClientName)}" criado e selecionado com sucesso!`;
                formGroup.appendChild(successMessage);
                
                setTimeout(() => {
                    successMessage.remove();
                }, 3000);
            }
            
            // Também seleciona no dropdown de clientes existentes
            const clienteExistenteSelect = document.getElementById('cliente_existente');
            if (clienteExistenteSelect) {
                clienteExistenteSelect.value = newClientId;
                
                // Atualiza as variáveis globais
                clienteSelecionadoId = newClientId;
                clienteSelecionadoNome = decodeURIComponent(newClientName);
            }
        }
        
        // Remove os parâmetros da URL para evitar seleção repetida em recargas
        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('new_client_id');
        cleanUrl.searchParams.delete('new_client_name');
        window.history.replaceState({}, document.title, cleanUrl.toString());
    }
    
    const buscarBtn = document.getElementById('buscarClienteBtn');
    const buscarInput = document.getElementById('busca_nome');
    const resultsContainer = document.getElementById('search-results-container');
    
    if (buscarBtn && buscarInput && resultsContainer) {
        buscarBtn.addEventListener('click', function() {
            searchClients();
        });
        
        buscarInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchClients();
            }
        });
    }
    
    function searchClients() {
        const searchTerm = buscarInput.value.trim();
        
        if (searchTerm === '') {
            resultsContainer.innerHTML = '<div class="alert alert-warning">Digite um termo para buscar.</div>';
            return;
        }
        
        // Show loading indicator
        resultsContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
        
        // Get current URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        
        // Create form data
        const formData = new FormData();
        formData.append('busca_nome', searchTerm);
        formData.append('ajax_search', '1'); // Flag to indicate this is an AJAX search
        
        // Add all current GET parameters to the request
        for (const [key, value] of urlParams.entries()) {
            if (key !== 'busca_nome') {
                formData.append(key, value);
            }
        }
        
        // Send AJAX request
        fetch('add_process2.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Extract just the search results section from the response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const resultsSection = doc.querySelector('.form-section:nth-child(2)');
            
            if (resultsSection) {
                resultsContainer.innerHTML = resultsSection.innerHTML;
                
                // Re-attach event listeners to the client items
                const clientItems = resultsContainer.querySelectorAll('.client-item');
                clientItems.forEach(item => {
                    const clientId = parseInt(item.dataset.id);
                    const clientName = item.textContent.trim();
                    
                    item.addEventListener('click', function() {
                        selectClient(clientId, clientName);
                    });
                    
                    item.addEventListener('mouseover', function() {
                        this.style.backgroundColor = '#f8f9fa';
                    });
                    
                    item.addEventListener('mouseout', function() {
                        this.style.backgroundColor = '';
                    });
                });
            } else {
                resultsContainer.innerHTML = '<div class="alert alert-danger">Nenhum resultado encontrado.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultsContainer.innerHTML = '<div class="alert alert-danger">Erro ao buscar clientes. Tente novamente.</div>';
        });
    }
});

// Variáveis globais para controle
let clienteSelecionadoId = null;
let clienteSelecionadoNome = '';
let todosClientes = [];

// Inicializa a lista de clientes quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    // Captura todos os clientes do select para usar na busca
    const select = document.getElementById('cliente_existente');
    if (select) {
        for (let i = 1; i < select.options.length; i++) { // Começa do 1 para pular a opção vazia
            todosClientes.push({
                id: select.options[i].value,
                nome: select.options[i].text
            });
        }
    }
});

// Função para filtrar clientes conforme o usuário digita
function filtrarClientes(termo) {
    const resultadosDiv = document.getElementById('resultados_busca');
    const listaResultados = document.getElementById('lista_clientes_filtrados');
    
    // Limpa resultados anteriores
    listaResultados.innerHTML = '';
    
    if (!termo || termo.length < 2) {
        resultadosDiv.style.display = 'none';
        return;
    }
    
    // Filtra clientes que correspondem ao termo de busca
    const termoLower = termo.toLowerCase();
    const clientesFiltrados = todosClientes.filter(cliente =>
        cliente.nome.toLowerCase().includes(termoLower)
    );
    
    if (clientesFiltrados.length === 0) {
        // Nenhum cliente encontrado
        resultadosDiv.style.display = 'block';
        listaResultados.innerHTML = `
            <li class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Nenhum cliente encontrado</span>
                    <button type="button" class="btn btn-sm btn-success" onclick="prepararNovoCliente('${termo}')">
                        <i class="fas fa-plus-circle"></i> Criar
                    </button>
                </div>
            </li>
        `;
    } else {
        // Mostra os resultados
        resultadosDiv.style.display = 'block';
        
        clientesFiltrados.forEach(cliente => {
            const li = document.createElement('li');
            li.className = 'list-group-item client-item';
            li.style.cursor = 'pointer';
            li.innerHTML = `<i class="fas fa-user mr-2"></i> ${cliente.nome}`;
            li.onclick = function() {
                selecionarClientePorId(cliente.id, cliente.nome);
            };
            listaResultados.appendChild(li);
        });
    }
}

// Função para preparar a criação de um novo cliente
function prepararNovoCliente(nome) {
    document.getElementById('novo_cliente_nome').value = nome;
    document.getElementById('novo_cliente_nome').style.backgroundColor = '#d4edda';
    setTimeout(() => {
        document.getElementById('novo_cliente_nome').style.backgroundColor = '';
    }, 1500);
    
    // Fecha os resultados da busca
    document.getElementById('resultados_busca').style.display = 'none';
    
    // Foca no campo de novo cliente
    document.getElementById('novo_cliente_nome').focus();
}

// Função para selecionar um cliente pelo ID
function selecionarClientePorId(id, nome) {
    // Atualiza o select
    const select = document.getElementById('cliente_existente');
    select.value = id;
    
    // Atualiza as variáveis globais
    atualizarClienteSelecionado(id);
    
    // Fecha os resultados da busca
    document.getElementById('resultados_busca').style.display = 'none';
    
    // Atualiza o campo de busca
    document.getElementById('busca_cliente').value = nome;
    
    // Destaca o select
    select.style.backgroundColor = '#d4edda';
    setTimeout(() => {
        select.style.backgroundColor = '';
    }, 1500);
    
    // Mostra mensagem de sucesso
    const successMessage = document.createElement('div');
    successMessage.className = 'alert alert-success mt-2';
    successMessage.innerHTML = `<i class="fas fa-check-circle"></i> Cliente "${nome}" selecionado!`;
    
    // Remove mensagens anteriores
    const previousMessages = document.querySelectorAll('.alert-success');
    previousMessages.forEach(msg => msg.remove());
    
    // Adiciona a nova mensagem
    select.parentNode.appendChild(successMessage);
    
    // Remove a mensagem após 3 segundos
    setTimeout(() => {
        successMessage.remove();
    }, 3000);
}

// Função para selecionar uma parte identificada
function selecionarParte(nome) {
    // Preenche o campo de busca com o nome da parte
    document.getElementById('busca_cliente').value = nome;
    
    // Aciona a busca
    filtrarClientes(nome);
    
    // Também preenche o campo de novo cliente caso seja necessário criar
    document.getElementById('novo_cliente_nome').value = nome;
    
    // Tenta encontrar o cliente no dropdown
    const select = document.getElementById('cliente_existente');
    let encontrado = false;
    
    for (let i = 0; i < select.options.length; i++) {
        const option = select.options[i];
        if (option.text.toLowerCase().includes(nome.toLowerCase())) {
            select.selectedIndex = i;
            encontrado = true;
            atualizarClienteSelecionado(option.value);
            break;
        }
    }
    
    // Destaca o campo apropriado
    if (encontrado) {
        document.getElementById('cliente_existente').style.backgroundColor = '#d4edda';
        setTimeout(() => {
            document.getElementById('cliente_existente').style.backgroundColor = '';
        }, 1500);
    } else {
        document.getElementById('novo_cliente_nome').style.backgroundColor = '#d4edda';
        setTimeout(() => {
            document.getElementById('novo_cliente_nome').style.backgroundColor = '';
        }, 1500);
    }
}

// Função para atualizar o cliente selecionado
function atualizarClienteSelecionado(id) {
    if (!id) return;
    
    clienteSelecionadoId = id;
    const select = document.getElementById('cliente_existente');
    clienteSelecionadoNome = select.options[select.selectedIndex].text;
}

// Função para confirmar o cliente selecionado
function confirmarClienteSelecionado() {
    if (!clienteSelecionadoId) {
        alert('Por favor, selecione um cliente da lista.');
        return;
    }
    
    // Preenche o campo id_cliente no formulário
    document.getElementById('id_cliente').value = clienteSelecionadoId;
    
    // Mostra mensagem de sucesso
    const successMessage = document.createElement('div');
    successMessage.className = 'alert alert-success mt-2';
    successMessage.innerHTML = `<i class="fas fa-check-circle"></i> Cliente "${clienteSelecionadoNome}" selecionado com sucesso!`;
    
    // Insere a mensagem após o select
    const cardBody = document.getElementById('cliente_existente').closest('.card-body');
    cardBody.appendChild(successMessage);
    
    // Remove a mensagem após 3 segundos
    setTimeout(() => {
        successMessage.remove();
    }, 3000);
    
    // Destaca o cliente no formulário principal
    const clienteSelect = document.getElementById('id_cliente');
    clienteSelect.style.backgroundColor = '#d4edda';
    clienteSelect.style.borderColor = '#c3e6cb';
    
    setTimeout(() => {
        clienteSelect.style.backgroundColor = '';
        clienteSelect.style.borderColor = '';
    }, 1500);
    
    // Scroll para o formulário
    document.getElementById('addProcessForm').scrollIntoView({ behavior: 'smooth' });
}

// Função para criar um novo cliente
function criarNovoCliente() {
    const nome = document.getElementById('novo_cliente_nome').value.trim();
    
    if (!nome) {
        alert('Por favor, digite o nome do cliente.');
        return;
    }
    
    // Mostra indicador de carregamento
    const cardBody = document.getElementById('novo_cliente_nome').closest('.card-body');
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'text-center mt-2';
    loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando cliente...';
    cardBody.appendChild(loadingIndicator);
    
    // Cria form data
    const formData = new FormData();
    formData.append('busca_nome', nome);
    formData.append('create_new_client', '1');
    
    // Adiciona parâmetros GET atuais
    const urlParams = new URLSearchParams(window.location.search);
    for (const [key, value] of urlParams.entries()) {
        if (key !== 'busca_nome') {
            formData.append(key, value);
        }
    }
    
    // Envia requisição AJAX
    fetch('add_process2.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Extrai o ID do cliente recém-criado do HTML
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        try {
            // Tenta encontrar o ID do cliente recém-criado
            const match = html.match(/new_client_id=(\d+)/);
            if (match && match[1]) {
                const newClientId = match[1];
                
                // Adiciona o ID do cliente recém-criado como parâmetro na URL
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('new_client_id', newClientId);
                currentUrl.searchParams.set('new_client_name', encodeURIComponent(nome));
                
                // Redireciona para a mesma página com o novo parâmetro
                window.location.href = currentUrl.toString();
            } else {
                // Se não conseguir extrair o ID, apenas recarrega a página
                window.location.reload();
            }
        } catch (e) {
            console.error('Erro ao processar resposta:', e);
            // Em caso de erro, apenas recarrega a página
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        loadingIndicator.innerHTML = '<div class="alert alert-danger">Erro ao criar cliente. Tente novamente.</div>';
    });
}
</script>

<style>
.table-responsive {
    overflow-x: auto;
}
.badge {
    font-size: 85%;
    padding: 0.35em 0.65em;
    border-radius: 0.25rem;
}
.bg-success {
    background-color: #28a745!important;
    color: white;
}
.bg-warning {
    background-color: #ffc107!important;
}
.text-dark {
    color: #212529!important;
}
.table {
    width: 100%;
    margin-bottom: 1rem;
    color: #212529;
    border-collapse: collapse;
}
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,.05);
}
.table-bordered {
    border: 1px solid #dee2e6;
}
.table-bordered td, .table-bordered th {
    border: 1px solid #dee2e6;
}
.table-dark {
    color: #fff;
    background-color: #343a40;
}
.table th, .table td {
    padding: 0.75rem;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}
.card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    word-wrap: break-word;
    background-color: #fff;
    background-clip: border-box;
    border: 1px solid rgba(0,0,0,.125);
    border-radius: 0.25rem;
}
.card-header {
    padding: 0.75rem 1.25rem;
    margin-bottom: 0;
    background-color: rgba(0,0,0,.03);
    border-bottom: 1px solid rgba(0,0,0,.125);
}
.card-body {
    flex: 1 1 auto;
    padding: 1.25rem;
}
.bg-primary {
    background-color: #007bff!important;
}
.bg-success {
    background-color: #28a745!important;
}
.text-white {
    color: #fff!important;
}
.form-select {
    display: block;
    width: 100%;
    padding: 0.375rem 2.25rem 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    appearance: none;
}
.row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;
}
.g-3 {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 1rem;
}
.col-md-6 {
    flex: 0 0 auto;
    width: 50%;
    padding-right: 15px;
    padding-left: 15px;
}
</style>
