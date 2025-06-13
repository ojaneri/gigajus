<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
require 'config.php';
require 'includes/notifications_helper.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Inicializa variáveis de filtro
$where_clause = "";
// Data atual para os filtros de data
$current_date = date('Y-m-d');

$filters = [
    'classe' => isset($_GET['classe']) ? trim($_GET['classe']) : '',
    'termo' => isset($_GET['termo']) ? trim($_GET['termo']) : '',
    'tribunal' => isset($_GET['tribunal']) ? trim($_GET['tribunal']) : '',
    'data_inicio' => isset($_GET['data_inicio']) ? trim($_GET['data_inicio']) : $current_date,
    'data_fim' => isset($_GET['data_fim']) ? trim($_GET['data_fim']) : $current_date,
    'status' => isset($_GET['status']) ? trim($_GET['status']) : 'all',
    'advogado' => isset($_GET['advogado']) ? trim($_GET['advogado']) : ''
];

// Lista de UFs para o filtro
$ufs = [
    'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 
    'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 
    'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO'
];

// Lista de tribunais para o filtro
$tribunais = [
    'STF', 'STJ', 'TST', 'TSE', 'STM', 'TRF1', 'TRF2', 'TRF3', 'TRF4', 'TRF5',
    'TJAC', 'TJAL', 'TJAM', 'TJAP', 'TJBA', 'TJCE', 'TJDF', 'TJES', 'TJGO', 'TJMA',
    'TJMG', 'TJMS', 'TJMT', 'TJPA', 'TJPB', 'TJPE', 'TJPI', 'TJPR', 'TJRJ', 'TJRN',
    'TJRO', 'TJRR', 'TJRS', 'TJSC', 'TJSE', 'TJSP', 'TJTO'
];

// Verifica se o botão de busca foi clicado
$fetch_from_api = isset($_GET['fetch_api']) && $_GET['fetch_api'] == 1;

// As funções fetchNotificationsFromAPI e saveNotificationsToDatabase estão definidas em includes/notifications_helper.php

// Busca intimações da API e salva no banco se solicitado
$api_message = '';
$api_debug = null;

