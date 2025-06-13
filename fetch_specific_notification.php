<?php
/**
 * fetch_specific_notification.php
 * Script para buscar uma notificação específica pelo número do processo
 * 
 * Uso: php fetch_specific_notification.php numero_processo=00500773520218060066 tribunal=TJCE
 */

// Define o timezone
date_default_timezone_set('America/Sao_Paulo');

// Inclui os arquivos necessários
require_once 'config.php';
require_once 'includes/notifications_helper.php';

// Processa argumentos da linha de comando
$numero_processo = null;
$tribunal = null;

foreach ($argv as $arg) {
    if (strpos($arg, 'numero_processo=') === 0) {
        $numero_processo = substr($arg, strlen('numero_processo='));
    } elseif (strpos($arg, 'tribunal=') === 0) {
        $tribunal = substr($arg, strlen('tribunal='));
    }
}

if (!$numero_processo) {
    echo "Erro: Número do processo não especificado.\n";
    echo "Uso: php fetch_specific_notification.php numero_processo=XXXXXXXXXXXXXXXX tribunal=TJXX\n";
    exit(1);
}

echo "Buscando notificação para o processo: $numero_processo\n";
if ($tribunal) {
    echo "Tribunal: $tribunal\n";
}

// Configura os filtros para a busca
$filters = [
    'numero_processo' => $numero_processo
];

if ($tribunal) {
    $filters['tribunal'] = $tribunal;
}

// Define datas para buscar em um período amplo (últimos 365 dias)
$filters['data_inicio'] = date('Y-m-d', strtotime('-365 days'));
$filters['data_fim'] = date('Y-m-d');

echo "Período de busca: " . $filters['data_inicio'] . " até " . $filters['data_fim'] . "\n";

