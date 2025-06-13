<?php
session_start();
require 'config.php';
include 'header.php';

// Adicionar CSS específico para a página de perfil
echo '<link rel="stylesheet" href="assets/css/profile.css">';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $telefone = $_POST['telefone'];
        $theme = $_POST['theme'];
        
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Update user data
            $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, theme = ? WHERE id_usuario = ?");
            $stmt->bind_param("ssssi", $nome, $email, $telefone, $theme, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                // Update session data
                $_SESSION['theme'] = $theme;
                $_SESSION['nome'] = $nome;
                
                // Commit transaction
                $conn->commit();
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                
                $success_message = "Perfil atualizado com sucesso!";
                
                // Log the action
                logMessage("Usuário {$_SESSION['user_id']} atualizou seu perfil");
            } else {
                throw new Exception("Erro ao atualizar perfil");
            }
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Erro ao atualizar perfil: " . $e->getMessage();
            logMessage("Erro ao atualizar perfil do usuário {$_SESSION['user_id']}: " . $e->getMessage());
        }
        
        $stmt->close();
    }
    else if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id_usuario = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $stmt->close();
            
            if (password_verify($current_password, $user_data['senha'])) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id_usuario = ?");
                    $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $success_message = "Senha atualizada com sucesso!";
                        logMessage("Usuário {$_SESSION['user_id']} alterou sua senha");
                    } else {
                        throw new Exception("Erro ao atualizar senha");
                    }
                    $stmt->close();
                } else {
                    throw new Exception("As novas senhas não coincidem");
                }
            } else {
                throw new Exception("Senha atual incorreta");
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            logMessage("Erro ao alterar senha do usuário {$_SESSION['user_id']}: " . $e->getMessage());
        }
    }
}

// Apply theme immediately after saving
$currentTheme = $user['theme'] ?? 'law';
echo "<script>document.documentElement.setAttribute('data-theme', '$currentTheme');</script>";
?>

    <div class="profile-container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" id="success-alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error" id="error-alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-section">
            <h3>Informações Pessoais</h3>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="tel" name="telefone" class="form-control" value="<?php echo htmlspecialchars($user['telefone']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Tema</label>
                    <div class="theme-options">
                        <label class="theme-option <?php echo $user['theme'] === 'light' ? 'selected' : ''; ?>">
                            <input type="radio" name="theme" value="light" <?php echo $user['theme'] === 'light' ? 'checked' : ''; ?>>
                            <strong>Tema Claro</strong>
                            <p>Interface clara e moderna</p>
                        </label>
                        
                        <label class="theme-option <?php echo $user['theme'] === 'dark' ? 'selected' : ''; ?>">
                            <input type="radio" name="theme" value="dark" <?php echo $user['theme'] === 'dark' ? 'checked' : ''; ?>>
                            <strong>Tema Escuro</strong>
                            <p>Interface escura para menor fadiga visual</p>
                        </label>
                        
                        <label class="theme-option <?php echo $user['theme'] === 'law' ? 'selected' : ''; ?>">
                            <input type="radio" name="theme" value="law" <?php echo $user['theme'] === 'law' ? 'checked' : ''; ?>>
                            <strong>Tema Direito</strong>
                            <p>Interface profissional para área jurídica</p>
                        </label>
                    </div>
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">Salvar Alterações</button>
            </form>
        </div>
        
        <div class="profile-section">
            <h3>Alterar Senha</h3>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Senha Atual</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nova Senha</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirmar Nova Senha</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" name="change_password" class="btn btn-primary">Alterar Senha</button>
            </form>
        </div>
    </div>

<script>
// Handle theme selection
document.querySelectorAll('input[name="theme"]').forEach(input => {
    input.addEventListener('change', function() {
        document.querySelectorAll('.theme-option').forEach(option => {
            option.classList.remove('selected');
        });
        this.closest('.theme-option').classList.add('selected');
        
        // Apply theme immediately
        document.documentElement.setAttribute('data-theme', this.value);
    });
});

// Auto-hide alerts after 5 seconds
const alerts = document.querySelectorAll('.alert');
if (alerts.length > 0) {
    setTimeout(() => {
        alerts.forEach(alert => {
            alert.classList.add('fade-out');
            setTimeout(() => alert.style.display = 'none', 500);
        });
    }, 5000);
}
</script>