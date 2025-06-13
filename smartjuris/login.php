<?php
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    include 'functions/functions_auth.php';  // Ensure this is included here
    include 'functions/functions_bd.php';  // Needed for any database operations in auth
    
    if (authenticateUser($email, $senha)) {
        session_start();
        $_SESSION['user_id'] = 1;  // Set session based on user
        header('Location: index.php');
        exit();
    } else {
        echo "Login falhou.";
    }
}
?>

<main>
    <h2>Login</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="senha" placeholder="Senha" required><br>
        <button type="submit">Entrar</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>