if ($fetch_from_api) {
    // Registra a tentativa de busca na API
    file_put_contents('api.log', date('[Y-m-d H:i:s] ') . "Iniciando busca na API com filtros: " . json_encode($filters) . PHP_EOL, FILE_APPEND);
    
    // Obtém os dados do advogado vinculado ao usuário
    $user_id = $_SESSION['user_id'];
    $advogado_data = null;
    
    // Busca a empresa do usuário
    $empresa_query = "SELECT id_empresa FROM usuario_empresa WHERE id_usuario = ?";
    $empresa_stmt = $conn->prepare($empresa_query);
    $empresa_stmt->bind_param("i", $user_id);
    $empresa_stmt->execute();
    $empresa_result = $empresa_stmt->get_result();
    
    if ($empresa_result->num_rows > 0) {
        $empresa_row = $empresa_result->fetch_assoc();
        $id_empresa = $empresa_row['id_empresa'];
        
        // Salva na sessão para uso futuro
        $_SESSION['company_id'] = $id_empresa;
        
        // Se não foi selecionado um advogado específico, pega o primeiro advogado da empresa
        if (empty($filters['advogado']) || $filters['advogado'] == 'all') {
            $advogado_query = "SELECT id_advogado, nome_advogado, oab_numero, oab_uf
                               FROM advogados
                               WHERE id_empresa = ?
                               ORDER BY nome_advogado
                              LIMIT 1";
            $advogado_stmt = $conn->prepare($advogado_query);
            $advogado_stmt->bind_param("i", $id_empresa);
            $advogado_stmt->execute();
            $advogado_result = $advogado_stmt->get_result();
            
            if ($advogado_result->num_rows > 0) {
                $advogado_data = $advogado_result->fetch_assoc();
                $filters['oab_numero'] = $advogado_data['oab_numero'];
                $filters['oab_uf'] = $advogado_data['oab_uf'];
                $filters['advogado_nome'] = $advogado_data['nome_advogado'];
            }
            $advogado_stmt->close();
        } else {
            // Busca os dados do advogado selecionado
            $advogado_id = $filters['advogado'];
            $advogado_query = "SELECT id_advogado, nome_advogado, oab_numero, oab_uf
                               FROM advogados
                               WHERE id_advogado = ? AND id_empresa = ?";
            $advogado_stmt = $conn->prepare($advogado_query);
            $advogado_stmt->bind_param("ii", $advogado_id, $id_empresa);
            $advogado_stmt->execute();
            $advogado_result = $advogado_stmt->get_result();
            
            if ($advogado_result->num_rows > 0) {
                $advogado_data = $advogado_result->fetch_assoc();
                $filters['oab_numero'] = $advogado_data['oab_numero'];
                $filters['oab_uf'] = $advogado_data['oab_uf'];
                $filters['advogado_nome'] = $advogado_data['nome_advogado'];
            }
            $advogado_stmt->close();
        }
    }
    $empresa_stmt->close();
    
    // Log dos filtros atualizados
    file_put_contents('api.log', date('[Y-m-d H:i:s] ') . "Filtros atualizados com dados do advogado: " . json_encode($filters) . PHP_EOL, FILE_APPEND);
    
    // Executa a busca na API apenas se tiver os dados do advogado
    if (!empty($filters['oab_numero']) && !empty($filters['oab_uf'])) {
        $api_notifications = fetchNotificationsFromAPI($filters);
    } else {
        $api_message = "Erro: Não foi possível obter os dados do advogado para consulta na API.";
        $api_notifications = ['error' => 'Dados do advogado não encontrados'];
    }
    
    // Verifica se houve erro
    if (is_array($api_notifications) && isset($api_notifications['error'])) {
        $error_details = $api_notifications['error'];
        $api_message = "Erro ao buscar intimações da API externa.";
        
        // Adiciona detalhes específicos ao erro para melhor diagnóstico
        if (isset($api_notifications['errno'])) {
            $curl_error_code = $api_notifications['errno'];
            switch ($curl_error_code) {
                case CURLE_OPERATION_TIMEDOUT:
                    $api_message .= " Timeout na conexão com o servidor.";
                    break;
                case CURLE_COULDNT_CONNECT:
                    $api_message .= " Não foi possível conectar ao servidor.";
                    break;
                case CURLE_COULDNT_RESOLVE_HOST:
                    $api_message .= " Não foi possível resolver o nome do host.";
                    break;
                case CURLE_SSL_CONNECT_ERROR:
                    $api_message .= " Erro na conexão SSL.";
                    break;
                case CURLE_PROXY_AUTHENTICATION_FAILED:
                    $api_message .= " Falha na autenticação do proxy.";
                    break;
                default:
                    if (strpos($error_details, "Server Error") !== false) {
                        $api_message .= " O servidor da API retornou um erro interno (500).";
                    } else {
                        $api_message .= " Erro: " . $error_details;
                    }
            }
        }
        
        if (isset($api_notifications['status_code'])) {
            $status_code = $api_notifications['status_code'];
            $api_message .= " (Status HTTP: $status_code)";
        }
        
        if (isset($api_notifications['execution_time'])) {
            $exec_time = round($api_notifications['execution_time'], 2);
            $api_message .= " Tempo de execução: {$exec_time}s.";
        }
        
        // Salva informações detalhadas para debug
        $api_debug = $api_notifications;
        
        // Verifica o status do servidor proxy
        $proxy_check = shell_exec("ping -c 1 " . PROXY_SOCKS_HOST);
        $api_debug['proxy_check'] = $proxy_check;
        
        // Verifica o status do servidor da API
        $api_check = shell_exec("ping -c 1 comunicaapi.pje.jus.br");
        $api_debug['api_check'] = $api_check;
        
        // Adiciona informações sobre o último log
        $last_logs = shell_exec("tail -n 20 api.log");
        $api_debug['last_logs'] = $last_logs;
        
        // Adiciona sugestão de solução
        $api_message .= "<br><br><strong>Possíveis soluções:</strong><br>";
        $api_message .= "1. Verifique se o servidor proxy está acessível<br>";
        $api_message .= "2. Verifique se as credenciais do proxy estão corretas<br>";
        $api_message .= "3. Verifique se a API está disponível<br>";
        $api_message .= "4. Tente novamente mais tarde<br>";
        
        // Log do erro para análise posterior
        file_put_contents('api.log', date('[Y-m-d H:i:s] ') . "ERRO DETALHADO: " . print_r($api_notifications, true) . PHP_EOL, FILE_APPEND);
    } else {
        // Log the API response for debugging
        file_put_contents('api.log', date('[Y-m-d H:i:s] ') . "API Response: " . print_r($api_notifications, true) . PHP_EOL, FILE_APPEND);
        
        // Ensure $api_notifications is an array
        if (!is_array($api_notifications)) {
            $api_message = "Erro: A API retornou um formato inválido.";
            $api_debug = ['error' => 'Invalid response format', 'response' => $api_notifications];
        } else {
            $saved_count = saveNotificationsToDatabase($conn, $api_notifications);
            $api_message = "Foram encontradas e salvas $saved_count novas intimações.";
        }
        
        if ($is_admin) {
            $api_debug = [
                'success' => true,
                'count' => count($api_notifications),
                'saved' => $saved_count,
                'sample' => array_slice($api_notifications, 0, 2) // Mostra apenas 2 exemplos para não sobrecarregar
            ];
        }
    }
}

// Obtém as notificações do banco de dados com base nos filtros
// Debug: Inicia registro de queries
$debug_queries = [];
$start_time = microtime(true);

// Configuração de paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20; // Número de itens por página
$offset = ($page - 1) * $per_page;

