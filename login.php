<?php
session_start();
require 'config.php';

// Verificar se o formulário foi enviado
// Verificar se existe um cookie de "lembrar-me"
if (!isset($_SESSION['user_id']) && isset($_COOKIE['gigajus_remember'])) {
    $token = $_COOKIE['gigajus_remember'];
    
    // Verificar o token no banco de dados
    $sql = "SELECT u.* FROM usuarios u
            JOIN user_tokens t ON u.id_usuario = t.user_id
            WHERE t.token = ? AND t.expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Definir variáveis de sessão
        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        
        // Redirecionar para a página solicitada ou para o dashboard
        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
        header("Location: $redirect");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $lembrar = isset($_POST['lembrar']) ? true : false;

    // Preparar a consulta para buscar o usuário
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verificar se o usuário foi encontrado e se a senha está correta
    if ($user && password_verify($senha, $user['senha'])) {
        // Definir variáveis de sessão
        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        
        // Se o usuário marcou "lembrar-me", criar um cookie
        if ($lembrar) {
            // Gerar um token único
            $token = bin2hex(random_bytes(32));
            
            // Definir a data de expiração (30 dias)
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Verificar se a tabela user_tokens existe
            $tableCheck = $conn->query("SHOW TABLES LIKE 'user_tokens'");
            if ($tableCheck->num_rows == 0) {
                // Criar a tabela se não existir
                $conn->query("CREATE TABLE user_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at DATETIME NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES usuarios(id_usuario)
                )");
            }
            
            // Salvar o token no banco de dados
            $sql = "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $user['id_usuario'], $token, $expires);
            $stmt->execute();
            
            // Definir o cookie
            setcookie('gigajus_remember', $token, time() + (86400 * 30), '/', '', false, true); // 30 dias
        }

        // Redirecionar para a página inicial ou dashboard
        echo "<script>showNotification('Boas vindas - Bem vindo ao GigaJus!', 'success');</script>";
        header("Location: index.php");
        exit();
    } else {
        echo "<script>showNotification('Email ou senha incorretos.', 'error');</script>";
        $error = "Email ou senha incorretos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GigaJus</title>
    <link rel="stylesheet" href="assets/css/unified.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <img src="https://i.ibb.co/DgrxVHRC/Logotipo-Giga-Jus.png" alt="GigaJus Logo" class="login-logo">
                <h2>Acesso ao Sistema</h2>
            </div>
            <div class="login-form-container">
                <form method="POST" action="login.php" class="unified-form">
                    <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <div class="input-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="Digite seu email" placeholder-text="Digite seu email">
                    </div>
                    <div class="input-group">
                        <label for="senha"><i class="fas fa-lock"></i> Senha</label>
                        <input type="password" id="senha" name="senha" class="form-control" required placeholder="Digite sua senha" placeholder-text="Digite sua senha">
                    </div>
                    <div class="input-group checkbox-group">
                        <input type="checkbox" id="lembrar" name="lembrar" class="form-check-input">
                        <label for="lembrar" class="form-check-label">Lembrar-me</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Entrar
                    </button>
                </form>
                <div class="login-footer">
                    <a href="forgot_password.php" class="forgot-password-link">Esqueci minha senha</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.body.classList.add('login-body');
        });
        
        // Adicionar estilo para o checkbox
        const style = document.createElement('style');
        style.textContent = `
            .checkbox-group {
                display: flex;
                align-items: center;
                margin-bottom: 15px;
            }
            .form-check-input {
                margin-right: 10px;
                width: 18px;
                height: 18px;
            }
            .form-check-label {
                font-size: 14px;
                cursor: pointer;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
