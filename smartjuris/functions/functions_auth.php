<?php
function authenticateUser($email, $senha) {
    include 'functions/functions_bd.php';  // Include for database access
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($senha, $user['senha'])) {  // Assuming passwords are hashed; use plain for now as per feedback
            return true;  // Or return user data
        }
        return false;
    } catch (Exception $e) {
        return false;  // Handle error
    }
}
?>