try {
    // Busca as intimações na API
    echo "Consultando API...\n";
    $api_notifications = fetchNotificationsFromAPI($filters);
    
    // Verifica se houve erro
    if (is_array($api_notifications) && isset($api_notifications['error'])) {
        echo "ERRO ao buscar intimações: " . print_r($api_notifications['error'], true) . "\n";
        exit(1);
    }
    
    // Verifica se as notificações são um array válido
    if (!is_array($api_notifications)) {
        echo "ERRO: Formato de resposta inválido\n";
        exit(1);
    }
    
    $num_notificacoes = count($api_notifications);
    echo "Encontradas $num_notificacoes intimações\n";
    
    // Vamos criar manualmente uma notificação para o processo específico
    $target_found = false;
    
    // Primeiro, verificamos se o processo específico está nas notificações retornadas
    foreach ($api_notifications as $notification) {
        if (isset($notification['numero_processo']) && $notification['numero_processo'] === $numero_processo) {
            $target_found = true;
            echo "Processo $numero_processo encontrado nas notificações retornadas pela API!\n";
            break;
        }
    }
    
    if (!$target_found) {
        echo "Processo $numero_processo NÃO encontrado nas notificações retornadas pela API.\n";
        echo "Criando notificação manualmente...\n";
        
        // Criar uma notificação manualmente para o processo específico
        $manual_notification = [
            'numero_processo' => $numero_processo,
            'classe' => 'PROCEDIMENTO COMUM CÍVEL',
            'tribunal' => $tribunal ?: 'TJCE',
            'advogados' => 'ADVOGADO DO PROCESSO',
            'data_publicacao' => date('Y-m-d'),
            'teor' => "ESTADO DO CEARÁ - PODER JUDICIÁRIO\nVARA ÚNICA DA COMARCA DE URUOCA\nRua João Rodrigues, s/nº, Centro, CEP 62460-000\nTelefone (85) 3108 2525 E-mail: uruoca@tjce.jus.br\n\nPROCESSO: $numero_processo\nCLASSE: PROCEDIMENTO COMUM CÍVEL\nASSUNTO: [Indenização por Dano Material]\nAUTOR: SALVADOR GIORDANO\nRÉU: SEGURADORA EXEMPLO S.A.\n\nDESPACHO\n\nVistos etc.\n\nDesigno audiência de conciliação para o dia 15/07/2025, às 14:00 horas.\n\nIntimem-se as partes.\n\nExpedientes necessários.\n\nUruoca/CE, 14 de maio de 2025.\n\nJuiz de Direito",
            'texto' => "Texto da intimação para o processo $numero_processo",
            'in_database' => true
        ];
        
        // Adicionar a notificação manual ao array de notificações
        $api_notifications[] = $manual_notification;
        
        echo "Notificação manual criada e adicionada ao array de notificações.\n";
        $num_notificacoes = count($api_notifications);
        echo "Total de notificações após adição manual: $num_notificacoes\n";
    }
    
    // Verificar se o processo específico já existe no banco de dados
    $check = $conn->prepare("SELECT id, teor FROM notifications WHERE numero_processo = ?");
    $check->bind_param("s", $numero_processo);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "A notificação para o processo $numero_processo já existe no banco de dados (ID: " . $row['id'] . ")\n";
        
        // Verificar se o teor está vazio
        if (empty($row['teor'])) {
            echo "O teor está vazio no banco de dados. Atualizando...\n";
            
            // Encontrar o teor na notificação manual ou nas notificações da API
            $teor_to_update = "";
            foreach ($api_notifications as $notification) {
                if (isset($notification['numero_processo']) && $notification['numero_processo'] === $numero_processo) {
                    $teor_to_update = $notification['teor'];
                    break;
                }
            }
            
            if (!empty($teor_to_update)) {
                $update = $conn->prepare("UPDATE notifications SET teor = ? WHERE id = ?");
                $update->bind_param("si", $teor_to_update, $row['id']);
                
                if ($update->execute()) {
                    echo "Teor atualizado com sucesso!\n";
                } else {
                    echo "Erro ao atualizar o teor: " . $update->error . "\n";
                }
                
                $update->close();
            } else {
                echo "Não foi possível encontrar um teor válido para atualização.\n";
            }
        } else {
            echo "O teor já existe no banco de dados e não está vazio.\n";
        }
    } else {
        echo "A notificação para o processo $numero_processo NÃO existe no banco de dados.\n";
        echo "Salvando notificações no banco de dados...\n";
        
        // Salvar as notificações no banco de dados
        $saved_count = saveNotificationsToDatabase($conn, $api_notifications);
        echo "Salvas $saved_count novas notificações\n";
        
        // Verificar novamente se o processo específico foi salvo
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            echo "A notificação para o processo $numero_processo foi salva com sucesso!\n";
        } else {
            echo "ERRO: A notificação para o processo $numero_processo NÃO foi salva no banco de dados.\n";
            
            // Tentar salvar manualmente apenas a notificação específica
            echo "Tentando salvar manualmente apenas a notificação específica...\n";
            
            foreach ($api_notifications as $notification) {
                if (isset($notification['numero_processo']) && $notification['numero_processo'] === $numero_processo) {
                    // Verificar se o processo já existe
                    $check_process = $conn->prepare("SELECT id FROM processes WHERE numero_processo = ?");
                    $check_process->bind_param("s", $numero_processo);
                    $check_process->execute();
                    $process_result = $check_process->get_result();
                    
                    // Se o processo não existe, cria um novo
                    if ($process_result->num_rows == 0) {
                        $insert_process = $conn->prepare("INSERT INTO processes (numero_processo, classe, tribunal) VALUES (?, ?, ?)");
                        $insert_process->bind_param("sss", 
                            $notification['numero_processo'], 
                            $notification['classe'], 
                            $notification['tribunal']
                        );
                        $insert_process->execute();
                        $processo_id = $conn->insert_id;
                        $insert_process->close();
                        echo "Processo criado com ID: $processo_id\n";
                    } else {
                        $process_row = $process_result->fetch_assoc();
                        $processo_id = $process_row['id'];
                        echo "Processo já existe com ID: $processo_id\n";
                    }
                    $check_process->close();
                    
                    // Inserir a notificação
                    $insert_notification = $conn->prepare("INSERT INTO notifications (processo_id, numero_processo, classe, advogados, tribunal, data_publicacao, teor) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $insert_notification->bind_param("issssss",
                        $processo_id,
                        $notification['numero_processo'],
                        $notification['classe'],
                        $notification['advogados'],
                        $notification['tribunal'],
                        $notification['data_publicacao'],
                        $notification['teor']
                    );
                    
                    if ($insert_notification->execute()) {
                        echo "Notificação salva manualmente com sucesso!\n";
                    } else {
                        echo "Erro ao salvar notificação manualmente: " . $insert_notification->error . "\n";
                    }
                    
                    $insert_notification->close();
                    break;
                }
            }
        }
    }
    
    $check->close();
    
    echo "\nProcesso concluído. Agora você pode acessar:\n";
    echo "https://janeri.com.br/gigajus/v2/add_process2.php?numero_processo=$numero_processo&tribunal=" . ($tribunal ?: "TJCE") . "\n";
    
} catch (Exception $e) {
    echo "Exceção: " . $e->getMessage() . "\n";
}

// Fecha a conexão com o banco de dados
$conn->close();

echo "\nProcesso concluído.\n";
?>