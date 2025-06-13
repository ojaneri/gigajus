<?php
session_start();
require 'config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_processed'])) {
    // Obtém o ID da notificação
    if (isset($_POST['notification_id']) && is_numeric($_POST['notification_id'])) {
        $notification_id = intval($_POST['notification_id']);
        
        // Verifica se a notificação existe e não está processada
        $check_query = "SELECT id, processada FROM notifications WHERE id = ?";
        $check_stmt = $conn->prepare($check_query);
        
        if ($check_stmt) {
            $check_stmt->bind_param("i", $notification_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $notification = $check_result->fetch_assoc();
                
                // Verifica se a notificação já está processada
                if ($notification['processada']) {
                    $check_stmt->close();
                    
                    // Verifica se há um URL de retorno especificado
                    if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
                        // Adiciona o parâmetro de status ao URL de retorno
                        $return_url = $_POST['return_url'];
                        $separator = (strpos($return_url, '?') !== false) ? '&' : '?';
                        header("Location: " . $return_url . $separator . "status=already_processed");
                    } else {
                        // Redireciona para a página de notificações por padrão
                        header("Location: notifications.php?status=already_processed");
                    }
                    exit();
                }
                
                // Verifica se as colunas processed_by e processed_at existem
                $check_columns = $conn->query("SHOW COLUMNS FROM notifications WHERE Field IN ('processed_by', 'processed_at')");
                $columns = [];
                while ($column = $check_columns->fetch_assoc()) {
                    $columns[] = $column['Field'];
                }
                
                // Constrói a query com base nas colunas existentes
                if (count($columns) == 2) {
                    // Ambas as colunas existem
                    $update_query = "UPDATE notifications SET processada = 1, processed_by = ?, processed_at = NOW() WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("ii", $user_id, $notification_id);
                } elseif (in_array('processed_by', $columns)) {
                    // Apenas processed_by existe
                    $update_query = "UPDATE notifications SET processada = 1, processed_by = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("ii", $user_id, $notification_id);
                } elseif (in_array('processed_at', $columns)) {
                    // Apenas processed_at existe
                    $update_query = "UPDATE notifications SET processada = 1, processed_at = NOW() WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("i", $notification_id);
                } else {
                    // Nenhuma das colunas existe
                    $update_query = "UPDATE notifications SET processada = 1 WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("i", $notification_id);
                }
                
                if ($update_stmt) {
                    if ($update_stmt->execute()) {
                        // Verifica se a tabela system_logs existe antes de tentar inserir
                        $check_table = $conn->query("SHOW TABLES LIKE 'system_logs'");
                        if ($check_table->num_rows > 0) {
                            // Verifica se a tabela users existe
                            $check_users_table = $conn->query("SHOW TABLES LIKE 'users'");
                            $users_table_exists = $check_users_table->num_rows > 0;
                            
                            // Verifica se o usuário existe na tabela users (se existir)
                            $user_exists = false;
                            if ($users_table_exists) {
                                $check_user = $conn->prepare("SELECT id FROM users WHERE id = ?");
                                $check_user->bind_param("i", $user_id);
                                $check_user->execute();
                                $user_exists = $check_user->get_result()->num_rows > 0;
                                $check_user->close();
                            }
                            
                            // Só tenta inserir no log se o usuário existir na tabela users
                            if ($user_exists) {
                                // Registra a ação no log do sistema
                                $log_query = "INSERT INTO system_logs (user_id, action, entity_type, entity_id, details)
                                             VALUES (?, 'process', 'notification', ?, 'Notificação marcada como processada')";
                                
                                $log_stmt = $conn->prepare($log_query);
                                if ($log_stmt) {
                                    $log_stmt->bind_param("ii", $user_id, $notification_id);
                                    $log_stmt->execute();
                                    $log_stmt->close();
                                }
                            } else {
                                // Registra que não foi possível inserir no log
                                error_log("Não foi possível registrar no system_logs: usuário ID $user_id não existe na tabela users");
                            }
                        }
                        
                        // Redireciona de volta para a página apropriada com sucesso
                        $update_stmt->close();
                        
                        // Verifica se há um URL de retorno especificado
                        if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
                            // Adiciona o parâmetro de status ao URL de retorno
                            $return_url = $_POST['return_url'];
                            $separator = (strpos($return_url, '?') !== false) ? '&' : '?';
                            header("Location: " . $return_url . $separator . "status=processed");
                        } else {
                            // Redireciona para a página de notificações por padrão
                            header("Location: notifications.php?status=processed");
                        }
                        exit();
                    } else {
                        // Log de erro e redireciona com falha
                        error_log("Erro ao marcar a notificação como processada: " . $update_stmt->error);
                        $update_stmt->close();
                        
                        // Verifica se há um URL de retorno especificado
                        if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
                            // Adiciona o parâmetro de status ao URL de retorno
                            $return_url = $_POST['return_url'];
                            $separator = (strpos($return_url, '?') !== false) ? '&' : '?';
                            header("Location: " . $return_url . $separator . "status=error");
                        } else {
                            // Redireciona para a página de notificações por padrão
                            header("Location: notifications.php?status=error");
                        }
                        exit();
                    }
                } else {
                    // Log de erro e redireciona com falha
                    error_log("Erro na preparação da consulta de atualização: " . $conn->error);
                    
                    // Verifica se há um URL de retorno especificado
                    if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
                        // Adiciona o parâmetro de status ao URL de retorno
                        $return_url = $_POST['return_url'];
                        $separator = (strpos($return_url, '?') !== false) ? '&' : '?';
                        header("Location: " . $return_url . $separator . "status=error");
                    } else {
                        // Redireciona para a página de notificações por padrão
                        header("Location: notifications.php?status=error");
                    }
                    exit();
                }
            } else {
                // Notificação não encontrada
                $check_stmt->close();
                
                // Verifica se há um URL de retorno especificado
                if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
                    // Adiciona o parâmetro de status ao URL de retorno
                    $return_url = $_POST['return_url'];
                    $separator = (strpos($return_url, '?') !== false) ? '&' : '?';
                    header("Location: " . $return_url . $separator . "status=not_found");
                } else {
                    // Redireciona para a página de notificações por padrão
                    header("Location: notifications.php?status=not_found");
                }
                exit();
            }
            
            $check_stmt->close();
        } else {
            // Log de erro e redireciona com falha
            error_log("Erro na preparação da consulta de verificação: " . $conn->error);
            
            // Verifica se há um URL de retorno especificado
            if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
                // Adiciona o parâmetro de status ao URL de retorno
                $return_url = $_POST['return_url'];
                $separator = (strpos($return_url, '?') !== false) ? '&' : '?';
                header("Location: " . $return_url . $separator . "status=error");
            } else {
                // Redireciona para a página de notificações por padrão
                header("Location: notifications.php?status=error");
            }
            exit();
        }
    } else {
        // Redireciona com parâmetro inválido
        // Verifica se há um URL de retorno especificado
        if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
            // Adiciona o parâmetro de status ao URL de retorno
            $return_url = $_POST['return_url'];
            $separator = (strpos($return_url, '?') !== false) ? '&' : '?';
            header("Location: " . $return_url . $separator . "status=invalid_id");
        } else {
            // Redireciona para a página de notificações por padrão
            header("Location: notifications.php?status=invalid_id");
        }
        exit();
    }
} else {
    // Acesso inválido
    // Verifica se há um URL de retorno especificado na query string
    if (isset($_GET['return_url']) && !empty($_GET['return_url'])) {
        // Adiciona o parâmetro de status ao URL de retorno
        $return_url = $_GET['return_url'];
        $separator = (strpos($return_url, '?') !== false) ? '&' : '?';
        header("Location: " . $return_url . $separator . "status=invalid_request");
    } else {
        // Redireciona para a página de notificações por padrão
        header("Location: notifications.php?status=invalid_request");
    }
    exit();
}
?>