// Verifica se as colunas polo_ativo e polo_passivo existem na tabela processes
$check_columns_sql = "SHOW COLUMNS FROM processes WHERE Field IN ('polo_ativo', 'polo_passivo', 'parte_ativa', 'parte_passiva')";
$check_columns = $conn->query($check_columns_sql);
$debug_queries[] = [
    'sql' => $check_columns_sql,
    'rows' => $check_columns->num_rows
];

$columns = [];
while ($column = $check_columns->fetch_assoc()) {
    $columns[] = $column['Field'];
}
// Verifica se a coluna teor existe na tabela notifications
$check_teor_column = $conn->query("SHOW COLUMNS FROM notifications LIKE 'teor'");
if ($check_teor_column->num_rows == 0) {
    // A coluna não existe, vamos adicioná-la
    $add_column = $conn->query("ALTER TABLE notifications ADD COLUMN teor TEXT AFTER data_publicacao");
    if ($add_column) {
        error_log("Coluna 'teor' adicionada automaticamente à tabela notifications");
    } else {
        error_log("Erro ao adicionar coluna 'teor': " . $conn->error);
    }
}

// Verifica se a coluna processada existe na tabela notifications
$check_processada_column = $conn->query("SHOW COLUMNS FROM notifications LIKE 'processada'");
if ($check_processada_column->num_rows == 0) {
    // A coluna não existe, vamos adicioná-la
    $add_column = $conn->query("ALTER TABLE notifications ADD COLUMN processada TINYINT(1) DEFAULT 0 AFTER teor");
    if ($add_column) {
        error_log("Coluna 'processada' adicionada automaticamente à tabela notifications");
    } else {
        error_log("Erro ao adicionar coluna 'processada': " . $conn->error);
    }
}

// Constrói a query com base nas colunas existentes
if (in_array('polo_ativo', $columns) && in_array('polo_passivo', $columns)) {
    $query = "SELECT n.*, p.polo_ativo, p.polo_passivo FROM notifications n
              LEFT JOIN processes p ON n.processo_id = p.id
              WHERE 1=1";
} elseif (in_array('parte_ativa', $columns) && in_array('parte_passiva', $columns)) {
    $query = "SELECT n.*, p.parte_ativa AS polo_ativo, p.parte_passiva AS polo_passivo FROM notifications n
              LEFT JOIN processes p ON n.processo_id = p.id
              WHERE 1=1";
} else {
    $query = "SELECT n.* FROM notifications n WHERE 1=1";
}
$params = [];
$types = "";

// Verifica se o usuário está vinculado a uma empresa
// Nota: Removida a condição de company_id pois a coluna não existe na tabela processes
if (isset($_SESSION['company_id'])) {
    // Apenas registra o ID da empresa na sessão para uso em outras partes do código
    $user_company_id = $_SESSION['company_id'];
}

if (!empty($filters['classe'])) {
    $query .= " AND n.classe LIKE ?";
    $param_classe = "%" . $filters['classe'] . "%";
    $params[] = $param_classe;
    $types .= "s";
}

if (!empty($filters['termo'])) {
    $query .= " AND (n.numero_processo LIKE ? OR n.advogados LIKE ?)";
    $param_termo = "%" . $filters['termo'] . "%";
    $params[] = $param_termo;
    $params[] = $param_termo;
    $types .= "ss";
}

if (!empty($filters['tribunal'])) {
    $query .= " AND n.tribunal = ?";
    $params[] = $filters['tribunal'];
    $types .= "s";
}

// Adiciona filtros de data se existirem
if (!empty($filters['data_inicio'])) {
    $query .= " AND n.data_publicacao >= ?";
    $params[] = $filters['data_inicio'];
    $types .= "s";
}

if (!empty($filters['data_fim'])) {
    $query .= " AND n.data_publicacao <= ?";
    $params[] = $filters['data_fim'];
    $types .= "s";
}

// Adiciona filtro de status (processada/não processada)
if ($filters['status'] === 'processed') {
    $query .= " AND n.processada = 1";
} elseif ($filters['status'] === 'pending') {
    $query .= " AND n.processada = 0";
}
// Filtro de advogados cadastrados
if (!empty($filters['advogado']) && $filters['advogado'] !== 'all') {
    $advogado_id = $filters['advogado'];
    
    // Verifica se a sessão tem id_empresa
    if (isset($_SESSION['company_id'])) {
        $company_id = $_SESSION['company_id'];
        $advogado_name_query = "SELECT nome_advogado, oab_numero, oab_uf FROM advogados WHERE id_advogado = ? AND id_empresa = ?";
        $advogado_name_stmt = $conn->prepare($advogado_name_query);
        $advogado_name_stmt->bind_param("ii", $advogado_id, $company_id);
        $advogado_name_stmt->execute();
        $advogado_name_result = $advogado_name_stmt->get_result();
        
        if ($advogado_name_result && $advogado_name_result->num_rows > 0) {
            $advogado_data = $advogado_name_result->fetch_assoc();
            $advogado_name = $advogado_data['nome_advogado'];
            $query .= " AND n.advogados LIKE ?";
            $param_advogado = "%" . $advogado_name . "%";
            $params[] = $param_advogado;
            $types .= "s";
        }
        $advogado_name_stmt->close();
    }
}

