<?php
/**
 * delete_client.php
 * Script para excluir permanentemente um cliente e seus arquivos
 * Autor: Kilo Code
 * Data: 2025-04-29
 */

session_start();
require 'config.php';
include 'header.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar se o usuário é admin
$admin_query = "SELECT permissoes FROM usuarios WHERE id_usuario = ?";
$stmt = mysqli_prepare($conn, $admin_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);

// Verificar se o usuário tem permissões de admin
$is_admin = false;
if ($user_data && isset($user_data['permissoes'])) {
    $permissoes = json_decode($user_data['permissoes'], true);
    $is_admin = isset($permissoes['admin']) && $permissoes['admin'] === true;
}

if (!$is_admin) {
    echo "<script>showNotification('Apenas administradores podem excluir clientes.', 'error');</script>";
    echo '<script>
            setTimeout(function() {
                window.location.href = "clients.php";
            }, 1500);
          </script>';
    exit();
}

// Verificar se o ID do cliente e a confirmação foram fornecidos
if (!isset($_POST['id']) || !isset($_POST['confirm']) || $_POST['confirm'] !== 'true') {
    echo "<script>showNotification('Parâmetros inválidos para exclusão.', 'error');</script>";
    echo '<script>
            setTimeout(function() {
                window.location.href = "clients.php";
            }, 1500);
          </script>';
    exit();
}

$id = $_POST['id'];

// Obter informações do cliente antes de excluí-lo
$sql = "SELECT nome FROM clientes WHERE id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();

if (!$cliente) {
    echo "<script>showNotification('Cliente não encontrado.', 'error');</script>";
    echo '<script>
            setTimeout(function() {
                window.location.href = "clients.php";
            }, 1500);
          </script>';
    exit();
}

// Registrar a ação no log
logMessage("Administrador {$_SESSION['user_id']} solicitou a exclusão permanente do cliente ID $id: {$cliente['nome']}", 'WARNING');

// Encontrar e excluir a pasta do cliente
$folderName = preg_replace('/[^a-zA-Z0-9\s]/', '', $cliente['nome']); // Remove caracteres especiais
$folderName = trim($folderName); // Remove espaços extras
$folderName = "$folderName (ID $id)";
$uploadDir = "uploads/clientes/$folderName/";

// Função recursiva para excluir diretório e seu conteúdo
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

// Excluir a pasta do cliente
if (file_exists($uploadDir)) {
    if (deleteDirectory($uploadDir)) {
        logMessage("Pasta do cliente excluída com sucesso: $uploadDir", 'INFO');
    } else {
        logMessage("Erro ao excluir a pasta do cliente: $uploadDir", 'ERROR');
    }
}

// Excluir registros relacionados ao cliente
// 1. Excluir processos
$sql = "DELETE FROM processos WHERE id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$processos_excluidos = $stmt->affected_rows;
logMessage("$processos_excluidos processos excluídos para o cliente ID $id", 'INFO');

// 2. Excluir atendimentos
$sql = "DELETE FROM atendimentos WHERE id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$atendimentos_excluidos = $stmt->affected_rows;
logMessage("$atendimentos_excluidos atendimentos excluídos para o cliente ID $id", 'INFO');

// 3. Excluir tarefas
$sql = "DELETE FROM tarefas WHERE id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$tarefas_excluidas = $stmt->affected_rows;
logMessage("$tarefas_excluidas tarefas excluídas para o cliente ID $id", 'INFO');

// 4. Excluir o cliente
$sql = "DELETE FROM clientes WHERE id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    logMessage("Cliente ID $id excluído permanentemente", 'WARNING');
    echo "<script>showNotification('Cliente excluído permanentemente com sucesso.', 'success');</script>";
} else {
    logMessage("Erro ao excluir cliente ID $id: " . $conn->error, 'ERROR');
    echo "<script>showNotification('Erro ao excluir cliente: " . $conn->error . "', 'error');</script>";
}

echo '<script>
        setTimeout(function() {
            window.location.href = "clients.php";
        }, 1500);
      </script>';
exit();
?>