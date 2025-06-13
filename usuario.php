#!/usr/bin/php
<?php
/**
 * Script de linha de comando para gerenciamento de usuários
 * 
 * Uso:
 * php usuario.php -usuarios     # Listar todos os usuários
 * php usuario.php -admin        # Listar todos os administradores
 * php usuario.php -promover ID  # Promover usuário para administrador
 * php usuario.php -novo "Nome" "email@exemplo.com" "senha" "cargo"  # Criar novo usuário
 */

// Carregar configurações e conexão com o banco de dados
require_once __DIR__ . '/config.php';

// Função para exibir mensagens coloridas no console
function console_output($message, $type = 'info') {
    $colors = [
        'info' => "\033[0;36m",    // Ciano
        'success' => "\033[0;32m",  // Verde
        'warning' => "\033[0;33m",  // Amarelo
        'error' => "\033[0;31m",    // Vermelho
        'reset' => "\033[0m"        // Reset
    ];
    
    echo $colors[$type] . $message . $colors['reset'] . PHP_EOL;
}

// Função para exibir ajuda
function show_help() {
    console_output("Uso do script de gerenciamento de usuários:", 'info');
    console_output("php usuario.php -usuarios     # Listar todos os usuários", 'info');
    console_output("php usuario.php -admin        # Listar todos os administradores", 'info');
    console_output("php usuario.php -promover ID  # Promover usuário para administrador", 'info');
    console_output("php usuario.php -novo \"Nome\" \"email@exemplo.com\" \"senha\" \"cargo\"  # Criar novo usuário", 'info');
    exit(0);
}

// Função para listar usuários
function list_users($conn, $admin_only = false) {
    // Preparar a consulta
    if ($admin_only) {
        $query = "SELECT id_usuario, nome, email, cargo, ativo FROM usuarios 
                 WHERE JSON_EXTRACT(permissoes, '$.admin') = true 
                 ORDER BY nome";
        console_output("=== LISTA DE ADMINISTRADORES ===", 'info');
    } else {
        $query = "SELECT id_usuario, nome, email, cargo, permissoes, ativo FROM usuarios ORDER BY nome";
        console_output("=== LISTA DE USUÁRIOS ===", 'info');
    }
    
    $result = $conn->query($query);
    
    if ($result->num_rows == 0) {
        console_output("Nenhum " . ($admin_only ? "administrador" : "usuário") . " encontrado.", 'warning');
        return;
    }
    
    // Definir formato da tabela
    $format = "| %-5s | %-30s | %-30s | %-20s | %-10s | %-8s |\n";
    
    // Imprimir cabeçalho
    printf($format, "ID", "Nome", "Email", "Cargo", "Admin", "Status");
    console_output(str_repeat("-", 115), 'info');
    
    // Imprimir dados
    while ($row = $result->fetch_assoc()) {
        $permissoes = json_decode($row['permissoes'] ?? '{}', true);
        $is_admin = isset($permissoes['admin']) && $permissoes['admin'] === true ? "Sim" : "Não";
        $status = $row['ativo'] ? "Ativo" : "Inativo";
        
        printf($format, 
            $row['id_usuario'],
            substr($row['nome'], 0, 28) . (strlen($row['nome']) > 28 ? "..." : ""),
            substr($row['email'], 0, 28) . (strlen($row['email']) > 28 ? "..." : ""),
            substr($row['cargo'], 0, 18) . (strlen($row['cargo']) > 18 ? "..." : ""),
            $is_admin,
            $status
        );
    }
}

// Função para promover usuário para administrador
function promote_user($conn, $user_id) {
    // Verificar se o usuário existe
    $check_query = "SELECT id_usuario, nome, email, permissoes FROM usuarios WHERE id_usuario = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        console_output("Usuário com ID $user_id não encontrado.", 'error');
        return;
    }
    
    $user = $result->fetch_assoc();
    $permissoes = json_decode($user['permissoes'] ?? '{}', true);
    
    // Verificar se já é admin
    if (isset($permissoes['admin']) && $permissoes['admin'] === true) {
        console_output("O usuário {$user['nome']} já é administrador.", 'warning');
        return;
    }
    
    // Atualizar permissões
    $permissoes['admin'] = true;
    $permissoes_json = json_encode($permissoes);
    
    $update_query = "UPDATE usuarios SET permissoes = ? WHERE id_usuario = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $permissoes_json, $user_id);
    
    if ($stmt->execute()) {
        console_output("Usuário {$user['nome']} promovido a administrador com sucesso!", 'success');
    } else {
        console_output("Erro ao promover usuário: " . $stmt->error, 'error');
    }
}

// Função para criar novo usuário
function create_user($conn, $nome, $email, $senha, $cargo) {
    // Verificar se o email já existe
    $check_query = "SELECT id_usuario FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        console_output("Já existe um usuário com o email $email.", 'error');
        return;
    }
    
    // Hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Definir permissões (usuário comum)
    $permissoes = json_encode(['admin' => false]);
    
    // Inserir usuário
    $insert_query = "INSERT INTO usuarios (nome, email, senha, cargo, permissoes) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sssss", $nome, $email, $senha_hash, $cargo, $permissoes);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        console_output("Usuário $nome criado com sucesso! ID: $user_id", 'success');
    } else {
        console_output("Erro ao criar usuário: " . $stmt->error, 'error');
    }
}

// Verificar argumentos
if ($argc < 2) {
    show_help();
}

// Processar argumentos
switch ($argv[1]) {
    case '-usuarios':
        list_users($conn, false);
        break;
        
    case '-admin':
        list_users($conn, true);
        break;
        
    case '-promover':
        if ($argc < 3) {
            console_output("Erro: ID do usuário não fornecido.", 'error');
            show_help();
        }
        promote_user($conn, intval($argv[2]));
        break;
        
    case '-novo':
        if ($argc < 6) {
            console_output("Erro: Informações insuficientes para criar usuário.", 'error');
            console_output("Uso: php usuario.php -novo \"Nome\" \"email@exemplo.com\" \"senha\" \"cargo\"", 'info');
            exit(1);
        }
        create_user($conn, $argv[2], $argv[3], $argv[4], $argv[5]);
        break;
        
    case '-help':
    case '--help':
    case '-h':
        show_help();
        break;
        
    default:
        console_output("Opção desconhecida: {$argv[1]}", 'error');
        show_help();
}

// Fechar conexão
$conn->close();