$query .= $where_clause;
$query .= " ORDER BY n.created_at DESC";

// Primeiro, executa a query de contagem para obter o total de registros
$count_query = str_replace("SELECT n.*", "SELECT COUNT(*) as total", $query);
$count_query = preg_replace('/SELECT.*?FROM/s', "SELECT COUNT(*) as total FROM", $count_query);

$count_stmt = $conn->prepare($count_query);
if (!empty($types)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $per_page);
$count_stmt->close();
$count_result->close();

// Depois, adiciona LIMIT e OFFSET para paginação e executa a query principal
$query .= " LIMIT ? OFFSET ?";
// Cria uma cópia dos parâmetros originais para a query principal
$main_params = $params;
$main_types = $types;

// Adiciona os parâmetros de paginação
$main_types .= "ii";
$main_params[] = $per_page;
$main_params[] = $offset;

// Prepara e executa a query principal
$stmt = $conn->prepare($query);
if (!empty($main_types)) {
    $stmt->bind_param($main_types, ...$main_params);
}
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];

while ($row = $result->fetch_assoc()) {
    // Formata a data para exibição
    $row['date'] = date('d/m/Y H:i', strtotime($row['created_at']));
    
    // Adiciona a mensagem formatada
    $row['message'] = "Processo {$row['numero_processo']} ({$row['classe']}) - {$row['tribunal']}";
    if (!empty($row['data_publicacao'])) {
        $row['message'] .= " - Publicado em " . date('d/m/Y', strtotime($row['data_publicacao']));
    }
    
    // Marca que a notificação está no banco de dados
    $row['in_database'] = true;
    
    // Usamos o teor já carregado na consulta principal
    if (!isset($row['teor'])) {
        $row['teor'] = '';
    }

    // Extrai partes ativa e passiva
    $parte_ativa = '';
    $parte_passiva = '';
    
    // Primeiro procura nos headers
    if (preg_match('/\bPOLO\s+ATIVO\s*:?\s*(.*?)(?:\bPOLO\s+PASSIVO|$)/is', $row['teor'], $matches_ativo) &&
        preg_match('/\bPOLO\s+PASSIVO\s*:?\s*(.*?)(?:\n\n|\r\n\r\n|$)/is', $row['teor'], $matches_passivo)) {
        $parte_ativa = trim($matches_ativo[1]);
        $parte_passiva = trim($matches_passivo[1]);
    }
    // Se não achar, procura como REQUERENTE ou REQUERIDO
    elseif (preg_match('/REQUERENTE\(S\):\s*(.*?)\s*REQUERIDO\(A\)\(S\):\s*(.*?)(?=\s*Vistos,|\s*\n\n|\s*$)/is', $row['teor'], $matches)) {
        $parte_ativa = trim($matches[1]);
        $parte_passiva = trim($matches[2]);
    }
    // Se não achar, procura como AUTOR: XXX REU:YYY
    elseif (preg_match('/\bAUTOR\s*:?\s*(.*?)(?:\bR[EÉ]U\s*:|$)/is', $row['teor'], $matches_autor) &&
            preg_match('/\bR[EÉ]U\s*:?\s*(.*?)(?:\n\n|\r\n\r\n|$)/is', $row['teor'], $matches_reu)) {
        $parte_ativa = trim($matches_autor[1]);
        $parte_passiva = trim($matches_reu[1]);
    }
    // Se não achar, procura como PROMOVENTE(S): e PROMOVIDO(A)
    elseif (preg_match('/PROMOVENTE\(S\):\s*(.*?)\s*PROMOVIDO\(A\):\s*(.*?)(?=\s*Vistos,|\s*\n\n|\s*$)/is', $row['teor'], $matches)) {
        $parte_ativa = trim($matches[1]);
        $parte_passiva = trim($matches[2]);
    }
    // Se não achar, procura como APELADO ou APELANTE
    elseif (preg_match('/\bAPELANTE\s*:?\s*(.*?)(?:\bAPELADO\s*:|$)/is', $row['teor'], $matches_apelante) &&
            preg_match('/\bAPELADO\s*:?\s*(.*?)(?:\n\n|\r\n\r\n|$)/is', $row['teor'], $matches_apelado)) {
        $parte_ativa = trim($matches_apelante[1]);
        $parte_passiva = trim($matches_apelado[1]);
    }
    // Se não achar, procura como AGRAVANTE e AGRAVADO
    elseif (preg_match('/\bAGRAVANTE\s*:?\s*(.*?)(?:\bAGRAVADO\s*:|$)/is', $row['teor'], $matches_agravante) &&
            preg_match('/\bAGRAVADO\s*:?\s*(.*?)(?:\n\n|\r\n\r\n|$)/is', $row['teor'], $matches_agravado)) {
        $parte_ativa = trim($matches_agravante[1]);
        $parte_passiva = trim($matches_agravado[1]);
    }
    
    // Remove linhas extras e espaços em branco
    $parte_ativa = preg_replace('/\s+/', ' ', $parte_ativa);
    $parte_passiva = preg_replace('/\s+/', ' ', $parte_passiva);
    
    $row['parte_ativa'] = $parte_ativa;
    $row['parte_passiva'] = $parte_passiva;

    // Extrai o número do processo CNJ usando regex no teor ou mensagem
    $numero_cnj = '';
    $regex_cnj = '/\d{7}-\d{2}\.\d{4}\.\d\.\d{4}/';
    $regex_cnj_unformatted = '/\d{20}/';

    if (preg_match($regex_cnj, $row['teor'], $matches)) {
        $numero_cnj = $matches[0];
    } elseif (preg_match($regex_cnj, $row['message'], $matches)) {
        $numero_cnj = $matches[0];
    } elseif (isset($row['numero_processo']) && preg_match($regex_cnj, $row['numero_processo'], $matches)) {
        $row['add_process_link'] = 'add_process2.php?numero_processo=' . urlencode($matches[0]) . '&id_notificacao=' . $row['id'];
        $numero_cnj = $matches[0];
        $row['numero_processo'] = $numero_cnj; // Ensure formatted CNJ number
    } else {
        // Se não encontrar no formato CNJ, procura por 20 dígitos seguidos
        if (preg_match($regex_cnj_unformatted, $row['teor'], $matches)) {
            $numero_cnj = $matches[0];
        } elseif (preg_match($regex_cnj_unformatted, $row['message'], $matches)) {
            $numero_cnj = $matches[0];
        } elseif (isset($row['numero_processo']) && preg_match($regex_cnj_unformatted, $row['numero_processo'], $matches)) {
            $numero_cnj = $matches[0];
        }
    }
    $row['numero_cnj'] = $numero_cnj;

    // Verifica se o número do processo existe na tabela processos
    $row['processo_existe'] = false;
    $row['processo_tooltip'] = '';
    if (!empty($numero_cnj)) {
        $query_proc = $conn->prepare("SELECT id_processo FROM processos WHERE numero_processo = ? OR REPLACE(REPLACE(REPLACE(REPLACE(numero_processo, '-', ''), '.', ''), '/', ''), ' ', '') = ?");
        $numero_cnj_unformatted_val = preg_replace('/\D/', '', $numero_cnj);
        $query_proc->bind_param("ss", $numero_cnj, $numero_cnj_unformatted_val);
        $query_proc->execute();
        $query_proc->store_result();
        if ($query_proc->num_rows > 0) {
            $row['processo_existe'] = true;
            $row['processo_tooltip'] = 'Processo cadastrado no banco de dados';
        } else {
            $row['processo_existe'] = false;
            $row['processo_tooltip'] = 'Processo não cadastrado no banco de dados';
        }
        $query_proc->close();
    }
    
    // Add link with both process number and notification ID
    $row['add_process_link'] = 'add_process2.php?numero_processo=' . urlencode($row['numero_processo']) . '&id_notificacao=' . $row['id'];

    // Add link with notification ID
    $row['add_process_link'] = 'add_process2.php?numero_processo=' . urlencode($row['numero_processo']) . '&id_notificacao=' . $row['id'];
    $notifications[] = $row;
}

