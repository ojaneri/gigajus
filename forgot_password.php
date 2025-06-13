<?php
session_start();
require 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Verificar se o email existe no banco de dados
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Gerar token seguro
        $token = bin2hex(random_bytes(50));
        $expiracao = date('Y-m-d H:i:s', strtotime('+15 minutes')); // Validade de 15 minutos

        // Inserir token e data de expiração na tabela de recuperação de senha
        $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $email, $token, $expiracao);
        $stmt->execute();

        // Enviar email com link de redefinição de senha
        $reset_link = "https://janeri.com.br/gigajus/v2/reset_password.php?token=" . $token;
        $message = "Clique no link para redefinir sua senha: <a href='" . $reset_link . "'>" . $reset_link . "</a>";
        mail($email, "Redefinição de senha", $message, "From: no-reply@janeri.com.br");

        $message = "Um link de redefinição de senha foi enviado para seu email.";
    } else {
        $message = "Email não encontrado.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci minha senha - Sistema GigaJus</title>
    <link rel="stylesheet" href="assets/css/unified.css">
</head>
<body>
    <div class="login-container">
        <h1>Esqueci minha senha</h1>
        <form method="POST" action="forgot_password.php">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" name="email" required>
            </div>
            <button type="submit">Enviar</button>
        </form>
        <?php if ($message): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
