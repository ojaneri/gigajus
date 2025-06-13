<?php
session_start();
require 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $nova_senha = $_POST['nova_senha'];

    // Verificar se o token é válido
    $sql = "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset_request = $result->fetch_assoc();

    if ($reset_request) {
        // Atualizar a senha do usuário
        $email = $reset_request['email'];
        $hashed_password = password_hash($nova_senha, PASSWORD_DEFAULT);

        $sql = "UPDATE usuarios SET senha = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashed_password, $email);
        $stmt->execute();

        // Remover o token de recuperação
        $sql = "DELETE FROM password_resets WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $message = "Senha redefinida com sucesso.";
    } else {
        $message = "Token inválido ou expirado.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha - Sistema GigaJus</title>
    <link rel="stylesheet" href="assets/css/unified.css">
</head>
<body>
    <div class="login-container">
        <h1>Redefinir senha</h1>
        <form method="POST" action="reset_password.php">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
            <div class="input-group">
                <label for="nova_senha">Nova senha</label>
                <input type="password" name="nova_senha" required>
            </div>
            <button type="submit">Redefinir senha</button>
        </form>
        <?php if ($message): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