// Update the link in the HTML section
foreach ($notifications as &$notification) {
    $params = [
        'numero_processo' => urlencode($notification['numero_processo']),
        'id_notificacao' => $notification['id']
    ];
    
    // Adiciona parte ativa e passiva aos parâmetros se existirem
    if (!empty($notification['parte_ativa'])) {
        $params['nome_parte'] = urlencode($notification['parte_ativa']);
    }
    
    if (!empty($notification['parte_passiva'])) {
        $params['nome_parte_passiva'] = urlencode($notification['parte_passiva']);
    }
    
    // Constrói a URL com todos os parâmetros
    $notification['add_link'] = 'add_process2.php?' . http_build_query($params);
}
$stmt->close();

// Obtém a lista de advogados vinculados ao usuário através da empresa
$advogados = [];
$user_id = $_SESSION['user_id'];

// Debug: Verificar user_id
error_log("Buscando advogados para user_id: $user_id");

// Busca a empresa do usuário através da tabela usuario_empresa
$empresa_query = "SELECT id_empresa FROM usuario_empresa WHERE id_usuario = ?";
$empresa_stmt = $conn->prepare($empresa_query);
$empresa_stmt->bind_param("i", $user_id);
$empresa_stmt->execute();
$empresa_result = $empresa_stmt->get_result();

