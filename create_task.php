<?php
session_start();
require 'config.php';

// Função para registrar mensagens de log
function logDebug($message) {
    file_put_contents('gigajus.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

// Inicia o log para esta requisição
logDebug("Iniciando processamento de create_task.php");

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém e sanitiza os dados do formulário
    $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    $task_title = isset($_POST['task_title']) ? trim($_POST['task_title']) : '';
    $task_description = isset($_POST['task_description']) ? trim($_POST['task_description']) : '';
    $task_user = isset($_POST['task_user']) ? intval($_POST['task_user']) : 0;
    $task_datetime = isset($_POST['task_datetime']) ? trim($_POST['task_datetime']) : '';
    
    // Valida os dados
    $errors = [];
    
    if ($notification_id <= 0) {
        $errors[] = "ID de notificação inválido.";
    }
    
    if (empty($task_title)) {
        $errors[] = "O título da tarefa é obrigatório.";
    }
    
    if (empty($task_description)) {
        $errors[] = "A descrição da tarefa é obrigatória.";
    }
    
    if ($task_user <= 0) {
        $errors[] = "Selecione um usuário responsável válido.";
    }
    
    if (empty($task_datetime)) {
        $errors[] = "A data de vencimento é obrigatória.";
    } else {
        // Verifica se a data é válida e futura
        $scheduled_datetime = new DateTime($task_datetime);
        $now = new DateTime();
        
        if ($scheduled_datetime < $now) {
            $errors[] = "A data de vencimento deve ser futura.";
        }
    }
    
    // Se não houver erros, prossegue com a criação da tarefa
    if (empty($errors)) {
        // Converte a data e hora para o formato adequado
        $scheduled_at = date('Y-m-d H:i:s', strtotime($task_datetime));
        
        // Verifica se a notificação existe
        $check_notification = $conn->prepare("SELECT id, numero_processo, classe, tribunal FROM notifications WHERE id = ?");
        $check_notification->bind_param("i", $notification_id);
        $check_notification->execute();
        $notification_result = $check_notification->get_result();
        
        if ($notification_result->num_rows > 0) {
            $notification = $notification_result->fetch_assoc();
            
            // Prepara a consulta para inserir a tarefa na tabela tarefas
            $insert_task_query = "INSERT INTO tarefas (id_usuario, descricao, descricao_longa, data_horario_final, status)
                                 VALUES (?, ?, ?, ?, 'pendente')";
            $stmt = $conn->prepare($insert_task_query);
            
            if ($stmt) {
                $stmt->bind_param("isss",
                    $task_user,
                    $task_title,
                    $task_description,
                    $scheduled_at
                );
                
                logDebug("Tentando executar a inserção na tabela tarefas: " . $insert_task_query);
                
                if ($stmt->execute()) {
                    $task_id = $conn->insert_id;
                    logDebug("Tarefa criada com sucesso. ID: " . $task_id);
                    
                    // Cria uma iteração para a tarefa
                    $insert_iteration_query = "INSERT INTO tarefa_iteracoes (id_tarefa, id_usuario, descricao)
                                             VALUES (?, ?, ?)";
                    logDebug("Tentando criar iteração para a tarefa: " . $insert_iteration_query);
                    $iteration_stmt = $conn->prepare($insert_iteration_query);
                    
                    if ($iteration_stmt) {
                        $iteration_descricao = "Tarefa criada a partir da notificação #{$notification_id}";
                        $iteration_stmt->bind_param("iis",
                            $task_id,
                            $current_user_id,
                            $iteration_descricao
                        );
                        
                        if ($iteration_stmt->execute()) {
                            logDebug("Iteração criada com sucesso para a tarefa ID: " . $task_id);
                        } else {
                            logDebug("ERRO ao criar iteração: " . $iteration_stmt->error);
                        }
                        $iteration_stmt->close();
                    }
                    
                    // Registra a ação no log do sistema (comentado devido a problemas de foreign key)
                    // $log_query = "INSERT INTO system_logs (user_id, action, entity_type, entity_id, details)
                    //              VALUES (?, 'create', 'task', ?, ?)";
                    
                    // $log_details = "Tarefa criada para a notificação do processo {$notification['numero_processo']}";
                    // $log_stmt = $conn->prepare($log_query);
                    
                    // if ($log_stmt) {
                    //     $log_stmt->bind_param("iis", $current_user_id, $task_id, $log_details);
                    //     $log_stmt->execute();
                    //     $log_stmt->close();
                    // }
                    
                    // Verifica se deve marcar a notificação como processada
                    if (isset($_POST['mark_processed']) && $_POST['mark_processed'] == 1) {
                        logDebug("Marcando notificação ID: " . $notification_id . " como processada");
                        $update_notification = $conn->prepare("UPDATE notifications SET processada = 1, processed_by = ?, processed_at = NOW() WHERE id = ?");
                        $update_notification->bind_param("ii", $current_user_id, $notification_id);
                        
                        if ($update_notification->execute()) {
                            logDebug("Notificação marcada como processada com sucesso");
                        } else {
                            logDebug("ERRO ao marcar notificação como processada: " . $update_notification->error);
                        }
                        $update_notification->close();
                    }
                    
                    // Envia notificação ao usuário responsável se não for o usuário atual
                    if ($task_user != $current_user_id) {
                        // Obtém informações do usuário responsável
                        logDebug("Buscando informações do usuário ID: " . $task_user . " para envio de notificação");
                        $user_query = $conn->prepare("SELECT email, nome as name FROM usuarios WHERE id_usuario = ?");
                        $user_query->bind_param("i", $task_user);
                        
                        if ($user_query->execute()) {
                            $user_result = $user_query->get_result();
                            logDebug("Informações do usuário obtidas com sucesso");
                        } else {
                            logDebug("ERRO ao buscar informações do usuário: " . $user_query->error);
                            $user_result = false;
                        }
                        
                        if ($user_result->num_rows > 0) {
                            $user = $user_result->fetch_assoc();
                            
                            // Prepara a mensagem de notificação
                            $email_subject = "Nova tarefa atribuída: {$task_title}";
                            $email_message = "Olá {$user['name']},\n\n";
                            $email_message .= "Uma nova tarefa foi atribuída a você:\n\n";
                            $email_message .= "Título: {$task_title}\n";
                            $email_message .= "Descrição: {$task_description}\n";
                            $email_message .= "Processo: {$notification['numero_processo']} ({$notification['classe']})\n";
                            $email_message .= "Tribunal: {$notification['tribunal']}\n";
                            $email_message .= "Data de vencimento: " . date('d/m/Y H:i', strtotime($scheduled_at)) . "\n\n";
                            $email_message .= "Acesse o sistema para mais detalhes.\n\n";
                            $email_message .=  "Atenciosamente,\nSistema de Gerenciamento de Intimações";
                            
                            // Envia a notificação por email
                            if (function_exists('sendNotification')) {
                                logDebug("Enviando notificação por email para: " . $user['email']);
                                $result = sendNotification('email', $user['email'], $email_message, $email_subject);
                                logDebug("Resultado do envio de notificação: " . ($result ? "Sucesso" : "Falha"));
                            } else {
                                logDebug("Função sendNotification não existe");
                            }
                        }
                        $user_query->close();
                    }
                    
                    // Redireciona de volta para a página apropriada com sucesso
                    $stmt->close();
                    
                    // Verifica se há um URL de retorno especificado
                    if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
                        // Adiciona o parâmetro de status ao URL de retorno
                        $return_url = $_POST['return_url'];
                        $separator = (strpos($return_url, '?') !== false) ? '&' : '?';
                        $redirect_url = $return_url . $separator . "status=task_created";
                        logDebug("Redirecionando para URL de retorno: " . $redirect_url);
                        header("Location: " . $redirect_url);
                    } else {
                        // Redireciona para a página de notificações por padrão
                        logDebug("Redirecionando para notifications.php com status=task_created");
                        header("Location: notifications.php?status=task_created");
                    }
                    exit();
                } else {
                    // Log de erro e redireciona com falha
                    $error_msg = "Erro ao criar a tarefa: " . $stmt->error;
                    logDebug("ERRO: " . $error_msg);
                    error_log($error_msg);
                    $errors[] = $error_msg;
                }
                
                $stmt->close();
            } else {
                // Log de erro e redireciona com falha
                $error_msg = "Erro na preparação da consulta: " . $conn->error;
                logDebug("ERRO: " . $error_msg);
                error_log($error_msg);
                $errors[] = $error_msg;
            }
        } else {
            $error_msg = "Notificação não encontrada. ID: " . $notification_id;
            logDebug("ERRO: " . $error_msg);
            $errors[] = $error_msg;
        }
        $check_notification->close();
    }
    
    // Se chegou até aqui, houve erros
    $error_message = implode("<br>", $errors);
    logDebug("Erros encontrados: " . $error_message);
    
    // Verifica se há um URL de retorno especificado
    if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
        // Adiciona o parâmetro de status ao URL de retorno
        $return_url = $_POST['return_url'];
        $separator = (strpos($return_url, '?') !== false) ? '&' : '?';
        $redirect_url = $return_url . $separator . "status=error&message=" . urlencode($error_message);
        logDebug("Redirecionando para URL de retorno com erro: " . $redirect_url);
        header("Location: " . $redirect_url);
    } else {
        // Redireciona para a página de notificações por padrão
        $redirect_url = "notifications.php?status=error&message=" . urlencode($error_message);
        logDebug("Redirecionando para notifications.php com erro: " . $redirect_url);
        header("Location: " . $redirect_url);
    }
    exit();
} else {
    // Acesso inválido
    // Verifica se há um URL de retorno especificado na query string
    if (isset($_GET['return_url']) && !empty($_GET['return_url'])) {
        // Adiciona o parâmetro de status ao URL de retorno
        $return_url = $_GET['return_url'];
        $separator = (strpos($return_url, '?') !== false) ? '&' : '?';
        $redirect_url = $return_url . $separator . "status=invalid_request";
        logDebug("Método inválido. Redirecionando para URL de retorno: " . $redirect_url);
        header("Location: " . $redirect_url);
    } else {
        // Redireciona para a página de notificações por padrão
        logDebug("Método inválido. Redirecionando para notifications.php");
        header("Location: notifications.php?status=invalid_request");
    }
    exit();
}

// Finaliza o log para esta requisição
logDebug("Finalizando processamento de create_task.php");
?>
