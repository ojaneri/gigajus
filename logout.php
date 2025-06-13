<?php
session_start();

// Verificar se existe o cookie de "lembrar-me" e removê-lo
if (isset($_COOKIE['gigajus_remember'])) {
    $token = $_COOKIE['gigajus_remember'];
    
    // Conectar ao banco de dados
    require 'config.php';
    
    // Remover o token do banco de dados
    $sql = "DELETE FROM user_tokens WHERE token = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
    }
    
    // Remover o cookie
    setcookie('gigajus_remember', '', time() - 3600, '/'); // Expira no passado
}

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se for necessário matar o cookie da sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login
header("Location: login.php");
exit();
?>