if ($empresa_result->num_rows > 0) {
    $empresa_row = $empresa_result->fetch_assoc();
    $id_empresa = $empresa_row['id_empresa'];
    
    // Salva na sessão para uso futuro se ainda não estiver definido
    if (!isset($_SESSION['company_id'])) {
        $_SESSION['company_id'] = $id_empresa;
    }
    
    $advogados_query = "SELECT a.id_advogado, a.nome_advogado, a.oab_numero, a.oab_uf
                       FROM advogados a
                       WHERE a.id_empresa = ?
                       ORDER BY a.nome_advogado";
    
    if ($advogados_stmt = $conn->prepare($advogados_query)) {
        $advogados_stmt->bind_param("i", $id_empresa);
        
        if ($advogados_stmt->execute()) {
            $advogados_result = $advogados_stmt->get_result();
            
            if ($advogados_result->num_rows > 0) {
                while ($advogado = $advogados_result->fetch_assoc()) {
                    $advogados[] = $advogado;
                }
            } else {
                error_log("Nenhum advogado encontrado para a empresa ID: $id_empresa");
            }
        } else {
            error_log("Erro na execução da query: " . $advogados_stmt->error);
        }
        
        $advogados_stmt->close();
    } else {
        error_log("Erro ao preparar query: " . $conn->error);
    }
    
    $empresa_stmt->close();
} else {
    error_log("Nenhuma empresa vinculada ao usuário ID: $user_id");
}

// Obtém a lista de usuários para o formulário de criação de tarefas
$users_query = "SELECT id_usuario as id, nome as name FROM usuarios WHERE ativo = 1 ORDER BY nome";
$users_result = $conn->query($users_query);
$users = [];

if ($users_result) {
    while ($user = $users_result->fetch_assoc()) {
        $users[] = $user;
    }
}
?>

    <style>
        .filters-container {
            background-color: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .filter-item {
            margin-bottom: 10px;
        }
        
        .filter-item label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .filter-item select,
        .filter-item input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }
        
        .filter-buttons button {
            padding: 8px 15px;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .btn-fetch-api {
            background-color: #28a745;
            color: white;
            margin-left: auto;
        }
        
        .notification-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .notification-table th,
        .notification-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .notification-table th {
            background-color: #f2f2f2;
        }
        
        .notification-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .db-badge {
            background-color: #e0f7fa;
            color: #0288d1;
        }
        
        .db-icon {
            color: #28a745;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .fa-database {
            font-size: 14px;
            vertical-align: middle;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #ffecb3;
            color: #856404;
        }
        
        .status-processed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .action-button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .view-button {
            background-color: #007bff;
            color: white;
        }
        
        /* Modal styles */
        
        .api-message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
        }
    </style>
    
    <style>
        /* Estilos para a paginação */
        .pagination-container {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .page-link {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 4px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
            background-color: #fff;
        }
        
        .page-link:hover {
            background-color: #f5f5f5;
        }
        
        .page-link.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .pagination-info {
            color: #666;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <div class="filters-container">
        <form method="GET" action="">
            <h3>Filtros</h3>
            <?php if(!empty($api_message)): ?>
                <div class="api-message"><?= $api_message ?></div>
            <?php endif; ?>
            <div class="filters-grid">
                <div class="filter-item">
                    <label>Advogado:</label>
                    <select name="advogado">
                        <option value="all">Todos</option>
                        <?php foreach ($advogados as $adv) { ?>
                            <option value="<?= $adv['id_advogado'] ?>" <?= $filters['advogado'] == $adv['id_advogado'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($adv['nome_advogado']) ?> - <?= htmlspecialchars($adv['oab_numero']) ?>/<?= htmlspecialchars($adv['oab_uf']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <script>
                document.querySelectorAll('.show-more').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        this.nextElementSibling.style.display = 'block';
                        this.remove();
                    });
                });
                </script>
                
                <div class="filter-item">
                    <label>Classe:</label>
                    <input type="text" name="classe" value="<?= htmlspecialchars($filters['classe']) ?>">
                </div>
                
                <div class="filter-item">
                    <label>Termo:</label>
                    <input type="text" name="termo" value="<?= htmlspecialchars($filters['termo']) ?>">
                </div>
                
                <div class="filter-item">
                    <label>Tribunal:</label>
                    <select name="tribunal">
                        <option value="">Todos</option>
                        <?php foreach ($tribunais as $trib): ?>
                            <option <?= $filters['tribunal'] === $trib ? 'selected' : '' ?>><?= $trib ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label>Data Início:</label>
                    <input type="date" name="data_inicio" value="<?= htmlspecialchars($filters['data_inicio']) ?>">
                </div>
                
                <div class="filter-item">
                    <label>Data Fim:</label>
                    <input type="date" name="data_fim" value="<?= htmlspecialchars($filters['data_fim']) ?>">
                </div>
                
                <div class="filter-item">
                    <label>Status:</label>
                    <select name="status">
                        <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>Todos</option>
                        <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pendentes</option>
                        <option value="processed" <?= $filters['status'] === 'processed' ? 'selected' : '' ?>>Processados</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-buttons">
                <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='notifications.php'">Limpar Filtros</button>
                <button type="submit" name="fetch_api" value="1" class="btn-fetch-api">Obter Intimações</button>
            </div>
        </form>
    </div>
    
    <!-- Painel de debug flutuante -->
    <div id="debugPanel" style="display: none; position: fixed; bottom: 0; left: 0; right: 0; background-color: #f8f9fa; border-top: 3px solid #007bff; padding: 15px; z-index: 999; max-height: 50vh; overflow-y: auto; box-shadow: 0 -2px 10px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <h3 style="margin: 0;">Informações de Debug</h3>
            <button id="closeDebugPanel" class="btn btn-sm btn-danger">&times;</button>
        </div>
        
        <div class="debug-content">
            <h4>URL da API Construída:</h4>
            <pre style="background-color: #272822; color: #f8f8f2; padding: 10px; border-radius: 4px; overflow: auto;"><?php
                $debug_api_params = [];
                // Log inicial
                file_put_contents('gigajus.log', "\n\n[".date('Y-m-d H:i:s')."] Iniciando processamento - Notifications\n", FILE_APPEND);
                
                // Adiciona o parâmetro meio (D = Diário)
                $debug_api_params['meio'] = 'D';
                
                // Adiciona parâmetros de paginação
                $debug_api_params['pagina'] = 1;
                $debug_api_params['tamanhoPagina'] = 1000;
                
                if (!empty($filters['oab_numero'])) $debug_api_params['numeroOab'] = $filters['oab_numero'];
                if (!empty($filters['oab_uf'])) $debug_api_params['ufOab'] = $filters['oab_uf'];
                if (!empty($filters['tribunal'])) $debug_api_params['siglaTribunal'] = $filters['tribunal'];
                if (!empty($filters['classe'])) $debug_api_params['classe'] = $filters['classe'];
                if (!empty($filters['termo'])) $debug_api_params['texto'] = $filters['termo'];
                
                // Adiciona filtros de data com os novos nomes de parâmetros
                if (!empty($filters['data_inicio'])) {
                    $debug_api_params['dataDisponibilizacaoInicio'] = $filters['data_inicio'];
                } else {
                    $debug_api_params['dataDisponibilizacaoInicio'] = date('Y-m-d');
                }
                
                if (!empty($filters['data_fim'])) {
                    $debug_api_params['dataDisponibilizacaoFim'] = $filters['data_fim'];
                } else {
                    $debug_api_params['dataDisponibilizacaoFim'] = date('Y-m-d');
                }
                
                $debug_url_params = http_build_query($debug_api_params);
                $debug_api_url = "https://comunicaapi.pje.jus.br/api/v1/comunicacao?" . $debug_url_params;
                echo htmlspecialchars($debug_api_url);
            ?></pre>
            
            <h4>Parâmetros da Requisição:</h4>
            <pre style="background-color: #272822; color: #f8f8f2; padding: 10px; border-radius: 4px; overflow: auto;"><?php echo htmlspecialchars(json_encode($filters, JSON_PRETTY_PRINT)); ?></pre>
            
            <?php if (file_exists('api.log')): ?>
                <h4>Últimas entradas do log:</h4>
                <pre style="background-color: #272822; color: #f8f8f2; padding: 10px; border-radius: 4px; overflow: auto; max-height: 200px;"><?php
                    $log_content = file_get_contents('api.log');
                    $log_lines = explode(PHP_EOL, $log_content);
                    $last_lines = array_slice($log_lines, -20); // Últimas 20 linhas
                    echo htmlspecialchars(implode(PHP_EOL, $last_lines));
                ?></pre>
            <?php endif; ?>
            
            <?php if (!empty($api_debug)): ?>
                <h4>Resposta da API:</h4>
                <pre style="background-color: #272822; color: #f8f8f2; padding: 10px; border-radius: 4px; overflow: auto;"><?php echo htmlspecialchars(json_encode($api_debug, JSON_PRETTY_PRINT)); ?></pre>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <h1>Intimações Judiciais</h1>
        
        <?php if (!empty($api_message)): ?>
            <div class="api-message">
                <?php echo htmlspecialchars($api_message); ?>
            </div>
        <?php endif; ?>
        

        <?php if ($is_admin && !empty($api_debug)): ?>
            <div class="debug-panel">
                <h3>Informações de Debug (Apenas Admin)</h3>
                <pre><?php echo htmlspecialchars(json_encode($api_debug, JSON_PRETTY_PRINT)); ?></pre>
                
                <?php if (file_exists('api.log')): ?>
                    <h4>Últimas entradas do log:</h4>
                    <pre><?php
                        $log_content = file_get_contents('api.log');
                        $log_lines = explode(PHP_EOL, $log_content);
                        $last_lines = array_slice($log_lines, -20); // Últimas 20 linhas
                        echo htmlspecialchars(implode(PHP_EOL, $last_lines));
                    ?></pre>
                <?php endif; ?>
                
                <h4>Teste com URLs específicas:</h4>
                <p>
                    <a href="https://janeri.com.br/gigajus/v2/notifications.php?classe=&termo=&tribunal=TJCE&oab_numero=25695&oab_uf=CE&data_inicio=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&data_fim=<?php echo date('Y-m-d'); ?>&fetch_api=1"
                       target="_blank" class="btn btn-info">
                        Testar URL com parâmetros específicos (OAB 25695/CE)
                    </a>
                    <br><br>
                    <a href="https://janeri.com.br/gigajus/v2/notifications.php?classe=&termo=&tribunal=&oab_numero=&oab_uf=&data_inicio=2025-04-08&data_fim=2025-04-08&fetch_api=1"
                       target="_blank" class="btn btn-info">
                        Testar URL com data específica
                    </a>
                </p>
            </div>
        <?php endif; ?>
        
        
        <table class="improved-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Mensagem</th>
                    <th>Nº Processo CNJ</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notifications)): ?>
                    <tr>
                        <td colspan="4">Nenhuma intimação encontrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($notification['date']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($notification['message']); ?>
                                
                                <?php if (isset($notification['processada']) && $notification['processada'] == 1): ?>
                                    <span class="status-badge status-processed">PROCESSADA</span>
                                <?php endif; ?>
                                
                                <?php if (isset($notification['in_database']) && $notification['in_database']): ?>
                                    <span class="status-badge db-badge">DB</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($notification['numero_cnj'])): ?>
                                    <span
                                        style="color: <?= $notification['processo_existe'] ? '#28a745' : '#dc3545' ?>; font-weight: bold; cursor: pointer;"
                                        title="<?= htmlspecialchars($notification['processo_tooltip']) ?>"
                                    >
                                        <?= htmlspecialchars($notification['numero_cnj']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="action-button view-button" title="Visualizar notificação" onclick="window.location.href='view_notification.php?id=<?php echo htmlspecialchars($notification['id']); ?>'">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if ($is_admin): ?>
                                    <button class="action-button" onclick="window.location.href='process_notification.php?id=<?php echo htmlspecialchars($notification['id']); ?>'">
                                        Processar
                                    </button>
                                <?php endif; ?>
                                <?php if (!empty($notification['numero_cnj']) && !$notification['processo_existe']): ?>
<?php
// Lógica para extrair nome_parte (AUTOR) se polo_ativo/parte_ativa estiverem vazios
$nome_parte_btn = '';
if (!empty($notification['polo_ativo'])) {
    $nome_parte_btn = $notification['polo_ativo'];
} elseif (!empty($notification['parte_ativa'])) {
    $nome_parte_btn = $notification['parte_ativa'];
} else {
    // Tenta extrair do texto da intimação
    $teor_text = isset($notification['teor']) ? $notification['teor'] : (isset($notification['message']) ? $notification['message'] : '');
    if (preg_match('/AUTOR:\s*([^\n\r]+?)\s+REU:/i', $teor_text, $matches)) {
        $nome_parte_btn = trim($matches[1]);
    }
}
?>
                                    <button class="action-button" style="background-color: #ffc107; color: #212529; margin-left: 5px;"
                                        title="Adicionar processo"
                                        onclick="window.location.href='add_process2.php?numero_processo=<?= urlencode($notification['numero_cnj']) ?>&nome_parte=<?= urlencode($nome_parte_btn) ?>&tribunal=<?= urlencode(isset($notification['tribunal']) ? $notification['tribunal'] : '') ?>'">
                                        <i class="fas fa-plus-circle"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginação -->
    <?php if (!empty($notifications)): ?>
    <div class="pagination-container">
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="page-link">&laquo; Primeira</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">&lsaquo; Anterior</a>
            <?php endif; ?>
            
            <?php
            // Determina o intervalo de páginas a mostrar
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            // Garante que pelo menos 5 páginas sejam mostradas se disponíveis
            if ($end_page - $start_page + 1 < 5) {
                if ($start_page == 1) {
                    $end_page = min($total_pages, $start_page + 4);
                } elseif ($end_page == $total_pages) {
                    $start_page = max(1, $end_page - 4);
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">Próxima &rsaquo;</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="page-link">Última &raquo;</a>
            <?php endif; ?>
        </div>
        <div class="pagination-info">
            Mostrando <?php echo count($notifications); ?> de <?php echo $total_records; ?> registros
            (Página <?php echo $page; ?> de <?php echo $total_pages; ?>)
        </div>
    </div>
    <?php endif; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function() {
            // Inicializa o painel de debug
            var debugPanel = $('#debugPanel');
            var closeDebugPanelButton = $('#closeDebugPanel');
            
            // Adiciona botão de toggle ao cabeçalho
            $('h1').after('<button id="toggleDebugPanel" class="btn btn-sm btn-info" style="margin-bottom: 15px;">Mostrar Debug</button>');
            var toggleDebugPanelButton = $('#toggleDebugPanel');
            
            // Função para mostrar/esconder o painel de debug
            toggleDebugPanelButton.click(function() {
                debugPanel.slideToggle();
            });
            
            // Função para fechar o painel de debug
            closeDebugPanelButton.click(function() {
                debugPanel.slideUp();
            });
        });
    </script>
